<?php

class IsicDate {
    const DEFAULT_DATE_FORMAT = 'd.m.Y';
    const DEFAULT_TIME_FORMAT = 'd.m.Y H:i:s';
    const EMPTY_DATE = '0000-00-00';
    const EMPTY_DATETIME = '0000-00-00 00:00:00';
    const EMPTY_DISPLAY_DATE = '';
    const YEAR_IN_SECONDS = 31536000;
    const EMPTY_DATETIME_IN_SECONDS = -4200000000; // this constant is smaller than 1900-01-01 but larger than 0000-00-00
    const DB_DATE_FORMAT = 'Y-m-d';
    const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function getTimeStampFormatted($time, $format = self::DEFAULT_DATE_FORMAT) {
        if (self::isTimeValid($time)) {
            return date($format, $time);
        }
        return self::EMPTY_DISPLAY_DATE;
    }

    private static function isTimeValid($time) {
        return $time && $time > self::EMPTY_DATETIME_IN_SECONDS;
    }

    public static function getDateFormatted($date, $format = self::DEFAULT_DATE_FORMAT) {
        return self::getTimeStampFormatted(strtotime($date), $format);
    }

    public static function getDateTimeFormatted($date, $format = self::DEFAULT_TIME_FORMAT) {
        return self::getDateFormatted($date, $format);
    }

    public static function getDateFormattedFromEuroToDb($date) {
        if (strlen($date) >= 10) {
            $tDate = substr($date, 6, 4) . "-" . substr($date, 3, 2) . "-" . substr($date, 0, 2);
            return self::getAsDate($tDate);
        }
        return self::EMPTY_DATE;
    }

    public static function getTimeFormattedFromEuroToDb($date) {
        return self::getDateFormattedFromEuroToDb($date) . substr($date, 10);
    }

    public static function getAsDate($dateOrDateTime) {
        $date = self::getDateFormatted($dateOrDateTime, self::DB_DATE_FORMAT);
        return $date ? $date : self::EMPTY_DATE;
    }

    public static function getCurrentTimeFormatted($format = self::DEFAULT_TIME_FORMAT) {
        return self::getTimeStampFormatted(time(), $format);
    }

    public static function isDefined($date) {
        return $date && $date != self::EMPTY_DATE && $date != self::EMPTY_DATETIME;
    }

    public static function isExpiredDate($date, $compareDate = '') {
        if (!$compareDate) {
            $compareDate = self::getCurrentTimeFormatted(self::DB_DATE_FORMAT);
        }
        return $date < $compareDate;
    }

    public static function getCurrentTimeAsTimeStamp() {
        return self::getCurrentTimeFormatted('Ymd') . 'T' . self::getCurrentTimeFormatted('His') . 'Z';
    }

    /**
     * Calculates birthday from social securit number (isikukood)
     *
     * @param str $socsecnum soscial security number
     * @return string birthday in format yyyy-mm-dd
    */
    public static function calcBirthdayFromNumber($socsecnum) {
        if (strlen($socsecnum) == 11) {
            if (substr($socsecnum, 0, 1) <= "2") {
                $century = "18";
            } elseif (substr($socsecnum, 0, 1) <= "4") {
                $century = "19";
            } else {
                $century = "20";
            }
            $t_date = $century . substr($socsecnum, 1, 2) . "-" . substr($socsecnum, 3, 2) . "-" . substr($socsecnum, 5, 2);
            if (strtotime($t_date) != false && strtotime($t_date) != -1 && strtotime($t_date) < time()) {
                return $t_date;
            }
        }
        return false;
    }

    public static function diffInYears($date1, $date2, $usingTimeStamps = true) {
        return self::dateDiff(min($date1, $date2), max($date1, $date2), $usingTimeStamps);
    }

    public static function getAgeInYears($birthday) {
        return self::dateDiff($birthday, self::getCurrentTimeFormatted(self::DB_DATE_FORMAT));
    }

    /*
     * http://www.developertutorials.com/php/calculating-date-difference-more-precisely-in-php-71/
    */
    public static function dateDiff($datefrom, $dateto, $using_timestamps = false) {
        if (!$using_timestamps) {
            $datefrom = strtotime($datefrom, 0);
            $dateto = strtotime($dateto, 0);
        }
        $difference = $dateto - $datefrom; // Difference in seconds

        $years_difference = floor($difference / self::YEAR_IN_SECONDS);
        if (mktime(date("H", $datefrom),
                              date("i", $datefrom),
                              date("s", $datefrom),
                              date("n", $datefrom),
                              date("j", $datefrom),
                              date("Y", $datefrom) + $years_difference) > $dateto) {

            $years_difference--;
        }
        if (mktime(date("H", $dateto),
                              date("i", $dateto),
                              date("s", $dateto),
                              date("n", $dateto),
                              date("j", $dateto),
                              date("Y", $dateto) - ($years_difference + 1)) > $datefrom) {

            $years_difference++;
        }
        return $years_difference;
    }

    /**
     * Check if given payment date is valid (not in the future)
     * @param string $date
     */
    public static function isValidPaymentDate($date) {
        return $date != self::EMPTY_DATE && $date <= self::getCurrentTimeFormatted(self::DB_DATE_FORMAT);
    }

    /**
     * Subtracts given amount of workdays from given date or current time if date not given
     *
     * @param $days
     * @param null $date
     * @return string
     */
    static public function subtractWorkDaysFromDate($days, $date = null) {
        if ($date) {
            $time = strtotime($date);
        } else {
            $time = time();
        }

        while ($days > 0) {
            $time = strtotime("-1 days", $time);
            if (date('N', $time) < 6) {
                $days--;  // count only work days in
            }
        }
        return self::getTimeStampFormatted($time, self::DB_DATE_FORMAT);
    }

    public static function getDiffInMonthsx($d1, $d2) {
        $t1 = new DateTime(self::getTimeStampFormatted($d1, self::DB_DATE_FORMAT));
        $t2 = new DateTime(self::getTimeStampFormatted($d2, self::DB_DATE_FORMAT));
        $interval = $t2->diff($t1);
        $diff = $interval->format('%y') * 12 + $interval->format('%m');
        return $diff;
    }


    public static function getDiffInMonths($startTime, $endTime) {
        $diffYears = date('Y', $endTime) - date('Y', $startTime);
        $diffMonths = date('n', $endTime) - date('n', $startTime);
        $diffDays = date('j', $endTime) - date('j', $startTime);

        $diff = $diffMonths;
        $diff -= ($diffDays >= 0) ? 0 : 1;
        $diff += $diffYears * 12;
        return $diff;
    }
}
