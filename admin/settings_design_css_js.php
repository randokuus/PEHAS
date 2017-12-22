<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");       // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");   // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . '/class/admin2.class.php');

/**
 * Get content of file
 *
 * @param string $folder
 * @param string $file
 * @return string content of file
 */
function getFileContent($folder, $file){
    $file_name = SITE_PATH . '/'. $folder  . $file;
    $content = '';
    if (($fh = @fopen($file_name, 'r')) !== false){
        $content = fread($fh, filesize($file_name));
        fclose($fh);
    }
    return $content;
}

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
$txt  = new Text($language2, "admin_general");
$txtf = new Text($language2, "files_index");
$trf  = new Text($language2, "admin_design_css_js");

$general["button"] = $txt->display("button");
$general['template_form'] = 'tmpl/admin_form.html';

$type_list = array(
    'css',
    'js'
);

///////////////////////////////////////////////////////
function external() {
    global $adm, $txtf, $txt, $show, $trf, $group, $language, $sel_language,
           $id, $file, $structure, $sel_template, $type_list, $folder,$start_folder1;
    //$sq = new sql;

    $adm->displayOnly("design");
    $adm->assign("design", $GLOBALS["templates_".$sel_language][$sel_template][0]);

    $adm->assignProp("name", "type", "textinput");
    $adm->assignProp("name", "size", "40");
    $adm->assignProp("desc", "type", "textinput");
    $adm->assignProp("desc", "size", "40");
    $adm->assignProp("content", "type", "textfield");
    $adm->assignProp("content", "cols", "90");
    $adm->assignProp("content", "rows", "30");
    $adm->assignProp("content", "wrap", "off");

    //$adm->assignProp("name", "extra", $txtf->display("name_extra"));

    $adm->assignProp("type", "type", "select");
    $tmp_arr = array();
    foreach ($type_list as $ext){
        $tmp_arr[$ext] = $trf->display('type_'.$ext);
    }
    $adm->assignProp("type", "list", $tmp_arr);
    //$adm->assignProp("type", "list", array("1" => $txtf->display("type1"), "2" => $txtf->display("type2"), "3" => $txtf->display("type3"), "4" => $txtf->display("type4"), "5" => $txtf->display("type5"), "6" => $txtf->display("type6")));

    //  if ($show == "modify" && ($id || $file))
    if (in_array($show, array('modify', 'add')) && ($id || $file))
    {
        $adm->displayOnly("name");
        $adm->displayOnly("type");
        $adm->displayOnly("folder");
        if (isset($id) && !empty($id) && (!isset($file) || $file !== $id)){
            $file = $id;
        }

        $adm->assign("content", "<__DUMMY__>");

        if ($show == 'modify') {
            $adm->assign("name", substr($file, 0, strrpos($file, '.')));
        }
        if (($pos = strrpos($file, '.')) !== false) {
            $ext = strtolower(substr($file, $pos+1));
            if (in_array($ext, $type_list)){
                $adm->assign("type",  $ext);
            }
            $adm->assign("folder", $GLOBALS["directory"]["img"].'/'.$folder);
        }
        $adm->assignHidden("file", $file);
        //$adm->assignHidden("folder", $folder);

    }
}

// ##############################################################
// ##############################################################

$db = new DB;
$db->connect();
$sq = new sql;

if (false !== strpos($folder, '.')) exit();
if ($folder == "/") $folder = "";

if (strpos($folder, "/") === 0) {
    $folder = substr($folder,1);
}


$show_max = 500;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/browser_withedit.html");

// ###############################################################

if (!$start) $start = 0;
if (!$mode) $mode = "all";
if ($mode == "-") $mode = "all";

$header_fields = array(
    "icon" => "&nbsp;",
    "name" => $txtf->display("info_name"),
    "size" => $txtf->display("info_size"),
    "date" => $txtf->display("info_date"),
    //"text" => $txta->display("text"),
    "modify" => "&nbsp;",
    "delete" => "&nbsp;",
);

