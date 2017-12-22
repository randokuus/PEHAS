<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
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
$txtf = new Text($language2, "module_projects");

// ##############################################################
// ##############################################################

$table = "module_projects_task"; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" =>$GLOBALS["modera_debug"],
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
    "sort" => "$table.lastmod DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "project" => $txtf->display("name"),
    "task" => $txtf->display("task"),
    "date1" => $txtf->display("begin"),
    "date2" => $txtf->display("end"),
    "info" => $txtf->display("info"),
    "type" => $txtf->display("type"),
    "status" => $txtf->display("status"),
    "priority" => $txtf->display("priority"),
    "status" => $txtf->display("status"),
    "team" =>  $txtf->display("team"),
    "solution" => $txtf->display("solution"),
    "date3" => $txtf->display("ready")
//  "lastmod" => $txtf->display("lastmod")
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
    "project",
    "task",
    "date1",
    "date2",
    "info",
    "type",
    "status",
    "priority",
    "status",
    "team",
    "solution",
    "date3",
    "lastuser",
    "lastmod"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//  $idfield => "ID", // if you want to display the ID as well,
    "projectn" => $txtf->display("name"),
    "task" => $txtf->display("task"),
    "date1" => $txtf->display("begin"),
    "date2" => $txtf->display("end"),
    "type" => $txtf->display("type"),
    "status" => $txtf->display("status"),
    "priority" => $txtf->display("priority")
);

/* required fields */
$required = array(
    "project",
    "task",
    "info"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*",
        "module_projects_main.name as projectn"
    );
    $from = array(
        $table,
         "LEFT JOIN module_projects_main ON $table.project = module_projects_main.id"
    );

    //$where = "language = '$language'";

    $filter_fields = array(
        "$table.task",
        "module_projects_main.name",
        "$table.info",
        "$table.solution",
        "$table.date1",
        "$table.date2",
        "$table.date3"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $fields, $txtf, $txt, $group, $language, $id, $structure, $project;
    $sq = new sql;

    if ($show == "add") {
        $adm->assign("date1", date("Y-m-d H:i:s"));
        $adm->assign("date2", date("Y-m-d H:i:s"));

    }
    else if ($show == "modify") {
        $fields["owner"] = $txtf->display("owner");
        $fields["lastuser"] = $txtf->display("lastuser");
    }

    $adm->displayOnly("project");
    $adm->assignProp("project", "type", "select");
    $adm->assignExternal("project", "module_projects_main", "id", "name", "", true);

    $adm->assignProp("status", "type", "select");
    for ($u = 1; $u < 8; $u++) {
        $ar[$u] = $txtf->display("status".$u);
    }
    $adm->assignProp("status", "list", $ar);
    $adm->assignProp("priority", "type", "select");
    $adm->assignProp("priority", "list", array("1" => $txtf->display("priority1"), "2" => $txtf->display("priority2"), "3" => $txtf->display("priority3")));
    $adm->assignProp("type", "type", "select");
    $adm->assignProp("type", "list", array("1" => $txtf->display("type1"), "2" => $txtf->display("type2")));

    $adm->assignProp("team", "type", "select2");
    $adm->assignProp("team", "size", "5");
    $adm->assignExternal(
        "team",
        "module_user_users LEFT JOIN module_user_groups ON module_user_users.ggroup = module_user_groups.id",
        "module_user_users.user",
        "CONCAT(module_user_groups.name, ' - ', module_user_users.name)",
        "WHERE module_user_users.active = 1",
        false);


    $adm->assignProp("info", "rows", "10");
    $adm->assignProp("info", "cols", "100");
    $adm->assignProp("solution", "rows", "10");
    $adm->assignProp("solution", "cols", "100");

    $adm->displayOnly("owner");
    $adm->assignProp("owner", "type", "select");
    $adm->assignExternal("owner", "module_user_users", "user", "name", "", false);

    $adm->displayOnly("lastuser");
    $adm->assignProp("lastuser", "type", "select");
    $adm->assignExternal("lastuser", "module_user_users", "user", "name", "WHERE type = 2 AND active = 1", false);

    $adm->displayOnly("lastmod");

    // #############

        $fdata = $adm->fields["project"];
        //$fdata["java"] = "onChange=\"this.form.submit()\"";
        $f = new AdminFields("project",$fdata);
        $project_select = $f->display($project);

        if ($project) {
            $adm->assignFilter("project", $project, "project = '".addslashes($project)."'", $project_select);
        }
        else{
            $adm->assignFilter("project", "", "", $project_select);
        }

        //$adm->assignHidden("project", $project);

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

    $adm->assign("lastmod", date("Y-m-d H:i:s"));
    $adm->assign("lastuser", $user);
    //$adm->assign("language", $language);

    /* DB writing part */
    if ($do == "add") {

        // permissions
        $perm->Access (0, 0, "a", "projects");

        $adm->assign("owner", $user);

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
        $perm->Access (0, $id, "m", "projects");

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
        $perm->Access (0, $id, "d", "projects");

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) { $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\""); }
        else { $result = $general["error"]; }
    }
    /* end DB writing part */

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "projects");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "projects");

        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if (!$res || $res == 0) {
        // permissions
        $perm->Access (0, 0, "m", "projects");

        external();
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

if ($project) {
    $tpl->addDataItem("HIDDEN", "$table.project='".addslashes($project)."'");
}
else {
    $tpl->addDataItem("HIDDEN", "$table.project='".addslashes($adm->extra_filter["project"][0])."'");
}

if ($show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->display("module_title2"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->display("module_title2"));
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

$result = $tpl->parse();
echo $result;