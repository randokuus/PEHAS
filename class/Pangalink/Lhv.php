<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Lhv extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/lhv/.cert/lhv-banklink-cert.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/lhv/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.lhv.ee/banklink';
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