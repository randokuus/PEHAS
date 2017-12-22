<?php
/**
 * @version $Revision: 970 $
 */

require_once('admin_header.php');
require_once(SITE_PATH . "/class/SearchReplace.php");
require_once(SITE_PATH . "/class/SearchHighlighter.php");
require_once(SITE_PATH . "/class/Arrays.php");
require_once(SITE_PATH . "/class/adminfields.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . '/class/aliases_helpers.php');
require_once(SITE_PATH . '/class/ContentWorkflow.php');
require_once(SITE_PATH . '/class/ContentStructure.php');
require_once(SITE_PATH . '/class/ContentActions.php');
require_once(SITE_PATH . '/class/PageTags.php');
require_once(SITE_PATH . "/class/Versioning.php");

/**
 * Get tables intersection
 *
 * Returns array of columns that present in both tables
 *
 * @param Database $database
 * @param string $table1
 * @param string $table2
 * @return array|FALSE
 */
function tables_intersect(&$database, $table1, $table2)
{
    $fields1 = $database->fetch_first_col("SHOW COLUMNS FROM ?f", $table1);
    $fields2 = $database->fetch_first_col("SHOW COLUMNS FROM ?f", $table2);
    if (!$fields1 || !$fields2) {
        return false;
    } else {
        return array_intersect($fields1, $fields2);
    }
}

/**
 * Make link to object administration page
 *
 * @param string $object
 * @param string $text
 * @param array $data
 * @return string
 */
function make_link($object, $text, $data)
{
    $link = "#";
    $on_click = '';

    switch ($object) {
        case 'pages':
            $node_type = urlencode($data['node_type']);
            $content = urlencode($data['content']);
            $language = urlencode($data['language']);
            $on_click = "load_right('content_admin.php?show=modify&id=$content"
                . "&node_type=$node_type&language=$language');";
            //
            // NB! load_left_content() js function and this call should be modified
            //
            $on_click .= "select_top_tab(1); load_left_content('');";
            break;

        case 'translations':
            $token = urlencode($data['token']);
            $domain = urlencode($data['domain']);
            $on_click = "load_right('settings_translator.php?do=edittr&token=$token&domain=$domain');";
            $on_click .= "select_top_tab(3); load_left_navi('settings_navi.php?selected=settings_translator.php');";
            break;

        case 'news':
            $id = urlencode($data['id']);
            $on_click = "load_right('module_news.php?show=modify&id=$id');";
            $on_click .= "select_top_tab(4); load_left_navi('modules_navi.php?selected=module_news.php');";
            break;

        case 'products':
            $id = urlencode($data['id']);
            $on_click = "load_right('module_products.php?show=modify&id=$id');";
            $on_click .= "select_top_tab(4); load_left_navi('modules_navi.php?selected=module_products.php');";
            break;
    }

    return sprintf('<a href="%s" onClick="%s">%s</a>', htmlspecialchars($link), $on_click, $text);
}

$translator =& ModeraTranslator::instance($language2, 'dashboard');

$tpl = new Template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);
$tpl->addDataItem("TITLE", $translator->tr("dashboard_title"));

//
// Dashboard template
//

$tpl_db = new Template();
$tpl_db->setCacheLevel(TPL_CACHE_NOTHING);
$tpl_db->setTemplateFile('tmpl/dashboard.html');

//
// init search form
//

$tpl_db->addDataItem('FORM_SEARCH_ACTION', './' . basename(__FILE__) . '?do=search');
$tpl_db->addDataItem('FORM_REPLACE_ACTION', './' . basename(__FILE__));

$sr = new SearchReplace($GLOBALS['database']);
$available_objs = $sr->available_objects();

// available objects dropdown
$fdata = array(
    'type' => 'select',
    'list' => array_merge(array('_all' => $translator->tr('Everywhere'))
        , Arrays::array_combine($available_objs, $available_objs)),
);

$obj_sel = new AdminFields('object', $fdata);
$tpl_db->addDataItem('OBJECTS_SELECT', $obj_sel->display(@$_POST['object']));

