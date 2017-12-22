<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
$show = "add";
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
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

// init systemlog object
$log = &SystemLog::instance($database);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "root", true);

// permissions
$perm->Access (0, 0, "m", "");

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_files");

// ##############################################################
// ##############################################################

$table = ""; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main.html",
    "template_form" => "tmpl/admin_form.html",
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
    "max_entries" => 30,
    "sort" => "" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "folder" => $txtf->display("folder"),
    "action" => $txtf->display("modifyfolder")
);

$tabs = array(
    1 => array($txtf->display("folderaction"), $_SERVER["PHP_SELF"]."?show=add")
//  2 => array($txtf->display("modifyfolder"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
//
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well
    "folder" => $txtf->display("folder")
);

/* required fields */
$required = array(
    "folder","action"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

//  $where = "$table.structure = '$structure' AND language = '$language'";

    $filter_fields = array(
    //
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function parse_folder($dir, $folder_list) {
    $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
        while ($file=@readdir($dh)){
            if ($file != "." && $file != ".." && @is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir."/".$file)) {
                if ($dir) $final = $dir."/".$file;
                else { $final = $file; }
                $folder_list[$final] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/")) . $file;
                $folder_list = parse_folder($final, $folder_list);
            }
        }
    return $folder_list;
}

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp("folder", "type", "select");

    $ar = array();
    $ar[""] = "-";
    $ar = parse_folder("", $ar);
    if (!is_array($ar)) $ar = array();
    $adm->assignProp("folder", "list", $ar);

    $adm->assignProp("action", "type", "select");
    $adm->assignProp("action", "extra", $txtf->display("folder_action_extra"));
    $adm->assignProp("action", "list", array("1" => $txtf->display("folder_action1"), "2" => $txtf->display("folder_action2"), "3" => $txtf->display("folder_action3")));
}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################

function delete_file($dir, $remove_root){
    $dh=@opendir($dir);
        while ($file=@readdir($dh)){
            if ($file!="." && $file!="..") {
                $fullpath=$dir."/".$file;
                if(!is_dir($fullpath)){
                    if (@unlink($fullpath)) {
                        // save log about this action
                        $GLOBALS['log']->log('file_manager', 'File '.substr($fullpath, strlen(SITE_PATH))
                            . ' deleted by ' . $GLOBALS['ses']->getUsername());
                    }
                } else {
                    $subdir=delete_file($fullpath, $remove_root);
                    if (!$subdir && @rmdir($fullpath)) {
                        // save log about this action
                        $GLOBALS['log']->log('file_manager', 'Folder '.substr($fullpath, strlen(SITE_PATH))
                            . ' deleted by ' . $GLOBALS['ses']->getUsername());
                    }
                }
            }
        }

        closedir($dh);

    if ($remove_root == true) {
        if(@rmdir($dir)){
            // save log about this action
            $GLOBALS['log']->log('file_manager', 'Folder ' . substr($dir, strlen(SITE_PATH))
                . ' deleted by ' . $GLOBALS['ses']->getUsername());
            return true;
        } else {
            return false;
        }
    }
    else {
        return true;
    }
}

// #####

function checkType1($file) {
    if (false === strpos(strtolower($file), '_thumb.') && (strtolower(substr($file, -3)) == "gif" || strtolower(substr($file, -3)) == "jpg" || strtolower(substr($file, -3)) == "png")) {
        return true;
    }
    else {
        return false;
    }
}

// #####

function generate_thumbs($folder) {
    //get files
    $opendir = addslashes($folder);
    if ($dir = @opendir($opendir)) {
        // files
        while (($file = @readdir($dir)) !== false) {
            if (@!is_dir($opendir . $file) && $file != "." && $file != ".." && checkType1($file) == true) {
                $file_thumb = $opendir . substr($file, 0, -4) . "_thumb." . substr($file, -3);
                @system(IMAGE_CONVERT . " -geometry 120x100 ".$opendir .$file." $file_thumb", $kala);

                if(file_exists($file_thumb)){
                    // save log about this action
                    $GLOBALS['log']->log('file_manager', 'Thumbnail of file '
                        . substr($opendir . $file, strlen(SITE_PATH))
                        . ' created by ' . $GLOBALS['ses']->getUsername());
                }
            }
        }
    }
}

// ##############################################################

if ($max_entries && $max_entries <= 100) { $general["max_entries"] = $max_entries; }

//if (!$show) $show = "add";

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

    $adm = new Admin($table);

    $sq = new sql;

    // #############

    $adm->assign("moddate", date("Y-m-d H:i:s"));
    $adm->assign("moduser", $user);

    $orig_folder = $folder;
    if ($folder != "") $folder .= "/";

    /* DB writing part */
    if ($do == "add") {

        if (false !== strpos($folder, '.')) exit();

        if ($action == 1 && $folder != "") {
            delete_file(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" .$folder, false);
            $sq = new sql;
            $sq->query($adm->dbc, "DELETE FROM files WHERE folder LIKE '".addslashes($orig_folder)."%'");
            //echo '<body onLoad= "document.location=\'files_folderaction.php?show=add\'">';
            //exit;
            $adm->db_write = true;
            $show = "add";
        }
        else if ($action == 2 && $folder != "") {
            delete_file(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" .$folder, true);
            $sq = new sql;
            $sq->query($adm->dbc, "DELETE FROM files WHERE folder LIKE '".addslashes($orig_folder)."%'");
            //echo '<body onLoad= "document.location=\'files_folderaction.php?show=add\'">';
            //exit;
            $adm->db_write = true;
            $show = "add";
        }
        else if ($action == 3 && $folder != "") {
            generate_thumbs(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" .$folder);
            echo '<body onload= "document.location=\'browser.php?folder='.urlencode($folder).'\'">';
            exit;
        }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }

    if ($show == "add") {
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    }



$tpl->addDataItem("TITLE", $txtf->display("folderaction"));
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

echo $tpl->parse();
