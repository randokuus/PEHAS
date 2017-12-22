<?php

require_once('admin_header.php');
require_once(SITE_PATH . '/class/Database.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/Strings.php');
require_once(SITE_PATH . '/class/aliases_helpers.php');

/**
 * Process sitemap node
 *
 * @param array $node
 * @param string $language language code
 * @param string $content
 * @param int $level
 * @global Translator $translator Translator instance
 * @global array|NULL $invalid_fields array of field names that have invalid data
 * @global string $GLOBALS['site_settings']['lang'] default site language
 */
function process_node(&$node, $language, &$content, $level = 0)
{
    global $translator, $invalid_fields;

    foreach ($node as $k => $data){
        $pad = str_repeat('&nbsp;&nbsp;&nbsp;', $data['level']);
        $title = htmlspecialchars(Strings::shorten($data['title'], 25));
        $plain_title = htmlspecialchars(str_replace('/', '', $data['title']));

        if (strtolower($GLOBALS['site_settings']['lang']) != $language && $data['level']==1) {
            $plain_title = "$language/ $plain_title";
        }

        $id         = "content:$data[content]";
        $value      = htmlspecialchars($data['uri_alias']);
        $txt_link   = $translator->tr('generate_uri', null, null, 'admin_content');
        $label_style= (isset($invalid_fields) && in_array($id, $invalid_fields))
            ? 'color: red;' : '';
        $content .= <<<EOB
            <tr>
            <td nowrap="nowrap" style="padding-right: 20px;$label_style">
                $pad<img src="pic/icosmall_other.gif" width="16" height="16" alt="" />
                <label for="$id">$title</label>
            </td>
            <td width="100%">
                <input type="text" id="$id" name="$id" size="50" maxlength="255" value="$value"/>
                <input type="hidden" name="title-$id" value="$plain_title" />
                <input type="hidden" id="mpath-$id" name="mpath-$id" value="$data[fullpath]" />
                <label><a href="javascript:generateAlias('title-$id', '$id', 'mpath-$id');">$txt_link</a></label></td>
            </tr>
EOB;
    }
}

// translator
$translator =& ModeraTranslator::instance($language2, 'admin_aliases');

$db =& $GLOBALS['database'];

// template
$tpl = new template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tmpl/admin_aliases.html');
$tpl->addDataItem('TITLE', $translator->tr('module_title'));
$tpl->addDataItem('PHP_SELF', basename(__FILE__));
$tpl->addDataItem('SENDBUTTONTXT', $translator->tr('button', null, null, 'admin_general'));
$tpl->addDataItem('GENERATEBUTTONTXT', $translator->tr('generate_all_aliases'));
$tpl->addDataItem('CLEARBUTTONTXT', $translator->tr('clear_all_aliases'));
$tpl->addDataItem('REWRITEMAP_NOTICE', $translator->tr('rewritemap_notice'));
$tpl->addDataItem('LABEL_ENABLEALIASES', $translator->tr('aliases_enabled'));

