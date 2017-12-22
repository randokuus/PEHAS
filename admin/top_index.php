<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

error_reporting(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

// ######### BEGIN INIT PART  #########

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

// init Text object for this page
$txt = new Text($language2, "top_index");
$txt1 = new Text($language2, "admin_settings1");

$db = new DB;
$db->connect();
$sq = new sql;

// ######### END INIT PART  #########

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/top_index.html");

    if (in_array(MODERA_PRODUCT, array('webmaster', 'webmaster-pro', 'intranet'
        , 'intranet-pro', 'extranet', 'extranet-pro')))
    {
        $tpl->addDataItem("TYPE", MODERA_PRODUCT);
    } else {
        $tpl->addDataItem("TYPE", "webmaster");
    }

    $sq->query($db->con, "SELECT DATE_FORMAT(time, '%d.%m.%Y %H:%i') as timex, DATE_FORMAT(DATE_ADD(time, INTERVAL 3 HOUR), '%d.%m.%Y %H:%i') as expire FROM adm_session where sid = '$logged' and user = '$user'");
    $login = $sq->column(0, "timex");
    $expire = $sq->column(0, "expire");
    $sq->free();

    $sq->query($db->con, "SELECT u.name as uname, g.name as gname FROM adm_user as u LEFT JOIN adm_group as g ON u.ggroup = g.ggroup WHERE u.user = '$user' LIMIT 1");
    $uname = $sq->column(0, "uname");
    $gname = $sq->column(0, "gname");
    $sq->free();

    //$txt_user = ereg_replace("{NAME}", $uname . " ($gname)", $txt->display("user"));
    $txt_user = str_replace("{NAME}", $uname, $txt->display("user"));

    $tpl->addDataItem("USER", $txt_user);
    //$tpl->addDataItem("LOGGEDIN", ereg_replace("{DATE}", $login, $txt->display("loggedin")));
    //$tpl->addDataItem("EXPIRES", ereg_replace("{DATE}", $expire, $txt->display("expires")));
    $tpl->addDataItem("LOGOUT", $txt->display("logout"));
    $tpl->addDataItem("HELP", $txt->display("help"));

    $tpl->addDataItem("LANGUAGESELECT", $txt->display("languageselect"));
    $sq->query($db->con, "SELECT DATE_FORMAT(moddate, '%d.%m.%Y %H:%i') as `mod` FROM content ORDER BY moddate DESC LIMIT 1");
    $moddate = $sq->column(0, "mod");
    $sq->free();

$tpl->addDataItem("LASTMOD", str_replace("{DATE}", "$moddate", $txt->display("lastmod")));

    $sq->query($db->con, "SELECT active FROM settings");
    $active = $sq->column(0, "active");
    $sq->free();

$tpl->addDataItem("STATUS", str_replace("{STATUS}", $txt->display("status_$active"), $txt->display("status")));

    if (!$top) $top = "content";

    //$ar = array(
    //  "content" => "index.php?content/content_navi.php/empty.html",
    //  "files" => "index.php?files/files_navi.php/empty.html",
    //  "settings" => "index.php?settings/settings_navi.php/settings_general.php",
    //  "modules" => "index.php?modules/modules_navi.php/empty.html",
    //  "preview" => "index.php?preview/preview.php"
    //);

    $ar = array(
        "content" => "main_index.php?content_navi.php/dashboard.php",
        "files" => "main_index.php?files_navi.php/browser.php",
        "settings" => "main_index.php?settings_navi.php/settings_general.php",
        "modules" => "main_index.php?modules_navi.php/empty.html",
        "isic" => "main_index.php?modules_navi_isic.php/empty.html",
        "preview" => "preview.php?language=".$language
    );

    // ONLY ROOT LEVEL ALLOWED
    if ($perm->root != $group) {
        $ar["settings"] = "main_index.php?empty.html/settings_general.php";
    }

    if (!$top) $top = "content";

    $nr = 1;
    while(list($key, $val) = each($ar)) {
        $tpl->addDataItem("MENU.NAME", $txt->display($key));
        $tpl->addDataItem("MENU.URL", "javascript:jumpToMenu('".$nr."', '$val');");
        if ($top == $key) {
            $tpl->addDataItem("MENU.STYLE", "active");
        }
        else {
            $tpl->addDataItem("MENU.STYLE", "");
        }
        $tpl->addDataItem("MENU.ID", $nr);
        $nr++;
    }

    $res =& $database->query('SELECT `language`, `title` FROM `languages`');

    while ($row = $res->fetch_assoc()) {
        // converting language to code to upper case to emulate previour version of
        // i18n system
        $key = strtoupper(htmlspecialchars($row['language']));
        $title = htmlspecialchars($row['title']);

        $tpl->addDataItem("LANGUAGE.CODE", $key);
        $tpl->addDataItem("LANGUAGE.LANGUAGE", $title);
        if ($language == $key) {
            $tpl->addDataItem("LANGUAGE.SEL", "selected");
        } else {
            $tpl->addDataItem("LANGUAGE.SEL", "");
        }

    }

    $tpl->addDataItem("INTERFACE", $language2);
    $tpl->addDataItem("VERSION", MODERA_VERSION);

$result = $tpl->parse();
//$result = addParameterToUrl($result, "rnd=".randomNumber());
echo $result;
