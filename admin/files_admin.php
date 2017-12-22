<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

@set_time_limit (0);
//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . '/class/FileUploader.php');
require_once(SITE_PATH . '/class/Filenames.php');
require_once(SITE_PATH . '/class/FileBrowser.php');

// ##############################################################

// init session object
if ($do == 'savefile' && isset($_GET['ADM_SESS_SID']) && isset($_GET['ADM_LANG_SID'])) {
    $_COOKIE['ADM_SESS_SID'] = $_GET['ADM_SESS_SID'];
    $_COOKIE['ADM_LANG_SID'] = $_GET['ADM_LANG_SID'];
}

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

//design files only to Root users
if ($access) {
    $perm = new Rights($group, $user, "root", true);
    $perm->Access (0, 0, "m", "");
}
else {
    $perm = new Rights($group, $user, "module", true);
}

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_files");

if (false !== strpos($folder, '.')) exit();

// ##############################################################
// ##############################################################

$table = "files"; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main.html",
    "template_form" => $show == "add" ? "tmpl/admin_form_files.html" : "tmpl/admin_form.html",
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
    "sort" => "name ASC", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
if ($show == "add") {
    $fields = array(
    "folder" => $txtf->display("folder"),
    "owner" => $txtf->display("owner"),
    "keywords" => $txtf->display("keywords"),
    "add_date" => $txtf->display("add_date"),
    "lastmod" => $txtf->display("lastmod"),
    "permissions" => $txtf->display("permissions"),
    "size_head" => $txtf->display("size_head"),
    "size_thumb" => $txtf->display("size_thumb"),
    "size_big" => $txtf->display("size_big"),
    "size_only" => $txtf->display("size_only"),
    "size_custom_x" => $txtf->display("size_custom_x"),
    "size_custom_y" => $txtf->display("size_custom_y"),
    );
}
/*elseif($show == "addperm") {
    $fields = array(
    "perm_groups" => $txtf->display("perm_groups"),
    "perm_group" => $txtf->display("perm_group"),
    "perm_other" => $txtf->display("perm_other"),
    "owner" => $txtf->display("perm_owner")
    );
}*/
else {
    $fields = array(
    "text" => $txtf->display("text"),
    "filedata" => $txtf->display("file"),
    "folder" => $txtf->display("folder"),
    "owner" => $txtf->display("owner"),
    "keywords" => $txtf->display("keywords"),
    "add_date" => $txtf->display("add_date"),
    "lastmod" => $txtf->display("lastmod"),
    "permissions" => $txtf->display("permissions"),
    "size_head" => $txtf->display("size_head"),
    "size_thumb" => $txtf->display("size_thumb"),
    "size_big" => $txtf->display("size_big"),
    "size_only" => $txtf->display("size_only"),
    "size_custom_x" => $txtf->display("size_custom_x"),
    "size_custom_y" => $txtf->display("size_custom_y")
    );
}
/*$sql = "SELECT owner FROM `files` WHERE `id`=?;";
$perms= $database->fetch_all($sql, $id);*/

