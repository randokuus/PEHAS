<?php
include_once("class/config.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");

class Epayment {
    public $isicPayment;
    public $isicDbCurrency = false;

    public function __construct($isicPayment) {
        $this->isicDbCurrency = IsicDB::factory('Currency');
        $this->isicPayment = $isicPayment;
    }

    function _getCurrency()
    {
        return $this->isicDbCurrency->getDefault();
    }

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

    function _getReturnUrl($id) {
        return SITE_URL . '/pay/' . $id;
        //return SITE_URL . '/pay/bank/' . $this->_getBankId() . '/id/' . $id;
        //return SITE_URL . '/pay/?bank=' . $this->_getBankId() . '&id=' . $id;
    }

    /**
     * Calculates reference number from given number with 7-3-1 algorithm
     *
     * @param string $num
     * @return string
     */

    function _getReferenceNumber($num) {
        $weight = array(7, 3, 1);
        if (is_numeric($num)) {
            $num_rev = strrev($num);
            $weight_count = 0;
            $num_sum = 0;
            for ($i = 0; $i < strlen($num_rev); $i++) {
                $num_sum += $num_rev[$i] *  $weight[$weight_count++];
                if ($weight_count >= 3) {
                    $weight_count = 0;
                }
            }
            $next_ten = floor($num_sum / 10) * 10 + 10;
            $ctrl_sum = ($next_ten - $num_sum);
            if ($ctrl_sum == 10) {
                $ctrl_sum = 0;
            }
            return $num . $ctrl_sum;
        }
        return $num;
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
     * Wrapper method with the same name as in Pangalink-class to be able to use compatible calls
     * @param string $url
     * @param string $type
     */
    function generateVKForm($url, $type) {
        return $this->generateForm($type, $url);
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
            case 'e-payment-response':
                $data = '';
                $macKey = $this->_getMacKey();
                $macFields = $this->_getBankRequest('SOLOPMT_RETURN_');
                $variableOrder = $this->_getVariableOrder($type);

                foreach ($variableOrder as $key) {
                    $data .= $macFields[$key] . '&';
                }

                $data .= $macKey . '&';
                $check = strtoupper(md5($data));

                if ($check == $macFields['SOLOPMT_RETURN_MAC']) {
                    return $this->generateResponseList($macFields);
                }
            break;
        }

        return false;
    }

    function generateResponseList($res) {
        if (!$res['SOLOPMT_RETURN_PAID']) {
            return false;
        }
        $id = $res['SOLOPMT_RETURN_STAMP'];
        $pay_info = $this->isicPayment->getPaymentInfoAppl($id);
        $res['VK_T_NO'] = $res['SOLOPMT_RETURN_PAID'];
        $res['VK_AMOUNT'] = $pay_info['pay_amount'];
        $res['VK_CURR'] = $this->_getCurrency();
        $res['VK_REC_ACC'] = $this->_getBankAccount();
        $res['VK_REC_NAME'] = mb_convert_encoding($this->_getRecipientName(), 'iso-8859-1', 'UTF-8');
        $res['VK_SND_ACC'] = '-';
        $res['VK_SND_NAME'] = '-';
        $res['VK_REF'] = $res['SOLOPMT_RETURN_REF'];
        $res['VK_MSG'] = mb_convert_encoding($pay_info['pay_message'], 'iso-8859-1', 'UTF-8');
        $res['VK_T_DATETIME'] = date("d.m.Y");
        return $res;
    }

    function _getTimeStamp() {
        list($usec, $sec) = explode(" ", microtime());
        $t = date("YmdHis", $sec) . str_pad(round($usec * 100), 2, "0", STR_PAD_LEFT);
        return $t;
    }

    function _getVariableOrder($type)
    {
        switch ($type) {
            case 'e-payment':
                return array('SOLOPMT_VERSION', 'SOLOPMT_STAMP', 'SOLOPMT_RCV_ID',
                'SOLOPMT_AMOUNT', 'SOLOPMT_REF', 'SOLOPMT_DATE', 'SOLOPMT_CUR');
            break;
            case 'e-payment-response':
                return array('SOLOPMT_RETURN_VERSION', 'SOLOPMT_RETURN_STAMP',
                'SOLOPMT_RETURN_REF', 'SOLOPMT_RETURN_PAID');
            break;
       }
        return false;
    }

    function setPayParameter($param, $value) {
        $this->$param = mb_convert_encoding($value, 'iso-8859-1', 'UTF-8');
    }

    function _getMacFields($type, $return_url, $snd_id) {
        switch ($type) {
            case 'e-payment':
                $tmp_arr = array (
                    'SOLOPMT_VERSION'     => '0003',
                    'SOLOPMT_STAMP'       => $this->pay_id,
                    'SOLOPMT_RCV_ID'      => $snd_id,
                    'SOLOPMT_RCV_ACCOUNT' => $this->pay_account,
                    'SOLOPMT_RCV_NAME'    => $this->pay_name,
                    'SOLOPMT_LANGUAGE'    => 4, // estonian
                    'SOLOPMT_AMOUNT'      => $this->pay_amount,
                    'SOLOPMT_REF'         => $this->pay_ref_number,
                    'SOLOPMT_DATE'        => 'EXPRESS',
                    'SOLOPMT_MSG'         => $this->pay_message,
                    'SOLOPMT_RETURN'      => $return_url,
                    'SOLOPMT_CANCEL'      => $return_url,
                    'SOLOPMT_REJECT'      => $return_url,
                    'SOLOPMT_CONFIRM'     => 'YES',
                    "SOLOPMT_KEYVERS"     => '0001',
                    "SOLOPMT_CUR"         => $this->pay_currency,
                );
                $tmp_arr['SOLOPMT_MAC'] = $this->_generateMacString($type, $tmp_arr);
                return $tmp_arr;
            break;
        }
        return false;
    }
}