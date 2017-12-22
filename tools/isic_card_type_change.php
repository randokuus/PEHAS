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
$card = IsicDB::factory('Cards');

$oldType = 1;
$newType = 19;

$r = &$database->query('
    SELECT
        `module_isic_card`.*
    FROM
        `module_isic_card`
    WHERE
        `module_isic_card`.`expiration_date` = ? AND
        `module_isic_card`.`kind_id` = 2 AND
        `module_isic_card`.`type_id` = !
    ',
    '2011-12-31',
    $oldType

);

$rowCount = 1;

while ($data = $r->fetch_assoc()) {
    echo ($rowCount++) . '. ' . $data['isic_number'];
    $card->updateRecord($data['id'], array('type_id' => $newType));
    echo "\n<br>";
}
