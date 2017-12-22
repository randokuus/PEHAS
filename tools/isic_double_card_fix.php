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

$cardPairs = array(
array(264329, 334083),
array(264893, 353406),
array(263455, 329914),
array(287566, 336487),
array(270018, 312564),
array(288255, 337945),
array(306952, 319738),
array(319890, 329927),
array(267766, 332592),
array(291258, 332082),
array(316352, 336017),
array(321940, 327684),
array(316604, 336647),
array(287914, 337957),
array(262507, 332687),
array(314911, 332093),
array(293551, 353971),
array(293678, 317865),
array(306769, 329957),
array(306945, 329062),
array(312566, 330802),
array(318434, 334257),
array(289457, 329582),
array(321692, 328070),
array(269715, 335296),
array(262621, 352604),
array(270518, 335300),
array(313623, 319430),
array(267815, 333262),
array(177498, 270606),
array(271376, 354250),
array(328256, 354288),
array(266370, 338675),
array(264817, 312238),
array(314562, 353775),
array(263189, 328701),
array(289423, 330742),
array(263856, 338624),
array(262471, 337382),
array(291032, 336643),
array(270034, 334079),
array(265686, 354000),
array(264842, 288500),
array(292119, 337381),
array(315450, 329415),
array(287560, 319591),
array(266404, 354902),
array(313628, 354327),
array(291010, 330731),
array(262004, 319094),
array(270383, 334213)
);

$dbCards = IsicDB::factory('Cards');
echo "<pre>\n";
echo date('H:i:s') . "\n";

foreach ($cardPairs as $pair) {
    $newCard = $dbCards->getRecord($pair[1]);
    echo $newCard['prev_card_id'] . " : " . $pair[0] . '; ' . ($newCard['prev_card_id'] == $pair[0]) . "\n";
    $dbCards->deActivateOtherCards($newCard);
}

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
