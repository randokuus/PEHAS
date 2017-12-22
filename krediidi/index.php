<?php
error_reporting(0);
chdir('..');
include("class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
    hokusPokus();
} else {
    trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/" . DB_TYPE . ".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
if (defined("PANGALINK_MOCK")) {
    require_once(SITE_PATH . "/class/Pangalink/Krediidi_Mock.php");
} else {
    require_once(SITE_PATH . "/class/Pangalink/Krediidi.php");
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

if (!$GLOBALS["templates_" . $language]) {
    $GLOBALS["templates_" . $language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_" . $language]) {
    $GLOBALS["temp_desc_" . $language] = $GLOBALS["temp_desc_EN"];
}

// ##############################################################

// extract some request parameters
foreach (array('username', 'id') as $v) {
    if (array_key_exists($v, $_GET)) $$v = $_GET[$v];
    if (array_key_exists($v, $_POST)) $$v = $_POST[$v];
}

if ($username) {
    $id = $username;
    $tpl = new template;
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile(SITE_PATH . "/tmpl/module_pangalink_form_utf8.html");
    $url = SITE_URL . "/krediidi/id/" . $id;
    $pangalink = new Pangalink_Krediidi();
    $form = $pangalink->generateVKForm($url, 4011);
    if ($form) {
        $tpl->addDataItem("FORM", $form);
        echo $tpl->parse();
        exit();
    }
} elseif ($id) {
    $pangalink = new Pangalink_Krediidi();
    $res = $pangalink->checkActivation(3012);
    $usr = new user;

    $vkInfo = $pangalink->buildAttrList($res);
    $error = $vkInfo ? 0 : 7;
    if (!$error && !$usr->setKrediidiSession($id, $vkInfo)) {
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
// Krediidipank authorization should be enabled
header('Location: ' . SITE_URL . '/?auth_type=7&auth_error=1&error_code=' . $error);
