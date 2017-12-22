<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once("../class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . '/class/'.DB_TYPE.'.class.php');
require_once(SITE_PATH . '/class/admin.session.class.php');
require_once(SITE_PATH . '/class/admin.language.class.php');
require_once(SITE_PATH . '/class/text.class.php');
require_once(SITE_PATH . '/class/admin.class.php');             // administration main object
require_once(SITE_PATH . '/class/adminfields.class.php'); // form fields definitions for admin
require_once(SITE_PATH . '/class/templatef.class.php');  // site default template object
require_once(SITE_PATH . '/class/Database.php');

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

//error_reporting(E_ALL);
// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_datafeed");

// ##############################################################
// ##############################################################

$table = "module_datafeed"; // SQL table name to be administered
$idfield = "id";            // name of the id field (unique field, usually simply 'id')

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
    "max_entries" => 50,
    "sort" => "id DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "feed_name" => $txtf->display("feed_name"),
    "feed_title" => $txtf->display("feed_title"),
    "feed_source" => $txtf->display("feed_source"),
    "feed_group" => $txtf->display("feed_group"),
    "feed_type" => $txtf->display("feed_type"),
    "password" => $txtf->display("password"),
    "active" => $txtf->display("active")
);

$tabs = array(
    1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "feed_name",
    "feed_title",
    "feed_source",
    "feed_group",
    "feed_type",
    "active"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    "feed_name" => $txtf->display("feed_name"),
    "feed_source" => $txtf->display("feed_source"),
    //"feed_group" => $txtf->display("feed_group"),
    "feed_type" => $txtf->display("feed_type"),
    "active" => $txtf->display("active")
);

/* required fields */
$required = array(
    "feed_name",
    "feed_source",
//  "feed_group",
    "feed_type"
 );


    /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/
    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    $where = "";

    $filter_fields = array(
        "$table.feed_name"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $fields, $upd_fields;
    $sq = new sql;

    if ($show == "modify") {
        $adm->assign("password", "");
    }

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("feed_group", "type", "select");
    $adm->assignProp("password", "type", "password");

    $adm->assignProp("feed_source", "type", "select");
    $adm->assignProp("feed_source", "list", array( "site_menu" => $txtf->display("source_sitemenu"), "news" => $txtf->display("source_news")));
    $adm->assignProp("feed_source", "java", "onChange=\"submitTo();\"");

    $adm->assignProp("feed_type", "type", "select");

    if($adm->fields['feed_source']['value'] == "news" || $show == "")   {

        $adm->assignProp("feed_type", "list", array(1 => $txtf->display("xml_feed"), 2 => $txtf->display("rss_feed")));
    }
    else {
        $adm->assignProp("feed_type", "list", array(1 => $txtf->display("xml_feed")));
    }

    if($adm->fields['feed_source']['value'] == "news")
    {

        $news_groups = array("" => "- - -");
        $sq->query($adm->dbc, "SELECT * FROM module_news_groups");
        while($data = $sq->nextrow())
        {
            $news_groups[$data['id']] = $data['name'];
        }
        $adm->assignProp("feed_group", "list", $news_groups);
    } else {
        unset($fields['feed_title']);
        unset($upd_fields['feed_title']);
    }


    $adm->assignHidden("submit_to", "0");

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

    $adm = new Admin($table);
    $adm->assignProp("password", "type", "password");
    $sq = new sql;

    $adm->assign("language", $language);

    /* DB writing part */
    if ($do == "add") {
        // permissions
        $perm->Access (0, 0, "a", "datafeed");

        if ($_REQUEST["submit_to"] == "1") {
            $res = 99;
            $adm->getValues();
            $adm->types();
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
        }
        else {

            $tmp_name = $adm->values['feed_name'];

            $sq->query($adm->dbc, "SELECT id FROM module_datafeed WHERE feed_name = '".addslashes($tmp_name)."'");
            if($sq->numrows > 0 )
            {
                $adm->values['feed_name'] = "";
                $adm->general["required_error"] = $txtf->display("feed_exists");
            }

            $res = $adm->add($table, $required, $idfield);

            if ($res == 0) {
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
            } else {
                $show = "add";
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
                $adm->getValues();
                $adm->types();
                external();
                $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
            }

        }

    }   else if ($do == "update" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "datafeed");

        if ($_POST["password"]) $upd_fields[] = "password";

        if ($_REQUEST["submit_to"] == "1") {
            $res = 99;
            $adm->getValues();
            $adm->types();
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
        }
        else {

            $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
            if ($res == 0) {
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
            }
            else {
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
                $adm->getValues();
                $adm->types();
                external();
                $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
            }
        }

    } else if ($do == "delete" && $id) {

        // permissions
        $perm->Access (0, $id, "d", "datafeed");
        $res = $adm->delete($table, $idfield, $id);

        $res = 0;
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
         }
        else {
            $result = $general["error"];
        }
    }
    /* end DB writing part */

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "datafeed");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    } else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "datafeed");

        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    } else if (!$res || $res == 0) {
        // permissions
        $perm->Access (0, 0, "m", "datafeed");

        external();
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

if ($show == "add" || ($do == "add" && $res)) {
    $tpl->addDataItem("TITLE", $txtf->display("module_title"));
    $active_tab = 1;
} else {
    $tpl->addDataItem("TITLE", $txtf->display("module_title"));
    $active_tab = 2;
}


$nr = 1;
while(list($key, $val) = each($tabs)) {
    $tpl->addDataItem("TABS.ID", $nr);
    $tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
    $tpl->addDataItem("TABS.NAME", $val[0]);
        if ($active_tab == $nr) {
            $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
        } else {
            $tpl->addDataItem("TABS.CLASS", "class=\"\"");
        }
    $nr++;
}

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";
$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();
