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
$txtf = new Text($language2, "admin_groups");

// ##############################################################
// ##############################################################

$table = "adm_group"; // SQL table name to be administered

$idfield = "ggroup"; // name of the id field (unique field, usually simply 'id')

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
    "max_entries" => 30,
    "sort" => "ggroup ASC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "name" => $txtf->display("name")
    //"perm_module"  => $txtf->display("modules")
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
    "name",
    "perm_module"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    $idfield => "ID", // if you want to display the ID as well,
    "name" => $txtf->display("name")
);

/* required fields */
$required = array(
    "name"
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
    global $adm, $show, $txtf, $txt, $group, $language, $id, $fields;
    //$sq = new sql;

    // perm_module
    // kala=1,1,1;jura=1,0,0 jne

    $mod_acc = array();

    if ($adm->fields["perm_module"]["value"] != "") {
        $mod = split(";", $adm->fields["perm_module"]["value"]);
        foreach ($mod as $module_data) {
            list($module_name, $perm_data) = split("=", $module_data);
            if (0 === strncmp("perm_", $module_name, 5)) {
                $permissions = $perm_data;
            } else {
                $permissions = split(",", $perm_data);
                if (count($permissions) != 3) {
                    for ($i = 0; $i < 3; $i++) {
                        if (!isset($permissions[$i])) {
                            $permissions[$i] = 0;
                        }
                    }
                }
            }

            $mod_acc[$module_name] = $permissions;
        }
    }

    //
    // Enable "content publish" permission only for PRO versions
    //
    if (pro_version()) {
        // Editor rights
        $fields["perm_contentpublish"] = $txtf->display("publish_content");
        $adm->assignProp("perm_contentpublish", "type", "checkbox");
        $adm->assign("perm_contentpublish", isset($mod_acc["perm_contentpublish"])
           ? $mod_acc["perm_contentpublish"] : 0);
    }

    // File rights
    $fields["module_fileaccess"] = $txtf->display("files");
    if (is_array($mod_acc["fileaccess"])) {
        $adm->assign("module_fileaccess", $mod_acc["fileaccess"]);
    }
    else {
        $adm->assign("module_fileaccess", array($GLOBALS["perm_file_group"]["a"], $GLOBALS["perm_file_group"]["m"], $GLOBALS["perm_file_group"]["d"]));
    }
    $adm->assignProp("module_fileaccess", "type", "checkboxp");
    $adm->assignProp("module_fileaccess", "list", array("0" => $txt->display("perm_a"), "1" => $txt->display("perm_m"), "2" => $txt->display("perm_d")));

    // ####

    foreach ($GLOBALS["modules"] as $module) {
        $trm = &ModeraTranslator::instance($language, "module_$module");
        $title = $trm->tr("module_title");

        if ($trm->_missed_translation("module_$module", "module_title") != $title) {
            $fields["module_$module"] = $title;

            if (is_array($mod_acc[$module])) {
                $adm->assign("module_$module", $mod_acc[$module]);
            } else {
                $adm->assign("module_$module", array(
                   $GLOBALS["perm_module_group"]["a"],
                   $GLOBALS["perm_module_group"]["m"],
                   $GLOBALS["perm_module_group"]["d"],
                ));
            }

            $adm->assignProp("module_$module", "type", "checkboxp");
            $adm->assignProp("module_$module", "list", array(
                "0" => $txt->display("perm_a"),
                "1" => $txt->display("perm_m"),
                "2" => $txt->display("perm_d"),
            ));
        }
    }
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

    $sq = new sql;

    // Root group cannot be changed or deleted
    if ($id && $id == $group) {
        $perm->displayError(403, "");
    }

    foreach ($_POST as $key => $val) {
        if (0 === strncmp("module_", $key, 7)) {
            $b = array();
            if (is_array($adm->values[$key])) {
                for ($c = 0; $c < 3; $c++) {
                    if (in_array($c, $adm->values[$key])) $b[] = 1;
                    else { $b[] = 0; }
                }
                $d = join(",", $b);
            }
            else {
                $d = "0,0,0";
            }

            $final[] = substr($key, 7) . "=" . $d;

        } else if (0 === strncmp("perm_", $key, 5)) {
            $final[] = "$key=" . (int)$val;
        }
    }

    if (is_array($final)) {
        $adm->assign("perm_module", join(";", $final));
    }
    else {
        $adm->assign("perm_module", "");
    }
    unset($_POST);

    /* DB writing part */
    if ($do == "add") {
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
