<?php
/**
 * FILE API HOÀN CHỈNH - PHIÊN BẢN CUỐI CÙNG
 * API Endpoint ECShop dành cho n8n - Tích hợp đầy đủ tính năng.
 */

// ===================================================================
// SECTION 1: KHỞI TẠO MÔI TRƯỜNG & HÀM CHUNG
// ===================================================================

ob_start();
@ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('IN_ECS')) { define('IN_ECS', true); }
if (!defined('DEBUG_MODE')) { define('DEBUG_MODE', 0); }
if (!defined('ROOT_PATH')) { define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . '/'); }

require_once(ROOT_PATH . 'includes/config.php');
require_once(ROOT_PATH . 'includes/inc_constant.php');
require_once(ROOT_PATH . 'includes/cls_mysql.php');
require_once(ROOT_PATH . 'includes/cls_ecshop.php');
require_once(ROOT_PATH . 'includes/lib_main.php');
require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/lib_time.php');
require_once(ROOT_PATH . 'includes/lib_base.php');
require_once(ROOT_PATH . 'includes/lib_ecshopvietnam.php');
require_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_main.php');
require_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/lib_article.php');
include_once(ROOT_PATH . 'includes/cls_image.php');

$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$ecs = new ECS($db_name, $prefix);
$_CFG = load_config();
date_default_timezone_set('Asia/Ho_Chi_Minh');

function send_json_response($data, $http_code = 200) {
    ob_clean();
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Hàm xử lý ảnh tùy chỉnh (tạo thumb và ảnh lớn dưới định dạng WebP).
 * LƯU Ý: Hàm này phải được định nghĩa BÊN NGOÀI create_product.
 */
function create_resized_image_webp($source_path, $dest_folder, $new_filename_without_ext, $max_width, $max_height) {
    $source_info = getimagesize($source_path); if (!$source_info) return false;
    $width = $source_info[0]; $height = $source_info[1]; $mime = $source_info['mime'];
    switch ($mime) {
        case 'image/jpeg': $source_image = imagecreatefromjpeg($source_path); break;
        case 'image/gif':  $source_image = imagecreatefromgif($source_path); break;
        case 'image/png':  $source_image = imagecreatefrompng($source_path); break;
        case 'image/webp': $source_image = imagecreatefromwebp($source_path); break;
        default: return false;
    }
    if (!$source_image) return false;
    $ratio = $width / $height;
    if ($max_width / $max_height > $ratio) { $new_width = $max_height * $ratio; $new_height = $max_height; } 
    else { $new_height = $max_width / $ratio; $new_width = $max_width; }
    $dest_image = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($dest_image, false); imagesavealpha($dest_image, true);
    $transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
    imagefilledrectangle($dest_image, 0, 0, $new_width, $new_height, $transparent);
    imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    $dest_path_with_ext = $dest_folder . $new_filename_without_ext . '.webp';
    imagewebp($dest_image, $dest_path_with_ext, 80);
    imagedestroy($source_image); imagedestroy($dest_image);
    return str_replace(ROOT_PATH, '', $dest_path_with_ext);
}

// ===================================================================
// SECTION 2: ĐIỀU PHỐI (ROUTER) YÊU CẦU API
// ===================================================================

define('API_SECRET_KEY', 'a91f2c47e5d8b6f03a7c4e9d12f0b8a6');
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($api_key !== API_SECRET_KEY) {
    send_json_response(['error' => true, 'message' => 'Xác thực thất bại. API Key không hợp lệ.'], 401);
}

// Kiểm tra phương thức HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['error' => true, 'message' => 'Phương thức yêu cầu không hợp lệ. Chỉ hỗ trợ POST.'], 405);
}

// Lấy dữ liệu từ body của request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(['error' => true, 'message' => 'Định dạng JSON không hợp lệ.'], 400);
}

// Kiểm tra hành động và xử lý
if ($action === 'create_product') {
    create_product($data);
} else if ($action === 'update_product') {
    // Lấy ID sản phẩm từ query string
    $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
    if ($goods_id === 0) {
        send_json_response(['error' => true, 'message' => "Thiếu ID sản phẩm (goods_id) trong URL."], 400);
    }
    update_product($goods_id, $data);
} else {
    // Nếu hành động không hợp lệ
    send_json_response(['error' => true, 'message' => "Hành động không hợp lệ. Cần có ?action=create_product hoặc ?action=update_product."], 400);
}

// ===================================================================
// SECTION 3: CÁC HÀM XỬ LÝ
// ===================================================================

/**
 * Hàm tạo sản phẩm chính
 */
