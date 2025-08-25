<?php
/**
 * API Endpoint để tạo sản phẩm mới trong ECShop
 *
 * @version     2.2.0 - Sửa lỗi đường dẫn tuyệt đối và thêm các bước kiểm tra môi trường.
 */

// --- BẬT BÁO LỖI ĐỂ DEBUG ---
// Ghi chú: Sau khi API hoạt động ổn định, bạn nên xóa hoặc comment 2 dòng này lại.
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('IN_ECS', true);

// --- CẤU HÌNH ---
define('API_SECRET_KEY', 'a91f2c47e5d8b6f03a7c4e9d12f0b8a6'); // <-- GIỮ NGUYÊN KEY CỦA BẠN

// --- KHỞI TẠO MÔI TRƯỜNG ECSHOP (SỬA LẠI ĐƯỜNG DẪN) ---
// Định nghĩa đường dẫn gốc của website một cách tường minh
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__) . '/../');
}
require(ROOT_PATH . 'includes/init.php');
require_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_goods.php');
include_once(ROOT_PATH . 'includes/cls_image.php');

// --- BẮT ĐẦU XỬ LÝ API ---
header('Content-Type: application/json; charset=utf-8');

// Thêm bước kiểm tra quan trọng: Môi trường ECShop đã được khởi tạo đúng chưa?
if (!isset($db) || !isset($ecs) || !isset($_CFG)) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Khởi tạo môi trường ECShop thất bại. Vui lòng kiểm tra lại cấu hình đường dẫn trong file API.']);
    exit;
}

// 1. --- Kiểm tra bảo mật: Xác thực yêu cầu ---
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
if ($api_key !== API_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Xác thực thất bại. API Key không hợp lệ.']);
    exit;
}

// 2. --- Kiểm tra phương thức: Chỉ cho phép POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Phương thức yêu cầu không hợp lệ. Chỉ hỗ trợ POST.']);
    exit;
}

// 3. --- Nhận dữ liệu: Lấy dữ liệu JSON từ body của request ---
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Định dạng JSON không hợp lệ.']);
    exit;
}

// 4. --- Kiểm tra dữ liệu đầu vào: Kiểm tra các trường bắt buộc ---
$required_fields = ['goods_name', 'cat_id', 'shop_price', 'goods_number'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => "Thiếu trường bắt buộc: '$field'"]);
        exit;
    }
}

