<?php

//
// Initialization
//

require_once('admin_header.php');
require_once(SITE_PATH . '/class/Database.php');
require_once(SITE_PATH . '/class/ModeraTranslator.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/text.class.php');
require_once(SITE_PATH . '/class/JsonEncoder.php');

/**
 * Get array of sample numbers for specified plural form
 *
 * @param string $expr gettext plural form expression
 * @param int $formnum plural form number
 * @param int $top_limit top limit for searching numbers set
 * @param int $max maximum numbers returned
 * @return array
 */
function get_sample_plural_numbers($expr, $formnum, $top_limit, $max)
{
    $code = TranslatorHelpers::gtpexpr_to_php($expr);
    if (false === $code) return array();

    // create list of numbers for plural form description
    $plurals = array();
    for ($n = 0; $n < $top_limit; $n++) {
        $plural = null;
        eval($code);

        if (!is_null($plural) && $formnum == $plural) {
            $plural = (int)$plural;
            $plurals[] = $n;
        }
    }

    while (count($plurals) > $max) array_pop($plurals);
    return $plurals;
}

/**
 * Get html code for domains dropdown
 *
 * @param Database $database
 * @param string $selected_domain
 * @return string html code for SELECT options
 */
function get_domains_options_html(&$database, $selected_domain = null)
{
    $res =& $database->query('SELECT distinct `domain` FROM `tokens` ORDER BY `domain`');
    $domains = array();
    while ($row = $res->fetch_row()) $domains[] = current($row);

    // create options for domain select
    $options = '';
    foreach ($domains as $domain) {
        $options .= sprintf("<option value=\"%2\$s\"%s>%s</option>\n"
            , $selected_domain == $domain ? ' selected' : ''
            , htmlspecialchars($domain));
    }

    return $options;
}

/**
 * Delete translation from translations and tokens tables
 *
 * @param Database $database
 * @param string $domain
 * @param string $token
 */
function delete_translation(&$database, $domain, $token)
{
    $database->query('DELETE FROM `translations` WHERE `domain` = ? AND `token` = ?', $domain, $token);
    $database->query('DELETE FROM `tokens` WHERE `domain` = ? AND `token` = ?', $domain, $token);
}

// translator
$translator =& ModeraTranslator::instance($language2, 'admin_langfiles');
$translator->set_format('html');

// template
$tpl = new template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tmpl/admin_translator_main.html');
$tpl->addDataItem("TITLE", $translator->tr('module_title'));

// two tabs
$tabs = array(
    1 => array($translator->tr('tab_translations_list'), basename(__FILE__) . '?do=list'),
    2 => array($translator->tr('tab_add_translation'), basename(__FILE__) . '?do=addtr'),
);

$content = '';
// save domanin name in session
if (isset($_POST['domain-sel']) && $_POST['domain-sel'] !== '') {
    $_SESSION['admin']['translator']['domain'] = $_POST['domain-sel'];
}

