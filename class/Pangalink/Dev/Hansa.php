<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Hansa extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/hansa/.cert/IpizzaHPavalikvoti.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/hansa/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.swedbank.ee/banklink';
    }

    function _getSndId()
    {
        return 'KOOLIS';
    }

    function _getCharset()
    {
        return 'utf-8';
    }
}