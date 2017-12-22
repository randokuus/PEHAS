<?php
require_once(SITE_PATH . '/class/PangalinkMock.php');

class Pangalink_Hansa extends PangalinkMock
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
        return  SITE_URL . '/hansa/id/' . $GLOBALS['id'];
        //return  'https://www.swedbank.ee/banklink';
    }

    function _getSndId()
    {
        return 'KOOLIS';
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}