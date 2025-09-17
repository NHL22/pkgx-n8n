<?php

/**
 * ECSHOP 编码转换类
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: cls_iconv.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class Chinese
{
    var $table = '';
    var $iconv_enabled = false;
    var $unicode_table = array();

    function __construct($in_charset, $out_charset)
    {
        $this->iconv_enabled = function_exists('iconv');

        if (!$this->iconv_enabled)
        {
            $this->Openthetable();
        }
        else
        {
            if (strtoupper($in_charset) == 'UTF8')
            {
                $in_charset = 'UTF-8';
            }
            if (strtoupper($out_charset) == 'UTF8')
            {
                $out_charset = 'UTF-8';
            }

            $this->in_charset = $in_charset;
            $this->out_charset = $out_charset;
        }
    }

    function Convert($str)
    {
        if ($this->iconv_enabled)
        {
            return iconv($this->in_charset, $this->out_charset, $str);
        }
        else
        {
            return $this->CHSUbStr($str, 0, -1);
        }
    }

    /*
    *@description:
    *@param string $str
    *@return binary
    */
    function OpenTheTable()
    {
        if (empty($this->unicode_table))
        {
            $filename = ROOT_PATH . 'includes/codetable/gb-unicode.table';

            $this->table = file($filename);

            $temp = '';

            for ($i = 0, $n = count($this->table); $i < $n; $i++)
            {
                $this->unicode_table[hexdec(substr($this->table[$i], 0, 4))] = substr($this->table[$i], 5, 4);
            }
        }
    }

    /*
    *@description:
    *@param string $str
    *@return string
    */
    function CHSUbStr($str, $start, $len)
    {
        $tmpstr = '';
        $i = 0;
        $n = 0;
        $str_length = strlen($str);
        while ($i < $str_length)
        {
            if (ord($str[$i]) >= 128)
            {
                $tmpstr .= $str[$i] . $str[$i+1];
                $i += 2;
                $n++;
            }
            else
            {
                $tmpstr .= $str[$i];
                $i++;
                $n++;
            }
        }
        if ($len < 0)
        {
            $len = $n;
        }
        if ($start < 0)
        {
            $start = $n + $start;
        }
        $tmpstr = '';
        $i = 0;
        $j = 0;
        $n = 0;
        $str_length = strlen($str);
        while ($i < $str_length)
        {
            if ($j >= $start)
            {
                break;
            }
            if (ord($str[$i]) >= 128)
            {
                $tmpstr .= $str[$i] . $str[$i+1];
                $i += 2;
                $j++;
            }
            else
            {
                $tmpstr .= $str[$i];
                $i++;
                $j++;
            }
        }

        $tmpstr = '';
        while ($i < $str_length)
        {
            if ($n >= $len)
            {
                break;
            }
            if (ord($str[$i]) >= 128)
            {
                $tmpstr .= $str[$i] . $str[$i+1];
                $i += 2;
                $n++;
            }
            else
            {
                $tmpstr .= $str[$i];
                $i++;
                $n++;
            }
        }

        return $tmpstr;
    }

    /*
    *@description:
    *@param string $c
    *@return string
    */
    function Utf8_To_Gb($c)
    {
        $str = '';

        if ($c < 0x80)
        {
            $str .= $c;
        }
        elseif ($c < 0x800)
        {
            $str .= (chr(0xC0 | $c >> 6));
            $str .= (chr(0x80 | $c & 0x3F));
        }
        elseif ($c < 0x10000)
        {
            $str .= (chr(0xE0 | $c >> 12));
            $str .= (chr(0x80 | $c >> 6 & 0x3F));
            $str .= (chr(0x80 | $c & 0x3F));
        }
        elseif ($c < 0x200000)
        {
            $str .= (chr(0xF0 | $c >> 18));
            $str .= (chr(0x80 | $c >> 12 & 0x3F));
            $str .= (chr(0x80 | $c >> 6 & 0x3F));
            $str .= (chr(0x80 | $c & 0x3F));
        }

        return $str;
    }

    /*
    *@description:
    *@param string $str
    *@return string
    */
    function Gb_To_Utf8($str)
    {
        $str = $this->CHSUbStr($str, 0, -1);
        $result = '';
        for ($i = 0, $n = strlen($str); $i < $n; $i++)
        {
            if (ord($str[$i]) >= 128)
            {
                $p = ord($str[$i]) * 256 + ord($str[$i+1]);
                if (isset($this->unicode_table[$p]))
                {
                    $result .= $this->Utf8_To_Gb(hexdec($this->unicode_table[$p]));
                }
                $i++;
            }
            else
            {
                $result .= $str[$i];
            }
        }

        return $result;
    }

    /*
    *@description:
    *@param string $str
    *@return integer
    */
    function strlen_utf8($str)
    {
        $i = 0;
        $count = 0;
        $len = strlen($str);
        while ($i < $len)
        {
            $chr = ord($str[$i]);
            $count++;
            $i++;
            if ($i >= $len)
            {
                break;
            }

            if ($chr & 0x80)
            {
                $chr <<= 1;
                while ($chr & 0x80)
                {
                    $i++;
                    $chr <<= 1;
                }
            }
        }

        return $count;
    }
}
if (!function_exists('ecs_iconv'))
{
    function ecs_iconv($source_lang, $target_lang, $source_string = '')
    {
        if ($source_string === '' || $source_lang === $target_lang)
        {
            return $source_string;
        }

        if (function_exists('iconv') && M_CHARSET != 'zh_cn')
        {
            $return_string = iconv($source_lang, $target_lang, $source_string);
        }
        elseif (substr(M_CHARSET, 0, 2) == 'zh')
        {
            if ($source_lang == 'UTF8')
            {
                $source_lang = 'UTF-8';
            }

            if ($target_lang == 'UTF8')
            {
                $target_lang = 'UTF-8';
            }

            if ($source_lang == 'GB2312')
            {
                $source_lang = 'GBK';
            }

            if ($target_lang == 'GB2312')
            {
                $target_lang = 'GBK';
            }
            
            // Đảm bảo file cls_iconv.php được nạp để có class Chinese
            if (!class_exists('Chinese')) {
                include_once(ROOT_PATH . 'includes/cls_iconv.php');
            }

            // DÒNG ĐÃ SỬA: Truyền đủ 2 tham số vào hàm khởi tạo
            $chinese = new Chinese($source_lang, $target_lang);
            $return_string = $chinese->Convert($source_string);
        }
        else
        {
            $return_string = $source_string;
        }

        return $return_string;
    }
}

