<?php
require_once(SITE_PATH . '/class/PangalinkMock.php');

class Pangalink_Sampo extends PangalinkMock
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/sampo/.cert/sampo_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/sampo/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return SITE_URL . '/sampo/id/' . $GLOBALS['id'];
        //return  'https://www2.sampopank.ee/ibank/pizza/pizza';
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