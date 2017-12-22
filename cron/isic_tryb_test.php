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

echo "Data transfer from Trueb: \n";

$scp = new scp('', HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME, SOURCE_HOSTNAME, SOURCE_USERNAME);
if (!$scp->download($src_path, $tar_path)) {
    echo $scp->getErrors();
}

exit();