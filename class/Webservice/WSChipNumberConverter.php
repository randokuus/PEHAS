<?php

class WSChipNumberConverter
{
    public static function convert($chip)
    {
        if (strlen($chip) != 14) {
            return $chip;
        }
        return substr($chip, 2, 8);
    }
}