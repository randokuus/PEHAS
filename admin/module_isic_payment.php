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
$txtf = new Text($language2, "module_isic_payment");

// ##############################################################
// ##############################################################

$table = "module_isic_payment"; // SQL table name to be administered

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

/* the fields in the table */
$fields = array(
    "prev_id" => $txtf->display("previous_payment"),
    "adddate" => $txtf->display("adddate"),
    "application_id" => $txtf->display("application"),
    "card_id" => $txtf->display("card"),
//    "active" => $txtf->display("active"),
    "free" => $txtf->display("free"),
    "person_number" => $txtf->display("person_number"),
    "type_id" => $txtf->display("card_type"),
    "deposit_id" => $txtf->display("bank_payment"),
    "payment_type" => $txtf->display("payment_type"),
    "payment_sum" => $txtf->display("payment_sum"),
    "currency" => $txtf->display("currency"),
    "payment_method" => $txtf->display("payment_method"),
    "bank_id" => $txtf->display("bank"),
//    "rejected" => $txtf->display("rejected"),
//    "returned" => $txtf->display("returned"),
    "should_share" => $txtf->display("should_share"),
//    "expired" => $txtf->display("expired"),
    "payment_returned" => $txtf->display("payment_returned"),
//    "rejected_date" => $txtf->display("rejected_date"),
//    "returned_date" => $txtf->display("returned_date"),
    "should_share_date" => $txtf->display("should_share_date"),
//    "expired_date" => $txtf->display("expired_date"),
    "autoreturn" => $txtf->display("autoreturn"),
    "autoreturn_date" => $txtf->display("autoreturn_date"),
    "payment_returned_date" => $txtf->display("payment_returned_date"),
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
    "prev_id",
    "adddate",
    "application_id",
    "card_id",
//    "active",
    "free",
    "person_number",
    "type_id",
    "deposit_id",
    "payment_type",
    "payment_sum",
    "currency",
    'payment_method',
    'bank_id',
//    "rejected",
//    "rejected_date",
    "returned",
    "returned_date",
    "should_share",
    "should_share_date",
//    "expired",
//    "expired_date",
    "autoreturn",
    "autoreturn_date",
    "payment_returned",
    "payment_returned_date",
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "prev_id" => $txtf->display("previous_payment"),
    "adddate" => $txtf->display("adddate"),
    "application_id" => $txtf->display("application"),
    "card_id" => $txtf->display("card"),
//    "active" => $txtf->display("active"),
    "free" => $txtf->display("free"),
    "person_number" => $txtf->display("person_number"),
    "type_id" => $txtf->display("card_type"),
    "deposit_id" => $txtf->display("bank_payment"),
    "payment_type" => $txtf->display("payment_type"),
    "payment_sum" => $txtf->display("payment_sum"),
    "currency" => $txtf->display("currency"),
    "payment_method" => $txtf->display("payment_method"),
    "bank_id" => $txtf->display("bank"),
//    "rejected" => $txtf->display("rejected"),
//    "rejected_date" => $txtf->display("rejected_date"),
//    "returned" => $txtf->display("returned"),
//    "returned_date" => $txtf->display("returned_date"),
    "should_share" => $txtf->display("should_share"),
    "should_share_date" => $txtf->display("should_share_date"),
//    "expired" => $txtf->display("expired"),
//    "expired_date" => $txtf->display("expired_date"),
    "autoreturn" => $txtf->display("autoreturn"),
    "autoreturn_date" => $txtf->display("autoreturn_date"),
    "payment_returned" => $txtf->display("payment_returned"),
    "payment_returned_date" => $txtf->display("payment_returned_date"),
);

/* required fields */
$required = array(
    "adddate",
    "application_id",
    "person_number",
    "type_id",
    "payment_type",
    "payment_sum",
    "currency"
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
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    $adm->assignProp("prev_id", "type", "select");
    $adm->assignExternal("prev_id", "module_isic_payment", "id", "CONCAT(adddate, ' (', id , ')') AS title ", " ORDER BY title desc", true);

    $adm->assignProp("deposit_id", "type", "select");
    $adm->assignExternal("deposit_id", "module_isic_payment_deposit", "id", "CONCAT(transaction_date, ' (', transaction_number , ')') AS title ", " ORDER BY title", true);

    $person_number = $adm->fields["person_number"]["value"];

    $adm->assignProp("application_id", "type", "select");
    $adm->assignExternal("application_id", "module_isic_application", "module_isic_application.id", "CONCAT(module_isic_school.name, ' / ', module_isic_card_type.name, ' (Last change: ', module_isic_application.moddate, ')') AS title",
        "
        LEFT JOIN
            module_isic_school ON module_isic_school.id = module_isic_application.school_id
        LEFT JOIN
            module_isic_card_type ON module_isic_card_type.id = module_isic_application.type_id
        WHERE
            module_isic_application.person_number = '{$person_number}' ORDER BY title ASC", true);

    $adm->assignProp("card_id", "type", "select");
    $adm->assignExternal("card_id", "module_isic_card", "id", "isic_number", " WHERE module_isic_card.person_number = '{$person_number}' ORDER BY isic_number", true);

    $adm->assignProp("type_id", "type", "select");
    $adm->assignExternal("type_id", "module_isic_card_type", "id", "name", " ORDER BY name", true);

    $adm->assignProp("bank_id", "type", "select");
    $adm->assignExternal("bank_id", "module_isic_bank", "id", "name", " ORDER BY name", true);

    $checkBoxFields = array('free', 'should_share', 'autoreturn', 'payment_returned');
    foreach ($checkBoxFields as $field) {
        $adm->assignProp($field, "type", "checkbox");
        $adm->assignProp($field, "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));
    }

    $paymentTypeList = array(
        "1" => $txtf->display("payment_type1"),
        "2" => $txtf->display("payment_type2"),
        "3" => $txtf->display("payment_type3"),
        "4" => $txtf->display("payment_type4"),
        "5" => $txtf->display("payment_type5")
    );
    $adm->assignProp("payment_type", "type", "select");
    $adm->assignProp("payment_type", "list", $paymentTypeList);

    $paymentMethodList = array("1" => $txtf->display("payment_method1"), "2" => $txtf->display("payment_method2"), "3" => $txtf->display("payment_method3"));
    $adm->assignProp("payment_method", "type", "select");
    $adm->assignProp("payment_method", "list", $paymentMethodList);
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
