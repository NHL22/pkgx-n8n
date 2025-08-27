<?php

if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = true;
}

/* Lựa chọn phương thức trả góp, mặc địnhh là credit thẻ tín dụng */
$method = isset($_params['m']) ? $_params['m'] : 'credit';

/*
Nếu lấy sp từ giỏ hàng thì check giỏ hàng có tồn tại ko
 */

/* Lấy thông tin sản phẩm từ giở hàng chính xác nhất gì đã chọn */
require(ROOT_PATH . 'includes/lib_order.php');
$flow_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : CART_GENERAL_GOODS;
$flow_type = strip_tags($flow_type);
$flow_type = json_str_iconv($flow_type);
/* 标记购物流程为普通商品 */
$_SESSION['flow_type'] = $flow_type;
$cart_goods = get_cart_goods($flow_type);


/** Check giỏ hàng có hàng không */
if(empty($cart_goods['goods_list'])){
    show_message('Giỏ hàng trống.', 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
}

/* Check Tổng tiền hợp lệ tham gia trả góp */

if(($cart_goods['total']['goods_amount'] < 3000000 || $cart_goods['total']['goods_amount'] > 60000000) && $method){
    $allow_installement = false;
}else{
    $allow_installement = $cart_goods['total']['goods_amount'] < 3000000 ? false : true;
}

if(!$allow_installement){
    show_message('Tổng giá trị đơn hàng không đủ điều kiện tham gia trả góp', 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
}

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$cache_id = $method.$_SESSION['user_rank'].'-'.$_CFG['lang'];
$cache_id = sprintf('%X', crc32($cache_id));

$_template = 'installment.dwt';


if (!$smarty->is_cached($_template, $cache_id))
{

    $smarty->assign('helps',        get_shop_help());
    $smarty->assign('id',           $goods_id);
    $smarty->assign('type',         2);
    $smarty->assign('cfg',          $_CFG);
    assign_template();

    $smarty->assign('method',  $method);
    $smarty->assign('goods_list', $cart_goods['goods_list']);
    $smarty->assign('total', $cart_goods['total']);

    /** Lấy danh sách tỉnh thành */
    $region = get_regions(1,1);
    $region_list = array();
    foreach ($region as $key => $row) {
        $region_list[$key]['region_id'] = $row['region_id'];
        $region_list[$key]['region_name'] = $row['region_name'];
    }

    $smarty->assign('region_list', $region_list);

    /* Token */
    $_SESSION['csrf_token'] = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].mt_rand());
    $smarty->assign('csrf_token', $_SESSION['csrf_token']);

}

$smarty->assign('now_time',  time());
$smarty->display($_template,    $cache_id);


/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */



 ?>