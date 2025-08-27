<?php

$pay_code = !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
$msg = array('type'=>'notice','message'=>'');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

/* Check pay code exits */
if (empty($pay_code))
{
    $msg['message'] = $_LANG['pay_not_exist'];
    $msg['type'] = 'error';
}
else
{
    /* 检查code里面有没有问号 */
    if (strpos($pay_code, '?') !== false)
    {
        $arr1 = explode('?', $pay_code);
        $arr2 = explode('=', $arr1[1]);

        $_REQUEST['code']   = $arr1[0];
        $_REQUEST[$arr2[0]] = $arr2[1];
        $_GET['code']       = $arr1[0];
        $_GET[$arr2[0]]     = $arr2[1];
        $pay_code           = $arr1[0];
    }

    /* 判断是否启用 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('payment') . " WHERE pay_code = '$pay_code' AND enabled = 1";
    if ($db->getOne($sql) == 0)
    {
        $msg['message'] = $_LANG['pay_disabled'];
        $msg['type'] = 'error';
    }
    else
    {

        $plugin_file = ROOT_PATH.'includes/modules/payment/' . $pay_code . '.php';
        /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
        if (file_exists($plugin_file))
        {
            /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
            include_once($plugin_file);

            $payment = new $pay_code();
            if(@$payment->respond()){
                $msg['message'] = $_LANG['pay_success'];
                $msg['type'] = 'success';
            }else{
                $msg['message'] = $_LANG['pay_fail'];
                $msg['type'] = 'error';
            }

            //var_dump($payment->order_sn);
        }
        else
        {
            $msg['message'] = $_LANG['pay_not_exist'];
            $msg['type'] = 'error';
        }
    }
}

assign_template();
$position = assign_ur_here();
$smarty->assign('page_title', $position['title']);   // 页面标题
$smarty->assign('ur_here',    $position['ur_here']); // 当前位置
$smarty->assign('helps',      get_shop_help());      // 网店帮助

$smarty->assign('msg',    $msg);
$smarty->assign('shop_url',   $ecs->url());

$smarty->display('respond.dwt');

?>
