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
$sql = "SELECT person_number, type_id, isic_number FROM module_isic_card WHERE state_id IN (1, 2)";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
        $count++;
//        echo $count . ". " . $data["person_number"] . ": " . $data["tot"] . "<br />\n";

        $sql = "SELECT module_isic_application.*, module_isic_card_type.name AS type_name, module_isic_school.name as school_name FROM module_isic_application, module_isic_card_type, module_isic_school WHERE module_isic_application.type_id = module_isic_card_type.id and module_isic_application.school_id = module_isic_school.id AND module_isic_application.person_number = '" . $data["person_number"] . "' AND module_isic_application.type_id = " . $data["type_id"] . " AND NOT(module_isic_application.state_id IN (6, 7))";
        $sq2->query($db, $sql);
        while ($data2 = $sq2->nextrow()) {
            echo $data2["id"] . "," . $data2["person_name_first"] . "," . $data2["person_name_last"] . "," . $data2["person_number"]  . "," . $data2["type_name"] . "," . $data2["school_name"] . "," . $data["isic_number"] . "\n";
        }

}
