<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . '/class/admin.class.php');                      // administration main object
require_once(SITE_PATH . '/class/TranslatorHelpers.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/text.class.php');

// permissions
$perm->Access (0, 0, "m", "");

// init Text object for this page
$txt = new Text($language2, "amdin_general");
$txtf = new Text($language2, "admin_langfiles");

// ##############################################################
// ##############################################################

$table = "languages"; // SQL table name to be administered
$idfield = "language"; // name of the id field (unique field, usually simply 'id')

/* the fields in the table */
$fields = array(
    "language" => $txtf->display('language code'),
    "title" => $txtf->display('title'),
    "plural" => $txtf->display('language group (by plurals)'),
    "description" => $txtf->display('description'),
);

$tabs = array(
    1 => array($txtf->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txtf->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txtf->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "title",
    "nplurals",
    "expr",
    "description",
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "language" => $txtf->display('language code'), // listnumber displays the number of current row starting from 1
    "title" => $txtf->display('title'),
    "description" => $txtf->display('description'),
);

/* required fields */
$required = array(
    "language",
    "title",
    "nplurals",
    "expr",
);

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    $filter_fields = array(
        "$table.language"
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp('language', 'max', 2);
    $adm->assignProp('title', 'max', 50);

    // collect language groups data
    $lgroups = array();
    foreach (TranslatorHelpers::plural_lang_groups() as $group_id => $group) {
        foreach ($group as $k => $v) $group[$k] = htmlspecialchars($v);
        if (1 == $group_id) array_unshift($group, $txtf->display('NO PLURAL FORMS'));
        $lgroups[$group_id] = implode(', ', $group);
    }

    $adm->assignProp('plural', 'type', 'select');
    $adm->assignProp('plural', 'list', $lgroups);
}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

/**
 * Process input variables
 *
 * @param Admin $admin
 */
function process_form(&$admin)
{
    $admin->values['language'] = strtolower($admin->values['language']);
    list($admin->values['nplurals'], $admin->values['expr']) = TranslatorHelpers::pform_data_by_code(
        $admin->values['plural']);
}

/**
 * Performs validation of input variables
 *
 * @param Admin $admin
 * @param Text $text
 */
function validate_form(&$admin, &$text)
{
    global $required, $txtf;

    // language
    if (2 != strlen($admin->values['language'])) {
        $admin->general["required_error"] .= "<br/>" . $txtf->display('language_code_error');
        $required[] = 'language';
    }
}

if ($max_entries && $max_entries <= 50) { $general["max_entries"] = $max_entries; }

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

    $adm = new Admin($table);
    $sq = new sql;

    /* DB writing part */
    if ($do == "add") {
        // process and validate form data
        process_form($adm);
        validate_form($adm, $text);

        // if idfield will be set to language than Admin class will try to inser NULL value into
        // this field
        $old_idfield = $idfield;
        $idfield = '';

        // get nplurals and plural expression by selected plural group id
        $res = $adm->add($table, $required, $idfield);

        // restore idfield
        $idfield = $old_idfield;

        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

        } else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "update" && $id) {

        // process and validate form data
        process_form($adm);
        validate_form($adm, $text);

        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);

        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\""); }

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
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
            // remove related records from translations table
            $sq->query($adm->dbc, sprintf('DELETE FROM `translations` WHERE `language` = \'%s\'', addslashes($id)));

        } else {
            $result = $general["error"];
        }
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

        // get all language groups
        foreach (TranslatorHelpers::plural_lang_groups() as $group_id => $dummy) {
            // get plural expression for current group
            list(, $expr) = TranslatorHelpers::pform_data_by_code($group_id);
            if ($adm->fields['expr']['value'] == $expr) {
                $adm->fields['plural']['value'] = $group_id;
                break;
            }
        }

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
