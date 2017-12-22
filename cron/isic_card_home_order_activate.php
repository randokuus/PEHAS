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
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicDB.php");
require_once(SITE_PATH . "/class/Isic/IsicDate.php");

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

$dbSettings = IsicDB::factory('GlobalSettings');
$dbApps = IsicDB::factory('Cards');

echo "<pre>\n";
echo date('H:i:s') . "\n";

$days = intval($dbSettings->getRecord('home_ordered_card_activation_days'));
$curTime = time();
$curDay = date('j', $curTime);
$curMon = date('n', $curTime);
$curYear = date('Y', $curTime);
$date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, $curMon, $curDay - $days, $curYear), 'Y-m-d');

echo 'Activated: ' . $dbApps->activateHomeOrderedCards($date) . "\n";

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
