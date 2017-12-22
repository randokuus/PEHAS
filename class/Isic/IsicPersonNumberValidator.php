<?php
require_once(SITE_PATH . '/class/Isic/IsicDate.php');

class IsicPersonNumberValidator {
    public static function isValid($personNumber) {
        return strlen($personNumber) == 11 &&
            self::getBirthDate($personNumber) &&
            self::isValidControlCode($personNumber)
        ;
    }

    public static function isValidControlCode($personNumber) {
        $weights = array(
            array(1, 2, 3, 4, 5, 6, 7, 8, 9, 1),
            array(3, 4, 5, 6, 7, 8, 9, 1, 2, 3)
        );
        $sum = array(0, 0);
        for ($i = 0; $i < 11; $i++) {
            $numStr = substr($personNumber, $i, 1);
            if ($numStr < '0' || $numStr > '9') {
                return false;
            }
            $num = intval($numStr);
            if ($i == 10) {
                $check = $sum[0] % 11;
                $check = $check != 10 ? $check : $sum[1] % 11;
                $check = $check == 10 ? 0 : $check;
                return $num == $check;
            } else {
                $sum[0] += $weights[0][$i] * $num;
                $sum[1] += $weights[1][$i] * $num;
            }
        }

        return false;
    }

    private static function getBirthDate($personNumber) {
        return IsicDate::calcBirthdayFromNumber($personNumber);
    }
}