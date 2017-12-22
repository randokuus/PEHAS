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
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . '/class/IsicReport/IsicReport_ApplicationCampaigns.php');

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
$bind_user = $ses->getBindUser();
//$bind_user = $user;
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
$txtf = new Text($language2, "module_isic_card");

// ##############################################################
// ##############################################################

$table = "module_isic_application"; // SQL table name to be administered
$table_log = "module_isic_application_log"; // SQL table name for log

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
    "sort" => "adddate DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

if ($show == "csv_report") {
    $general["button"] = $txt->display("button_csv");
}

if (isset($csv_report)) {
    $repoInst = new IsicReport_ApplicationCampaigns($database);
    $repoInst->getReport($start_time, $end_time);
}

/* the fields in the table */
$fields = array(
    "application_type_id" => $txtf->display("application_type"),
    "card_id" => $txtf->display("card"),
    "state_id" => $txtf->display("state"),
    "user_step" => $txtf->display("user_step"),
    "language_id" => $txtf->display("language"),
    "kind_id" => $txtf->display("kind"),
    "bank_id" => $txtf->display("bank"),
    "type_id" => $txtf->display("type"),
    "school_id" => $txtf->display("school"),
    "person_name_first" => $txtf->display("person_name_first"),
    "person_name_last" => $txtf->display("person_name_last"),
    "person_birthday" => $txtf->display("person_birthday"),
    "person_number" => $txtf->display("person_id"),
    "delivery_id" => $txtf->display("delivery"),
    "delivery_addr1" => $txtf->display("delivery_addr1"),
    "delivery_addr2" => $txtf->display("delivery_addr2"),
    "delivery_addr3" => $txtf->display("delivery_addr3"),
    "delivery_addr4" => $txtf->display("delivery_addr4"),
    "person_email" => $txtf->display("person_email"),
    "person_phone" => $txtf->display("person_phone"),
    "person_position" => $txtf->display("person_position"),
    "person_class" => $txtf->display("person_class"),
    "person_stru_unit" => $txtf->display("person_structure_unit"),
    "person_stru_unit2" => $txtf->display("person_structure_unit2"),
    "person_staff_number" => $txtf->display("person_staff_number"),
    "person_bankaccount" => $txtf->display("person_bankaccount"),
    "person_bankaccount_name" => $txtf->display("person_bankaccount_name"),
    "person_newsletter" => $txtf->display("person_newsletter"),
    "expiration_date" => $txtf->display("expiration_date"),
    "pic" => $txtf->display("photo"),
    "adddate" => $txtf->display("adddate"),
    "adduser" => $txtf->display("adduser"),
    "moddate" => $txtf->display("moddate"),
    "moduser" => $txtf->display("moduser"),
    "exported" => $txtf->display("exported"),
    "agree_user" => $txtf->display("agree_user"),
    "confirm_user" => $txtf->display("confirm_user"),
    "confirm_payment_collateral" => $txtf->display("confirm_payment_collateral"),
    "collateral_sum" => $txtf->display("collateral_sum"),
    "will_return_card" => $txtf->display("will_return_card"),
    "confirm_payment_cost" => $txtf->display("confirm_payment_cost"),
    "cost_sum" => $txtf->display("cost_sum"),
    "compensation_sum" => $txtf->display("compensation_sum"),
    "confirm_payment_delivery" => $txtf->display("confirm_payment_delivery"),
    "delivery_sum" => $txtf->display("delivery_sum"),
    "compensation_sum_delivery" => $txtf->display("compensation_sum_delivery"),
    "confirm_admin" => $txtf->display("confirm_admin"),
    "currency" => $txtf->display("currency"),
    "reject_reason_id" => $txtf->display("reject_reason"),
    "reject_reason_text" => $txtf->display("reject_reason_text"),
    "user_request_date" => $txtf->display("user_request_date"),
    "payment_started" => $txtf->display("payment_started"),
    "campaign_code" => $txtf->display("campaign_code"),
    "order_for_others" => $txtf->display("order_for_others"),
    "parent_user_id" => $txtf->display("parent_user_id"),
);

