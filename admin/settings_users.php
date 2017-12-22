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

// permissions
$perm->Access (0, 0, "m", "");

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_users");

// ##############################################################
// ##############################################################

$table = "adm_user"; // SQL table name to be administered

$idfield = "user"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main_module.html",
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
    "max_entries" => 50,
    "sort" => "username ASC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "ggroup" => $txtf->display("ggroup"),
    "username" => $txtf->display("username"),
    "password" => $txtf->display("password"),
    "password2" => $txtf->display("password2"),
    "name" => $txtf->display("name"),
    "email" => $txtf->display("email"),
    "phone" => $txtf->display("phone"),
    "ips" => $txtf->display("ips"),
    "active" => $txtf->display("active"),
    "user_id" => $txtf->display("bind_user"),
);

$tabs = array(
    1 => array($txtf->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txtf->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "ggroup",
    "username",
    "name",
    "email",
    "phone",
    "active",
    "user_id"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    //$idfield => "ID", // if you want to display the ID as well,
    "username" => $txtf->display("username"),
    "name" => $txtf->display("name"),
    "groupname" => $txtf->display("ggroup"),
    "email" => $txtf->display("email"),
    "active" => $txtf->display("active"),
    "user_id" => $txtf->display("bind_user"),
);

/* required fields */
$required = array(
    "name"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "adm_group.name as groupname"
    );
    $from = array(
        $table,
        "LEFT JOIN adm_group ON $table.ggroup = adm_group.ggroup"
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "$table.name",
        "$table.email",
        "$table.username",
        "adm_group.name"
    );

 /* end display list part */

//
// Enable can_publish field only for PRO versions
//
if (pro_version()) {
    $disp_fields['can_publish'] = $txtf->display("publish_content");
    $upd_fields[] = 'can_publish';
    $fields['can_publish'] = $txtf->display("publish_content");
}

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $perm;
    $sq = new sql;

    if ($show == "modify") {
        $adm->assign("password", "");
        $adm->assign("password2", "");
        $adm->displayOnly("username");
    }
    if ($show == "add") {
        //$adm->displayOnly("ggroup");
    }

    $adm->assignProp("ips", "extra", $txtf->display("ips_extra"));

    $adm->assignProp("password", "type", "password");
    $adm->assignProp("password", "size", "30");
    $adm->assignProp("password2", "type", "password");
    $adm->assignProp("password2", "size", "30");


    $adm->assignProp("can_publish", "type", "checkbox");
    $adm->assignProp("can_publish", "list", array("0" => $txtf->display("depends_on_group"), "1" => $txt->display("yes")));
    // overwrite value for root users
    if (isset($adm->fields['ggroup']['value'])
        && $adm->fields['ggroup']['value'] == $perm->root)
    {
        $adm->assign('can_publish', 1);
    }

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    //$adm->displayOnly("ggroup");
    $adm->assignProp("ggroup", "type", "select");
    $adm->assignExternal("ggroup", "adm_group", "ggroup", "name", "", false);

    $adm->assignProp("user_id", "type", "select");
    $adm->assignExternal("user_id", "module_user_users", "user", "concat(name_last, ', ', name_first, ' (id: ', user, ')')", "order by name_last, name_first", false);
}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 50) { $general["max_entries"] = $max_entries; }

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

    $adm = new Admin($table);
    $adm->assignProp("password", "type", "password");
    $adm->assignProp("password2", "type", "password2");

    $sq = new sql;

    /* DB writing part */
    if ($do == "add") {

        $required[] = "password";
        $required[] = "password2";

        if ($_POST["password"] != $_POST["password2"]) {
            $adm->general["required_error"] .= "<br/>" . $txtf->display("password_error");
            $adm->values["password"] = "";
            $adm->values["password2"] = "";
        }

        $res = $adm->add($table, $required, $idfield);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\""); }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "update" && $id) {

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
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\""); }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "delete" && $id) {

        // Current root user cannot be deleted
        if ($id && $id == $user) {
            $perm->displayError(403, "");
        }

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\""); }
        else { $result = $general["error"]; }
    }
    /* end DB writing part */

    if ($show == "add") {
        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {
        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if (!$res || $res == 0) {
        external();
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
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
