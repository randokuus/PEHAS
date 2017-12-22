<?php
require_once(SITE_PATH . '/class/PangalinkMock.php');

class Pangalink_Seb extends PangalinkMock
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
        //return  'https://www.seb.ee/cgi-bin/unet3.sh/un3min.r';
        return SITE_URL . '/seb/id/' . $GLOBALS['id'];
    }
    
    function _getSndId()
    {
        return 'ksystem';
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}