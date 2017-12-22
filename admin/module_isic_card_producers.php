<?php

require_once('admin_header.php');
require_once('../class/admin.class.php');       // administration main object
require_once('../class/templatef.class.php');   // site default template object
require_once('../class/IsicDB.php');   // site default template object

$module_name = 'module_isic_card_producers';

$translator =& ModeraTranslator::instance($language2, 'module_isic_card_producers');

$table = 'module_isic_card_producers';
$idfield = 'id';

$general['sort'] = "$table.name ASC"; // default sort to use
$general['template_form'] = "tmpl/admin_form.html";
$general['template_main'] = "tmpl/admin_main_module.html";

$fields = array(
    'name' => $translator->tr('producer_name')
);


//
// page tabs
//
function setupTabs() {
    global $tabs, $translator;
    if (count(IsicDB::factory('CardProducers')->listUnregisteredProducers()) == 0) {
        $tabs = array(
            1 => array($translator->tr('modify', null, null, 'admin_general'),$_SERVER['PHP_SELF']),
        );
    }
    else {
        $tabs = array(
            1 => array($translator->tr('add', null, null, 'admin_general'),$_SERVER['PHP_SELF'].'?show=add',),
            2 => array($translator->tr('modify', null, null, 'admin_general'),$_SERVER['PHP_SELF']),
        );
    }
}
setupTabs();

//
// array of fieldgroups for form
//
$field_groups = array(
    1 => array($translator->tr('fieldset1', null, null, 'admin_general'), ''),
);

$fields_in_group = array();


//
// the fields that we want to update (do not include primary key (id) here)
//
//$upd_fields = array('default', 'active');

//
// which data columns to display in the list
//
$disp_fields = array(
    // listnumber displays the number of current row starting from 1
    'id' => $translator->tr('nr', null, null, 'admin_general'),
    // if you want to display the ID also
    'name' => $translator->tr('producer_name')
);

//
// required fields
//
$required = array();

// ##############################################################
// To construct the main list query SELECT what from where / also which fields to include in the Filter command

$what = array(
    "`$table`.*",
);

$from = array(
    $table
);

$where = "";

$filter_fields = array(
    "`$table`.`name`"
);


/**
 * External function is called with every form/list call. Here you can define or
 * redefine values, lists, fields and their types.
 *
 * @param Admin $adm reference to admin object
 * @param string $show show variable, add/modify
 * @param int $id field value
 * @param string $language language code
 * @global $GLOBALS["translator"]
 */
function external(&$adm, $show, $id, $language)
{
    global $adm, $translator, $fields, $do;
    if (($show == 'modify' || $do == 'update') && $id) {
        $producerData = IsicDB::factory('CardProducers')->getRecord($id);
        IsicDB::assert($producerData, 'Card producer not found');
        $producerName = $producerData['name'];
    } else {
        $producerData = array();
        $producerName = $_POST['name'];
    }
    
    if ($show == 'add' && !$_POST['submit_to']) {
        $list = array('' => $translator->tr('select_producer'));
        foreach (IsicDB::factory('CardProducers')->listUnregisteredProducers() as $pName) {
            $list[$pName] = $pName;
        }
        $adm->assignProp('name', 'type', 'select');
        $adm->assignProp('name', 'list', $list);
        $adm->assignProp('name', 'java', 'onchange="submitTo();"');
    }
    
    if ($producerName) {
        $adm->assignProp('name', 'type', 'textinput');
        $adm->assignProp('name', 'value', $producerName);
        $adm->displayOnly('name');
        if ($_POST['name']) {
            $adm->assignHidden("name", $_POST['name']);
        }
        foreach (IsicDB::factory('CardProducers')->getConfigFields($producerName) as $field) {
            $adm->assignProp("config_$field", 'type', 'textinput');
            $adm->assignProp("config_$field", 'value', $producerData['config'][$field]);
            $fields["config_$field"] = $translator->tr($producerName . '_' . $field);
        }
    }
    
    $adm->assignHidden("submit_to", "0");
}


/**
 * Cachemanager is called after every add/modify/update
 *
 * @param string $action add, modify or delete
 */
function cacheManager($action)
{
    // let's clear all tpl_demo1* cache files (may not be necessary for all modules)
    clearCacheFiles('tpl_demo1', 'tpl_demo1_something');
}

if ($_REQUEST['max_entries'] && $_REQUEST['max_entries'] <= 100) {
    $general['max_entries'] = $_REQUEST['max_entries'];
}

$show = $_REQUEST['show'];
$id = $_REQUEST['id'];
$do = $_REQUEST['do'];
$start = $_REQUEST['start'];
$sort = $_REQUEST['sort'];
$sort_type = $_REQUEST['sort_type'];
$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general['template_main']);

$tpl->addDataItem('CONFIRMATION', $general['delete_confirmation']);

$adm = new Admin($table);
$adm->assign('user', $user);
$adm->assign('language', $language);

if ($do == 'add' && $_POST['submit_to']) {
    $show = 'add';
    $do = '';
}


