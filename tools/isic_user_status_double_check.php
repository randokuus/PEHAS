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

$userDb = IsicDB::factory('Users');

function getUsersFromStatuses($groupId) {
    global $database;

    $list = array();
    $sql = "SELECT * FROM module_user_status_user WHERE group_id = ! AND active = 1";
    $r = &$database->query($sql, $groupId);
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['user_id'];
    }
    return $list;
}

echo date('H:i:s');
$sql = "SELECT * FROM `module_user_groups` WHERE id > 1 ORDER BY isic_school, user_status_id ";
$r = &$database->query($sql);
$groupList = array();
while ($row = $r->fetch_assoc()) {
    $groupList[$row['isic_school']][$row['user_status_id']][$row['automatic']] = array('id' => $row['id'], 'name' => $row['name']);
}

echo "Non-auto group,Auto group,User code,First name,Last name\n";

foreach ($groupList as $schoolId => $statusList) {
    foreach ($statusList as $statusId => $automaticList) {
        if (sizeof($automaticList) > 1) {
            $usersAuto = getUsersFromStatuses($automaticList[1]['id']);
            $usersNonAuto = getUsersFromStatuses($automaticList[0]['id']);
            $sameUsers = array_intersect($usersNonAuto, $usersAuto);
            if (sizeof($sameUsers) > 0) {
//                echo 'Sc: ' . $schoolId . ', St: ' . $statusId . ', non-auto: ' . $automaticList[0]['name'] . ' (' . sizeof($usersNonAuto) . '), auto: ' . $automaticList[1]['name'] . ' (' . sizeof($usersAuto) . ")\n";
//                print_r($sameUsers);
                foreach ($sameUsers as $userId) {
                    $userRecord = $userDb->getRecord($userId);
                    echo $automaticList[0]['name'] . ',' . $automaticList[1]['name'] . ',' . $userRecord['user_code'] . ',' . $userRecord['name_first'] . ',' . $userRecord['name_last'] . "\n";
                }
            }
        }
    }
}

echo 'done ...' . date('H:i:s');