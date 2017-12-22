<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once(dirname(__FILE__) . "/admin_header.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object


$txt =& ModeraTranslator::instance($language2, 'admin_general');
$txtf =& ModeraTranslator::instance($language2, 'module_races');

$table = "module_race_statuses"; // SQL table name to be administered
$idfield = "status_id"; // name of the id field (unique field, usually simply 'id')

/* the fields in the table */
$fields = array(
    "status_title" => $txtf->tr("title"),
    "language" => $txtf->tr("status_language"),
);

$tabs = array(
    1 => array($txt->tr("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txt->tr("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->tr("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "status_title",
    "language",
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "status_id" => $txtf->tr("status_id"),
    "status_title" => $txtf->tr("status"),
    "language" => $txtf->tr("status_language"),
);

/* required fields */
$required = array(
    'status_title',
    'language',
 );

$what = array("$table.*");
$from = array($table);
$where = '';

$filter_fields = array(
    "$table.name",
);

/* end display list part */

// If for example our table has references to another table (foreign key)
function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    $adm->assignProp("language", "type", "select");
    $adm->assignProp("language", "list", array("EN" => 'EN', "ET" => 'ET'));
    //$adm->assignProp("is_tour", "type", "checkbox");
    //$adm->assignProp("is_tour", "list", array("0" => $txt->tr("no"), "1" => $txt->tr("yes")));
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
$adm->assign("language", $language);
//$adm->setApplyVisible();
//$adm->setbackVisible();

//$statuses = 

/* DB writing part */
if ($do == "add") {

    // permissions
    //$perm->Access (0, 0, "a", "concerts");

    $res = $adm->add($table, $required, $idfield);
    if ($res == 0) {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

        // clear cache
        clearCacheFiles("tpl_concerts", "");
    } else {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
        $adm->getValues();
        $adm->types();
        external();
        $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
    }
} else if ($do == "update" && $id) {

    // permissions
    $perm->Access (0, $id, "m", "concerts");

    $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
    if ($res == 0) {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");

        // clear cache
        clearCacheFiles("tpl_concerts", "");

    } else {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
        $adm->getValues();
        $adm->types();
        external();
        $result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
    }
} else if ($do == "delete" && $id) {

    // permissions
    //$perm->Access (0, $id, "d", "concerts");

    $adm->fillValues($table, $idfield, $id);
    $adm->types();
    external();

    $res = $adm->delete($table, $idfield, $id);
    if ($res == 0) {
        $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");

        // clear cache
        clearCacheFiles("tpl_concerts", "");

    }
    else {
        $result = $general["error"];
    }
}
/* end DB writing part */

if ($show == "add") {

    // permissions
    $perm->Access (0, 0, "a", "concerts");

    if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
    $adm->types();
    external();
    $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
}
else if ($show == "modify" && $id) {

    // permissions
    $perm->Access (0, $id, "m", "concerts");

    $adm->fillValues($table, $idfield, $id);
    $adm->types();
    external();
    $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
}
else if (!$res || $res == 0) {
    // permissions
    $perm->Access (0, 0, "m", "concerts");

    external();
    $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
}

if ($show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->tr("module_title"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->tr("module_title"));
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
