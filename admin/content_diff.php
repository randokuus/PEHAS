<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once(dirname(__FILE__) . '/admin_header.php');
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/admin.class.php");
require_once(SITE_PATH . "/class/module.user.class.php");
require_once(SITE_PATH . "/class/aliases_helpers.php");
require_once(SITE_PATH . '/class/Versioning.php');
require_once(SITE_PATH . '/class/PageTags.php');
require_once(SITE_PATH . '/class/Diff.php');


/**
 * Get admin user info.
 *
 * @param Database $db
 * @param int $id
 * @return array|FALSE
 */
function get_admin_user(&$db, $id) {
    static $admin_users;
    $id = (int)$id;

    if (is_null($admin_users)) $admin_users = array();

    if (!isset($admin_users[$id])) {
        $admin_users[$id] = $db->fetch_first_row("SELECT * FROM `adm_user` WHERE `user` = ?;", $id);
    }

    return $admin_users[$id];
}


// Translators.
$trf =& ModeraTranslator::instance($language2, 'admin_content');
$tr  =& ModeraTranslator::instance($language2, 'content_diff');

$result  = '';

$general['from'] = $tr->tr('from');
$general['to'] = $tr->tr('to');

$view_modes = array('simple', 'full', 'normal');

// default values for Diff object
$default_values = array(
    'view_type' => 'inline',
    'ignore_type' => array('blank_lines', 'case_changes', 'white_spaces'),
    'inline' => 2,
);
// ignore types for Diff object
$ignore_type = array(
    'blank_lines'  => $tr->tr('blank_line'),
    'case_changes'  => $tr->tr('case_changes'),
    'white_spaces'  => $tr->tr('white_spaces'),
);
// view types for Diff object
$view_type = array(
    'inline'    => $tr->tr('inline'),
    'sidebyside'=> $tr->tr('sidebyside'),
);
// Table styles for Diff object
$diff_styles = array(
    'unmod'     => 'unmod',
    'position'  => 'position',
    'space'     => 'space',
    'add'       => 'add',
    'remove'    => 'remove',
    'modifi'    => 'modifi',
    'head'      => 'head',
    'table'     => 'datatable', // diff_table
);

$content_fields = array(
    'title',
    'tags',
    'lead',
    'keywords',
    'visible',
    'first',
    'template',
    'menu',
    'ajax',
    'login',
    'logingroups',
    'redirect',
    'redirectto',
    'redirect_child',
    'new_window',
    'uri_alias',
//    'module',
    'owner',
//    'perm_group',
//    'perm_other',
);
$onoff_fields = array(
    'visible', 'first', 'menu', 'ajax', 'redirect', 'login', 'new_window'
);

$tabs = array(
    1   => $tr->tr('dashboard'),
    2   => $tr->tr('timeline'),
    3   => $tr->tr('changeset'),
);
$tab_links = array(
    1 => "dashboard.php",
    2 => "?content=$ATT[object_id]&node_type=$ATT[object_type]",
    3 => '#',
);
$active_tab = 2;

// Prepare/validate input data.
$ATT = array_merge($_GET, $_POST);
$ATT['changeset']   = (int)@$ATT['changeset'];
$ATT['node_type']   = @$ATT['node_type'];
$ATT['view_mode']   = @$ATT['view_mode'];
$ATT['content']     = (int)@$ATT['content'];
$ATT['id1']         = (int)@$ATT['id1'];
$ATT['id2']         = (int)@$ATT['id2'];
$ATT['nooptions']   = (bool)@$ATT['nooptions'];
$ATT['nolinks']     = (bool)@$ATT['nolinks'];
$ATT['inline']      = (int)@$ATT['inline'];
if (!$ATT['view_type']) $ATT['view_type'] = $default_values['view_type'];
if (!$ATT['inline']) $ATT['inline'] = $default_values['inline'];
if (!$ATT['ignore_type'] && !$ATT['btn_update']) $ATT['ignore_type'] = $default_values['ignore_type'];
if (!in_array($ATT['view_mode'], $view_modes)) $ATT['view_mode'] = 'normal';

$ATT['nooptions'] = true;

$versioning =& new Versioning($database);

$diff =& new Diff();
// table head
$diff->setOldStringHead("Old");
$diff->setNewStringHead("New");
// set styles
foreach ($diff_styles as $key => $val) {
    $diff->setStyle($key, $val);
}
//presentation type
$diff->setViewType($ATT['view_type']);
// arround lines
$diff->setArroundLines($ATT['inline']);
// ignore parameters
foreach ($ATT['ignore_type'] as $val) {
    if (array_key_exists($val, $ignore_type)) $diff->setIgnore($val);
}

// Main template.
$tpl = new Template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

