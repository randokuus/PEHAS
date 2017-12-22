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
$txtf = new Text($language2, "module_isic_card_type");

// ##############################################################
// ##############################################################

$table = "module_isic_card_type"; // SQL table name to be administered

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
    "sort" => "name ASC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "name" => $txtf->display("name"),
    "short_name" => $txtf->display("short_name"),
    "prefix" => $txtf->display("prefix"),
    "code" => $txtf->display("code"),
    "priority" => $txtf->display("priority"),
    "expiration_year" => $txtf->display("expiration_year"),
    "expiration_break" => $txtf->display("expiration_break"),
    "expiration_break_day" => $txtf->display("expiration_break_day"),
    "expiration_type" => $txtf->display("expiration_type"),
    "expiration_repl_card" => $txtf->display("expiration_repl_card"),
    "prolong_limit" => $txtf->display("prolong_limit"),
    "should_return_in" => $txtf->display("should_return_in"),
    "number_type" => $txtf->display("number_type"),
    "use_school_code" => $txtf->display("use_school_code"),
    "auto_export" => $txtf->display("auto_export"),
    "order_not_joined_schools" => $txtf->display("order_not_joined_schools"),
    "payment_required" => $txtf->display("payment_required"),
    "collateral_sum" => $txtf->display("collateral_sum"),
    "collateral_free_days_until_expiration" => $txtf->display("collateral_free_days_until_expiration"),
    "description" => $txtf->display("description"),
    "pic" => $txtf->display("pic"),
    "benefit_url" => $txtf->display("benefit_url"),
    "conditions_url" => $txtf->display("conditions_url"),
    "conditions" => $txtf->display("conditions"),
    "binded_types" => $txtf->display("binded_types"),
    "age_restricted" => $txtf->display("age_restricted"),
    "age_lower_bound" => $txtf->display("age_lower_bound"),
    "age_upper_bound" => $txtf->display("age_upper_bound"),
    "age_upper_bound_deact" => $txtf->display("age_upper_bound"),
    "chip" => $txtf->display("chip"),
    "tryb_export_name_split" => $txtf->display("tryb_export_name_split"),
    "order_for_others_allowed" => $txtf->display("order_for_others"),
    "person_email_required" => $txtf->display("person_email_required"),
    "ccdb_name" => $txtf->display("ccdb_name"),
    "picture_expiration" => $txtf->display("picture_expiration"),
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
    "name",
    "short_name",
    "prefix",
    "code",
    "priority",
    "expiration_year",
    "expiration_break",
    "expiration_break_day",
    "expiration_type",
    "expiration_repl_card",
    "prolong_limit",
    "should_return_in",
    "number_type",
    "payment_required",
    "use_school_code",
    "auto_export",
    "collateral_sum",
    "description",
    "pic",
    "benefit_url",
    "conditions_url",
    "conditions",
    "binded_types",
    "order_not_joined_schools",
    "collateral_free_days_until_expiration",
    'age_restricted',
    "age_lower_bound",
    "age_upper_bound",
    "age_upper_bound_deact",
    'chip',
    "tryb_export_name_split",
    'order_for_others_allowed',
    'person_email_required',
    'ccdb_name',
    'picture_expiration'
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "name" => $txtf->display("name"),
    "prefix" => $txtf->display("prefix"),
    "code" => $txtf->display("code"),
    "expiration_year" => $txtf->display("expiration_year"),
    "expiration_break" => $txtf->display("expiration_break"),
    "expiration_break_day" => $txtf->display("expiration_break_day"),
    "expiration_type" => $txtf->display("expiration_type"),
    "expiration_repl_card" => $txtf->display("expiration_repl_card"),
    "prolong_limit" => $txtf->display("prolong_limit"),
    "should_return_in" => $txtf->display("should_return_in"),
    "number_type" => $txtf->display("number_type"),
    "payment_required" => $txtf->display("payment_required"),
    "collateral_free_days_until_expiration" => $txtf->display("collateral_free_days_until_expiration"),
    "use_school_code" => $txtf->display("use_school_code"),
    "auto_export" => $txtf->display("auto_export"),
    "priority" => $txtf->display("priority"),
);

