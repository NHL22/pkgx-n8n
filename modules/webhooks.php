<?php
/**
 * Webhook là cổng tiếp nhận kết quả xử lí
 * do các Service API trả về.
 * Dự vào kết quả đó xử lí sự kiện tương ứng với trạng thái.
 */

$act = isset($_params['act']) ? $_params['act'] : '';

/**
 * SMS gửi đi - Delivery Status Report
 * Link: https//domain.com/webhook?act=sms_delivered
 * @param: type: để phân biệt giữa bản tin thông báo delivery report với bản tin thông báo incoming sms
 * @param: tranId là transaction id mà bạn nhận được sau khi gọi API /sms/send
 * @param: phone: là số điện thoại của khách hàng nhận được tin nhắn
 * @param: status: là trạng thái của tin nhắn đã gửi thành công/thất bại tới máy người nhận hay chưa
 * @return status = 0: success | 0 < status < 64 tạm thời thất bại | status >= 64: thất bại
 */
if($act == 'sms_delivered'){

    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'logs/sms_delivered.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current);

}
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
elseif ($act == 'incoming_sms') {
    $input = file_get_contents('php://input');
    /** Ghi Log response */
    $file = ROOT_PATH. 'logs/incoming_sms.'.date('dmY').'.log';
    $current = file_get_contents($file);
    $current .= $input;
    $current .= "\n";
    file_put_contents($file, $current);
}


 ?>