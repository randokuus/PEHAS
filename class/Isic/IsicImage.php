<?php

require_once SITE_PATH . '/class/Isic/IsicDate.php';

class IsicImage {
    const dummyUrl = 'img/tyhi.gif';
    const IMG_PLACEHOLDER = 'img/img_placeholder_<SIZE>.png';
    
    public static function getImgTagForUrl($url) {
        $img_url = $url;
        if (!$img_url) {
            $img_url = self::getImgPlaceholder();
        }
        return "<img src=\"" . $img_url . "\" alt=\"\" border=\"0\">";
    }
    
    public function getImgPlaceholder($size = 'thumb') {
        if (!in_array($size, array('thumb', 'big'))) {
            $size = 'thumb';
        }
        return str_replace('<SIZE>', $size, self::IMG_PLACEHOLDER);
    }
    
    public static function getPopUpForUrl($url) {
        if ($url) {
            return "javascript:openPicture('" . $url ."')";
        }
        return '#';
    }
    
    public static function getPictureUrl($pic, $size) {
        if (!$pic) {
            return '';
        }
        $path = self::getPictureName($pic, $size);
        if (@file_exists(SITE_PATH . substr($path, strpos($path, "upload") - 1))) {
            return SITE_URL . $path;
        }
        return '';
    }
    
    public static function getPictureUrlOrDummyUrlIfNotFound($pic, $size) {
        $url = self::getPictureUrl($pic, $size);
        if (!$url) {
            $url = self::getImgPlaceholder($size);
        }
        return $url;
    }
    
    public static function getPictureName($pic, $size) {
        if (strpos($pic, "_thumb") !== false) {
            return ($size == 'big') ? str_replace("_thumb", "", $pic) : $pic;
        } else {
            return ($size == 'big') ? $pic : str_replace(".", "_thumb.", $pic);
        }
    }

    public static function getAgeInMonths($pic) {
        if (!file_exists($pic)) {
            return -1;
        }
        return IsicDate::getDiffInMonths(filemtime($pic), time());
    }
}
