<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php"); 			// administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
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
	echo '<body onLoad= "top.document.location=\'login.php\'">';
exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_form2");

// ##############################################################
// ##############################################################

$table = "module_form2_fields"; // SQL table name to be administered

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
	"sort" => "prio ASC, id ASC" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
	"form" => $txtf->display("form"),
	"name" => $txtf->display("name"),
	"type" => $txtf->display("type"),
	"descr" => $txtf->display("descr"),
//	"fields1" => $txtf->display("fields1"),
	"question" => $txtf->display("options"),
	"required" => $txtf->display("required"),
	"prio" => $txtf->display("prio")
);

$tabs = array(
	1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add&form=$form"),
	2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
	"form",
	"name",
	"type",
	"descr",
	"options",
//	"fields1",
	"required",
	"prio"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//	$idfield => "ID", // if you want to display the ID as well,
	"formname" => $txtf->display("form"),
	"name" => $txtf->display("name"),
	"type" => $txtf->display("type"),
	"required" => $txtf->display("required"),
	"prio" => $txtf->display("prio")
);

/* required fields */
$required = array(
	"form",
	"name",
	"type"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

 	$what = array(
		"$table.*",
		"module_form2.title as formname"
	);
	$from = array(
		$table,
		"LEFT JOIN module_form2 ON $table.form = module_form2.id"
	);

	$where = "module_form2.language = '".addslashes($language)."'"; //$table.language = '$language'";

	$filter_fields = array(
		"$table.name",
		"$table.descr"
	);

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
	global $adm, $form, $show, $do, $txtf, $txt, $group, $language, $id, $structure, $question;
	$sq = new sql;

	$adm->assignProp("required", "type", "checkbox");
	$adm->assignProp("required", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

	if ($show == "add") {
		$sq->query($adm->dbc, "SELECT max(prio) as mm FROM module_form2_fields WHERE form = '".addslashes($form)."'");
		if ($sq->numrows == 0) {
			$adm->assign("prio", "5");
		}
		else {
			$data = $sq->nextrow();
			$adm->assign("prio", ($data["mm"]+5));
			$sq->free();
		}
	}

	$adm->assignProp("type", "type", "select");
	for ($c = 1; $c < 9; $c++) {
		$ar[$c] = $txtf->display("type".$c);
	}
	$adm->assignProp("type", "list", $ar);

	unset($ar);

/*	$adm->assignProp("fields1", "type", "select");

	for($c = 1; $c < 31; $c++) {
		$list[$c] = $c;
	}
	$adm->assignProp("fields1", "list", $list);		*/

	unset($list);

	$adm->assignProp("descr", "rows", "3");
	$adm->assignProp("descr", "cols", "60");

	$adm->assignProp("form", "type", "select");
	$adm->assignExternal("form", "module_form2", "id", "title", "WHERE language = '$language' AND active = 1 ORDER BY id DESC", false);

	$fdata = $adm->fields["form"];
//	$fdata["java"] = "onChange=\"this.form.submit()\"";
	$f = new AdminFields("form",$fdata);
	$form_select = $f->display($form);

	if ($form) {
		$adm->assignFilter("form", $form, "form = '".addslashes($form)."'", $form_select);
	}
	else {
		$test = $adm->fields["form"]["list"];
		if (is_array($test)) {
			list($key, $val) = each($test);
			if ($key) {
				$adm->assignFilter("form", $key, "form = '$key'", $form_select);
			}
		}
		else {
			$adm->assignFilter("form", "", "", $form_select);
		}
	}

	$adm->assignProp("prio", "extra", $txtf->display("prio_extra"));

	$adm->displayButtons("question");
	$adm->assignProp("question", "list", "");
	$adm->assignProp("question", "type", "select2");
	$adm->assignProp("question", "size", "5");
	//$adm->assignProp("question", "extra", "&nbsp;<a href=\"javascript:doPrompt('" . $txtf->display("question_add") . "', 'question')\"><img align=top src=\"pic/but0.php?t=" . $txtf->display("question_add") . "\" border=0 alt=\"\"></a>&nbsp;<a href=\"javascript:editItemInList('" . $txtf->display("question_change") . "', 'question')\"><img align=top src=\"pic/but0.php?t=" . $txtf->display("question_change") . "\" border=0 alt=\"\"></a>&nbsp;<a href=\"javascript:deleteSelectedItemsFromList('question')\"><img align=top src=\"pic/but0.php?t=" . $txtf->display("question_del") . "\" border=0 alt=\"\"></a>");

	$adm->assignProp("question", "extra", "<button type=button onClick=\"doPrompt('" . $txtf->display("question_add") . "', 'question')\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$txtf->display("question_add")."</button><br />
	<button type=button onClick=\"editItemInList('" . $txtf->display("question_change") . "', 'question')\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$txtf->display("question_change")."</button><br />
	<button type=button onClick=\"deleteSelectedItemsFromList('question')\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$txtf->display("question_del")."</button>");

	if ($show == "modify") {
		if ($adm->fields["options"]["value"] != "") {
			$ar = split(";;", $adm->fields["options"]["value"]);
			unset($list);
			for ($c = 0; $c < sizeof($ar); $c++) {
				$list[$ar[$c]] = $ar[$c];
			}
			$adm->assignProp("question", "list", $list);
		}
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
if (is_array($_POST["question"])) $_POST["options"] = $_POST["question"];

	$adm = new Admin($table);

	$sq = new sql;

	//$adm->assign("lastmod", date("Y-m-d H:i:s"));
	//$adm->assign("user", $user);
	$adm->assign("language", $language);

	/* DB writing part */
	if ($do == "add") {

		// permissions
		$perm->Access (0, 0, "a", "form2");

		if (is_array($question)) {
			$adm->fields["options"]["value"] = $question;
		}

		$res = $adm->add($table, $required, $idfield);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

			// clear cache
			clearCacheFiles("tpl_form2_".addslashes($adm->fields["form"]["value"])."_", "");

		}
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
		$perm->Access (0, $id, "m", "form2");

		if (is_array($question)) {
			$adm->fields["options"]["value"] = $question;
		}

		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");

			// clear cache
			clearCacheFiles("tpl_form2_".addslashes($adm->fields["form"]["value"])."_", "");

		}
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
		$perm->Access (0, $id, "d", "form2");

		$sq->query($adm->dbc, "SELECT form FROM module_form2_fields WHERE id = '".addslashes($id)."'");
		if ($sq->numrows > 0) {
			$form_id = $sq->column("0","form");
		}

		$res = $adm->delete($table, $idfield, $id);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");

			// clear cache
			clearCacheFiles("tpl_form2_".addslashes($form_id)."_", "");

		 }
		else {
			$result = $general["error"];
		}
	}
	/* end DB writing part */

	if ($show == "add") {

		// permissions
		$perm->Access (0, 0, "a", "form2");

		if ($copyto != "") 	$adm->fillValues($table, $idfield, $copyto);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
	}
	else if ($show == "modify" && $id) {

		// permissions
		$perm->Access (0, $id, "m", "form2");

		$adm->fillValues($table, $idfield, $id);
		$adm->types();
		external();
		$result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
	}
	else if (!$res || $res == 0) {
		// permissions
		$perm->Access (0, 0, "m", "form2");

		external();
		$result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
	}

if ($form) {
	$tpl->addDataItem("HIDDEN", "form=$form");
}
else {
	$tpl->addDataItem("HIDDEN", "form=".$adm->extra_filter["form"][0]);
}

if ($show == "add" || ($do == "add" && is_array($res))) {
	$tpl->addDataItem("TITLE", $txtf->display("module_title1"));
	$active_tab = 1;
}
else {
	$tpl->addDataItem("TITLE", $txtf->display("module_title1"));
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

echo $tpl->parse();
