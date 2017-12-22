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
require_once(SITE_PATH . "/class/IsicCommon.php");

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

$ic = new IsicCommon();



$count = 0;
$sql = "SELECT module_user_users.* FROM module_user_users WHERE user_code <> '' AND birthday = '0000-00-00' ORDER BY user ";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    $t_bd = $ic->calcBirthdayFromNumber($data["user_code"]);
    $t_time = strtotime($t_bd);
    if ($t_time != false && $t_time != -1) {
        echo $data["user"] . "," . $data["name_first"] . "," . $data["name_last"] . ": " . $data["user_code"] . ": " . $t_bd . "\n";
        $sq2->query($db, "UPDATE module_user_users SET birthday = '" . $t_bd . "' WHERE user = " . $data["user"]);
    }
}
