<?php
include_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicDB.php");
require_once(SITE_PATH . "/class/IsicReport/IsicReport_CompoundCards.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$report = new IsicReport_CompoundCards($database, IsicDB::factory('Cards'));
$endTime = mktime(0, 0, 0, date('n'), 1, date('Y')) - 1;
$begTime = mktime(0, 0, 0, date('n', $endTime), 1, date('Y', $endTime));

echo 'Start: ' . date('Y-m-d H:i:s') . "\n";
// seb
echo "Generating report for SEB: ";
$report->generateAndSaveReport(1, $begTime, $endTime);
echo "Done\n";
// swed
echo "Generating report for Swed: ";
$report->generateAndSaveReport(2, $begTime, $endTime);
echo "Done\n";

echo 'End: ' . date('Y-m-d H:i:s') . "\n";