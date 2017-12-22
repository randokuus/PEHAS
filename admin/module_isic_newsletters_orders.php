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
require_once(SITE_PATH . '/class/IsicReport/IsicReport_NewsletterOrders.php');


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

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_isic_newsletters_orders");

// ##############################################################
// ##############################################################

$table = "module_isic_newsletters_orders"; // SQL table name to be administered

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
    "max_entries" => 50,
    //"sort" => "event_time DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

if ($show == "csv_report") {
    $general["button"] = $txt->display("button_csv");
}

if (isset($csv_report)) {
    $repoInst = new IsicReport_NewsletterOrders($database);
    $repoInst->getReport($start_time, $end_time);
}

/* the fields in the table */
$fields = array(
    "user" => $txtf->display("user"),
    "newsletter_id" => $txtf->display("name"),
    "active" => $txtf->display("active"),
    "mod_date" => $txtf->display("mod_date"),
    "mod_user" => $txtf->display("mod_user"),
);

$tabs = array(
    1 => array($txt->display("generate_as_csv"), $_SERVER["PHP_SELF"]."?show=csv_report"),
    2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "user",
    "newsletter_id",
    "active",
    "mod_date",
    "mod_user",
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    "user" => $txtf->display("user"),
    "usercode" => $txtf->display("usercode"),
    "newsletter_id" => $txtf->display("name"),
    "active" => $txtf->display("active"),
    "mod_date" => $txtf->display("mod_date"),
    "mod_user" => $txtf->display("mod_user"),

);

/* required fields */
$required = array();

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "`module_user_users`.`user_code` as `usercode`",
    );

    $from = array(
    "{$table} JOIN `module_user_users` ON `{$table}`.`user`=`module_user_users`.`user`
              JOIN `module_isic_newsletters` ON `{$table}`.`newsletter_id`=`module_isic_newsletters`.`id`",
    );

/*
    $where = "language = '$language'";
*/

    $filter_fields = array("`module_isic_newsletters`.`name`", "`module_user_users`.`name_first`", "`module_user_users`.`name_last`", "`module_user_users`.`user_code`");


 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    $adm->assignProp("newsletter_id", "type", "select");
    $adm->assignExternal("newsletter_id", "module_isic_newsletters", "id", "name", "", false);
    $adm->assignProp("user", "type", "select");
    $adm->assignExternal("user", "module_user_users", "user", "CONCAT(name_first,' ',name_last)", "", true);
    $adm->assignProp("mod_user", "type", "select");
    $adm->assignExternal("mod_user", "module_user_users", "user", "CONCAT(name_first,' ',name_last)", "", true);
    $adm->fields["mod_date"]["list"]["0000-00-00 00:00:00"] = "";
    $adm->assignProp("name", "size", "30");
    $adm->assignProp("card_types", "type", "select2");
    $adm->assignProp("card_types", "size", "30");
    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));
}

function external_csv() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;

    $adm->assignProp("start_time", "type", "textinput");
    $adm->assignProp("end_time", "type", "textinput");
    $adm->assignHelper("start_time", "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=1&field=start_time', 270, 250);");
    $adm->assignHelper("end_time", "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=1&field=end_time', 270, 250);");
    $adm->assignProp("csv_report", "type", "nothing");
    //$adm->assignProp("csv_report", "value", "");
    //$adm->assignProp("csv_report", "display", "none");
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

    //$adm->assign("lastmod", date("Y-m-d H:i:s"));
    //$adm->assign("user", $user);
    //$adm->assign("language", $language);

    /* DB writing part */
    if ($do == "add") {

        // permissions
        $perm->Access (0, 0, "a", "isic");
        $res = $adm->add($table, $required, $idfield);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\""); }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "update" && $id) {
        // permissions
        $perm->Access (0, $id, "m", "isic");

        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\""); }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "delete" && $id) {

        // permissions
        $perm->Access (0, $id, "d", "isic");

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\""); }
        else { $result = $general["error"]; }
    }
    /* end DB writing part */

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "isic");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "isic");
        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "csv_report") {

       $general["button"] = $txt->display("button");
       $fields = array(
         "start_time" => $txtf->display("start_time"),
         "end_time" => $txtf->display("end_time"),
         "csv_report" => "",
        );
        // permissions
        //$perm->Access (0, $id, "m", "isic");
        //$adm->fillValues($table, $idfield, $id);
        $adm->types();
        external_csv();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if (!$res || $res == 0) {
        external();
        if ($filter) {
            $where = "";
            foreach ($filter_fields as $fIndex => $fField) {
                $where .= ($fIndex?" OR":""). $fField. " LIKE '%{$filter}%'";
            }
            $filter = false;
        }
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

if ($show == "add" || ($do == "add" && is_array($res) || $show == "csv_report")) {
    $tpl->addDataItem("TITLE", $txtf->display("module_title"));
    $active_tab = 1;
}
else {
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
        }
        else {
            $tpl->addDataItem("TABS.CLASS", "class=\"\"");
        }
    $nr++;
}

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();