$tabs = array(
    1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txt->display("modify"), $_SERVER["PHP_SELF"]),
    3 => array($txt->display("generate_as_csv"), $_SERVER["PHP_SELF"]."?show=csv_report"),
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "application_type_id",
    "card_id",
    "state_id",
    "language_id",
    "kind_id",
    "bank_id",
    "type_id",
    "school_id",
    "person_name_first",
    "person_name_last",
    "person_birthday",
    "person_number",
    "person_email",
    "person_phone",
    "person_position",
    "person_class",
    "person_stru_unit",
    "person_stru_unit2",
    "person_staff_number",
    "person_bankaccount",
    "person_bankaccount_name",
    "person_newsletter",
    "expiration_date",
    "pic",
    "adddate",
    "adduser",
    "moddate",
    "moduser",
    "exported",
    "confirm_user",
    "confirm_payment_collateral",
    "confirm_payment_cost",
    "confirm_admin",
    "user_step",
    "agree_user",
    "collateral_sum",
    "will_return_card",
    "cost_sum",
    'compensation_sum',
    "reject_reason_id",
    "reject_reason_text",
    "user_request_date",
    "currency",
    "delivery_id",
    "delivery_addr1",
    "delivery_addr2",
    "delivery_addr3",
    "delivery_addr4",
    "delivery_sum",
    "confirm_payment_delivery",
    'payment_started',
    "campaign_code",
    'parent_user_id',
    'order_for_others',
    'compensation_sum_delivery'
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "person_pic" => $txtf->display("pic"),
    "state_id" => $txtf->display("state"),
    "school_id" => $txtf->display("school"),
    "type_id" => $txtf->display("type"),
    "person_name_first" => $txtf->display("person_name_first"),
    "person_name_last" => $txtf->display("person_name_last"),
    "person_number" => $txtf->display("person_id"),
    "kind_id" => $txtf->display("kind"),
    "bank_id" => $txtf->display("bank"),
    "exported" => $txtf->display("exported"),
    "confirm_user" => $txtf->display("confirm_user"),
    "confirm_payment_collateral" => $txtf->display("confirm_payment_collateral"),
    "confirm_payment_cost" => $txtf->display("confirm_payment_cost"),
    "confirm_admin" => $txtf->display("confirm_admin"),
    "user_step" => $txtf->display("user_step"),
    "application_type_id" => $txtf->display("application_type"),
);

