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
require_once(SITE_PATH . "/class/IsicUserStatusChecker.php");

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

$iusc = new IsicUserStatusChecker($database);

/*
$userStatuses = IsicDB::factory('UserStatuses');
$validity = IsicDB::factory('CardValiditiez');
$dbCards = IsicDB::factory('Cards');
$dbUserStatusTypes = IsicDB::factory('UserStatusTypes');
$dbUsers = IsicDB::factory('Users');
$dbUserGroups = IsicDB::factory('UserGroups');
$common = IsicCommon::getInstance();
$systemUserId = $common->getLogUserId(0);
*/
echo "<pre>\n";
echo date('H:i:s') . "\n";
// regular manual
echo "Check regular manual:\n";
$iusc->checkActiveManualStatuses(1);
// bank manual
echo "Check bank manual:\n";
$iusc->checkActiveManualStatuses(3);
// age restricted manual
echo "Check age restricted manual:\n";
$iusc->checkActiveAgeRestrictedStatuses();
echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
