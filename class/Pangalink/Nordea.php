<?php
require_once(SITE_PATH . '/class/Eident.php');

class Eident_Nordea extends Eident {

    function _getMacKey()
    {
        return 'YfaGa8Jjysqr3MW5xa8RJ9rNadN37mV4';
//        return 'LEHTI';
    }

    function _getBankLink()
    {
//        return  'https://netbank.nordea.com/pnbeidtest/eidn.jsp';
        return  'https://netbank.nordea.com/pnbeid/eidn.jsp';
    }

    function _getSndId()
    {
        return '11458311';
//        return '12345678';
    }

    function _getCharset()
    {
        return 'iso-8859-1';
    }
}