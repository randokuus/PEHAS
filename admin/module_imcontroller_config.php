<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
$show = "modify";
$id = 1;
include_once("../class/config.php");
require_once("../class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once("../class/".DB_TYPE.".class.php");
require_once("../class/admin.session.class.php");
require_once("../class/admin.language.class.php");
require_once("../class/text.class.php");
require_once("../class/admin.class.php"); 			// administration main object
require_once("../class/adminfields.class.php"); // form fields definitions for admin
require_once("../class/templatef.class.php");  // site default template object
require_once("../class/Database.php");

// ##############################################################

// init session object
$ses = new Session();

// create database instance
// using database connection id from Session instance
$sql = new sql();
$sql->con = $ses->dbc;
$database = new Database($sql);
$GLOBALS['database'] =& $database;
load_site_settings($database);
unset($sql);

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

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_imcontroller");

// ##############################################################
// ##############################################################

$table = "IM_config"; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
	"debug" => $GLOBALS["modera_debug"],
	"template_main" => "tmpl/admin_main_module.html",
	"template_form" => "tmpl/admin_form1.html",
	"template_list" => "tmpl/admin_list.html",
	"add_text" => $txt->display("add_text"),
	"modify_text" => $txt->display("modify_text"),
	"delete_text" => $txt->display("delete_text"),
	"required_error" => $txt->display("required_error"),
	"delete_confirmation" => $txt->display("delete_confirmation"),
	"backtolist" => $txt->display("backtolist"),
	"current" => $txt->display("current"),
	"error" => $txt->display("error"),
	"filter" => $txt->display("filter"),
	"display" => $txt->display("display"),
	"display1" => $txt->display("display1"),
	"prev" => $txt->display("prev"),
	"next" => $txt->display("next"),
	"pages" => $txt->display("pages"),
	"button" => $txt->display("button"),
	"max_entries" => 50,
	"sort" => "" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
	"conf_server" => $txtf->display("conf_server"),
	"conf_port1" => $txtf->display("conf_port1"),
	"conf_port2" => $txtf->display("conf_port2"),
	"conf_type" => $txtf->display("conf_type"),
	"conf_title" => $txtf->display("conf_title"),
	"conf_anon" => $txtf->display("conf_anon"),
	"conf_lang" => $txtf->display("conf_lang"),
	"conf_sound" => $txtf->display("conf_sound"),
	"conf_exittxt" => $txtf->display("conf_exittxt"),
	"conf_email" => $txtf->display("conf_email"),
	"conf_status" => $txtf->display("conf_status")
);

$tabs = array(
	2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
	"conf_server",
	"conf_port1",
	"conf_port2",
	"conf_type",
	"conf_title",
	"conf_anon",
	"conf_lang",
	"conf_sound",
	"conf_exittxt",
    "conf_email",
	"conf_status"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
	$idfield => "ID" // if you want to display the ID as well,
);

/* required fields */
$required = array(
	"conf_server",
	"conf_port1",
	"conf_port2",
	"conf_type",
	"conf_title",
	"conf_anon",
	"conf_lang",
	"conf_sound"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

 	$what = array(
		"$table.*"
	);
	$from = array(
		$table
	);

	$where = ""; //"language = '$language'";

	$filter_fields = array(
	);

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
	global $adm, $show, $txtf, $txt, $group, $language, $id, $do, $structure;
	$sq = new sql;

	$langs = array();
	if ($dir = @opendir(SITE_PATH . "/modera_im/")) {
		while (($fil = readdir($dir)) !== false) {
			if ($fil != "." && $fil != ".." && !is_dir($dir.$fil) && substr($fil, 0, 8) == "im_lang_") {
				$langs[$fil] = $fil;
			}
		}
    closedir($dir);
	}

	$adm->assignProp("conf_lang", "type", "select");
	$adm->assignProp("conf_lang", "list", $langs);

	$sound = array();
	if ($dir = @opendir(SITE_PATH . "/modera_im/")) {
		while (($fil = readdir($dir)) !== false) {
			if ($fil != "." && $fil != ".." && !is_dir($dir.$fil) && strtolower(substr($fil, -3)) == "mp3") {
				$sound[$fil] = $fil;
			}
		}
    closedir($dir);
	}

	$adm->assignProp("conf_sound", "type", "select");
	$adm->assignProp("conf_sound", "list", $sound);

	$adm->assignProp("conf_type", "type", "select");
	$adm->assignProp("conf_type", "list", array("1" => $txtf->display("conf_type1"), "2" => $txtf->display("conf_type2")));

	$adm->assignProp("conf_status", "type", "select");
	$adm->assignProp("conf_status", "list", array("1" => $txtf->display("conf_status1"), "2" => $txtf->display("conf_status2")));

}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 100) { $general["max_entries"] = $max_entries; }

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

	$adm = new Admin($table);

	$sq = new sql;
	$sq2 = new sql;

	//$adm->assign("lastmod", date("Y-m-d H:i:s"));
	//$adm->assign("user", $user);
	$adm->assign("language", $language);

	/* DB writing part */
	if ($do == "update" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "imcontroller");

		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
		if ($res == 0) {

			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");

		}
		else {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external();
			$result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
		}
	}
	/* end DB writing part */

	if ($show == "modify" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "imcontroller");

		$adm->fillValues($table, $idfield, $id);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
	}
/*	else if (!$res || $res == 0) {
		// permissions
		$perm->Access (0, 0, "m", "imcontroller");

		external();
		$result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
	}	*/

$tpl->addDataItem("TITLE", $txtf->display("module_title1"));
$active_tab = 1;

$nr = 1;
while(list($key, $val) = each($tabs)) {
	$tpl->addDataItem("TABS.ID", $nr);
	$tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
	$tpl->addDataItem("TABS.NAME", $val[0]);
		if ($active_tab == $nr) {
			$tpl->addDataItem("TABS.CLASS", "class=\"active\"");
		}
		else {
			$tpl->addDataItem("TABS.CLASS", "class=\"\"");
		}
	$nr++;
}

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;