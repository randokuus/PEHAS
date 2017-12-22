<?php

// cron script, should be run daily

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

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

print "Sending notification for unsuccesful records...\n";
$settings = IsicDB::factory('GlobalSettings');
$days = (int)$settings->getRecord("card_transfer_admin_notification_days");
$days = max(0, min($days, 3650));

$transfers = IsicDB::factory('CardTransfers');
$transfersList = $transfers->findUnsuccessfulRecordsOlderThan(
    IsicDate::subtractWorkDaysFromDate($days)
);
if (count($transfersList) > 0) {
    IsicMail::sendCardsTransferDelayedNotification($transfersList);
    print "E-mail about " . count($transfersList) . " transfers has been sent.\n";
} else {
    print "No transfers found to send an e-mail about.";
}
