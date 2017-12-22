<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Lhv_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/pay/.cert/lhv/lhv-banklink-cert.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/pay/.cert/lhv/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.lhv.ee/banklink';
    }

    function _getSndId()
    {
        return 'EYL';
    }

    function _getBankId()
    {
        return 8;
    }

    function _getBankAccount() {
        return 'EE177700771000937169';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}