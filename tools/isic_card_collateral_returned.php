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
$card_list = array(
array('S372224801450P', '2008-09-24'),
array('S372224801466K', '2008-10-24'),
array('S372224801034N', '2008-11-17'),
array('S372500200455N', '2008-11-18'),
array('S372224801461M', '2008-11-18'),
array('S372224801456Q', '2008-12-09'),
array('S372224801423G', '2008-12-09'),
array('S372224801041M', '2009-02-05'),
array('S372224801377G', '2009-02-05'),
array('T372151270601N', '2009-02-13'),
array('T372151270599T', '2009-02-13'),
array('T372151270595L', '2009-02-13'),
array('S372224801006N', '2009-02-13'),
array('S372224801372S', '2009-02-13'),
array('S372224801315Q', '2009-02-20'),
array('S372224801170D', '2009-03-23'),
array('S372500200470N', '2009-04-09'),
array('S372224801417J', '2009-04-30'),
array('S372224801167M', '2009-05-19'),
array('S372224801096N', '2009-05-27'),
array('S372224801101E', '2009-05-28'),
array('S372224801226C', '2009-05-28'),
array('S372224801126M', '2009-05-28'),
array('S372224801136H', '2009-05-29'),
array('S372224801147N', '2009-06-10'),
array('S372224801005B', '2009-06-10'),
array('S372224801462N', '2009-07-27'),
array('S372224801425K', '2009-08-10'),
array('S372224801043Q', '2009-08-10'),
array('S372224801440K', '2009-08-14'),
array('S372224801794K', '2009-09-11'),
array('S372224807718D', '2009-09-11'),
array('S372224802387H', '2009-10-05'),
array('S372224801719N', '2009-10-12'),
array('S372224801025K', '2009-11-16'),
array('S372224804940G', '2009-11-16'),
array('S372224801082M', '2009-11-27'),
array('S372224801846B', '2009-11-27'),
);

foreach ($card_list as $card_data) {
    $card_number = $card_data[0];
    $pay_date = $card_data[1];
    $data = $ic->getCardRecord($ic->getCardIdByNumber($card_number));
    if ($data) {
        echo "Card: " . $data['isic_number'] . ", PD: " . $pay_date . ": " . $data['confirm_payment_collateral'] . ", Ret: " . $data['returned'];
        $payment = $ip->getPaymentByCard($data['id'], $ip->payment_type_collateral);
        
        if ($payment) {

            $upd_sql = "UPDATE module_isic_payment SET free = 0, payment_returned = 1, payment_returned_date = ? WHERE module_isic_payment.id = !";
            $res =& $t_db->query($upd_sql, $pay_date, $payment['id']);

            
            //print_r($payment);
            echo ", R: " . $payment['returned'] . ", E: " . $payment['expired'], ", D: " . $payment['deposit_id'] . ", PD: " . $payment['adddate'] . ", Free: " . $payment['free'];
            
        } else {
            echo "Could not find payment ..."; 
        }
        echo "\n<br>";
    }
}

/*
if ($payment) {
    $ip->setPaymentExpired($last_card['id']);
    $ic->setApplicationCollateralPayment($last_card['id'], $data['id']);
    $ic->saveApplicationChangeLog($ic->log_type_mod, $data['id'], $data, $ic->getApplicationRecord($data['id']), $ic->system_user);
}
echo ", calculated: " . $last_card['id'] . ", kind: " . $last_card['kind_id'] . ", coll: " . $last_card['confirm_payment_collateral'] . ", payment free: " . $payment['free'] . ", expired: " . $payment['expired'] . ", payment returned: " . $payment['payment_returned'];
*/
