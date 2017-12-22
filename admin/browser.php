<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;
//error_reporting(E_ALL);
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Filenames.php");
require_once(SITE_PATH . "/class/JsonEncoder.php");
require_once(SITE_PATH . "/class/FileBrowser.php");

// ##############################################################

// init session object
$ses = new Session();

// create database instance
// using database connection id from Session instance
$sql = new sql();
$sql->con = $ses->dbc;
$database = new Database($sql);
load_site_settings($database);
unset($sql);

$db = new DB;
$db->connect();
$sq = new sql;

$logged = $ses->returnID();
$user = $ses->returnUser();
$group = $ses->group;

if (!$logged) {
    echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
    echo '<body onLoad= "top.document.location=\'login.php\'">';
exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "files_index");
$txta = new Text($language2, "admin_files");

// ##############################################################
// ##############################################################


if (false !== strpos($folder, '.')) exit();
if ($folder == "/") $folder = "";

$show_max = 500;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/browser.html");
$tpl->addDataItem("BROWSER.DUMMY");


$browser = new FileBrowser();

if (!isset($_GET['folder'])) {
    $_GET['folder'] = $browser->getFolderState();
}
if (substr($_GET['folder'], 0, 1) != '/') {
    $_GET['folder'] = '/' . $_GET['folder'];
}

$tpl->addDataItem("FOLDER", $_GET['folder']);
$tpl->addDataItem("PERMACCESS", (int) $browser->hasPermAccess());
$tpl->addDataItem("VIEWSTATE", $browser->getViewState());
$tpl->addDataItem("DISABLEDSTATE", (int) $browser->getDisabledState());
$tpl->addDataItem("SELECTEDFILES", JsonEncoder::encode($_GET['selectedfile']));


if (!$mode) $mode = "all";
if ($mode == "-") $mode = "all";


$browser->process();

echo $tpl->parse();