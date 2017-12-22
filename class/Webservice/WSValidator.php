<?php
require_once(SITE_PATH . "/class/Webservice/WSValidator.php");

class WSValidator {

    function isEmpty($val) {
        return (!$val && ($val !== 0) && ($val !== "0"));
    }

    function isValidNumberType($type) {
        return (($type == 1) || ($type == 2) || ($type == 3));
    }

    function isValidCurrency($currency) {
        return $currency == 'EEK' || $currency == 'EUR';
    }

    function isValidSum($sum) {
        return is_numeric($sum);
    }

    function isValidDeviceType($type) {
        return (($type == 1) || ($type == 2));
    }

    function isValidId($id) {
        return !WSValidator::isEmpty($id);
    }
}
