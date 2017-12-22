<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

error_reporting(0);

require_once("../class/config.php");
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
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/adminfields.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

// ######### BEGIN INIT PART  #########

// init session object
$ses = new Session();

// create database instance
// using database connection id from Session instance
$sql = new sql();
$sql->con = $ses->dbc;
$database = new Database($sql);
load_site_settings($database);
unset($sql);

if (($login == "true" && $username && $password) || (isset($PHP_AUTH_USER) && isset($PHP_AUTH_PW) && !$_COOKIE["ASID"])) {
    // start session
    $status = $ses->setSession($username, $password);
}

$logged = $ses->returnID();
$user = $ses->returnUser();

if (!$language2) $language2 = "EN";

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

if ($logged) {
    $href = substr($href, strpos($href, "admin"));
    $href = str_replace("/index.php", "/", $href);
    if ($href) {
        redirect($href . (false === strpos($href, '?') ? ('?') : ('&'))
          . "language2=$language2");
    }
    else {
        redirect("admin/?language2=$language2");
    }
    exit;
}

// init Text object for this page
$txt = new Text($language2, "login");
$txt1 = new Text($language2, "admin_settings1");

// ######### END INIT PART  #########

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/login.html");

    if (in_array(MODERA_PRODUCT, array('webmaster', 'webmaster-pro', 'intranet'
        , 'intranet-pro', 'extranet', 'extranet-pro')))
    {
        $tpl->addDataItem("TYPE", MODERA_PRODUCT);
    } else {
        $tpl->addDataItem("TYPE", "webmaster");
    }

    $tpl->addDataItem("USERNAME", $txt->display("username"));
    $tpl->addDataItem("PASSWORD", $txt->display("password"));
    $tpl->addDataItem("LANGUAGE", $txt->display("language"));
    $tpl->addDataItem("BUTTON", $txt->display("button"));

    $lang_list = array();
    $res =& $database->query('SELECT `language`, `title` FROM `languages` ORDER BY `language` = \'en\' DESC');
    while ($row = $res->fetch_assoc()) {
        $lang_list[htmlspecialchars($row['language'])] = htmlspecialchars($row['title']);
    }

    $fdata["type"] = "select";
    $fdata["list"] = $lang_list;
    $f = new AdminFields("language2",$fdata);
    $field_data = $f->display($language2);
    $tpl->addDataItem("FIELD_language", $field_data);

    if ($login == "true") {
        $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("login_error"));
    }

    $tpl->addDataItem("HREF", $href);

$tpl->addDataItem("TITLE", preg_replace("/http:\/\/|https:\/\//", "", SITE_URL) . " - modera.net administration");
echo $tpl->parse();
