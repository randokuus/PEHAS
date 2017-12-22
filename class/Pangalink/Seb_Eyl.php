<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Seb_Eyl extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/seb_eyl/.cert/eypcert.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/seb_eyl/.cert/privkey.pem';
    }

    function _getBankLink()
    {
        return  'https://www.seb.ee/cgi-bin/unet3.sh/un3min.r';
    }

    function _getSndId()
    {
        return 'eyliit';
    }

    function _getBankId()
    {
        return 1;
    }

    function _getBankAccount() {
        return '10000003871019';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}