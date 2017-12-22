<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/module.calendar.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");

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

$logged = $ses->returnID();
$user = $ses->returnUser();
$group = $ses->group;

if (!$logged) {
	echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
	echo '<body onLoad= "self.close();">';
exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$txtf = new Text($language2, "admin_general");

//$sq = new sql;
//$sq1 = new sql;
//$db = new db;
//$db->connect();

// ##############################################################

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/popup_calendar.html");

if (!$_GET["field"]) $_GET["field"] = "date";
if (!$_GET["type"]) $_GET["type"] = "1";

$cal = new calendar;
//if ($type == 2) {
//    $cal->parameters($_SERVER["PHP_SELF"] . "?type=2&field=$field", array(), $field);
//} else {
 $cal->parameters($_SERVER["PHP_SELF"] . "?type=".$_GET["type"]."&field=".$_GET["field"], array(), $_GET["field"]);
//}
$cal->setTemplate("tmpl/popup_calendar_body.html");
$tpl->addDataItem("CALENDAR_BODY", $cal->show());

echo $tpl->parse();
exit;