function create_product($data)
{
    global $db, $ecs, $_CFG;

    // --- Kiểm tra dữ liệu đầu vào ---
    if (empty($data['cat_id']) && empty($data['category'])) {
        send_json_response(['error' => true, 'message' => "Thiếu trường bắt buộc: 'cat_id' hoặc 'category'"], 400);
    }
    $required_fields = ['goods_name', 'shop_price', 'goods_number'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) { send_json_response(['error' => true, 'message' => "Thiếu trường bắt buộc: '$field'"], 400); }
    }

    try {
        // --- Gán dữ liệu từ JSON vào các biến ---
        $goods_name = isset($data['goods_name']) ? trim($data['goods_name']) : '';
        $cat_id = isset($data['cat_id']) ? intval($data['cat_id']) : (isset($data['category']) ? intval($data['category']) : 0);
        $brand_id = isset($data['brand_id']) ? intval($data['brand_id']) : 0;
        $seller_note = isset($data['seller_note']) ? trim($data['seller_note']) : '';
        $is_best = !empty($data['best']) ? 1 : 0;
        $is_hot = !empty($data['hot']) ? 1 : 0;
        $is_new = !empty($data['new']) ? 1 : 0;
        $is_home = !empty($data['ishome']) ? 1 : 0;
        
        // Xử lý tất cả các trường giá
        $shop_price = isset($data['shop_price']) ? floatval($data['shop_price']) : 0;
        $market_price = isset($data['market_price']) ? floatval($data['market_price']) : $shop_price * 1.2;
        $partner_price = isset($data['partner_price']) ? floatval($data['partner_price']) : 0;
        $ace_price = isset($data['ace_price']) ? floatval($data['ace_price']) : 0;
        $deal_price = isset($data['deal_price']) ? floatval($data['deal_price']) : 0;
        
        $goods_number = intval($data['goods_number']);
        $goods_desc = isset($data['goods_desc']) ? trim($data['goods_desc']) : '';
        $keywords = isset($data['keywords']) ? trim($data['keywords']) : '';
        $goods_brief = isset($data['goods_brief']) ? trim($data['goods_brief']) : '';
        $meta_title = isset($data['meta_title']) ? trim($data['meta_title']) : $goods_name;
        $meta_desc = isset($data['meta_desc']) ? trim($data['meta_desc']) : '';
        $goods_sn = isset($data['goods_sn']) ? trim($data['goods_sn']) : '';
        $original_img_path = isset($data['original_img']) ? trim($data['original_img']) : '';

        // --- Chèn sản phẩm vào CSDL ---
        if (empty($goods_sn)) { $max_id = $db->getOne("SELECT MAX(goods_id) + 1 FROM " . $ecs->table('goods')); $goods_sn = generate_goods_sn($max_id); } 
        else { if (check_goods_sn_exist($goods_sn)) { throw new Exception("Mã sản phẩm '$goods_sn' đã tồn tại."); }}
        $add_time = gmtime(); $last_update = gmtime();
        
        // ===================================================================
        // SỬA ĐỔI DUY NHẤT TẠI ĐÂY: is_shipping được đổi từ '1' thành '0'
        // ===================================================================
        $sql_pre_insert = "INSERT INTO " . $ecs->table('goods') . 
            " (goods_name, cat_id, brand_id, shop_price, market_price, partner_price, ace_price, deal_price, goods_number, goods_desc, keywords, goods_brief, meta_title, meta_desc, goods_sn, is_on_sale, add_time, last_update, is_alone_sale, goods_type, is_shipping, seller_note, is_best, is_hot, is_new, is_home)" .
            " VALUES ('" . addslashes($goods_name) . "', '" . $cat_id . "', '" . $brand_id . "', '" . $shop_price . "', '" . $market_price . "', '" . $partner_price . "', '" . $ace_price . "', '" . $deal_price . "', '" . $goods_number . "', '" . addslashes($goods_desc) . "', '" . addslashes($keywords) . "', '" . addslashes($goods_brief) . "', '" . addslashes($meta_title) . "', '" . addslashes($meta_desc) . "', '" . addslashes($goods_sn) . "', '1', '" . $add_time . "', '" . $last_update . "', '1', '0', '0', '" . addslashes($seller_note) . "', '" . $is_best . "', '" . $is_hot . "', '" . $is_new . "', '" . $is_home . "')";
        
        $db->query($sql_pre_insert);
        $goods_id = $db->insert_id();
        if ($goods_id <= 0) { throw new Exception('Không thể thêm sản phẩm vào CSDL: ' . $db->errorMsg()); }

        // --- Tạo slug ---
        $slug = build_slug($goods_name);
        $check_slug = $db->getOne("SELECT COUNT(id) FROM " . $ecs->table('slug') . " WHERE slug='$slug'");
        if ($check_slug > 0) { $slug = $slug . '-' . $goods_id; }
        $db->autoExecute($ecs->table('slug'), ['id' => $goods_id, 'module' => 'goods', 'slug' => $slug], 'INSERT');
        
        // --- Xử lý ảnh ---
        $goods_img_final = ''; $goods_thumb_final = ''; $original_img_final = '';
        if (!empty($original_img_path) && file_exists(ROOT_PATH . $original_img_path)) {
            $original_img_final = $original_img_path;
            $source_full_path = ROOT_PATH . $original_img_path;
            $original_basename = pathinfo($original_img_path, PATHINFO_FILENAME);
            $source_dir = dirname($original_img_path);
            $parent_dir = dirname($source_dir);
            $goods_dir = ROOT_PATH . $parent_dir . '/goods_img/';
            $thumb_dir = ROOT_PATH . $parent_dir . '/thumb_img/';
            if (!is_dir($goods_dir)) { mkdir($goods_dir, 0755, true); }
            if (!is_dir($thumb_dir)) { mkdir($thumb_dir, 0755, true); }
            if (is_writable($goods_dir) && is_writable($thumb_dir)) {
                $goods_thumb_final = create_resized_image_webp($source_full_path, $thumb_dir, $original_basename, $_CFG['thumb_width'], $_CFG['thumb_height']);
                $goods_img_final = create_resized_image_webp($source_full_path, $goods_dir, $original_basename, $_CFG['image_width'], $_CFG['image_height']);
            }
        }
        
        if (substr($goods_img_final, 0, 4) === 'cdn/') { $goods_img_final = substr($goods_img_final, 4); }
        if (substr($goods_thumb_final, 0, 4) === 'cdn/') { $goods_thumb_final = substr($goods_thumb_final, 4); }
        if (substr($original_img_final, 0, 4) === 'cdn/') { $original_img_final = substr($original_img_final, 4); }
        $sql_update_img = "UPDATE " . $ecs->table('goods') . " SET goods_img = '" . addslashes($goods_img_final) . "', goods_thumb = '" . addslashes($goods_thumb_final) . "', original_img = '" . addslashes($original_img_final) . "' WHERE goods_id = '$goods_id'";
        $db->query($sql_update_img);

        // --- Hoàn tất ---
        clear_cache_files();
        send_json_response(['error' => false, 'message' => 'Sản phẩm đã được tạo thành công.', 'goods_id' => $goods_id, 'created_slug' => $slug], 201);

    } catch (Exception $e) {
        if (isset($goods_id) && $goods_id > 0) {
            $db->query("DELETE FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");
            $db->query("DELETE FROM " . $ecs->table('slug') . " WHERE id = '$goods_id' AND module = 'goods'");
        }
        send_json_response(['error' => true, 'message' => $e->getMessage()], 500);
    }
}
/**
 * Cập nhật thông tin sản phẩm dựa trên goods_id.
 *
 * @param int   $goods_id  ID của sản phẩm cần cập nhật.
 * @param array $data      Mảng chứa dữ liệu mới của sản phẩm.
 */
