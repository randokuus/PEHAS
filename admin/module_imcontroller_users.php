<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
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

$table = "IM_users"; // SQL table name to be administered

$idfield = "user_id"; // name of the id field (unique field, usually simply 'id')

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
	"sort" => "$table.username ASC" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
	"username" => $txtf->display("username"),
	"password"=> $txtf->display("password"),
	"firstname" => $txtf->display("firstname"),
	"lastname" => $txtf->display("lastname"),
	"active" => $txtf->display("active"),
	"user_role" => $txtf->display("user_role"),
	"validity_begin" => $txtf->display("validity_begin"),
	"validity_end" => $txtf->display("validity_end"),
	"use_emoticons" => $txtf->display("use_emoticons"),
	"use_sound" => $txtf->display("use_sound"),
	"user_groups" => $txtf->display("user_groups")
);

$tabs = array(
	1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
	2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
	"username",
	"firstname",
	"lastname",
	"active",
	"user_role",
	"validity_begin",
	"validity_end",
	"use_emoticons",
	"use_sound"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
	$idfield => "ID", // if you want to display the ID as well,
	"username" => $txtf->display("username"),
	"firstname" => $txtf->display("firstname"),
	"lastname" => $txtf->display("lastname"),
	"user_role" => $txtf->display("user_role"),
	"active" => $txtf->display("active")
);

/* required fields */
$required = array(
	"username",
	"firstname",
	"user_role"
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
		"$table.username",
		"$table.firstname",
		"$table.lastname"
	);

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
	global $adm, $show, $txtf, $txt, $group, $language, $id, $do, $structure;
	$sq = new sql;

	if ($show == "modify") {
	 	$adm->assign("password", "");
	}
	else {
		$adm->assign("validity_begin", date("Ymdhis"));
		$adm->assign("validity_end", date("Ymdhis", mktime(0, 0, 0, date("m"),  date("d"),  date("Y")+5)));
	}

	$adm->assignProp("password", "type", "password");

	$adm->displayOnly("username");

	$adm->assignProp("user_role", "type", "select");
	$adm->assignProp("user_role", "list", array("1" => $txtf->display("user_role1"), "2" => $txtf->display("user_role2"), "3" => $txtf->display("user_role3")));

	$adm->assignProp("active", "type", "checkbox");
	$adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

	$adm->assignProp("use_emoticons", "type", "checkbox");
	$adm->assignProp("use_emoticons", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

	$adm->assignProp("use_sound", "type", "checkbox");
	$adm->assignProp("use_sound", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

	$adm->assignProp("user_groups", "type", "select2");
	$adm->assignExternal("user_groups", "IM_groups", "group_id", "name", "ORDER BY name ASC", false);

	if ($show == "modify" && $id && !$do) {
		$sq->query($adm->dbc, "SELECT user_id, group_id FROM IM_users_group WHERE user_id = '".addslashes($id)."'");
		$list = array();
		while ($data = $sq->nextrow()) {
			$list[] = $data["group_id"];
		}
		$sq->free();
		$adm->assign("user_groups", $list);
	}

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
	if ($do == "add") {

		// permissions
		$perm->Access (0, 0, "a", "imcontroller");

		$sq->query($adm->dbc, "SELECT username FROM IM_users WHERE username = '" . addslashes($adm->values["username"]) . "'");
		if ($sq->numrows != 0) {
			$adm->values["username"] = "";
			$adm->general["required_error"] .= ". " . $txtf->display("user_error");
		}

		$sq->query($adm->dbc, "SELECT MD5(PASSWORD('".addslashes($adm->values["password"])."')) as passu");

		$adm->values["password"] = $sq->column(0, "passu");
		$adm->assignProp("password", "type", "passwordx");

		$res = $adm->add($table, $required, $idfield);

		$adm->assignProp("password", "type", "password");

		if ($res == 0) {

			// process groups.
			if ($adm->fields["user_groups"]["value"]) {
				$grps = split(",", $adm->fields["user_groups"]["value"]);
				for ($c = 0; $c < sizeof($grps); $c++) {
					$sq2->query($adm->dbc, "INSERT INTO IM_users_group (user_id, group_id) VALUES ('".addslashes($adm->insert_id)."','".addslashes($grps[$c])."')");
				}
			}

			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

		 }
		else {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external();
			$result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
		}
	}
	else if ($do == "update" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "imcontroller");

		if ($_POST["password"] != "") $upd_fields[] = "password";

		$sq->query($adm->dbc, "SELECT MD5(PASSWORD('".addslashes($adm->values["password"])."')) as passu");

		$adm->values["password"] = $sq->column(0, "passu");
		$adm->assignProp("password", "type", "passwordx");

		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);

		$adm->assignProp("password", "type", "password");

		if ($res == 0) {

			// process groups.
			if ($adm->fields["user_groups"]["value"]) {

				// select existing users in group
				$grps_existing = array();
				$sq2->query($adm->dbc, "SELECT group_id FROM IM_users_group WHERE user_id = '".addslashes($id)."' ORDER BY group_id ASC");
				while ($data = $sq2->nextrow()) {
					$grps_existing[] = $data["group_id"];
				}
				$sq->free();

				$grps = $adm->values["user_groups"]; //split(",", $adm->fields["user_groups"]["value"]);
				//$grps = sort($grps, SORT_NUMERIC);

				$add = array();
				for ($c = 0; $c < sizeof($grps); $c++) {
					//new entry
					if (!in_array($grps[$c], $grps_existing)) {
						$add[] = $grps[$c];
					}
				}
				reset($grps);

				$remove = array();
				for ($c = 0; $c < sizeof($grps_existing); $c++) {
					//remove entry
					if (!in_array($grps_existing[$c], $grps)) {
						$remove[] = $grps_existing[$c];
					}
				}
				reset($grps_existing);

				for ($c = 0; $c < sizeof($remove); $c++) {
					$sq2->query($adm->dbc, "DELETE FROM IM_users_group WHERE user_id = '".addslashes($id)."' AND group_id = '".addslashes($remove[$c])."'");
				}
				for ($c = 0; $c < sizeof($add); $c++) {
					$sq2->query($adm->dbc, "INSERT INTO IM_users_group (user_id, group_id) VALUES ('".addslashes($id)."','".addslashes($add[$c])."')");
				}

			}

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
	else if ($do == "delete" && $id) {

		// permissions
		$perm->Access (0, $id, "d", "imcontroller");

		$res = $adm->delete($table, $idfield, $id);
		if ($res == 0) {

			$sq2->query($adm->dbc, "DELETE FROM IM_users_group WHERE user_id = '".addslashes($id)."'");

			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");

		}
		else { $result = $general["error"]; }
	}
	/* end DB writing part */

	if ($show == "add") {

		// permissions
		$perm->Access (0, 0, "a", "imcontroller");

		if ($copyto != "") 	$adm->fillValues($table, $idfield, $copyto);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
	}
	else if ($show == "modify" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "imcontroller");

		$adm->fillValues($table, $idfield, $id);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
	}
	else if (!$res || $res == 0) {
		// permissions
		$perm->Access (0, 0, "m", "imcontroller");

		external();
		$result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
	}

if ($show == "add" || ($do == "add" && is_array($res))) {
	$tpl->addDataItem("TITLE", $txtf->display("module_title2"));
	$active_tab = 1;
}
else {
	$tpl->addDataItem("TITLE", $txtf->display("module_title2"));
	$active_tab = 2;
}

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