if ($access == "img" || $access == "tmpl") {
    $tabs = array(
    1 => array($txtf->display("add"), "#"),
    2 => array($txtf->display("modify"), "#")
 //   3 => array($txtf->display("add_perm"), "#" )
    );
}
else {
    /*if ($perms[0]['owner'] == $user) {
        $tabs = array(
        1 => array($txtf->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
        2 => array($txtf->display("modify"), $_SERVER["PHP_SELF"]),
        3 => array($txtf->display("add_perm"), $_SERVER["PHP_SELF"]."?show=addperm&id=" . $id )
        );
    } else {*/
        $tabs = array(
        1 => array($txtf->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
           2 => array($txtf->display("modify"), $_SERVER["PHP_SELF"])
        );
    //}

}

$field_groups = array(
1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
/*$upd_fields = array(
//  "name",
//  "type",
    "text",
    //"owner",
    "lastmod",
    "folder"
);*/
/* the fields that we want to update (do not include primary key (id) here) */
if (isset($access) and $access) {
    $upd_fields = array(
    //  "name",
    //  "type",
        "text",
        "owner",
        "keywords",
        "add_date",
        "lastmod",
        "permissions",
        "folder"
        /*"groups",
        "perm_group",
        "perm_other*/
    );
}else {
    /*if ($perms[0]['owner'] == $user) {
        if($show == "addperm") {
            $upd_fields = array(
            "groups",
            "perm_group",
            "perm_other",
            "owner"
            );
        }*/
        if ($show == "modify") {
            $upd_fields = array(
                "text",
                "lastmod",
                "folder"
            );
        }
    /*} else {
      $upd_fields = array(
        "text",
        "lastmod",
        "folder"
        );
    }*/
}

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well
    "type" => $txtf->display("type"),
    "name" => $txtf->display("name"),
    "text" => $txtf->display("text"),
	"owner" => $txtf->display("owner")
);

/* required fields */
$required = array(
//  "text"
);

/* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

$what = array(
    "$table.*",
);

$from = array(
    $table,
);

//  $where = "$table.structure = '$structure' AND language = '$language'";

$filter_fields = array(
    "$table.type",
    "$table.name",
    "$table.text",
    "$table.folder"
);

/* end display list part */

// If for example our table has references to another table (foreign key)

function parse_folder($rootfolder, $dir, $folder_list) {
    $dh=@opendir(SITE_PATH . "/" . $rootfolder . "/" . $dir);
    while ($file=@readdir($dh)){
        if ($file != "." && $file != ".." && is_dir(SITE_PATH . "/" . $rootfolder . "/" . $dir."/".$file)) {
            if ($dir) $final = $dir . "/" . $file ;
            else { $final = $file ; }
            $folder_list['/'.$final. '/'] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/")) . $file;
            $folder_list = parse_folder($rootfolder, $final, $folder_list);
        }
    }
    return $folder_list;
}

function parse_folder2($rootfolder, $dir, $folder_list) {
    $dh=@opendir(SITE_PATH . "/" . $rootfolder . "/" . $dir);
    while ($file=@readdir($dh)){
        if ($file != "." && $file != ".." && is_dir(SITE_PATH . "/" . $rootfolder . "/" . $dir."/".$file)) {
            if ($dir) $final = $dir."/".$file;
            else { $final = $file; }
            $folder_list['/'.$rootfolder . "/" . $final] = $rootfolder . "/" . $file;
            $folder_list = parse_folder2($rootfolder, $final, $folder_list);
        }
    }
    return $folder_list;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

// ########################################
// Create permission string
function encodePermissions($read, $write, $delete) {
    $perm = array();
    $perm_list = "";
    if (is_array($read)) {
        while (list($key, $val) = each($read)) {
            if ($val) {
                $perm[$key]["r"] = 1;
            }
        }
    }
    if (is_array($write)) {
        while (list($key, $val) = each($write)) {
            if ($val) {
                $perm[$key]["w"] = 1;
            }
        }
    }
    if (is_array($delete)) {
        while (list($key, $val) = each($delete)) {
            if ($val) {
                $perm[$key]["d"] = 1;
            }
        }
    }
    // creating encoded list from permission array
    while (list($key, $val) = each($perm)) {
        if ($perm_list) {
            $perm_list .= ";";
        }
        $perm_list .= $key;
        $perm_list .= ":" . ($val["r"] ? "1" : "0");
        $perm_list .= "," . ($val["w"] ? "1" : "0");
        $perm_list .= "," . ($val["d"] ? "1" : "0");
    }
    return $perm_list;
}

// ########################################
// decodes permission string into array
function decodePermissions($perm_str) {
    $tmp_perm = explode(";", $perm_str);
    $perm = array();

    for ($i = 0; $i < sizeof($tmp_perm); $i++) {
        $tmp_gp = explode(":", $tmp_perm[$i]);
        $tmp_p = explode(",", $tmp_gp[1]);
        $perm[$tmp_gp[0]]["r"] = $tmp_p[0];
        $perm[$tmp_gp[0]]["w"] = $tmp_p[1];
        $perm[$tmp_gp[0]]["d"] = $tmp_p[2];
    }

    return $perm;
}

// ########################################
// returns array of all permissions
function getAllPermissions($r, $w, $d) {
    global $adm;
    $sq = new sql;

    $sql = "SELECT id FROM module_user_groups";
    $sq->query($adm->dbc, $sql);
    while ($data = $sq->nextrow()) {
        $read[$data["id"]] = $r;
        $write[$data["id"]] = $w;
        $delete[$data["id"]] = $d;
    }

    return array($read, $write, $delete);
}

function file_permissions($file_perm) {
    global $adm, $txtf;
    $sq = new sql;

    $perm_list = "<table border=1 cellpadding=0 cellspacing=0>\n";
    $perm_list .= "<tr>\n";
    $perm_list .= "<th><label for=\"action\" class=\"left\">" . $txtf->display("group") . "</label></th>\n";
    $perm_list .= "<th><label for=\"action\" class=\"left\">" . $txtf->display("read") . "</label></th>\n";
    $perm_list .= "<th><label for=\"action\" class=\"left\">" . $txtf->display("write") . "</label></th>\n";
    $perm_list .= "<th><label for=\"action\" class=\"left\">" . $txtf->display("delete") . "</label></th>\n";
    $perm_list .= "</tr>\n";
    $perm_array = decodePermissions($file_perm);

    $sql = "SELECT * FROM module_user_groups ORDER BY module_user_groups.name";

    $sq->query($adm->dbc, $sql);
    while ($gdata = $sq->nextrow()) {
        $perm_list .= "<tr>\n";
        $perm_list .= "<td><label for=\"action\" class=\"left\">" . $gdata["name"] . "&nbsp;</label></td>\n";
        $perm_list .= "<td align=\"center\"><input type=\"checkbox\" name=\"group_read[" . $gdata["id"] . "]\" " . ($perm_array[$gdata["id"]]["r"] ? "checked" : "") . "></td>\n";
        $perm_list .= "<td align=\"center\"><input type=\"checkbox\" name=\"group_write[" . $gdata["id"] . "]\" " . ($perm_array[$gdata["id"]]["w"] ? "checked" : "") . "></td>\n";
        $perm_list .= "<td align=\"center\"><input type=\"checkbox\" name=\"group_delete[" . $gdata["id"] . "]\" " . ($perm_array[$gdata["id"]]["d"] ? "checked" : "") . "></td>\n";
        $perm_list .= "</tr>\n";
    }

    $perm_list .= "</table>\n";
    return $perm_list;
}

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $returnto, $file, $type, $access, $fields, $folder;
    $sq = new sql;

    $adm->assignHidden("max_size", return_bytes(ini_get("upload_max_filesize")));
    $adm->assignHidden("form_id", rand(0, 99999999));

    $adm->assignProp("text", "size", "30");

    $adm->assignProp("filedata", "type", "file");

    if ($access != "tmpl" && $access != "img") {
        $adm->assignProp("filedata", "extra", "&nbsp;" . $txtf->display("file_extra"));
    }

    $size_thumb = array();
    $size_picture = array();
    $sq->query($adm->dbc, "SELECT size, name FROM files_imagesizes ORDER BY id ASC");
    while ($data = $sq->nextrow()) {
        $size_thumb[$data["size"]] = $data["name"];
        $size_picture[$data["size"]] = $data["name"];
    }
    $sq->free();

    if (sizeof($size_thumb) == 0) {
        $size_thumb = array("80x80" => "80x80", "100x80" => "100x80", "120x100" => "120x100", "140x105" => "140x105", "200x150" => "200x150");
    }

    if (sizeof($size_picture) == 0) {
        $size_picture = array("400x300" => "400x300", "640x480" => "640x480", "800x600" => "800x600");
    }

   /* $adm->assignProp("owner", "type", "select");
    $adm->assignExternal("owner", "`adm_user` LEFT JOIN `adm_group` ON `adm_user`.
                    `ggroup` = `adm_group`.`ggroup`", "`adm_user`.`user`",
                     "concat(`adm_user`.`username`, ' (', `adm_group`.`name`,')')",
                    "ORDER BY `adm_user`.`ggroup` ASC, `adm_user`.`username` ASC", false);
    $adm->assignProp("perm_groups", "type", "select2");
    $adm->assignExternal("perm_groups", "adm_group", "ggroup", "name", "", false);
    //$adm->assignProp("modify_group", "type", "checkbox");
    //$adm->assignProp("delete_group", "type", "checkbox");
     $adm->assignProp("perm_group", "type", "checkboxp");
    $adm->assignProp("perm_group", "display", "none");
    $adm->assignProp("perm_group", "list", array("1" => $txtf->display("modify_group"), "2" => $txtf->display("delete_group")));
    $adm->assignProp("perm_other", "type", "checkboxp");
    $adm->assignProp("perm_other", "display", "none");
    $adm->assignProp("perm_other", "list", array("1" => $txtf->display("modify_others"), "2" => $txtf->display("delete_others")));*/



    $adm->assignProp("size_custom_x", "type", "textinput");
    $adm->assignProp("size_custom_y", "type", "textinput");
    $adm->assignProp("size_custom_x", "size", "5");
    $adm->assignProp("size_custom_y", "size", "5");

    // predefined checkbox element. Dont scale image if it's smaller then selected size.
    $_pce = "<input type='checkbox' name='%s' value='1' /> " . $txtf->display('dont_scale');

    $adm->assignProp("size_thumb", "type", "select");
    $adm->assignProp("size_thumb", "list", $size_thumb);
    $adm->assignProp("size_thumb", "extra", sprintf($_pce, 'size_thumb_dontscale'));

    $adm->assignProp("size_big", "type", "select");
    $adm->assignProp("size_big", "list", array_merge($size_picture, array("nosize" => $txtf->display("size_no"))));
    $adm->assignProp("size_big", "extra", sprintf($_pce, 'size_big_dontscale'));

    $adm->assignProp("size_only", "type", "checkbox");

    $adm->assign("size_thumb", "120x100");
    $adm->assign("size_big", "640x480");

    if ($show == "modify") {
        $adm->displayOnly("folder");
    }
    else {
        $adm->assignProp("folder", "type", "select");
        $adm->assign('folder', $folder);

        $ar = array();
        if ($access == "img" || $access == "tmpl") {
            $ar["img"] = "img";
            $ar["tmpl"] = "tmpl";
            $ar = parse_folder2($GLOBALS["directory"]["img"], "", $ar);
            $ar = parse_folder2($GLOBALS["directory"]["tmpl"], "", $ar);
        }
        else {
            $ar["/"] = " - ";
            $ar = FileBrowser::getStaticFolders("", $ar);
        }
        $adm->assignProp("folder", "list", $ar);
    }

    if ($returnto != "") {
        $adm->assignHidden("returnto", $returnto);
    }
    if ($file != "") {
        $adm->assignHidden("file", addslashes($file));
    }
    if ($access != "") {
        $adm->assignHidden("access", addslashes($access));
    }
    if ($_GET['script'] != "") {
        $adm->assignHidden("script", addslashes($_GET['script']));
    }

    //  $adm->assignProp("size_head", "type", "extern");

    $list = array();
    $list[""] = "---";
    $sql = "SELECT user, username, name FROM adm_user ORDER BY name";
    $sq->query($adm->dbc, $sql);
    while ($data = $sq->nextrow()) {
        $list[$data["user"]] = "ADM: " . $data["name"] . " (" . $data["username"] . ")";
    }

//    $sql = "SELECT user, username, name FROM module_user_users ORDER BY name";
//    $sq->query($adm->dbc, $sql);
//    while ($data = $sq->nextrow()) {
//        $list["99" . $data["user"]] = $data["name"] . " (" . $data["username"] . ")";
//    }

    $adm->assignProp("owner", "type", "select");
    $adm->assignProp("owner", "list", $list);
//  $adm->assignExternal("owner", "module_user_users", "CONCAT('99', user) AS user", "CONCAT(name, ' (', username, ')') AS name", " ORDER BY name", true);

    $adm->displayOnly("add_date");
    $adm->displayOnly("lastmod");

//    if ($show == "add") {
//        $t_perm = getAllPermissions(1, 0, 0);
//        $adm->fields["permissions"]["value"] = encodePermissions($t_perm[0], $t_perm[1], $t_perm[2]);
//    }
//    $adm->assignProp("permissions", "type", "onlyhidden");
//    $adm->assignProp("permissions", "extra", file_permissions($adm->fields["permissions"]["value"]));

    // #############

    $adm->assignProp("type", "type", "select");
    $adm->assignProp("type", "list", array("" => "-", "jpg" => "jpg","gif" => "gif", "png" => "png", "doc" => "doc", "xls" => "xls", "pdf" => "pdf", "zip" => "zip", "txt" => "txt"));

    $fdata = $adm->fields["type"];
    $fdata["java"] = "onChange=\"this.form.submit()\"";
    $f = new AdminFields("type",$fdata);
    $type_select = $f->display($type);

    if ($type) {
        $adm->assignFilter("type", $type, "type = '".addslashes($type)."'", $type_select);
    }
    else{
        $adm->assignFilter("type", "", "", $type_select);
    }

    //$adm->assignHidden("type", $type);

}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 100) { $general["max_entries"] = $max_entries; }

