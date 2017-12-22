<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/" . DB_TYPE . ".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicDB.php");
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
$GLOBALS["language"] = & $language;

$pay = new IsicPayment();
$dbAppl = IsicDB::factory('Applications');
$applRec = $dbAppl->getRecord(131830);

print_r($applRec);
$applRec['confirm_payment_cost'] = 0;

$costData = $pay->getCardCostCollDeliveryData($applRec);
print_r($costData);

var_dump($pay->isApplicationPaymentComplete($applRec, $costData));

// $res = $pay->getCardCostCollData('46502056515', 1402, 3);


// $comp = IsicDB::factory('SchoolCompensationsUser');
// $res = $comp->getEHLCompensationDataByPersonCardType('48204080025', 3);
//
// print_r($res);

