<?php

class MobileNumberValidator {
    const COUNTRY_PREFIX = '372';
    const MIN_LENGTH = 7;
    const MAX_LENGTH = 8;

    public static function isValid($number) {
        $converted = self::convertNumber($number);
        return $converted !== null;
    }

    public static function convertNumber($number) {
        $clean = self::cleanNumber($number);
        if (!self::isValidMinLength($clean)) {
            return null;
        }
        if (substr($clean, 0, strlen(self::COUNTRY_PREFIX)) == self::COUNTRY_PREFIX) {
            $clean = substr($clean, strlen(self::COUNTRY_PREFIX));
        }
        if (!self::isValidLength($clean)) {
            return null;
        }
        if (!self::isValidPrefix($clean)) {
            return null;
        }
        return self::COUNTRY_PREFIX . $clean;
    }

    public static function cleanNumber($number) {
        $clean = '';
        for ($i = 0; $i < strlen($number); $i++) {
            $digit = $number[$i];
            if ($digit >= '0' && $digit <= '9') {
                $clean .= $digit;
            }
        }
        return $clean;
    }

    public function isValidMinLength($number) {
        return strlen($number) >= self::MIN_LENGTH;
    }

    public function isValidLength($number) {
        $len = strlen($number);
        return $len >= self::MIN_LENGTH && $len <= self::MAX_LENGTH;
    }

    public function isValidPrefix($number) {
        return substr($number, 0, 1) == '5';
    }
}