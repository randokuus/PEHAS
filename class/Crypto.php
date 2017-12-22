<?php

class crypto {
    var $plaintext = false;
    var $secret_key = "e64d82be8b444d920b1f7b60433e2c49";
    var $iv = "";
    var $iv_array = array (121, 241, 10, 1, 132, 74, 11, 39, 255, 91, 45, 78, 14, 211, 22, 62);

    function crypto ($plaintext = false) {
        for ($i = 0; $i < sizeof($this->iv_array); $i++) {
            $this->iv .= chr($this->iv_array[$i]);
        }
        
        $this->plaintext = $plaintext;
    }

    function encryptData($value) {
        if (!$value && $value !== 0 && $value !== "0") {
            return false;
        }
        if ($this->plaintext) {
            return $value;
        }
        if (function_exists("mcrypt_encrypt")) {
            $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->secret_key, $value, MCRYPT_MODE_CBC, $this->iv);
            return trim(base64_encode($crypttext)); //encode data
        }
        return false;
    }
    
    function decryptData($value) {
        if (!$value && $value !== 0 && $value !== "0") {
            return false;
        }
        if ($this->plaintext) {
            return $value;
        }
        if (function_exists("mcrypt_decrypt")) {
            $crypttext = base64_decode($value); //decode data
            $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->secret_key, $crypttext, MCRYPT_MODE_CBC, $this->iv);
            return trim($decrypttext);
        }
        return false;
    }

    function setSecretKey($key) {
        $this->secret_key = $key;
    }
}