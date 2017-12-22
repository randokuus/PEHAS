<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Seb extends Pangalink
{

    function _getBankKeyFile()
    {
        //return SITE_PATH . '/seb/.cert/eypcert.pem';
        return SITE_PATH . '/seb/.cert/eyp_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        //return SITE_PATH . '/seb/.cert/privkey.pem';
        return SITE_PATH . '/seb/.cert/kaupmees_priv.pem';
    }

    function _getBankLink()
    {
        //return  'https://www.seb.ee/cgi-bin/unet3.sh/un3min.r';
        return 'https://www.seb.ee/cgi-bin/dv.sh/un3min.r';
    }

    function _getSndId()
    {
        //return 'ksystem';
        return 'testvpos';
    }

    function _getCharset()
    {
        //return 'iso-8859-1';
        return 'UTF-8';
    }
    
    function _getBankId()
    {
        return 1;
    }
}