if (!function_exists('json_str_iconv'))
{
function json_str_iconv($str)
{
    if (EC_CHARSET != 'UTF-8')
    {
        if (is_string($str))
        {
            return ecs_iconv('UTF-8', 'GBK', $str);
        }
        elseif (is_array($str))
        {
            foreach ($str AS $key => $value)
            {
                $str[$key] = json_str_iconv($value);
            }
            return $str;
        }
        elseif (is_object($str))
        {
            foreach ($str AS $key => $value)
            {
                $str->$key = json_str_iconv($value);
            }
            return $str;
        }
        else
        {
            return $str;
        }
    }
    return $str;
}
}
function file_name_str_iconv($str)
{
    if (EC_CHARSET != 'UTF-8')
    {
        if (is_string($str))
        {
            return ecs_iconv('UTF-8', 'GBK', $str);
        }
        elseif (is_array($str))
        {
            foreach ($str AS $key => $value)
            {
                $str[$key] = json_str_iconv($value);
            }
            return $str;
        }
        elseif (is_object($str))
        {
            foreach ($str AS $key => $value)
            {
                $str->$key = json_str_iconv($value);
            }
            return $str;
        }
        else
        {
            return $str;
        }
    }
    return $str;
}

if (!function_exists('json_encode'))
{
    include_once('../includes/cls_json.php');
    function json_encode($value)
    {
        $json = new JSON;
        return $json->encode($value);
    }
}