function update_product($goods_id, $data)
{
    global $db, $ecs, $_CFG;

    // --- Kiểm tra dữ liệu đầu vào ---
    $goods_id = intval($goods_id);
    if ($goods_id <= 0) {
        send_json_response(['error' => true, 'message' => "ID sản phẩm không hợp lệ."], 400);
        return;
    }

    $product_info = $db->getRow("SELECT goods_id, original_img FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");
    if (!$product_info) {
        send_json_response(['error' => true, 'message' => "Sản phẩm với ID '$goods_id' không tồn tại."], 404);
        return;
    }
    
    if (empty($data)) {
        send_json_response(['error' => false, 'message' => 'Không có dữ liệu nào được gửi để cập nhật.', 'goods_id' => $goods_id], 200);
        return;
    }

    // Lọc dữ liệu đầu vào để tránh ghi đè bằng giá trị rỗng
    $filtered_data = [];
    foreach ($data as $key => $value) {
        $boolean_keys = ['best', 'hot', 'new', 'ishome'];
        if (in_array($key, $boolean_keys)) {
            $filtered_data[$key] = $value;
        } else if ($value !== '') {
            $filtered_data[$key] = $value;
        }
    }

    if (empty($filtered_data)) {
        send_json_response(['error' => false, 'message' => 'Không có dữ liệu hợp lệ nào được gửi để cập nhật.', 'goods_id' => $goods_id], 200);
        return;
    }

    try {
        $update_fields = [];
        $new_goods_name = null;

        // Xây dựng danh sách các trường cần cập nhật từ dữ liệu đã lọc
        if (isset($filtered_data['goods_name'])) {
            $new_goods_name = trim($filtered_data['goods_name']);
            $update_fields[] = "goods_name = '" . addslashes($new_goods_name) . "'";
        }
        if (isset($filtered_data['cat_id'])) {
            $update_fields[] = "cat_id = '" . intval($filtered_data['cat_id']) . "'";
        }
        if (isset($filtered_data['category'])) {
            $update_fields[] = "cat_id = '" . intval($filtered_data['category']) . "'";
        }
        if (isset($filtered_data['brand_id'])) {
            $update_fields[] = "brand_id = '" . intval($filtered_data['brand_id']) . "'";
        }
        if (isset($filtered_data['seller_note'])) {
            $update_fields[] = "seller_note = '" . addslashes(trim($filtered_data['seller_note'])) . "'";
        }
        if (isset($filtered_data['best'])) {
            $update_fields[] = "is_best = '" . (!empty($filtered_data['best']) ? 1 : 0) . "'";
        }
        if (isset($filtered_data['hot'])) {
            $update_fields[] = "is_hot = '" . (!empty($filtered_data['hot']) ? 1 : 0) . "'";
        }
        if (isset($filtered_data['new'])) {
            $update_fields[] = "is_new = '" . (!empty($filtered_data['new']) ? 1 : 0) . "'";
        }
        if (isset($filtered_data['ishome'])) {
            $update_fields[] = "is_home = '" . (!empty($filtered_data['ishome']) ? 1 : 0) . "'";
        }
        if (isset($filtered_data['shop_price'])) {
            $update_fields[] = "shop_price = '" . floatval($filtered_data['shop_price']) . "'";
        }
        if (isset($filtered_data['market_price'])) {
            $update_fields[] = "market_price = '" . floatval($filtered_data['market_price']) . "'";
        }
        if (isset($filtered_data['partner_price'])) {
            $update_fields[] = "partner_price = '" . floatval($filtered_data['partner_price']) . "'";
        }
        if (isset($filtered_data['ace_price'])) {
            $update_fields[] = "ace_price = '" . floatval($filtered_data['ace_price']) . "'";
        }
        if (isset($filtered_data['deal_price'])) {
            $update_fields[] = "deal_price = '" . floatval($filtered_data['deal_price']) . "'";
        }
        if (isset($filtered_data['goods_number'])) {
            $update_fields[] = "goods_number = '" . intval($filtered_data['goods_number']) . "'";
        }
        if (isset($filtered_data['goods_desc'])) {
            $update_fields[] = "goods_desc = '" . addslashes(trim($filtered_data['goods_desc'])) . "'";
        }
        if (isset($filtered_data['keywords'])) {
            $update_fields[] = "keywords = '" . addslashes(trim($filtered_data['keywords'])) . "'";
        }
        if (isset($filtered_data['goods_brief'])) {
            $update_fields[] = "goods_brief = '" . addslashes(trim($filtered_data['goods_brief'])) . "'";
        }
        if (isset($filtered_data['meta_title'])) {
            $update_fields[] = "meta_title = '" . addslashes(trim($filtered_data['meta_title'])) . "'";
        }
        if (isset($filtered_data['meta_desc'])) {
            $update_fields[] = "meta_desc = '" . addslashes(trim($filtered_data['meta_desc'])) . "'";
        }
        if (isset($filtered_data['goods_sn'])) {
            $goods_sn = trim($filtered_data['goods_sn']);
            if (check_goods_sn_exist($goods_sn, $goods_id)) {
                throw new Exception("Mã sản phẩm '$goods_sn' đã tồn tại cho một sản phẩm khác.");
            }
            $update_fields[] = "goods_sn = '" . addslashes($goods_sn) . "'";
        }

        // --- XỬ LÝ ẢNH MỚI ---
        if (isset($filtered_data['original_img']) && !empty($filtered_data['original_img'])) {
            $original_img_path_raw = trim($filtered_data['original_img']);
            
            // Bước 1: Chuẩn hóa đường dẫn bằng cách loại bỏ các tiền tố trùng lặp
            $cleaned_path = preg_replace('/^(cdn\/)+/', '', $original_img_path_raw);
            $cleaned_path = preg_replace('/(images\/)+/', 'images/', $cleaned_path);
            $cleaned_path = preg_replace('/(source_img\/)+/', 'source_img/', $cleaned_path);

            // Bước 2: Xây dựng đường dẫn file tuyệt đối
            $source_full_path = ROOT_PATH . 'cdn/' . $cleaned_path;

            if (file_exists($source_full_path)) {
                $original_basename = pathinfo($source_full_path, PATHINFO_FILENAME);
                $base_dir = dirname($cleaned_path);
                
                // Bước 3: Đảm bảo thư mục lưu ảnh tồn tại
                $goods_dir = ROOT_PATH . 'cdn/images/' . $base_dir . '/goods_img/';
                $thumb_dir = ROOT_PATH . 'cdn/images/' . $base_dir . '/thumb_img/';

                if (!is_dir($goods_dir)) { 
                    mkdir($goods_dir, 0755, true); 
                }
                if (!is_dir($thumb_dir)) { 
                    mkdir($thumb_dir, 0755, true); 
                }

                if (is_writable($goods_dir) && is_writable($thumb_dir)) {
                    // Tạo ảnh và lấy đường dẫn tương đối để lưu vào DB (không có 'cdn')
                    $goods_img_final = create_resized_image_webp($source_full_path, $goods_dir, $original_basename, $_CFG['image_width'], $_CFG['image_height']);
                    $goods_thumb_final = create_resized_image_webp($source_full_path, $thumb_dir, $original_basename, $_CFG['thumb_width'], $_CFG['thumb_height']);
                    
                    // SỬA LỖI TẠI ĐÂY: Loại bỏ tiền tố 'cdn/' nếu có
                    if (substr($goods_img_final, 0, 4) === 'cdn/') { 
                        $goods_img_final = substr($goods_img_final, 4); 
                    }
                    if (substr($goods_thumb_final, 0, 4) === 'cdn/') { 
                        $goods_thumb_final = substr($goods_thumb_final, 4); 
                    }

                    $update_fields[] = "goods_img = '" . addslashes($goods_img_final) . "'";
                    $update_fields[] = "goods_thumb = '" . addslashes($goods_thumb_final) . "'";
                    $update_fields[] = "original_img = '" . addslashes($cleaned_path) . "'";
                } else {
                     throw new Exception("Không thể ghi vào thư mục ảnh. Vui lòng kiểm tra quyền truy cập (755).");
                }
            } else {
                 throw new Exception("Lỗi: Không tìm thấy tệp ảnh gốc tại đường dẫn: " . $source_full_path);
            }
        }
        
        // Thực thi cập nhật CSDL
        if (!empty($update_fields)) {
            $update_fields[] = "last_update = '" . gmtime() . "'";
            $sql_update = "UPDATE " . $ecs->table('goods') . " SET " . implode(', ', $update_fields) . " WHERE goods_id = '$goods_id'";
            $db->query($sql_update);
        }

        // Cập nhật slug nếu tên sản phẩm thay đổi
        $updated_slug = null;
        if (isset($new_goods_name)) {
            $slug = build_slug($new_goods_name);
            $check_slug = $db->getOne("SELECT COUNT(id) FROM " . $ecs->table('slug') . " WHERE slug='$slug' AND id <> '$goods_id'");
            if ($check_slug > 0) {
                $slug = $slug . '-' . $goods_id;
            }
            $db->autoExecute($ecs->table('slug'), ['slug' => $slug], 'UPDATE', "id = '$goods_id' AND module = 'goods'");
            $updated_slug = $slug;
        }

        // Hoàn tất và trả về JSON
        clear_cache_files();
        $response_data = [
            'error' => false,
            'message' => 'Sản phẩm đã được cập nhật thành công.',
            'goods_id' => $goods_id,
            'data' => [
                'original_img_path_raw' => isset($original_img_path_raw) ? $original_img_path_raw : 'Không có ảnh được gửi',
                'cleaned_path' => isset($cleaned_path) ? $cleaned_path : 'Không có ảnh được gửi',
                'source_full_path' => isset($source_full_path) ? $source_full_path : 'Không có ảnh được gửi',
                'db_paths' => [
                    'original_img' => isset($cleaned_path) ? $cleaned_path : 'Không thay đổi',
                    'goods_img' => isset($goods_img_final) ? $goods_img_final : 'Không thay đổi',
                    'goods_thumb' => isset($goods_thumb_final) ? $goods_thumb_final : 'Không thay đổi'
                ]
            ]
        ];
        if ($updated_slug) {
            $response_data['updated_slug'] = $updated_slug;
        }
        send_json_response($response_data, 200);

    } catch (Exception $e) {
        send_json_response(['error' => true, 'message' => $e->getMessage()], 500);
    }
}
?>