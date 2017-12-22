<?php

require_once ('class/Pangalink.php');

class PangalinkMock extends Pangalink {
    function generateMacFields() {
        $ic = new IsicPayment();
        $pay_info = $ic->getPaymentInfoAppl($_REQUEST['id']);
        /*
        return array(
            'VK_SERVICE' => '1101',
            'VK_T_NO' => '1234567890',
            'VK_AMOUNT' => '999',
            'VK_CURR' => 'EUR',
            'VK_REC_ACC' => '987654321',
            'VK_REC_NAME' => 'Receiver Name',
            'VK_SND_ACC' => '111222333',
            'VK_SND_NAME' => 'Sender Name',
            'VK_REF' => '',
            'VK_MSG' => 'Message',
            'VK_T_DATE' => date("d.m.Y"),
        );
        */
        return array(
            'VK_SERVICE' => '1101',
            'VK_T_NO' => '1234567890',
            'VK_AMOUNT' => $pay_info['pay_amount'],
            'VK_CURR' => 'EUR',
            'VK_REC_ACC' => '987654321',
            'VK_REC_NAME' => 'Receiver Name',
            'VK_SND_ACC' => '111222333',
            'VK_SND_NAME' => 'Sender Name',
            'VK_REF' => '',
            'VK_MSG' => $pay_info['pay_message'],
            'VK_T_DATETIME' => date("d.m.Y"),
        );
    }


    /**
     * Check user activation
     *
     * @return bool|array
     */
    function checkActivation($return_id) {
        switch ($return_id) {
            case 1101:
                $bankMacFields = $this->generateMacFields();
                if (isset($bankMacFields['VK_SERVICE'])) {
                    if ($bankMacFields['VK_SERVICE'] == $return_id) {
                        return $bankMacFields;
                    }
                }
            break;
            case 3002:
                return array('VK_INFO' => 'ISIK:' . $_GET['id'] . ';NIMI:FIRSTNAME LASTNAME');
            break;
        }
        return false;
    }
}