if (!function_exists('json_decode'))
{
    include_once('../includes/cls_json.php');
    function json_decode($json, $assoc = false)
    {
        $json = new JSON;
        return $json->decode($json, $assoc);
    }
}

if (!function_exists('file_put_contents'))
{
    define('FILE_APPEND', '1');
    function file_put_contents($file, $data, $flags = '')
    {
        $contents = (is_array($data)) ? implode('', $data) : $data;

        if ($flags == '1')
        {
            $mode = 'ab+';
        }
        else
        {
            $mode = 'wb';
        }
        $fp = @fopen($file, $mode);
        if ($fp)
        {
            flock($fp, LOCK_EX);
            fwrite($fp, $contents);
            flock($fp, LOCK_UN);
            fclose($fp);

            return true;
        }
        else
        {
            return false;
        }
    }
}
// From file: D:\wwwroot\working\utf8\upload\includes\cls_json.php
/**
 * Convert a string to UTF-8.
 *
 * @param   string  $str
 * @return  string
 */
function utf8_encode_deep($str)
{
    if (is_array($str))
    {
        foreach ($str AS $key => $value)
        {
            $str[$key] = utf8_encode_deep($value);
        }

        return $str;
    }
    else
    {
        return ecs_iconv(EC_CHARSET, 'utf-8', $str);
    }
}

function get_binary($str, $order)
{
    $bin = '';
    $arr = explode(' ', $str);
    foreach ($arr as $val)
    {
        $bin .= decbin(hexdec($val));
    }

    return bindec(substr($bin, $order, 1));
}

function u2char($c)
{
    if ($c < 0x80)
    {
        return chr($c);
    }
    elseif ($c < 0x800)
    {
        return chr(0xC0 | $c >> 6)
             . chr(0x80 | $c & 0x3F);
    }
    elseif ($c < 0x10000)
    {
        return chr(0xE0 | $c >> 12)
             . chr(0x80 | $c >> 6 & 0x3F)
             . chr(0x80 | $c & 0x3F);
    }
    elseif ($c < 0x200000)
    {
        return chr(0xF0 | $c >> 18)
             . chr(0x80 | $c >> 12 & 0x3F)
             . chr(0x80 | $c >> 6 & 0x3F)
             . chr(0x80 | $c & 0x3F);
    }

    return false;
}
if (!function_exists('make_semiangle'))
{
function make_semiangle($str)
{
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                 '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                 'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                 'ｙ' => 'y', 'ｚ' => 'z',
                 '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
                 '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
                 '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                 '》' => '>',
                 '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                 '·' => '.', '、' => ',', '。' => '.', '，' => ',', '；' => ';',
                 '：' => ':', '？' => '?', '！' => '!', '…' => '-', '—' => '-',
                 '′' => '`', '″' => '`', '〃' => '`',
                 '　' => ' ');

    return strtr($str, $arr);
}
}
function sc_to_tc($str)
{
    if (EC_CHARSET == 'utf-8')
    {
        $sc_to_tc_table = array();
        if (file_exists(ROOT_PATH . 'includes/codetable/sc-tc.php'))
        {
            require_once(ROOT_PATH . 'includes/codetable/sc-tc.php');
            return strtr($str, $sc_to_tc_table);
        }
    }
    return $str;
}

function tc_to_sc($str)
{
    if (EC_CHARSET == 'utf-8')
    {
        $tc_to_sc_table = array();
        if (file_exists(ROOT_PATH . 'includes/codetable/tc-sc.php'))
        {
            require_once(ROOT_PATH . 'includes/codetable/tc-sc.php');
            return strtr($str, $tc_to_sc_table);
        }
    }
    return $str;
}

