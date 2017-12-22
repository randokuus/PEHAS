<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

error_reporting(0);
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

// init user permissions
$perm = new Rights($group, $user, "module", true);

// init Text object(s) for this page, multiple can be used
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_iforum");

// ##############################################################
// ##############################################################

$module_name = "iforum"; //define module name
require_once(dirname(__FILE__).'/module_'.$module_name.'_common.php');
$table = "module_iforum_sections"; // SQL table name to be administered (many possible datasources can be used, but one should be primary)

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
	"sort" => "$table.prio ASC, $table.name ASC" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

// fields to show in the form
$fields = array(
	"name"  => $txtf->display("section_name"),
	"prio" => $txtf->display("prio")
);

$tabs = array(
	1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
	2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

        /* you can use different tabsets for add/modify and list view if needed
        $tabs = array(
          1 => $txtf->display("tabset1"),
        	2 => $txtf->display("tabset2")
        );
        $tabs_list = array(
        	1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
        	2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
        );
   */

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();



// the fields that we want to update (do not include primary key (id) here)
$upd_fields = array(
	"language",
	"name",
	"prio"
);

// which data columns to display in the list
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
	$idfield => "ID", // if you want to display the ID also
	"name" => $txtf->display("section_name"),
	 "prio" => $txtf->display("prio")
);

// required fields
$required = array(
	"name"
	,'prio'
 );

// ##############################################################
// To construct the main list query SELECT what from where / also which fields to include in the Filter command

 	$what = array(
		"$table.*"
	);
	$from = array(
		$table
	);

	$where = "$table.language = '".addslashes($language)."'";

	$filter_fields = array(
		"$table.name"
	);

// end display list part
// ##############################################################

/**
 * External function is called with every form/list call. Here you can define or redefine values, lists, fields and their types.
 * @param object reference to admin object
 * @param string show variable, add/modify
 * @param integer id field value
 * @param string language code
*/

function external(&$adm, $show, $id, $language) {
	global $txtf;
}

/**
 * Cachemanager is called after every add/modify/update
 * @param string add, modify or delete
*/
function cacheManager($action) {
	clearCacheFiles("tpl_iforum", '');
}

// ##############################################################
// ##############################################################
// DO NOT EDIT BELOW THESE LINES
// ##############################################################
// ##############################################################

if ($_REQUEST["max_entries"] && $_REQUEST["max_entries"] <= 100) { $general["max_entries"] = $_REQUEST["max_entries"]; }

$show = $_REQUEST["show"];
$id = $_REQUEST["id"];
$do = $_REQUEST["do"];
$start = $_REQUEST["start"];
$sort = $_REQUEST["sort"];
$sort_type = $_REQUEST["sort_type"];

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

	$adm = new Admin($table);

	$sq = new sql;

	$adm->assign("user", $user);
	$adm->assign("language", $language);

// ##############################################################
// DB writes

	if ($do == "add") {

		// permissions
		$perm->Access (0, 0, "a", $module_name);

		$res = $adm->add($table, $required, $idfield);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
            cacheManager("add");
		 }
		else {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external($adm, $show, $id, $language);
			$result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
		}
	}
	else if ($do == "update" && $id) {

		// permissions
		$perm->Access (0, $id, "m", $module_name);

		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
            cacheManager("modify");
		}
		else {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external($adm, $show, $id, $language);
			$result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
		}
	}
	else if ($do == "delete" && $id) {
		$sq->query($adm->dbc,"select count(*) as kokku from module_iforum_forums where section_id = ".intval($id));
		if ($sq->column(0,'kokku') > 0){
			$result = $txtf->display('delete_forums_first');
		}else{
			// permissions
			$perm->Access (0, $id, "d", $module_name);

			$res = $adm->delete($table, $idfield, $id);
			if ($res == 0) {
				$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
	            cacheManager("delete");
			}
			else { $result = $general["error"]; }
		}
		$sq->free();
	}

// ##############################################################

	if ($show == "add") {

		// permissions
		$perm->Access (0, 0, "a", $module_name);

		$sq->query($adm->dbc,
			 "SELECT MAX(prio) as max FROM module_iforum_sections");
		$max = $sq->column(0, "max");
		if (!$max) $max = 0;
		$sq->free();

		$adm->assign("prio", ($max+5));
		$adm->values["prio"] = ($max+5);

		if ($copyto != "") 	$adm->fillValues($table, $idfield, $copyto);
		$adm->types();
        external($adm, $show, $id, $language);
		$result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
	}
	else if ($show == "modify" && $id) {

		// permissions
		$perm->Access (0, $id, "m", $module_name);

		$adm->fillValues($table, $idfield, $id);
		$adm->types();
        external($adm, $show, $id, $language);
		$result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
	}
	else if (!$res || $res == 0) {
        // list

		// permissions
		$perm->Access (0, 0, "m", $module_name);
        external($adm, $show, $id, $language);
		$result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
	}

// ##############################################################

if ($show == "add" || ($do == "add" && is_array($res))) {
	$tpl->addDataItem("TITLE", $txtf->display("module_title"));
	$active_tab = 1;
}
else {
	$tpl->addDataItem("TITLE", $txtf->display("module_title"));
	$active_tab = 2;
}

// #########################

// 2 different tabsets
if (is_array($tabs_list) && sizeof($tabs_list) > 0) {
	// LIST VIEW
	if ((($show == "modify" && !$id) || !$show) && (!$res || $res == 0)) {
		$nr = 1;
		while(list($key, $val) = each($tabs_list)) {
			$tpl->addDataItem("TABS.ID", $nr);
			$tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs_list).", '".$val[1]."');");
			$tpl->addDataItem("TABS.NAME", $val[0]);
				if ($active_tab == $nr) {
					$tpl->addDataItem("TABS.CLASS", "class=\"active\"");
				}
				else {
					$tpl->addDataItem("TABS.CLASS", "class=\"\"");
				}
			$nr++;
		}
	}
	// FORM VIEW
	else {
		$nr = 1;
		while(list($key, $val) = each($tabs)) {
			$tpl->addDataItem("TABS.ID", $nr);
			$tpl->addDataItem("TABS.URL", "javascript:enableFieldset($nr, 'fieldset$key', '', ".sizeof($tabs).", ".sizeof($field_groups).");");
			//$tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
			$tpl->addDataItem("TABS.NAME", $val);
			if ($key == 1) {
				$tpl->addDataItem("TABS.CLASS", "class=\"active\"");
			}
			$nr++;
		}

		$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";
	}
}

// 1 tabset
else {
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

}

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;