$tabs = array(
    1 => array($txtf->display("view_detail"), "?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($folder)."&sort=$sort&sort_type=$sort_type"),
    2 => array($txtf->display("addnew"), "?show=add&folder=" . urlencode($folder)."&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder)),
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

// #######################

function doFile ($id, $obj, $text, $type, $date, $folder) {
    global $tpl, $mode2, $txtf;
    //if ($folder) $folder .= "/";
    $icon = "<img src=\"pic/icosmall_other.gif\" align=absmiddle border=0 alt=\"$text\">";
    $url = "javascript:openFile('" . SITE_URL . "/" . $GLOBALS["directory"]["img"] . "/"  . $folder . $obj . "." . $type . "');";

    if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/"  . $folder . $obj . "." . $type)) {
        $size = round(filesize(SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/" . $folder . $obj . "." . $type)/1000) . " kb";
    }
    else {
        $size = "? kb";
    }
    $url2 = urlencode($folder . $obj . "." . $type); // . "?" . $_SERVER["QUERY_STRING"]);

    if (strlen($obj) > 26) $f_name = substr($obj,0,26)."...".$type;
    else { $f_name = "$obj.$type"; }

    $tpl->addDataItem("ROWS.ICON", $icon);
    $tpl->addDataItem("ROWS.URL1", $url);

    if ($id) {
        $tpl->addDataItem("ROWS.MODURL", "?show=modify&id=$id&file=".$obj . "." . $type."&folder=".urlencode($folder)."&&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder));
    }
    else {
        $tpl->addDataItem("ROWS.MODURL", "?show=modify&id=&file=".$obj . "." . $type."&folder=".urlencode($folder)."&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder));
    }

    if ($text == "") $text = "&nbsp;";
    $tpl->addDataItem("ROWS.TEXT", $text);
    $tpl->addDataItem("ROWS.NAME", $f_name);
    $tpl->addDataItem("ROWS.SIZE", $size);
    $tpl->addDataItem("ROWS.DATE", $date);
    $tpl->addDataItem("ROWS.MODIFY", $txtf->display("info_modify"));
    if ($id) {
        $tpl->addDataItem("ROWS.DELURL", "settings_design_css_js.php?do=delete&id=".$id."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder));
    }
    else {
        $tpl->addDataItem("ROWS.DELURL", "settings_design_css_js.php?do=delete&file=".$obj . "." . $type."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?folder=" . $folder));
    }

}

// ###############################################
function doFold($fold, $fold_name) {
    global $tpl, $mode, $filter, $sort, $sort_type;
    global $folder;
    if ($fold_name == "..")  {
        //$fold = "";
        $icon = "<img src=\"pic/icosmall_folder-up.gif\" align=absmiddle border=0 alt=\"$fold\">";
    }
    else {
        $icon = "<img src=\"pic/icosmall_folder-closed.gif\" align=absmiddle border=0 alt=\"$fold\">";
    }

    if ($folder && $fold && $fold_name != "..") $fold = $folder . $fold;
    if ($fold != "" && substr($fold, 0, -1) != "/") $fold .= "/";
    $url = $_SERVER["PHP_SELF"] . "?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($fold)."&sort=$sort&sort_type=$sort_type";

    $tpl->addDataItem("FOLDERS.URL", $url);
    $tpl->addDataItem("FOLDERS.ICON", $icon);
    $tpl->addDataItem("FOLDERS.NAME", $fold_name);
    $tpl->addDataItem("FOLDERS.TEXT", $fold_name);
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
    global $mode, $type_list;
    if (($pos = strrpos($file, '.')) !== false){
        $ext = strtolower(substr($file, $pos+1));
        if (in_array($ext, $type_list)){
            if(!empty($mode) && $mode!='all' && $mode != $ext)
                return false;
            return true;
        }
    }
    return false;
}

/**
 * Recrusive function which returns
 * list of folders( for selectobject of forms)
 * and subfolders located in $folder
 *
 * @author Stanislav Chichkan <stas@itworks.biz.ua>
 *
 * @param string $folder file folder name(path)
 * @param array $folder_list
 * @return array folders list
 */
function parse_folder( $folder = '/' , &$folder_list){


    if(empty($folder_list))
       $folder_list[$folder] = $GLOBALS["directory"]["img"]  . $folder;
       $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["img"]  . $folder);
        while ($dir = @readdir($dh)){
            if ($dir != "." && $dir != ".." && is_dir(SITE_PATH . "/".  $GLOBALS["directory"]["img"] . $folder . "/" . $dir)) {
                $final = $folder . $dir . "/";
                $folder_list[$GLOBALS["directory"]["img"]  . $final] = $GLOBALS["directory"]["img"] . $final;
                parse_folder( $final , $folder_list);
            }
        }

    return $folder_list;
}
        // #######################


// get folders

        $start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/";
        $start_folder1 = $GLOBALS["directory"]["img"] . "/";
        $start_url = SITE_URL . "/" . $GLOBALS["directory"]["img"] . "/";

function show(){
    global $tpl, $txt, $txtf,  $mode, $sort, $sort_type, $filter,
           $folder, $start_folder, $header_fields,
           $start, $key, $sort_type1, $max_entries;

    $tpl->addDataItem("CONFIRMATION", $txt->display("delete_confirmation"));
    // header
    while (list($key, $val) = each($header_fields)) {
        $url = $_SERVER['PHP_SELF'] . "?start=$start&sort=$key&sort_type=$sort_type1&max_entries=$max_entries&filter=".urlencode($filter)."&folder=".urlencode($folder);
        $tpl->addDataItem("HEADER.NAME", $val);
        $tpl->addDataItem("HEADER.URL", $url);
        if ($sort == $key) {
            if ($sort_type == "asc") {
                $tpl->addDataItem("HEADER.STYLE", "active up");
            }
            else if ($sort_type == "desc") {
                $tpl->addDataItem("HEADER.STYLE", "active dn");
            }
        }
    }
    reset($header_fields);
        // general text
    $tpl->addDataItem("FILTER", $txt->display("filter"));
        //$tpl->addDataItem("DISPLAY", $txt->display("display"));
    $tpl->addDataItem("SUBMIT", $txt->display("filter"));

    $mode_list = array("-", "all","css","js");
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

    $tpl->addDataItem("VAL_SORT", $sort);
    $tpl->addDataItem("VAL_SORT_TYPE", $sort_type);
    $tpl->addDataItem("VAL_FILTER", $filter);
    $tpl->addDataItem("VAL_FOLDER", $folder);

    $desc = array();

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

    if ($folder != "") {
        $fold = substr(substr($folder,0,-1), 0, strrpos(substr($folder,0,-1), '/'));
        doFold($fold, "..");
        //$tpl->parse("doc.row");
    }

    for ($c = 0; $c < sizeof($folders); $c++) {
        doFold($folders[$c], $folders[$c]);
        //$tpl->parse("doc.row");
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

        $obj = getName($files[$c]);
        $text = $desc[$files[$c]][1];
        $type = getTyp($files[$c]);
        $id = $desc[$files[$c]][0];
        $date = date ("d.m.y H:i", filemtime($opendir . $files[$c]));
        $folder = addslashes($folder);

        doFile($id, $obj, $text, $type, $date, $folder);

        //$tpl->parse("doc.row");

    }
}
if (in_array($show, array('modify', 'add')) || in_array($do, array('update', 'add', 'delete'))){

   $adm = new Admin2("");
   $adm->assign("language", $language);

   /* the fields in the table */
   $fields = array(
      "type"    => $trf->display("type"),
      "folder"  => $trf->display("folder"),
      "name"    => $trf->display("name"),
      "content" => $trf->display("content")
   );
}


// #######################################################




/* Writing file part */
switch (true){
    case $do == "add":

        $folder = preg_replace('#^'.$GLOBALS["directory"]["img"] . '/#','',$folder);
        $tempfile = str_replace(' ', '_', $name);
        $tempfile = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $tempfile);
        $tempfile = preg_replace('/\$|\.|%|@|&|�|�|�|�|�|�|�|�|\"|\'/', '', $tempfile);
        $tempfile = strtolower($tempfile);
        // if new file name is empty, then try again.
        if (!strlen($tempfile)) {
            $adm->values["name"] = "";
        }
        if (in_array($type, $type_list)){
            $tempext = $type;
            $file_path = SITE_PATH . "/" . $GLOBALS["directory"]["img"] ."/". $folder . "/" . $tempfile . "." . $tempext;
            if (file_exists($file_path)){
                $adm->values["name"] = "";
            }
            if (sizeof($adm->checkRequired($required)) == 0){
                if (($handle = @fopen ($file_path, "w")) !== false){
                    $status = @fwrite($handle, $content);
                    fclose($handle);
                    if ($status) {
                        // save log about this action
                        $log->log('settings_design_css', 'File ' . substr($file_path, strlen(SITE_PATH))
                            . ' created by ' . $GLOBALS['ses']->getUsername());
                    }
                }
            }else{
                $res = 99;
            }
            if ($res == 0)
            {
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
            }else{
                $adm->values["content"] = preg_replace("/<TPL_OBJECT/m", "<TPLOBJECT", $adm->values["content"]);
                $adm->values["content"] = preg_replace("/<\/TPL_OBJECT/m", "</TPLOBJECT", $adm->values["content"]);
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
                $adm->getValues();
                //$adm->types();
                external();
                $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
            }

        }
        break;
    case $do == "update" && ($id || $file):

        $folder = preg_replace('#^'.$GLOBALS["directory"]["img"] . '/#','',$folder);
        $content = preg_replace("/\\\"/m", "\"", $content);
        $content = preg_replace("/<textarea\/>/m", "</textarea>", $content);
        $file_path = SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/".  $folder  . $file;

        if (($fh = @fopen($file_path, 'w')) !== false){
            $status = @fwrite($fh, $content);
            @fclose($fh);
            if ($status) {
                // save log about this action
                $log->log('settings_design_css', 'File ' . substr($file_path, strlen(SITE_PATH))
                    . ' updated by ' . $GLOBALS['ses']->getUsername());
            }
        }

        break;
    case $do == "delete" && ($id || $file):
        if (isset($id) && isset($file) && $file == ''){
            $file = $id;
        }
        $file_path = SITE_PATH . "/{$GLOBALS['directory']['img']}/{$folder}{$file}";
        if (@unlink($file_path)) {
            // save log about this action
            $log->log('settings_design_css', 'File ' . substr($file_path, strlen(SITE_PATH))
                . ' deleted by ' . $GLOBALS['ses']->getUsername());
        }


        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
        break;
}

/* End writing part*/

$active_tab = 2;
switch (true){
    case $show == "add":
        $tpl->setTemplateFile('tmpl/admin_main.html');
        $tpl->addDataItem("TITLE", $trf->display("add"));
        external();
        $adm->assignProp("folder", "type", "select");
        $ar = parse_folder();
        $adm->assignProp("folder", "list", $ar);
        $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
        break;
    case $show == "modify" && ($id || $file):
        $tpl->addDataItem("TITLE", $trf->display("info_modify"));

        $tabs[2] = array($txtf->display("info_modify"),"#");
        external();
        $adm->assignProp("folder", "type", "select");
        $ar = parse_folder();
        $adm->assignProp("folder", "list", $ar);

        $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        $tpl->setTemplateFile('tmpl/admin_main.html');
        break;
    case !isset($res) || !$res:
        show();
        $tpl->addDataItem("TITLE", $txtf->display("folder") . ": " . $GLOBALS["directory"]["img"] . "/"  . $folder);
        $active_tab = 1;
}


$nr = 1;
while(list($key, $val) = each($tabs))
{
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
$tpl->addDataItem("CONTENT", $result);
// if file is set and $show is one of 'modify' or 'add'
// then change/copy content of given file.

if (in_array($show, array('modify', 'add')) && $file){

    $content = getFileContent($GLOBALS["directory"]["img"] . "/". $folder, $file);

    $content  = preg_replace("/\"/m", "\"", $content);
    $content  = preg_replace("/<\/textarea>/m", "<textarea/>", $content);

    $result   = $tpl->parse();
    $result   = preg_replace("/<__DUMMY__>/i", $content, $result);

}else{
    $result = $tpl->parse();
    $result = preg_replace("/<TPLOBJECT/m", "<TPL_OBJECT", $result);
    $result = preg_replace("/<\/TPLOBJECT/m", "</TPL_OBJECT", $result);
}
echo $result;