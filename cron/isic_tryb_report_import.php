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
require_once(SITE_PATH . '/class/Xml2Csv.php');

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

$src_path = SOURCE_PATH . "Report*.xml";
$tar_path = SITE_PATH . "/cache/tryb/";
$reportPath  = "/reports/tryb/";

echo "Data transfer from Trueb: \n";

$scp = new scp(HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);
if (!$scp->download($src_path, $tar_path)) {
    echo $scp->getErrors();
} else {
    $opendir = addslashes($tar_path);
    $dir = @opendir($opendir);
    if (!$dir) {
        exit();
    }
    while (($file = @readdir($dir)) !== false) {
        if (is_dir($opendir . $file) || $file == "." || $file == "..") {
            continue;
        }
        $converter = new Xml2Csv();
        $reportName = str_replace('.xml', '.csv', $file);
        $written = $converter->convertAndSaveAsCsv($opendir . $file, SITE_PATH . '/upload' . $reportPath . $reportName);
        if ($written) {
            // moving all of the already imported files to imported subfolder
            rename($opendir . $file, $opendir . "imported/" . $file);
            // inserting according record into db
            $database->query(
                "INSERT INTO `files` (`type`, `name`, `folder`, `add_date`) VALUES (?, ?, ?, ?)",
                'csv', str_replace('.csv', '', $reportName), $reportPath, $database->now()
            );
        } else {
            // if order_id couldn't be found then moving files to error subfolder
            rename($opendir . $file, $opendir . "error/" . $file);
        }
        // and then deleting the file from source destination
        $scp->delete(SOURCE_PATH . $file);
    }
}