// search & replace string values
$tpl_db->addDataItem('SEARCH_VAL', htmlspecialchars(@$_POST['search']));
$tpl_db->addDataItem('SEARCH_FORM_TITLE', $translator->tr('search_form_title'));
$tpl_db->addDataItem('SEARCH_LABEL', $translator->tr('search_label'));
$tpl_db->addDataItem('IN_LABEL', $translator->tr('in_label'));
$tpl_db->addDataItem('GO', $translator->tr('search_btn'));
$tpl_db->addDataItem('CHECK_ALL', $translator->tr('check_all_btn'));
$tpl_db->addDataItem('UNCHECK_ALL', $translator->tr('uncheck_all_btn'));

switch (@$_REQUEST['do']) {
    case 'search':
        //
        // search
        //

        $tpl_db->addDataItem('FORM_TITLE', $translator->tr('search_form_title'));
        $tpl_db->addDataItem('SEARCH_TBL.COL1_HDR', $translator->tr('replace_col_hdr'));
        $tpl_db->addDataItem('SEARCH_TBL.COL2_HDR', $translator->tr('object_col_hdr'));
        $tpl_db->addDataItem('SEARCH_TBL.COL3_HDR', $translator->tr('title_col_hdr'));
        $tpl_db->addDataItem('SEARCH_TBL.COL4_HDR', $translator->tr('text_col_hdr'));
        $tpl_db->addDataItem('REPLACE_CTRLS.REPLACE_CHECKED', $translator->tr('replace_btn'));
        $tpl_db->addDataItem('REPLACE_CTRLS.REPLACE_VAL', htmlspecialchars(@$_POST['replace']));

        //
        // perform search
        //
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $search = @$_POST['search'];
            $replace = @$_POST['replace'];
            $where = '_all' == @$_POST['object'] ? null : array(@$_POST['object']);

            $s_res = $sr->search($search, null, $where);

            // break from cycle if there are no results found
            if (!$s_res) {
                break;
            }

            // enable replace controls
            $tpl_db->addDataItem('REPLACE_CTRLS.', '');

            $tpl_db->addDataItem('REPLACE_CTRLS.REPLACE_LABEL', sprintf($translator->tr('replace_%s_with')
                , '<span style="background-color:yellow;">' . htmlspecialchars($search) . '</span>'));

            $highlighter = new SearchHighlighter();
            $highlighter->set_escape_cb('htmlspecialchars');
            $highlighter->set_output_maxlen(200);

            foreach ($s_res as $object_type => $matches) {
                $object_type = htmlspecialchars($object_type);
                foreach ($matches as $match) {
                    $title = htmlspecialchars($match['title']);
                    $matched_text = $match['data'];
                    $key = htmlspecialchars(serialize(array('object' => $object_type
                        ,'table' => $match['table'], 'key' => $match['key'])));

                    $tpl_db->addDataItem('SEARCH_TBL.RESULT_ROW.COL1', '<input type="checkbox" '
                        . 'name="where[]" onclick="on_checkbox_change();" value="' . $key . '" />');
                    $tpl_db->addDataItem('SEARCH_TBL.RESULT_ROW.COL2', $object_type);
                    $tpl_db->addDataItem('SEARCH_TBL.RESULT_ROW.COL3', make_link($object_type, $title, $match['key']));
                    $tpl_db->addDataItem('SEARCH_TBL.RESULT_ROW.COL4', make_link($object_type, $highlighter->highlight(
                        $matched_text, $search), $match['key']));
                }
            }
        }

        break;

    case 'replace':
        //
        // replace
        //

        // only root users have permission for using replace feature
        $perm->Root();

        foreach (array('search', 'replace', 'where') as $v) {
            $$v = $_POST[$v];
        }

        // search string should not be empty
        // there should be some replacements checked
        if ('' !== (string)$search && $where) {
            foreach ($where as $awhere) {
                $awhere = unserialize($awhere);
                $sr->replace_one($search, $replace, $awhere['object'], $awhere['table']
                    , $awhere['key']);
            }
        }

        // return back to search results
        redirect('admin/dashboard.php');
        break;

    case 'approve':
        $perm = new Rights($group, $user, 'content', false);
        $versioning = new Versioning($database);
        $p_tags = new PageTags($database);
        $c_structure = new ContentStructure($database);
        $c_actions = new ContentActions($database, $p_tags, $versioning, $c_structure);
        $c_workflow = new ContentWorkflow($database, $perm);
        $c_workflow->setPageTags($p_tags);
        $c_workflow->setContentStructure($c_structure);
        $c_workflow->setVersioning($versioning);
        $c_workflow->setContentActions($c_actions);

        // does not care about status of approvment, however later user interface
        // can be improved with nicely formatted approment results
        if (is_array($_POST['where'])) {
            foreach ($_POST['where'] as $content_id) {
                $c_workflow->approve($content_id);
            }
        }

        redirect('admin/dashboard.php');
        break;

    case 'decline':
        // redirect to content_decline.php for specifying decline message
        if (is_array($_POST['where']) && $_POST['where']) {
            $url = 'admin/content_decline.php?redirect=dashboard.php';
            foreach ($_POST['where'] as $id) {
                $url .= '&id[]=' . urlencode($id);
            }
            redirect($url);
        } else {
            redirect('admin/dashboard.php');
        }

        break;

    case 'last_changes':
    default:
        //
        // Show last changes
        //

        // enable workflow controls
        $tpl_db->addDataItem('WORKFLOW_CTRLS.', '');

        $tpl_db->addDataItem('FORM_TITLE', $translator->tr('content_update_header'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL1_HDR', $translator->tr('select'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL2_HDR', $translator->tr('date_col_hdr'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL3_HDR', $translator->tr('user_col_hdr'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL4_HDR', $translator->tr('title_col_hdr'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL5_HDR', $translator->tr('page_type_col_hdr'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL6_HDR', $translator->tr('pending_col_hdr'));
        $tpl_db->addDataItem('WORKFLOW_TBL.COL7_HDR', $translator->tr('changes_col_hdr'));

        $versioning = new Versioning($database);
        $timeline = $versioning->getTimeline(array('content', 'trash', 'template')
            , array('title', 'language'));

        $perm = new Rights($group, $user, 'content', false);
        $c_structure = new ContentStructure($database);
        $c_workflow = new ContentWorkflow($database, $perm);
        $c_workflow->setContentStructure($c_structure);

        foreach ($timeline as $row) {
            $link_data = array('language' => $row['data']['language']
                , 'content' => $row['object_id'], 'node_type' => $row['object_type']);

            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL2', htmlspecialchars($row['mod_time']));

            $_style = $row['user_removed'] ? 'text-decoration:line-through;' : '';

            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL3', "<span style='white-space: nowrap;$_style'>"
                . htmlspecialchars($row['user']) . '</span>');

            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL4', make_link('pages'
                , htmlspecialchars($row['data']['title']), $link_data));
            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL5', $translator->tr('ptype_' . $row['object_type']));


            if ($row['pending']) {
                // check if user has enough permissions for approving/declining this action
                if ($c_workflow->canApprove($row['object_id'], $row['pending'])) {
                    $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL1', '<input type="checkbox" '
                        . 'name="where[]" onclick="on_checkbox_change();" value="'
                        . $row['object_id'] . '" />');
                } else {
                    $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL1', '-');
                }

                $pending_text = $translator->tr('pending' . $row['pending']);
                if (MODERA_PENDING_CHANGES == $row['pending']) {
                    $pending_text .= ' (<a href="#" onClick="newWindow(\'content_diff.php?changeset='.$row['id'].'&nooptions=1&view_mode=simple\',750,350);">diff</a>)';
                }
            } else {
                $pending_text = '-';
                $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL1', '-');
            }

            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL6', $pending_text);
            $tpl_db->addDataItem('WORKFLOW_TBL.RESULT_ROW.COL7', '<a href="content_diff.php?content='.$row['object_id'].'&node_type='.$row['object_type'].'">'
                . $translator->ntr('%d_times', $row['changes'], $row['changes'])
                . '</a>');
        }
}

$tpl->addDataItem('CONTENT', $tpl_db->parse());
echo $tpl->parse();