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

$cardIdList = array(
    385233,
    385234,
    385235,
    385236,
    385237,
    385238,
    385239,
    385240,
    385241,
    385242,
    385413,
    385414,
    385415,
    385416,
    384476,
    384678,
    384679,
    385417,
    385865,
    386355,
    386356,
    386357,
    386358,
    386359,
    386360,
    390047,
    390048,
    390049,
    390050,
    390269,
    390310,
    390309,
    390408,
    390409,
    390410,
    390411,
    390580,
    390581,
    390582,
    390583,
    390734,
    390894,
    390978,
    390897,
    390905,
    390906,
    390907,
    390908,
    390909,
    390910,
    390911,
    390912,
    390913,
    390914,
    390915,
    390916,
    390898,
    390899,
    390900,
    390901,
    390902,
    390903,
    390904,
    390936,
    390937,
    390945,
    390946,
    390947,
    390948,
    390950,
    390951,
    390952,
    390953,
    391037,
    391038,
    391068,
    391069,
    391070,
    391071,
    391072,
    391073,
    391074,
    391139,
    391145,
    391149,
    391153,
    391156,
    391158,
    391162,
    391164,
    391165,
    391176,
    391177,
    391179,
    391147,
    391152,
    391154,
    391155,
    391159,
    391160,
    391169,
    391170,
    391172,
    391173,
    391174,
    391175,
    391178,
    391144,
    391146,
    391148,
    391150,
    391151,
    391157,
    391161,
    391163,
    391166,
    391167,
    391168,
    391171,
    391220,
    391221,
    391222,
    391232,
    391233,
    391234,
    391235,
    391236,
    391237,
    391265,
    391266,
    391271,
    391272,
    391277,
    391298,
    391327,
    391330,
    391331,
    391332,
    391333,
    391335,
    391362,
    391367,
    391308,
    391310,
    391311,
    391328,
    391334,
    391364,
    391365,
    391366,
    391368,
    383082,
    383560,
    383794,
    383970,
    383972,
    383078,
    383079,
    383080,
    383081,
    383195,
    383795,
    383971,
    384173,
);

foreach ($cardIdList as $cardId) {
    echo $cardId;
    $cardRecord = $isicDbCards->getRecord($cardId);
    $isicDbCardDataSync->scheduleCard($cardRecord);
    echo "\n";
}

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