switch ($_REQUEST['do']) {
    // for edit with ext-ajax
    case 'savetr':
        $result = false;
        if ($_REQUEST['domain'] && $_REQUEST['token'] && $_REQUEST['lan'] && isset($_REQUEST['value'])
              && false !== $database->fetch_first_value('SELECT `token` FROM `tokens` WHERE '
                    . 'BINARY `token`=? AND `domain`=?', $_REQUEST['token'], $_REQUEST['domain'])) {
            if ($_REQUEST['value']) {
                if (false !== $database->fetch_first_value('SELECT `translation` FROM `translations` WHERE '
                            . 'BINARY `token`=? AND `domain`=? AND `language`=?'
                            , $_REQUEST['token'], $_REQUEST['domain'], $_REQUEST['lan'])) {

                    $database->query('UPDATE `translations` SET `translation` = ?
                           WHERE `token`=? AND `domain`=? AND `language`=?'
                           , $_REQUEST['value'], $_REQUEST['token'], $_REQUEST['domain'], $_REQUEST['lan']);
                } else {
                    $fields = array('domain', 'token', 'translation', 'language');
                    $data = array($_REQUEST['domain'], $_REQUEST['token'], $_REQUEST['value'], $_REQUEST['lan']);
                    $database->query('INSERT INTO `translations` (?@f) VALUES(?@)', $fields, $data);

                }
            }else {
                // delete translation
                $database->query('DELETE FROM `translations`
                       WHERE `token`=? AND `domain`=? AND `language`=?'
                       , $_REQUEST['token'], $_REQUEST['domain'], $_REQUEST['lan']);
            }
            $result = true;
        }

        echo JsonEncoder::encode(array('result'=>array(array('edited'=>$result,
                                                             'value'=>$_REQUEST['value']))));


        exit;

    case 'sort':
        if (isset($_GET['column']) && isset($_GET['order'])) {
            $_SESSION['tr_sorting'] = array('column' => $_GET['column'], 'order' => $_GET['order']);
        }

        header('Location: ' . basename(__FILE__));
        exit;

        break;

    case 'apply_filters':
        // combine filters passed by get and post
        $filters = array();
        if (is_array($_POST['filters'])) {
            $filters = $_POST['filters'];
        }

        if (is_array($_GET['filters'])) {
            $filters = array_merge($filters, $_GET['filters']);
        }

        // save received filters into session
        if (!empty($filters)) {
            if (isset($_SESSION['tr_filters']) && is_array($_SESSION['tr_filters'])) {
                $_SESSION['tr_filters'] = array_merge($_SESSION['tr_filters'], $filters);
            } else {
                $_SESSION['tr_filters'] = $filters;
            }
        } else if(!isset($_SESSION['tr_filters'])) {
            $_SESSION['tr_filters'] = array();
        }

        //
        // filters postprocessing
        //

        // process domain
        if ('_all' == $_SESSION['tr_filters']['domain']) unset($_SESSION['tr_filters']['domain']);

        // process translation filters
        foreach ($_SESSION['tr_filters'] as $name => $value) {
            if (preg_match('/^lang_[a-z]{2}$/', $name) && empty($value)) {
                unset($_SESSION['tr_filters'][$name]);
            }
        }

        header('Location: ' . basename(__FILE__));
        exit();

        break;

    //
    // remove language column
    //
    case 'remove_lang_col':
        if (!isset($_REQUEST['lang'])) break;
        if (false !== $key = array_search($_REQUEST['lang'], $_SESSION['tr_languages'])) {
            unset($_SESSION['tr_languages'][$key]);
        }
        header('Location: ' . basename(__FILE__));
        exit();
        break;

    case 'deltr':
        delete_translation($database, $_GET['domain'], $_GET['token']);
        header('Location: ' . basename(__FILE__));
        break;

    case 'edittr':
        // add translation form
        require_once(SITE_PATH . '/class/TranslatorHelpers.php');
        // get parameters token and domain always passed
        if (!isset($_GET['token']) || !isset($_GET['domain'])) {
            header('Location: ' . basename(__FILE__));
            exit();
        }

        // change first tab text
        $active_tab = 1;
        $tabs[1] = array($translator->tr('tab edit translation'), '#');

        // template initialization
        $form_tpl = new template();
        $form_tpl->setCacheLevel(TPL_CAHCE_NOTHING);
        $form_tpl->setTemplateFile('tmpl/admin_translator_translations_add.html');
        $form_tpl->addDataItem('SUMBIT_TXT', $translator->tr('btn_edit_translation'));
        $form_tpl->addDataItem('ADDITIONAL_BUTTONS', '&nbsp;<button onClick="document.location.href=\''
            . basename(__FILE__) . '\'; return false;"><img src="pic/button_decline.gif" alt="" border="0">'
            . $translator->tr('btn_cancel') . '</button>');

        $form_tpl->addDataItem('FORM_ACTION', htmlspecialchars('./' . basename(__FILE__)
            . "?do=edittr&token=$_GET[token]&domain=$_GET[domain]"));
        $form_tpl->addDataItem('LEGEND', $translator->tr('edit translation'));

        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            // edit form submitted

            //
            // process input data
            //

            // trimming domain and token, do not trim translations data
            $_POST['domain'] = trim($_POST['domain']);
            $_POST['token'] = trim($_POST['token']);

            // disabled element values are not passed (at least by firefox)
            // if domain text input was disabled we have to take domain value
            // from select box (domain-sel)
            if (empty($_POST['domain']) && '_new-domain' != $_POST['domain-sel']) {
                $_POST['domain'] = $_POST['domain-sel'];
            }

            //
            // validate input data
            //

            $error = false;
            $infotext = '';

            // check token
            if (empty($_POST['token'])) {
                $infotext .= $translator->tr('please enter token') . '<br />';
                $error = true;

            } else if (($_GET['domain'] != $_POST['domain'] || $_GET['token'] != $_POST['token'])
               && false !== $database->fetch_first_value('SELECT `token` FROM `tokens` WHERE'
                . ' BINARY `token`=? AND `domain`=?', $_POST['token'], $_POST['domain']))
            {
                // token or domain was changed and they already exists
                $infotext .= $translator->tr('token already exists') . '<br />';
                $error = true;
            }

            // check domain
            if (!preg_match('/^[a-z_A-Z0-9]+$/', $_POST['domain'])) {
                $infotext .= $translator->tr('please enter valid domain') . '<br />';
                $error = true;

            } else if (0 === strpos($_POST['domain'], '_')) {
                $infotext .= $translator->tr('reserved domain name') . '<br />';
                $error = true;
            }

            if (!$error) {
                // modify translation
                $old_token = $_GET['token'];
                $old_domain = $_GET['domain'];
                $token = trim($_POST['token']);
                $domain = trim($_POST['domain']);

                // first delete translations
                delete_translation($database, $old_domain, $old_token);

                // than add translations
                // loop through all posted data
                foreach ($_POST as $k => $v) {
                    // match only translations data
                    $m = null;
                    if (!preg_match('/^tr-([a-z]{2})(?:-plural-(\d+))?$/', $k, $m)) continue;
                    // plural translation matched, but plurals checkbox was not selected
                    if (!$_POST['plural'] && 3 == count($m)) continue;
                    if ('' == trim($v)) continue;

                    $language = $m[1];
                    $plural = isset($m[2]) ? $m[2] : 0; // used only if plurals checkbox was selected
                    $fields = array('domain', 'token', 'translation', 'language');
                    $data = array($domain, $token, $v, $language);

                    if ($_POST['plural']) {
                        // in plural mode add plural form data
                        $fields[] = 'plural';
                        $data[] = $plural;
                    }

                    // run actual sql query
                    $database->query('INSERT INTO `translations` (?@f) VALUES(?@)', $fields, $data);
                }

                // add record into tokens table
                $database->query('INSERT INTO `tokens` (`domain`, `token`, `is_plural`) VALUES(?@)'
                    , array($domain, $token, (int)$_POST['plural']));

                // redirect to translations
                header('Location: ' . basename(__FILE__));
                exit();
            }

            // following code is executed only in case of invalid data
            $form_tpl->addDataItem('INFO.TITLE', $translator->tr('information'));
            $form_tpl->addDataItem('INFO.TYPE', 'error');
            $form_tpl->addDataItem('INFO.INFO', $infotext);

            //
            // populate form with posted values
            //
            $form_tpl->addDataItem('TOKEN', $translator->tr('token'));
            $form_tpl->addDataItem('DOMAIN', $translator->tr('domain'));
            $form_tpl->addDataItem('NEW_DOMAIN', $translator->tr('new domain'));
            $form_tpl->addDataItem('HAS_PLURAL_FORMS', $translator->tr('has plural forms'));

            $form_tpl->addDataItem('TOKEN_VALUE', htmlspecialchars($_POST['token']));
            $form_tpl->addDataItem('DOMAIN_OPTIONS', get_domains_options_html($database, $_POST['domain']));
            $form_tpl->addDataItem('DOMAIN_VALUE', htmlspecialchars($_POST['domain']));
            $form_tpl->addDataItem('PLURAL_CHECKED', $_POST['plural'] ? 'checked="checked"' : '');

            // select all languages
            $res =& $database->query('SELECT `language`, `nplurals`, `title`, `expr`'
                . ', `description` from `languages` ORDER BY `title`');

            while ($row = $res->fetch_assoc()) {
                $title = htmlspecialchars($row['title']);

                // singular translation
                $form_tpl->addDataItem('LANGUAGE.LANGUAGE_NAME', $title);
                $form_tpl->addDataItem('LANGUAGE.LANGUAGE_CODE', 'tr-' . $row['language']);
                $form_tpl->addDataItem('LANGUAGE.TRANSLATION', htmlspecialchars($_POST["tr-$row[language]"]));

                // plural forms related form elements
                if ($row['nplurals'] > 1) {
                    $form_tpl->addDataItem('LANGUAGE.PLURAL_FORM_DESCR', 'Plural form #0: ('
                        . implode(', ', get_sample_plural_numbers($row['expr'], 0, 30, 5)) . ')');

                    for ($i = 1; $i < $row['nplurals']; $i++) {
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.LANGUAGE_NAME'
                            , $title);
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_DESCR'
                            , "Plural form #$i: (" . implode(', ', get_sample_plural_numbers($row['expr'], $i, 30, 5)) . ')');

                        $plural_id = 'tr-' . $row['language'] . "-plural-$i";
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_ID', $plural_id);
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.TRANSLATION'
                            , htmlspecialchars($_POST[$plural_id]));
                    }
                }
            }

            $content .= $form_tpl->parse();
            break; // break from case
        }

        //
        // load all translations into form
        //

        // populate array with translations
        $res =& $database->query('
            SELECT
                tk.domain, tk.token, tk.is_plural, tr.translation, tr.language, tr.plural
            FROM
                `tokens` AS `tk` LEFT JOIN `translations` AS `tr` USING(`domain`, `token`)
            WHERE
                `tk`.`token`=? and `tk`.`domain`=?'
            , $_GET['token'], $_GET['domain']);

        $translations = array();
        $plural = null;
        while ($row = $res->fetch_assoc()) {
            $translations[$row['language']][(int)$row['plural']] = $row['translation'];
            if (is_null($plural)) $plural = $row['is_plural'] == 1;
        }
        $form_tpl->addDataItem('PLURAL_CHECKED', $plural ? 'checked="checked"' : '');

        if (!empty($translations)) {
            // fill form
            $form_tpl->addDataItem('TOKEN', $translator->tr('token'));
            $form_tpl->addDataItem('DOMAIN', $translator->tr('domain'));
            $form_tpl->addDataItem('NEW_DOMAIN', $translator->tr('new domain'));
            $form_tpl->addDataItem('HAS_PLURAL_FORMS', $translator->tr('has plural forms'));

            $form_tpl->addDataItem('TOKEN_VALUE', htmlspecialchars($_GET['token']));
            $form_tpl->addDataItem('DOMAIN_OPTIONS', get_domains_options_html($database, $_GET['domain']));
            $form_tpl->addDataItem('DOMAIN_VALUE', htmlspecialchars($_GET['domain']));

            // select all languages
            $res =& $database->query('SELECT `language`, `nplurals`, `title`, `expr`'
                . ', `description` from `languages` ORDER BY `title`');
            while ($row = $res->fetch_assoc()) {
                $title = htmlspecialchars($row['title']);
                if (!isset($translations[$row['language']])) $translations[$row['language']] = array();

                // singular translation
                $form_tpl->addDataItem('LANGUAGE.LANGUAGE_NAME', $title);
                $form_tpl->addDataItem('LANGUAGE.LANGUAGE_CODE', 'tr-' . $row['language']);

                if (isset($translations[$row['language']][-1])) {
                    $translation = $translations[$row['language']][-1];
                } else if (isset($translations[$row['language']][0])) {
                    $translation = $translations[$row['language']][0];
                } else {
                    $translation = '';
                }

                $form_tpl->addDataItem('LANGUAGE.TRANSLATION', htmlspecialchars($translation));

                // plural forms related form elements
                if ($row['nplurals'] > 1) {
                    $form_tpl->addDataItem('LANGUAGE.PLURAL_FORM_DESCR', 'Plural form #0: ('
                        . implode(', ', get_sample_plural_numbers($row['expr'], 0, 30, 5)) . ')');

                    for ($i = 1; $i < $row['nplurals']; $i++) {
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.LANGUAGE_NAME', $title);
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_DESCR'
                            , "Plural form #$i: (" . implode(', ', get_sample_plural_numbers($row['expr']
                                , $i, 30, 5)) . ')');

                        $plural_id = 'tr-' . $row['language'] . "-plural-$i";
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_ID', $plural_id);
                        $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.TRANSLATION'
                            , htmlspecialchars($translations[$row['language']][$i]));
                    }
                }
            }

        } else {
            $content .= $translator->tr('no such translation exists');
            break;
        }

        $content .= $form_tpl->parse();
        break;

    case 'addtr':
        // add translation form
        require_once(SITE_PATH . '/class/TranslatorHelpers.php');
        $active_tab = 2;

        $form_tpl = new template();
        $form_tpl->setCacheLevel(TPL_CAHCE_NOTHING);
        $form_tpl->setTemplateFile('tmpl/admin_translator_translations_add.html');
        $form_tpl->addDataItem('SUMBIT_TXT', $translator->tr('add_translation'));

        switch ($_GET['act']) {
            case 'showsuccess':
                $form_tpl->addDataItem('INFO.TITLE', $translator->tr('information'));
                $form_tpl->addDataItem('INFO.TYPE', 'confirm');
                $form_tpl->addDataItem('INFO.INFO', $translator->tr('translation_added_succ'));
                break;
        }

        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            // form was submitted, trying to add new translation

            //
            // process input data
            //

            // trimming domain and token, do not trim translations data
            $_POST['domain'] = trim($_POST['domain']);
            $_POST['token'] = trim($_POST['token']);

            // disabled elements values are not passed, if domain text input was
            // disabled we have to take domain value from select box (domain-sel)
            if (empty($_POST['domain']) && '_new-domain' != $_POST['domain-sel']) {
                $_POST['domain'] = $_POST['domain-sel'];
            }

            //
            // validate input data
            //

            $error = false;
            $infotext = '';

            // check token
            if (empty($_POST['token'])) {
                $infotext .= $translator->tr('please enter token') . '<br />';
                $error = true;

            } else if (false !== $database->fetch_first_value('SELECT * FROM `tokens` WHERE'
                . ' BINARY `token`=? AND `domain`=?', $_POST['token'], $_POST['domain']))
            {
                // token already exists, set error
                $infotext .= $translator->tr('token already exists') . '<br />';
                $error = true;
            }

            // check domain
            if (!preg_match('/^[a-z_A-Z0-9]+$/', $_POST['domain'])) {
                $infotext .= $translator->tr('please enter valid domain name') . '<br />';
                $error = true;

            } else if (0 === strpos($_POST['domain'], '_')) {
                $infotext .= $translator->tr('reserved domain name') . '<br />';
                $error = true;
            }

            //
            // Add translation if data was validated successfully
            //

            if (!$error) {
                $token = $_POST['token'];
                $domain = $_POST['domain'];
                $_POST['plural'] = isset($_POST['plural']) ? 1 : 0;

                // loop through all posted data
                foreach ($_POST as $k => $v) {
                    // match only translations data
                    $m = array();
                    if (!preg_match('/^tr-([a-z]{2})(?:-plural-(\d+))?$/', $k, $m)) continue;
                    // plural translation matched, but plurals checkbox was not selected, skip
                    // plural forms in this case
                    if (!$_POST['plural'] && 3 == count($m)) continue;
                    // ignore empty translations
                    if ('' == trim($v)) continue;

                    $language = $m[1];
                    $plural = isset($m[2]) ? $m[2] : 0; // used only if plurals checkbox was selected
                    $fields = array('domain', 'token', 'translation', 'language');
                    $data = array($domain, $token, $v, $language);

                    if ($_POST['plural']) {
                        // in plural mode add plural form data
                        $fields[] = 'plural';
                        $data[] = $plural;
                    }

                    // run actual sql query
                    $database->query('INSERT INTO `translations` (?@f) VALUES(?@)'
                        , $fields, $data);
                }
                // add to session new domain
                if (isset($_POST['domain']) && $_POST['domain'] !== ''){
                    $_SESSION['admin']['translator']['domain'] = $_POST['domain'];
                }
                // add record into tokens table
                $database->query('INSERT INTO `tokens` (`domain`, `token`, `is_plural`) VALUES(?@)'
                    , array($domain, $token, $_POST['plural']));

                // redirect to self to prevent submitting form again on refreshing
                header('Location: ' . basename(__FILE__) . '?do=addtr&act=showsuccess');
                exit();
            }

            // following code is executed only in case of invalid data
            $form_tpl->addDataItem('INFO.TITLE', $translator->tr('information'));
            $form_tpl->addDataItem('INFO.TYPE', 'error');
            $form_tpl->addDataItem('INFO.INFO', $infotext);
        }

        //
        // Fill form
        //

        if (isset($_POST['domain'])) {
            $curr_domain = $_POST['domain'];
        } else if (!isset($_POST['domain']) && isset($_SESSION['admin']['translator']['domain'])) {
            $curr_domain =  $_SESSION['admin']['translator']['domain'];
        } else {
            $curr_domain = null;
        }

        $form_tpl->addDataItem('TOKEN', $translator->tr('token'));
        $form_tpl->addDataItem('DOMAIN', $translator->tr('domain'));
        $form_tpl->addDataItem('NEW_DOMAIN', $translator->tr('new domain'));
        $form_tpl->addDataItem('HAS_PLURAL_FORMS', $translator->tr('has plural forms'));

        $form_tpl->addDataItem('FORM_ACTION', './' . basename(__FILE__) . '?do=addtr');
        $form_tpl->addDataItem('LEGEND', 'Add translation');
        $form_tpl->addDataItem('PLURAL_CHECKED', $_POST['plural'] ? 'checked="checked"' : '');
        $form_tpl->addDataItem('TOKEN_VALUE', htmlspecialchars($_POST['token']));
        $form_tpl->addDataItem('DOMAIN_OPTIONS', get_domains_options_html($database, $curr_domain));
        $form_tpl->addDataItem('DOMAIN_VALUE', htmlspecialchars($curr_domain));

        // select all languages
        $res =& $database->query('SELECT `language`, `nplurals`, `title`, `expr`'
            . ', `description` from `languages` ORDER BY `title`');
        while ($row = $res->fetch_assoc()) {
            $title = htmlspecialchars($row['title']);

            // singular translation
            $form_tpl->addDataItem('LANGUAGE.LANGUAGE_NAME', $title);
            $form_tpl->addDataItem('LANGUAGE.LANGUAGE_CODE', 'tr-' . $row['language']);
            $form_tpl->addDataItem('LANGUAGE.TRANSLATION', htmlspecialchars($_POST["tr-$row[language]"]));

            // plural forms related form elements
            if ($row['nplurals'] > 1) {
                $form_tpl->addDataItem('LANGUAGE.PLURAL_FORM_DESCR', 'Plural form #0: ('
                    . implode(', ', get_sample_plural_numbers($row['expr'], 0, 30, 5)) . ')');

                for ($i = 1; $i < $row['nplurals']; $i++) {
                    $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.LANGUAGE_NAME'
                        , $title);
                    $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_DESCR'
                        , "Plural form #$i: (" . implode(', ', get_sample_plural_numbers($row['expr'], $i, 30, 5)) . ')');

                    $plural_id = 'tr-' . $row['language'] . "-plural-$i";
                    $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.PLURAL_FORM_ID', $plural_id);
                    $form_tpl->addDataItem('LANGUAGE.LANGUAGE_PLURALS.TRANSLATION'
                        , htmlspecialchars($_POST[$plural_id]));
                }
            }
        }

        $content .= $form_tpl->parse();

        break;

    case 'list':
    default:
        // list of translations
        require_once(SITE_PATH . '/class/TranslationsList.php');
        $active_tab = 1;

        //
        // process filters
        //

        if (!isset($_SESSION['tr_filters'])) $_SESSION['tr_filters'] = array();

        // default language column
        if (empty($_SESSION['tr_languages'])) $_SESSION['tr_languages'] = array(strtolower($language));

        // remove translations filters for languages that are not displaed in current layout
        foreach ($_SESSION['tr_filters'] as $name => $value) {
            $m = array();
            if (preg_match('/^lang_([a-z]{2})$/', $name, $m)) {
                if (!in_array($m[1], $_SESSION['tr_languages'])) {
                    unset($_SESSION['tr_filters'][$name]);
                }
            }
        }

        if (isset($_GET['page'])) {
            $_SESSION['page'] = (int)$_GET['page'];
        } else if (!isset($_SESSION['page'])) {
            $_SESSION['page'] = 1;
        }

        $trlist = new TranslationsList($translator, $database);
        $trlist->set_page_size(isset($_SESSION['tr_filters']['rows']) ? $_SESSION['tr_filters']['rows'] : 25);
        $trlist->set_filters($_SESSION['tr_filters']);
        $trlist->set_urlbase('./' . basename(__FILE__));
        $trlist->set_languages($_SESSION['tr_languages']);
        $trlist->set_page($_SESSION['page']);

        if (isset($_SESSION['tr_sorting'])) {
            $trlist->set_sorting($_SESSION['tr_sorting']['column'], $_SESSION['tr_sorting']['order']);
        }

        $content .= $trlist->__toString();

        break;
}

foreach ($tabs as $i => $tab_data) {
    list($title, $url) = $tab_data;
    $tpl->addDataItem("TABS.ID", $i);
    $tpl->addDataItem("TABS.URL", htmlspecialchars($url));
    $tpl->addDataItem("TABS.NAME", htmlspecialchars($title));
    $tpl->addDataItem('TABS.CLASS', $i == $active_tab ? 'class="active"' : '');
}

$tpl->addDataItem('CONTENT', $content);

echo $tpl->parse();