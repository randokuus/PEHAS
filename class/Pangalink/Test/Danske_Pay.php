<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Danske_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/pay/.cert/danske/pangalink.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/pay/.cert/danske/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www2.danskebank.ee/ibank/pizza/pizza';
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