if ('POST' == $_SERVER['REQUEST_METHOD']) {
    $invalid_fields = array();
    $submitted_data = array();

    // validate posted data
    $m = array();
    foreach ($_POST as $k => $v) {
        if (preg_match('/^(sid|content):(\d+)$/', $k, $m)) {
            $submitted_data[$k] = array('user_value' => $v, 'key_name' => $m[1], 'key_value' => $m[2]);

            // validate alias
            $uri_aliases = dispatch_aliases($v);
            foreach ($uri_aliases as $alias) {
                if (!is_valid_uri_alias($alias)) {
                    array_push($invalid_fields, $k);
                    continue;
                }
            }
        }
    }

    if (@$_POST['aliases_enabled']) {
        $tpl->addDataItem('ALIASES_CHECKED', 'checked');
    }

    if ($invalid_fields) {
        // invalid input detected
        $tpl->addDataItem('INFO.TITLE', $translator->tr('error', null, null, 'admin_general'));
        $tpl->addDataItem('INFO.TYPE', 'error');
        $tpl->addDataItem('INFO.INFO', $translator->tr('required_error', null, null, 'admin_general'));

    } else {
        $changed = 0;
        // update data in the database
        foreach ($submitted_data as $k => $v) {
            extract($v);
            $table_name = 'sid' == $key_name ? 'structure' : 'content';

            // process alias
            $db->query('UPDATE ?f SET `uri_alias` = ? WHERE ?f = ?', $table_name, $user_value
                , $key_name, $key_value);
            $changed += $db->affected_rows();
        }

        // save status of aliases (enabled/disabled)
        if (!isset($_POST['aliases_enabled'])) $_POST['aliases_enabled'] = 0;
        $db->query('UPDATE `settings` SET `niceurls` = ?', $_POST['aliases_enabled']);

        // recreate map file and clear cache
        refresh_rewrite_map($db);

        if ($changed || $GLOBALS['site_settings']['niceurls'] != $_POST['aliases_enabled']) {
            $opendir = SITE_PATH . "/cache";
            $files = array();
            if ($dir = @opendir($opendir)) {
              while (($file = readdir($dir)) !== false) {
                  if (!is_dir($opendir . $file) && $file != "." && $file != ".." && $file != ".htaccess" && $file != "error.log" && (preg_match("/xslp_sitemenu/", $file) || preg_match("/xslp_menuxml/", $file))) {
                      $files[] = $file;
                  }
              }
              sort($files);
              reset($files);
            }
            for ($c = 0; $c < sizeof($files); $c++) {
                @unlink(SITE_PATH . "/cache/" . $files[$c]);
            }
        }

        $tpl->addDataItem('INFO.TITLE', $translator->tr('modify_text', null, null, 'admin_general'));
        $tpl->addDataItem('INFO.TYPE', 'confirm');
        $tpl->addDataItem('INFO.INFO', $translator->tr('modify_text', null, null, 'admin_general'));
    }

} else if ($GLOBALS['site_settings']['niceurls']) {
    $tpl->addDataItem('ALIASES_CHECKED', 'checked');
}

// loop through all languages
$lang_res =& $db->query('SELECT `language`, `title` FROM `languages`');
while (list($lang_code, $lang_title) = $lang_res->fetch_row()) {
    $tpl->addDataItem('FIELDSET.TITLE', $lang_title);

    // get pages
    $sql = "
        SELECT
              concat(`mpath`, IF(LENGTH(`mpath`)>0,CONCAT('.',`content`),`content`)) as `fullpath`
            , LENGTH(`mpath`) AS `level`
            , `mpath`
            , `content`
            , `title`
            , `uri_alias`
        FROM `content`
        WHERE `language` = ?
        ORDER BY `fullpath`, `zort`, `first` DESC;";
    $res =& $db->query($sql, strtoupper($lang_code));
    $pages = $res->fetch_all();

    // build sitemap array
    $sitemap = array();
    // insert pages into sitemap
    foreach ($pages as $page) {
        if (isset($submitted_data["content:$page[content]"])) {
            $page['uri_alias'] = $submitted_data["content:$page[content]"]['user_value'];
        }

        $node = array(
            'level'     => (count(explode('.',$page['fullpath']))),
            'mpath'     => $page['mpath'],
            'fullpath'  => $page['fullpath'],
            'content'   => $page['content'],
            'title'     => $page['title'],
            'uri_alias' => $page['uri_alias'],
        );
        $sitemap[] = $node;
    }
    //
    // render sitemap
    //

    $aliases_tbl = '<table width="100%" cellpadding="0" cellspacing="2" border="0">';
    process_node($sitemap, $lang_code, $aliases_tbl);
    $aliases_tbl.= '</table>';

    $tpl->addDataItem('FIELDSET.ALIASES_TBL', $aliases_tbl);
}


echo $tpl->parse();
