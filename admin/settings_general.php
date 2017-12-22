<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
$id = 1;
$show = "modify";
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
require_once(SITE_PATH . "/class/Arrays.php");
require_once(SITE_PATH . "/class/Translator.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . '/class/ModeraTranslator.php');

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

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_settings1");
$txtg = new Text($language2, "admin_content");

// ##############################################################
// ##############################################################

$table = "settings"; // SQL table name to be administered

$idfield = "1"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main.html",
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
    "max_entries" => 30,
    "sort" => "", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "logo"  => $txtf->display("logo"),
    "name" => $txtf->display("name"),
    "descr" => $txtf->display("descr"),
    "admin"  => $txtf->display("admin"),
    "admin_email"  => $txtf->display("admin_email"),
    "lang"  => $txtf->display("language"),
    "active"  => $txtf->display("active"),
    "userlogin"  => $txtf->display("userlogin"),
    "loginform"  => $txtf->display("loginform"),
    "translator" => 'Translator used',
//  "translator" => $txtf->display('translator'),

    "sitelocale" => $txtf->display("sitelocale"),
    "sitecache" => $txtf->display("sitecache"),
    "cachetime" => $txtf->display("cachetime"),
    "debuglevel" => $txtf->display("debuglevel"),

    "cache"  => $txtf->display("cache"),
    "text" => $txtf->display("info")
);

$tabs = array(
    1 => $txtf->display("modify"),
    2 => $txtf->display("info")
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
    2 => array($txtf->display("info"), "")
);

$fields_in_group = array(
    "logo"  => 1,
    "name" => 1,
    "descr" => 1,
    "admin"  => 1,
    "admin_email"  => 1,
    "lang"  => 1,
    "active"  => 1,
    "userlogin"  => 1,
    "loginform"  => 1,
    "translator" => 1,
    "sitelocale",
    "sitecache",
    "cachetime",
    "debuglevel",
    "cache"  => 1,
    "text" => 2
);


/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    //"name",
    //"info",
    "descr",
    "admin",
    "admin_email",
    "lang",
    "active",
    "userlogin",
    "loginform",
    "translator",
    "sitelocale",
    "sitecache",
    "cachetime",
    "debuglevel",
    "user",
    "lastmod"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    $idfield => "ID" // if you want to display the ID as well
);

