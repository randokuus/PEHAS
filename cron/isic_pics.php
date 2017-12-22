<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

function getPicFiles($pic) {
    $pics = false;
    if (file_exists(SITE_PATH . "" . $pic)) {
        $pics["main"] = $pic;
    }
    $t_pic = str_replace(".jpg", "_thumb.jpg", $pic);
    if (file_exists(SITE_PATH . $t_pic)) {
        $pics["thumb"] = $t_pic;
    }
    return $pics;
}

function copyCardPic($pics) {
    foreach ($pics as $pic) {
        $pi = pathinfo($pic);
        @copy(SITE_PATH . $pic, SITE_PATH . "/upload/itcpics/" . $pi["basename"]);
    }
}

$sql = "SELECT * FROM module_isic_card WHERE school_id = 3 AND pic <> '' ORDER BY person_name_last";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    echo $data["person_name_first"] . " " . $data["person_name_last"];
    $pics = getPicFiles($data["pic"]);
    if (is_array($pics)) {
        echo " - " . $pics["main"] . " / " . $pics["thumb"];
        copyCardPic($pics);
    }
    echo "<br>\n";
}
