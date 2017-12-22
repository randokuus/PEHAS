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
require_once(SITE_PATH . "/class/IsicCommon.php");
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

/** @var IsicDB_Cards $cards */
$cards = IsicDB::factory('Cards');
/** @var IsicDB_CardTransfers $cardTransfers */
$cardTransfers = IsicDB::factory('CardTransfers');

$src_path_list = array(
    SOURCE_PATH . "log_ISIC*.txt", 
    SOURCE_PATH . "log_BRANCH_ISIC*.txt", 
    SOURCE_PATH . "log_POST_ISIC*.txt"
);
$tar_path = SITE_PATH . "/cache/tryb/";

echo "Data transfer from Trueb: \n";

$scp = new scp(HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);
foreach ($src_path_list as $src_path) {
    if (!$scp->download($src_path, $tar_path)) {
        echo $scp->getErrors();
        continue;
    }
    $opendir = addslashes($tar_path);
    $dir = @opendir($opendir);
    if (!$dir) {
        continue;
    }

    while (($file = @readdir($dir)) !== false) {
        if (is_dir($opendir . $file) || $file == "." || $file == "..") {
            continue;
        }
        $fp = fopen($opendir . $file, "rb");
        if (!$fp) {
            continue;
        }
        $error = false;
        $order_id = 0;

        echo $file . "<br>\n";
        $line_count = 0;
        $import_log = "";
        $order_name = '';
        while (!feof($fp)) {
            $t_line = fgetcsv($fp, 1000, ":");
            // if first line, then assuming it's a order number
            if (!$line_count && (strpos($t_line[0], "ISIC") !== false)) {
                $order_name = str_replace(array('.pgp', 'BRANCH_', 'POST_'), '', $t_line[0]);
                $order_id = $cardTransfers->getOrderId($order_name, $file);
                continue;
            }
            if (!$order_id) {
                $error = true;
                $import_log .= "<b>Order name not found, terminating import ...</b><br>\n";
                break;
            }
            if ($t_line[0]) {
                $line_count++;
                $t_line[1] = str_replace(" ", "", $t_line[1]);
                $chipCardId = $cards->saveChipNumber($t_line[0], $t_line[1], $order_id);
                $resChip = $cards->getSaveStatus();

                $t_line[2] = str_replace(" ", "", $t_line[2]);
                $panCardId = $cards->savePanNumber($t_line[2], $t_line[1], $order_id);
                $resPan = $cards->getSaveStatus();

                // Activate card if chip number was assigned
                if ($chipCardId) {
                    $cards->activate($chipCardId);
                }

                $import_log .= $line_count . ", " .
                    $t_line[0] . " -> " . $t_line[1] . ", " .
                    $resChip . "; " .
                    $t_line[2] . ", " .
                    $resPan . "<br>\n"
                ;
            }
        }
        echo $import_log . "<br>\n";
        echo "done<br>\n";
        fclose($fp);

        if ($order_id) {
            $card_list = $cards->getCardsWithoutChipNumber($order_id);
            if (count($card_list) > 0) {
                $error = true;
                IsicMail::sendCardTransferFailedNotification($order_name, $card_list);
                echo "Some of the cards didn't get chip numbers: " . print_r($card_list, true) . "<br>\n";
            }
        }

        if (!$error) {
            // moving all of the already imported files to imported subfolder
            rename($opendir . $file, $opendir . "imported/" . $file);
            $cardTransfers->setCardTransferSuccess($order_name, true);
        } else {
            // if order_id couldn't be found then moving files to error subfolder
            rename($opendir . $file, $opendir . "error/" . $file);
        }
        // and then deleting the file from source destination
        $scp->delete(SOURCE_PATH . $file);
    }
}
exit();