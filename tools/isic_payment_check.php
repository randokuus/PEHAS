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
);


$pay_fields = array(
    'id',
    'card_id',
    'deposit_date',
    'adddate',
    'person_number',
    'free',
    'returned',
    'expired',
    'payment_returned',
    'deposit_id',
);

$card_fields = array(
    'id',
    'adddate',
    'exported',
    'person_number',
    'prev_card_id',
    'confirm_payment_collateral',
    'active',
    'returned',
    'expiration_date'
);

$col_span = (max(count($pay_fields), count($card_fields)) + 1);

echo "<table border='1'>\n";

echo "<tr>\n";
echo "<th></th>\n";
echo "<th>CRow</th>\n";
foreach ($card_fields as $card_field) {
    echo "<th>" . $card_field . "</th>\n";    
}
echo "</tr>\n";

echo "<tr>\n";
echo "<th>PRow</th>\n";
foreach ($pay_fields as $pay_field) {
    echo "<th>" . $pay_field . "</th>\n";    
}
echo "</tr>\n";

$prev_person = false;
$prev_type = false;
$pay_count = 0;
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

    ORDER BY 
        module_isic_payment.person_number, 
        module_isic_payment.adddate
";

$person_type = array();

$res_pay =& $t_db->query($pay_sql, $ip->payment_type_collateral);
while ($pay_data = $res_pay->fetch_assoc()) {
    if (!isset($person_type[$pay_data['person_number']][$pay_data['type_id']])) {
        $person_type[$pay_data['person_number']][$pay_data['type_id']] = -1;
    }
    
    if ($person_type[$pay_data['person_number']][$pay_data['type_id']] == -1) {
        $card_sql = "SELECT * FROM module_isic_card WHERE kind_id = 1 AND type_id = ! AND person_number = ? ORDER BY adddate";
        $res_card =& $t_db->query($card_sql, $pay_data['type_id'], $pay_data['person_number']);
        $prev_person = $pay_data['person_number'];
        $prev_type = $pay_data['person_number'];
        
        $person_type[$pay_data['person_number']][$pay_data['type_id']] = $res_card->num_rows();
        if ($res_card->num_rows() > 1) {
            echo "<tr>\n";
            echo "<td colspan='" . $col_span . "'><hr></td>\n";
            echo "</tr>\n";
            
            $card_count = 0;
            while ($card_data = $res_card->fetch_assoc()) {
                $card_count++;
                echo "<tr>\n";
                echo "<td></td>\n";
                echo "<td>" . $card_count . "</td>\n";
                foreach ($card_fields as $card_field) {
                    echo "<td>" . $card_data[$card_field] . "</td>\n";    
                }
                echo "</tr>\n";
            }
        }
    }
    
    if ($person_type[$pay_data['person_number']][$pay_data['type_id']] > 1) {
        $pay_count++;
        echo "<tr>\n";
        echo "<td>" . $pay_count . "</td>\n";
        foreach ($pay_fields as $pay_field) {
            echo "<td>" . $pay_data[$pay_field] . "</td>\n";    
        }
        echo "</tr>\n";
    }
    
}

echo "</table>\n";
