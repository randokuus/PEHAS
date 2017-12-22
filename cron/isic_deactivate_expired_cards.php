<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
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
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicDB::factory('Cards');

echo "Checking soon expiring cards ...\n";
$card_count = $ic->setCollateralFreeForSoonExpiringCards();
echo "Cards with collateral set free: " . $card_count . "\n";

echo "Deactivating expired cards ...\n";
$card_count = $ic->deactivateExpiredCards();
echo "Deactivated cards: " . $card_count;
