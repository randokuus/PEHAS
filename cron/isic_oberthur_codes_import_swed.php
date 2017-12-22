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
require_once(SITE_PATH . "/class/Isic/IsicDirectory.php");
require_once(SITE_PATH . "/class/Isic/IsicMail.php");

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

$src_path_list = array(OBERTHUR_SOURCE_PATH . 'SWE*.*');
$tar_path = SITE_PATH . "/cache/oberthur/";
$opendir = addslashes($tar_path);
$dir = @opendir($opendir);
if (!$dir) {
    echo 'Could not open target directory: ' . $tar_path . "\n";
    exit();
}

echo "Data transfer from Oberthur: \n";

$scp = new scp(OBERTHUR_HOST_FILE, OBERTHUR_ID_FILE, OBERTHUR_HOSTNAME,
    OBERTHUR_USERNAME, OBERTHUR_PASSWORD
);

foreach ($src_path_list as $src_path) {
    if (!$scp->download($src_path, $tar_path)) {
        echo $scp->getErrors();
        continue;
    }

    $fileList = IsicDirectory::getAsSortedList($tar_path);
    foreach ($fileList as $file) {
        try {
            echo $file . PHP_EOL;
            $import_log = $isicChipFileImporter->importFile($opendir . $file);
            echo implode(PHP_EOL, $import_log) . PHP_EOL;
            // moving all of the already imported files to imported subfolder
            if ($isicChipFileImporter->hasErrors()) {
                rename($opendir . $file, $opendir . "error/" . $file);
                // send error notification
                IsicMail::sendOberthurImportErrorNotification(
                    $isicChipFileImporter->getErrors(),
                    $file
                );
            } else {
                rename($opendir . $file, $opendir . "imported/" . $file);
            }

            // and then deleting the file from source destination
            $scp->delete(OBERTHUR_SOURCE_PATH . $file, 'sftp');
        } catch (IsicFileOpenException $e) {
            echo 'Exception: ' . $e->getMessage() . PHP_EOL;
        }
    }
}
echo PHP_EOL . 'Done' . PHP_EOL;
exit();