function string_character_iconv($str)
{
    $str_cha_arr = array();
    $str_cha_arr[0] = $str;
    if(EC_CHARSET == 'utf-8')
    {
        $str_cha_arr[1] = sc_to_tc($str);
        $str_cha_arr[2] = tc_to_sc($str);
    }
    elseif(EC_CHARSET == 'gbk')
    {
        $str_cha_arr[1] = ecs_iconv('GBK', 'BIG5', $str);
        $str_cha_arr[2] = ecs_iconv('BIG5', 'GBK', $str);
    }
    else
    {
        // gbk to big5
        $str_cha_arr[1] = $str_cha_arr[2] = $str;
    }

    return $str_cha_arr;
}


// From file: D:\wwwroot\working\utf8\upload\includes\cls_pinyin.php
define('PINYIN_TONE', false);
define('PINYIN_SPACE', ' ');

class Pinyin
{
    //var
    var $pinyins = NULL;
    // pinyin table
    var $_pinyins = array();
    //construct
    function __construct()
    {
    }
    // 获取单个字符的拼音
    function get($char, $is_str = false)
    {
        if (is_null($this->pinyins))
        {
            $this->_pinyins = $this->pinyins = include(ROOT_PATH . 'includes/codetable/pinyin.php');
        }
        $this->pinyins = $this->_pinyins;
        if (isset($this->pinyins[$char]))
        {
            if (PINYIN_TONE)
            {
                if (is_array($this->pinyins[$char]))
                {
                    $pinyin = $this->pinyins[$char][0];
                }
                else
                {
                    $pinyin = $this->pinyins[$char];
                }
            }
            else
            {
                if (is_array($this->pinyins[$char]))
                {
                    $this->pinyins[$char] = $this->pinyins[$char][0];
                }
                $pinyin = preg_replace("/(à|á|ǎ|ā)/", 'a', $this->pinyins[$char]);
                $pinyin = preg_replace("/(ò|ó|ǒ|ō)/", 'o', $pinyin);
                $pinyin = preg_replace("/(è|é|ě|ē)/", 'e', $pinyin);
                $pinyin = preg_replace("/(ì|í|ǐ|ī)/", 'i', $pinyin);
                $pinyin = preg_replace("/(ù|ú|ǔ|ū)/", 'u', $pinyin);
                $pinyin = preg_replace("/(ü|ǘ|ǚ|ǜ)/", 'v', $pinyin);
            }
            return $pinyin;
        }
        elseif ($is_str === true)
        {
            return $char;
        }
        else
        {
            return NULL;
        }
    }

    /**
     * 将utf8的字符串转换为拼音
     *
     * @param string $str
     * @return string
     */
    function str2py($str)
    {
        $ret = array();
        $str_len = mb_strlen($str, EC_CHARSET);
        for ($i = 0; $i < $str_len; $i++)
        {
            $char = mb_substr($str, $i, 1, EC_CHARSET);
            if(ord($char) > 127)
            {
                $ret[] = $this->get(ecs_iconv(EC_CHARSET, 'gb2312', $char), true);
            }
            else
            {
                $ret[] = $char;
            }
        }
        return implode(PINYIN_SPACE, $ret);
    }

    /**
     * 获取汉字的首字母
     *
     * @param string $str
     * @return string
     */
    function get_f($str)
    {
        $py_str = $this->str2py($str);
        if ($py_str)
        {
            $f_str = '';
            $py_arr = explode(PINYIN_SPACE, $py_str);
            if (!empty($py_arr))
            {
                foreach ($py_arr AS $py)
                {
                    if (preg_match("/^[a-zA-Z]$/", $py))
                    {
                        $f_str .= $py;
                    }
                    else
                    {
                        $f_str .= substr($py, 0, 1);
                    }
                }
            }

            return strtoupper($f_str);
        }
        else
        {
            return $str;
        }
    }
}
?>