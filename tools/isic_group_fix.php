<?php
set_time_limit(0);
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

$userStatuses = IsicDB::factory('UserStatuses');

function getCardValidities($statusId) {
    global $database;

    $list = array();
    $sql = "SELECT * FROM module_isic_card_validities WHERE user_status_id = ! ORDER BY id";
    $r = &$database->query($sql, $statusId);
    while ($row = $r->fetch_assoc()) {
        $list[] = $row;
//        print_r($row);
    }
    return $list;
}


function getUserStatuses($groupId) {
    global $database;

    $list = array();
    $sql = "SELECT * FROM module_user_status_user WHERE group_id = ! ORDER BY id";
    $r = &$database->query($sql, $groupId);
    while ($row = $r->fetch_assoc()) {
//        $row['validities'] = getCardValidities($row['id']);
        $list[] = $row;
//        print_r($row);
//        echo 'Validities:';
    }
    return $list;
}

function getGroups($schoolId, $statusId, $automatic) {
    global $database;

    $list = array();
    $sql = "SELECT * FROM module_user_groups WHERE isic_school = ! AND user_status_id = ! AND automatic = ! ORDER BY id";
    $r = &$database->query($sql, $schoolId, $statusId, $automatic);
    while ($row = $r->fetch_assoc()) {
//        echo implode("|", $row) . "\n";
//        continue;
        $row['statuses'] = getUserStatuses($row['id']);
        $list[] = $row;
//        print_r($row);
//        echo 'Statuses:';
//        getUserStatuses($row['id']);
    }
    return $list;
}

function getUsers($groupId) {
    global $database;

    $list = array();
    $sql = "SELECT * FROM module_user_users WHERE FIND_IN_SET(!, ggroup) > 0";
    $r = &$database->query($sql, $groupId);
    while ($row = $r->fetch_assoc()) {
        $list[] = $row;
    }
    return $list;
}

echo date('H:i:s') . "\n";

$sql = "
	SELECT
		`isic_school`,
		`user_status_id`,
		automatic,
		count(*) AS combo_count
	FROM
		`module_user_groups`
	WHERE
		1
	GROUP BY
		isic_school,
		user_status_id,
		automatic
	ORDER BY
		combo_count DESC";
$r = &$database->query($sql);
while ($row = $r->fetch_assoc()) {
    if ($row['combo_count'] > 1) {
        echo $row['isic_school'] . ', ' . $row['user_status_id'] . ', ' . $row['automatic'] . ', ' . $row['combo_count'] . "\n";
        $groups = getGroups($row['isic_school'], $row['user_status_id'], $row['automatic']);
//        echo "\n";
//        continue;
        $statusList = array();
        $lastGroup = array();
        $deleteList = array();
        $lastTime = '9999';
        foreach ($groups as $group) {
            if (count($group['statuses'])) {
                if ($lastTime > $group['addtime']) {
                    $lastGroup = $group;
                    unset($lastGroup['statuses']);
                    $lastTime = $group['addtime'];
                }
                $statusList = array_merge($statusList, $group['statuses']);
            } else {
                $deleteList[] = $group['id'];
                //$sql = "DELETE FROM module_user_groups WHERE id = !";
                //$database->query($sql, $group['id']);
            }
        }

        $fixedStatusCount = 0;
        foreach ($statusList as $status) {
            if ($status['group_id'] != $lastGroup['id']) {
                $fixedStatusCount++;
                //print_r($status);
                //$userStatuses->updateRecord($status['id'], array('group_id' => $lastGroup['id']));
                //break;
            }
        }
        //echo "Fixed statuses: " . $fixedStatusCount . "\n";
        echo 'Deleted: ';
        print_r($deleteList);
        print_r($lastGroup);
//        print_r($statusList);
    }
}
echo "\ndone ..." . date('H:i:s');