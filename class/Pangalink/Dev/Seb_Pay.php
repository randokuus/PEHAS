<?php
require_once(SITE_PATH . '/class/Pangalink.php');

class Pangalink_Seb_Pay extends Pangalink
{

    function _getBankKeyFile()
    {
        return SITE_PATH . '/seb_eyl/.cert/eyp_pub.pem';
    }

    function _getPrivateKeyFile()
    {
        return SITE_PATH . '/seb_eyl/.cert/kaupmees_priv.pem';
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

    function _getCurrency()
    {
        return 'EUR';
    }

    function _getBankAccount() {
        return '10002050618003';
    }

    function _getRecipientName() {
        return "MTÜ Eesti Üliõpilaskondade Liit";
    }

    function _getReferenceNumber() {
        return '';
    }
}