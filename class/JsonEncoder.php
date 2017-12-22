<?php
/**
 * @version $Revision: 1430 $
 */

/**
 * JSON encoder
 *
 * @author Stas Chichkan <stas@itworks.biz.ua>
 * @static
 */
class JsonEncoder
{
    /**
     * Encode data into JSON string
     *
     * @param mixed $data
     * @return string JSON string
     */
    function encode($data)
    {

        switch (true) {
            case is_object($data):
                return JsonEncoder::_encodeObject($data);
            case is_array($data):
                return JsonEncoder::_encodeArray($data);
            case is_float($data):
                return str_replace(',', '.', $data);
            case is_int($data):
                return $data;
            case (is_string($data) && is_numeric($data)):
                return "'" . $data . "'";
            case is_string($data):
                return JsonEncoder::_encodeString($data);
            case is_bool($data):
                return $data ? 'true' : 'false';
            default:
                return 'null';
        }

    }

    /**
     * Get ascii code by utf multibyte char
     *
     * @param int $num number of utf character
     * @return string char
     * @access private
     */
    function _getAsciiCode($char)
    {
        $ascii = 0;
        if (ord($char{0})>=0 && ord($char{0})<=127)
            $ascii = $char{0};
        if (ord($char{0})>=192 && ord($char{0})<=223)
            $ascii = (ord($char{0})-192)*64 +
                     (ord($char{1})-128);
        if (ord($char{0})>=224 && ord($char{0})<=239)
            $ascii = (ord($char{0})-224)*4096 +
                     (ord($char{1})-128)*64 +
                     (ord($char{2})-128);
        if (ord($char{0})>=240 && ord($char{0})<=247)
            $ascii = (ord($char{0})-240)*262144 +
                     (ord($char{1})-128)*4096 +
                     (ord($char{2})-128)*64 +
                     (ord($char{3})-128);
        if (ord($char{0})>=248 && ord($char{0})<=251)
            $ascii = (ord($char{0})-248)*16777216 +
                     (ord($char{1})-128)*262144 +
                     (ord($char{2})-128)*4096 +
                     (ord($char{3})-128)*64 +
                     (ord($char{4})-128);
        if (ord($char{0})>=252 && ord($char{0})<=253)
            $ascii = (ord($char{0})-252)*1073741824 +
                     (ord($char{1})-128)*16777216 +
                     (ord($char{2})-128)*262144 +
                     (ord($char{3})-128)*4096 +
                     (ord($char{4})-128)*64 +
                     (ord($char{5})-128);
        if (ord($char{0})>=254 && ord($char{0})<=255)
            $ascii = false;
        return $ascii;
     }


    /**
     * Encode string
     *
     * @param mixed $value
     * @return string JSON string
     * @access private
     */
    function _encodeString($value)
    {
        $value = (string) $value;
        $valuelength = strlen($value);
        $converted = '';
        for ($i=0; $i<$valuelength; $i++){
            $ascii = ord($value{$i});
            switch($ascii){
                case $ascii == 8:
                    $converted .= '\b';
                break;
                case $ascii == 9:
                    $converted .= '\t';
                break;
                case $ascii == 10:
                    $converted .= '\n';
                break;
                case $ascii == 12:
                    $converted .= '\f';
                break;
                case $ascii == 13:
                    $converted .= '\r';
                break;
                case $ascii == 34:
                case $ascii == 47:
                case $ascii == 92:
                     $converted .= '\\'.$value{$i};
                break;
                case(($ascii > 31 && $ascii < 128)):
                    $converted .= $value{$i};
                break;
                case (($ascii >= 240) && ($ascii <= 255)):
                    $char = JsonEncoder::_getAsciiCode(substr ($value, $i, 4));
                    $converted .= sprintf("\\u%04x", $char);
                    $i += 3;
                break;
                case (($ascii >= 224) && ($ascii <= 239)):
                    $char = JsonEncoder::_getAsciiCode(substr ($value, $i, 3));
                    $converted .= sprintf("\\u%04x", $char);
                    $i += 2;
                break;
                case (($ascii >= 192) && ($ascii <= 223)):
                    $char = JsonEncoder::_getAsciiCode(substr ($value, $i, 2));
                    $converted .= sprintf("\\u%04x", $char);
                    $i++;
                break;
                default:
                    $converted .= JsonEncoder::_getAsciiCode(substr ($value, $i, 1));
                break;
            }
        }

        if (is_numeric($converted)) {
            return $converted;
        }

        return '"' . $converted . '"';
    }

    /**
     * JSON encode Array
     *
     * @param mixed $value
     * @return mixed JSON string
     * @access private
     */
    function _encodeObject($value)
    {
        // shortcut
        $obj = get_object_vars($value);
        $obj_keys = array_keys($obj);
        $obj_vals = array_values($obj);

        $objlen = sizeof($obj_keys);

        for ($i = 0; $i < $objlen; $i++) {
            $temp[] = JsonEncoder::_encodeString($obj_keys[$i]) . ":"
                . JsonEncoder::encode($obj_vals[$i]);
        }

        // generating json object
        return '{' . implode(',', $temp) . '}';
    }

    /**
     * JSON encode Array
     *
     * @param mixed $value
     * @return mixed JSON string
     * @access private
     */
    function _encodeArray($value)
    {
        $temp = array();
        if (!empty($value) && (array_keys($value) !== range(0, sizeof($value) - 1))) {
            foreach ($value as $key => $_value) {
                $temp[] = JsonEncoder::_encodeString($key) . ":"
                    . JsonEncoder::encode($_value);
            }
            // generating assoc json array
            return  '{' .implode(',', $temp) . '}';
        } else {
            $arrlen = sizeof($value);
            for ($i = 0; $i < $arrlen; $i++) {
                $temp[] = JsonEncoder::encode($value[$i]);
            }
            // generating indexed json array
            return '[' . implode(',', $temp) . ']';
        }
    }
}