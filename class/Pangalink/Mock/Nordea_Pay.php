<?php
require_once(SITE_PATH . '/class/Epayment.php');

class Epayment_Nordea extends Epayment {

    function _getMacKey()
    {
        return 'Lss5aZ8aZepUrM3kCArZ8uKrqQbn2KQV';
//        return 'LEHTI';
    }

    function _getBankLink()
    {
        return  'https://netbank.nordea.com/pnbepay/epayn.jsp';
//        return  'https://netbank.nordea.com/pnbepaytest/epayn.jsp';
    }

    function _getSndId()
    {
        return '80059438';
//        return '12345678';
    }

    function _getBankId()
    {
        return 4;
    }

    function _getBankAccount() {
        return '17001680027';
//        return '';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}