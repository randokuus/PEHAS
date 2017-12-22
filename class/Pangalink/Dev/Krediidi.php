<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Krediidi extends Pangalink
{

    function _getBankKeyFile()
    {
        //return SITE_PATH . '/krediidi/.cert/krediidi_pub.pem';
        return SITE_PATH . '/krediidi/.cert-test/krediidi_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        //return SITE_PATH . '/krediidi/.cert/privkey.pem';
        return SITE_PATH . '/krediidi/.cert-test/privkey.pem';
    }

    function _getBankLink()
    {
        //return  'https://i-pank.krediidipank.ee/teller/autendi';
        return 'https://secure.krediidipank.ee/teller/autendi';
    }

    function _getSndId()
    {
        return 'PANGALINK1';
    }

    function _getCharset()
    {
        return 'utf-8';
    }
}