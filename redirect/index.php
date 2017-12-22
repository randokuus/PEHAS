<?
// ***********************************************
// module 'redirect' linker
// last mod 21.02.05, siim
// ***********************************************
error_reporting(0);

include_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/common.php");
if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
	hokusPokus();
}
else {
	trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

$db = new db;
$db->connect();
$sq = new sql;

// new database object (wrapper for the old one)
$sq->con = $db->con;
$database = new Database($sq);

// loading globally site settings
load_site_settings($database);

$lan = new Language($GLOBALS['database'], $language);
$language = $lan->lan();

$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
	$GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
	$GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$rdr = new redirect;
$status = $rdr->jump($_GET['link']);
