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

$table = "module_isic_card_type_school"; // SQL table name to be administered

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
    "sort" => "id ASC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "type_id" => $txtf->display("type"),
    "school_id" => $txtf->display("school"),
    "description" => $txtf->display("description"),
    "payment_required" => $txtf->display("payment_required"),
    "card_home_delivery" => $txtf->display("card_home_delivery"),
    "card_eyl_delivery" => $txtf->display("card_eyl_delivery"),
    "payment_sum" => $txtf->display("payment_sum"),
    "logo_front" => $txtf->display("logo_front"),
    "logo_back" => $txtf->display("logo_back"),
    "design_front" => $txtf->display("design_front"),
    "design_back" => $txtf->display("design_back"),
    "delivery_address" => $txtf->display("delivery_address"),
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
    "type_id",
    "school_id",
    "description",
    "payment_required",
    'card_home_delivery',
    'card_eyl_delivery',
    "payment_sum",
    "logo_front",
    "logo_back",
    "design_front",
    "design_back",
    "delivery_address",
    'picture_expiration'
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "type_id" => $txtf->display("type"),
    "school_id" => $txtf->display("school"),
    "payment_required" => $txtf->display("payment_required"),
    "card_home_delivery" => $txtf->display("card_home_delivery"),
    "card_eyl_delivery" => $txtf->display("card_eyl_delivery"),
    "payment_sum" => $txtf->display("payment_sum"),
    "logo_front" => $txtf->display("logo_front"),
    "logo_back" => $txtf->display("logo_back"),
    "design_front" => $txtf->display("design_front"),
    "design_back" => $txtf->display("design_back"),
    "delivery_address" => $txtf->display("delivery_address"),
);

/* required fields */
$required = array(
    "type_id",
    "school_id",
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
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $table, $school_id, $type_id;
    //$sq = new sql;

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

    $adm->assignProp("payment_required", "type", "checkbox");
    $adm->assignProp("payment_required", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("card_home_delivery", "type", "checkbox");
    $adm->assignProp("card_home_delivery", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("card_eyl_delivery", "type", "checkbox");
    $adm->assignProp("card_eyl_delivery", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("description", "rows", "10");
    $adm->assignProp("description", "cols", "80");

    $t_pic_fields = array("logo_front", "logo_back", "design_front", "design_back");
    $fld_cnt = 1;

    foreach ($t_pic_fields as $t_pic_fld) {
        $adm->assignProp($t_pic_fld, "type", "onlyhidden");
        if ($adm->fields[$t_pic_fld]["value"]) {
            $t_pic = "<img src=\"" . SITE_URL . $adm->fields[$t_pic_fld]["value"] . "\" border=0>";
        } else {
            $t_pic = "&nbsp;";
        }
        $adm->assignProp($t_pic_fld, "extra", "
        <table border=0 cellpadding=0 cellspacing=0>
        <tr valign=top><td><div align=\"left\" id=\"newspic$fld_cnt\">$t_pic</div></td>
        <td>&nbsp;&nbsp;</td>
        <td><button type=button onClick=\"newWindow('editor/Inc/insimage1.php?pic=$fld_cnt&insert_into=$t_pic_fld',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_choose"))."</button>
        <button type=button onClick=\"javascript:clearPicUniversal($fld_cnt, '$t_pic_fld');\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_del"))."</button>
        </td></tr></table>");
        $fld_cnt++;
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

    $t_pic_fields = array("logo_front", "logo_back", "design_front", "design_back");
    foreach ($t_pic_fields as $t_pic_fld) {
        $_POST[$t_pic_fld] = str_replace(SITE_URL, "", $_POST[$t_pic_fld]);
    }

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
