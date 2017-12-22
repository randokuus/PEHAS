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
$txtf = new Text($language2, "module_user_status_user");

// ##############################################################
// ##############################################################

$table = "module_user_status_user"; // SQL table name to be administered

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
    "group_id" => $txtf->display("group_id"),
    "user_id" => $txtf->display("user_id"),
    "status_id" => $txtf->display("status_id"),
    "school_id" => $txtf->display("school_id"),
    "active" => $txtf->display("active"),
    "addtime" => $txtf->display("addtime"),
    "adduser" => $txtf->display("adduser"),
    "addtype" => $txtf->display("addtype"),
    "modtime" => $txtf->display("modtime"),
    "moduser" => $txtf->display("moduser"),
    "modtype" => $txtf->display("modtype"),
    "faculty" => $txtf->display("faculty"),
    "class" => $txtf->display("class"),
    "course" => $txtf->display("course"),
    "position" => $txtf->display("position"),
    "structure_unit" => $txtf->display("structure_unit"),
    "expiration_date" => $txtf->display("expiration_date"),
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
    "group_id",
    "status_id",
    "user_id",
    "active",
    "addtime",
    "adduser",
    "addtype",
    "modtime",
    "moduser",
    "modtype",
    "school_id",
    "faculty",
    "class",
    "course",
    "position",
    "structure_unit",
    'expiration_date'
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "group_id" => $txtf->display("group_id"),
    "status_id" => $txtf->display("status_id"),
    "user_name" => $txtf->display("user_id"),
    "active" => $txtf->display("active"),
    "addtime" => $txtf->display("addtime"),
    "school_id" => $txtf->display("school_id"),
    "expiration_date" => $txtf->display("expiration_date"),
);

/* required fields */
$required = array(
    "status_id",
    "user_id",
    "school_id",
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "CONCAT(module_user_users.name_first, ' ', module_user_users.name_last, ' (', module_user_users.user_code, ')') AS user_name"
    );
    $from = array(
        $table,
        "LEFT JOIN module_user_users ON $table.user_id = module_user_users.user"
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "CONCAT(module_user_users.name_first, ' ', module_user_users.name_last)",
        "module_user_users.user_code",
//        "$table.name"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $bind_user;
    //$sq = new sql;

    if ($show == "add") {
        $adm->assign("addtime", date("Y-m-d H:i:s"));
    }
    //$adm->assign("modtime", date("Y-m-d H:i:s"));

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

    if ($adm->fields['user_id']['value']) {
        $userId = $adm->fields['user_id']['value'];
    } else {
        $userId = $bind_user;
    }

    $adm->assignProp("adduser", "type", "select");
    $adm->assignExternal("adduser", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", " WHERE user = {$addUserId} ORDER BY name", false);

    $adm->assignProp("moduser", "type", "select");
    $adm->assignExternal("moduser", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", " WHERE user = {$modUserId} ORDER BY name", false);

    $adm->displayOnly("addtime");
    $adm->displayOnly("modtime");
    $adm->displayOnly("adduser");
    $adm->displayOnly("moduser");
    $adm->displayOnly("addtype");
    $adm->displayOnly("modtype");

    $adm->assignProp("group_id", "type", "select");
    $adm->assignExternal("group_id", "module_user_groups", "id", "name", " ORDER BY name", false);

    $adm->assignProp("status_id", "type", "select");
	$adm->assignExternal("status_id", "module_user_status", "id", "name", " ORDER BY name", false);

    $adm->assignProp("user_id", "type", "select");
    $adm->assignExternal("user_id", "module_user_users", "user", "CONCAT(name_last, ' ', name_first, ' (', username, ')') AS name", "  WHERE user = {$userId} ORDER BY name", false);

    $adm->assignProp("school_id", "type", "select");
    $adm->assignExternal("school_id", "module_isic_school", "id", "name", " ORDER BY name", false);

    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $originTypes = array("1" => $txtf->display("origin_type1"), "2" => $txtf->display("origin_type2"), "3" => $txtf->display("origin_type3"), "4" => $txtf->display("origin_type4"));

    $adm->assignProp("addtype", "type", "select");
    $adm->assignProp("addtype", "list", $originTypes);

    $adm->assignProp("modtype", "type", "select");
    $adm->assignProp("modtype", "list", $originTypes);
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

    /* DB writing part */
    if ($do == "add") {

        // permissions
        $perm->Access (0, 0, "a", "user");

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
        $perm->Access (0, $id, "m", "user");

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
        $perm->Access (0, $id, "d", "user");

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\""); }
        else { $result = $general["error"]; }
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
