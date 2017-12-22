<?php

class WSIp {
    /**
     * Checks if given ip is allowed (supports wildcard)
     *
     * @param string $ip ip address
     * @return boolean (true|false)
    */
    function isIpAllowed($allowedIpList, $ip) {
        foreach ($allowedIpList as $t_ip) {
            if (strpos($t_ip, '*') !== false) {
                $t_ip = substr($t_ip, 0, strpos($t_ip, '*'));
                if (strlen($t_ip) <= strlen($ip) && ($t_ip == substr($ip, 0, strlen($t_ip)))) {
                    return true;
                }
            } elseif ($t_ip == $ip) {
                return true;
            }
        }
        return false;
    }    
}
