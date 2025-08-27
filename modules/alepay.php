<?php
/**
 * 4.990.000
 * 9T visa sacombank 725.223k/thang, Phi: 1.537.000, Total: 6.527.000
 */
/** Xử lí Submit */
$act = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

 /* Load danh sách thẻ từ cache */
require(ROOT_PATH . 'alepay/config.php');
require(ROOT_PATH . 'alepay/Alepay.php');
$alepay = new Alepay($alepay_config);

if($act == 'banklist'){
    define('IS_AJAX', true);
    $result   = array('error' => 0, 'content' => '');
    $price = isset($_POST['price']) ? intval($_POST['price']) : 0;

    $data = read_static_cache('alepay_banklist_'.$price);
    if ($data === false)
    {
        $installment = $alepay->getlistBank($price);
        $installment = json_decode($installment, true);
        write_static_cache('alepay_banklist_'.$price, $installment, 3600);
    }else{
        $installment = $data;
    }

    $smarty->assign('installment',  $installment);
    $result['content'] = $smarty->fetch('library/alepay_banklist.lbi');
    die(json_encode($result));

}
elseif($act == 'cardlist'){
    define('IS_AJAX', true);
    $result   = array('error' => 0, 'content' => '');
    $price = isset($_POST['price']) ? intval($_POST['price']) : 0;
    $bankcode = isset($_POST['bankcode']) ? intval($_POST['bankcode']) : 0;

    $data = read_static_cache('alepay_banklist_'.$price);
    if ($data === false)
    {
        $installment = $alepay->getlistBank($price);
        $installment = json_decode($installment, true);
        write_static_cache('alepay_banklist_'.$price, $installment);
    }else{
        $installment = $data;
    }
    $cardtype = $installment[$bankcode]['paymentMethods'];

    $result['total'] = count($cardtype);

    $smarty->assign('cardtype',  $cardtype);
    $result['content'] = $smarty->fetch('library/alepay_cardtype.lbi');
    die(json_encode($result));
}
elseif($act == 'periods'){
    define('IS_AJAX', true);
    $result   = array('error' => 0, 'content' => '');
    $price = isset($_POST['price']) ? intval($_POST['price']) : 0;
    $bankcode = isset($_POST['bankcode']) ? intval($_POST['bankcode']) : 0;
    $cardtype = isset($_POST['cardtype']) ? intval($_POST['cardtype']) : 0;

    $data = read_static_cache('alepay_banklist_'.$price);
    if ($data === false)
    {
        $installment = $alepay->getlistBank($price);
        $installment = json_decode($installment, true);
        write_static_cache('alepay_banklist_'.$price, $installment);
    }else{
        $installment = $data;
    }
    $periods_list = $installment[$bankcode]['paymentMethods'][$cardtype]['periods'];

    $smarty->assign('periods_list',  $periods_list);
    $result['content'] = $smarty->fetch('library/alepay_periods_list.lbi');
    die(json_encode($result));
}

/**
 * Send Request Checkout Alepay
 * @var [type]
 */
