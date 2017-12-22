<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/" . DB_TYPE . ".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
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
$GLOBALS["language"] = & $language;

$isicDbUserStatus = IsicDB::factory('UserStatuses');
$isicDbCard = IsicDB::factory('Cards');
$isicDbCardValidity = IsicDB::factory('CardValidities');

echo "<pre>\n";
echo date('H:i:s') . "\n";

$cards = array(
    '07090646',
    '07090672',
    '07090792',
    '07090838',
    '07090872',
    '07090909',
    '07090916',
    '05093892',
    '05092799',
    '05093026',
    '05093321',
    '05093403',
    '05093488',
    '05094083',
    '05094104',
    'S372224806248M',
    'S372224806305J',
    'S372224806360R',
    'S372226258122K',
    'S372226258280E',
    'T372151271101T',
    'T372151271109N',
    'T372151270946K',
    'T372151271004P',
    'T372151271242J',
    'T372151271444N',
    'T372151271449L',
    'T372151271619H',
    'T372151271636L',
    'T372150931709J',
);

$rowCount = 0;
foreach ($cards as $isic_number) {
    echo $isic_number . "\n";
    $sql = '
        select 
            s.* 
        from 
            module_isic_card as c,
            module_user_users as u,
            module_user_status_user as s
        where
            c.isic_number = ? and
            c.active = 1 and
            u.user_code = c.person_number and
            s.user_id = u.user and
            s.addtype = 2 and
            s.active = 1 and
            s.expiration_date <> ?
    ';
    $res = $database->query($sql, $isic_number, '0000-00-00');
    if ($data = $res->fetch_assoc()) {
        echo ' ==> ' . $data['id'] . ': ' . $isic_number . ', '. $data['addtype'] . ', ' . $data['active'] . ', '. $data['expiration_date'] . "\n";
        // $isicDbUserStatus->deActivate($data['id']);
        
    } else {
        echo $isic_number . "\n";        
        // echo ' ==> NONE' . "\n";
    }
    $rowCount++;
}

echo "\n";
echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
