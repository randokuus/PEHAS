<?php
require_once('../class/config.php');
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . '/class/IsicDB.php');

$db = new DB();
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$triples = array(
    'CFFEBE7C:S372900469062L:9233736580160074027',
);

/** @var IsicDB_Cards $dbCards */
$dbCards = IsicDB::factory('Cards');

// foreach ($triples as $triple) {
//     list($chip, $serial, $pan) = explode(':', $triple);
//     $cardId = $dbCards->getIdByIsicNumber($serial);
//     echo $chip . ' > ' . $serial . ' > ' . $pan . ' > ' . $cardId . "\n";
// 
//     $tmpData = array(
//         'chip_number' => $chip,
//         'pan_number' => $pan
//     );
//     $dbCards->updateRecord($cardId, $tmpData);
// }

$dbCards->updateRecord(638005, array('chip_number' => '334EB779', 'pan_number' => '9233733180130733555'));
$dbCards->updateRecord(637888, array('chip_number' => 'AE6863D5', 'pan_number' => '9233733180130733563'));
$dbCards->updateRecord(649342, array('chip_number' => '7E57E7A2', 'pan_number' => '9233733180130733571'));
$dbCards->updateRecord(637454, array('chip_number' => 'E3618476', 'pan_number' => '9233733180140654247'));
$dbCards->updateRecord(649559, array('chip_number' => '33AC8576', 'pan_number' => '9233733180140654254'));
$dbCards->updateRecord(648676, array('chip_number' => '33428576', 'pan_number' => '9233733180140654270'));
$dbCards->updateRecord(631415, array('chip_number' => '839A8576', 'pan_number' => '9233733180140654296'));
$dbCards->updateRecord(637872, array('chip_number' => '039B8576', 'pan_number' => '9233733180140654304'));
$dbCards->updateRecord(649698, array('chip_number' => 'F3A38973', 'pan_number' => '9233733180106170139'));