elseif($act == 'checkout_alepay'){

    if($_POST['csrf_token'] != $_SESSION['csrf_token']){
       show_message('Token không hợp lệ');
    }


    parse_str(file_get_contents('php://input'), $params); // Lấy thông tin dữ liệu bắn vào
    /* Validate */
    if(empty($params['goodsName'])){
        show_message('Tên sản phẩm dịch vụ không thể để trống');
    }
    $params['amount'] = str_replace(",", "", $params['amount']);
    /* Ràng buộc Dữ liệu */
    if($params['amount'] < 3000000 || $params['amount'] > 60000000){
        show_message('Tổng giá trị đơn hàng phải từ 3 - 60 triệu mới hợp lệ để làm trả góp');
    }
    if(is_email($params['buyerEmail']) == false){
        show_message('Email không hợp lệ');
    }
    if(is_tel($params['buyerPhone']) == false){
        show_message('Số điện thoại không hợp lệ');
    }
    $sex = $params['sex'] == 1 ? 'Anh ' : 'Chị '.

    /* Khai báo tham số truyền */
    $data = array();

    $data['amount']           = intval($params['amount']);
    $data['orderCode']        = 'NN-'.date('dmY') .'_'. uniqid();
    $data['currency']         = 'VND';
    $data['orderDescription'] = $params['goodsName'];
    $data['totalItem']        = 1;
    $data['checkoutType']     = 2; // 2 Thanh toán trả góp, 1 Trả thẳng
    $data['buyerName']        = trim($params['buyerName']);
    $data['buyerEmail']       = trim($params['buyerEmail']);
    $data['buyerPhone']       = trim($params['buyerPhone']);
    $data['buyerAddress']     = trim($params['buyerAddress']);
    $data['buyerCity']        = trim($params['buyerCity']);
    $data['buyerCountry']     = 'Việt Nam';
    $data['paymentHours']     = 48; //48 tiếng :  Thời gian cho phép thanh toán (tính bằng giờ)
    $data['cancelUrl']        = $ecsvn_request['getBaseUrl'];

    /* Buộc data phải điền */
    foreach ($data as $k => $v) {
        if (empty($v)) {
            show_message("Bắt buộc phải nhập/chọn tham số [ " . $k . " ]");
        }
    }

    /* Option params */
    /**
     *  True : Đơn hàng chỉ cho phép trả góp (Phải truyền lên cả month, bankCode)
     *  ==> khi chuyển hướng form sẻ ko chọn lại month, và bank nữa.
        False : Đơn hàng cho phép trả góp hoặc thanh toán thường
     */
    $data['installment']   = TRUE;
    $data['month']         = intval($params['month']);
    $data['bankCode']      = $params['bankCode'];
    $data['paymentMethod'] = $params['paymentMethod'];

    /* gui request thanh toan */
    $result = $alepay->sendOrderToAlepay($data);
    if (isset($result) && !empty($result->checkoutUrl)) {
        /* Save Đơn hàng trả góp vào CSDL */
         $tragop_data = array(
            'orderCode'=>$data['orderCode'],
            'amount'=>$data['amount'],
            'orderDescription'=>$data['orderDescription'],
            'totalItem'=>$data['totalItem'],
            'checkoutType'=>$data['checkoutType'],
            'buyerName'=> $sex.$data['buyerName'],
            'buyerEmail'=>$data['buyerEmail'],
            'buyerPhone'=>$data['buyerPhone'],
            'buyerAddress'=>$data['buyerAddress'],
            'buyerCity'=>$data['buyerCity'],
            'addTime'=> time(),
            'goods_info'=> $params['goodsName'].' - '.price_format($data['amount']),
            'shop_notice'=> $params['shop_notice'],
            'goods_gift'=> $params['good_gift']
         );
        $db->autoExecute($ecs->table('alepay'), $tragop_data, 'INSERT');
        /* send mail */
        // if ( $GLOBALS['_CFG']['send_service_email'] &&  $GLOBALS['_CFG']['service_email'] != '')
        // {
        //     $content = "Trả góp Alepay từ ".$data['buyerName']." ĐT: ".$data['buyerPhone']." Mua: - ".$data['orderDescription'];
        //     send_mail( $GLOBALS['_CFG']['shop_name'],  $GLOBALS['_CFG']['service_email'], 'Thông báo có đơn hàng trả góp Alepay', $content, 1);
        // }
        /* Chuyển hướng thanh toán */
        //$alepay->return_json('OK', 'Thành công', $result->checkoutUrl);
        echo '<meta http-equiv="refresh" content="0;url=' . $result->checkoutUrl. '">';
    } else {
        show_message($result->errorDescription);
    }


}
 /**
 * Xử lí kết quả trả về Webhook khi trả góp Phương thức thanh toán Alepay
 *
 * Khi giao dịch thành công hoặc có thay đổi về trạng thái giao dịch (duyệt / không duyệt trả
 * góp, duyệt / không duyệt thẻ Review) hoặc người dùng thực hiện liên kết thẻ thành công,
 * Alepay sẽ thực hiện callback trả về thông tin giao dịch và thông tin thẻ liên kết thông
 * qua URL callback mà Merchant đã khai báo trên Alepay.
 */

elseif($act == 'webhook_alepay_payment'){

    require(ROOT_PATH . 'includes/lib_payment.php');

    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'alepay/response.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current,FILE_APPEND);

    /* Lấy Data */
    $obj = json_decode($input);
    $orderCode    = $obj->transactionInfo->orderCode;
    $arr          = explode('-',$obj->transactionInfo->orderCode);
    $order_sn     = $arr[0];
    $log_id       = intval($arr[1]);

    $transactionCode  = $obj->transactionInfo->transactionCode;
    $status           = $obj->transactionInfo->status;
    $errorDescription = $status == '155' ? 'Giao dịch đang chờ duyệt' : $obj->transactionInfo->message;

    /* GD thành công */
    if($status == '000'){
        $installment     = $obj->transactionInfo->installment;
        $month           = $obj->transactionInfo->month;
        $bankCode        = $obj->transactionInfo->bankCode;
        $method          = $obj->transactionInfo->method;
        $transactionTime = $obj->transactionInfo->transactionTime;
        $successTime     = $obj->transactionInfo->successTime;
        $merchantFee     = $obj->transactionInfo->merchantFee;
        $payerFee        = $obj->transactionInfo->payerFee;
        $cardNumber      = $obj->transactionInfo->cardNumber;

        if($installment == true){
            $note = 'Trạng thái: Thanh toán thành công ! GD Trả góp có Mã: '.$transactionCode. ' đã Thành Công. Giao hàng cho khách nhé !';
            order_paid($log_id,2, $note);
        }
        else{
            $note = 'Trạng thái: Thanh toán thành công ! GD Trả Thẳng có Mã: '.$transactionCode. ' đã Thành Công. Giao hàng cho khách nhé !';
            order_paid($log_id,2, $note);
        }
    }
    /* Các trạng thái khác đều thất bại */
    else {
        /* Cập nhật trạng thái đơn hàng thất bại */
        $note = 'Trạng thái: '. $errorDescription.'.Mã lỗi'.$status;
        order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $note, 'Alepay Webhook');
    }

    // Điều kiện refund hiện chưa hoàn thiện, sẽ cập nhật sau
    /** ghi log */
    $error_log = $db->error();
    $File = ROOT_PATH."alepay/error_log.txt";
    if($error_log){
        $Handle = fopen($File, 'a');
        fwrite($Handle, $sql."\n".$error_log);
        fclose($Handle);
    }

}
 /**
 * Xử lí kết quả trả về Webhook khi
 *  trả góp Alepay độc lập ở table alepay
 *
 * Khi giao dịch thành công hoặc có thay đổi về trạng thái giao dịch (duyệt / không duyệt trả
 * góp, duyệt / không duyệt thẻ Review) hoặc người dùng thực hiện liên kết thẻ thành công,
 * Alepay sẽ thực hiện callback trả về thông tin giao dịch và thông tin thẻ liên kết thông
 * qua URL callback mà Merchant đã khai báo trên Alepay.
 */
