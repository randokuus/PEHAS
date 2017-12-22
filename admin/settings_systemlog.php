<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;


require_once('admin_header.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/admin.class.php');
require_once(SITE_PATH . '/class/Strings.php');

$tr  =& ModeraTranslator::instance($language2, 'admin_general');
$trf =& ModeraTranslator::instance($language2, 'admin_systemlog');

if ($max_entries) {
    $general['max_entries'] = $max_entries;
}

if ($show != 'modify' || !$id) {
    unset($id);
}

$table   = 'systemlog'; // SQL table name to be administered
$idfield = 'log_id';    // name of the id field (unique field, usually simply 'id')


// general parameters (templates, messages etc.)
$general['template_list']  = 'tmpl/admin_view_list.html';
$general['template_pages'] = 'tmpl/pages.html';
$general['sort']           = '`time` DESC'; // default sort to use


$disp_fields = array(
    'time'    => $trf->tr('date'),
    'source'  => $trf->tr('source'),
    'message' => $trf->tr('message')
);

/*
 * To construct the main list query SELECT what from where /
 * also which fields to include in the Filter command
 */

$what = array('*');
$from = array($table);


/* the fields for filtering */
$filter_fields = array(
    'time',
    'source',
    'message'
);

/* the fields in the table */
$fields = array(
    'time'     => $trf->tr('date'),
    'source'   => $trf->tr('source'),
    'message'  => $trf->tr('message')
);

$field_groups = array(
    1 => array($trf->tr('event_details'), ''),
);

$fields_in_group = array();

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general['template_main']);
$tpl->addDataItem("TITLE", $trf->tr('module_title'));

$adm = new Admin($table);

if ($show == 'modify' && $id) {

    $adm->general['button'] = $general['backtolist'];
    $adm->fillValues($table, $idfield, $id);
    $adm->types();

    foreach($fields as $key=>$val) {
        $adm->displayOnly($key);
    }

    $adm->assignHidden('max_entries', $max_entries);
    $adm->assignHidden('start', $start);

    $result = $adm->form($fields, $sort, $sort_tyyp, $filter
              , 'update', $id, $field_groups, $fields_in_group);

} else {

    $query = "SELECT DISTINCT `source` FROM `$table`";

    // getting distinct sources
    $sources = &$database->fetch_all($query);
    if($sources && is_array($sources)) {

        $fdata['type'] = 'select';
        $fdata['list'] = array('' => $trf->tr('all_sources'));
        foreach ($sources as $node) {
            $fdata["list"][$node['source']] = $node['source'];
        }

        $nfilter = new AdminFields('source', $fdata);
        $type_select = $nfilter->display($source);

        if ($source) {
            $adm->assignFilter('source', $source, "source = '"
                                 . addslashes($source) . "'", $type_select);
        } else {
            $adm->assignFilter('source', '', '', $type_select);
        }

        $adm->assignHidden('source', '', '', $type_select);
    }
    // results table
    $result = $adm->show($disp_fields, $what, $from, $where, $start
                    , $sort, $sort_type, $filter, $filter_fields, $idfield);

}

$tpl->addDataItem('CONTENT', $result);
echo $tpl->parse();
