<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Seb_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/pay/.cert/seb/eyp_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/pay/.cert/seb/kaupmees_priv.pem';
    }

    function _getBankLink()
    {
        return  'https://www.seb.ee/cgi-bin/dv.sh/un3min.r';
    }

    function _getSndId()
    {
        return 'testvpos';
    }

    function _getBankId()
    {
        return 1;
    }

    function _getBankAccount() {
        return '10002050618003';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}