if ($_GET["folder"] != "") {
    if (substr($_GET["folder"], -1) == "/") {
        $_POST["folder"] = substr($_GET["folder"], 0, -1);
    }
    else {
        $_POST["folder"] = $_GET["folder"];
    }
}

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

$adm = new Admin($table);

$sq = new sql;

// #############

$adm->assign("moddate", date("Y-m-d H:i:s"));
$adm->assign("moduser", $user);

//$folder .= "/";
if ($do == 'add' || $do == 'update' || $do == 'savefile'){
//if ($do == 'add' || $do == 'update' || $do == 'savefile' || $do == 'update_perm'){
    if ($do == 'savefile') {
        $_FILES["filedata"] = $_FILES["Filedata"];
        $_POST = $_SESSION["upload"][$_GET['form_id']];
        $_POST["text"] = $_REQUEST["text"] = $_POST["description"][$_GET['file']];
//        $fields["text"] = $txtf->display("text");
        $adm->assign("text", $_POST["text"]);
        foreach ($_POST as $k => $v) $$k = $v;
        unset($id);
        unset($file);
    }

    $id = (int)$id;

    // if $id is not set and file then retrive $id from database.
    if (!$id && $file){
        $folder   = dirname($file);
        $filename = basename($file);

        if ($folder == '.') $folder = '';

        $sql = "SELECT id FROM `files` WHERE `folder`=? AND `name`=?;";
        $id = $database->fetch_first_value($sql, $folder, $filename);
        if (!$id) $id = 0;

        // file not exists in DB, then we must add it into DB
        // @todo:....
    }

    // Check persmissions
    if ($do == 'add' || $do == 'savefile') $perm->Access (0, 0, "a", "fileaccess");
    if ($do == 'update') $perm->Access (0, $id, "m", "fileaccess");


    /**
     * get all data from $_POST $_FILES
     */
    $filedata_name= $_FILES["filedata"]["name"];
    $filedata_size= $_FILES["filedata"]["size"];
    $filedata     = $_FILES["filedata"]["tmp_name"];
    $size_thumb   = $_POST["size_thumb"];
    $size_big     = $_POST["size_big"];
    $size_only    = $_POST["size_only"];
    $folder       = $_POST["folder"];

    // set default upload action to 'rename'
    $upload_action = 'rename';


    // Set `rootfolder` and `folder`.
    if ($access == "img" || $access == "tmpl"
    || (isset($GLOBALS["directory"][$access]) && $GLOBALS["directory"][$access]))
    {
        $rootfolder = $GLOBALS["directory"][$access];
    } else {
        $rootfolder = $GLOBALS["directory"]["upload"];
    }

    if ( ($do=='add' || $do == 'savefile') && ($access == "img" || $access == "tmpl"))
    {
        if (substr($folder, 0, 3) == "img") {
            $rootfolder = $GLOBALS["directory"]["img"];
            $folder = substr($folder, 4);
        }
        else if (substr($folder, 0, 4) == "tmpl") {
            $rootfolder = $GLOBALS['directory']['tmpl'];
            $folder = substr($folder, 5);
        }
        elseif ($folder != '') {
            $rootfolder = $GLOBALS['directory']['upload'];
            $folder = '';
        }
    }

    // Check, if `folder` ends with `"/"`...
    if ($folder == "/" || $folder == "") {
        $folder = "/";
    } elseif (substr($folder,0,-1) != "/") {
        $folder .= "/";
    }

    //If new file was uploaded, process it with FileUpload class...
    if ($filedata_name && $filedata_size) {
        // if we have deal with existed file, then we must do something with it...
        // Change destination filename and change upload_action to 'replace'
        if ($file && false === strpos($file, '..')) {
            $_pathinfo = Filenames::pathinfo($file);
            $folder = $_pathinfo['dirname'];
            if ($folder == '.') {
                $folder = '';
            } else{
               // $folder .= "/";
            }
            $filedata_name = $_pathinfo['basename'];
            $upload_action = 'replace';
        }

        $filedata_name= preg_replace('/ /', '_', $filedata_name);
        $filedata_name= ereg_replace("[^[:space:]a-zA-Z0-9*_.-]", "", $filedata_name);

        $upd_fields[] = "name";
        $upd_fields[] = "type";
        $upd_fields[] = "owner";

        // Get path info about file
        $pathinfo = Filenames::pathinfo($filedata_name);
        $filename = $pathinfo['filename'];
        $filetype = strtolower($pathinfo['extension']);

        // if file type not defined, then stop processing and give an error.
        if ($filetype == "") {
            trigger_error("File upload failed. File has no extension (eg: .jpg)", E_USER_ERROR);
            exit;
        }

        if ($access != "tmpl") {
            $filetype = substr($filetype,0,3);
        }

        if (substr($filetype,0,3) == "jpe") $filetype = "jpg";
        if (substr($filetype,0,3) == "php") exit;

        $up = preg_replace('/ /', '%20', Filenames::constructPath($filename, $filetype));
        $destination = SITE_PATH . "/" . $rootfolder . "/"
            . $folder . $up;

        $fup = new FileUploader();
        // if uploaded file is an image, then process with `processUploadedImage` function
        if ($filetype == "gif" || $filetype == "jpg" || $filetype == "png") {

            if ($size_only) {
                $size_thumb = null;
            }

            if ('nosize' != $size_big) {
                if (isset($_POST["size_custom_x"]) && isset($_POST["size_custom_y"])
                && $_POST["size_custom_x"] > 0 && $_POST["size_custom_x"] < 10000
                && $_POST["size_custom_y"] > 0 && $_POST["size_custom_y"] < 10000)
                {
                    $size_big = $_POST["size_custom_x"]."x".$_POST["size_custom_y"];
                }
            } else {
                $size_big = null;
            }

            if ($size_big && $_POST['size_big_dontscale']) {
                $size_big .= '">"';
            }
            if ($size_thumb && $_POST['size_thumb_dontscale']) {
                $size_thumb .= '">"';
            }

            // process uploaded image
            $new_fname = $fup->processUploadedImage($filedata, $destination, $size_big
                , $size_thumb, $upload_action, true);

        } else {
            // process uploaded file
            $new_fname = $fup->processUploadedFile($filedata, $destination, $upload_action, true);
        }

        if ($new_fname) {
            // save log about this upload action
            $log->log('file_manager', 'File ' . substr($new_fname, strlen(SITE_PATH))
                . ' uploaded by ' . $GLOBALS['ses']->getUsername());
        }

        $adm->assign("type", $filetype);
        $adm->assign("name", basename($new_fname, '.' . $filetype));
        $adm->assign("removed", 0);
        $adm->assign("visible", 1);

        $_SESSION["upload"][$_GET['form_id']]['uploaded'][] = basename($new_fname);

    }


    $_folder = $folder;

    if ($_folder != '/' && $_folder && substr($_folder, -2) == '//') $_folder = substr($_folder, 0, -1);
    $adm->assign("folder", $_folder);
    $adm->assign("owner", $_POST["owner"]);
    $adm->assign("keywords", $_POST["keywords"]);
    $adm->assign("lastmod", date("Y-m-d H:i:s"));

    // add new row into table
    if (($do =='add' || $do == 'savefile') && $access != "img" && $access != "tmpl") {
        $res = $adm->add($table, $required, $idfield);
        $sql = "UPDATE files SET add_date = NOW(), lastmod = NOW(), permissions = '" . addslashes(encodePermissions($_POST["group_read"], $_POST["group_write"], $_POST["group_delete"])) . "' WHERE id = '" . $sq->insertid() . "'";
        $sq->query($adm->dbc, $sql);
    }
    elseif ($do =='update' && $id && $access != "img" && $access != "tmpl") // update row.
    {
         $upd_fields = array(
        "text",
        "lastmod",
        "folder"
        );
        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
        $sql = "UPDATE files SET lastmod = NOW(), permissions = '" . addslashes(encodePermissions($_POST["group_read"], $_POST["group_write"], $_POST["group_delete"])) . "' WHERE id = '" . $id . "'";
        $sq->query($adm->dbc, $sql);
    }
  /*  elseif($do =='update_perm' && $id && $access != "img" && $access != "tmpl") {
                $perm_o = '';
        if ($_POST['perm_other'][0]) {
            $perm_o .= '1,';
        } else {
            $perm_o .= '0,';
        }
        if ($_POST['perm_other'][1]) {
            $perm_o .= '1';
        } else {
            $perm_o .= '0';
        }

        $adm->assign("perm_other",'0,' . $perm_o);
        $perm_o = '';
        if ($_POST['perm_group'][0]) {
            $perm_o .= '1,';
        } else {
            $perm_o .= '0,';
        }
        if ($_POST['perm_group'][1]) {
            $perm_o .= '1';
        } else {
            $perm_o .= '0';
        }
        $upd_fields = array(
            "groups",
            "perm_group",
            "perm_other",
            "owner"

            );
        $adm->assign("groups",implode(",",$_POST['perm_groups']));
        $adm->assign("perm_group",'1,' . $perm_o);
        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
    }*/

    if ($do == "savefile") {
        if ($res != 0) {
            $_SESSION["upload"][$_GET['form_id']]['error'] = 1;
            exit();
        }
    }

    if ($res == 0) {
        if ($returnto != "") {
            redirect(substr($returnto, strpos($returnto, "admin")));
            exit;
        }
        else {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><body onload="parent.frames.left.cleanInfo(); top.main.right.document.location=\'browser.php?folder='.urlencode($_folder).'&selectedfile[0]='.urlencode($file).'\'"></body></html>';
            exit;
        }
        //   $tpl->assign("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
    }
    else {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
        $adm->getValues();
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, $do, $id, $field_groups, $fields_in_group);
    }
}
else if ($do == "delete" && $id)
{
    // permissions
    $perm->Access (0, $id, "d", "fileaccess");

    if ($access == "img" || $access == "tmpl") {
        if (substr($folder, 0, 4) == "img/") {
            $rootfolder = $GLOBALS["directory"]["img"];
            $folder = substr($folder, 4);
        }
        else if (substr($folder, 0, 5) == "tmpl/") {
            $rootfolder = $GLOBALS["directory"]["tmpl"];
            $folder = substr($folder, 5);
        }
        else {
            $rootfolder = $GLOBALS["directory"]["upload"];
            $folder = "";
        }
    }
    else {
        $rootfolder = $GLOBALS["directory"]["upload"];
    }

    $sq = new sql;
    $sq->query($adm->dbc, "SELECT name, type, folder FROM $table WHERE $idfield = '$id'");
    $data = $sq->nextrow();
    $sq->free();

    if ($data["folder"] != "")  $folder = $data["folder"] . "/";
    $file_folder = SITE_PATH . "/" . $rootfolder . "/" . $folder;
    $file_orig = Filenames::constructPath($data["name"], $data["type"], $file_folder);
    $file_thum = Filenames::constructPath($data["name"] . "_thumb", $data["type"], $file_folder);

    $un_status1 = @unlink($file_orig);

    if ($un_status1) {
        // save log about this upload action
        $log->log('file_manager', 'File ' . substr($file_orig, strlen(SITE_PATH))
            . ' deleted by ' . $GLOBALS['ses']->getUsername());
    }

    if (file_exists($file_thum) && @unlink($file_thum)) {
        // save log about this action
        $log->log('file_manager', 'Thumbnail File ' . substr($file_thum, strlen(SITE_PATH))
            . ' deleted by ' . $GLOBALS['ses']->getUsername());
    }

    if (!$un_status1) {
        trigger_error("File delete failed. Check file/folder permissions", E_USER_ERROR);
        exit;
    }

    $res = $adm->delete($table, $idfield, $id);

    if ($res == 0) {
        if ($returnto != "") {
            //echo '<body onLoad= "document.location=\'' . $returnto . '\'">';
            //Header("Location: $returnto");
            redirect(substr($returnto, strpos($returnto, "admin")));
            exit;
        }
        else {
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><body onload= "parent.frames.left.cleanInfo(); top.main.right.document.location=\'browser.php?folder='.urlencode($folder).'\'"></body></html>';
            exit;
        }
        //   $tpl->assign("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
    }
    else { $result = $general["error"]; }
} else if ($do == "prepare_upload") {
    unset($_POST["do"]);
    $_SESSION["upload"][$_POST['form_id']] = $_POST;
    exit();
} else if ($do == "finish_upload") {
    if (isset($_SESSION["upload"][$_GET['form_id']]['error'])) {
        //$_POST = $_SESSION["upload"][$_GET['form_id']];
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
        $adm->getValues();
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, $do, $id, $field_groups, $fields_in_group);
    } else {
        $uploadedfiles = '';
        if (is_array($_SESSION["upload"][$_POST['form_id']]['uploaded'])) {
            foreach($_SESSION["upload"][$_POST['form_id']]['uploaded'] as $key=>$uf) {
                $uploadedfiles .= '&selectedfile['.$key.']=' . $uf;
            }
        }

        unset($_SESSION["upload"][$_POST['form_id']]);
        if ($returnto != "") {
            redirect(substr($returnto, strpos($returnto, "admin")));
            exit;
        }
        else {
            $_script_to = 'browser.php';
            if (isset($script) && FALSE !== strpos($script, 'browser_selectfile'))
            {
                $_script_to = "browser_selectfile.php";
            }
           // echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><body onload="parent.frames.left.cleanInfo(); top.main.right.document.location=\'browser.php?folder='.urlencode($folder).'\'"></body></html>';
            echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><body onLoad= "document.location=\''.$_script_to.'?folder='.urlencode($folder).$uploadedfiles.'\'"></body></html>';
            exit;
        }
        //   $tpl->assign("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
    }
}
/* end DB writing part */

