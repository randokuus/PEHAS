<?php

class WSDate {
    /**
     * converts given date from yyyymmddThhmmssZ into yyyy-mm-dd hh:mm:ss
     * @access private
     * @param string $tstr string value of date
     * @return string converted value of datetime
    */
    function string2date($tstr) {
        $tstr = str_replace("Z", "", str_replace("T", "", $tstr));
        preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $tstr, $regs);
        return date("Y-m-d H:i:s", mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]));
    }

    function getValidFromDate($from) {
        if (!$from) {
            return "0000-00-00";
        } else {
            return WSDate::string2date($from);
        }
    }

    function getValidUntilDate($until) {
        if (!$until) {
            return date('Y-m-d H:i:s');
        } else {
            return WSDate::string2date($until);
        }
    }
}
