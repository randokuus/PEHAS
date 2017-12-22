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
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php"); 			// administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Filenames.php");

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

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "files_index");
$txta = new Text($language2, "admin_files");

// ##############################################################
// ##############################################################

$db = new DB;
$db->connect();
$sq = new sql;

if (false !== strpos($folder, '.')) exit();
if ($folder == "/") $folder = "";

$show_max = 500;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/browser_icons.html");

$path_parts = parse_url(SITE_URL);
$engine_url = $path_parts['path'];
if (substr($engine_url,0,1) != "/") $engine_url = "/" . $engine_url;
if (substr($engine_url,-1) != "/") $engine_url = $engine_url . "/";

// ###############################################################

if (!$start) $start = 0;
if (!$mode) $mode = "all";
if ($mode == "-") $mode = "all";

//$tpl->addDataItem("TITLE", $txtf->display("folder") . ": " . $GLOBALS["directory"]["upload"] . "/"  . $folder);
$tpl->addDataItem("TITLE", $txtf->display("info_choose_file") . ": " . $GLOBALS["directory"]["upload"] . "/"  . $folder);

$tpl->addDataItem("CONFIRMATION", $txt->display("delete_confirmation"));

$header_fields = array(
	"icon" => "&nbsp;",
	"name" => $txtf->display("info_name"),
	"size" => $txtf->display("info_size"),
	"date" => $txtf->display("info_date"),
	"text" => $txta->display("text"),
	"delete" => "&nbsp;"
);

