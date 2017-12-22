<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Seb extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/seb/.cert/eypcert.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/seb/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.seb.ee/cgi-bin/unet3.sh/un3min.r';
    }

    function _getSndId()
    {
        return 'ksystem';
    }

    function _getCharset()
    {
        return 'UTF-8';
    }
    
    function _getBankId()
    {
        return 1;
    }
}