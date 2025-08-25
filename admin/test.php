<?php
define('IN_ECS', true);
@ini_set('display_errors',    1);
include('../includes/cls_captcha.php');
$_captcha = new captcha('../cdn/data/captcha/',104,36);
@ob_end_clean();
//$_captcha->generate_image();
exit;

 ?>