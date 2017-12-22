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

$count = 0;
$sql = "SELECT module_isic_card_number.*, IF (module_isic_card.id, module_isic_card.id, 0) AS card_exist FROM module_isic_card_number LEFT JOIN module_isic_card ON module_isic_card_number.card_number = module_isic_card.isic_number WHERE module_isic_card_number.reserved = 1";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    if ($data["card_exist"] == 0) {
        $count++;
        echo $count . ". " . $data["card_number"] . "<br />\n";
        $sql = "UPDATE module_isic_card_number SET reserved = 0, reserved_date = '0000-00-00 00:00:00' WHERE id = " . $data["id"];
        echo "$sql\n";
        $sq2->query($db, $sql);
    }
}
