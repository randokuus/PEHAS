<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/admin.class.php');
require_once(SITE_PATH . '/class/Strings.php');
require_once(SITE_PATH . '/class/Versioning.php');

$trf = &ModeraTranslator::instance($language2, 'admin_decline');

if (!is_array($id) && !is_numeric($id) && $_id = unserialize($id)) {
    $id = $_id;
}

if (!$id || !$redirect) {
    redirect('admin/dashboard.php');
}

$fields = array('message' => '');
$field_groups = array(1 => array($trf->tr('decline_message'), ''));
$general['template_form'] = 'tmpl/admin_form1.html';
$general['button'] =   $trf->tr('submit');
$general['extra_buttons'] = array(
    1 => array(
        $trf->tr('cancel'),
        "window.location='$redirect'",
        'pic/button_decline.gif',
        '',
    ),
);

// objects initialization
$perm = new Rights($group, $user, 'content', true);
$perm->Access(null, $id, 'm', null);
$versioning = new Versioning($database);
$adm = new Admin(null);

if ($do) {
    if (!$perm->canPublish()) {
        redirect('error.php?error=403');
    }

    $message = trim($message);
    if ('' != $message) {
        if ('update' == $do) {
            $vdata = $versioning->getCurrentData('content', $id);
            if (isset($vdata['__notice'])) {
                $versioning->injectToCurrentData('content', $id, array(
                    '__notice' => $message));

            }
        } else {
            require_once(SITE_PATH . '/class/ContentStructure.php');
            require_once(SITE_PATH . '/class/ContentWorkflow.php');
            require_once(SITE_PATH . '/class/ContentActions.php');
            require_once(SITE_PATH . '/class/PageTags.php');

            $p_tags = new PageTags($database);
            $c_structure = new ContentStructure($database);
            $c_actions = new ContentActions($database, $p_tags, $versioning, $c_structure);
            $c_workflow = new ContentWorkflow($database, $perm);
            $c_workflow->setContentActions($c_actions);
            $c_workflow->setVersioning($versioning);
            $c_workflow->setContentStructure($c_structure);

            if (!is_array($id)) {
                $id = array($id);
            }

            foreach ($id as $content_id) {
                $c_workflow->decline($content_id, $message);
            }
        }

        redirect('admin/' . $redirect);

    } else {
        // message was not entered
        $adm->badfields[] = '';
    }
}

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general['template_main']);
$tpl->addDataItem('TITLE', $trf->tr('decline'));

$adm->assignProp('message', 'type', 'textfield');
$adm->assignProp('message', 'rows', 10);
$adm->assignProp('message', 'cols', 100);
$adm->assignHidden('redirect', htmlspecialchars($redirect));

switch ($show) {
    case 'modify':
        $vdata = $versioning->getCurrentData('content', $id);
        $adm->assign('message', $vdata['__notice']);
        $do = 'update';
        break;

    default:
        $do = 'save';
        break;
}

$result = $adm->form($fields, null, null, null, $do, htmlspecialchars(serialize($id))
    , $field_groups, array());
// ugly hack: removing <td> with empty label
$result = str_replace('<td align="left"><label for="action" class="left">'
    . '<font color="">:</font>&nbsp;</label></td>', '', $result);
$tpl->addDataItem('CONTENT', $result);
echo $tpl->parse();