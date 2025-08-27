<?php
/**
 * SMS gửi đến - Nhận Tin Nhắn Phản Hồi
 * Link: https//domain.com/webhook?act=sms_delivered
 * Khi có tin nhắn phàn hồi từ phía KH SpeedSMS sẽ gửi một bản tin HTTP với dữ liệu như sau tới
 *  địa chỉ url mà bạn đã cung cấp:
 *  {"type": "sms", "phone": "phone number", "content": "sms content"}
 *  @param type: để phân biệt giữa bản tin thông báo delivery report với bản tin thông báo incoming sms
 *  @param phone: là số điện thoại của khách hàng nhận được tin nhắn
 *  @param content: là nội dung tin nhắn
 */

    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'temp/logs/incoming_sms.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current,FILE_APPEND);
 ?>