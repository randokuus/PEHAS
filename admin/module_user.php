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
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicDB.php");

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
$txtf = new Text($language2, "module_user");

// ##############################################################
// ##############################################################

$table = "module_user_users"; // SQL table name to be administered

$idfield = "user"; // name of the id field (unique field, usually simply 'id')

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
    "sort" => "added DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "user_type" => $txtf->display("user_type"),
    "region_list" => $txtf->display("region_list"),
    "use_region_list" => $txtf->display("use_region_list"),
    "ggroup" => $txtf->display("group"),
    "auth_type" => $txtf->display("auth_type"),
    "username" => $txtf->display("username"),
    "password"  => $txtf->display("password"),
    "password2" => $txtf->display("password2"),
    "user_code" => $txtf->display("user_code"),
    "birthday" => $txtf->display("birthday"),
    "name_first" => $txtf->display("name_first"),
    "name_last" => $txtf->display("name_last"),
    "delivery_addr1" => $txtf->display("delivery_addr1"),
    "delivery_addr2" => $txtf->display("delivery_addr2"),
    "delivery_addr3" => $txtf->display("delivery_addr3"),
    "delivery_addr4" => $txtf->display("delivery_addr4"),
    "email" => $txtf->display("email"),
    "phone" => $txtf->display("phone"),
    "bankaccount" => $txtf->display("bankaccount"),
    "bankaccount_name" => $txtf->display("bankaccount_name"),
    "special_offers" => $txtf->display("special_offers"),
    "newsletter" => $txtf->display("newsletter"),
    "children_list" => $txtf->display("children_list"),
    "ips" => $txtf->display("ips"),
    "last_pan_query" => $txtf->display("last_pan_query"),
    "pan_queries" => $txtf->display("pan_queries"),
    "data_sync_allowed" => $txtf->display("data_sync_allowed"),
//  "type" => $txtf->display("type"),
    "active" => $txtf->display("active"),
    "external_status_check_allowed" => $txtf->display("external_status_check_allowed"),
    "ehl_status_check_allowed" => $txtf->display("ehl_status_check_allowed"),
    "appl_confirmation_mails" => $txtf->display("appl_confirmation_mails"),
    "added" => $txtf->display("added"),
    "pic" => $txtf->display("pic"),
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
    "user_type",
    "auth_type",
    "ggroup",
    "username",
    "user_code",
    "name_first",
    "name_last",
    "email",
    "phone",
    "ips",
    "type",
    "active",
    "added",
    "birthday",
    "delivery_addr1",
    "delivery_addr2",
    "delivery_addr3",
    "delivery_addr4",
    "bankaccount",
    "bankaccount_name",
    "newsletter",
    "special_offers",
    "external_status_check_allowed",
    "ehl_status_check_allowed",
    "pic",
    "last_pan_query",
    "pan_queries",
    "data_sync_allowed",
    "appl_confirmation_mails",
    'children_list',
    'region_list',
    'use_region_list'
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "user_type" => $txtf->display("user_type"),
    "groupname" => $txtf->display("group"),
    "user_code" => $txtf->display("user_code"),
    "username" => $txtf->display("username"),
    "name_first" => $txtf->display("name_first"),
    "name_last" => $txtf->display("name_last"),
//  "type" => $txtf->display("type"),
    "active" => $txtf->display("active")
//  "added" => $txtf->display("added")
);

