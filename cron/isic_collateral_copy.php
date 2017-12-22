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

$cards = array(
array('S372224801142N','S372224802023M'),
);

$count = 0;
echo "<table border=1>";
echo "<tr>";
echo "<th>src</th>";
echo "<th>tar prev</th>";
echo "<th>tar</th>";
echo "<th>Appl</th>";
echo "</tr>";

foreach ($cards as $card_pair) {
    $card_src = $ic->getCardRecord($ic->getCardIdByNumber($card_pair[0]));
    $card_tar = $ic->getCardRecord($ic->getCardIdByNumber($card_pair[1]));
    
    echo "<tr>";
    echo "<td>" . $card_src['id'] . "</td>";
    echo "<td>" . $card_tar['prev_card_id'] . "</td>";
    echo "<td>" . $card_tar['id'] . "</td>";

    $res =& $t_db->query("
    SELECT 
        module_isic_application.*
    FROM 
        module_isic_application
    WHERE 
        module_isic_application.card_id = !
    ", $card_tar['id']);
    
    if ($data = $res->fetch_assoc()) {
        echo "<td>" . $data['id'] . "</td>";
        
        $ip->createPaymentFromPayment($card_src['id'], $data);
        $payment = $ip->getPaymentByApplication($data['id'], $ip->payment_type_collateral);
        $payment['card_id'] = $card_tar['id'];
        $ip->updatePayment($payment);
        echo "<td>" . ($payment ? $payment['id'] : "-") . "</td>";
        
    }

    echo
    "</tr>".
    "\n"; 
    
/*    
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
}
*/
}

echo "</table>";