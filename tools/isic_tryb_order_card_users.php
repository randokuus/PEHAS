<?php
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
$orderList = array(
    'ISIC2013013010.txt',
    'ISIC2013020101.txt',
    'ISIC2013020102.txt',
    'ISIC2013020103.txt',
    'ISIC2013020104.txt',
    'ISIC2013020105.txt',
    'ISIC2013020106.txt',
    'ISIC2013020107.txt',
    'ISIC2013020108.txt',
    'ISIC2013020401.txt',
    'ISIC2013020402.txt',
    'ISIC2013020403.txt',
    'ISIC2013020404.txt',
    'ISIC2013020405.txt',
    'ISIC2013020406.txt',
    'ISIC2013020407.txt',
    'ISIC2013020408.txt',
    'ISIC2013020501.txt',
    'ISIC2013020502.txt',
    'ISIC2013020503.txt',
    'ISIC2013020504.txt',
    'ISIC2013020505.txt',
    'ISIC2013020506.txt',
    'ISIC2013020507.txt',
    'ISIC2013020508.txt',
    'ISIC2013020601.txt',
    'ISIC2013020602.txt',
    'ISIC2013020603.txt',
    'ISIC2013020604.txt',
    'ISIC2013020605.txt',
    'ISIC2013020701.txt',
    'ISIC2013020702.txt',
    'ISIC2013020703.txt',
    'ISIC2013020704.txt',
    'ISIC2013020705.txt',
    'ISIC2013020706.txt',
    'ISIC2013020801.txt',
    'ISIC2013020802.txt',
    'ISIC2013020803.txt',
    'ISIC2013020804.txt',
    'ISIC2013020805.txt',
    'ISIC2013020806.txt',
    'ISIC2013021101.txt',
    'ISIC2013021102.txt',
    'ISIC2013021103.txt',
    'ISIC2013021104.txt',
    'ISIC2013021105.txt',
    'ISIC2013021106.txt',
    'ISIC2013021107.txt',
    'ISIC2013021108.txt',
    'ISIC2013021201.txt',
    'ISIC2013021202.txt',
    'ISIC2013021203.txt',
    'ISIC2013021204.txt',
    'ISIC2013021205.txt',
    'ISIC2013021206.txt',
    'ISIC2013021207.txt',
    'ISIC2013021208.txt',
    'ISIC2013021301.txt',
    'ISIC2013021302.txt',
    'ISIC2013021303.txt',
    'ISIC2013021304.txt',
    'ISIC2013021305.txt',
    'ISIC2013021306.txt',
    'ISIC2013021401.txt',
    'ISIC2013021402.txt',
    'ISIC2013021403.txt',
    'ISIC2013021404.txt',
    'ISIC2013021405.txt',
    'ISIC2013021406.txt',
    'ISIC2013021501.txt',
    'ISIC2013021502.txt',
    'ISIC2013021503.txt',
    'ISIC2013021504.txt',
    'ISIC2013021505.txt',
    'ISIC2013021506.txt',
    'ISIC2013021801.txt',
    'ISIC2013021802.txt',
    'ISIC2013021803.txt',
    'ISIC2013021804.txt',
    'ISIC2013021805.txt',
    'ISIC2013021806.txt',
    'ISIC2013021807.txt',
    'ISIC2013021901.txt',
    'ISIC2013021902.txt',
    'ISIC2013021903.txt',
    'ISIC2013021904.txt',
    'ISIC2013021905.txt',
    'ISIC2013021906.txt',
    'ISIC2013022002.txt',
    'ISIC2013022004.txt',
    'ISIC2013022006.txt',
    'ISIC2013022008.txt',
    'ISIC2013022009.txt',
    'ISIC2013022010.txt',
    'ISIC2013022011.txt',
    'ISIC2013022201.txt',
    'ISIC2013022202.txt',
    'ISIC2013022203.txt',
    'ISIC2013022204.txt',
    'ISIC2013022205.txt',
    'ISIC2013022206.txt',
    'ISIC2013022207.txt',
    'ISIC2013022209.txt',
    'ISIC2013022501.txt',
    'ISIC2013022502.txt',
    'ISIC2013022503.txt',
    'ISIC2013022506.txt',
    'ISIC2013022507.txt',
    'ISIC2013022509.txt',
    'ISIC2013022511.txt',
    'ISIC2013022604.txt',
    'ISIC2013022605.txt',
    'ISIC2013022606.txt',
    'ISIC2013022608.txt',
    'ISIC2013022609.txt',
    'ISIC2013022701.txt',
    'ISIC2013022702.txt',
    'ISIC2013022704.txt',
    'ISIC2013022706.txt',
    'ISIC2013022707.txt',
    'ISIC2013022801.txt',
    'ISIC2013022802.txt',
    'ISIC2013022803.txt',
    'ISIC2013022806.txt',
    'ISIC2013022807.txt',
    'ISIC2013030101.txt',
    'ISIC2013030102.txt',
    'ISIC2013030104.txt',
    'ISIC2013030105.txt',
    'ISIC2013030106.txt',
    'ISIC2013030201.txt',
);

echo 'Order,First name,Last name,Person number,Card type,Chip,E-mail' . "\n";

foreach ($orderList as $orderName) {
    $res = $database->query('SELECT t.* FROM module_isic_card_transfer AS t WHERE t.order_name = ?', $orderName);
    while ($data = $res->fetch_assoc()) {
        $res2 = $database->query('
            SELECT
                c.*,
                ct.name AS card_type_name,
                ct.chip AS card_type_chip
            FROM
                module_isic_card AS c,
                module_isic_card_type AS ct
            WHERE
                c.type_id = ct.id AND
                c.order_id = !
            ',
            $data['id']);
        while($cardData = $res2->fetch_assoc()) {
            echo
                $orderName . ',' .
                $cardData['person_name_first'] . ',' .
                $cardData['person_name_last'] . ',' .
                $cardData['person_number'] . ',' .
                $cardData['card_type_name'] . ',' .
                $cardData['card_type_chip'] . ',' .
                $cardData['person_email'] . "\n"
            ;
        }
    }
}


echo "Done\n";