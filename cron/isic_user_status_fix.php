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

$userStatuses = IsicDB::factory('UserStatuses');
$validity = IsicDB::factory('CardValiditiez');
$dbCards = IsicDB::factory('Cards');
$dbUserStatusTypes = IsicDB::factory('UserStatusTypes');
$dbUsers = IsicDB::factory('Users');
$dbUserGroups = IsicDB::factory('UserGroups');
$common = IsicCommon::getInstance();
$systemUserId = $common->getLogUserId(0);

echo date('H:i:s') . "\n";
$sql = "
    SELECT
        module_isic_card.person_name_first,
        module_isic_card.person_name_last,
        module_isic_card.person_email,
        module_isic_card.person_phone,
        module_isic_card.person_birthday,
        module_isic_card.person_addr1,
        module_isic_card.person_addr2,
        module_isic_card.person_addr3,
        module_isic_card.person_addr4,
        module_isic_card.id,
        module_isic_card.adddate,
        module_isic_card.deactivation_time,
        module_isic_card.school_id,
        module_isic_card.type_id,
        module_isic_card.isic_number,
        module_isic_card.person_number,
        IF(module_isic_card_validities.id, 1, 0) AS validity_exists
    FROM
        module_isic_card
        LEFT JOIN
            module_isic_card_validities
            ON
                module_isic_card.id = module_isic_card_validities.card_id
    WHERE
        module_isic_card.state_id = 4

    ORDER BY
        module_isic_card.person_number
    LIMIT 1000000
";

$cardTypeList = array();
$cardTypeIdList = array();
$userNumberList = array();
$prevUserCode = '';
$userRecord = false;

$r = &$database->query($sql);
$count = array('yes' => 0, 'no' => 0);
while ($row = $r->fetch_assoc()) {
    if ($row['validity_exists']) {
        $count['yes']++;
    } else {
        /*
        if (!in_array($row['type_id'], $cardTypeIdList)) {
            $cardTypeIdList[] = $row['type_id'];
            $statusTypes = $dbUserStatusTypes->getRecordsByCardType($row['type_id']);
            $cardTypeList[$row['type_id']] = $statusTypes[0]['id'];
        }

        if ($prevUserCode != $row['person_number']) {
            $userRecord = $dbUsers->getRecordByCode($row['person_number']);
            $userNumberList[] = $row['person_number'];
        }
        */

        if ($userRecord) {
            /*
            echo $row['person_number'] . ': ' . $userRecord['user'] . ', add: ' . $row['adddate'] . ', deact: ' . $row['deactivation_time'] . "\n";
            flush();
            ob_flush();

            $filters = array(
                'status_id' => $cardTypeList[$row['type_id']],
                'user_id' => $userRecord['user'],
                'school_id' => $row['school_id'],
                //'active' => 1
            );
            $statusList = $userStatuses->findRecords($filters);
            $statusNeeded = true;

            if ($statusList) {
                foreach ($statusList as $statusRecord) {
                    $ok1 = $ok2 = true;
//                    print_r($statusRecord);
                    if (!$statusRecord['active']) {
                        if ($statusRecord['modtime'] < $row['adddate']) {
                            $ok1 = false;
                        }
                    }

                    if ($statusRecord['addtime'] > $row['deactivation_time']) {
                        $ok2 = false;
                    }

                    if ($ok1 && $ok2) {
                        $statusNeeded = false;
                        break;
                    }
                }
            }

            if ($statusNeeded) {
                echo "creating new status\n";
                $groupRecord = $dbUserGroups->getRecordBySchoolStatusAutomaticAndAddIfNotFound($row['school_id'], $cardTypeList[$row['type_id']]);
                $sql = 'INSERT INTO
                    `module_user_status_user` (
                        `group_id`,
                        `status_id`,
                        `user_id`,
                        `active`,
                        `addtime`,
                        `adduser`,
                        `addtype`,
                        `modtime`,
                        `moduser`,
                        `modtype`,
                        `school_id`
                    ) VALUES (
                        !,
                        !,
                        !,
                        0,
                        ?,
                        !,
                        !,
                        ?,
                        !,
                        !,
                        !
                    )';
                $database->query(
                    $sql,
                    $groupRecord['id'],
                    $cardTypeList[$row['type_id']],
                    $userRecord['user'],
                    $row['adddate'],
                    $systemUserId,
                    1,
                    $row['deactivation_time'],
                    $systemUserId,
                    1,
                    $row['school_id']
                );
            }
            */

        } else {
            /*
            echo $row['person_number'] . ': ' . $userRecord['user'] . ', add: ' . $row['adddate'] . ', deact: ' . $row['deactivation_time'] . "\n";

            $userData = array(
                'user_code' => $row['person_number'],
                'name_first' => $row['person_name_first'],
                'name_last' => $row['person_name_last'],
                'email' => $row['person_email'],
                'phone' => $row['person_phone'],
                'birthday' => $row['person_birthday'],
                'addr1' => $row['person_addr1'],
                'addr2' => $row['person_addr2'],
                'addr3' => $row['person_addr3'],
                'addr4' => $row['person_addr4'],
            );

            $dbUsers->insertRecord($userData);

            flush();
            ob_flush();
            */
        }

        $cardRecord = $dbCards->getRecord($row['id']);
        $validity->insertOrUpdateRecordByCard($cardRecord);
        $count['no']++;

        echo $count['no'] . '. ' . $row['isic_number'] . ', ' . $row['person_number'] . ', add: ' . $row['adddate'] . ', deact: ' . $row['deactivation_time'] . "\n";
        flush();
        ob_flush();
    }
}

echo ', cards: ' . print_r($count, true) . "\n";
echo ', types: ' . print_r($cardTypeList, true) . "\n";
//echo ', users: ' . print_r($userList, true) . ', ' . count($userList) . "\n";

echo 'done ...' . date('H:i:s');