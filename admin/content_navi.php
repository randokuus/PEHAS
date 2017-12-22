<?php
/**
 * @version $Revision: 461 $
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */

// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once(dirname(__FILE__) . '/admin_header.php');
require_once(SITE_PATH . "/class/templatef.class.php");

$tpl = new Template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile("tmpl/content_navi.html");

$trf = ModeraTranslator::instance($language2, 'admin_navi');

// pass language data (temporary solution)
$tpl->addDataItem('LANGUAGE_DATA', "
    'templates_hdr' : '".$trf->tr('templates_hdr')."',
    'structure_hdr' : '".$trf->tr('structure_hdr')."',
    'trash_hdr' : '".$trf->tr('trash_hdr')."',
    'new_page' : '".$trf->tr('new_page')."',
    'new_template' : '".$trf->tr('new_template')."',
    'empty_trash' : '".$trf->tr('empty_trash')."',
    'loading_msg' : '".$trf->tr('loading_msg')."',
    'saving_msg' : '".$trf->tr('saving_msg')."',
    'btn_up_msg' : '".$trf->tr('btn_up_msg')."',
    'btn_down_msg' : '".$trf->tr('btn_down_msg')."',
    'btn_remove_msg' : '".$trf->tr('btn_remove_msg')."',
    'remove_page' : '".$trf->tr('remove_page')."',
    'all_children_will_be_removed' : '".$trf->tr('all_children_will_be_removed')."',
    'remove_template' : '".$trf->tr('remove_template')."',
    'remove_from_trash' : '".$trf->tr('remove_from_trash')."',
    'confirm_empty_trash' : '".$trf->tr('confirm_empty_trash')."'
");

echo $tpl->parse();