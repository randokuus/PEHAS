<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
include_once("../class/config.php");
require_once("../class/common.php");
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
$txtf = new Text($language2, "module_poll");

// ##############################################################
// ##############################################################

$table = "module_poll"; // SQL table name to be administered

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
	"sort" => "end_time DESC" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
	"title"  => $txtf->display("title"),
	"start_time" => $txtf->display("start_time"),
	"end_time" => $txtf->display("end_time"),
	"active" => $txtf->display("active"),
	"question" => $txtf->display("question")
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
	"language",
	"title",
	"start_time",
	"end_time",
	"active"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//	$idfield => "ID", // if you want to display the ID as well,
	"title" => $txtf->display("title"),
	"start_time" => $txtf->display("start_time"),
	"end_time" => $txtf->display("end_time"),
	"active" => $txtf->display("active")
);

/* required fields */
$required = array(
	"title",
	"start_time",
	"end_time"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

 	$what = array(
		"$table.*"
	);
	$from = array(
		$table
	);

	$where = "language = '$language'";

	$filter_fields = array(
		"$table.title",
		"$table.start_time",
		"$table.end_time"
	);

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
	global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
	$sq = new sql;

	if ($show == "add") {
		$adm->assign("start_time", date("Y-m-d H:i:s"));
		$adm->assign("end_time", date("Y-m-d H:i:s", mktime(0,1,0,date("m"),date("d")+30, date("Y"))));
	}

	$adm->assignProp("active", "type", "checkbox");
	$adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

	if ($show == "add") {
		$adm->displayButtons("question");
		$adm->assignProp("question", "list", "");
		$adm->assignProp("question", "type", "select2");
		$adm->assignProp("question", "size", "5");
		//$adm->assignProp("question", "extra", "&nbsp;<a href=\"javascript:doPrompt('" . $txtf->display("question_add") . "', 'question')\"><img align=top src=\"pic/but0.php?t=" . $txtf->display("question_add") . "\" border=0 alt=\"\"></a>&nbsp;<a href=\"javascript:deleteSelectedItemsFromList('question')\"><img align=top src=\"pic/but0.php?t=" . $txtf->display("question_del") . "\" border=0 alt=\"\"></a>");
		$adm->assignProp("question", "extra", "<button type=button onClick=\"doPrompt('" . $txtf->display("question_add") . "', 'question')\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$txtf->display("question_add")."</button><br />
		<button type=button onClick=\"editItemInList('" . $txtf->display("question_change") . "', 'question')\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$txtf->display("question_change")."</button><br />
		<button type=button onClick=\"deleteSelectedItemsFromList('question')\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".$txtf->display("question_del")."</button>");

	}
	else {
		$adm->assignProp("question", "type", "nothing");
		//$adm->assignProp("question", "extra", "<button onClick=\"newWin('module_poll_question.php?poll=$id')\">".$txtf->display("question_change") . "</button>");
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

	//$adm->assign("lastmod", date("Y-m-d H:i:s"));
	//$adm->assign("user", $user);
	$adm->assign("language", $language);

	/* DB writing part */
	if ($do == "add") {

		// permissions
		$perm->Access (0, 0, "a", "poll");

		$required[] = "question";
		$res = $adm->add($table, $required, $idfield);
		if (is_array($question)) {
			$isid = $sq->insertID();
			//$isid = mysql_insert_id();
			if ($isid && $isid != 0) {
				for ($c = 0; $c < sizeof($question); $c++) {
					$sq->query($adm->dbc, "INSERT into module_poll_questions (id, poll, question, score, prio) VALUES (null, '$isid', '" . addslashes($question[$c]) . "', 0, 0)");
				}
			}
		}

		if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\""); }
		else {
			$show = "add";
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external();
			$result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
		}
	}
	else if ($do == "update" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "poll");

		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
		if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\""); }
		else {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
			$adm->getValues();
			$adm->types();
			external();
			$result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
		}
	}
	else if ($do == "delete" && $id) {

		// permissions
		$perm->Access (0, $id, "d", "poll");

		$res = $adm->delete($table, $idfield, $id);
		if ($res == 0) {
			$sq->query($adm->dbc, "DELETE FROM module_poll_questions WHERE poll = '" . addslashes($id) . "'");
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
		 }
		else {
			$result = $general["error"];
		}
	}
	/* end DB writing part */

	if ($show == "add") {

		// permissions
		$perm->Access (0, 0, "a", "poll");

		if ($copyto != "") 	$adm->fillValues($table, $idfield, $copyto);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
	}
	else if ($show == "modify" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "poll");

		$adm->fillValues($table, $idfield, $id);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
	}
	else if (!$res || $res == 0) {
		// permissions
		$perm->Access (0, 0, "m", "poll");

		external();
		$result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
	}

if ($show == "add" || ($do == "add" && is_array($res))) {
	$tpl->addDataItem("TITLE", $txtf->display("module_title"));
	$active_tab = 1;
}
else {
	$tpl->addDataItem("TITLE", $txtf->display("module_title"));
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