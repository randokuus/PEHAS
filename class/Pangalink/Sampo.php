<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Sampo extends Pangalink
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
        return  'https://www2.sampopank.ee/ibank/pizza/pizza';
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