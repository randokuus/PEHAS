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
require_once(SITE_PATH . "/tools/archive.php");
require_once(SITE_PATH . "/class/scp.class.php");
require_once(SITE_PATH . "/class/IsicDB.php");
require_once(SITE_PATH . "/class/Isic/IsicChipNumberFileImporter.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

/** @var IsicDB_Cards $isicDbCards */
$isicDbCards = IsicDB::factory('Cards');

/** @var IsicChipNumberFileImporter $isicChipFileImporter */
$isicChipFileImporter = new IsicChipNumberFileImporter($isicDbCards);
$file = SITE_PATH . '/cache/oberthur/imported/SWEdbank_EE_ISIC_UID_140925_1517.txt';
try {
    echo $file . "<br>\n";
    $import_log = $isicChipFileImporter->importFile($file);
    echo $import_log . "<br>\n";
} catch (IsicFileOpenException $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}
echo "\nDone\n";
exit();
