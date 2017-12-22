<?php
require_once('admin_header.php');
require_once(SITE_PATH . '/class/admin2.class.php');

require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/DbHelpers.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/ModeraTranslator.php');

/**
 * Escapes data before using it in xml
 *
 * @param string $data data to escape
 * @return string
 */
function escape_xml_data($data)
{
    return base64_encode($data);
}

/**
 * Unescape data escaped by {@link escape_xml_data()}
 *
 * @param string $data data to unescape
 * @return string
 */
function unescape_xml_data($data)
{
    return base64_decode($data);
}

/**
 * Parses language data from xml
 *
 * @param int $depth
 * @param stirng $element node name
 * @param array $data
 * @param array $context
 */
function parse_language($depth, $element, $data, &$context)
{
    global $translator;

    $tmp = array();
    foreach ($data['VALUE'] as $node_name => $node_data) {
        $tmp[strtolower($node_name)] = base64_decode($node_data['VALUE']);
    }
    $data = $tmp;

    // check if this language already exists in database
    // if so than compare nplurals and expression parameters
    $db_row = $context['db']->fetch_first_row('SELECT `nplurals` FROM `languages` WHERE `language` = ?'
        , $data['language']);

    if ($db_row) {
        if ($db_row['nplurals'] != $data['nplurals']) {
            array_push($context['errors'], $translator->tr('language_params_differ', $data['language']));
            return;
        }
    } else {
        // insert language into database
        $context['db']->query("INSERT INTO `languages`(`language`, `nplurals`, `expr`, `title`, `description`)
            VALUES(?@)", array((string)$data['language'], (string)$data['nplurals'], (string)$data['expr']
            , (string)$data['title'], (string)$data['description']));
        if (!$context['db']->affected_rows()) return;

        $context['affected_rows'] += $context['db']->affected_rows();
    }

    array_push($context['parsed_langs'], $data['language']);
}

/**
 * Parses translation data
 *
 * @param int $depth
 * @param string $element
 * @param array $data
 * @param array $context
 */