$tabs = array(
	1 => array($txtf->display("view_detail"), "browser_selectfile.php"."?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($folder)."&sort=$sort&sort_type=$sort_type"),
	2 => array($txtf->display("view_icon"), "browser_icons_selectfile.php"."?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($folder)."&sort=$sort&sort_type=$sort_type"),
	3 => array($txtf->display("addnew"), "files_admin.php?show=add&folder=" . urlencode($folder)."&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder))
);

$active_tab = 2;

$nr = 1;
while(list($key, $val) = each($tabs)) {
	$tpl->addDataItem("TABS.ID", $nr);
	$tpl->addDataItem("TABS.URL", $val[1]);
	$tpl->addDataItem("TABS.NAME", $val[0]);
		if ($active_tab == $nr) {
			$tpl->addDataItem("TABS.CLASS", "class=\"active\"");
		}
		else {
			$tpl->addDataItem("TABS.CLASS", "class=\"\"");
		}
	$nr++;
}

// general text
$tpl->addDataItem("FILTER", $txt->display("filter"));
//$tpl->addDataItem("DISPLAY", $txt->display("display"));
$tpl->addDataItem("SUBMIT", $txt->display("filter"));

// max entries
/*$entries = array(10, 20, 30, 50,100);
for ($c = 0; $c < sizeof($entries); $c++) {
	$tpl->addDataItem("ENTRIES.VALUE", $entries[$c]);
	$tpl->addDataItem("ENTRIES.NAME", $entries[$c]);
	if ($max_entries == $entries[$c]) {
		$tpl->addDataItem("ENTRIES.SEL", "selected");
	}
	else {
		$tpl->addDataItem("ENTRIES.SEL", "");
	}
}*/

// file types
$mode_list = array("-", "all","pic","nopic","doc","pdf","xls","zip");
for ($c = 0; $c < sizeof($mode_list); $c++) {
	$tpl->addDataItem("MODES.VALUE", $mode_list[$c]);
	$tpl->addDataItem("MODES.NAME", $txtf->display("sel_" .  $mode_list[$c]));
	if ($mode == $mode_list[$c]) {
		$tpl->addDataItem("MODES.SEL", "selected");
	}
	else {
		$tpl->addDataItem("MODES.SEL", "");
	}
}

// extrafilter
//$tpl->addDataItem("EXTRAFILTER.ID", "ef1");
//$tpl->addDataItem("EXTRAFILTER.LABEL", $txtf->display("info_thumb"));
//$tpl->addDataItem("EXTRAFILTER.FIELD", "<input type=\"checkbox\" name=\"small_pic\" value=\"1\" checked>");
$tpl->addDataItem("SELECTTHUMB", $txtf->display("info_thumb"));

$tpl->addDataItem("VAL_SORT", $sort);
$tpl->addDataItem("VAL_SORT_TYPE", $sort_type);
$tpl->addDataItem("VAL_FILTER", $filter);
$tpl->addDataItem("VAL_FOLDER", $folder);

//$tpl->addDataItem("HIDDEN", $hidhid);


// #######################

function doFile ($nr, $id, $obj, $text, $type, $date, $folder) {
	global $tpl, $txtf, $engine_url;
	//if ($folder) $folder .= "/";

	if ($type == "gif" || $type == "jpg" || $type == "jpe" || $type == "png" || $type == "tif") {
		// THUMB Exists
		if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "_thumb." . $type)) {
			$icon = "<img src=\"".SITE_URL . "/".$GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "_thumb." . $type."\" align=absmiddle border=0 alt=\"$text\">";
		}
		else {
			$icon = "<img src=\"pic/ico_image.gif\" alt=\"$text\">";
		}
		$url = "javascript:openPicture('" . SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "." . $type . "');";
	}
	else {
		$url = "javascript:openFile('" . SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "." . $type . "');";
		if (file_exists(SITE_PATH . "/admin/pic/ico_" . strtolower($type) . ".gif")) {
			$icon = "<img src=\"pic/ico_".strtolower($type). ".gif\" alt=\"$text\">";
		}
		else {
			$icon = "<img src=\"pic/ico_other.gif\" alt=\"$text\">";
		}
	}
	if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "." . $type)) {
		$size = round(filesize(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $obj . "." . $type)/1000) . " kb";
	}
	else {
		$size = "? kb";
	}
	if ($type == "gif" || $type == "jpg" || $type == "jpe" || $type == "png") {
	    if (false !== strpos($obj, '_thumb')) {
			$url1 = $engine_url . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "." . $type;
		}
		else {
		if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "_thumb." . $type)) {
			$url1 = $engine_url . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "_thumb." . $type;
		}
		else {
			$url1 = "";
		}
		}
	}
	$url2 = urlencode($folder . $obj . "." . $type); // . "?" . $_SERVER["QUERY_STRING"]);

	if (strlen($obj) > 18) $f_name = substr($obj,0,18)."...".$type;
	else { $f_name = "$obj.$type"; }

	$tpl->addDataItem("ROWS.OBJ$nr", $icon);
	$tpl->addDataItem("ROWS.URL$nr", "javascript:selectFile('$id', '$obj', '$type', '$date', '$size', '" . $engine_url . $GLOBALS["directory"]["upload"] . "/"  . $folder . $obj . "." . $type . "', '$url1','$url2');");
	if ($text == "") $text = "&nbsp;";
	//$tpl->addDataItem("ROWS.TEXT$nr", $text);
	$tpl->addDataItem("ROWS.NAME$nr", $f_name);

}

// ###############################################
function doFold($nr, $fold, $fold_name) {
	global $tpl, $mode, $filter, $sort, $sort_type;
	global $folder;
	if ($fold_name == "..")  {
		//$fold = "";
		$icon = "<img src=\"pic/ico_folder-up.gif\" alt=\"$fold\">";
	}
	else {
		$icon = "<img src=\"pic/ico_folder-closed.gif\" alt=\"$fold\">";
	}

	if ($fold == "..") $fold = "";
	if ($folder && $fold && $fold_name != "..") $fold = $folder . $fold;
	if ($fold != "" && substr($fold, 0, -1) != "/") $fold .= "/";
	$url = $_SERVER["PHP_SELF"] . "?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($fold)."&sort=$sort&sort_type=$sort_type";

	$tpl->addDataItem("ROWS.URL$nr", $url);
	$tpl->addDataItem("ROWS.OBJ$nr", $icon);
	$tpl->addDataItem("ROWS.NAME$nr", $fold_name);
}
// ################################################

