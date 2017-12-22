<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/admin.class.php");
require_once(SITE_PATH . "/class/module.user.class.php");
require_once(SITE_PATH . "/class/aliases_helpers.php");
require_once(SITE_PATH . '/class/JsonEncoder.php');
require_once(SITE_PATH . '/class/Versioning.php');
require_once(SITE_PATH . '/class/ContentWorkflow.php');
require_once(SITE_PATH . '/class/PageTags.php');
require_once(SITE_PATH . '/class/ContentStructure.php');
require_once(SITE_PATH . '/class/ContentActions.php');
require_once(SITE_PATH . '/class/cache_helpers.php');

/**
 * Process fields array
 *
 * Takes associative array of fields => values and moves some elements to the
 * end of the array
 *
 * @param array $fields
 * @return array
 */
function reSortFields($fields) {
    global $is_root;
    $arr_before = array("text", "perm_group", "perm_other", "perm_owner");
    $arr_before = array(
        'text', 'publishing_date', 'expiration_date', 'menu', 'login'
        , 'logingroups', 'loginusertypes', 'redirect' , 'redirectto', 'new_window'
        , 'uri_alias', 'perm_group', 'perm_other', 'perm_owner', 'public'
    );

    $new_ar = array();
    foreach ($fields as $k => $v) {
        if (!in_array($k, $arr_before)) {
            $new_ar[$k] = $v;
        }
    }

    foreach ($fields as $k => $v) {
        if ($k == 'perm_owner' && !$is_root) {
            continue;
        }

        if (in_array($k, $arr_before)) {
            $new_ar[$k] = $v;
        }
    }
    return $new_ar;
}

/**
 * @todo write function description
 *
 * @param string $template
 * @return bool
 */
