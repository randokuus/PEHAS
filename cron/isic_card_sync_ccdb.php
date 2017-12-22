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
require_once(SITE_PATH . "/class/RestCurlClient.php");
require_once(SITE_PATH . "/class/Isic/IsicCCDBClient.php");

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

echo "<pre>\n";
echo "ISIC CCDB:\n";

$isicDbCards = IsicDB::factory('Cards');
$isicDbCardDataSync = IsicDB::factory('CardDataSyncCCDB');
$restClient = new RestCurlClient();
$client = new IsicCCDBClient($restClient, CCDB_API_URL, CCDB_API_USERNAME, CCDB_API_PASSWORD);
$client->sync($isicDbCardDataSync, $isicDbCards);

//$cardData = array(
//    'isic_number' => 'S372903350001X',
//    'card_type_ccdb' => 'ISIC',
//    'card_status_ccdb' => 'VALID',
//    'person_name' => 'Elmar Kala',
//    'person_name_first' => 'Elmas',
//    'person_name_last' => 'Kala',
//    'person_gender' => '',
//    'exported_date' => '2011-01-01',
//    'expiration_date' => '2011-12-31',
//    'school_name' => 'ÄÖÜÕSchool Name',
//    'issuer_name_ccdb' => IsicCCDBClient::ISSUER_NAME,
//);
//
//$xml = $client->getCardXml($cardData);
//$res = $client->send($xml);
//print_r($res);

echo "\nDone\n";
echo "</pre>\n";
exit();