/* required fields */
$required = array(
    "state_id",
    "language_id",
    "kind_id",
    "type_id",
    "school_id",
    "person_name_first",
    "person_name_last",
    "person_birthday",
    "person_number",
//    "person_email",
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "IF (" . $table . ".pic <> '', CONCAT('<img src=\"" . SITE_URL . "', REPLACE(" . $table . ".pic,'.jpg','_thumb.jpg'), '\" border=\"0\">'), '') AS person_pic"
    );
    $from = array(
        $table
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "CONCAT($table.person_name_first, ' ', $table.person_name_last)",
        "$table.person_name_first",
        "$table.person_name_last",
        "$table.person_number",
        "$table.campaign_code"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $table, $school_id, $type_id, $exported_filter, $bind_user;
    //$sq = new sql;

    if ($show == "add") {
        $adm->assign("adddate", date("Y-m-d H:i:s"));
        $adm->assign("moddate", date("Y-m-d H:i:s"));
    }

    $person_number = $adm->fields["person_number"]["value"];

    $adm->assignProp("card_id", "type", "select");
    $adm->assignExternal("card_id", "module_isic_card", "id", "isic_number", " WHERE module_isic_card.person_number = '{$person_number}' ORDER BY isic_number", true);

    if ($adm->fields['adduser']['value']) {
        $addUserId = $adm->fields['adduser']['value'];
    } else {
        $addUserId = $bind_user;
    }

    if ($adm->fields['moduser']['value']) {
        $modUserId = $adm->fields['moduser']['value'];
    } else {
        $modUserId = $bind_user;
    }

    $adm->assignProp("adduser", "type", "select");
    $adm->assignExternal("adduser", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", " WHERE user = {$addUserId} ORDER BY name", false);

    $adm->assignProp("moduser", "type", "select");
    $adm->assignExternal("moduser", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", " WHERE user = {$modUserId} ORDER BY name", false);

    if ($adm->fields['parent_user_id']['value']) {
        $parentUserId = $adm->fields['parent_user_id']['value'];
    } else {
        $parentUserId = 0;
    }

    $adm->assignProp("parent_user_id", "type", "select");
    $adm->assignExternal("parent_user_id", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", " WHERE user = {$parentUserId} ORDER BY name", false);

    $adm->displayOnly("adddate");
    $adm->displayOnly("moddate");
    $adm->displayOnly("adduser");
    $adm->displayOnly("moduser");
    $adm->displayOnly("expiration_date");

    $adm->assignProp("will_return_card", "type", "checkbox");
    $adm->assignProp("will_return_card", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("agree_user", "type", "checkbox");
    $adm->assignProp("agree_user", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("confirm_user", "type", "checkbox");
    $adm->assignProp("confirm_user", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("confirm_payment_collateral", "type", "checkbox");
    $adm->assignProp("confirm_payment_collateral", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("confirm_payment_cost", "type", "checkbox");
    $adm->assignProp("confirm_payment_cost", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("confirm_payment_delivery", "type", "checkbox");
    $adm->assignProp("confirm_payment_delivery", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("confirm_admin", "type", "checkbox");
    $adm->assignProp("confirm_admin", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("order_for_others", "type", "checkbox");
    $adm->assignProp("order_for_others", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("application_type_id", "type", "select");
    $adm->assignExternal("application_type_id", "module_isic_application_type", "id", "name", " ORDER BY name", true);

    $adm->assignProp("state_id", "type", "select");
    $adm->assignExternal("state_id", "module_isic_application_state", "id", "name", " ORDER BY name", false);

    $adm->assignProp("person_newsletter", "type", "checkbox");
    $adm->assignProp("person_newsletter", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("kind_id", "type", "select");
    $adm->assignExternal("kind_id", "module_isic_card_kind", "id", "name", " ORDER BY name", false);

    $adm->assignProp("bank_id", "type", "select");
    $adm->assignExternal("bank_id", "module_isic_bank", "id", "name", " ORDER BY name", true);

    $adm->assignProp("type_id", "type", "select");
    $adm->assignExternal("type_id", "module_isic_card_type", "id", "name", " ORDER BY name", true);
    $fdata = $adm->fields["type_id"];
    $f = new AdminFields("type_id", $fdata);
    $form_select = $f->display($type_id);
    if ($type_id) {
        $adm->assignFilter("type_id", $type_id, "$table.type_id = '" . addslashes($type_id) . "'", $form_select);
    }
    else {
        $adm->assignFilter("type_id", "", "", $form_select);
    }
    $adm->assignExternal("type_id", "module_isic_card_type", "id", "name", " ORDER BY name", false);

    $adm->assignProp("language_id", "type", "select");
    $adm->assignExternal("language_id", "module_isic_card_language", "id", "name", " ORDER BY name", false);

    $adm->assignProp("reject_reason_id", "type", "select");
    $adm->assignExternal("reject_reason_id", "module_isic_application_reject_reason", "id", "name", " ORDER BY name", true);

    $adm->assignProp("school_id", "type", "select");
    $adm->assignExternal("school_id", "module_isic_school", "id", "name", " ORDER BY name", true);
    $fdata = $adm->fields["school_id"];
    $f = new AdminFields("school_id", $fdata);
    $form_select = $f->display($school_id);
    if ($school_id) {
        $adm->assignFilter("school_id", $school_id, "$table.school_id = '" . addslashes($school_id) . "'", $form_select);
    }
    else {
        $adm->assignFilter("school_id", "", "", $form_select);
    }
    $adm->assignExternal("school_id", "module_isic_school", "id", "name", " ORDER BY name", false);

    $adm->assignProp("delivery_id", "type", "select");
    $adm->assignExternal("delivery_id", "module_isic_card_delivery", "id", "name", " ORDER BY name", true);
    $adm->assignProp("delivery_addr1", "extra", $txtf->display("delivery_extra"));
    $adm->assignProp("delivery_addr2", "extra", $txtf->display("delivery_extra"));
    $adm->assignProp("delivery_addr3", "extra", $txtf->display("delivery_extra"));
    $adm->assignProp("delivery_addr4", "extra", $txtf->display("delivery_extra"));

    $adm->assignProp("exported_filter", "type", "select");
    $adm->assignProp("exported_filter", "list", array("0" => $txtf->display("all"), "1" => $txtf->display("not_exported"), "2" => $txtf->display("exported")));

    $fdata = $adm->fields["exported_filter"];
    $f = new AdminFields("exported_filter", $fdata);
    $form_select = $f->display($exported_filter);
    if ($exported_filter == 1) {
        $adm->assignFilter("exported_filter", $exported_filter, "$table.exported = '0000-00-00 00:00:00'", $form_select);
    } elseif ($exported_filter == 2) {
        $adm->assignFilter("exported_filter", $exported_filter, "$table.exported <> '0000-00-00 00:00:00'", $form_select);
    } else {
        $adm->assignFilter("exported_filter", "", "", $form_select);
    }

    $adm->assignProp("payment_started", "type", "checkbox");
    $adm->assignProp("payment_started", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));
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
        $isic = IsicCommon::getInstance();

        $res = $adm->add($table, $required, $idfield);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

            $added_id = $sq->insertID();
            $isic->saveApplicationChangeLog($isic->log_type_add, $added_id, array(), $isic->getApplicationRecord($added_id), $bind_user);

        } else {
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

        $isic = IsicCommon::getInstance();
        $row_old = $isic->getApplicationRecord($id);
        $adm->values['moddate'] = date("Y-m-d H:i:s");
        $adm->values['moduser'] = $bind_user;

        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
            $isic->saveApplicationChangeLog($isic->log_type_mod, $id, $row_old, $isic->getApplicationRecord($id), $bind_user);
        }
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

        $isic = IsicCommon::getInstance();
        $row_old = $isic->getApplicationRecord($id);

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
            $isic->saveApplicationChangeLog($isic->log_type_del, $id, $row_old, array(), $bind_user);
        } else {
            $result = $general["error"];
        }
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
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

$tpl->addDataItem("TITLE", $txtf->display("module_title_application"));
if ($show == "add" || ($do == "add" && is_array($res))) {
    $active_tab = 1;
} else if ($show == "csv_report") {
    $active_tab = 3;
}
else {
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