function checkTemplate($template) {
    global $adm, $language, $site_settings;

    if (!$template && $adm->fields["template"]["value"]) {
        $template = $adm->fields["template"]["value"];
    }

    if ($template && $template > 0) {

        $template_file = SITE_PATH . "/" . $GLOBALS["templates_".$language][$site_settings["template"]][1]
          . "/" . $GLOBALS["templates_".$language][$site_settings["template"]][2][$template] . ".html";

        if (file_exists($template_file)) {
            $tf=@fopen($template_file,"r");
            $return = @fread($tf, filesize($template_file));
            fclose($tf);
            return parseTemplate($return);
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
}

/**
 * Parse template and return parameters metadata for modules specified in it
 *
 * @param string $content
 * @return array
 */
function parseTemplate($content) {
    // fetch all object declarations from passed content
    preg_match_all("/<([\\/]?TPL_OBJECT[^>]*):([^>]*)>/i", $content, $tags, PREG_SET_ORDER);

    $list = array();
    foreach ($tags as $tag) {
        if ("TPL_OBJECT" == strtoupper($tag[1])) {
            $title = $tag[2];
            $o = new $title;
            if ($o->content_module) {
                $data = $o->moduleOptions();
                $list[] = array($title, $data[0], $data[1], $data[2]);
                if ($data[3] != "") {
                    $list[] = array($title . "1", $data[3], $data[4], $data[5]);
                }
                if ($data[6] != "") {
                    $list[] = array($title . "2", $data[6], $data[7], $data[8]);
                }
                if ($data[9] != "") {
                    $list[] = array($title . "3", $data[9], $data[10], $data[11]);
                }
            }
        }
    }

    return $list;
}

/**
 * @todo write function description
 *
 * @param Admin $adm
 * @return Admin
 */
function parseParameters($adm) {
    $ar = split(";", $adm->fields["module"]["value"]);
    foreach ($ar as $v) {
        $a = split("=", $v);
        $adm->assign("module_".$a[0], $a[1]);
    }
    return $adm;
}

/**
 * Pack POSTed module parameters into string
 *
 * @return string
 */
function makeParameters() {
    $result = array();
    foreach ($_POST as $key => $val) {
        if (false !== strpos($key, 'module_')) {
            if (is_array($val)) {
                $val = join(",", $val);
            }
            $result[] = substr($key, 7) . "=" . $val;
        }
    }
    return join(";", $result);
}

/**
 * Compare two arrays
 *
 * Check if all elelemns from $new_data exists in $old_data, elements
 * that exists only in $old_data array are ignored from comparision
 *
 * @param array $old_data
 * @param array $new_data
 * @return bool
 */
function content_data_changed($old_data, $new_data)
{
    foreach ($new_data as $field => $value) {
        if ($value != $old_data[$field]) {
            return true;
        }
    }

    return false;
}

/**
 * Postprocess permission parameters passed from form
 *
 * @param Admin $admin
 */
function processPermissions(&$admin)
{
    foreach (array("perm_group", "perm_other") as $v) {
        if (is_array($admin->values[$v])) {
            $p = array();
            for ($i = 0; $i < 3; $i++) {
                $p[] = in_array($i, $admin->values[$v]) ? 1 : 0;
            }
            $admin->values[$v] = join(",", $p);
        } else {
            $admin->values[$v] = "0,0,0";
        }
    }
}

/**
 * Update notice rendered by Admin object regarding passed pending status
 *
 * @param int $pending pending flag
 * @param string $decline_message
 * @param string $decline_user
 * @global $GLOBALS['adm']
 * @global $GLOBALS['perm']
 */
function set_pending_notice($pending, $decline_message = '', $decline_user = '')
{
    global $adm, $perm, $trf, $id, $c_workflow, $versioning;

    $notice = '';
    $declined = '' != $decline_message;
    $redirect = urlencode("content_admin.php?show=modify&id=$id");
    $preview_url = 'previewContent();';
    $decline_url = "window.location='content_decline.php?redirect=$redirect&id=$id';";
    $cancel_url = "window.location='content_admin.php?do=cancel&id=$id';";
    $buttons = array();

    // collect notice text and buttons metadata
    switch ($pending) {
        case MODERA_PENDING_CREATION:
            if ($perm->canPublish()) {
                $notice = $trf->tr('notice_pending_creation_can_publish');
                $buttons = array_merge($buttons, array(
                    'publish' => array('canApprove' => 'selectAll();'),
                    'decline' => $declined ? false : array('canDecline' => $decline_url),
                    'preview' => $preview_url,
                ));
            } else {
                $notice = $trf->tr('notice_pending_creation_no_publish');
                $confirm_text = $trf->tr('confirm_page_creation_cancel', null, 'js');
                $buttons['cancel'] = array('canCancel' => "if(confirm('$confirm_text'))"
                        . $cancel_url);
            }
            break;

        case MODERA_PENDING_CHANGES:
            $changeset = $versioning->getCurrentRawVersion('content', $id, array('id'));
            $changeset = $changeset['id'];
            $buttons['check'] = "newWindow('content_diff.php?changeset=$changeset&nooptions=1&view_mode=simple',750,350);";

            if ($perm->canPublish()) {
                $notice = $trf->tr('notice_pending_changes_can_publish');
                $buttons = array_merge($buttons, array(
                    'approve' => array('canApprove' => 'selectAll();'),
                    'decline' => $declined ? false : array('canDecline' => $decline_url),
                    'preview' => $preview_url,
                ));
            } else {
                $notice = $trf->tr('notice_pending_changes_no_publish');
                $buttons['undo'] = array('canCancel' => $cancel_url);
            }
            break;

        case MODERA_PENDING_REMOVAL:
            if ($perm->canPublish()) {
                $notice = $trf->tr('notice_pending_removal_can_publish');
                $confirm_text = $trf->tr('confirm_page_removal', null, 'js');

                $buttons = array_merge($buttons, array(
                    'approve' => array('canApprove' => "if(confirm('$confirm_text'))"
                        . "window.location='content_admin.php?id=$id&do=approve';"),
                    'cancel' => array('canDecline' => "window.location='content_admin.php"
                        . "?do=decline&id=$id';"),
                ));
            } else {
                $buttons['cancel'] = array('canCancel' => $cancel_url);
                $notice = $trf->tr('notice_pending_removal_no_publish');

            }
            break;

        default:
            return;
    }

    $buttons_html = array();
    foreach ($buttons as $label => $btn_data) {
        // do not display button if it's value is FALSE
        if (false === $btn_data) {
            continue;
        }

        if (is_array($btn_data)) {
            list($p_method, $link) = each($btn_data);
            // check permissions
            if (!$c_workflow->$p_method($id, $pending)) {
                continue;
            }
        } else {
            $link = $btn_data;
        }

        $label = $trf->tr($label);
        $buttons_html[] = "<a href=\"#\" onclick=\"$link return false;\">$label</a>";
    }

    if ($buttons_html) {
        $map = array(
            MODERA_PENDING_CREATION => 'creation',
            MODERA_PENDING_CHANGES => 'changes',
            MODERA_PENDING_REMOVAL => 'removal',
        );

        $token = 'buttons_suffix_' . $map[$pending] . '_' . ($perm->canPublish()
            ? 'can_publish' : 'no_publish');
        $notice .= '<br />' . $trf->tr('allowed_to') . ' ' . implode(', ', $buttons_html)
            . ' ' . $trf->tr($token);
    }

    // process decline message
    if ($declined) {
        $decline_message = htmlspecialchars($decline_message);
        $decline_user = htmlspecialchars($decline_user);
        $decline_message = $trf->tr('declined_by_%s_%s', array($decline_user
            , $decline_message));

        if ($perm->canPublish()) {
            $decline_message .= ' (<a href="content_decline.php?show=modify&'
                . "redirect=$redirect&id=$id\">" . $trf->tr('edit_message') . '</a>)';
        }

        $notice .= '<div class="decline-message">' . $decline_message . '</div>';
    }

    if ('' != $notice) {
        $adm->setNotice($notice);
    }
}
$perm2 = new Rights($group, $user, "root", false);
$is_root = $perm2->Root();
if (isset($id)) {
    $id = (int) $id;
}

$tr =& ModeraTranslator::instance($language2, 'admin_general');
$trf =& ModeraTranslator::instance($language2, 'admin_content');

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}

if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}
//
// get list of active users from module user
//

$res = &$database->query("SELECT `user`, `username` FROM `module_user_users`"
    . " WHERE `active` = 1 ORDER BY `username`");

if ($res && $res->num_rows()) {
    $js_save_user = 'document.cookie=\'preview_user_id=\' + '
        . 'this.options[this.selectedIndex].value + \';\';';
    $option_list = "<select name=\"preview_user_id\" onchange=\"$js_save_user\">";
    $option_list .= '<option value="-">- ' . $trf->tr("select_preview_user")
        . ' -</option>';

    while($row = $res->fetch_assoc()) {
        if (isset($_COOKIE["preview_user_id"]) && $_COOKIE["preview_user_id"] == $row["user"]) {
            $selected = " selected";
        } else {
            $selected = "";
        }

        $option_list .= "<option value=\"$row[user]\" $selected>"
            . htmlspecialchars($row["username"]) . "</option>";
    }
    $option_list .= '</select>';
} else {
    $option_list = '';
}

// general parameters (templates, messages etc.)
$general = array_merge($general, array(
    "template_main" => "tmpl/admin_main.html",
    "template_form" => "tmpl/admin_form1.html",
    "extra_buttons" => array(
        "1" => array(
            $tr->tr("preview"),
            "previewContent();",
            "pic/button_preview.gif",
            $option_list,
        ),
    ),
    "sort" => "zort ASC",
));

