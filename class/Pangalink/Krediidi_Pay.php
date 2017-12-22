<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Krediidi_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/pay/.cert/krediidi/krediidi_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/pay/.cert/krediidi/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://i-pank.krediidipank.ee/teller/maksa';
    }

    function _getSndId()
    {
        return 'MINUKOOL';
    }

    function _getBankId()
    {
        return 7;
    }

    function _getBankAccount() {
        return 'EE854204278699999800';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}