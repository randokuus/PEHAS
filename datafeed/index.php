<?php
/**
 * Modera.net datafeed waiter. this program interfaces to datafeed module to give XML/RSS formatted datafeeds
 * @access public
 */

// ##############################################################
error_reporting(0);
include_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
	hokusPokus();
}
else {
	trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sql = new sql;
$sql->con = $db->con;
$database = new Database($sql);
load_site_settings($database);
unset($sql);

// init language object
$lan = new Language($database, $_REQUEST["language"]);
$language = $lan->lan();

$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
	$GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
	$GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$nocache = true;
$_REQUEST["nocache"] = true;
$data_feed = new datafeed($language);
$data_feed->show();
exit();
