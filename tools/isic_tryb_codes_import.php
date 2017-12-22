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
require(SITE_PATH . "/tools/archive.php");
require(SITE_PATH . "/class/scp.class.php");

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

$src_path = SOURCE_PATH . "log_*.txt";
$tar_path = SITE_PATH . "/cache/tryb/";

function saveChipNumber ($chip_number, $isic_number) {
    global $db;
    $sq = new sql;

    $sql = "SELECT `id`, `chip_number` FROM `module_isic_card` WHERE `isic_number` = '" . mysql_escape_string($isic_number) . "' LIMIT 1";
    $sq->query($db, $sql);
    if ($data = $sq->nextrow()) {
        if ($data["chip_number"] != $chip_number) {
            $sql = "UPDATE `module_isic_card` SET `chip_number` = '" . mysql_escape_string($chip_number) . "' WHERE `id` = " . $data["id"];
            $sq->query($db, $sql);
            return "Chip number assigned.";
        } else {
            return "Chip number already assigned.";
        }
    }

    return "<b>Chip number not assigned.</b> Couldn't find the card.";
}


echo "Data transfer from Trueb: \n";

$scp = new scp('', HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME, SOURCE_HOSTNAME, SOURCE_USERNAME);
if (!$scp->download($src_path, $tar_path)) {
    echo $scp->getErrors();
} else {
    $opendir = addslashes($tar_path);
    if ($dir = @opendir($opendir)) {
        while (($file = @readdir($dir)) !== false) {
            if (!is_dir($opendir . $file) && $file != "." && $file != "..") {
                if ($fp = fopen($opendir . $file, "rb")) {
                    echo $file . "<br>\n";
                    $line_count = 0;
                    $import_log = "";
                    while (!feof($fp)) {
                        $t_line = fgetcsv($fp, 1000, ":");
                        if ($t_line[0]) {
                            $line_count++;
                            $t_line[1] = str_replace(" ", "", $t_line[1]);
                            $res = saveChipNumber($t_line[0], $t_line[1]);
                            $import_log .= $line_count . ", " . $t_line[0] . " -> " . $t_line[1] . ", " . $res . "<br>\n";
                        }
                    }
                    echo $import_log . "<br>\n";
                    echo "done<br>\n";
                    fclose($fp);
                    // moving all of the already imported files to imported subfolder
                    rename($opendir . $file, $opendir . "imported/" . $file);
                    // and then deleting the file from source destination
                    $scp->delete(SOURCE_PATH . $file);
                }
            }
        }
    }
}
exit();