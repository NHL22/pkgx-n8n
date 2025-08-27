<?php
// database host không được đổi
$db_host   = "localhost:3306";
// database name
$db_name   = "phukiengiax_d1b4";
// database username
$db_user   = "phukiengiad1b4";
// database password
$db_pass   = "d1b42e4ef697c36fc778f301657a6";
// Time work
$open_time   = "9:00-21:00";
// table prefix
$prefix    = "ecsvn_";
$timezone    = "Asia/Ho_Chi_Minh";
$cookie_path    = "/";
$cookie_domain    = $_SERVER['HTTP_HOST'];
$cookie_secure = isset($_SERVER['HTTPS']);
$cookie_http_only = false;
$session = "1440";
$base_cdn = 'https://phukiengiaxuong.com.vn/cdn';
/* on/off cache for dev */
$ecsvn_iscached = true;
/* allow index on Google Search */
$ecsvn_index_follow = true;
/**
 * Formart Domain index google
 * 1: https://domain.com
 * 2: https://www.domain.com
 * 3: http://domain.com
 * 4: http://www.domain.com
 */
$domain_index = 1;

define('EC_CHARSET','utf-8');
define('CDN_PATH','cdn');
define('ADMIN_PATH','admin');
define('AUTH_KEY', 'this is a key');
define('OLD_AUTH_KEY', '');
define('API_TIME', '');
define('STORE_KEY','c3227bfbd16a1d7ec30fd23cda74771a');

/**
 * SMS Token API
 */
define('SMS_TOKEN', '');
?>