/* required fields */
$required = array(
    "name",
    "expiration_year",
    "number_type",
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
        "$table.name"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    $list = array();
    for ($c = 1; $c < 3; $c++) {
        $list[$c] = $txtf->display("number_type" . $c);
    }
    $adm->assignProp("number_type", "type", "select");
    $adm->assignProp("number_type", "list", $list);

    $adm->assignProp("expiration_type", "type", "select");
    $adm->assignProp("expiration_type", "list", array("0" => $txtf->display("type0"), "1" => $txtf->display("type1")));

    $adm->assignProp("expiration_repl_card", "type", "select");
    $adm->assignProp("expiration_repl_card", "list", array("0" => $txtf->display("expiration_repl_card0"), "1" => $txtf->display("expiration_repl_card1")));

    $adm->assignProp("payment_required", "type", "checkbox");
    $adm->assignProp("payment_required", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("use_school_code", "type", "checkbox");
    $adm->assignProp("use_school_code", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("auto_export", "type", "checkbox");
    $adm->assignProp("auto_export", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));
    $adm->assignProp("auto_export", "extra", $txtf->display("auto_export_extra"));

    $adm->assignProp("order_not_joined_schools", "type", "checkbox");
    $adm->assignProp("order_not_joined_schools", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("age_restricted", "type", "checkbox");
    $adm->assignProp("age_restricted", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("age_upper_bound", "extra", $txtf->display("used_for_ordering"));
    $adm->assignProp("age_upper_bound_deact", "extra", $txtf->display("used_for_deactivation"));

    $adm->assignProp("chip", "type", "checkbox");
    $adm->assignProp("chip", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("tryb_export_name_split", "type", "checkbox");
    $adm->assignProp("tryb_export_name_split", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("order_for_others_allowed", "type", "checkbox");
    $adm->assignProp("order_for_others_allowed", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("person_email_required", "type", "checkbox");
    $adm->assignProp("person_email_required", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("description", "rows", "10");
    $adm->assignProp("description", "cols", "80");

    $adm->assignProp("conditions", "rows", "10");
    $adm->assignProp("conditions", "cols", "80");

    $adm->assignProp("binded_types", "type", "select2");
    $adm->assignProp("binded_types", "size", 10);
    $adm->assignExternal("binded_types", "module_isic_card_type", "module_isic_card_type.id", "module_isic_card_type.name", "ORDER BY module_isic_card_type.name", false);

    $adm->displayButtons("pic");
    $adm->assignProp("pic","type","onlyhidden");
    $prod_image = $adm->fields["pic"]["value"];
    if ($prod_image != "") {
        $adm->assignProp("pic", "extra", "
        <table border=0 cellpadding=0 cellspacing=0>
        <tr valign=top><td><div align=\"left\" id=\"newspic\"><img src=\"" . $prod_image . "\" border=0></div></td>
        <td>&nbsp;&nbsp;</td>
        <td><button type=button onClick=\"newWindow('editor/Inc/insimage1.php',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_choose"))."</button>
        <button type=button onClick=\"javascript:clearPic();\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_del"))."</button>
        </td></tr></table>");
    } else {
        $adm->assignProp("pic", "extra", "
        <table border=0 cellpadding=0 cellspacing=0>
        <tr valign=top><td><div align=\"left\" id=\"newspic\">&nbsp;</div></td>
        <td>&nbsp;&nbsp;</td>
        <td><button type=button onClick=\"newWindow('editor/Inc/insimage1.php',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_choose"))."</button>
        <button type=button onClick=\"javascript:clearPic();\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_del"))."</button>
        </td></tr></table>");
    }

    $adm->assignProp("picture_expiration", "extra", $txtf->display("picture_expiration_extra"));
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
