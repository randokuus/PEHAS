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

/* @var IsicDB_Cards $isicDbCards */
$isicDbCards = IsicDB::factory('Cards');
/* @var IsicDB_CardDataSync $isicDbCardDataSync */
$isicDbCardDataSync = IsicDB::factory('CardDataSync');

echo "<pre>\n";
echo date('H:i:s') . "\n";
$cardIdList = $isicDbCards->findAllActiveRecordsWithPanWithoutDataSync();
foreach ($cardIdList as $cardId) {
    echo $cardId;
    $cardRecord = $isicDbCards->getRecord($cardId);
    $isicDbCardDataSync->scheduleCard($cardRecord);
    echo "\n";
}

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
