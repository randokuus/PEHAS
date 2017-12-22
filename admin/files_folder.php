<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
foreach (array('topfolder','folder','old_folder') as $_f) {
    if (isset($_GET[$_f]) && false !== strpos($_GET[$_f], '..')) exit;
    if (isset($_POST[$_f]) && false !== strpos($_POST[$_f], '..')) exit;
}
require_once("../class/config.php");
require_once("../class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin2.class.php");            // administration main object
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
    "template_main" => "tmpl/admin_main_module.html",
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
    "max_entries" => 100,
    "sort" => "" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "topfolder" => $txtf->display("topfolder"),
    "folder" => $txtf->display("folder")
);

$tabs = array(
    1 => array($txtf->display("addfolder"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txtf->display("modifyfolder"), "files_folder_list.php")
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    //$idfield => "ID", // if you want to display the ID as well,
    "folder" => $txtf->display("folder")
);

/* required fields */
$required = array(
//  "design",
    "folder"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
    );
    $from = array(
    );

    //$where = "language = '$language'";

    $filter_fields = array(
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function parse_folder($dir, $folder_list) {
    $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
        while ($file=@readdir($dh)){
            if ($file != "." && $file != ".." && is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir."/".$file)) {
                if ($dir) $final = $dir."/".$file;
                else { $final = $file; }
                $folder_list[$final] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/")) . $file;
                $folder_list = parse_folder($final, $folder_list);
            }
        }
    return $folder_list;
}

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $sel_language, $id, $file, $structure, $sel_template;
    //$sq = new sql;

    if (isset($_GET["folder"])) $folder = $_GET["folder"];
    if (isset($_POST["folder"])) $folder = $_POST["folder"];
    if (substr($folder, -1) == "/") $folderx = substr($folder, 0, -1);
    else { $folderx = $folder; }

    $topfolder = substr($folderx, 0, strrpos($folderx, "/"));
    if ($topfolder) {
        $folderx = substr($folderx, strrpos($folderx, "/")+1);
    }
    else {
        $folderx = substr($folderx, strrpos($folderx, "/"));
    }

    $adm->assign("folder", $folderx);

    $adm->assignHidden("returnto", $_GET["returnto"]);
    if ($_POST["returnto"]) {
        $adm->assignHidden("returnto", $_POST["returnto"]);
    }

    if ($show == "modify") {
        $adm->assignHidden("old_folder", $folder);
        $adm->displayOnly("topfolder");
        $adm->assign("topfolder", $topfolder);

    }
    else {
        $adm->assignProp("topfolder", "type", "select");
        $ar = array();
        $ar[""] = "-";
        $ar = parse_folder("", $ar);
        if (!is_array($ar)) $ar = array();
        $adm->assignProp("topfolder", "list", $ar);
        $adm->assign("topfolder", $topfolder);
    }

    $adm->assignProp("folder", "type", "textinput");
    $adm->assignProp("folder", "size", "30");

}

