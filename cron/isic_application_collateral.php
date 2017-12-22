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
require_once(SITE_PATH . "/class/IsicPayment.php");

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

$ic = IsicCommon::getInstance();
$ip = new IsicPayment();
$appl_list = array(
);

$r = &$database->query('
    SELECT 
        `module_isic_application`.* 
    FROM 
        `module_isic_application` 
    WHERE 
        `type_id` = ! AND 
        `school_id` = ! AND 
        `state_id` = ! AND 
        `application_type_id` = !'
    ,
    2,
    3,
    1,
    2
);

while ($data = $r->fetch_assoc()) {
    echo $data['id'] . ", prev_card: " . $data['prev_card_id'] . ", app coll: " . $data['confirm_payment_collateral'];
    $last_card = $ic->getUserLastCard($data['person_number'], $data['school_id'], $data['type_id']);
    if ($last_card) {
        $payment = $ip->getPaymentByCard($last_card['id'], $ip->payment_type_collateral);
        /*
        if ($payment) {
            $ip->setPaymentExpired($last_card['id']);
            $ic->setApplicationCollateralPayment($last_card['id'], $data['id']);
            $ic->saveApplicationChangeLog($ic->log_type_mod, $data['id'], $data, $ic->getApplicationRecord($data['id']), $ic->system_user);
        }
        */
        echo ", calculated: " . $last_card['id'] . ", kind: " . $last_card['kind_id'] . ", coll: " . $last_card['confirm_payment_collateral'] . ", payment free: " . $payment['free'] . ", expired: " . $payment['expired'] . ", payment returned: " . $payment['payment_returned'];
    }
    echo "\n<br>";
}