/* the fields in the table */
$fields = array(
    "template"  => $trf->tr("template"),
    "title"  => $trf->tr("title"),
    "keywords"  => $trf->tr("keywords"),
    "lead"  => $trf->tr("lead"),
    "tags" => $trf->tr("tags"),
    //"dummy" => "",
    //"pics"  => $trf->tr("pics"),
    //"files"  => $trf->tr("files"),
    "visible"  => $trf->tr("visible"),
    "publishing_date"  => $trf->tr("publishing_date"),
    "expiration_date"  => $trf->tr("expiration_date"),
    "menu"  => $trf->tr("menu"),
    "public"  => $trf->tr("public"),
    "login"  => $trf->tr("login"),
    "logingroups"  => $trf->tr("logingroups"),
    "loginusertypes"  => $trf->tr("loginusertypes"),
    "new_window"  => $trf->tr("new_window"),
    "redirect"  => $trf->tr("redirect"),
    "redirectto"  => $trf->tr("redirectto"),
    "uri_alias" => $trf->tr("alias"),
    "text"  => $trf->tr("text"),
    "perm_group" => $trf->tr("perm_group"),
    "perm_other" => $trf->tr("perm_other"),
);

$tabs = array(
    1 => $trf->tr("tabset1"),
    2 => "<strong style='color:990000;'>" . $trf->tr("tabset2") . "</strong>",
    3 => $trf->tr("tabset3"),
    4 => $trf->tr("tabset4"),
);

if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")) {
    if ($is_root) {
        $float = "right";
        $per1 = "44%";
        $per2 = "45%";
    }
    else {
        $float = "center; margin-left:20px";
        $per1 = "260";
        $per2 = "260";
    }
}
else {
    $float = "center";
    $per1 = "44%";
    $per2 = "45%";
}

$field_groups = array(
    1 => array($trf->tr("fieldset1"), ""),
    2 => array($trf->tr("fieldset2"), 'style="display:none;"'),
    3 => array($trf->tr("fieldset3"), 'style="display:none;"'),
    4 => array($trf->tr("fieldset4"), 'style="display:none;"'),
    5 => array($trf->tr("fieldset5"), 'style="display:none;"'),
);

$fields_in_group = array(
    "template"  => 1,
    "title"  => 1,
    "keywords" => 1,
    "lead" => 1,
    "tags" => 1,
    "visible"  => 1,
    "text"  => 2,
    //"pics"  => 1,
    //"files"  => 1,
    "publishing_date"  => 3,
    "expiration_date"  => 3,
    "menu"  => 3,
    "public"  => 3,
    "login"  => 3,
    "logingroups" => 3,
    "loginusertypes" => 3,
    "new_window" => 3,
    "redirect"  => 3,
    "redirectto"  => 3,
    "uri_alias" => 3,
    "perm_group" => 4,
    "perm_other" => 5,
);

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "template",
    "title",
    "keywords",
    "lead",
    "tags",
//  "text",
//  "pics",
//  "files",
    "visible",
    "menu",
    "public",
    "login",
    "logingroups",
    "loginusertypes",
    "redirect",
    "redirectto",
    "new_window",
    "module",
    "perm_group",
    "perm_other",
    "moddate",
    "moduser",
    "uri_alias",
    "is_published",
);

//
// Restore page content from selected version
//
if ($_GET['do'] == 'restore' && (int)$_GET['changeset']) {
    $_GET['changeset'] = (int)$_GET['changeset'];

    $_res_ver =& new Versioning($database);
    $_restore_data = $_res_ver->getRawVersion($_GET['changeset']);

    if ($_restore_data !== false && $_restore_data['object_id']) {
        $_POST = array_merge(
            $_POST,
            array('sort'=>null, 'sort_type'=>null, 'filter'=>null),
            $_restore_data['object_data']
        );

        $_POST['editor_src'] = "editor/editor.php?id=$id&node_type=$node_type";
        $_POST['editor_reload'] = 1;
        $_POST['submit_to'] = 1;
        $_POST['perm_group'] = split(',', $_POST['perm_group']);
        $_POST['perm_other'] = split(',', $_POST['perm_other']);

        extract($_POST);

        $node_type = $_restore_data['object_type'];
        $id = $_restore_data['object_id'];
        $do = 'update';
        $_GET = array();
    }
}


//
// Choose database table by node_type
//
switch ($node_type) {
    case "trash":
        if ("add" == $do || "add" == $show) {
            // creating new trash nodes is not allowed
            exit();
        }

        $perm = new Rights($group, $user, "trash", true);
        break;

    case "template":
        // unset array emelents in $fields and $fields_in_group arrays corresponding
        // to missing fields in content_template table
        unset($fields['is_published'], $fields["uri_alias"], $fields_in_group["uri_alias"]);

        $upd_fields = array_diff($upd_fields, array('uri_alias', 'is_published'));
        // recreate numeric indexes sequence
        $upd_fields = array_values($upd_fields);

        $perm = new Rights($group, $user, "template", true);
        break;

    case "content":
    default:
        $upd_fields[] = 'publishing_date';
        $upd_fields[] = 'expiration_date';
        $node_type = "content";
        $perm = new Rights($group, $user, "content", true);
}

// yes, it's mess of objects
$versioning = new Versioning($database);
$p_tags = new PageTags($database);
$c_structure = new ContentStructure($database);
$c_actions = new ContentActions($database, $p_tags, $versioning, $c_structure);
$c_workflow = new ContentWorkflow($database, $perm);
$c_workflow->setContentStructure($c_structure);
$c_workflow->setVersioning($versioning);
$c_workflow->setContentActions($c_actions);

// get db table name for current object type (content, template, trash)
$table = $c_actions->objectTypeToTable($node_type);

if ($node_type != 'content') {
    unset($fields['publishing_date']);
    unset($fields['expiration_date']);
    unset($fields_in_group['publishing_date']);
    unset($fields_in_group['expiration_date']);
}