function delete_file($dir){
    $dh=@opendir($dir);
        while ($file=@readdir($dh)){
                if($file!="." && $file!=".."){
                    $fullpath=$dir."/".$file;

                        if(!is_dir($fullpath)){
                    if (@unlink($fullpath)) {
                        // save log about this action
                        $GLOBALS['log']->log('file_manager', "File " . substr($fullpath, strlen(SITE_PATH))
                            . "deleted by " . $GLOBALS['ses']->getUsername());

                        }
                } else {
                            $subdir=delete_file($fullpath);
                    if (!$subdir && @rmdir($fullpath)) {
                        // save log about this action
                        $GLOBALS['log']->log('file_manager', 'Folder ' . substr($fullpath, strlen(SITE_PATH))
                            . 'deleted by ' . $GLOBALS['ses']->getUsername());
                            }
                        }
                }
        }

        closedir($dh);

    if(@rmdir($dir)){
        // save log about this action
        $GLOBALS['log']->log('file_manager', 'Folder ' . substr($dir, strlen(SITE_PATH))
            . 'deleted by ' . $GLOBALS['ses']->getUsername());
        return true;
    } else {
        return false;
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

    $adm = new Admin2($table);

    $sq = new sql;

    /* DB writing part */
    if ($do == "add") {
        $res = 0;
        $_POST["folder"] = preg_replace('/ /', '_', $_POST["folder"]);
        $_POST["folder"] = ereg_replace("[^[:space:]a-zA-Z0-9*_-]", "", $_POST["folder"]);
        if ($_POST["folder"] == "") {
            $res = 99;
        }

        $_create_folder = "/{$GLOBALS['directory']['upload']}/{$_POST['topfolder']}/{$_POST['folder']}";
        if ($_POST["folder"] != "" && @!file_exists(SITE_PATH . $_create_folder) && !is_dir($_create_folder)) {
            if (!@mkdir(SITE_PATH . $_create_folder, 0777)) {
                trigger_error("Create folder failed. Check parent folder permissions", E_USER_ERROR);
                exit;
            } else {
                // save log about this action
                $log->log('file_manager', "Folder $_create_folder created by "
                    . $GLOBALS['ses']->getUsername());
            }
        }

        if ($res == 0) {
            if ($returnto != "") {
                redirect($returnto);
                exit;
            }
            else {
                redirect("admin/files_folder_list.php");
                exit;
            }
        }
        else {
            //$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->general["other_error"] = $general["required_error"];
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
        }

    }
    else if ($do == "update" && $folder) {
        $res = 0;
        $_POST["folder"] = preg_replace('/ /', '_', $_POST["folder"]);
        $_POST["folder"] = ereg_replace("[^[:space:]a-zA-Z0-9*_-]", "", $_POST["folder"]);
        if ($_POST["folder"] == "") {
            $res = 99;
        }

        // rename
        // old folder name
        $_rf_old_folder = "/{$GLOBALS['directory']['upload']}/{$_POST['old_folder']}";
        // new folder name
        $_rm_new_folder = "/{$GLOBALS['directory']['upload']}/{$_POST['topfolder']}{$_POST['folder']}";

        if ($_POST["folder"] != "" && @file_exists(SITE_PATH . $_rm_old_folder)
            && @is_dir(SITE_PATH . $_rm_old_folder))
        {

            if ($_POST["topfolder"] != "" && substr($_POST["topfolder"], 0, -1) != "/") $_POST["topfolder"] .= "/";

            $ac_status = @rename(SITE_PATH . $_rm_old_folder, SITE_PATH . $_rm_new_folder);
            if (!$ac_status) {
                trigger_error("Folder rename on '$_rm_old_folder' failed. Check folder permissions", E_USER_ERROR);
                exit;
            } else {
                // save log about this action
                $log->log('file_manager', "Folder $_rm_old_folder renamed by "
                    . $GLOBALS['ses']->getUsername() . " to $_rm_new_folder");
            }
        }
        unset($_rm_old_folder, $_rm_new_folder);

        if ($res == 0) {
            if ($returnto != "") {
                redirect($returnto);
                exit;
            }
            else {
                redirect("admin/files_folder_list.php");
                exit;
            }
        }


        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\""); }
        else {
            //$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->general["other_error"] = $general["required_error"];
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "delete" && $folder) {

        $status = delete_file(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder);

        if ($status == false) {
            trigger_error("Folder delete failed. Check folder and files contained within permissions.", E_USER_ERROR);
            exit;
        }
        else {
            if ($returnto != "") {
                redirect($returnto);
                exit;
            }
            else {
                redirect("admin/files_folder_list.php");
                exit;
            }
        }

    }
    /* end DB writing part */

    if ($show == "add") {
        //if ($copyto != "")    $adm->fillValues($table, $idfield, $copyto);
        //$adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $folder) {
        //$adm->fillValues($table, $idfield, $id);
        //$adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
    }

if ($show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->display("addfolder"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->display("modifyfolder"));
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
