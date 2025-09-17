<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class smtp
{
    var $connection;
    var $recipients;
    var $headers;
    var $timeout;
    var $errors;
    var $status;
    var $body;
    var $from;
    var $host;
    var $port;
    var $helo;
    var $auth;
    var $user;
    var $pass;
    var $debug = false; // Bật chế độ debug

    function __construct($params = array())
    {
        $this->timeout  = 20; // Tăng timeout
        $this->status   = 0;
        $this->host     = 'localhost';
        $this->port     = 25;
        $this->auth     = false;
        $this->user     = '';
        $this->pass     = '';
        $this->errors   = array();

        foreach ($params AS $key => $value)
        {
            $this->$key = $value;
        }

        $this->helo     = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $this->auth = ('' == $this->user) ? false : true;
    }

    function log_debug($message)
    {
        if ($this->debug) {
            echo '<pre>' . htmlspecialchars(date('Y-m-d H:i:s') . ' - ' . $message) . '</pre>' . "\n";
        }
    }

    function connect($params = array())
    {
        $host = $this->host;
        // Bắt buộc sử dụng ssl:// wrapper cho cổng 465
        if ($this->port == 465) {
            $host = 'ssl://' . $this->host;
        }

        $this->log_debug("Bắt đầu kết nối tới: " . $host . ":" . $this->port);

        // Thêm context để xử lý các vấn đề về chứng chỉ SSL/TLS
        $context = stream_context_create(array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        ));

        $this->connection = @stream_socket_client(
            $host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($this->connection === false)
        {
            $error_msg = "Không thể kết nối tới máy chủ SMTP: $errno - $errstr";
            $this->errors[] = $error_msg;
            $this->log_debug($error_msg);
            return false;
        }

        $this->log_debug("Kết nối socket thành công. Đang chờ phản hồi từ máy chủ...");
        stream_set_timeout($this->connection, $this->timeout);

        $greeting = $this->get_data();
        $this->log_debug("SERVER: " . $greeting);

        if (!is_resource($this->connection) || substr($greeting, 0, 3) != '220') {
            $this->errors[] = 'Kết nối thất bại hoặc không nhận được lời chào từ máy chủ: ' . $greeting;
            $this->log_debug('Kết nối thất bại hoặc không nhận được lời chào từ máy chủ.');
            return false;
        }

        // Với cổng 587, chúng ta cần thực hiện STARTTLS
        if ($this->port == 587) {
            $this->log_debug('CLIENT: EHLO ' . $this->helo);
            $this->send_data('EHLO ' . $this->helo);
            $this->get_data();

            $this->log_debug('CLIENT: STARTTLS');
            $this->send_data('STARTTLS');
            $tls_response = $this->get_data();
            $this->log_debug('SERVER: ' . $tls_response);

            if (substr($tls_response, 0, 3) != '220') {
                $this->errors[] = 'Lệnh STARTTLS thất bại.';
                return false;
            }
            // Bật mã hóa
            if (!stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->errors[] = 'Không thể bật mã hóa TLS.';
                return false;
            }
            $this->log_debug("Mã hóa TLS đã được bật thành công.");
        }

        $this->status = 1;
        
        if (!$this->ehlo()) {
            return false;
        }
        
        return true;
    }

    function ehlo()
    {
        $this->log_debug("CLIENT: EHLO " . $this->helo);
        if (!$this->send_data('EHLO ' . $this->helo)) return false;

        $response = $this->get_data();
        $this->log_debug("SERVER: " . $response);
        if(substr($response, 0, 3) !== '250') {
            $this->errors[] = 'Lệnh EHLO thất bại: ' . $response;
            return false;
        }
        return true;
    }

    // Các hàm khác giữ nguyên như phiên bản debug trước đó
    
    function send($params = array())
    {
        foreach ($params AS $key => $value)
        {
            $this->$key = $value;
        }

        if (!$this->is_connected()) {
             $this->errors[] = 'Chưa kết nối đến máy chủ!';
             $this->log_debug('Lỗi: Chưa kết nối đến máy chủ trong hàm send()');
             return false;
        }

        if ($this->auth)
        {
            if (!$this->auth())
            {
                $this->log_debug('Xác thực thất bại.');
                return false;
            }
        }

        if (!$this->mail($this->from)) return false;

        if (is_array($this->recipients))
        {
            foreach ($this->recipients AS $value)
            {
                if (!$this->rcpt($value)) return false;
            }
        }
        else
        {
            if (!$this->rcpt($this->recipients)) return false;
        }

        if (!$this->data()) return false;

        $headers = str_replace("\r\n.", "\r\n..", trim(implode("\r\n", $this->headers)));
        $body    = str_replace("\r\n.", "\r\n..", $this->body);
        $body    = substr($body, 0, 1) == '.' ? '.' . $body : $body;

        $this->send_data($headers);
        $this->send_data('');
        $this->send_data($body);
        $this->send_data('.');
        
        $final_response = $this->get_data();
        $this->log_debug("SERVER (FINAL): " . $final_response);
        $this->log_debug("Đã gửi xong email.");

        return (substr($final_response, 0, 3) === '250');
    }

    function auth()
    {
        $this->log_debug("CLIENT: AUTH LOGIN");
        if (!is_resource($this->connection) || !$this->send_data('AUTH LOGIN')) return false;

        $response = $this->get_data();
        $this->log_debug("SERVER: " . $response);
        if (substr($response, 0, 3) !== '334') { $this->errors[] = 'AUTH LOGIN thất bại: ' . $response; return false; }
        
        $this->log_debug("CLIENT: " . base64_encode($this->user));
        if (!$this->send_data(base64_encode($this->user))) return false;

        $response = $this->get_data();
        $this->log_debug("SERVER: " . $response);
        if (substr($response, 0, 3) !== '334') { $this->errors[] = 'Username thất bại: ' . $response; return false; }

        $this->log_debug("CLIENT: [Mật khẩu đã được che]");
        if (!$this->send_data(base64_encode($this->pass))) return false;
        
        $response = $this->get_data();
        $this->log_debug("SERVER: " . $response);
        if (substr($response, 0, 3) !== '235') { $this->errors[] = 'Password thất bại: ' . $response; return false; }

        $this->log_debug("Xác thực thành công.");
        return true;
    }

    function mail($from)
    {
        $this->log_debug("CLIENT: MAIL FROM:<$from>");
        if ($this->is_connected() && $this->send_data('MAIL FROM:<' . $from . '>'))
        {
            $response = $this->get_data();
            $this->log_debug("SERVER: " . $response);
            if(substr($response, 0, 3) === '250') return true;
        }
        $this->errors[] = 'Lệnh MAIL FROM thất bại: ' . (isset($response) ? $response : 'Không có phản hồi');
        return false;
    }

    function rcpt($to)
    {
        $this->log_debug("CLIENT: RCPT TO:<$to>");
        if ($this->is_connected() && $this->send_data('RCPT TO:<' . $to . '>'))
        {
            $response = $this->get_data();
            $this->log_debug("SERVER: " . $response);
            if(substr($response, 0, 2) === '25') return true;
        }
         $this->errors[] = 'Lệnh RCPT TO thất bại: ' . (isset($response) ? $response : 'Không có phản hồi');
        return false;
    }

    function data()
    {
        $this->log_debug("CLIENT: DATA");
        if ($this->is_connected() && $this->send_data('DATA'))
        {
            $response = $this->get_data();
            $this->log_debug("SERVER: " . $response);
            if(substr($response, 0, 3) === '354') return true;
        }
        $this->errors[] = 'Lệnh DATA thất bại: ' . (isset($response) ? $response : 'Không có phản hồi');
        return false;
    }

    function is_connected()
    {
        return (is_resource($this->connection) && $this->status === 1);
    }

    function send_data($data)
    {
        if (is_resource($this->connection))
        {
            return fwrite($this->connection, $data . "\r\n");
        }
        return false;
    }
    
    function get_data()
    {
        $return = '';
        $line = '';
        if (is_resource($this->connection)) {
            while (strpos($return, "\r\n") === false || ($line && substr($line, 3, 1) !== ' ')) {
                $line = @fgets($this->connection, 512);
                if($line === false) {
                    $this->log_debug("Kết nối bị đóng bởi máy chủ hoặc timeout.");
                    break;
                }
                $return .= $line;
            }
            return trim($return);
        }
        return '';
    }

    function error_msg()
    {
        if (!empty($this->errors))
        {
            return implode("\n", $this->errors);
        }
        return '';
    }
}
?>