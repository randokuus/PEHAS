<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Swed_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/pay/.cert/swed/IpizzaHPavalikvoti.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/pay/.cert/swed/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.swedbank.ee/banklink';
    }

    function _getSndId()
    {
        return 'EYL';
    }

    function _getBankId()
    {
        return 2;
    }

    function _getBankAccount() {
        return '221042638015';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}