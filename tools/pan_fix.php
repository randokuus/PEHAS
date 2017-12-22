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
require_once(SITE_PATH . "/class/IsicCommon.php");

$db = new DB();
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $GLOBALS['site_settings'];

$isicCommon = IsicCommon::getInstance();

$dir = '/home/minukool/web/khs/cache/tryb/imported';

$files = scandir($dir);

$prefixes = array(
    'log_ISIC201301',
    'log_ISIC201302',
    'log_ISIC201303',
    'log_ISIC201304',
    'log_ISIC201305',
);

function getOrderId($name) {
    global $database;
    $r = $database->query('SELECT `id` FROM `module_isic_card_transfer` WHERE `order_name` = ? LIMIT 1', $name);
    if ($data = $r->fetch_assoc()) {
        return $data['id'];
    }
    return null;
}

$numbers = array();

foreach ($files as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }

    $filePrefix = substr($file, 0, strlen($prefixes[0]));
    if (in_array($filePrefix, $prefixes)) {
//        echo $file . "\n";

        $rows = file($dir . '/' . $file, FILE_IGNORE_NEW_LINES);
        $orderName = '';

        foreach ($rows as $rowNum => $data) {
            if (!$rowNum) {
                $orderName = str_replace('.pgp', '', trim($data));
                $orderId = getOrderId($orderName);
                continue;
            }
            $values = explode(':', $data);
            if (sizeof($values) != 3) {
                continue;
            }

            $isicNum = $values[1];
            $panNum = $values[2];

            $numbers[$isicNum][] = array($orderId, trim($panNum), $orderName);
        }
    }
}

foreach ($numbers as $isicNum => $panNumbers) {
    // if (sizeof($panNumbers) > 1) {
        $orderPan = end($panNumbers);
        $result = $isicCommon->savePanNumber($orderPan[1], $isicNum, $orderPan[0]);
        echo $isicNum . ',' . implode(',', $orderPan) . ': ' . $result . "\n";
//        exit();
    // }
}

//print_r($numbers);