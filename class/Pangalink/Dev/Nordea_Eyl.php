<?php
require_once(SITE_PATH . '/class/Epayment.php');

class Epayment_Nordea extends Epayment {

    function _getMacKey()
    {
//        return 'YfaGa8Jjysqr3MW5xa8RJ9rNadN37mV4';
        return 'LEHTI';
    }

    function _getBankLink()
    {
//        return  'https://netbank.nordea.com/pnbepay/epayn.jsp';
        return  'https://netbank.nordea.com/pnbepaytest/epayn.jsp';
    }

    function _getSndId()
    {
//        return '11458311';
        return '12345678';
    }

    function _getBankId()
    {
        return 4;
    }

    function _getCurrency()
    {
        return 'EEK';
//        return 'EUR';
    }

    function _getBankAccount() {
        return '';
//        return '221042638015';
    }

    function _getRecipientName() {
        return "Eesti Üliõpilaskondade Liit";
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}