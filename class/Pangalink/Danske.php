<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Danske extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/danske/.cert/pangalink.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/danske/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www2.danskebank.ee/ibank/pizza/pizza';
    }

    function _getSndId()
    {
        return 'koolisusteem';
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}