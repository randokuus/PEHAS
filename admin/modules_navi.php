<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/text.class.php");
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

$logged = $ses->returnID();
$user = $ses->returnUser();

if (!$logged) {
	echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
	echo '<body onLoad= "top.document.location=\'login.php\'">';
exit;
}

$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// ######### END INIT PART  #########

$db = new DB;
$db->connect();
$sq = new sql;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/vertical_navi.html");

// preselected item
if (isset($_GET['selected'])) {
    $tpl->addDataItem('PRESELECTED_LINK', htmlspecialchars($_GET['selected'], ENT_QUOTES));
}

$i = 0;
foreach ($GLOBALS['modules'] as $module) {
    if (file_exists(SITE_PATH . "/admin/module_$module.php") || is_array($GLOBALS["modules_sub"][$module])) {
        $i++;
        $txt = new Text($language2, "module_" . $module);
        $tpl->addDataItem("PARENT.ID", $i);
        $tpl->addDataItem("PARENT.NAME", $txt->display("module_title"));

        if (is_array($GLOBALS["modules_sub"][$module])) {
            $tpl->addDataItem("PARENT.STYLE", ' closed');
			$tpl->addDataItem("PARENT.URL", "null");
			$tpl->addDataItem("PARENT.CHILDREN.PARENT", $i);

			$j = 0;
			foreach ($GLOBALS["modules_sub"][$module] as $subelement) {
			    $j++;
				$tpl->addDataItem("PARENT.CHILDREN.CHILD.NAME", $txt->display("module_title$j"));
				$tpl->addDataItem("PARENT.CHILDREN.CHILD.URL", "module_$subelement.php");
				$tpl->addDataItem("PARENT.CHILDREN.CHILD.PARENT_ID", $i);
				$tpl->addDataItem("PARENT.CHILDREN.CHILD.ID", $j);
			}

		} else {
		    $tpl->addDataItem("PARENT.STYLE", ' opened');
			$tpl->addDataItem("PARENT.URL", "'module_$module.php'");
		}
    }
}

echo $tpl->parse();