function parse_translation($depth, $element, $data, &$context)
{
    static $tokens_cache = array(); // tokens cache

    $tmp = array();
    foreach ($data['VALUE'] as $node_name => $node_data) {
        $tmp[strtolower($node_name)] = base64_decode($node_data['VALUE']);
    }
    $data = $tmp;

    if (!in_array($data['language'], $context['parsed_langs'])) return false;

    // check is token exists and check it's is_plural value
    if (isset($tokens_cache[$language][$domain][$token])) {
        $db_row = $tokens_cache[$language][$domain][$token];

    } else {

        // check if token exists, and check is_plural value
        $db_row = $context['db']->fetch_first_row('SELECT * FROM `tokens` WHERE `domain` = ?
            AND `token` = ?', $data['domain'], $data['token']);
    }

    if (!$db_row) {
        // no such token exists, insert new record into tokens table
        $context['db']->query('INSERT INTO `tokens`(`domain`, `token`, `is_plural`) VALUES(?@)'
            , array($data['domain'], $data['token'], $data['is_plural']));
        $context['affected_rows'] += $context['db']->affected_rows();

    } else  if ($db_row['is_plural'] && !$data['is_plural']) {
        // convert plurals to singular translations for current token

        $context['db']->query('UPDATE `tokens` SET `is_plural` = 0 WHERE `domain` = ? AND `token` = ?'
            , $data['domain'], $data['token']);
        $context['affected_rows'] += $context['db']->affected_rows();

        // delete extra plurals form
        $context['db']->query('DELETE FROM `translations` WHERE `domain` = ? AND `token` = ?
            AND `plural` \!= 0', $data['domain'], $data['token']);
        $context['affected_rows'] += $context['db']->affected_rows();

        // update plural number
        $context['db']->query('UPDATE `translations` SET `plural` = -1 WHERE `domain` = ?
            AND `token` = ? AND `plural` = 0', $data['domain'], $data['token']);
        $context['affected_rows'] += $context['db']->affected_rows();

    } else if (!$db_row['is_plural'] && $data['is_plural']) {
        // convert singural translations to plural for current token

        $context['db']->query('UPDATE `tokens` SET `is_plural` = 1 WHERE `domain` = ? AND `token` = ?'
            , $data['domain'], $data['token']);
        $context['affected_rows'] += $context['db']->affected_rows();

        // update plural number
        $context['db']->query('UPDATE `translations` SET `plural` = 0 WHERE `domain` = ?
            AND `token` = ? AND `plural` = 0', $data['domain'], $data['token']);
        $context['affected_rows'] += $context['db']->affected_rows();
    }

    $translation_exists = $context['db']->fetch_first_value('SELECT COUNT(*) FROM
        `translations` WHERE `token` = ? AND `domain` = ? AND `language` = ?
        AND `plural` = ?', $data['token'], $data['domain'], $data['language']
        , $data['plural']);

    if ($translation_exists) {
        // update translation
        $context['db']->query('UPDATE `translations` SET ?% WHERE `domain` = ? AND `token` = ?
            AND `language` = ? AND `plural` = ?', array('translation' => $data['translation'])
            , $data['domain'], $data['token'], $data['language'], $data['plural']);
        $context['affected_rows']++;

    } else {
        // insert new translation
        $context['db']->query('INSERT INTO `translations`(`domain`, `token`, `translation`, `plural`, `language`)
            VALUES(?@)', array($data['domain'], $data['token'], $data['translation'], $data['plural']
            , $data['language']));
        $context['affected_rows'] += $context['db']->affected_rows();
    }

    $tokens_cache[$data['language']][$data['domain']][$data['token']]['is_plural'] = $data['is_plural'];
}

$db =& $GLOBALS['database'];

// translator
$translator =& ModeraTranslator::instance($language2, 'admin_langfiles');

$fields = array(
    'languages' => $translator->tr('languages'),
    'domains' => $translator->tr('domains'),
    'all_domains' => $translator->tr('all_domains'),
    'datafile' => $translator->tr('file'),
);

$field_groups = array(
	1 => array($translator->tr("export_form_titile"), ""),
	2 => array($translator->tr("import_form_title"), ""),
);

$fields_in_group = array(
	"languages" => 1,
	"domains" => 1,
	"all_domains" => 1,
	"datafile" => 2,
);

// permissions
$perm = new Rights($group, $user, "root", true);
//$perm->Access (0, 0, "m", "");

// general parameters (templates, messages etc.)
$general['enctype'] = "enctype=\"multipart/form-data\"";
$general["button"] = $translator->tr("perform_action_btn");

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);
$tpl->addDataItem("TITLE", $translator->tr("import_export_languages"));

$adm = new Admin2('none');
$adm->assign("language", $language);

if (isset($_POST['do'])) {
    if (isset($_FILES['datafile'])
        && move_uploaded_file($_FILES['datafile']['tmp_name'], $uploadfile =
            SITE_PATH . '/cache/import_' . $_FILES['datafile']['name']))
    {
        // performing import
        error_reporting(E_ALL);
        require_once(SITE_PATH . '/class/SaxParserCb.php');
        $parser = new SaxParserCb('UTF-8');

        // TODO: this is quick hack, should be fixed in SaxParserCb class
        $parsed_langs = array();
        $errors = array();
        $affected_rows = 0;

        $context = array('db' => &$db, 'parsed_langs' => &$parsed_langs, 'errors' => &$errors
            , 'affected_rows' => &$affected_rows);
        $parser->set_callback(1, 'LANGUAGE', 'parse_language', $context);
        $parser->set_callback(1, 'TRANSLATION', 'parse_translation', $context);

        $parser->parse_file($uploadfile);
        unlink($uploadfile);

        if ($context['errors']) {
            // some errors happened
            $adm->general['other_error'] = implode('<br />', $content['errors']);

        } else if (!$context['affected_rows']) {
            $adm->general['other_error'] = $translator->tr('no_data_changed_in_db');

        } else {
            // no errors happened
            $adm->db_write = true;
        }

    } else {

        // validate form data

        if (!count($adm->values['languages'])) {
            $adm->badfields[] = 'languages';
        }

        if (!count($adm->values['domains']) && !$adm->values['all_domains']) {
            $adm->badfields[] = 'domains';
        }

        if (!count($adm->badfields)) {
            // performing export
            header("Pragma: no-cache");
            header("Cache-control: no-cache");
            header('Content-Type: text/xml');
            header("Content-disposition: attachment; filename=translations.xml");

            echo '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
            echo "<translations>\n";
            echo "\t<exportinfo>\n";
            echo "\t\t<date>" . date('r') . "</date>\n";
            echo "\t\t<data-format>base64</data-format>\n";
            echo "\t</exportinfo>\n";

            // process languages
            $res =& $db->query('SELECT * FROM `languages` WHERE `language` IN(?@)'
                , $adm->values['languages']);
            while ($row = $res->fetch_assoc()) {
                echo "\t<language>\n";
                foreach ($row as $field_name => $field_value) {
                    if (strlen($field_value)) {
                        echo "\t\t<$field_name>" . escape_xml_data($field_value)
                            . "</$field_name>\n";
                    }
                }
                echo "\t</language>\n";
            }

            $tr_sql = 'SELECT `t`.`domain`, `t`.`token`, `t`.`translation`
                , `t`.`plural`, `tk`.`is_plural`, `t`.`language`
                FROM `translations` `t` INNER JOIN `tokens` `tk` USING(`domain`, `token`)
                WHERE `t`.`language` IN(?@)';

            if ($adm->values['all_domains']) {
                $tr_sql .= ' ORDER BY `t`.`domain`, `t`.`token`';
                $res =& $db->query($tr_sql, $adm->values['languages']);
            } else {
                // process translations
                $tr_sql .= ' AND `t`.`domain` IN(?@) ORDER BY `t`.`domain`, `t`.`token`';
                $res =& $db->query($tr_sql, $adm->values['languages']
                    , $adm->values['domains']);
            }

            while ($row = $res->fetch_assoc()) {
                echo "\t<translation>\n";
                foreach ($row as $field_name => $field_value) {
                    echo "\t\t<$field_name>" . escape_xml_data($field_value)
                        . "</$field_name>\n";
                }
                echo "\t</translation>\n";
            }

            echo "</translations>";
            exit();
        }
    }
}

//
// languages list
//
$languages = array();
$res =& $db->query('SELECT `language`, `title` FROM `languages`');
while ($row = $res->fetch_assoc()) {
    $languages[$row['language']] = $row['title'];
}
$adm->assignProp("languages", "type", "select2");
$adm->assignProp("languages", "size", 5);
$adm->assignProp("languages", "list", $languages);
$adm->assignProp("languages", "value", $adm->values['languages']);

//
// domains list
//
$domains = array();
$res =& $db->query('SELECT DISTINCT `domain` FROM `tokens` ORDER BY `domain`');
while (list($domain) = $res->fetch_row()) {
    $domains[$domain] = $domain;
}
$adm->assignProp("domains", "type", "select2");
$adm->assignProp("domains", "size", "10");
$adm->assignProp("domains", "list", $domains);
$adm->assignProp("domains", "value", $adm->values['all_domains'] ? array_keys($domains) : $adm->values['domains']);

$adm->assignProp("all_domains", "type", "checkbox");
$adm->assignProp("all_domains", "value", $adm->values['all_domains']);

$adm->assignProp("datafile", "type", "file");

$result = $adm->form($fields, 0, 0, 0, "update", 0, $field_groups, $fields_in_group);

$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();