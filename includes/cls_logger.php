<?php
 /**
  * Simple logger class based on a similer class created by Nobj Nguyen
  * Usage
  * Define ROOT_PATH, LOG_PATH
   Logger::write('ERROR', 'customException', 'Here is Error Content');
**/

class Logger{

        protected static $log_path = 'temp/logs/';

        // Make dir log if not exits
        private static function setPath(){
            static::$log_path = ROOT_PATH.static::$log_path;
            //Make Dir if not exits
            if(!is_writable(static::$log_path)) {
                @mkdir(static::$log_path,0755, true);
            }
        }
        /**
         * writeToLog - writes out timestamped message to the log file as
         * defined by the $log_file class variable.
         * @method Static
         * @param String status - "INFO"/"DEBUG"/"ERROR"/WARNING e.t.c.
         * @param String tag - "Small tag to help find log entries"
         * @param String message - The message you want to output.
         * @return void
         **/
        public static function write($status, $tag, $message) {
            self::setPath();
            $date = date('[Y-m-d H:i:s]');
            $msg = "$date: [$tag][$status]"."\r\n" .$message."\r\n\r\n";
            $file = static::$log_path . date('Y-m-d').".log";
            //if not exits file, will make file by self
            if((DEBUG_MODE && false) == false){
               file_put_contents($file, $msg, FILE_APPEND);
            }
        }

        /**
         * Read log a day
         * @param   date Date Y-m-d
         * @return  String message
        **/
        public static function read($date){
            self::setPath();
            $file = static::$log_path .$date.".log";

            if(file_exists($file))
                $msg = file_get_contents($file);
             else
                $msg = "NOTE: Haven't log at $date !";

            return $msg;
        }


    }
?>