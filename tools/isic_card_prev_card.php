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
require_once(SITE_PATH . "/class/IsicPayment.php");

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
$ip = new IsicPayment();
$appl_list = array(
);

$r = &$database->query('
    SELECT 
        `module_isic_application`.* 
    FROM 
        `module_isic_application` 
    WHERE 
        `card_id` <> 0 AND
        `prev_card_id` <> 0'
);

while ($data = $r->fetch_assoc()) {
    $data2 = $ic->getCardRecord($data['card_id']);
    
    if ($data2 && !$data2['prev_card_id']) {
        echo $data['card_id'] . ", prev_card: " . $data['prev_card_id'];
        echo ", Prev Card: " . $data2['prev_card_id'];
        $database->query('
            UPDATE 
                `module_isic_card` 
            SET 
                `module_isic_card`.`prev_card_id` = ! 
            WHERE 
                `id` = !',
            $data['prev_card_id'],
            $data['card_id']
        );
        $ic->saveCardChangeLog($ic->log_type_mod, $data['card_id'], $data2, $ic->getCardRecord($data['card_id']), $ic->system_user);
        echo "\n<br>";
    }
}