// ##############################################################
// DB writes
switch (true){
    case $do == 'add':
        // permissions
        $perm->Access (0, 0, 'a', $module_name);
        //$res = $adm->add($table, $required, $idfield);
        $producerData = array('name' => $_POST['name'], 'config' => array());
        foreach (IsicDB::factory('CardProducers')->getConfigFields($producerData['name']) as $field) {
            if (isset($_POST["config_$field"])) {
                $producerData['config'][$field] = $_POST["config_$field"];
            }
        }
        IsicDB::factory('CardProducers')->insertRecord($producerData);
        $res = 0;
        if ($res == 0) {
            $tpl->addDataItem('NOTICE', "onLoad=\"notice('" . $general['add_text'] . "')\"");
            cacheManager('add');
            exit(redirect($_SERVER['PHP_SELF']));
        } else {
            $tpl->addDataItem('NOTICE', "onLoad=\"notice('" . $general['required_error'] . "')\"");
            $adm->getValues();
            $adm->types();
            external($adm, $show, $id, $language);
            $result .= $adm->form($fields, $sort, $sort_type, $filter, 'add', $id, $field_groups, $fields_in_group);
        }
        break;
    case $do == 'update' && $id:
        // permissions
        $perm->Access(0, $id, 'm', $module_name);
        //$res = $adm->modify($table, $upd_fields, $required, $idfield, $id); 
        $producerData = IsicDB::factory('CardProducers')->getRecord($id);
        foreach (IsicDB::factory('CardProducers')->getConfigFields($producerData['name']) as $field) {
            if (isset($_POST["config_$field"])) {
                $producerData['config'][$field] = $_POST["config_$field"];
            }
        }
        IsicDB::factory('CardProducers')->updateRecord($id, $producerData);
        $res = 0;
        if ($res == 0) {
            $tpl->addDataItem('NOTICE', "onLoad=\"notice('" . $general['modify_text'] . "')\"");
            cacheManager('modify');
        } else {
            $tpl->addDataItem('NOTICE', "onLoad=\"notice('" . $general['required_error'] . "')\"");
            $adm->getValues();
            $adm->types();
            external($adm, $show, $id, $language);
            $result .= $adm->form($fields, $sort, $sort_type, $filter, 'update', $id, $field_groups
              , $fields_in_group);
        }
        break;

    case $do == 'delete' && $id:
        // permissions
        $perm->Access (0, $id, 'd', $module_name);
        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem('NOTICE', "onLoad=\"notice('" . $general["delete_text"] . "')\"");
            cacheManager('delete');
            exit(redirect($_SERVER['PHP_SELF']));
        } else {
            $result = $general['error'];
        }
        break;
}


// Show part
switch (true){
    case $show == 'add':
        // permissions
        $perm->Access (0, 0, 'a', $module_name);

        if ($copyto != '')  {
            $adm->fillValues($table, $idfield, $copyto);
        }
        $adm->types();
        external($adm, $show, $id, $language);
        $result = $adm->form($fields, $sort, $sort_type, $filter, 'add', $id, $field_groups
           , $fields_in_group);
        break;
    case $show == 'modify' && $id:
        // permissions
        $perm->Access (0, $id, 'm', $module_name);
        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external($adm, $show, $id, $language);
        $result = $adm->form($fields, $sort, $sort_type, $filter, 'update', $id, $field_groups
           , $fields_in_group);
        break;
    case !$res || $res == 0:
       // list

       // permissions
       $perm->Access (0, 0, 'm', $module_name);
       external($adm, $show, $id, $language);
       $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter
          , $filter_fields, $idfield);
       break;
}


if ($show == 'add' || ($do == 'add' && is_array($res))) {
    $tpl->addDataItem('TITLE', $translator->tr('module_title'));
    $active_tab = 1;
} else {
    $tpl->addDataItem('TITLE', $translator->tr('module_title'));
    $active_tab = 2;
}

if (is_array($tabs_list) && sizeof($tabs_list) > 0) {
    // LIST VIEW
    if ((($show == 'modify' && !$id) || !$show) && (!$res || $res == 0)) {
        $nr = 1;
        while(list($key, $val) = each($tabs_list)) {
            $tpl->addDataItem('TABS.ID', $nr);
            $tpl->addDataItem('TABS.URL', "javascript:fieldJump($nr, ".sizeof($tabs_list).", '".$val[1]."');");
            $tpl->addDataItem('TABS.NAME', $val[0]);
                if ($active_tab == $nr) {
                    $tpl->addDataItem('TABS.CLASS', "class=\"active\"");
                } else {
                    $tpl->addDataItem('TABS.CLASS', "class=\"\"");
                }
            $nr++;
        }
    }
    // FORM VIEW
    else {
        $nr = 1;
        while(list($key, $val) = each($tabs)) {
            $tpl->addDataItem('TABS.ID', $nr);
            $tpl->addDataItem('TABS.URL', "javascript:enableFieldset($nr, 'fieldset$key', '', "
             . sizeof($tabs).", ".sizeof($field_groups).");");
            //$tpl->addDataItem('TABS.URL', "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
            $tpl->addDataItem('TABS.NAME', $val);
            if ($key == 1) {
                $tpl->addDataItem('TABS.CLASS', "class=\"active\"");
            }
            $nr++;
        }

        $result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit("
          . sizeof($field_groups).");</SCRIPT>\n";
    }
}
// 1 tabset
else {
    $nr = 1;
    while(list($key, $val) = each($tabs)) {
        $tpl->addDataItem('TABS.ID', $nr);
        $tpl->addDataItem('TABS.URL', "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
        $tpl->addDataItem('TABS.NAME', $val[0]);
            if ($active_tab == $nr) {
                $tpl->addDataItem('TABS.CLASS', "class=\"active\"");
            }
            else {
                $tpl->addDataItem('TABS.CLASS', "class=\"\"");
            }
        $nr++;
    }

    $result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";
}

$tpl->addDataItem('CONTENT', $result);
echo $tpl->parse();
