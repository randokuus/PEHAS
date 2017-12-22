<?php
require_once(SITE_PATH . '/class/Epayment.php');

class Epayment_Nordea extends Epayment {

    function _getMacKey()
    {
        return 'LEHTI';
    }

    function _getBankLink()
    {
        return  'https://netbank.nordea.com/pnbepaytest/epayn.jsp';
    }

    function _getSndId()
    {
        return '12345678';
    }

    function _getBankId()
    {
        return 4;
    }

    function _getBankAccount() {
        return '';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}