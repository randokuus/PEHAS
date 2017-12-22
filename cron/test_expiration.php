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

$tmpTime = mktime(0, 0, 0, 9, 5, 2012);
$isicCommon = IsicCommon::getInstance();
$isicCommon->setCurrentTime($tmpTime);

echo "<pre>\n";
echo date('H:i:s') . "\n";
/*
$person_number = '48311194273';
$school_id = '32';
$type_id = '20';
$lastCard = $isicCommon->getUserLastCard($person_number, $school_id, $type_id);
print_r($lastCard);
*/

$compare = '2012-12-31';
for ($prolongLimit = 0; $prolongLimit < 20; $prolongLimit++) {
    echo $prolongLimit . ': ' . $isicCommon->calcExpirationProlongLimit($compare, $prolongLimit) . "\n";
}

//echo $isicCommon->getCardExpiration(20, $compare, true);

echo "\ndone ..." . date('H:i:s');
echo "\n</pre>\n";
