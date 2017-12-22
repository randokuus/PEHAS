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
$sql = "SELECT person_number, type_id, count(id) as tot FROM module_isic_card WHERE active = 1 and kind_id = 1 group by person_number, type_id order by tot desc";
$sq->query($db, $sql);
while ($data = $sq->nextrow()) {
    if ($data["tot"] > 1) {
        $count++;
        //echo $count . ". " . $data["person_number"] . "<br />\n";
        $sql = "SELECT module_isic_card.*, module_isic_card_type.name AS type_name, module_isic_school.name as school_name FROM module_isic_card, module_isic_card_type, module_isic_school WHERE module_isic_card.type_id = module_isic_card_type.id and module_isic_card.school_id = module_isic_school.id AND module_isic_card.person_number = '" . $data["person_number"] . "' AND module_isic_card.active = 1 AND module_isic_card.kind_id = 1";
        $sq2->query($db, $sql);
        while ($data2 = $sq2->nextrow()) {
            echo $data2["id"] . "," . $data2["person_name_first"] . "," . $data2["person_name_last"] . "," . $data2["person_number"]  . "," . $data2["isic_number"] . "," . $data2["expiration_date"] . "," . $data2["exported"] . "," . $data2["type_name"] . "," . $data2["school_name"] . "\n";
        }
    } else {
        break;
    }
}