/* required fields */
$required = array(
    "username",
    "name_first",
    "name_last",
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "module_user_groups.name as groupname"
    );
    $from = array(
        $table,
        "LEFT JOIN module_user_groups ON $table.ggroup = module_user_groups.id"
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "module_user_groups.name",
        "$table.username",
        "$table.user_code",
        "$table.name_first",
        "$table.name_last",
        "$table.phone",
        "$table.email",
        "$table.added",
        "$table.children_list",
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    if ($show == "add") {
        $adm->assign("added", date("Y-m-d H:i:s"));
        $adm->assignProp("username", "extra", $txtf->display("username_extra"));
    }

    if ($show == "modify") {
        $adm->displayOnly("username");
        $adm->assign("password", "");
        $adm->assign("password2", "");
    }

    $adm->assignProp("password", "type", "password");
    $adm->assignProp("password2", "type", "password");

    $adm->assignProp("password", "size", "30");
    $adm->assignProp("password2", "size", "30");

    $adm->displayOnly("added");

    $adm->assignProp("password", "extra", $txtf->display("password_extra"));

    $adm->assignProp("ips", "extra", $txtf->display("ips_extra"));

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("external_status_check_allowed", "type", "checkbox");
    $adm->assignProp("external_status_check_allowed", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("ehl_status_check_allowed", "type", "checkbox");
    $adm->assignProp("ehl_status_check_allowed", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("data_sync_allowed", "type", "checkbox");
    $adm->assignProp("data_sync_allowed", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("appl_confirmation_mails", "type", "checkbox");
    $adm->assignProp("appl_confirmation_mails", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("type", "type", "select");
    $adm->assignProp("type", "list", array("1" => $txtf->display("type1"), "2" => $txtf->display("type2")));

    $adm->assignProp("user_type", "type", "select");
    $adm->assignProp("user_type", "list", array("1" => $txtf->display("user_type1"), "2" => $txtf->display("user_type2")));

    $adm->assignProp("region_list", "type", "select2");
    $adm->assignProp("region_list", "size", 10);
    $adm->assignExternal("region_list", "module_isic_region", "id", "name", " ORDER BY name", false);

    $adm->assignProp("use_region_list", "type", "checkbox");
    $adm->assignProp("use_region_list", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $adm->assignProp("ggroup", "type", "select2");
    $adm->assignProp("ggroup", "size", 25);
    $adm->assignExternal("ggroup", "module_user_groups", "id", "name", " ORDER BY name", false);

    $adm->assignProp("newsletter", "type", "select2");
    $adm->assignProp("newsletter", "size", 5);
    $adm->assignExternal("newsletter", "module_isic_card_type", "id", "name", " ORDER BY name", false);

    $adm->assignProp("special_offers", "type", "select2");
    $adm->assignProp("special_offers", "size", 2);
    $adm->assignProp("special_offers", "list", array("1" => $txtf->display("special_offer1"), "2" => $txtf->display("special_offer2")));

    $list = array();
    for ($i = 1; $i < 9; $i++) {
        $list[$i] = $txtf->display("auth_type" . $i);
    }
    $adm->assignProp("auth_type", "type", "select2");
    $adm->assignProp("auth_type", "size", 7);
    $adm->assignProp("auth_type", "list", $list);

    $adm->displayButtons("pic");
    //$adm->displayOnly("pic");
    $adm->assignProp("pic","type","onlyhidden");
    $prod_image = SITE_URL . $adm->fields["pic"]["value"];
    //$adm->assign("pic", "");
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
    $adm->assignProp("password2", "type", "password");

    $sq = new sql;

    //$adm->assign("lastmod", date("Y-m-d H:i:s"));
    //$adm->assign("user", $user);
    //$adm->assign("language", $language);

    /* DB writing part */
    if ($do == "add") {

        // permissions
        $perm->Access (0, 0, "a", "user");
        $isic = IsicCommon::getInstance();

        $required[] = "password";
        $required[] = "password2";

        $sq->query($adm->dbc, "SELECT username FROM module_user_users WHERE username = '" . addslashes($username) . "'");
        if ($sq->numrows != 0) $adm->values["username"] = "";

        if ($_POST["password"] != $_POST["password2"]) {
            $adm->general["required_error"] .= "<br/>" . $txtf->display("password_error");
            $adm->values["password"] = "";
            $adm->values["password2"] = "";
        }

        if ($_POST['user_type'] == 1) {
            $adm->values['appl_confirmation_mails'] = 1;
        }

        $res = $adm->add($table, $required, $idfield);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

            $added_id = $sq->insertID();
            $isic->saveUserChangeLog($isic->log_type_add, $added_id, array(), $isic->getUserRecord($added_id), $bind_user);
            $dbUser = IsicDB::factory('Users');
            $dbUser->updateGroups($added_id);
        }
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
        $perm->Access (0, $id, "m", "user");

        $isic = IsicCommon::getInstance();
        $row_old = $isic->getUserRecord($id);

        if ($_POST["password"] != "") {
            $upd_fields[] = "password";
            $required[] = "password";
            $required[] = "password2";
            if ($_POST["password"] != $_POST["password2"]) {
                $adm->general["required_error"] .= "<br/>" . $txtf->display("password_error");
                $adm->values["password"] = "";
                $adm->values["password2"] = "";
            }
        }

        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
            $isic->saveUserChangeLog($isic->log_type_mod, $id, $row_old, $isic->getUserRecord($id), $bind_user);
            $dbUser = IsicDB::factory('Users');
            $dbUser->updateGroups($id);
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
        $perm->Access (0, $id, "d", "user");

        $isic = IsicCommon::getInstance();
        $row_old = $isic->getUserRecord($id);

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
            $isic->saveUserChangeLog($isic->log_type_del, $id, $row_old, array(), $bind_user);
        } else {
            $result = $general["error"];
        }
    }
    /* end DB writing part */

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "user");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "user");

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
    $tpl->addDataItem("TITLE", $txtf->display("module_title1"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->display("module_title1"));
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
