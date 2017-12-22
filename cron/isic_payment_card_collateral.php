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
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicCommon::getInstance();
$ip = new IsicPayment();

$pay_sql = "
    SELECT 
        module_isic_payment.*
    FROM 
        module_isic_payment
    WHERE 
        module_isic_payment.payment_type = ! AND
        module_isic_payment.card_id <> 0
    ORDER BY 
        module_isic_payment.person_number, 
        module_isic_payment.adddate
";

$count = 0;

$res_pay =& $t_db->query($pay_sql, $ip->payment_type_collateral);
while ($pay_data = $res_pay->fetch_assoc()) {
    $card_data = $ic->getCardRecord($pay_data['card_id']);
    if ($card_data && !$card_data['confirm_payment_collateral']) {
        $count++;
        echo "Row: " . $count . ". " . $pay_data['card_id'];
        $sql_card_col = "UPDATE module_isic_card SET confirm_payment_collateral = ! WHERE id = !";
        $t_db->query($sql_card_col, 1, $card_data['id']);
        $ic->saveCardChangeLog($ic->log_type_mod, $card_data['id'], $card_data, $ic->getCardRecord($card_data['id']), $ic->system_user);
        echo "<br>\n";
    }
}