elseif($act == 'webhook_alepay'){
    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'alepay/response.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current,FILE_APPEND);

    /* Lấy Data */
    $obj = json_decode($input);
    $orderCode        = $obj->transactionInfo->orderCode;
    $transactionCode  = $obj->transactionInfo->transactionCode;
    $status           = $obj->transactionInfo->status;
    $errorDescription = $status == '155' ? 'Giao dịch đang chờ duyệt' : $obj->transactionInfo->message;

    /* GD thành công */
   if($status == '000'){
        $installment     = $obj->transactionInfo->installment;
        $month           = $obj->transactionInfo->month;
        $bankCode        = $obj->transactionInfo->bankCode;
        $method          = $obj->transactionInfo->method;
        $transactionTime = $obj->transactionInfo->transactionTime;
        $successTime     = $obj->transactionInfo->successTime;
        $merchantFee     = $obj->transactionInfo->merchantFee;
        $payerFee        = $obj->transactionInfo->payerFee;
        $cardNumber      = $obj->transactionInfo->cardNumber;
        if($installment == true){
            /// Giao dịch thanh toán trả góp thành công => cập nhật db với
            $sql = "UPDATE ".$ecs->table('alepay'). " SET ".
                " errorCode = '000', errorDescription = 'Thành công', ".
                " month = '$month', bankCode = '$bankCode', paymentMethod = '$method', ".
                " transactionTime = '$transactionTime', successTime = '$successTime', ".
                " merchantFee = '$merchantFee', payerFee = '$payerFee', cardNumber = '$cardNumber' " .
                " WHERE  orderCode = '$orderCode'";
            $db->query($sql);
        }
        else{
        // Giao dịch thanh toán chuyển thanh toán ngay thành công => cập nhật db với mã đơn hàng $orderCode = $obj -> transactionInfo -> orderCode;
        }
    }
    /* Các trạng tháo khác */
    else {
        $sql = "UPDATE ".$ecs->table('alepay'). " SET errorCode = '$status', errorDescription = '$errorDescription', transactionCode = '$transactionCode' WHERE  orderCode = '$orderCode'";
        $db->query($sql);
    }

    // Điều kiện refund hiện chưa hoàn thiện, sẽ cập nhật sau
    /** ghi log */
    $error_log = $db->error();
    $File = ROOT_PATH."alepay/error_log.txt";
    if($error_log){
        $Handle = fopen($File, 'a');
        fwrite($Handle, $sql."\n".$error_log);
        fclose($Handle);
    }
}
/**
 * Nhận kết quả khi user thanh toán thành công.
 * @var [type]
 */
elseif ($act == 'callback_alepay') {

   $encryptKey = $alepay_config['encryptKey'];

    if (isset($_REQUEST['data']) && isset($_REQUEST['checksum'])) {
        $utils = new AlepayUtils();
        $result = $utils->decryptCallbackData($_REQUEST['data'], $encryptKey);
        $obj_data = json_decode($result);

        if($obj_data->errorCode){
            $result_tran = $alepay->getTransactionInfo($obj_data->data);
            $result_tran = json_decode($result_tran);
             /**
             * status 155: KH thanh toán thành công
             * Đợi chủ shop xác nhận còn hàng
             * Sau đó Alepay sẻ gọi webhook cập nhật trạng thái thành công hay thất bại...
             */
            if($result_tran->status == '155'){
                show_message('Giao dịch thanh toán thành công. Chúng tôi sẻ xác nhận và sớm liên hệ lại với quý khách.', 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
            }
            elseif($result_tran->status == '150'){
                show_message('Giao dịch thanh toán thành công ! Chúng tôi sẻ liên hệ với bạn để xác minh lại thẻ thanh toán.', 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
            }
            /* Giao dich loi */
            else{
                show_message($result_tran->message, 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
            }

        }
         /* Giao dich loi */
        else{
            show_message($alepay->getErrorMsg($obj_data->errorCode), 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
        }

    }
    else{
        show_message('Thông tin checksum không hợp lệ', 'Trang chủ', $ecsvn_request['getBaseUrl'], 'info', false);
    }
}
elseif($act=='test'){
    $installment = $alepay->getlistBank(4990000);

    var_dump($installment);

}