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
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/text.class.php");
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

$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "root", true);

// init Text object for this page
$txt = new Text($language2, "settings_index");

// ######### END INIT PART  #########

$db = new DB;
$db->connect();
$sq = new sql;

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/vertical_navi.html");

// preselected item
if (isset($_GET['selected'])) {
    $tpl->addDataItem('PRESELECTED_LINK', htmlspecialchars($_GET['selected'], ENT_QUOTES));
} else {
    $tpl->addDataItem('PRESELECTED_LINK', 'settings_general.php');
}

$menu = array(
    'settings' => 'settings_general.php',
    'users' => array(
        'users' => 'settings_users.php',
        'groups' => 'settings_groups.php',
     ),
     'img' => array(
        'design' => 'settings_designfiles.php?mode2=img',
        'template' => 'settings_designfiles.php?mode2=tmpl',
     ),
     'design' => array(
         'template' => 'settings_design_templates.php',
         'design_css_js'=> 'settings_design_css_js.php'
     ),
     'lang' => array(
        'translations_list' => 'settings_translator.php',
        'languages_list' => 'settings_translator_languages.php',
        'lang_import_export' => 'settings_translator_import_export.php',
     ),
     'importexport' => array(
        'export' => 'settings_importexport_export.php',
        'import' => 'settings_importexport_import.php',
     ),
     'aliases' => 'settings_aliases.php',
     'logs' => array(
        'logs_event' => 'settings_systemlog.php',
        'logs_error' => 'settings_error_log.php'
     ),
);

$i = 0;
foreach ($menu as $parent_name => $parent) {
    $i++;
    $tpl->addDataItem("PARENT.ID", $i);
    $tpl->addDataItem("PARENT.NAME", $txt->display($parent_name));

    if (is_array($parent)) {
        $tpl->addDataItem("PARENT.STYLE", ' closed');
        $tpl->addDataItem("PARENT.URL", "null");
        $tpl->addDataItem("PARENT.CHILDREN.PARENT", $i);

        $j = 0;
        foreach ($parent as $subelement => $subelement_url) {
            $j++;
            $tpl->addDataItem("PARENT.CHILDREN.CHILD.NAME", $txt->display($subelement));
            $tpl->addDataItem("PARENT.CHILDREN.CHILD.URL", $subelement_url);
            $tpl->addDataItem("PARENT.CHILDREN.CHILD.PARENT_ID", $i);
            $tpl->addDataItem("PARENT.CHILDREN.CHILD.ID", $j);
        }

    } else {
        $tpl->addDataItem("PARENT.STYLE", ' opened');
        $tpl->addDataItem("PARENT.URL", "'$parent'");
    }
}

echo $tpl->parse();
