<?php
class IsicEncoding {
    /**
     * Character encoding detection order
     *
     * @access public
     */

    const encoding_detect_order = "UTF-8, windows-1252, ISO-8859-15, ISO-8859-1";

    /**
     * Converts given string from UCS-2/UTF-16 to UTF8
     *
     * @param string $str input string
     * @return string converted string
     */

    public static function certstr2Utf8 ($str) {
        $str = preg_replace ("/\\\\x([0-9ABCDEF]{1,2})/e", "chr(hexdec('\\1'))", $str);
        $result = "";
        $encoding = mb_detect_encoding($str, "ASCII, UCS2, UTF8");
        if ($encoding == "ASCII") {
            $result = mb_convert_encoding($str, "UTF-8", "ASCII");
        } else {
            if (substr_count($str, chr(0)) > 0) {
                $result=mb_convert_encoding($str, "UTF-8", "UCS2");
            } else {
                $result=$str;
            }
        }

        return $result;
    }

    /**
     * Checks if given string is in UTF-8 encoding
     *
     * @param string $str input string
     * @access public
     * @return boolean
     */

    public static function isUTF8($str) {
        return preg_match('/^([\x09\x0A\x0D\x20-\x7E]|[\xC2][\xA0-\xBF]|[\xC3-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*$/', $str);
    }

    /**
     * Checks if given string is in iso-8859-1 encoding
     *
     * @param string $str input string
     * @access public
     * @return boolean
     */

    public static function isISO88591($str) {
        return preg_match('/^([\x09\x0A\x0D\x20-\x7E\xA0-\xFF])*$/', $str);
    }


    /**
     * Checks if given string is in windows-1252 encoding
     *
     * @param string $str input string
     * @access public
     * @return boolean
     */

    public static function isCP1252($str) {
        return preg_match('/^([\x09\x0A\x0D\x20-\x7E\x80\x82-\x8C\x8E\x91-\x9C\x9E-\xFF])*$/', $str);
    }

    /**
     * Method for finding the character encoding of the string
     *
     * @param string $str input string
     * @access public
     * @return string encoding
     */

    public static function getStringEncoding($str) {
        $enc = mb_detect_encoding($str, self::encoding_detect_order, true);
        return $enc;
    }

    /**
     * Method for converting given string into UTF-8 string
     * if no encoding could be found then no conversion is done either
     *
     * @param string $str input string
     * @access public
     * @return string converted string
     */

    public static function convertStringEncoding($str) {
        if ($str) {
            $enc = self::getStringEncoding($str);
            if ($enc) {
                $str = mb_convert_encoding($str, "UTF-8", $enc);
            }
        }
        return $str;
    }

    /**
     * Method for converting all elements of the given array into UTF-8 strings
     * if no encoding could be found then no conversion is done either
     *
     * @param array $arr input array
     * @access public
     * @return array converted array
     */
    public static function convertArrayEncoding($arr) {
        $newArr = array();
        if (!is_array($arr)) {
            return self::convertStringEncoding($arr);
        }
        foreach ($arr as $key => $str) {
            $newArr[$key] = self::convertStringEncoding($str);
        }
        return $newArr;
    }

    /**
     * Converts the encoding of the input file into UTF-8
     *
     * @param string $fname input filename
     * @access public
     */

    public static function convertFileEncoding($fname, $convertLineEndings = false) {
        $tfname = SITE_PATH . "/cache/" . md5(rand(0, time()));

        if (($fp = fopen($fname, "rb")) && ($tfp = fopen($tfname, "wb"))) {
            while (!feof($fp)) {
                $line = fgets($fp);
                $new_line = self::convertStringEncoding($line);
                if ($convertLineEndings) {
                    $new_line = strtr($new_line, array("\r\n" => "\n", "\r" => "\n"));
                }
                fwrite($tfp, $new_line);
            }
            fclose($fp);
            fclose($tfp);
            @copy($tfname, $fname);
            @unlink($tfname);
        }
    }
}