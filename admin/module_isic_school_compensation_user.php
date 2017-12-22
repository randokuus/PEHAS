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

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_isic_school_compensation");

// ##############################################################
// ##############################################################

$table = "module_isic_school_compensation_user"; // SQL table name to be administered

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
    "sort" => "start_date DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "active" => $txtf->display("active"),
    "compensation_id" => $txtf->display("school_compensation"),
    "person_number" => $txtf->display("person_number"),
    "school_id" => $txtf->display("school"),
    "status_id" => $txtf->display("status"),
    "start_date" => $txtf->display("start_date"),
    "end_date" => $txtf->display("end_date"),
    "compensation_type_list" => $txtf->display("compensation_type_list"),
    "application_type_list" => $txtf->display("application_type_list"),
    "sum" => $txtf->display("sum"),
    "sum_used" => $txtf->display("sum_used"),
    "currency" => $txtf->display("currency"),
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
    'active',
    'compensation_id',
    'person_number',
    "school_id",
    "status_id",
    "start_date",
    "end_date",
    "sum",
    'sum_used',
    "currency",
    "compensation_type_list",
    "application_type_list"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    'person_number' => $txtf->display("person_number"),
    "school_id" => $txtf->display("school"),
    "status_id" => $txtf->display("status"),
    "start_date" => $txtf->display("start_date"),
    "end_date" => $txtf->display("end_date"),
    "sum" => $txtf->display("sum"),
    "sum_used" => $txtf->display("sum_used"),
    "currency" => $txtf->display("currency"),
    "active" => $txtf->display("active"),
);

/* required fields */
$required = array(
    'compensation_id',
    'person_number',
    "school_id",
    "status_id",
    "start_date",
    "end_date",
    "sum",
    "currency",
    "compensation_type_list",
    "application_type_list"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "$table.person_number",
        "$table.start_date",
        "$table.end_date",
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    $adm->assignProp("compensation_id", "type", "select");
    $adm->assignExternal("compensation_id", "module_isic_school_compensation", "module_isic_school_compensation.id",
        "CONCAT(
            module_isic_school.name,
            ' / ',
            module_user_status.name,
            ' / ',
            module_isic_school_compensation.start_date,
            ' - ',
            module_isic_school_compensation.end_date,
            ' / ',
            module_isic_school_compensation.length_in_years,
            ' years',
            ' (id: ', module_isic_school_compensation.id, ')')
            AS comp_name",
        " LEFT JOIN
            module_isic_school ON module_isic_school_compensation.school_id = module_isic_school.id
          LEFT JOIN
            module_user_status ON module_isic_school_compensation.status_id = module_user_status.id
          ORDER BY comp_name", true);

    $adm->assignProp("school_id", "type", "select");
    $adm->assignExternal("school_id", "module_isic_school", "id", "name", " ORDER BY name", true);

    $adm->assignProp("status_id", "type", "select");
    $adm->assignExternal("status_id", "module_user_status", "id", "name", " ORDER BY name", true);

    $adm->assignProp("compensation_type_list", "type", "select2");
    $adm->assignProp("compensation_type_list", "size", "2");
    $adm->assignProp("compensation_type_list", "list", array("2" => $txtf->display("cost"), "3" => $txtf->display("delivery")));

    $adm->assignProp("application_type_list", "type", "select2");
    $adm->assignProp("application_type_list", "size", "3");
    $adm->assignExternal("application_type_list", "module_isic_application_type", "id", "name", " ORDER BY name", false);

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));
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
    else if (!$res || $res == 0) {
        external();
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

if ($show == "add" || ($do == "add" && is_array($res))) {
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