// Secondary template object
$tpl_tl = new Template();
$tpl_tl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl_tl->setTemplateFile('tmpl/content_timeline.html');

/**
 * View differences for changeset and previous version
 *  OR for 2 different changeset's, which users preselects manually.
 */
if ($ATT['changeset'] || ($ATT['id1'] && $ATT['id2'])) {

    $tpl_tl->setTemplateFile('tmpl/content_diff.html');
    $active_tab = 3;
    $current_version;
    $previous_version;

    if ($ATT['changeset']) {
        $current  = $versioning->getRawVersion($ATT['changeset']);
        $previous = $versioning->getPreviousRawVersion($current['object_type'], $current['object_id'], $ATT['changeset']);

        $current_version  = $ATT['changeset'];
        $previous_version = $previous['id'];

        $tpl->addDataItem("TITLE", $tr->tr("changeset_title", array($ATT['changeset'])));
        $tpl_tl->addDataItem('FORM_URL', "?changeset=$ATT[changeset]");
    } else {
        $current  = $versioning->getRawVersion($ATT['id1']);
        $previous = $versioning->getRawVersion($ATT['id2']);

        $current_version  = $ATT['id1'];
        $previous_version = $ATT['id2'];

        $tpl->addDataItem("TITLE", $tr->tr("changes_title", array($ATT['id1'], $ATT['id2'])));
        $tpl_tl->addDataItem('FORM_URL', "?id1=$ATT[id1]&id2=$ATT[id2]");
    }

    $timeline_url = "?content=$current[object_id]&node_type=$current[object_type]";

    $diff->setOldStringHead($general['from']);
    $diff->setNewStringHead($general['to']);

    // Show options if only flag 'nooptions' is not set.
    if (!$ATT['nooptions']) {
        $tpl_tl->addDataItem('DIFF_OPTIONS', '');
        $tpl_tl->addDataItem('DIFF_OPTIONS.INLINE_VALUE', $ATT['inline']);
        // Ignore types for Diff object
        foreach ($ignore_type as $key => $val) {
            $tpl_tl->addDataItem('DIFF_OPTIONS.IGNORE_TYPES.VALUE', $key);
            $tpl_tl->addDataItem('DIFF_OPTIONS.IGNORE_TYPES.TEXT', $val);
            if (in_array($key, $ATT['ignore_type'])) {
                $tpl_tl->addDataItem('DIFF_OPTIONS.IGNORE_TYPES.CHECKED', 'checked');
            }
        }

        // View types for Diff object
        foreach ($view_type as $key => $val) {
            $tpl_tl->addDataItem('DIFF_OPTIONS.VIEW_TYPES.VALUE', $key);
            $tpl_tl->addDataItem('DIFF_OPTIONS.VIEW_TYPES.TEXT', $val);
            if ($key == @$ATT['view_type']) {
                $tpl_tl->addDataItem('DIFF_OPTIONS.VIEW_TYPES.SELECTED', 'selected="selected"');
            }
        }
    }

    // Add changed fields into template.
    $tpl_tl->addDataItem('CHANGED_ELEMENTS.TITLE', $tr->tr('changed_elements'));
    $tpl_tl->addDataItem('CHANGED_ELEMENTS.HEADER.TITLE', $tr->tr('content_parameter'));
    $tpl_tl->addDataItem('CHANGED_ELEMENTS.HEADER.TITLE', $general['from']);
    $tpl_tl->addDataItem('CHANGED_ELEMENTS.HEADER.TITLE', $general['to']);

    foreach ($content_fields as $field) {
        if (($current['object_data'][$field] == $previous['object_data'][$field])
            || ( in_array($field, $onoff_fields)
            && (bool)$current['object_data'][$field] == (bool)$previous['object_data'][$field]))
        {
            continue;
        }

        $tpl_tl->addDataItem('CHANGED_ELEMENTS.CHANGES.FIELD', $trf->tr($field));
        if (in_array($field, $onoff_fields)) {
            $old_value = $previous['object_data'][$field]?'ON':'OFF';
            $new_value = $current['object_data'][$field]?'ON':'OFF';
        } elseif($field == 'template') {
            $old_value = $GLOBALS["temp_desc_".$language][1][$previous['object_data'][$field]];
            $new_value = $GLOBALS["temp_desc_".$language][1][$current['object_data'][$field]];
        }else {
            $old_value = $previous['object_data'][$field];
            $new_value = $current['object_data'][$field];
        }
        $tpl_tl->addDataItem('CHANGED_ELEMENTS.CHANGES.OLD', $old_value);
        $tpl_tl->addDataItem('CHANGED_ELEMENTS.CHANGES.NEW', $new_value);
    }

    $tpl_tl->addDataItem('REVERS_DIFF_LINK', "?id1=$previous_version&id2=$current_version");
    $tpl_tl->addDataItem('TIMELINE_LINK', $timeline_url);

    $difference = $diff->getDiffs($previous['object_data']['text'], $current['object_data']['text']);

    //DIFFERENCE
    if ($diff->differ) {
        $tpl_tl->addDataItem('DIFFERENCE.TITLE', $tr->tr('content_difference'));
        $tpl_tl->addDataItem('DIFFERENCE.DIFF_TABLE', $difference);
    }

    if ($ATT['view_mode'] == 'simple') {
        $result .= '<style type="text/css">.infopanel, .tabmenu-dark{display:none;}</style>';
    }
    $result .= $tpl_tl->parse();

}
/**
 * Show Time line for given object
 */