if ($show == "add_single") {

    // permissions
    $perm->Access (0, 0, "a", "fileaccess");

    if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
    $adm->types();
    external();
    $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
}
else if ($show == "add") {
    // permissions
    $perm->Access (0, 0, "a", "fileaccess");

    if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
    $adm->types();
    external();
    //$tpl->addDataItem("FIELDSET.UPLOAD_URL", $_SERVER["PHP_SELF"] . "?file=");
    $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
}
/*else if ($show == "addperm") {
    // permissions
    $sql = "SELECT owner,groups, perm_group, perm_other FROM `files` WHERE `id`=?;";
    $perms= $database->fetch_all($sql, $id);
    if ($perms[0]['owner'] == $user) {
        $perm->Access (0, $id, "m", "fileaccess");
        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "update_perm", $id, $field_groups, $fields_in_group);
    } else {
        $perm->displayError(403, "right");
    }
}*/

else if ($show == "modify" && ($id || $file)) {
    // permissions
   /* $sql = "SELECT owner,groups, perm_group, perm_other FROM `files` WHERE `id`=?;";
    $perms= $database->fetch_all($sql, $id);
    $is_modify = false;
    if ($perms[0]['owner'] == $user) {
        $is_modify = true;
    } else {
        if ($perms[0]['groups'] != '' && $perms[0]['perm_group'] !='') {
            if (in_array($user,explode(",",$perms[0]['groups']))) {
                $p = array();
                $p = explode(",",$perms[0]['perm_group']);
                if ($p[1] == 1) {
                    $is_modify = true;
                }
            }
        } else {
            if($perms[0]['perm_other'] !='') {
                   $p = array();
                $p = explode(",",$perms[0]['perm_other']);
                if ($p[1] == 1) {
                    $is_modify = true;
                }
            }
        }
    }
    if ($is_modify) {*/
        $perm->Access (0, $id, "m", "fileaccess");
        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    /*}else {
        $perm->displayError(403, "right");
    }*/
}
else if (!$res || $res == 0) {
    // permissions
    $perm->Access (0, 0, "m", "fileaccess");
    external();
    $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
}

if ($show == "add_single" || $show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->display("add"));
    $active_tab = 1;
}
/*elseif ($show == "addperm" ) {
    $tpl->addDataItem("TITLE", $txtf->display("add_perm"));
    $active_tab = 3;
}*/

else {
    $tpl->addDataItem("TITLE", $txtf->display("modify"));
    $active_tab = 2;
}

$nr = 1;
while(list($key, $val) = each($tabs)) {
    $tpl->addDataItem("TABS.ID", $nr);
    //$tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
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

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

echo "<base target=\"_self\">" . $tpl->parse();