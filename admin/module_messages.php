<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
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
$txtf = new Text($language2, "module_messages");

$path_parts = parse_url(SITE_URL);
$engine_url = $path_parts['path'];
if (substr($engine_url,0,1) != "/") $engine_url = "/" . $engine_url;
if (substr($engine_url,-1) != "/") $engine_url = $engine_url . "/";

// ##############################################################
// ##############################################################

$table = "module_messages"; // SQL table name to be administered

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
	"sort" => "entrydate DESC" // default sort to use
	//"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
	"entrydate" => $txtf->display("entrydate"),
	"title"  => $txtf->display("title"),
	"lead" => $txtf->display("lead"),
	"content" => $txtf->display("content"),
	"client" => $txtf->display("client")
//	"msgread" => $txtf->display("msgread")	
//	"pic" => $txtf->display("pic")
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
	"entrydate",
	"title",
	"lead",
	"content",
	"client"
//	"msgread"
//	"pic"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
	"listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//	$idfield => "ID", // if you want to display the ID as well,
	"entrydate" => $txtf->display("entrydate"),
	"title" => $txtf->display("title"),
	"lead" => $txtf->display("lead")
//	"msgread" => $txtf->display("msgread")
);

/* required fields */
$required = array(
	"entrydate",
	"title",
	"lead"
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
		"$table.entrydate",		
		"$table.title",
		"$table.content"
	);
 
 /* end display list part */
 
// If for example our table has references to another table (foreign key)
 
function external() {
	global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
	//$sq = new sql; 
	
	if ($show == "add") {
		$adm->assign("entrydate", date("Y-m-d H:i:s"));
	}
	
	$adm->assignProp("content", "type", "nothing");	
	$adm->assignProp("lead", "rows", "3");		
	$adm->assignProp("lead", "cols", "60");			
	$adm->displayOnly("content");
//	$adm->assign("content", "<iframe id=\"contentFreim\" src=\"editor/editor.php?id=$id&type=messages\" WIDTH=100% HEIGHT=350>
//	</iframe>");	
	$adm->assign("content", "<iframe id=\"contentFreim\" name=\"contentFreim\" src=\"editor/editor.php?id=$id&type=messages&rnd=".randomNumber()."\" WIDTH=100% HEIGHT=350 marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" frameborder=\"0\">
	</iframe>");	
	
	$adm->assignProp("client", "type", "select2");	
	$adm->assignProp("client", "size", "5");		
	$adm->assignExternal("client", "module_user_users", "user", "name", "WHERE active = 1", true);		

	$adm->assignProp("msgread", "type", "checkbox");	
	$adm->assignProp("msgread", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

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

	/*	$_POST["content"] = preg_replace("/<\/?(HTML|HEAD|TITLE|BODY)>\n?/", "", $_POST["content"]);
		$_POST["content"] = preg_replace("/<\/?(html|head|title|body)>\n?/", "", $_POST["content"]);		
		$_POST["content"] = preg_replace("/<META[^>]*>\n?/", "", $_POST["content"]);	
		$_POST["content"] = preg_replace("/<meta[^>]*>\n?/", "", $_POST["content"]);			
		$_POST["content"] = preg_replace("/<link rel[^>]*>\n?/", "", $_POST["content"]);					
		$_POST["content"] = preg_replace("/<BODY[^>]*>\n?/", "", $_POST["content"]);					
		$_POST["content"] = preg_replace("/<body[^>]*>\n?/", "", $_POST["content"]);									
		$_POST["content"] = preg_replace("/<!DOCTYPE[^>]*>\n?/", "", $_POST["content"]);		
		//$_POST["content"] = preg_replace("/'/", "&lsquo;", $_POST["content"]);						
		$_POST["content"] = preg_replace("/\\\\'/m","'", $_POST["content"]);	
		$_POST["content"] = trim($_POST["content"]);		*/
		
		$_POST["content"]=stripslashes($_POST["content"]);//remove slashes (/)			
		$_POST["content"] = str_replace("src=\"../../","src=\"".$engine_url, $_POST["content"]);			
		$_POST["content"] = str_replace("href=\"../../","href=\"".$engine_url, $_POST["content"]);			
		$_POST["content"] = str_replace("src=\"".SITE_URL."/","src=\"".$engine_url, $_POST["content"]);					
		$_POST["content"] = str_replace("href=\"".SITE_URL."/","href=\"".$engine_url, $_POST["content"]);							
		


	$adm = new Admin($table);
	
	$sq = new sql;
	
	//$adm->assign("lastmod", date("Y-m-d H:i:s"));			
	//$adm->assign("user", $user);				
	$adm->assign("language", $language);					
	
	/* DB writing part */
	if ($do == "add") {
	
		// permissions	
		$perm->Access (0, 0, "a", "messages");	
	
		$res = $adm->add($table, $required, $idfield);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
			
			// clear cache
			clearCacheFiles("tpl_messages", "");					
		
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
		$perm->Access (0, $id, "m", "messages");	
	
		$res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
			
			// clear cache
			clearCacheFiles("tpl_messages", "");					
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
		$perm->Access (0, $id, "d", "messages");	
	
		$res = $adm->delete($table, $idfield, $id);
		if ($res == 0) {
			$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
			
			// clear cache
			clearCacheFiles("tpl_messages", "");					
			
		}
		else { $result = $general["error"]; }
	}
	/* end DB writing part */
	
	if ($show == "add") {
	
		// permissions	
		$perm->Access (0, 0, "a", "messages");	
	
		if ($copyto != "") 	$adm->fillValues($table, $idfield, $copyto);
		$adm->types();	
		external();
		$result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
	}	
	else if ($show == "modify" && $id) {
	
		// permissions	
		$perm->Access (0, $id, "m", "messages");	
	
		$adm->fillValues($table, $idfield, $id);
		$adm->types();		
		external();
		$result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
	}
	else if (!$res || $res == 0) {	
		// permissions	
		$perm->Access (0, 0, "m", "messages");	
			
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