else if($ATT['content'] && $ATT['node_type'])
{
    unset($tabs[3]);

    $time_line    = $versioning->getObjectTimeline($ATT['node_type'], $ATT['content']);
    $last_version = $versioning->getCurrentData($ATT['node_type'], $ATT['content']);
    $timeline_url = "?content=$ATT[content]&node_type=$ATT[node_type]";

    $tpl->addDataItem("TITLE", $tr->tr("timeline_4_page", array($last_version['title'])));
    $tpl_tl->addDataItem("TITLE", $tr->tr("timeline"));
    $tpl_tl->addDataItem("BUTTON_DIFFER", $tr->tr('button_view_changes'));

    $fields = array(
        'changeset' => $tr->tr('changeset'),
        'old'       => $general['from'],
        'new'       => $general['to'],
        'date'      => $tr->tr('time'),
        'user'      => $tr->tr('user'),
        'title'     => $tr->tr('title'),
        'preview'   => $tr->tr('preview'),
        'restore'   => $tr->tr('restore'),
    );

    foreach ($fields as $col) {
        $tpl_tl->addDataItem("HEADER_COLUMNS.TEXT", $col);
    }
    $i = 0;

    $link = '<a href="%s" %s>%s</a>';
    $versions_num = count($time_line);
    foreach ($time_line as $line) {
        $user_info = get_admin_user($database, $line['mod_user']);
        if (!$user_info) {
            $_userdata = unserialize($line['mod_userdata']);
            $user_info = "<span style='text-decoration:line-through'>$_userdata[0] ($_userdata[1])</span>";
        } else {
            $user_info = $user_info['name'];
        }

        $id1_checked = $id2_checked = '';
        $i++;
        if ($i == 1) $id1_checked = 'checked="checked"';
        if ($i == 2) $id2_checked = 'checked="checked"';

        $tpl_tl->addDataItem("VERSIONS.TD_COL1", sprintf($link, "?changeset={$line['id']}", '', "[$versions_num]"));
        $tpl_tl->addDataItem("VERSIONS.TD_COL2", "<input type='radio' name='id1' value='$line[id]' onchange='viewBtnStatus();' onclick='viewBtnStatus();' $id1_checked/>");
        $tpl_tl->addDataItem("VERSIONS.TD_COL3", "<input type='radio' name='id2' value='$line[id]' onchange='viewBtnStatus();' onclick='viewBtnStatus();' $id2_checked/>");
        $tpl_tl->addDataItem("VERSIONS.TD_COL4", $line['mod_time']);
        $tpl_tl->addDataItem("VERSIONS.TD_COL5", $user_info);
        $tpl_tl->addDataItem("VERSIONS.TD_COL6", $line['object_data']['title']);
        $tpl_tl->addDataItem("VERSIONS.TD_COL7"
            , "<a href='#' onclick=\"newWindow('../preview_page.php?changeset=$line[id]', 600, 400);\">" . $tr->tr('preview') . "</a>");
        $tpl_tl->addDataItem("VERSIONS.TD_COL8" ,
        '<a href="#" onclick="if(confirm(\'' . addslashes($tr->tr('restore_from_this_version')) . '\')){ restoreContent('.$line['id'].');} return false;">' . $tr->tr('restore') . '</a>');
        $versions_num--;
    }
    $result = $tpl_tl->parse();

}
/**
 * If nothing selected, through an error.
 */
else
{
    trigger_error('Not enough parameters are given.', E_USER_ERROR);
}

$tab_links[2] = $timeline_url;
foreach ($tabs as $key => $val) {
    $tpl->addDataItem("TABS.ID", $key);
    $tpl->addDataItem("TABS.NAME", $val);
    $tpl->addDataItem("TABS.URL", $tab_links[$key]);
    if ($key == $active_tab) {
        $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
    }
}
$tpl->addDataItem('content', $result);
echo $tpl->parse();