<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Sampo_Eyl extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/sampo_eyl/.cert/sampo_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/sampo_eyl/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www2.sampopank.ee/ibank/pizza/pizza';
    }

    function _getSndId()
    {
        return 'EYL';
    }

    function _getBankId()
    {
        return 3;
    }

    function _getCurrency()
    {
        return 'EEK';
    }

    function _getBankAccount() {
        return '332117290002';
    }

    function _getRecipientName() {
        return "MTÜ Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}