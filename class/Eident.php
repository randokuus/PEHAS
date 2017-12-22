<?php
include_once("class/config.php");

class Eident {

    function _getMacKey()
    {
        trigger_error("_getMacKey method must be overwrited!", E_USER_ERROR);
    }

    function _getBankLink()
    {
        trigger_error("_getBankLink method must be overwrited!", E_USER_ERROR);
    }

    function _getSndId()
    {
        trigger_error("_getSndId method must be overwrited!", E_USER_ERROR);
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }

    /**
     * Generates string for signature
     *
     * @param array $macFields
     * @return unknown
     */
    function _generateMACString($type, $macFields)
    {
        $data = '';
        $vkVariableOrder = $this->_getVariableOrder($type);
        foreach ($vkVariableOrder as $key) {
            if (isset($macFields[$key])) {
                $v = trim($macFields[$key]);
                $data .= $v . '&';
            }
        }
        $data .= $this->_getMacKey() . '&';
//        echo "<!-- " . $data . "-->\n";
        $data = strtoupper(md5($data));
        return $data;
    }

    /**
     * Generates Form for bank
     *
     * @return array|string
     */
    function generateForm($type, $return_url)
    {
        $form = '';
        $snd_id = $this->_getSndId();
        $charset = $this->_getCharset();
        $macfields = $this->_getMacFields($type, $return_url, $snd_id);
        $bankLink = $this->_getBankLink();

        $form = "<form name=\"hansaActivation\" method=\"POST\" action=\"" . $bankLink . "\">\n";
        foreach($macfields as $f => $v) {
            $form .=  "<input type=\"hidden\" name=\"" . $f . "\" value=\"" . htmlspecialchars ($v) . "\" />\n";
        }
        $form .= "</form>\n";
        return $form;
    }

    /**
     * Get the bank return Request
     *
     */
    function _getBankRequest($prefix)
    {
        $bankMacFields = array();
        foreach ($_REQUEST as $f => $v) {
            if (substr($f, 0, strlen($prefix)) == $prefix) {
                $bankMacFields[$f] = $v;
            }
        }
        return $bankMacFields;
    }

    /**
     * Check user activation
     *
     * @return bool|array
     */
    function checkActivation($type) {

        switch ($type) {
            case 'e-ident-response':
                $data = '';
                $macKey = $this->_getMacKey();
                $macFields = $this->_getBankRequest('B02K_');
                $variableOrder = $this->_getVariableOrder($type);

                foreach ($variableOrder as $key) {
                    if (isset($macFields[$key])) {
                        $v = $macFields[$key];
                        $data .= $v . '&';
                    }
                }

                $data .= $macKey . '&';
                $check = strtoupper(md5($data));
                if ($check == $macFields['B02K_MAC']) {
                    return $macFields;
                }
            break;
        }

        return false;
    }

    function _getTimeStamp() {
        list($usec, $sec) = explode(" ", microtime());
        $t = date("YmdHis", $sec) . str_pad(round($usec * 100), 2, "0", STR_PAD_LEFT);
        return $t;
    }

    function _getVariableOrder($type)
    {
        switch ($type) {
            case 'e-ident': 
                return array('A01Y_ACTION_ID', 'A01Y_VERS', 'A01Y_RCVID', 
                'A01Y_LANGCODE', 'A01Y_STAMP', 'A01Y_IDTYPE', 'A01Y_RETLINK', 
                'A01Y_CANLINK', 'A01Y_REJLINK', 'A01Y_KEYVERS', 'A01Y_ALG');
            break;
            case 'e-ident-response': 
                return array('B02K_VERS', 'B02K_TIMESTMP', 'B02K_IDNBR', 
                'B02K_STAMP', 'B02K_CUSTNAME', 'B02K_KEYVERS', 'B02K_ALG', 
                'B02K_CUSTID', 'B02K_CUSTTYPE');
            break;
       }
        return false;
    }

    function _getMacFields($type, $return_url, $snd_id) {
        switch ($type) {
            case 'e-ident': 
                $tmp_arr = array (
                    'A01Y_ACTION_ID' => '701',
                    'A01Y_VERS'      => '0002',
                    'A01Y_RCVID'     => $snd_id,
                    'A01Y_LANGCODE'  => 'ET',
                    'A01Y_STAMP'     => '200' . $this->_getTimeStamp(),
                    'A01Y_IDTYPE'    => '02',
                    'A01Y_RETLINK'   => $return_url,
                    'A01Y_CANLINK'   => $return_url,
                    'A01Y_REJLINK'   => $return_url,
                    'A01Y_KEYVERS'   => '0001',
                    'A01Y_ALG'       => '01'
                );
                $tmp_arr['A01Y_MAC'] = $this->_generateMacString($type, $tmp_arr);
                return $tmp_arr;
            break;
        }
        return false;
    }
}