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

$ic = IsicCommon::getInstance();
$ip = new IsicPayment();

$persons = array(
'35809100218',
'36907126525',
'36907190265',
'36912292723',
'37102120311',
'37310202711',
'37508040263',
'37612142730',
'37801052717',
'37804090210',
'37907302722',
'37908220218',
'38008160215',
'38201316015',
'38202230253',
'38302110334',
'38309210277',
'38407014921',
'38506260237',
'38606150259',
'38608094715',
'38612020326',
'38710020243',
'38710060271',
'38712132750',
'38807045711',
'38808064711',
'38810290012',
'38812035215',
'38812092745',
'38812142248',
'38903186533',
'48301010357',
'48607100264',
'48701030328',
'48805115219',
'48812070270',
);

$count = 0;
/*
$res =& $t_db->query("
    SELECT 
        module_isic_application.*
    FROM 
        module_isic_application, 
        module_isic_card 
    WHERE 
        module_isic_application.prev_card_id = module_isic_card.id AND
        module_isic_card.kind_id = 2 AND
        module_isic_application.state_id < 6");
*/
echo "<table border=1>";
echo "<tr>";
echo "<th>Row</th>";
echo "<th>Id</th>";
echo "<th>Prev</th>";
echo "<th>Person</th>";
echo "<th>First</th>";
echo "<th>Last</th>";
echo "<th>Isic</th>";
echo "<th>Type</th>";
echo "<th>Add</th>";
echo "<th>Appl</th>";
echo "<th>Free</th>";
echo "</tr>";

foreach ($persons as $person_number) {

$res =& $t_db->query("
    SELECT 
        module_isic_card.*,
        IF (module_isic_application.id, module_isic_application.id, 0) AS application_id
    FROM 
        module_isic_card 
    LEFT JOIN
        module_isic_application
        ON
            module_isic_card.id = module_isic_application.card_id
    WHERE 
        module_isic_card.person_number = ? AND
        module_isic_card.kind_id = 1
    ", $person_number);


while ($data = $res->fetch_assoc()) {
    $count++;
    
    $payment = $ip->getPaymentByCard($data['id'], $ip->payment_type_collateral);
    
    if ($payment && $payment['free'] || !$payment) {
        echo
            "<tr>".
            "<td>".
            $count . ". " .
            "</td>". 
            "<td>".
            $data["id"] .
            "</td>". 
            "<td>".
            $data["prev_card_id"] . 
            "</td>". 
            "<td>".
            $data["person_number"] . 
            "</td>". 
            "<td>".
            $data["person_name_first"] . 
            "</td>". 
            "<td>".
            $data["person_name_last"] .
            "</td>". 
            "<td>".
            $data["isic_number"] . 
            "</td>". 
            "<td>".
             $data["type_id"] .
            "</td>". 
            "<td>".
            $data["adddate"] .
            "</td>". 
            "<td>".
            $data['application_id'] .  
            "</td>". 
            "<td>".
            ($payment ? $payment['free'] : "-") .
            "</td>". 
            "</tr>".
            "\n"; 
    }
    
    /*
    $cost_data = $ip->getCardCostCollData($data["person_number"], $data["school_id"], $data["type_id"], $data["adddate"]);
    $payment = $ip->getPaymentByCard($data['prev_card_id'], $ip->payment_type_collateral);
//    print_r($cost_data);
    echo $count . ". " . 
        $data["person_number"] . ": " . 
        $data["person_name_first"] . " " . 
        $data["person_name_last"] . ": "  . 
        $data['application_type_id'] . " <-> " . 
        $cost_data["type"] . ", last card: " . 
        $data['prev_card_id'] . " <-> " . 
        $cost_data['last_card_id'] . ", coll: " . 
        $data['confirm_payment_collateral'] . ", cost: " . 
        $data['confirm_payment_cost'] . ", payment: " .
        ($payment ? $payment['free'] : "-") .
        " <br />\n";
        
    $t_db->query("UPDATE module_isic_application SET application_type_id = ! WHERE id = !", $cost_data["type"], $data["id"]);
    $t_db->query("UPDATE module_isic_application SET prev_card_id = ! WHERE id = !", $cost_data["last_card_id"], $data["id"]);
    $ic->saveApplicationChangeLog($ic->log_type_mod, $data['id'], $data, $ic->getApplicationRecord($data['id']), $ic->system_user);
    */    
}
}

echo "</table>";