/* required fields */
$required = array(
    "name",
    //"info",
    "admin",
    "admin_email",
    "lang"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    //$where = "$table.structure = '$structure' AND language = '$language'";

    $filter_fields = array(
//      "$table.title",
//      "$table.redirectto"
    );

    //if ($GLOBALS["editor"] == true && $show_editor != "true") {
    //  $general["template_form"] = "tmpl/admin_form.html";
    //}

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $show_editor, $txtf, $txtg, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->Assign('name', $GLOBALS['site_settings']['name']);
    $js = "window.location.href='settings_translator.php?do=edittr&token=site_name"
        . "&domain=output'; return false;";
    $adm->AssignProp('name', 'extra', "<a href=\"#\" onclick=\"$js\">". $txtf->display('translate')
        . '</a>');
    $adm->displayOnly('name');

    $adm->assignProp("admin", "size", "30");
    $adm->assignProp("admin_email", "size", "30");

    $adm->assignProp("text", "type", "nothing");
    $adm->displayOnly("text");
    $adm->assign("text", "<iframe id=\"contentFreim\" name=\"contentFreim\" src=\"img/empty.gif\" WIDTH=\"100%\" HEIGHT=\"350\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" frameborder=\"0\">
    </iframe>");

    $adm->assignHidden("editor_src", "editor/editor.php?id=$id&type=info&rnd=".randomNumber());
    $adm->assignHidden("editor_reload", "0");
    $adm->assignHidden("submit_to", "0");

    //$adm->assignHidden("show_editor", "0");

    //$adm->assignProp("info", "type", "textfield");
    //$adm->assignProp("info", "cols", "60");
    //$adm->assignProp("info", "rows", "5");

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("cache", "type", "checkbox");
    $adm->assignProp("cache", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("userlogin", "type", "select");
    $adm->assignProp("userlogin", "list", array("0" => $txtf->display("userlogin0"), "1" => $txtf->display("userlogin1")));

    $adm->assignProp("loginform", "type", "select");
    $adm->assignProp("loginform", "list", array("0" => $txtf->display("loginform0"), "1" => $txtf->display("loginform1")));

    $translators = Translator::available();

    // remove gettext line if extension is not loaded
    if (in_array('gettext', $translators) && ('WIN' == strtoupper(substr(PHP_OS, 0, 3))
       || !extension_loaded('gettext')))
    {
        unset($translators[array_search('gettext', $translators)]);
    }

    $adm->assignProp("translator", "type", "select");
    $adm->assignProp("translator", "list", Arrays::array_combine($translators, $translators));

    $adm->assignProp("lang", "type", "select");

    $sq->query($adm->dbc, 'SELECT `language`, `title` FROM `languages`');
    while ($row = $sq->nextrow()) {
        // converting language to code to upper case to emulate previour version of
        // i18n system
        $l_list[strtoupper(htmlspecialchars($row['language']))] = htmlspecialchars($row['title']);
    }
    $sq->free();

    $adm->assignProp("lang", "list", $l_list);
    //$adm->assignProp("lang", "list", array("EE" => $txtf->display("EE"), "EN" => $txtf->display("EN"), "RU" => $txtf->display("RU")));

    $adm->assignProp("logo", "type", "file");
    $adm->assignProp("logo", "extra", "&nbsp;" . $txtf->display("logo_info"));

    //$adm->assignProp("info", "rows", "5");
    $adm->assignProp("descr", "rows", "4");

    $adm->assignProp("sitecache", "type", "select");
    $adm->assignProp("sitecache", "list", array("1" => $txtf->display("sitecache1"), "2" => $txtf->display("sitecache2"), "3" => $txtf->display("sitecache3")));

    $adm->assignProp("debuglevel", "type", "select");
    $adm->assignProp("debuglevel", "list", array("1" => $txtf->display("debuglevel1"), "2" => $txtf->display("debuglevel2"), "3" => $txtf->display("debuglevel3")));


    $adm->assignProp("sitelocale", "extra", $txtf->display("sitelocale_extra"));
    $adm->assignProp("cachetime", "extra", $txtf->display("cachetime_extra"));


}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 50) { $general["max_entries"] = $max_entries; }

if ($_POST["text"] != "") {
    if ($GLOBALS["editor"] == true) {
    /*  $_POST["text"] = preg_replace("/<\/?(HTML|HEAD|TITLE|BODY)>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<\/?(html|head|title|body)>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<META[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<meta[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<link rel[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<BODY[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<body[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<!DOCTYPE[^>]*>\n?/", "", $_POST["text"]);
        //$_POST["text"] = preg_replace("/'/", "&lsquo;", $_POST["text"]);
        $_POST["text"] = preg_replace("/\\\\'/m","'", $_POST["text"]);
        $_POST["text"] = trim($_POST["text"]);
        //$_POST["text"] = $text;*/
        $_POST["text"]=stripslashes($_POST["text"]);//remove slashes (/)
    }
    else {
        $_POST["text"] = strip_tags($_POST["text"]);
    }
}

$info = $_POST["text"];

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

    $adm = new Admin($table);

    $sq = new sql;

    $adm->assign("lastmod", date("Y-m-d H:i:s"));
    $adm->assign("user", $user);

    /* DB writing part */

    if ($do == "update" && $id) {

        // permissions
        $perm->Access (0, 0, "m", "");

        if ($GLOBALS["editor"] == true && $submit_to == "1") {
            $res = 99;
            $adm->getValues();
            $adm->types();
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
        }
        else {

            $do = "";

            // save current translator
            $sq->query($adm->dbc, "SELECT * FROM settings");
            $data = $sq->nextrow();
            $sq->free();
            $old_translator = $data['translator'];

            $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);

            if (0 == $res && $old_translator != $_POST['translator']) {
                // recompile langauge files
                require_once(SITE_PATH . '/class/Locale.php');
                require_once(SITE_PATH . '/class/DbTranslatorCompiler.php');
                require_once(SITE_PATH . '/class/TranslatorCompiler.php');

                $driver = $_POST['translator'];

                // check if there are compiler for currenly selected translator
                if (in_array($driver, TranslatorCompiler::available())) {
                    // create Database instance
                    require_once(SITE_PATH . '/class/Database.php');
                    $sq->con = $adm->dbc;
                    $database = new Database($sq);

                    $c = new DbTranslatorCompiler($database, SITE_PATH . '/' . LANGUAGES_PATH);
                    // set settings
                    foreach ($GLOBALS['translator_settings'] as $adriver => $settings) {
                        if ($adriver == $driver) $c->set_params($settings, $driver);
                    }

                    $c->compile($driver);
                    unset($c, $database);
                }
            }

            // CLEAR SITE CACHE
            if ($cache == 1) {

                $opendir = SITE_PATH . "/cache";
                $files = array();
                if ($dir = @opendir($opendir)) {
                  while (($file = readdir($dir)) !== false) {
                      if (!is_dir("$opendir/$file") && !in_array($file, array('.', '..', '.htaccess'
                           , 'error.log', 'uri-aliases.map')))
                      {
                          $files[] = $file;
                      }
                  }
                  sort($files);
                  reset($files);
                }
                for ($c = 0; $c < sizeof($files); $c++) {
                    @unlink(SITE_PATH . "/cache/" . $files[$c]);
                }
            }

            /**
             * If new logo were submited, check/save it.
             */
            if ($_FILES['logo']['tmp_name'] && $_FILES['logo']['size']) {
                require_once(SITE_PATH . '/class/FileUploader.php');
                // get uploaded file info.
                $file_info = Filenames::pathinfo($_FILES['logo']['name']);
                $file_info['extension'] = strtolower($file_info['extension']);

                if ($file_info['extension'] == 'jpeg') $file_info['extension'] = 'jpg';

                // if uploaded file type is valid process with saveing this logo.
                if (in_array($file_info['extension'], array('gif', 'jpg'))) {

                    // create destination path string.
                    $dest = Filenames::constructPath(
                        'SITELOGO', $file_info['extension']
                        , SITE_PATH . "/" . $GLOBALS["directory"]["upload"]
                    );

                    // remove old SITELOGO files.
                    @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/SITELOGO.gif");
                    @unlink(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/SITELOGO.jpg");

                    // process with logo saving.
                    $file_uploader  = new FileUploader();
                    $logo_saved     = $file_uploader->processUploadedImage(
                        $_FILES['logo']['tmp_name'], $dest, null, null, 'replace', false
                    );

                    if ($logo_saved === FALSE) {
                        trigger_error($file_uploader->getLastError(), E_USER_ERROR);
    //                    trigger_error("Logo upload/copy failed. Check file/folder permissions", E_USER_ERROR);
                        exit;
                    }
                }
            }

            if ($res == 0 && $GLOBALS["editor"] == true && $info) {

                $sq->query($adm->dbc, "SELECT intro FROM intro WHERE language = '$language'");
                if ($sq->numrows == 0) {
                    $sq->query($adm->dbc, "INSERT INTO intro VALUES('$language', '" . addslashes($info) . "')");
                }
                else {
                    $sq->query($adm->dbc, "UPDATE intro SET intro = '" . addslashes($info) . "' WHERE language = '$language'");
                }

                //$tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
                //echo '<body onLoad= "top.main.left.document.location=\'content_navi.php?structure=' . $structure . '\'">';
            }
            else {
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
                $adm->getValues();
                $adm->types();
                external();
                $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
            }

        }
    }
    /* end DB writing part */

    if ($show == "modify" && $id && $do != "update") {

        // permissions
        $perm->Access (0, 0, "m", "");

        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }


if ($show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->display("add"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->display("modify"));
    $active_tab = 2;
}

$nr = 1;
while(list($key, $val) = each($tabs)) {
    $tpl->addDataItem("TABS.ID", $nr);
    $tpl->addDataItem("TABS.URL", "javascript:enableFieldset($nr, 'fieldset$key', '', ".sizeof($tabs).", ".sizeof($field_groups).");");
    $tpl->addDataItem("TABS.NAME", $val);
    if ($key == 1) {
        $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
    }
    $nr++;
}

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();