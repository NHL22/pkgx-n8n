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

// ĐỊNH NGHĨA CÁC HẰNG SỐ CẦN THIẾT
if (!defined('EC_CHARSET')) {
    define('EC_CHARSET', 'utf-8');
}
if (!defined('M_CHARSET')) {
    define('M_CHARSET', EC_CHARSET);
}
// Tải cấu hình
$_CFG = load_config();

// --- BƯỚC 5: DỌN SẠCH BỘ ĐỆM VÀ BẮT ĐẦU API ---
@ob_end_clean(); // Xóa sạch mọi output đã có (Warning, Notice,...)

// --- Cấu hình API và Bảo mật ---
define('API_SECRET_KEY', 'a91f2c47e5d8b6f03a7c4e9d12f0b8a6'); // Nhớ đổi key của bạn

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- Xác thực API KEY ---
$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
if (empty($apiKey) || $apiKey !== API_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Xác thực thất bại: API Key không hợp lệ hoặc bị thiếu.']);
    exit;
}

// --- Logic chính của API ---
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ. Chỉ chấp nhận POST.']);
    exit;
}

$json_payload = file_get_contents('php://input');
$data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ.']);
    exit;
}

$goods_id = isset($data['goods_id']) ? intval($data['goods_id']) : 0;
$rating   = isset($data['rating']) ? intval($data['rating']) : 5;
$content  = isset($data['content']) ? htmlspecialchars(trim($data['content'])) : '';
$email    = isset($data['email']) ? trim($data['email']) : '';
$username = isset($data['username']) ? htmlspecialchars(trim($data['username'])) : 'N8N Reviewer';

if ($goods_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.']);
    exit;
}
if (empty($email) || !is_email($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ hoặc bị thiếu.']);
    exit;
}

$comment_type = 0;
$user_id      = 0;
$ip_address   = real_ip();
$add_time     = gmtime();
$status       = 1 - $GLOBALS['_CFG']['comment_check'];

$sql = "INSERT INTO " . $ecs->table('comment') .
       " (comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) " .
       " VALUES ('$comment_type', '$goods_id', '$email', '$username', '$content', '$rating', '$add_time', '$ip_address', '$status', '0', '$user_id')";

if ($db->query($sql)) {
    http_response_code(201);
    $message = $status ? 'Đã thêm đánh giá thành công!' : 'Đánh giá đã được gửi và đang chờ duyệt.';
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu, không thể gửi đánh giá.']);
}

exit;
?>