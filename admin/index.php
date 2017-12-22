<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(0);

require_once("../class/config.php");
require_once("../class/common.php");

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

if ($login == "true" && $username && $password && !$_COOKIE["ADM_SESS_SID"]) {
//if (($login == "true" && $username && $password) || (isset($PHP_AUTH_USER) && isset($PHP_AUTH_PW) && !$_COOKIE["ASID"])) {
//  if (isset($PHP_AUTH_USER) && isset($PHP_AUTH_PW)) {
//      $status = $ses->setSession($PHP_AUTH_USER, $PHP_AUTH_PW);
//  }
//  else {
        $status = $ses->setSession($username, $password);
//  }
}
$logged = $ses->returnID();
$user = $ses->returnUser();

if (!$logged) redirect("admin/login.php?href=".$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// ######### END INIT PART  #########

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/index.html");

if ($_SERVER["QUERY_STRING"] && $_GET["language2"] == "" && $_GET["language"] == "") {
    $el = split("\\/", $_SERVER["QUERY_STRING"]);
    $top = $el[0];
    $left = $el[1];
    $right = $el[2];
}

if (!$left && !$right) {
    $left = "content_navi.php";
    $right = "dashboard.php";
}

if ($top) {
    $tpl->addDataItem("TOPPARAM", "top=$top");
}

if ($left && $right) {
    $tpl->addDataItem("BOTPARAM", "main_index.php?$left/$right");
}
else if ($left && !$right) {
    $tpl->addDataItem("BOTPARAM", "$left");
}

$tpl->addDataItem("TITLE", preg_replace("/http:\/\/|https:\/\//", "", SITE_URL) . " - modera.net administration");

$result = $tpl->parse();
//$result = addParameterToUrl($result, "rnd=".randomNumber());
echo $result;
//$db->disconnect();
