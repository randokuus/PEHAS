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
$sq3 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$count = 0;
$sql = "SELECT module_user_users.* FROM module_user_users WHERE user_code <> '' AND name = '' AND name_first = '' ORDER BY user_code LIMIT 10";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    $sql = "SELECT person_name_first, person_name_last FROM module_isic_card WHERE person_number = '" . $data["user_code"] . "' ";
    $sq2->query($db, $sql);
    echo $data["user_code"] . ": " . $data["name_first"] . " " . $data["name_last"] . "\n";
    echo "===================\n";
    while ($datac = $sq2->nextrow()) {
        echo $datac["person_number"] . ": " . $datac["id"] . ", " . $datac["person_name"] . ", " . $datac["person_name_first"] . ", " . $datac["person_name_last"] . "\n";
        /*
        $sql = "UPDATE module_user_users SET name_first = '" . mysql_escape_string($datac["person_name_first"]) . "', name_last = '" . mysql_escape_string($datac["person_name_last"]) . "' WHERE user = " . $data["user"];
        $sq3->query($db, $sql);
        */
    }
    echo "===================\n";
}
