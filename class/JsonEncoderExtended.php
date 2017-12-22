<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . '/JsonEncoder.php');

class JsonEncoderExtended extends JsonEncoder {
    /**
     * Encode data into JSON string
     *
     * @param mixed $data
     * @return string JSON string
     */
    function encode($data)
    {
        switch (gettype($data)) {
            case 'object':
                return parent::_encodeObject($data);
            case 'array':
                if ($data[0] == '***ARRAY_ASSOC***') {
                    return JsonEncoderExtended::_encodeArray($data[1], true);
                } else {
                    return parent::_encodeArray($data);
                }
            case 'double':
            case 'float':
                return str_replace(',', '.', $data);
            case 'integer':
                return $data;
            case 'string':
                return parent::_encodeString($data);
            case 'boolean':
                return $data ? 'true' : 'false';
            default:
                return 'null';
        }
    }
    
    /**
     * JSON encode Array
     *
     * @param mixed $value
     * @param boolean $assoc_array - if true, then data is converted into assoc. array instead of object
     * @return mixed JSON string
     * @access private
     */
    function _encodeArray($value, $assoc_array = false)
    {
        $temp = array();
        if ($assoc_array) {
            foreach ($value as $key => $_value) {
                $temp[] = '[' . $key . ':'
                    . parent::encode($_value) . ']';
            }
            // generating assoc json array
            return  '[' .implode(',', $temp) . ']';
            
        } else {
            return parent::_encodeArray($value);
        }
    }
}