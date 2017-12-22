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
require_once(SITE_PATH . "/class/Isic/IsicUnitedTicketsSync.php");

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

$isicDbCards = IsicDB::factory('Cards');
$isicDbUsers = IsicDB::factory('Users');
$isicDbCardDataSync = IsicDB::factory('CardDataSync');
$unitedTicketsSync = new IsicUnitedTicketsSync();

echo "<pre>\n";
echo date('H:i:s') . "\n";
$records = $isicDbCardDataSync->getScheduledRecords();
foreach ($records as $record) {
    echo $record['record_type'] . ': ' . $record['record_id'];

    if ($record['tries'] >= $isicDbCardDataSync->getSyncMaxTries()) {
        echo ": max tries exceeded, skipping\n";
        continue;
    }

    if ($record['record_type'] == $isicDbCardDataSync->getRecordTypeCard()) {
        $cardRecord = $isicDbCards->getRecord($record['record_id']);
        $cardRecord['transaction_id'] = $record['id'];
    } else if ($record['record_type'] == $isicDbCardDataSync->getRecordTypeUser()) {
        $userRecord = $isicDbUsers->getRecord($record['record_id']);
        $userRecord['transaction_id'] = $record['id'];
    } else {
        echo ": unknown record type, skipping\n";
        continue;
    }

    $sendResult = null;
    switch ($record['sync_type_id']) {
        case $isicDbCardDataSync->getSyncTypeActivate():
            echo ': activation';
            $sendResult = $unitedTicketsSync->sendActivationMessage($cardRecord);
        break;
        case $isicDbCardDataSync->getSyncTypeDeactivate():
            echo ': deactivation';
            $sendResult = $unitedTicketsSync->sendDeactivationMessage($cardRecord);
        break;
        case $isicDbCardDataSync->getSyncTypeRemoveUser():
            echo ': remove user';
            $sendResult = $unitedTicketsSync->sendRemoveUserMessage($userRecord);
        break;
        default:
            echo ': unknown';
        break;
    }

    if ($sendResult !== null) {
        $data = $record;
        $data['success'] = $sendResult ? 1 : 0;
        $data['request'] = $unitedTicketsSync->getLastUrl();
        $data['response'] = $unitedTicketsSync->getRawResponse();
        $data['tries']++;
        $isicDbCardDataSync->updateRecord($record['id'], $data);
        echo ': ' . ($sendResult ? 'OK' : 'ERROR');
        if (!$sendResult) {
            IsicMail::sendCardSyncUTFailedNotification($unitedTicketsSync->getLastUrl(), $unitedTicketsSync->getRawResponse());
        }
    }
    echo "\n";
}

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
