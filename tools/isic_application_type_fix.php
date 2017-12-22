<?php
include_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicPayment.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicCommon::getInstance();
$ip = new IsicPayment();

$count = 0;
$res =& $t_db->query("
    SELECT 
        module_isic_application.*
    FROM 
        module_isic_application, 
        module_isic_card 
    WHERE 
        module_isic_application.prev_card_id = module_isic_card.id AND
        module_isic_card.kind_id = 2 AND
        module_isic_application.state_id < 6");
while ($data = $res->fetch_assoc()) {
    $count++;
    $cost_data = $ip->getCardCostCollData($data["person_number"], $data["school_id"], $data["type_id"], $data["adddate"]);
    $payment = $ip->getPaymentByCard($data['prev_card_id'], $ip->payment_type_collateral);
//    print_r($cost_data);
    echo $count . ". " . 
        $data["person_number"] . ": " . 
        $data["person_name_first"] . " " . 
        $data["person_name_last"] . ": "  . 
        $data['application_type_id'] . " <-> " . 
        $cost_data["type"] . ", last card: " . 
        $data['prev_card_id'] . " <-> " . 
        $cost_data['last_card_id'] . ", coll: " . 
        $data['confirm_payment_collateral'] . ", cost: " . 
        $data['confirm_payment_cost'] . ", payment: " .
        ($payment ? $payment['free'] : "-") .
        " <br />\n";
        
    $t_db->query("UPDATE module_isic_application SET application_type_id = ! WHERE id = !", $cost_data["type"], $data["id"]);
    $t_db->query("UPDATE module_isic_application SET prev_card_id = ! WHERE id = !", $cost_data["last_card_id"], $data["id"]);
    $ic->saveApplicationChangeLog($ic->log_type_mod, $data['id'], $data, $ic->getApplicationRecord($data['id']), $ic->system_user);
    
}
