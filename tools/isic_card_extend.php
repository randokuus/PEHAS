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
$GLOBALS["language"] = &$language;

$ic = IsicDB::factory('Cards');
$card_list = array(
    'S372224812117P',
    'S372224808825T',
    'S372224808922N',
    'S372224808970M',
);

$expiration = "2011-12-31";
foreach ($card_list as $card_number) {
    echo $card_number . ": ";
    $cardId = $ic->getIdByIsicNumber($card_number);
    if ($cardId) {
        $ic->updateRecord($cardId, array('expiration_date' => $expiration));
        echo $ic->activate($cardId) ? 'act' : 'not';
    } else {
        echo '--CARD_NOT_FOUND--';
    }
    echo "<br>\n";
}