$idfield = "content"; // name of the id field (unique field, usually simply 'id')
if ($is_root) {
    $fields["perm_owner"] = $trf->tr("owner");
    $field_groups[6] = array($trf->tr("owner"), 'style="display:none;"');
    $fields_in_group["perm_owner"] = 6;
    $upd_fields[] = "owner";
}

/* required fields */
$required = array(
    "template",
    "title"
);

function external() {
    global $user, $adm, $fields_error, $show, $do, $trf, $tr, $language, $id
        , $database, $table, $node_type, $idfield, $versioning;

    $adm->assignProp("text", "type", "nothing");
    $adm->displayOnly("text");

    $resize_btn = '<div style="text-align:right">';
    $btn_tmpl = '<input class="button" type="button" value="%spx"'
        . ' onClick="document.getElementById(\'contentFreim\').style.height=this.value;" />';
    $resize_btn .= '<label>' . $trf->tr('resize_to') . ': </label>';
    $resize_btn .= sprintf($btn_tmpl, 500);
    $resize_btn .= sprintf($btn_tmpl, 750);
    $resize_btn .= sprintf($btn_tmpl, 1000);
    $resize_btn .= '</div>';

    $adm->assign("text", '<iframe id="contentFreim" name="contentFreim"'
        . ' src="img/empty.gif" WIDTH="100%" HEIGHT="320" marginwidth="0" marginheight="0"'
        . ' scrolling="no" frameborder="0"></iframe>' . $resize_btn);

    if ($fields_error) {
        $adm->assignHidden("editor_src", "editor/editor.php?id=$id&node_type=$node_type"
            . "&error=true&rnd=".randomNumber());
    } else {
        $adm->assignHidden("editor_src", "editor/editor.php?id=$id&node_type=$node_type"
            . "&rnd=".randomNumber());
    }

    $adm->assignProp("keywords", "type", "textinput");
    $adm->assignProp("keywords", "size", 60);

    $adm->assignProp("lead", "rows", 2);
    $adm->assignProp("lead", "cols", 57);

    $adm->assignProp("tags", "type", "textinput");
    $adm->assignProp("tags", "size", 60);

    $adm->assignProp("visible", "type", "checkbox");
    $adm->assignProp("visible", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("new_window", "type", "checkbox");
    $adm->assignProp("new_window", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("redirect", "type", "checkbox");
    $adm->assignProp("redirect", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("redirectto", "type", "textinput");
    $adm->assignProp("redirectto", "size", 60);

    $adm->assignProp("redirectto", "extra"
        , '&nbsp;<a href="javascript:newWindow(\'select_hyperlink.php\', 500,320);">'
        . '<img align=top src="pic/link.gif" WIDTH="23" HEIGHT="22" border=0 alt=""></a>'
    );

    $adm->assignProp("uri_alias", "extra", "&nbsp;<a href=\"javascript:generateAlias('title', 'uri_alias', 'mpath_title'"
       . (strtolower($GLOBALS['site_settings']['lang']) != strtolower($language) ? ", '" . strtolower($language) . "'" : '' ). ")\">"
       . $trf->tr('generate_uri') . ".</a>&nbsp;&nbsp;" . $trf->tr("uri_alias_notice"));

    $adm->assignProp("menu", "type", "checkbox");
    $adm->assignProp("menu", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("ajax", "type", "checkbox");
    $adm->assignProp("ajax", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("public", "type", "checkbox");
    $adm->assignProp("public", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("login", "type", "checkbox");
    $adm->assignProp("login", "list", array("0" => $tr->tr("no"), "1" => $tr->tr("yes")));

    $adm->assignProp("perm_group", "type", "checkboxp");
    $adm->assignProp("perm_group", "display", "none");
    $adm->assignProp("perm_group", "list", array("0" => $tr->tr("perm_a"), "1" => $tr->tr("perm_m"), "2" => $tr->tr("perm_d")));

    $adm->assignProp("perm_other", "type", "checkboxp");
    $adm->assignProp("perm_other", "display", "none");
    $adm->assignProp("perm_other", "list", array("0" => $tr->tr("perm_a"), "1" => $tr->tr("perm_m"), "2" => $tr->tr("perm_d")));

    $adm->assignProp("logingroups", "type", "select2");
    $adm->assignProp("logingroups", "size", "5");

    $user = new User();
    $adm->assignProp("logingroups", "list", $user->getGroups());

    $adm->assignProp("loginusertypes", "type", "select2");
    $adm->assignProp("loginusertypes", "size", "5");

    $user = new User();
    $adm->assignProp("loginusertypes", "list", $user->getUserTypes());

    if ($show == "add") {
        $adm->fields["perm_owner"]["value"] = $user;
    } else {
        $adm->fields["perm_owner"]["value"] = $adm->fields["owner"]["value"];
    }
    $adm->assignProp("perm_owner", "type", "select");
    $adm->assignExternal("perm_owner", "adm_user LEFT JOIN adm_group ON adm_user.ggroup = adm_group.ggroup", "adm_user.user", "concat(adm_user.username, ' (', adm_group.name,')')", "ORDER BY adm_user.ggroup ASC, adm_user.username ASC", false);

    if ($GLOBALS["picsandfiles"]) {
        foreach(array('pics', 'files') as $p_type) {
            $p_ar = array();
            $p_list = split(",", $adm->fields[$p_type]["value"]);
            foreach ($p_list as $v) {
                $row = $database->fetch_first_row('SELECT `name`, `type` FROM `files`'
                    . ' WHERE `id` = ?', $v);
                if ($row) {
                    $p_ar[$v] = $row['name'] . '.' . $row['type'];
                }
            }

            $adm->assignProp($p_type, 'list', $p_ar);
        }

        $adm->assignProp("pics", "type", "select2");
        $adm->assignProp("pics", "size", "4");
        $adm->assignProp("files", "type", "select2");
        $adm->assignProp("files", "size", "3");

    } else {

        $adm->assign("pics", "");
        $adm->assign("files", "");
        $adm->assignProp("pics", "type", "nothing");
        $adm->assignProp("files", "type", "nothing");
    }

    //
    // process template selection combo box
    //

    $sel_template = $database->fetch_first_value("SELECT `template` FROM `settings`");

    if (count($GLOBALS["templates_".$language][$sel_template][2]) <= 1) {
        $adm->fields["template"]["value"] = "";
        $adm->assignProp("template", "type", "nothing");
    } else {

        if (!$adm->fields["template"]["value"]) {
            $adm->fields["template"]["value"] = 1;
        }

        $list[0] = "---";

        foreach ($GLOBALS["templates_".$language][$sel_template][2] as $key => $val) {
            if (file_exists(SITE_PATH . "/" . $GLOBALS["templates_".$language][$sel_template][1]
                . "/" . $val . ".html"))
            {
                if ($GLOBALS["temp_desc_".$language][$sel_template][$key] != "") {
                    $list[$key] = $GLOBALS["temp_desc_".$language][$sel_template][$key];
                } else if ($GLOBALS["temp_desc_EN"][$sel_template][$key] != "") {
                    $list[$key] = $GLOBALS["temp_desc_EN"][$sel_template][$key];
                } else {
                    $list[$key] = $GLOBALS["templates_".$language][$sel_template][2][$key];
                }
            }
        }

        $adm->assignProp("template", "type", "select");
        $adm->assignProp("template", "list", $list);
        $js = "document.forms['vorm'].elements['submit_to'].value = '1';"
            . "selectAll();";
        $adm->assignProp("template", "java", "onChange=\"$js\"");
    }

    //
    // special actions for content nodes
    //

    if ('content' == $node_type) {

        //
        // additional fields for expiration date.
        //

        $never_expires_checked = '';
        $never_expires_extra = '';
        $current_date = $adm->fields["expiration_date"]["value"];
        $txt_never_expires = $trf->tr('never_expires');

        // on save, update and add, check never_expires flag.
        // if it is on, then set expiration_date to ''
        if (('add' == $do || 'update' == $do) && $adm->values['never_expires']) {
            $adm->fields['expiration_date']['value'] = '';
            $adm->values['expiration_date'] = '';
        }

        if ((int)$adm->fields["publishing_date"]["value"] <= 0) {
            $adm->fields["publishing_date"]["value"] = date('Y-m-d H:i:s');
        }

        // For form, if expiration_date is null or 0000-00-00....
        // then set never_expires flag to ON and set expiration_date = ''
        if ((int)$adm->fields["expiration_date"]["value"] <= 0) {
           $adm->fields["expiration_date"]["value"] = '';
           $never_expires_checked = 'checked="checked"';
           $current_date = date('Y-m-d H:i:s');
        }

        $never_expires_extra = " <input type='checkbox' id='never_expires' name='never_expires' value='1' style='vertical-align:middle;'"
            . "onChange=\"javascript:expField('never_expires');\" "
            . "onClick=\"javascript:expField('never_expires');\"$never_expires_checked /> $txt_never_expires";

        $never_expires_extra .= <<<EOB
            <script type='text/javascript' language='javascript'>
            var expiration_date_val = '{$current_date}';
            var last_state = 2;
            expField = function(chk_box){
                var el = document.getElementById(chk_box);
                if (!el || el == '' || el == 'undefined') return;
                if (el.checked) {
                    if (last_state != 1 && document.forms[0].elements['expiration_date'].value.length > 0)
                    {
                        expiration_date_val = document.forms[0].elements['expiration_date'].value;
                        document.forms[0].elements['expiration_date'].style.color = 'gray';
                    }
                    document.forms[0].elements['expiration_date'].value = '{$txt_never_expires}';
                    document.forms[0].elements['expiration_date'].disabled = true;
                    last_state = 1;
                } else {
                    if (last_state != 0 && expiration_date_val.length > 0) {
                        document.forms[0].elements['expiration_date'].value = expiration_date_val;
                        document.forms[0].elements['expiration_date'].style.color = 'black';
                    }
                    document.forms[0].elements['expiration_date'].disabled = false;
                    last_state = 0;
                }
            }
            expField('never_expires');
            </script>
EOB;

        $adm->assignProp("expiration_date", "extra", $never_expires_extra);

        //
        // generate full aliases path for node.
        //

        if ($show == 'add') {
            if ($GLOBALS['parent_id']) {
                $mpath = $database->fetch_first_value('SELECT `mpath` FROM `content`'
                    . ' WHERE `content` = ?', $GLOBALS['parent_id']);
                $mpath .= $mpath ? '.' . $GLOBALS['parent_id'] : $GLOBALS['parent_id'];

            } else {
                $mpath = '';
            }
        } else if($show == 'modify') {
            $mpath = $database->fetch_first_value('SELECT `mpath` FROM `content`'
                . ' WHERE `content` = ?', $id);
        }

        $mpath_title = '';
        if ($mpath) {
            $_titles = $database->fetch_first_col("SELECT `title` FROM `content` WHERE content IN (!@)"
                , explode('.', $mpath));
            $mpath_title = join('/', $_titles);
        }

        $adm->assignHidden("mpath_title", $mpath_title);

        //
        // set pending notice
        //

        if ('modify' == $show || '' == $show) {

            $pending = $database->fetch_first_value('SELECT `pending` FROM ?f'
                . ' WHERE ?f = ?', $table, $idfield, $id);

            $decline_message = '';
            $decline_message = '';

            if (MODERA_PENDING_CHANGES == $pending || MODERA_PENDING_CREATION == $pending) {
                // load data from versioning table
                $vdata = $versioning->getCurrentData($node_type, $id);

                if (isset($vdata['__notice'])) {
                    $decline_message = $vdata['__notice'];
                    $decline_user = $vdata['__decline_user'];

                    // get username
                    $_tmp = $database->fetch_first_value("
                        SELECT
                            CONCAT(`name`, ' (', `username`, ')')
                        FROM
                            `adm_user`
                        WHERE
                            `user` = ?
                        ", $decline_user);
                    if ($_tmp) {
                        $decline_user = $_tmp;
                    } else {
                        $decline_user = "id ($decline_user)";
                    }
                }
            }

            set_pending_notice($pending, $decline_message, $decline_user);
        }
    }

    $adm->assignHidden("editor_reload", "0");
    $adm->assignHidden("submit_to", "0");
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
$tpl->addDataItem("STRUC", "");

//echo "<!-- " . $_POST["text"] . "-->";

if ($_POST["text"] != "") {
    if ($GLOBALS["editor"]) {

        $path_parts = parse_url(SITE_URL);
        $engine_url = $path_parts['path'];

        if (substr($engine_url, 0, 1) != "/") {
            $engine_url = "/" . $engine_url;
        }

        if (substr($engine_url, -1) != "/") {
            $engine_url = $engine_url . "/";
        }

        /*$_POST["text"] = preg_replace("/<\/?(HTML|HEAD|TITLE|BODY)>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<\/?(html|head|title|body)>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<META[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<meta[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<link rel[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<BODY[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<body[^>]*>\n?/", "", $_POST["text"]);
        $_POST["text"] = preg_replace("/<!DOCTYPE[^>]*>\n?/", "", $_POST["text"]);
        //$_POST["text"] = preg_replace("/'/", "&lsquo;", $_POST["text"]);
        $_POST["text"] = preg_replace("/\\\\'/m","'", $_POST["text"]);
        $_POST["text"] = trim($_POST["text"]);
        //$_POST["text"] = $text;*/
        $_POST["text"]=stripslashes($_POST["text"]);//remove slashes (/)
        // replace ../../ path generated when moving images under Mozilla

        $_POST["text"] = str_replace("src=\"../../","src=\"".$engine_url, $_POST["text"]);
        $_POST["text"] = str_replace("href=\"../../","href=\"".$engine_url, $_POST["text"]);
        $_POST["text"] = str_replace("src=\"".SITE_URL."/","src=\"".$engine_url, $_POST["text"]);
        $_POST["text"] = str_replace("href=\"".SITE_URL."/","href=\"".$engine_url, $_POST["text"]);

        //$_POST["text"] = preg_replace("/src=\"\.\.\/\.\.\//","$engine_url", $_POST["text"]);
        //$_POST["text"] = preg_replace("/href=\"\.\.\/\.\.\//","$engine_url", $_POST["text"]);
        //$_POST["text"]=ereg_replace("'","''",$_POST["text"]);//fix SQL
    }
    else {
        $_POST["text"] = strip_tags($_POST["text"]);
    }
}

if (!$_POST["template"]) {
    $_POST["template"] = 1;
}

if ($_POST["text"] != "") {
    $upd_fields[] = "text";
    $_POST["text"] = trim($_POST["text"]);
}

if (isset($id)) {
    $id = (int) $id;
}

$adm = new Admin($table);
$sq = new sql;

// #############

$adm->assign("moddate", date("Y-m-d H:i:s"));
$adm->assign("moduser", $user);
$adm->assignHidden("active_language", $language);
$adm->assignHidden("node_type", $node_type);

// ###################################################

if ($do) {

    //
    // db write actions
    //
    switch ($do) {
        case "decline":
        case "cancel":
        case "approve":
            //
            // cancel changes
            //

            if ('content' != $node_type) {
                break;
            }

            // js to reload tree
            $js = "if(window.parent.left && typeof window.parent.left.tree=='object')"
                . "window.parent.left.tree.reload();";

            if ('decline' == $do) {
                // only decline removal should go here, however it's controlled
                // only by user interface
                $r = $c_workflow->decline($id);

            } else if ('cancel' == $do) {
                $r = $c_workflow->cancel($id);

            } else if ('approve' == $do) {
                $r = $c_workflow->approve($id);

            } else {
                break;
            }

            if (is_int($r)) {
                // got new trash node id (cancelled page creation)
                $url = "content_admin.php?show=modify&id=$r&node_type=trash";
            } else if ($r) {
                $url = "content_admin.php?show=modify&id=$id&reloadtree=true";
            } else {
                $url = "dashboard.php";
            }

            $js .= "window.parent.left.loadToRight('" . htmlspecialchars($url) . "');";

            $tpl->addDataItem("BODY_ONLOAD", $js);
            echo $tpl->parse();
            exit();
        case "add":
            //
            // add new page
            //
            $active_tab = 1;
            $tpl->addDataItem("TITLE", $trf->tr("add"));
            processPermissions($adm);
            $adm->assign("module", makeParameters());
            $adm->getValues();
            if (in_array('is_published', $upd_fields)){
                if (isset($adm->values['publishing_date']) && strtotime($adm->values['publishing_date']) > time()) {
                    $adm->fields['is_published']['value'] = 0;
                } else {
                    $adm->fields['is_published']['value'] = 1;
                }
            }

            if (empty($adm->fields['visible']['value'])){
                $adm->fields['visible']['value'] = 0;
            }

            //
            // handle form submit initiated by changing page template
            //

            if ($GLOBALS["editor"] && $submit_to) {
                $modls = checkTemplate($template);

                if ($modls != false) {
                    foreach ($modls as $info) {
                        $fields["module_".$info[0]] = $info[1];
                        $adm->assignProp("module_".$info[0], "type", $info[2]);
                        $adm->assignProp("module_".$info[0], "list", $info[3]);
                    }

                    $fields = reSortFields($fields);
                    $adm = parseParameters($adm);
                }

                $adm->types();
                $adm->assignHidden("above", $above);
                $adm->assignHidden("parent_id", $parent_id);

                $json_text = JsonEncoder::encode($adm->values['text']);
                $tpl->addDataItem('FOOTER_JS', 'var submittedContent=' . $json_text . ';');
                external();

                $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", ''
                    , $field_groups, $fields_in_group);

                $show = '';
                break;
            }

            //
            // common actions for all node types
            //

            // check alias
            $uri_aliases = dispatch_aliases($adm->values['uri_alias']);
            foreach ($uri_aliases as $alias) {
                if (!is_valid_uri_alias($alias)) {
                    $adm->badfields[] = 'uri_alias';
                    break;
                }
            }

            $req = $adm->checkRequired($required);

            if ($req) {
                $new_node_id = false;

            } else {

                //
                // fill node_data array
                //

                $node_data = array();
                $node_data["language"] = $language;

                if ($is_root && $adm->values["perm_owner"] != "") {
                    $node_data["owner"] = $adm->values["perm_owner"];
                } else {
                    $node_data["owner"] = $user;
                }

                // get field names copied to target table
                $table_fields = $database->fetch_first_col("SHOW FIELDS FROM ?f"
                    , $table);

                foreach ($table_fields as $field) {
                    if ($field == $idfield) {
                        continue;
                    }
                    if (isset($adm->fields[$field])) {
                        $node_data[$field] = $adm->fields[$field]["value"];
                    }
                }

                switch ($node_type) {
                    case 'template':
                        $new_node_id = $c_workflow->createTemplate($node_data);
                        break;

                    case 'content':
                        if ($above) {
                            $point = 'above';
                            $ref_node = $above;
                        } else {
                            $point = 'under';
                            $ref_node = $parent_id;
                        }
                        $new_node_id = $c_workflow->createContent($node_data
                            , $ref_node, $point);

                        $adm->insert_id = $new_node_id;
                        if ($new_node_id) {
                            $adm->db_write = true;
                        }

                        break;

                    default:
                        $new_node_id = false;
                }
            }

            if ($new_node_id) {
                //
                // node saved successfully
                //

                $show = "modify";
                $id = $_POST["id"] = $new_node_id;

                // since new page was just created a content tree should be
                // reloaded to render newly created page
                $js = "if(window.parent.left && typeof window.parent.left.tree=='object')"
                    . "window.parent.left.tree.reload();";
                $tpl->addDataItem("BODY_ONLOAD", $js);

            } else {
                //
                // failed saving node
                //

                $tpl->addDataItem("NOTICE", "onLoad=\"notice('$general[required_error]')\"");
                $fields_error = true;
                $adm->getValues();
                $adm->types();
                external();
                $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add"
                    , $id, $field_groups, $fields_in_group);
            }

            break;

        case "update":
            //
            // update page logic
            //
            if (!$id) {
                break;
            }

            // turn form into "modify" mode
            $active_tab = 2;
            $tpl->addDataItem("TITLE", $trf->tr("modify"));
            processPermissions($adm);
            $adm->assign("module", makeParameters());
            $adm->getValues();

            if (!isset($adm->values['expiration_date'])) {
                unset($upd_fields['expiration_date']);
            }
            if (in_array('is_published', $upd_fields)){
                if (isset($adm->values['publishing_date']) && strtotime($adm->values['publishing_date']) > time()) {
                    $adm->fields['is_published']['value'] = 0;
                } else {
                    $adm->fields['is_published']['value'] = 1;
                }
            }
            if (empty($adm->fields['visible']['value'])){
                $adm->fields['visible']['value'] = 0;
            }

            //
            // handle form submit initiated by changing page template
            //

            if ($GLOBALS["editor"] && $submit_to) {
                $modls = checkTemplate($template);

                if ($modls) {
                    foreach ($modls as $info) {
                        $fields["module_".$info[0]] = $info[1];
                        $adm->assignProp("module_".$info[0], "type", $info[2]);
                        $adm->assignProp("module_".$info[0], "list", $info[3]);
                    }

                    $fields = reSortFields($fields);
                    $adm = parseParameters($adm);
                }

                $adm->types();

                if ($adm->values['editor_reload']) {
                    $json_text = JsonEncoder::encode($adm->values['text']);
                    $tpl->addDataItem('FOOTER_JS', 'var submittedContent=' . $json_text . ';');
                }

                external();

                // rewrite perm_owner that was set in external()
                $adm->fields["perm_owner"]["value"] = $adm->values['perm_owner'];

                $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update"
                    , $id, $field_groups, $fields_in_group);

                // skip processing $show variable later
                $show = '';
                break;
            }

            //
            // common actions for all node types
            //

            // check alias
            if (in_array('uri_alias', $upd_fields)){
                $uri_aliases = dispatch_aliases($adm->values['uri_alias']);
                foreach ($uri_aliases as $alias) {
                    if (!is_valid_uri_alias($alias)) {
                        $adm->badfields[] = 'uri_alias';
                        break;
                    }
                }
            }

            if ($is_root && $adm->values["perm_owner"] != "") {
                $adm->assign("owner", $adm->values["perm_owner"]);
            } else {
                $adm->assign("owner", $user);
            }

            $req = $adm->checkRequired($required);

            if ($req) {
                $updated = false;

            } else {
                //
                // fill node_data array
                //

                $node_data = array();
                $node_data["language"] = $language;

                foreach ($upd_fields as $field) {
                    if ($field == $idfield) {
                        continue;
                    }

                    if (isset($adm->fields[$field])) {
                        $node_data[$field] = $adm->fields[$field]["value"];
                    } else {
                        $node_data[$field] = '';
                    }
                }

                if ('content' == $node_type) {
                    $pending = $database->fetch_first_value('SELECT `pending` FROM ?f'
                        . ' WHERE ?f = ?', $table, $idfield, $id);

                    // load text data from versioning table if node_data does not
                    // contain text element (content tab was not opened by user)
                    // and page is pending changes
                    if (!isset($node_data['text']) && MODERA_PENDING_CHANGES == $pending) {
                        $version = $versioning->getCurrentRawVersion($node_type
                            , $id, array('text'));
                        $node_data['text'] = $version['object_data']['text'];
                    }
                    $updated = $c_workflow->update($node_type, $id, $node_data);

                    if ($perm->canPublish()) {
                        if (MODERA_PENDING_CHANGES == $pending) {
                            $adm->setNotice($trf->tr('changes_approved'));

                        } else if (MODERA_PENDING_CREATION == $pending) {
                            $adm->setNotice($trf->tr('page_published'));
                        }
                    }
                } else {
                    $updated = $c_workflow->update($node_type, $id, $node_data);
                }
            }

            if ($updated) {
                //
                // node saved successfully
                //
                $adm->db_write = true;
                $show = "modify";

                // reload tree at left frame
                $js = "if(window.parent.left && typeof window.parent.left.tree=='object')"
                    . "window.parent.left.tree.reload();";
                $tpl->addDataItem("BODY_ONLOAD", $js);

            } else {
                //
                // failed updating node
                //

                $tpl->addDataItem("NOTICE", "onLoad=\"notice('$general[required_error]')\"");
                $fields_error = true;
                $adm->getValues();
                $adm->types();
                external();
                $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update"
                    , $id, $field_groups, $fields_in_group);
            }

            break;
    }
}

//
// form show actions
//

switch ($show) {
    case "add":
        //
        // show add new page form
        //

        // permissions
        $perm->Access(null, $parent_id, "a", null);

        $tpl->addDataItem("TITLE", $trf->tr("add"));
        $active_tab = 1;

        if ($copyto != "") {
            $adm->fillValues($table, $idfield, $copyto);
        }

        $modls = checkTemplate($template);
        if ($modls != false) {
            for ($c = 0; $c < sizeof($modls); $c++) {
                $info = $modls[$c];
                $fields["module_".$info[0]] = $info[1];
                $adm->assignProp("module_".$info[0], "type", $info[2]);
                $adm->assignProp("module_".$info[0], "list", $info[3]);
            }
            $fields = reSortFields($fields);
            $adm = parseParameters($adm);
        }

        $adm->types();
        $adm->assignHidden("above", $above);
        $adm->assignHidden("parent_id", $parent_id);
        external();

        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "add", ''
            , $field_groups, $fields_in_group);
        break;

    case "modify":
        //
        // show modify page form
        //

        if (!$id) {
            break;
        }

        // check if node with specified id exists
        $r = $database->fetch_first_value('SELECT ?f FROM ?f WHERE ?f = ?',
            $idfield, $table, $idfield, $id);
        if (!$r) {
            // node does not exists, reload left frame and redirect to dashboard
            $js = "if(window.parent.left && typeof window.parent.left.tree=='object')"
                . "{window.parent.left.tree.reload();"
                . "window.parent.left.loadToRight('dashboard.php');}";
            $tpl->addDataItem('BODY_ONLOAD', $js);
            break;
        }

        // permissions
        $perm->Access(null, $id, "m", null);

        $tpl->addDataItem("TITLE", $trf->tr("modify"));
        $active_tab = 2;

        $decline_message = '';
        $decline_user = '';

        // if page has some pending changes fill form with values from
        // versioning table
        if ('content' == $node_type) {
            $pending = $database->fetch_first_value('SELECT `pending` FROM ?f'
                . ' WHERE ?f = ?', $table, $idfield, $id);
            if (MODERA_PENDING_CHANGES == $pending || MODERA_PENDING_CREATION == $pending) {
                // load data from versioning table
                $vdata = $versioning->getCurrentData($node_type, $id);
                $vdata['pending'] = $pending;

                foreach ($vdata as $k => $v) {
                    $adm->fields[$k]['value'] = $v;
                }
            } else {
                $adm->fillValues($table, $idfield, $id);
            }
        } else {
            $adm->fillValues($table, $idfield, $id);
        }

        $modls = checkTemplate($template);
        if ($modls != false) {
            for ($c = 0; $c < sizeof($modls); $c++) {
                $info = $modls[$c];
                $fields["module_".$info[0]] = $info[1];
                $adm->assignProp("module_".$info[0], "type", $info[2]);
                $adm->assignProp("module_".$info[0], "list", $info[3]);
            }
        }

        $fields = reSortFields($fields);
        $adm = parseParameters($adm);
        $adm->types();
        external();

        if ('content' == $node_type) {
            if (MODERA_PENDING_REMOVAL == $adm->fields['pending']['value']) {
                $tpl->addDataItem('BODY_ONLOAD', 'disableForm();');
            }
        }

        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id
            , $field_groups, $fields_in_group);

        break;

    default:
        // if $result is not empty than
        if (!$result) {
            header('Location: dashboard.php');
            exit();
        }
}

//
// process form tabs
//
$tabs_count = count($tabs);
$field_groups_count = count($field_groups);

foreach ($tabs as $key => $val) {
    $tpl->addDataItem("TABS.ID", $key);
    if ($key == 4) {
        if ($is_root) {
            $js = "javascript:enableFieldset($key, 'fieldset$key', 'fieldset". ($key+1)
                . "', $tabs_count, $field_groups_count);enableSingleFieldset('fieldset6');";
        } else {
            $js = "javascript:enableFieldset($key, 'fieldset$key', 'fieldset". ($key+1)
                . "', $tabs_count, $field_groups_count);";
        }
    } else {
        $js = "javascript:enableFieldset($key, 'fieldset$key', '', $tabs_count, $field_groups_count);";
    }

    $tpl->addDataItem("TABS.NAME", $val);
    $tpl->addDataItem("TABS.URL", $js);
    if ($key == 1) {
        $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
    }
}

$result .= "<script>if('function' == typeof fieldsetInit)fieldsetInit($field_groups_count);</script>\n";
$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();