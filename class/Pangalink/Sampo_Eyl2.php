<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Sampo_Eyl2 extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/sampo_eyl2/.cert/sampo_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/sampo_eyl2/.cert/privkey.pem';
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

    function _getBankAccount() {
        return '332117290002';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}