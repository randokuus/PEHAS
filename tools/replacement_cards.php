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

echo "Added, Status, Active, Returned, Type, Type Name, Status, Status Name, School, School Name, Person Name, Person Number, Isic Number, Chip Number, Expiration, Exported\n";

$sql = "SELECT module_isic_card.person_number, count(*) as tot_cards FROM module_isic_card group by person_number order by tot_cards desc";
$sq->query($db, $sql);
while ($datat = $sq->nextrow()) {
    if ($datat["tot_cards"] <= 1) {
        break;
    } else {        
        $sql = "SELECT module_isic_card.*, module_isic_school.name AS school_name, module_isic_card_type.name AS type_name, IF(module_isic_card_status.id, module_isic_card_status.name, '') AS status_name FROM module_isic_school, module_isic_card_type, module_isic_card LEFT JOIN module_isic_card_status ON module_isic_card.status_id = module_isic_card_status.id WHERE module_isic_card.person_number = '" . $datat["person_number"] . "' AND module_isic_card.school_id = module_isic_school.id AND module_isic_card.type_id = module_isic_card_type.id order by module_isic_card.adddate";
        $sq2->query($db, $sql);
        while ($data = $sq2->nextrow()) {
            echo $data["adddate"] . ", " . $data["status_id"] . ", " . $data["active"] . ", " . $data["returned"] . ", " . $data["type_id"] . ", "  . $data["type_name"] . ", " . $data["status_id"] . ", "  . $data["status_name"] . ", " . $data["school_id"] . ", " . $data["school_name"] . ", " . $data["person_name"] . ", " . $data["person_number"] . ", " . $data["isic_number"] . ", " . $data["chip_number"] . ", " . $data["expiration_date"] . ", ". ", ". $data["exported"] . "\n";
        }
    }
}

