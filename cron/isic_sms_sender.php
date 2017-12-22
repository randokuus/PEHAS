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
require_once(SITE_PATH . "/class/SMS/SMSClient.php");
require_once(SITE_PATH . "/class/SMS/SMSSendQueue.php");
require_once(SITE_PATH . "/class/SMS/SMSSendLogger.php");

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
echo "SMS:\n";

$sendQueue = new SMSSendQueue($database);
$sendLogger = new SMSSendLogger($database);
$restClient = new RestCurlClient();
$schoolSMSCredit = IsicDB::factory('SchoolSMSCredit');

//$data = array(
//    'from' => 'Minukool',
//    'to' => '3725117076',
//    'text' => 'See on teine test Nexmo sms api-ga.'
//);
//echo $sendQueue->addToQueue($data);

$client = new SMSClient($restClient, SMS_API_URL, SMS_API_KEY, SMS_API_SECRET, $sendLogger, $schoolSMSCredit);
$client->sendAll($sendQueue);

echo "\nDone\n";
echo "</pre>\n";
exit();
