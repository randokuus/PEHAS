<?php
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Webservice.php");
require_once(SITE_PATH . "/class/Webservice/WSAuthentication.php");
require_once(SITE_PATH . "/class/Webservice/WSPartner.php");
require_once(SITE_PATH . "/class/Webservice/WSRawPostData.php");

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

$partner = new WSPartner();

if (!$partner->isAuthenticationCorrect()) {
    WSAuthentication::authenticate();
}

$webService = new Webservice($partner);

if (!headers_sent()) {
    header("Content-type: application/xml");
}

$query = WSRawPostData::getData();
$result = $webService->getResult($query); 
echo $result;

logAccess(print_r($_SERVER, true));
logAccess('Query: '. print_r($query, true));
logAccess('Result: '. print_r($result, true));
//logAccess(print_r($GLOBALS['POST_DATA'], true));
//logAccess(print_r($GLOBALS['HTTP_RAW_POST_DATA'], true));

function logAccess($data) {
    if ($fp_log = fopen(SITE_PATH . "/ws/log.txt", "a+")) {
        fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
        fwrite($fp_log, $data . "\n");
        fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
        fclose($fp_log);
    }
}
