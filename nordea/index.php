<?php
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
require_once(SITE_PATH . "/class/IsicEncoding.php");
if (defined("PANGALINK_MOCK")) {
    require_once(SITE_PATH . "/class/Pangalink/Nordea_Mock.php");
} else {
    require_once(SITE_PATH . "/class/Pangalink/Nordea.php");
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
foreach (array('username') as $v) {
    if (array_key_exists($v, $_GET)) $$v = $_GET[$v];
    if (array_key_exists($v, $_POST)) $$v = $_POST[$v];
}

if ($_GET['B02K_CUSTID']) {
    $id = $_GET['B02K_CUSTID'];
}

if ($username) {
    $id = $username;
    $tpl = new template;
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile(SITE_PATH . "/tmpl/module_pangalink_form.html");
    $url = SITE_URL . "/nordea/";
    $pangalink = new Eident_Nordea();
    $form = $pangalink->generateForm('e-ident', $url);
    if ($form) {
        $tpl->addDataItem("FORM", $form);
        echo $tpl->parse();
        exit();
    }
} elseif ($id) {
    $pangalink = new Eident_Nordea();
    $res = $pangalink->checkActivation('e-ident-response');
    $usr = new user;
    $isicEncoding = new IsicEncoding();
    $res['B02K_CUSTNAME'] = $isicEncoding->convertStringEncoding($res['B02K_CUSTNAME']); 
    
    $error = $res['B02K_CUSTID'] ? 0 : 6;
    if (!$error && !$usr->setNordeaSession($res['B02K_CUSTID'], $res['B02K_CUSTNAME'])) {
        $error = $usr->errorCode;
    }
    
    if (!$error) {
        $GLOBALS["user_logged"] = $usr->status;
        $usr->returnUser();
        $GLOBALS["user_data"] = array($usr->user, $usr->user_name, $usr->username, $usr->company, $usr->group, $usr->groups);
        $GLOBALS["user_show"] = $usr->isAuthorisedGroup();
        unset($usr);
        redirect(SITE_URL . "/?nocache=true");
    }
}
// Nordea authorization should be enabled
header('Location: ' . SITE_URL . '/?auth_type=6&auth_error=1&error_code=' . $error);
