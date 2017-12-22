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
$txtf = new Text($language2, "files_index");
$txta = new Text($language2, "admin_files");

// ##############################################################
// ##############################################################

$db = new DB;
$db->connect();
$sq = new sql;

if (false !== strpos($folder, '.')) exit();
if ($folder == "/") $folder = "";
if (!$mode2) $mode2 = "img";
if ($mode2 != "img" && $mode2 != "tmpl") $mode2 = "img";

$show_max = 500;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/browser_withedit.html");

// ###############################################################

        if (!$start) $start = 0;
        if (!$mode) $mode = "all";
        if ($mode == "-") $mode = "all";

        $tpl->addDataItem("TITLE", $txtf->display("folder") . ": " . $GLOBALS["directory"][$mode2] . "/"  . $folder);

        $tpl->addDataItem("CONFIRMATION", $txt->display("delete_confirmation"));

        $header_fields = array(
            "icon" => "&nbsp;",
            "name" => $txtf->display("info_name"),
            "size" => $txtf->display("info_size"),
            "date" => $txtf->display("info_date"),
            //"text" => $txta->display("text"),
            "modify" => "&nbsp;",
            "delete" => "&nbsp;"
        );

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
        //

        $tabs = array(
            1 => array($txtf->display("view_detail"), "settings_designfiles.php"."?mode=" . $mode . "&filter=" . urlencode($filter) . "&folder=" . urlencode($folder)."&sort=$sort&sort_type=$sort_type"),
            2 => array($txtf->display("addnew"), "files_admin.php?show=add&access=".$mode2 ."&folder=" . urlencode($folder)."&returnto=".urlencode($_SERVER["PHP_SELF"]."?mode2=".$mode2."&folder=" . $folder))
        );

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

        // general text
        $tpl->addDataItem("FILTER", $txt->display("filter"));
        //$tpl->addDataItem("DISPLAY", $txt->display("display"));
        $tpl->addDataItem("SUBMIT", $txt->display("filter"));

        // mode2 folder to access
        unset($fdata);
        $fdata["type"] = "select";
        $fdata["list"] = array("" => $txtf->display("sel2_-"), "img" => $txtf->display("sel2_img"), "tmpl" => $txtf->display("sel2_tmpl"));
        $f = new AdminFields("mode2",$fdata);
        $tpl->addDataItem("EXTRAFILTER.FIELD", $f->display($mode2));

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

        $tpl->addDataItem("VAL_SORT", $sort);
        $tpl->addDataItem("VAL_SORT_TYPE", $sort_type);
        $tpl->addDataItem("VAL_FILTER", $filter);
        $tpl->addDataItem("VAL_FOLDER", $folder);

        //$tpl->addDataItem("HIDDEN", $hidhid);


        // #######################

        function doFile ($id, $obj, $text, $type, $date, $folder) {
            global $tpl, $mode2, $txtf;
            //if ($folder) $folder .= "/";
            if ($type == "gif" || $type == "jpg" || $type == "png" || $type == "tif") {
                $icon = "<img src=\"pic/icosmall_image.gif\" align=absmiddle border=0 alt=\"$text\">";
                if ($mode2 == "tmpl") {
                    $url = "tmpl_opener.php?file=" . $folder . $obj . "." . $type;
                }
                else {
                    $url = "javascript:openPicture('" . SITE_URL . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "." . $type . "');";
                }
            }
            else {
                if ($mode2 == "tmpl") {
                    $url = "tmpl_opener.php?file=" . $folder . $obj . "." . $type;
                }
                else {
                    $url = "javascript:openFile('" . SITE_URL . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "." . $type . "');";
                }
                if (file_exists(SITE_PATH . "/admin/pic/icosmall_" . strtolower($type) . ".gif")) {
                    $icon = "<img src=\"pic/icosmall_".strtolower($type). ".gif\" align=absmiddle border=0 alt=\"$text\">";
                }
                else {
                    $icon = "<img src=\"pic/icosmall_other.gif\" align=absmiddle border=0 alt=\"$text\">";
                }
            }
            if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "." . $type)) {
                $size = round(filesize(SITE_PATH . "/" . $GLOBALS["directory"][$mode2] . "/" . $folder . $obj . "." . $type)/1000) . " kb";
            }
            else {
                $size = "? kb";
            }
            if ($type == "gif" || $type == "jpg" || $type == "png") {
                if (false !== strpos($obj, '_thumb')) {
                    //$url1 = SITE_URL . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "." . $type;
                }
                else {
                if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "_thumb." . $type)) {
                    //$url1 = SITE_URL . "/" . $GLOBALS["directory"][$mode2] . "/"  . $folder . $obj . "_thumb." . $type;
                }
                else {
                    //$url1 = "";
                }
                }
            }
            $url2 = urlencode($folder . $obj . "." . $type); // . "?" . $_SERVER["QUERY_STRING"]);

            if (strlen($obj) > 26) $f_name = substr($obj,0,26)."...".$type;
            else { $f_name = "$obj.$type"; }

            $tpl->addDataItem("ROWS.ICON", $icon);
            $tpl->addDataItem("ROWS.URL1", $url);

            if ($id) {
                $tpl->addDataItem("ROWS.MODURL", "files_admin.php?show=modify&id=$id&file=".$url2."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?mode2=".$mode2."&folder=" . $folder));
            }
            else {
                $tpl->addDataItem("ROWS.MODURL", "files_admin.php?show=modify&id=&file=".$url2."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?mode2=".$mode2."&folder=" . $folder));
            }

            if ($text == "") $text = "&nbsp;";
            $tpl->addDataItem("ROWS.TEXT", $text);
            $tpl->addDataItem("ROWS.NAME", $f_name);
            $tpl->addDataItem("ROWS.SIZE", $size);
            $tpl->addDataItem("ROWS.DATE", $date);
            $tpl->addDataItem("ROWS.MODIFY", $txtf->display("info_modify"));
            if ($id) {
                $tpl->addDataItem("ROWS.DELURL", "files_admin.php?do=delete&id=".$id."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?mode2=".$mode2."&folder=" . $folder));
            }
            else {
                $tpl->addDataItem("ROWS.DELURL", "files_delete.php?do=delete&file=".$url2."&folder=".urlencode($folder)."&access=".$mode2."&returnto=".urlencode($_SERVER["PHP_SELF"]."?mode2=".$mode2."&folder=" . $folder));
            }

        }

        // ###############################################
        function doFold($fold, $fold_name) {
            global $tpl, $mode, $mode2, $filter, $sort, $sort_type;
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
            $url = $_SERVER["PHP_SELF"] . "?mode=" . $mode . "&mode2=".$mode2."&filter=" . urlencode($filter) . "&folder=" . urlencode($fold)."&sort=$sort&sort_type=$sort_type";

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
            global $mode;
            if (false !== strpos($file, '_thumb.')) {
                return false;
            }
            else {
                if ($mode == "all") {
                    return true;
                }
                else if ($mode == "pic") {
                    if (getTyp($file) == "gif" || getTyp($file) == "jpg" || getTyp($file) == "png") {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
                else if ($mode == "nopic") {
                    if (getTyp($file) != "gif" && getTyp($file) != "jpg" && getTyp($file) != "png") {
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

        $start_folder = SITE_PATH . "/" . $GLOBALS["directory"][$mode2] . "/";
        $start_folder1 = $GLOBALS["directory"][$mode2] . "/";
        $start_url = SITE_URL . "/" . $GLOBALS["directory"][$mode2] . "/";

        // file descriptions
        //$sql = "SELECT id, CONCAT(name, \".\", type) as file, text, folder FROM files WHERE folder = '" . addslashes(substr($folder,0,-1)) . "' ORDER BY file ASC";
        //$sq->query($db->con, $sql);
        //while ($data = $sq->nextrow()) {
        //  $desc[$data["file"]] = array($data["id"],$data["text"]);
        //}
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
                        if (false !== strpos(strtolower($file), addslashed(strtolower($filter)))) {
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

echo $tpl->parse();
