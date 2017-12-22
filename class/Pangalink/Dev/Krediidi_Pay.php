<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Krediidi_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        //return SITE_PATH . '/krediidi_pay/.cert/krediidi_pub.pem';
        return SITE_PATH . '/pay/.cert-dev/krediidi/krediidi_pub.pem';
    }

    function _getPrivateKeyFile()
    {
//        return SITE_PATH . '/krediidi_pay/.cert/privkey.pem';
        return SITE_PATH . '/pay/.cert-dev/krediidi/privkey.pem';
    }

    function _getBankLink()
    {
        //return  'https://i-pank.krediidipank.ee/teller/maksa';
        return  'https://secure.krediidipank.ee/teller/maksa';
    }

    function _getSndId()
    {
        return 'PANGALINK1';
    }

    function _getBankId()
    {
        return 7;
    }

    function _getBankAccount() {
        //return '221042638015';
        return '4278699999800';
    }

    function _getRecipientName() {
        //return "Eesti Üliõpilaskondade Liit";
        return "TEST FIRMA AS";
    }

    function _getReferenceNumber() {
        return '';
    }
}