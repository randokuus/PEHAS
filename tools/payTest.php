<?php
error_reporting(0);
chdir('..');
include("class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
    hokusPokus();
}
else {
    trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/IsicPaymentTest.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data_settings = $data = $GLOBALS['site_settings'];

// $GLOBALS['user_data'][6] = 1;

// init language object
$lan = new Language($database, "");
$language = $lan->lan();
$isicPayment = new IsicPaymentTest();

$applId = 145745;

// $applData = $isicPayment->isic_common->getApplicationRecord($applId);
// print_r($applData);
//
//

$pay_info = $isicPayment->getPaymentInfoAppl($applId);

var_dump($pay_info);