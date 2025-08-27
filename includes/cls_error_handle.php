<?php

class ErrorHandler
{
    private $callbacks_error = array();
    private $callbacks_exception = array();
    private $iserror =  false;
    private $uniqid;

    public function __construct(){}

    public function register()
    {
        set_error_handler(array($this, 'handleError'));
        set_exception_handler(array($this, 'handleException'));

        if (php_sapi_name() !== 'cli') {
            register_shutdown_function(array($this, 'handleShutdown'));
        }
    }

    public function handleError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if (!(error_reporting() & $errno)) {
            //error do not match current error reporting level
            return true;
        }

        switch ($errno) {
            case E_ERROR:
                $errno_str = 'E_ERROR';
                break;
            case E_WARNING:
                $errno_str = 'E_WARNING';
                break;
            case E_PARSE:
                $errno_str = 'E_PARSE';
                break;
            case E_NOTICE:
                $errno_str = 'E_NOTICE';
                break;
            case E_CORE_ERROR:
                $errno_str = 'E_CORE_ERROR';
                break;
            case E_CORE_WARNING:
                $errno_str = 'E_CORE_WARNING';
                break;
            case E_COMPILE_ERROR:
                $errno_str = 'E_COMPILE_ERROR';
                break;
            case E_COMPILE_WARNING:
                $errno_str = 'E_COMPILE_WARNING ';
                break;
            case E_USER_ERROR  :
                $errno_str = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $errno_str = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $errno_str = 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $errno_str = 'E_STRICT';
                break;
            case E_DEPRECATED:
                $errno_str = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $errno_str = 'E_USER_DEPRECATED';
                break;
            default:
                $errno_str = 'UNKNOWN';
        }

        $Exception = new Exception();
        $trace = $Exception->getTraceAsString();
        //Debug
        $errormsg = '<div style="font-family:Verdana;padding:0.8em;line-height:1.2em;font-size:0.9em;margin-bottom:0.8em;">';
        $errormsg .= "<h3 style='font-size:36px;font-weight:500'>Ecshop Vietnam Application Error</h3>\n
             <p>The application could not run because of the following error: </p>\n
             <p><strong>Type:</strong> {$errno_str} [{$errno}] </p>\n
             <p><strong>Message:</strong> {$errstr} in {$errfile}</p>\n
             <p><strong>Line:</strong> {$errline}</p>\n
             <h3>Stack trace:</h3>\n
             <pre>{$trace}</pre>\n";
        $errormsg .= "</div>";
        //Write log
        $message = "Message: {$errstr} at line {$errfile} in {$errline}\r\n Stack trace: \r\n{$trace}\r\n";

        if(DEBUG_MODE && DEBUG_MODE != 0){
           echo  $errormsg;
            die;
        }else{
            $this->iserror = true;
            if(!defined('ECS_ADMIN')){
                Logger::write('ERROR', 'ErrorHandler', $message);
                $this->getFrendly();
            }
        }

        //return true;
    }

    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error !== null && $error['type'] === E_ERROR) {
            $message = "{$error['message']} \r\n";
           //Write log file if need
           if(DEBUG_MODE && DEBUG_MODE != 0){
                echo '<div style="font-family:Verdana;padding:0.8em;line-height:1.2em;font-size:0.9em;margin-bottom:0.8em;">';
                echo "<h3 style='font-size:36px;font-weight:500'>Ecshop Vietnam Application Error</h3>\n
                     <p>The application could not run because of the following error: </p>\n
                     <p><strong>Type:</strong> handleShutdown  $error[type] </p>\n";
                echo "<p><strong>Message:</strong> {$message}</p>\n";
                echo "<p><strong>File:</strong> {$error['file']}</p>\n";
                echo "<p><strong>Line:</strong> {$error['line']}</p>\n";
                echo '</div>';
                die;
            }else{
                $this->iserror = true;

                if(!defined('ECS_ADMIN')){
                    Logger::write('ERROR', 'handleShutdown', $message);
                    $this->getFrendly();
                }
            }
        }

    }

    public function handleException(Exception $Exception)
    {
        //$Exception = new Exception;
        $class =  get_class($Exception);
        $exception = '<div style="font-family:Verdana;padding:0.1em 0.8em;line-height:1.2em;font-size:0.9em;margin-bottom:0.8em;">';
        $exception .= "<h3 style='font-size:36px;font-weight:500'>Ecshop Vietnam Application Error</h3>\n
             <p>The application could not run because of the following error: </p>\n
             <p><strong>Type:</strong> Handler {$class} </p>\n
             <p><strong>Message:</strong> {$Exception->getMessage()}</p>\n
             <p><strong>Code:</strong> {$Exception->getCode()}</p>\n
             <p><strong>File:</strong> {$Exception->getFile()}</p>\n
             <p><strong>Line:</strong> {$Exception->getLine()}</p>\n
             <h3>Stack trace:</h3>\n
             <pre>{$Exception->getTraceAsString()}</pre>\n";
        $exception .= "</div>";

        //Write log
        $message = "Message: {$Exception->getMessage()} at line {$Exception->getLine()} in {$Exception->getFile()}\r\n Stack trace: \r\n{$Exception->getTraceAsString()}\r\n";

        if(DEBUG_MODE && DEBUG_MODE != 0){
            echo  $exception;
            die;
        }else{
            $this->iserror = true;
            if(!defined('ECS_ADMIN')){
                Logger::write('ERROR', 'customException', $message);
                $this->getFrendly();
            }

        }

    }

    private function getFrendly(){
        $frendly_smg = '<div style="width:500px;margin:0 auto;text-align:center;border:1px solid #ddd;border-top: 5px solid #35aa47;color:#35aa47;font-family:Verdana;padding:0.8em;line-height:1.5em;font-size:0.9em;margin-bottom:0.8em;">';
        $frendly_smg .= FRENDLY_MSG;
        $frendly_smg .= '</div>';
        echo $frendly_smg;
        die;
    }


}
