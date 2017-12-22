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
$isicDbCards = IsicDB::factory('TempCards');

$src_path_list = array(SOURCE_PATH . 'ISIC*.*');
$tar_path = SITE_PATH . "/cache/tag/";

echo "Data transfer from TAG: \n";

// $scp = new scp(HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);
// foreach ($src_path_list as $src_path) {
//     if (!$scp->download($src_path, $tar_path)) {
//         echo $scp->getErrors();
//         continue;
//     }

    $opendir = addslashes($tar_path);
    $dir = @opendir($opendir);
    if (!$dir) {
        continue;
    }

    while (($file = @readdir($dir)) !== false) {
        if (is_dir($opendir . $file) || $file == "." || $file == "..") {
            continue;
        }
        if ($fp = fopen($opendir . $file, "rb")) {
            echo $file . "<br>\n";
            $line_count = 0;
            $import_log = "";
            while (!feof($fp)) {
                $t_line = fgetcsv($fp, 1000, ":");
                if (count($t_line) != 3) {
                    continue;
                }
                $chipNumber = $t_line[0];
                if ($chipNumber) {
                    $line_count++;
                    $isicNumber = str_replace(" ", "", $t_line[1]);
                    $panNumber = str_replace(" ", "", $t_line[2]);
                    $result = $isicDbCards->saveChipAndPanNumber($isicNumber, $chipNumber, $panNumber);

                    $import_log .= $line_count . ". " . $isicNumber . ': (Chip: ' . $chipNumber .
                         "), (Pan: " . $panNumber . "): " . $result . "<br>\n";
                }
            }
            echo $import_log . "<br>\n";
            echo "done<br>\n";
            fclose($fp);
            // moving all of the already imported files to imported subfolder
            // rename($opendir . $file, $opendir . "imported/" . $file);
            // and then deleting the file from source destination
            // $scp->delete(SOURCE_PATH . $file);
        }
    }
// }
exit();