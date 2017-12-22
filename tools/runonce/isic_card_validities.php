<?php

include("../../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
set_time_limit(0);
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################

$db = new db;
$db->connect();
$sq = new sql;
$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

// ##############################################################

$validities = IsicDB::factory('CardValidities');
$userStatuses = IsicDB::factory('UserStatuses');
$cards = IsicDB::factory('Cards');

// ##############################################################
/*
echo "Generating or updating validities for all cards...\n";
$offset = 0;
do {
    if ($offset > 0) {
        echo "Progress: $offset cards processed...\n";
        flush();
    }
    $cardList = $cards->listRecords($offset, 100);
    foreach ($cardList as $cardData) {
        try {
            $validities->insertOrUpdateRecordByCard($cardData);
        } catch (Exception $e) {
            // ignore, since exceptions are sent to developer anyway
        }
    }
    $offset += count($cardList);
} while (count($cardList) > 0);
echo "Validities for all cards were successfully generated\n";
*/
// ##############################################################

echo "Generating or updating validities for all approporiate user statuses...\n";
$offset = 0;
do {
    if ($offset > 0) {
        echo "Progress: $offset user statuses processed...\n";
        flush();
    }
    $userStatusList = $userStatuses->listRecords($offset, 100);
    foreach ($userStatusList as $userStatusData) {
        try {
            $validities->insertOrUpdateRecordByUserStatus($userStatusData);
        } catch (Exception $e) {
            // ignore, since exceptions are sent to developer anyway
        }
    }
    $offset += count($userStatusList);
} while (count($userStatusList) > 0);
echo "Validities for all appropriate user statuses were successfully generated\n";

