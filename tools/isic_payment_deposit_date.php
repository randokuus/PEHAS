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
        module_isic_payment.*, 
        IF(module_isic_payment.deposit_id, module_isic_payment_deposit.event_time, '') AS deposit_date
    FROM 
        module_isic_payment
    LEFT JOIN
        module_isic_payment_deposit 
        ON 
            module_isic_payment.deposit_id = module_isic_payment_deposit.id 
    WHERE 
        module_isic_payment.payment_type = ! 
        AND module_isic_payment.deposit_id <> 0
    ORDER BY 
        module_isic_payment.person_number, 
        module_isic_payment.adddate
";

$person_type = array();

$res_pay =& $t_db->query($pay_sql, $ip->payment_type_collateral);
while ($pay_data = $res_pay->fetch_assoc()) {
    if (substr($pay_data['deposit_date'], 0, 10) != substr($pay_data['adddate'], 0, 10) && substr($pay_data['adddate'], -8) == "00:00:00") {
        echo $pay_data['id'] . ", DD: " . $pay_data['deposit_date'] . ", AD: " . $pay_data['adddate'] .  "<br>\n";
        //$t_db->query("UPDATE module_isic_payment SET adddate = ? WHERE id = !", $pay_data['deposit_date'], $pay_data['id']);
        
    }
}