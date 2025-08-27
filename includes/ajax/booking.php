<?php

/*------------------------------------------------------ */
//-- Booking Preorder - act=booking
/*------------------------------------------------------ */

    require(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $res    = array('err_msg' => '', 'err_no' => 1);
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $booking = array(
        'goods_id'     => isset($_POST['id'])      ? intval($_POST['id'])     : 0,
        'sex'     => isset($_POST['sex'])     ? intval($_POST['sex'])     : 0,
        'goods_amount' => 1,
        'desc'         => '',
        'linkman'      => isset($_POST['linkman']) ? trim($_POST['linkman'])  : '',
        'email'        => isset($_POST['email'])   ? trim($_POST['email'])    : '',
        'tel'          => isset($_POST['tel'])     ? trim($_POST['tel'])      : '',
    );
    /** Validate booking input data */
    if($booking['email'] != '' && !is_email($booking['email'])){
        $res['err_msg'] = 'Email không hợp lệ';
    }

    if(!is_tel($booking['tel']))
    {
        $res['err_msg'] = 'Số điện thoại không hợp lệ';
    }

    if($res['err_msg'] == ''){
        if (add_booking($booking))
        {
            $res['err_msg'] = 'Đặt trước thành công, chúng tôi sẽ liên hệ ngay khi có hàng.';
            $res['err_no']  = 0;
        }else{
            $res['err_msg'] = 'Đặt trước không thành công.';
        }
    }

    die($json->encode($res));


 ?>