<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Isic/IsicBankCardImporter_Swedbank.php");
require_once(SITE_PATH . "/class/Isic/IsicDirectory.php");
require_once(SITE_PATH . "/tools/archive.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

echo "<pre>\n";
echo "Swed files uncompress:\n";
$arc_options = array('basedir' => SWEDBANK_PATH, 'overwrite' => 1);
foreach (IsicDirectory::getAsSortedList(SWEDBANK_PATH) as $filename) {
    if (strpos($filename, '.tar.gz') !== false) {
        echo $filename . "\n";
        $arc = new gzip_file($filename);
        $arc->set_options($arc_options);
        $arc->extract_files();
        unlink(SWEDBANK_PATH . $filename);
    }
}
echo "\nDone\n";

echo "\nSwed files import: \n";
$is = new IsicBankCardImporter_Swedbank();
$is->readFiles(SWEDBANK_PATH);
$is->setCardPic();

//$is->createPicDb(SITE_PATH . "/upload/sonic/");
echo "\nDone\n";
echo "</pre>\n";
exit();