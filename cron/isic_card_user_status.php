<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
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

$ic = IsicCommon::getInstance();
$ust = IsicDB::factory('UserStatusTypes');
$us = IsicDB::factory('UserStatuses');

$cardTypeStatusList = array();
foreach ($ust->listRecords() as $status) {
    $types = explode(',', $status['card_types']);
    foreach ($types as $type) {
        $cardTypeStatusList[$type] = $status['id'];
    }
}

$r = &$database->query('
    SELECT
        `module_isic_card`.*,
        `module_user_users`.`user`
    FROM
        `module_isic_card`,
        `module_user_users`
    WHERE
        `module_isic_card`.`person_number` = `module_user_users`.`user_code` AND
        `module_isic_card`.`active` = 1 AND
        `module_isic_card`.`kind_id` = 1 AND
        `module_user_users`.`user_type` = 2
    GROUP BY
        `module_isic_card`.`id`
    '
);

while ($data = $r->fetch_assoc()) {
    $statusId = $cardTypeStatusList[$data['type_id']];
    if (!$statusId) {
        echo "<b>No status found for type: " . $data['type_id'] . "<br/>\n";
    } else {
        $userStatus = $us->getRecordByStatusUserSchool($statusId, $data['user'], $data['school_id']);
        if (!$userStatus) {
            echo $data['isic_number'];
            //$us->setUserStatusesBySchoolCardType($data['user'], $data['school_id'], $data['type_id'], 1);
            echo "\n<br>";
        }
    }
}