<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Krediidi extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/krediidi/.cert/krediidi_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/krediidi/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://i-pank.krediidipank.ee/teller/autendi';
    }

    function _getSndId()
    {
        return 'MINUKOOL';
    }

    function _getCharset()
    {
        return 'utf-8';
    }
}