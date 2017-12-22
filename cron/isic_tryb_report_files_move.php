<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/FileBrowser.php");

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

$GLOBALS['ses'] = new Session();
$GLOBALS['ses']->_username = 'root';
$GLOBALS['ses']->user = 1;

$fileBrowser = new FileBrowser();
$fileBrowser->_perm = new Rights(1, 1, 'module', false);

$dir = 'root/upload/reports/tryb';

for ($year = 2011; $year <= date('Y'); $year++) {
//    echo 'Year: ' . $year . "\n";
    $result = $fileBrowser->createFolder($dir, $year);


    for ($month = 1; $month <= 12; $month++) {
//        echo 'Month: ' . $month . "\n";
        $result = $fileBrowser->createFolder($dir . '/' . $year, str_pad($month, 2, '0', STR_PAD_LEFT));
    }
}

$files = $fileBrowser->getFiles($dir);

foreach ($files['rows'] as $file) {
    $filename = str_replace(
        array('Report_', '.csv'),
        array('', ''),
        $file['filename']
    );
    $parts = explode('-', $filename);
    $date = explode('.', $parts[0]);

    $source = $dir;
    $target = $dir . '/' . $date[2] . '/' . $date[1];

    echo $file['folder'] . $file['filename'];

    $res = $fileBrowser->moveFiles(array($file['filename']), $source, $target);
}

echo "\ndone ..." . date('H:i:s') . "\n";