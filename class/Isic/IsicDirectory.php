<?php

class IsicDirectory {
    public static function getAsSortedList($path) {
        $list = self::getAsList($path);
        if (is_array($list)) {
            sort($list);
        }
        return $list;
    }

    public static function getAsList($path) {
        $opendir = addslashes($path);
        $dir = @opendir($opendir);
        if (!$dir) {
            return;
        }
        $list = array();
        while (($file = @readdir($dir)) !== false) {
            $filePath = $opendir . $file;
            if (is_dir($filePath) || $file == "." || $file == "..") {
                continue;
            }
            $list[] = $file;
        }
        return $list;
    }
}
