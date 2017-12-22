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
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicPayment.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$reassignList = array(
    array('src_type_id' => 21, 'tar_type_id' => 3, 'amount' => 1000),
    // array('src_type_id' => 2, 'tar_type_id' => 18, 'amount' => 500),
    
    // array('src_type_id' => 28, 'tar_type_id' => 29, 'amount' => 100),
    // array('src_type_id' => 1, 'tar_type_id' => 29, 'amount' => 900),
    // array('src_type_id' => 2, 'tar_type_id' => 18, 'amount' => 320),
    // array('src_type_id' => 3, 'tar_type_id' => 26, 'amount' => 11),
    // array('src_type_id' => 24, 'tar_type_id' => 26, 'amount' => 100),
    // array('src_type_id' => 19, 'tar_type_id' => 25, 'amount' => 800),
    // array('src_type_id' => 19, 'tar_type_id' => 27, 'amount' => 700),
);

$sql = 'UPDATE module_isic_card_number SET card_type = ! WHERE id = !';

foreach ($reassignList as $reassignData) {
    print_r($reassignData);
    echo "\n";
    $res =& $t_db->query("
        SELECT
            module_isic_card_number.*
        FROM
            module_isic_card_number
        WHERE
            module_isic_card_number.card_type = ! AND
            module_isic_card_number.reserved = 0
        LIMIT !
        ", $reassignData['src_type_id'], $reassignData['amount']);

    while ($data = $res->fetch_assoc()) {
        echo $data['id'] . "\n";
        $t_db->query($sql, $reassignData['tar_type_id'], $data['id']);
    }
    echo "===\n";
}