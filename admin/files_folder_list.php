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

if (ereg("\.", $folder)) exit;
if ($folder == "/") $folder = "";

$show_max = 500;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/files_folder_list.html");

// ###############################################################

        if (!$start) $start = 0;
        if (!$mode) $mode = "all";
        if ($mode == "-") $mode = "all";

        $tpl->addDataItem("TITLE", $txtf->display("folder") . ": " . $folder);

        $tpl->addDataItem("CONFIRMATION", $txt->display("delete_confirmation"));

        $header_fields = array(
            "icon" => "&nbsp;",
            "name" => $txtf->display("folder"),
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
            1 => array($txta->display("addfolder"), "files_folder.php?show=add&filter=" . urlencode($filter) . "&topfolder=" . urlencode($folder)."&sort=$sort&sort_type=$sort_type"),
            2 => array($txta->display("modifyfolder"), "files_folder_list.php")
        );

        $active_tab = 2;

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


        // ###############################################
        function doFold($fold, $fold_name) {
            global $tpl, $filter, $sort, $sort_type;
            global $folder;
            if ($fold_name == "..")  {
                //$fold = "";
                $main = "TOPFOLDER";
                $icon = "<img src=\"pic/icosmall_folder-up.gif\" align=absmiddle border=0 alt=\"$fold\">";
            }
            else {
                $main = "FOLDERS";
                $icon = "<img src=\"pic/icosmall_folder-closed.gif\" align=absmiddle border=0 alt=\"$fold\">";
            }

            if ($folder && $fold && $fold_name != "..") $fold = $folder . $fold;
            if ($fold != "" && substr($fold, 0, -1) != "/") $fold .= "/";
            $url = $_SERVER["PHP_SELF"] . "?filter=" . urlencode($filter) . "&folder=" . urlencode($fold)."&sort=$sort&sort_type=$sort_type";

            $tpl->addDataItem("$main.URL", $url);
            $tpl->addDataItem("$main.ICON", $icon);
            $tpl->addDataItem("$main.NAME", $fold_name);
            $tpl->addDataItem("$main.TEXT", $fold_name);

            $tpl->addDataItem("$main.MODURL", "files_folder.php?show=modify&folder=".urlencode($fold)."&returnto=admin/files_folder_list.php?folder=" . urlencode($folder));
            $tpl->addDataItem("$main.DELURL", "files_folder.php?do=delete&folder=".urlencode($fold)."&returnto=admin/files_folder_list.php?folder=" . urlencode($folder));
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

        // #######################

        // get folders

        $start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/";
        $start_folder1 = $GLOBALS["directory"]["upload"] . "/";
        $start_url = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/";

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

echo $tpl->parse();
