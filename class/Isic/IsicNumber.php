<?php

class IsicNumber {
    const DECIMALS = 2;
    const DEC_POINT = ',';
    const THOUSANDS_SEP = ' ';
    
    public static function getMoneyFormatted($number) {
        return number_format($number, self::DECIMALS, self::DEC_POINT, self::THOUSANDS_SEP);
    }
}
