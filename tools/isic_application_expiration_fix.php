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

$isicDbApplication = IsicDB::factory('Applications');
$isicDbCard = IsicDB::factory('Cards');

echo "<pre>\n";
echo date('H:i:s') . "\n";

$sql = 'SELECT * FROM module_isic_application WHERE moddate >= ? AND state_id = 6';
foreach ($database->fetch_all($sql, '2013-07-31') as $applData) {
    echo $applData['id'];
    $modTime = strtotime($applData['moddate']) - 1;
    $modDate = date('Y-m-d', $modTime);
    $sql = '
        SELECT
            *
        FROM
            module_isic_card
        WHERE
            kind_id = ? AND
            type_id = ? AND
            school_id = ? AND
            person_number = ? AND
            adddate >= ?
    ';
    foreach ($database->fetch_all($sql,
        $applData['kind_id'],
        $applData['type_id'],
        $applData['school_id'],
        $applData['person_number'],
        $modDate) as $cardData) {
        echo ', C: ' . $cardData['id'];
        $isicDbApplication->updateRecord($applData['id'],
            array(
                'expiration_date' => $cardData['expiration_date'],
                'card_id' => $cardData['id']
            )
        );
    }
    echo "\n";
//    break;
}

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
