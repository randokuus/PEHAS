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
require_once(SITE_PATH . "/class/NewScp.class.php");
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

$src_path = "./share/test/tryb2isic/log_*.txt";
$tar_path = SITE_PATH . "/cache/tryb/";
$file = 'log_kala.txt';

echo "Data transfer from Trueb: \n";

$scp = new NewScp(HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);
if (!$scp->download($src_path, $tar_path)) {
    echo $scp->getErrors();
} else {
    $scp->delete('./share/test/tryb2isic/' . $file, 'sftp');
    echo $scp->getErrors();
}

exit();