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
require_once(SITE_PATH . "/class/FileUploader.php");

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

$pic_path = SITE_PATH . "/tpic/";
$file_uploader = new FileUploader();

$sql = "SELECT * FROM module_isic_card WHERE school_id = 3 AND pic = '' ORDER BY person_name";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    echo $data["person_name"];
    $fname = $pic_path . utf8_decode($data["person_name"]) . ".jpg";
    if (file_exists($fname)) {
        $pic_fname = "/upload/isic/" . 'ISIC' . str_pad($data["id"], 10, '0', STR_PAD_LEFT) . ".jpg";
        $sql = "UPDATE module_isic_card SET pic = '" . $pic_fname . "' WHERE id = " . $data["id"];
        $sq2->query($db, $sql);
        $dest = SITE_PATH . $pic_fname;
        echo " - PIC";
        $pic_saved = $file_uploader->processUploadedImage(
            $fname, $dest, "261x261", null, 'replace', false);
    } else {
        echo " - <b>NOT</b>";
    }
    echo "<br>\n";
}
