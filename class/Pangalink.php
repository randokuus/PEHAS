<?php
/**
 * @version $Revision: 134 $
 */

include_once("class/config.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once SITE_PATH . '/class/Isic/IsicLogger.php';

/**
 * Class for user activation via hansa Pangalink
 */
class Pangalink
{
    public $isicDbCurrency = false;
    protected $logger;

    public function __construct()
    {
        $this->isicDbCurrency = IsicDB::factory('Currency');
        $this->logger = new IsicLogger();
    }

    /**
     * Generates string for signature
     *
     * @param array $macFields
     * @return unknown
     */
    function _generateMACString($macFields)
    {
        $service = $macFields['VK_SERVICE'];
        $data = '';
        foreach ($this->_getVariableOrder($service) as $key) {
            if (isset($macFields[$key])) {
                $data .= $this->getMacFieldDataWithLength(trim($macFields[$key]), $service);
            }
        }
        return $data;
    }

    function getMacFieldDataWithLength($str, $service)
    {
        $strLen = $this->getStringLengthByService($str, $service);
        return str_pad($strLen, 3, '0', STR_PAD_LEFT) . $str;
    }

    function getStringLengthByService($str, $service)
    {
        if ($this->isMultiByteService($service)) {
            return mb_strlen($str, $this->_getCharset());
        } else {
            return strlen($str);
        }
    }

    function isMultiByteService($service)
    {
        return true;
        // if not SEB and authentication service, then multibyte
        return ($this->_getBankId() != 1 && ($service == 4001 || $service == 3002));
    }

    /**
     * Generates user's signature
     *
     * @return
     */
    function _generateSignature($macfields)
    {
        $data = $this->_generateMACString($macfields);
//        $this->logger->addDebug($data, 'data');
        $privateKeyFile = $this->_getPrivateKeyFile();
        if (!extension_loaded('openssl')) {
            // save data into temporary file
            $fp_data = fopen($data_fname = tempnam(SITE_PATH . '/cache', 'mac'), 'w+');
            fwrite($fp_data, $data);
            fclose($fp_data);
            $command = "openssl dgst -sha1 -sign $privateKeyFile $data_fname";
            if (false == $pipe = popen($command, 'r')) {
                trigger_error('Couldn\'t find openssl', E_USER_WARNING);
                return false;
            }
            $signature = fread($pipe, 2096);
            pclose($pipe);
            unlink($data_fname);
        } else {
            $key = openssl_pkey_get_private(file_get_contents($privateKeyFile));
            if (!openssl_sign($data, $signature, $key)) {
                triggerError('"Unable to generate signature"', E_USER_WARNING);
                return false;
            }
        }
        return base64_encode($signature);
    }

    /**
     * Generates Form for bank
     *
     * @return array|string
     */
    function generateVKForm($return_url, $mac_id)
    {
        $form = '';
        $snd_id = $this->_getSndId();
        $charset = $this->_getCharset();
        $macfields = $this->_getMacFields($mac_id, $return_url, $snd_id, $charset);
//        $this->logger->addDebug($macfields, 'macfields');
        $bankLink = $this->_getBankLink();
        $res = $this->_generateSignature($macfields);
        if (!$res) {
            return $res;
        } else {
            $macfields['VK_MAC'] = $res;
            $form = "<form name='hansaActivation' method='POST' action='" . $bankLink . "'>\n";
            foreach ($macfields as $f => $v) {
                $form .= "<input type=\"hidden\" name=\"" . $f . "\" value=\"" . htmlspecialchars($v) . "\" />\n";
            }
            $form .= "</form>\n";
            return $form;
        }
    }

    /**
     * Get the bank return Request
     *
     */
    function _getBankRequest()
    {
        $bankMacFields = array();
        foreach ($_REQUEST as $f => $v) {
            if (substr($f, 0, 3) == 'VK_') {
                $bankMacFields[$f] = $v;
            }
        }
        return $bankMacFields;
    }

    /**
     * Checks the bank's signature
     *
     * @return bool
     */
    function _verify()
    {
        $bankMacFields = $this->_getBankRequest();
        $bankKeyFile = $this->_getBankKeyFile();
        if (!extension_loaded('openssl')) {
            // save signature into temporary file
            $fp_sign = fopen($sign_fname = tempnam(SITE_PATH . '/cache', 'mac'), 'w+');
            fwrite($fp_sign, base64_decode($bankMacFields['VK_MAC']));
            fclose($fp_sign);
            if (false == $pipe = popen("openssl rsautl -verify -in $sign_fname -inkey $bankKeyFile -certin", 'r')) {
                trigger_error('Couldn\'t find openssl', E_USER_WARNING);
                return false;
            }
            $recovered = fread($pipe, 2096);
            pclose($pipe);
            unlink($sign_fname);
            if ('' == $recovered) {
                return false;
            }
        } else {
            $key = openssl_pkey_get_public(file_get_contents($bankKeyFile));
            if (!openssl_verify($this->_generateMACString($bankMacFields)
                , base64_decode($bankMacFields['VK_MAC']), $key)
            ) {
                return false;
            }
        }
        return $bankMacFields;
    }

    /**
     * Check user activation
     *
     * @return bool|array
     */
    function checkActivation($return_id)
    {
        $bankMacFields = $this->_verify();
        if (isset($bankMacFields['VK_SERVICE'])) {
            if ($bankMacFields['VK_SERVICE'] == $return_id) {
                return $bankMacFields;
            }
        }
        return false;
    }

    function _getBankKeyFile()
    {
        trigger_error("returnBankKeyFile method must be overwrited!", E_USER_ERROR);
    }

    function _getPrivateKeyFile()
    {
        trigger_error("returnPrivateKeyFile method must be overwrited!", E_USER_ERROR);
    }

    function _getBankLink()
    {
        trigger_error("returnBankLink method must be overwrited!", E_USER_ERROR);
    }

    function _getSndId()
    {
        trigger_error("returnSndId method must be overwrited!", E_USER_ERROR);
    }

    function _getBankId()
    {
        return 0;
    }

    function _getCurrency()
    {
        return $this->isicDbCurrency->getDefault();
    }

    function _getBankAccount()
    {
        return '';
    }

    function _getRecipientName()
    {
        return '';
    }

    function _getReferenceNumber()
    {
        return '';
    }

    function _getCharset()
    {
        return "UTF-8";
    }

    public function buildAttrList($vkData)
    {
        if (!is_array($vkData) ||
            !array_key_exists('VK_USER_ID', $vkData) ||
            !array_key_exists('VK_USER_NAME', $vkData)) {
            return false;
        }
        $data = array(
            'ISIK:' . $vkData['VK_USER_ID'],
            'NIMI:' . $vkData['VK_USER_NAME']
        );
        return implode(';', $data);
    }

    function _getReturnUrl($id)
    {
        //return SITE_URL . '/pay/bank/' . $this->_getBankId() . '/id/' . $id;
        return SITE_URL . '/pay/' . $id;
    }

    function _getVariableOrder($code)
    {
        switch ($code) {
            case 1001:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_ACC', 'VK_NAME'
                , 'VK_REF', 'VK_MSG');
            case 1002:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_REF', 'VK_MSG');
            case 1011:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_ACC', 'VK_NAME'
                , 'VK_REF', 'VK_MSG', 'VK_RETURN', 'VK_CANCEL', 'VK_DATETIME');
            case 1012:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_STAMP', 'VK_AMOUNT', 'VK_CURR', 'VK_REF', 'VK_MSG'
                , 'VK_RETURN', 'VK_CANCEL', 'VK_DATETIME');
            case 1101:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_STAMP', 'VK_T_NO', 'VK_AMOUNT', 'VK_CURR'
                , 'VK_REC_ACC', 'VK_REC_NAME', 'VK_SND_ACC', 'VK_SND_NAME'
                , 'VK_REF', 'VK_MSG', 'VK_T_DATE');
            case 1111:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_STAMP', 'VK_T_NO', 'VK_AMOUNT', 'VK_CURR'
                , 'VK_REC_ACC', 'VK_REC_NAME', 'VK_SND_ACC', 'VK_SND_NAME'
                , 'VK_REF', 'VK_MSG', 'VK_T_DATETIME');
            case 1901:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_STAMP', 'VK_REF', 'VK_MSG');
            case 1902:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_STAMP', 'VK_REF', 'VK_MSG', 'VK_ERROR_CODE');
            case 1911:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_STAMP', 'VK_REF', 'VK_MSG');
            case 3001:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_USER'
                , 'VK_DATE', 'VK_TIME', 'VK_SND_ID');
            case 3002:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_USER'
                , 'VK_DATE', 'VK_TIME', 'VK_SND_ID', 'VK_INFO');
            case 3003:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_NONCE', 'VK_INFO');
            case 3012:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_USER',
                    'VK_DATETIME', 'VK_SND_ID', 'VK_REC_ID', 'VK_USER_NAME',
                    'VK_USER_ID', 'VK_COUNTRY', 'VK_OTHER', 'VK_TOKEN', 'VK_RID'
                    );
            case 4001:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REPLY', 'VK_RETURN', 'VK_DATE', 'VK_TIME');
            case 4002:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REC_ID', 'VK_NONCE', 'VK_RETURN');
            case 4011:
                return array('VK_SERVICE', 'VK_VERSION', 'VK_SND_ID'
                , 'VK_REPLY', 'VK_RETURN', 'VK_DATETIME', 'VK_RID');
        }
        return array();
    }

    function setPayParameter($param, $value, $convert_encoding = false)
    {
        if ($convert_encoding) {
            $this->$param = mb_convert_encoding($value, 'iso-8859-1', 'UTF-8');
        } else {
            $this->$param = $value;
        }
    }

    function _getMacFields($mac_id, $return_url, $snd_id, $charset = 'UTF-8')
    {
        switch ($mac_id) {
            case 1001:
                return array(
                    'VK_SERVICE' => '1001',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    // 'VK_REPLY' => '3002',
                    "VK_STAMP" => $this->pay_id,
                    "VK_AMOUNT" => $this->pay_amount,
                    "VK_CURR" => $this->pay_currency,
                    "VK_ACC" => $this->pay_account,
                    "VK_NAME" => $this->pay_name,
                    "VK_REF" => $this->pay_ref_number,
                    "VK_MSG" => $this->pay_message,
                    "VK_LANG" => 'EST',
                    "VK_RETURN" => $return_url,
                    //'VK_ENCODING' => $charset
                );
                break;

            case 1002:
                return array(
                    'VK_SERVICE' => '1002',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    "VK_STAMP" => $this->pay_id,
                    "VK_AMOUNT" => $this->pay_amount,
                    "VK_CURR" => $this->pay_currency,
                    "VK_NAME" => $this->pay_name,
                    "VK_REF" => $this->pay_ref_number,
                    "VK_MSG" => $this->pay_message,
                    "VK_LANG" => 'EST',
                    "VK_RETURN" => $return_url,
                    'VK_CHARSET' => $charset
                );

            case 1011:
                return array(
                    'VK_SERVICE' => '1011',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    "VK_STAMP" => $this->pay_id,
                    "VK_AMOUNT" => $this->pay_amount,
                    "VK_CURR" => $this->pay_currency,
                    "VK_ACC" => $this->pay_account,
                    "VK_NAME" => $this->pay_name,
                    "VK_REF" => $this->pay_ref_number,
                    "VK_MSG" => $this->pay_message,
                    "VK_RETURN" => $return_url,
                    "VK_CANCEL" => $return_url,
                    'VK_DATETIME' => $this->getDateTime(),
                    'VK_ENCODING' => $charset,
                    "VK_LANG" => 'EST',
                );
                break;

            case 1012:
                return array(
                    'VK_SERVICE' => '1012',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    "VK_STAMP" => $this->pay_id,
                    "VK_AMOUNT" => $this->pay_amount,
                    "VK_CURR" => $this->pay_currency,
                    "VK_REF" => $this->pay_ref_number,
                    "VK_MSG" => $this->pay_message,
                    "VK_RETURN" => $return_url,
                    "VK_CANCEL" => $return_url,
                    'VK_DATETIME' => $this->getDateTime(),
                    'VK_ENCODING' => $charset,
                    "VK_LANG" => 'EST',
                );

            case 4001:
                return array(
                    'VK_SERVICE' => '4001',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    'VK_REPLY' => '3002',
                    'VK_RETURN' => $return_url,
                    'VK_DATE' => date("d.m.Y"),
                    'VK_TIME' => date("H:i:s"),
                    'VK_ENCODING' => $charset,
                    'VK_CHARSET' => $charset
                );

            case 4002:
                return array(
                    'VK_SERVICE' => '4002',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    'VK_REC_ID' => 'KREP',
                    'VK_NONCE' => '1234567890',
                    'VK_RETURN' => $return_url,
                    'VK_CHARSET' => $charset
                );

            case 4011:
                return array(
                    'VK_SERVICE' => '4011',
                    'VK_VERSION' => '008',
                    'VK_SND_ID' => $snd_id,
                    'VK_REPLY' => '3012',
                    'VK_RETURN' => $return_url,
                    'VK_DATETIME' => $this->getDateTime(),
                    'VK_RID' => '',
                    'VK_ENCODING' => $charset
                );
                break;
        }
        return false;
    }

    protected function getDateTime()
    {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $offset = date('O');
        return $date . 'T' . $time . $offset;
    }
}