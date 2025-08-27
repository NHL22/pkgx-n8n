<?php
/**
 * SMS gửi đi - Delivery Status Report
 * Link: https//domain.com/webhook?act=sms_delivered
 * @param: type: để phân biệt giữa bản tin thông báo delivery report với bản tin thông báo incoming sms
 * @param: tranId là transaction id mà bạn nhận được sau khi gọi API /sms/send
 * @param: phone: là số điện thoại của khách hàng nhận được tin nhắn
 * @param: status: là trạng thái của tin nhắn đã gửi thành công/thất bại tới máy người nhận hay chưa
 * @return status = 0: success | 0 < status < 64 tạm thời thất bại | status >= 64: thất bại
 */

    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'temp/logs/sms_delivered.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current, FILE_APPEND);

 ?>