// 5. --- Xử lý dữ liệu ---
try {
    $image = new cls_image($_CFG['bgcolor']);
    
    // Lọc và chuẩn bị các biến
    $goods_name     = isset($data['goods_name']) ? trim($data['goods_name']) : '';
    $cat_id         = isset($data['cat_id']) ? intval($data['cat_id']) : 0;
    $brand_id       = isset($data['brand_id']) ? intval($data['brand_id']) : 0;
    $shop_price     = isset($data['shop_price']) ? floatval($data['shop_price']) : 0;
    $market_price   = isset($data['market_price']) ? floatval($data['market_price']) : $shop_price * $_CFG['market_price_rate'];
    $deal_price     = isset($data['deal_price']) ? floatval($data['deal_price']) : 0;
    $partner_price  = isset($data['partner_price']) ? floatval($data['partner_price']) : 0;
    $goods_number   = isset($data['goods_number']) ? intval($data['goods_number']) : $_CFG['default_storage'];
    $goods_weight   = isset($data['goods_weight']) ? floatval($data['goods_weight']) : 0;
    $goods_desc     = isset($data['goods_desc']) ? trim($data['goods_desc']) : '';
    $goods_brief    = isset($data['goods_brief']) ? trim($data['goods_brief']) : '';
    $keywords       = isset($data['keywords']) ? trim($data['keywords']) : '';
    $meta_title     = isset($data['meta_title']) ? trim($data['meta_title']) : $goods_name;
    $meta_desc      = isset($data['meta_desc']) ? trim($data['meta_desc']) : '';
    $seller_note    = isset($data['seller_note']) ? trim($data['seller_note']) : '';
    
    if (empty($data['goods_sn'])) {
        $max_id = $db->getOne("SELECT MAX(goods_id) + 1 FROM " . $ecs->table('goods'));
        $goods_sn = generate_goods_sn($max_id);
    } else {
        $goods_sn = trim($data['goods_sn']);
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('goods') . " WHERE goods_sn = '" . $GLOBALS['db']->escape($goods_sn) . "' AND is_delete = 0";
        if ($db->getOne($sql) > 0) {
            throw new Exception('Mã sản phẩm (goods_sn) đã tồn tại.');
        }
    }

    $is_on_sale = isset($data['is_on_sale']) ? intval($data['is_on_sale']) : 1;
    $is_best    = isset($data['is_best']) ? intval($data['is_best']) : 0;
    $is_new     = isset($data['is_new']) ? intval($data['is_new']) : 0;
    $is_hot     = isset($data['is_hot']) ? intval($data['is_hot']) : 0;
    $add_time   = time();
    $last_update = time();

    // Xử lý hình ảnh từ đường dẫn
    $original_img_path = isset($data['original_img']) && !empty($data['original_img']) ? trim($data['original_img']) : '';
    $goods_img    = '';
    $goods_thumb  = '';

    if ($original_img_path && file_exists(ROOT_PATH . $original_img_path)) {
        $slug = build_slug($goods_name);
        
        // Xác định thư mục đích dựa trên cấu trúc của ECShop
        $img_date_folder = date('Ym');
        $goods_img_dir = ROOT_PATH . IMAGE_DIR . '/' . $img_date_folder . '/goods_img/';
        $thumb_img_dir = ROOT_PATH . IMAGE_DIR . '/' . $img_date_folder . '/thumb_img/';

        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!is_dir($goods_img_dir)) { mkdir($goods_img_dir, 0777, true); }
        if (!is_dir($thumb_img_dir)) { mkdir($thumb_img_dir, 0777, true); }

        $filename_thumb = $slug . '-thumb-' . time();
        $filename_img   = $slug . '-G-' . time();

        // Tạo ảnh sản phẩm và thumbnail
        $goods_img = $image->make_thumb(ROOT_PATH . $original_img_path, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'], $goods_img_dir, '', $filename_img);
        $goods_thumb = $image->make_thumb(ROOT_PATH . $original_img_path, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], $thumb_img_dir, '', $filename_thumb);

        if ($goods_img === false || $goods_thumb === false) {
             throw new Exception('Tạo ảnh thất bại. Lỗi: ' . $image->error_msg());
        }
    }

    // 6. --- Thêm vào CSDL ---
    $sql = "INSERT INTO " . $ecs->table('goods') . " (
                goods_name, goods_sn, cat_id, brand_id, shop_price, market_price, deal_price, partner_price,
                goods_number, goods_weight, goods_desc, goods_brief, keywords, meta_title, meta_desc, seller_note,
                is_on_sale, is_best, is_new, is_hot, add_time, last_update, goods_img, goods_thumb, original_img
            ) VALUES (
                '" . $GLOBALS['db']->escape($goods_name) . "', '" . $GLOBALS['db']->escape($goods_sn) . "', '$cat_id', 
                '$brand_id', '$shop_price', '$market_price', '$deal_price', '$partner_price', '$goods_number', 
                '$goods_weight', '" . $GLOBALS['db']->escape($goods_desc) . "', '" . $GLOBALS['db']->escape($goods_brief) . "', 
                '" . $GLOBALS['db']->escape($keywords) . "', '" . $GLOBALS['db']->escape($meta_title) . "', 
                '" . $GLOBALS['db']->escape($meta_desc) . "', '" . $GLOBALS['db']->escape($seller_note) . "',
                '$is_on_sale', '$is_best', '$is_new', '$is_hot', '$add_time', '$last_update',
                '" . $GLOBALS['db']->escape($goods_img) . "', '" . $GLOBALS['db']->escape($goods_thumb) . "', '" . $GLOBALS['db']->escape($original_img_path) . "'
            )";

    $db->query($sql);
    $goods_id = $db->insert_id();

    if ($goods_id > 0) {
        // Xử lý album ảnh (nếu có)
        // ... (logic xử lý album ảnh có thể thêm vào đây)

        clear_cache_files();

        // 7. --- Phản hồi thành công ---
        http_response_code(201);
        echo json_encode([
            'error' => false,
            'message' => 'Sản phẩm đã được tạo thành công.',
            'goods_id' => $goods_id,
            'goods_sn' => $goods_sn
        ]);
    } else {
        throw new Exception('Không thể thêm sản phẩm vào CSDL. Lỗi: ' . $db->errorMsg());
    }

} catch (Exception $e) {
    // 8. --- Phản hồi lỗi ---
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

?>
