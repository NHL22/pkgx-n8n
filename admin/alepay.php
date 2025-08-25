<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
$exc   = new exchange($ecs->table("alepay"), $db, 'id', 'orderCode');

if ($_REQUEST['act'] == 'list')
{

    $filter = array();
    $smarty->assign('ur_here',      'Trả góp Alepay');
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);
    $alepaylist = alepaylist();
    $smarty->assign('alepaylist',    $alepaylist['arr']);
    $smarty->assign('filter',         $alepaylist['filter']);
    $smarty->assign('record_count',   $alepaylist['record_count']);
    $smarty->assign('page_count',     $alepaylist['page_count']);
    $sort_flag  = sort_flag($alepaylist['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    assign_query_info();
    $smarty->display('alepay_list.htm');
}
elseif ($_REQUEST['act'] == 'query')
{
    admin_priv('alepay_remove');
    $alepaylist = alepaylist();
    $smarty->assign('alepaylist',    $alepaylist['arr']);
    $smarty->assign('filter',          $alepaylist['filter']);
    $smarty->assign('record_count',    $alepaylist['record_count']);
    $smarty->assign('page_count',      $alepaylist['page_count']);
    $sort_flag  = sort_flag($alepaylist['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('alepay_list.htm'), '',
    array('filter' => $alepaylist['filter'], 'page_count' => $alepaylist['page_count']));
}
elseif ($_REQUEST['act'] == 'remove')
{
    $id = intval($_GET['id']);
    admin_priv('alepay_remove');
    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
        admin_log(addslashes($name),'remove','alepay');
        clear_cache_files();
    }
    $url = 'alepay.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}
elseif ($_REQUEST['act'] == 'toggle_is_call')
{
    admin_priv('alepay_remove');
    $id       = intval($_POST['id']);
    $is_call        = intval($_POST['val']);
    if ($exc->edit("is_call = '$is_call'", $id))
    {
        clear_cache_files();
        make_json_result($is_call);
    }
}
elseif ($_REQUEST['act'] == 'checktran') {

    require(ROOT_PATH . 'alepay/config.php');
    require(ROOT_PATH . 'alepay/Alepay.php');

    $alepay = new Alepay($alepay_config);

    /* check ngược lên hệ thống xem trạng thái GD */
    $result_tran = $alepay->getTransactionInfo($_REQUEST['code']);
    /* conver thàn mảng */
    $result_tran = json_decode($result_tran,true);

    //var_dump($result_tran);

    $result_tran['message']; //Mô tả trạng thái
    $result_tran['orderCode']; //Mã đơn hàng của Merchant
    $result_tran['transactionTime']; //thời gian thực hiện thanh toán (millisecond)
    $result_tran['successTime']; //Thời gian thanh toán thành công (millisecond)
    $result_tran['bankHotline']; //Số Hotline của ngân hàng trả góp

    $arr          = explode('-',$result_tran->orderCode);
    $order_sn     = $arr[0];
    $log_id       = intval($arr[1]);

    if($result_tran['status'] == '000'){
        /* Cập nhật trạng thái đơn hàng thành công */
        $extra_note = $result_tran['message'].' - Bank: '.$result_tran['bankName'].' - Loại thẻ: '.$result_tran['method'].' Số thẻ: '.$result_tran['cardNumber'].' - Chu kỳ: '.$result_tran['month'].' tháng. MerchantFee: '.$result_tran['merchantFee'].', PayerFee: .'.$result_tran['payerFee'];
    }else{
        /* Cập nhật trạng thái đơn hàng thất bại */
        $extra_note = $result_tran['message'].' - Bank: '.$result_tran['bankCode'] . '-'.$result_tran['bankName'].' - Loại thẻ: '.$result_tran['method'].' Số thẻ: '.$result_tran['cardNumber'].' - Chu kỳ: '.$result_tran['month'].' tháng. MerchantFee: '.$result_tran['merchantFee'].', PayerFee: .'.$result_tran['payerFee'];
    }

   echo $extra_note;
   echo '<br><a href="alepay.php?act=list">Trở lại</a>';


}
function alepaylist()
{
    $result = get_filter();
    if ($result === false)
    {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $filter['buyerPhone']  = empty($_REQUEST['buyerPhone']) ? '' : $_REQUEST['buyerPhone'];
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['buyerPhone'] = json_str_iconv($filter['buyerPhone']);
        }
        $start_day = empty($_REQUEST['start_day']) ? '' : $_REQUEST['start_day'];
        $end_day = empty($_REQUEST['end_day']) ? '' : $_REQUEST['end_day'];

        $where = '';
        if($filter['buyerPhone'] != ''){
            $where .= " AND buyerPhone = $filter[buyerPhone] ";
        }

        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('alepay'). " AS g WHERE 1=1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('alepay') . " AS g WHERE 1=1 $where" .
                    " ORDER BY g.id DESC ".
                    " LIMIT " . $filter['start'] . ",$filter[page_size]";
        $filter['buyerPhone'] = stripslashes($filter['buyerPhone']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $rows) {
        $rows['amount'] = price_format($rows['amount']);
        $rows['payerFee'] = price_format($rows['payerFee']);
        $rows['merchantFee'] = price_format($rows['merchantFee']);
        $rows['errorDescription'] = empty($rows['errorCode']) ? 'Mới tạo' : $rows['errorDescription'];
        $rows['addTime'] = date('d-m-Y H:i:s', $rows['addTime']);
        $rows['transactionTime'] = $rows['transactionTime'] > 0 ? date('d-m-Y H:i:s', $rows['transactionTime']) : 0;
        $rows['successTime'] = $rows['successTime'] > 0 ? date('d-m-Y H:i:s', $rows['successTime']) : 0;
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
 ?>