function getSize($file){
	$s=filesize($file);
	if($s>1024){
		$s=round($s/1024);
		return "$s Kb";
	}
	if($s>1024*1024){
		$s=round($s/(1024*1024));
		return "$s Mb";
	}
	return "$s b";
}

function getName($file) {
	return substr($file,0,strrpos($file, '.'));
}
function getTyp($file) {
	return substr($file,(strrpos($file, '.')+1),4);
}

function checkType($file) {
	global $mode, $folder;
	if (false !== strpos($file, '_thumb.') || (($file == "SITELOGO.gif" || $file == "SITELOGO.jpg") && $folder == "")) {
		return false;
	}
	else {
		if ($mode == "all") {
			return true;
		}
		else if ($mode == "pic") {
			if (getTyp($file) == "gif" || getTyp($file) == "jpg" || getTyp($file) == "jpe" || getTyp($file) == "png") {
				return true;
			}
			else {
				return false;
			}
		}
		else if ($mode == "nopic") {
			if (getTyp($file) != "gif" && getTyp($file) != "jpg" && getTyp($file) != "jpe" && getTyp($file) != "png") {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			if (getTyp($file) == addslashes($mode)) {
				return true;
			}
			else {
				return false;
			}
		}
	}
}

// #######################

// get folders

$start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/";
$start_folder1 = $GLOBALS["directory"]["upload"] . "/";
$start_url = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/";

// file descriptions
$sql = "SELECT id, name, CONCAT(name, \".\", type) as file, text, folder FROM files WHERE folder = '" . addslashes(substr($folder,0,-1)) . "' ORDER BY file ASC";
$sq->query($db->con, $sql);
while ($data = $sq->nextrow()) {
	$desc[$data["name"]] = array($data["id"],$data["text"]);
}

$opendir = $start_folder . addslashes($folder);
if ($dir = @opendir($opendir)) {
  $folders = array();
  while (($fldr = readdir($dir)) !== false) {
	  if (is_dir($opendir . $fldr) && $fldr != "." && $fldr != "..") {
		  $fold = $folder . $fldr . "/";
		  $folders[] = $fldr;
		  $fold = "";
	  }
  }
  sort($folders);
  reset($folders);
}

//if ($folder != "") {
//if (sizeof($folders) == 0 && $folder != "") {
if ($folder != "") {
	$fold = substr(substr($folder,0,-1), 0, strrpos(substr($folder,0,-1), '/'));
	doFold("", $fold, "..");
}

for ($c = 0; $c < sizeof($folders); $c++) {
	$fold1 = $folders[$c];
	$fold1_name = $fold1;
	if ($fold1) {doFold("", $fold1, $fold1_name); }
	else {  }

}

// #######################

$opendir = $start_folder . addslashes($folder);
if ($dir = @opendir($opendir)) {
  // files
  $files = array();
  while (($file = readdir($dir)) !== false) {
	  if (!is_dir($opendir . $file) && $file != "." && $file != ".." && checkType($file) == true && getTyp($file) != "php" && substr($file,0,1) != ".") {
	  		if ($filter != "") {
				if (false !== strpos(strtolower($file), addslashes(strtolower($filter)))) {
				  $files[] = $file;
			    }
			}
			else {
			  $files[] = $file;
			}
	  }
  }
  sort($files);
  reset($files);
 }

for ($c = 0; $c < sizeof($files); $c++) {

	$pathinfo = Filenames::pathinfo($files[$c]);
//	$obj1 = getName($files[$c]);
//	$type1 = getTyp($files[$c]);
	$obj1 = $pathinfo['filename'];
	$type1 = $pathinfo['extension'];

	$text1 = $desc[$files[$c]][1];
	$id1 = $desc[$files[$c]][0];
	$date1 = date ("d.m.y H:i", filemtime($opendir . $files[$c]));
	$folder1 = addslashes($folder);

	if ($obj1) {doFile("", $id1, $obj1, $text1, $type1, $date1, $folder1); }
	else {  }

}


echo $tpl->parse();
