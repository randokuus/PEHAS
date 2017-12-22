<?php
/**
 * Modera.net user manager. Will manage login and user session creation, if default login form is used
 * @access public
 */

/**
 * Simple debug-function
*/

function log_id_action($data) {
    if ($fp_log = fopen(SITE_PATH . "/cache/id/log.txt", "a+")) {
        fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
        fwrite($fp_log, $data . "\n");
        fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
        fclose($fp_log);
    }
}


// ##############################################################
error_reporting(0);
chdir('..');
include("class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
	hokusPokus();
}
else {
	trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
if (defined("PANGALINK_MOCK")) {
    require_once(SITE_PATH . "/class/id_card_mock.php");
} else {
    require_once(SITE_PATH . "/class/id_card.class.php");
}

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data_settings = $data = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, "");
$language = $lan->lan();

$txt = new Text($language, "module_user");
$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
	$GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
	$GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

// ##############################################################

// extract some request parameters
foreach (array('href', 'structure', 'content', 'print', 'nocache', 'login_check', 'username') as $v) {
    if (array_key_exists($v, $_GET)) $$v = $_GET[$v];
    if (array_key_exists($v, $_POST)) $$v = $_POST[$v];
}

$id = new id_card();
$usr = new user;
//$_SERVER['SSL_CLIENT_S_DN_CN']
$error = $id->id_card_valid() ? 0 : 2;
if (!$error && !$usr->setIdCardSession($username, $id->getClientSDnCn())) {
    $error = $usr->errorCode;
}

if (!$error) {
	$GLOBALS["user_logged"] = $usr->status;
	$usr->returnUser();
	$GLOBALS["user_data"] = array($usr->user, $usr->user_name, $usr->username, $usr->company, $usr->group, $usr->groups);
	$GLOBALS["user_show"] = $usr->isAuthorisedGroup();
	unset($usr);
    log_id_action("GOOD: " . print_r($_SERVER, true));
    redirect(SITE_URL . "/?nocache=true");
} else {
    // ID-card authorization should be enabled
    log_id_action("FAIL:" . print_r($_SERVER, true));
    header('Location: ' . SITE_URL . '/?auth_type=2&auth_error=1&error_code=' . $error);
}