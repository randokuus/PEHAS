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
require_once(SITE_PATH . "/class/admin.class.php");           // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/FormAction.php");

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
$txtf = new Text($language2, "module_form2");

// ##############################################################
// ##############################################################

$table = "module_form2"; // SQL table name to be administered

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
    "sort" => "id DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "title"  => $txtf->display("title"),
    "action" => $txtf->display("actionto"),
//  "actioninfo" => $txtf->display("actioninfo"),
    "active" => $txtf->display("active")
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
    "language",
    "title",
    "action",
    "actioninfo",
    "active"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    $idfield => "ID", // if you want to display the ID as well,
    "title" => $txtf->display("title"),
    "active" => $txtf->display("active")
);

/* required fields */
$required = array(
    "title",
    "action"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    $where = "language = '$language'";

    $filter_fields = array(
        "$table.title",
        "$table.action"
    );

 /* end display list part */

if (!$action) {
    if (isset($id)) {
        $action = $database->fetch_first_value('SELECT `action` FROM `module_form2` WHERE `id` = ?', $id);
    } else {
        $action = 'dummy';
    }
}

// create additional configuration fields
$config_fields = FormAction::getConfigFields($action);

/**
 * Saves additional configuration fields into database
 *
 */
function saveAdditionalFields()
{
    global $config_fields, $database, $id;

    $database->query('DELETE FROM `module_form2_config` WHERE `form_id` = ?', $id);
    foreach (array_keys($config_fields) as $field_name) {
        $GLOBALS['database']->query('INSERT INTO `module_form2_config`(`form_id`, '
            . ' `key`, `value`) VALUES(?, ?, ?)', $id, $field_name, $_POST[$field_name]);
    }
}

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure, $action
       , $fields, $config_fields, $database;
    $sq = new sql;

    $adm->assignProp("action", "type", "select");
//  $adm->assignProp("actionto", "list", array("1" => $txtf->display("actionto1")));

    $actions = array('dummy' => $txtf->display('action_dummy'));
    foreach (FormAction::available() as $form_action) {
        if ('dummy' == $form_action) continue;
        $actions[$form_action] = $txtf->display('action_' . $form_action);
    }
    $adm->assignProp("action", "list", $actions);
    $adm->assignProp("action", "list", $actions);
    $adm->assignProp("action", "java", "onChange=\"submitTo();\"");

    $adm->assignHidden("submit_to", "0");
    $adm->assignProp("active", "type", "checkbox");
    $adm->assignProp("active", "list", array("0" => $txt->display("no"), "1" => $txt->display("yes")));

    $result =& $database->query('SELECT `key`, `value` FROM `module_form2_config` '
       . ' WHERE `form_id` = ?', $id);
    $config_values = array();
    while($row = $result->fetch_assoc()) {
        $config_values[$row['key']] = $row['value'];
    }

    // create additional configuration fields
    foreach ($config_fields as $field_name => $field_meta) {
        $fields[$field_name] = $txtf->display('action_' . $action . '_' . $field_name);
        foreach ($field_meta as $prop_type => $prop_value) {
            $adm->assignProp($field_name, $prop_type, $prop_value);
        }
        if (array_key_exists($field_name, $config_values)) {
            $adm->assignProp($field_name, 'value', $config_values[$field_name]);
        }
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

    $sq = new sql;

    //$adm->assign("lastmod", date("Y-m-d H:i:s"));
    //$adm->assign("user", $user);
    $adm->assign("language", $language);

    if (!$submit_to) {
        /* DB writing part */
        if ($do == "add") {

            // permissions
            $perm->Access (0, 0, "a", "form2");

            $res = $adm->add($table, $required, $idfield);
            if ($res == 0) {

                // save additional configuration fields
                $id = $adm->insert_id;
                saveAdditionalFields();
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

                // clear cache
                //clearCacheFiles("tpl_form2", "");

            }
            else {
                $show = "add";
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
                $adm->getValues();
                $adm->types();
                external();
                $result .= $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
            }
        }
        else if ($do == "update" && $id) {

            // permissions
            $perm->Access (0, $id, "m", "form2");

            $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
            if ($res == 0) {

                // save additional configuration fields
                saveAdditionalFields();
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");

                // clear cache
                clearCacheFiles("tpl_form2_".addslashes($id)."_", "");

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
            $perm->Access (0, $id, "d", "form2");

            $res = $adm->delete($table, $idfield, $id);
            if ($res == 0) {
                $sq->query($adm->dbc, "DELETE FROM module_form2_fields WHERE form = '" . addslashes($id) . "'");
                // remove additional configuration data
                $database->query('DELETE FROM `module_form2_config` WHERE `form_id` = ?', $id);
                $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");

                // clear cache
                clearCacheFiles("tpl_form2_".addslashes($id)."_", "");

             }
            else {
                $result = $general["error"];
            }
        }
        /* end DB writing part */
    } else {
        if ('update' == $do && $id) {
            $show = 'modify';
        } else {
            $show = 'add';
        }
    }

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "form2");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "form2");

        if (!$submit_to) $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if (!$res || $res == 0) {
        // permissions
        $perm->Access (0, 0, "m", "form2");

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
