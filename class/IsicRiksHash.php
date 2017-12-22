<?php

class IsicRiksHash {
    function IsicRiksHash() {
    }

    function encode ($str) {
        $str_hash = "";

        // step 1. reversing string
        $str_rev = strrev($str);
        
        for ($i = 0; $i < strlen($str_rev); $i++) {
            // step 2. ascii values of the characters
            $str_ord = ord(substr($str_rev, $i, 1));
            // step 3. generating random values for every symbol
            $str_rnd = rand(0, 255);
            //step 4. calculating xor of the two values
            $str_xor = $str_ord ^ $str_rnd;
            // step 5. generating hex-values and final hash
            $str_hash .= str_pad(dechex($str_xor), 2, "0", STR_PAD_LEFT) . str_pad(dechex($str_rnd), 2, "0", STR_PAD_LEFT);
        }
        
        return $str_hash;
    }

    function decode($str) {
        $str_rev = "";

        // if length of the string can not be diviced by 4, then it's invalid string for our algorithm
        if (strlen($str) % 4 != 0) {
            return false;
        }
        for ($i = 0; $i < strlen($str); $i += 4) {
            // step 1. converting all the hex-values to decimal values
            $str_xor = hexdec(substr($str, $i, 2));
            $str_rnd = hexdec(substr($str, $i + 2, 2));
            // step 2. calculating xor of the xor and rnd to get original value
            $str_ord = $str_rnd ^ $str_xor;
            // step 3. getting chr values of ord-string
            $str_rev .= chr($str_ord);
        }
        // step 4. reversing string and returning decoded value
        return strrev($str_rev);
    }
}
