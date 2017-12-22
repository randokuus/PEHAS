<?php

//
// Initialization
//

require_once('admin_header.php');
require_once(SITE_PATH . '/class/Database.php');
require_once(SITE_PATH . '/class/ModeraTranslator.php');
require_once(SITE_PATH . '/class/templatef.class.php');

// translator
$translator =& ModeraTranslator::instance($language, 'admin_langfiles');
$translator->set_format('html');

// template
$tpl = new template();
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tmpl/admin_translator_popup.html');

$content = '';
switch ($_REQUEST['do']) {
    //
    // Compile language files
    //
    case 'compile':
        require_once(SITE_PATH . '/class/Locale.php');
        require_once(SITE_PATH . '/class/DbTranslatorCompiler.php');
        require_once(SITE_PATH . '/class/TranslatorCompiler.php');

        // fetch selected translator from site settings
        $settings = $database->fetch_first_row("SELECT * FROM settings LIMIT 1");
        $driver = $settings['translator'];

        // check if there are compiler for currenly selected translator
        if (!in_array($driver, TranslatorCompiler::available())) {
            $tpl->addDataItem("TITLE", $translator->tr('nothing to compile'));
            $content .= $translator->tr('driver %s dont requires compiled files', array($driver));
            break;
        }

        $c = new DbTranslatorCompiler($database, SITE_PATH . '/' . LANGUAGES_PATH);
        // set settings
        foreach ($GLOBALS['translator_settings'] as $adriver => $settings) {
            if ($adriver == $driver) $c->set_params($settings, $driver);
        }

        $c->compile($driver);

        $tpl->addDataItem("TITLE", $translator->tr('language files compiled'));
        $content .= '<div>' . $translator->tr('lang files compiled descr') . '</div>';
        $content .= '<input type="button" onClick="window.close()" value="'
            . $translator->tr('close window') . '" />';

        break;

    //
    // save new languages in session
    //
    case 'update_lang_columns':
        if (isset($_POST['selected_languages']) && is_array($_POST['selected_languages'])) {
            $_SESSION['tr_languages'] = $_POST['selected_languages'];
        } else {
            $_SESSION['tr_languages'] = array();
        }

        echo '<html><body><script type="text/javascript">window.opener.window.location.reload(false);'
            .' window.close();</script></body></html>';

        exit();
        break;

    //
    // list languages
    //
    case 'list_lang_columns':
        $tpl->addDataItem("TITLE", $translator->tr('choose language columns'));

        $tpl_lists = new template();
        $tpl_lists->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl_lists->setTemplateFile('tmpl/admin_translator_langcols.html');

        // get array of selected languages
        $sel_languages = (isset($_SESSION['tr_languages']) ? $_SESSION['tr_languages']
            : array('en', 'ee'));

        $res =& $database->query('SELECT `language`, `title` FROM `languages`'
            . ' ORDER BY `title`');

        // create list of unselected
        $languages = array('selected' => array(), 'unselected' => array());
        while ($row = $res->fetch_assoc()) {
            if (in_array($row['language'], $sel_languages)) {
                $type = 'S_LANGS';
            } else {
                $type = 'A_LANGS';
            }
            $languages[$type][$row['language']] = htmlspecialchars($row['title']);
        }

        // set form action
        $tpl_lists->addDataItem('ACTION', './' . basename(__FILE__) . '?do=update_lang_columns');

        $tpl_lists->addDataItem('UP', $translator->tr('up'));
        $tpl_lists->addDataItem('DOWN', $translator->tr('down'));
        $tpl_lists->addDataItem('ALL', $translator->tr('all'));
        $tpl_lists->addDataItem('Submit', $translator->tr('submit'));

        // setup lists
        foreach (array('S_LANGS', 'A_LANGS') as $type) {
            foreach ($languages[$type] as $code => $title) {
                $tpl_lists->addDataItem("$type.CODE", $code);
                $tpl_lists->addDataItem("$type.TITLE", $title);
            }
        }

        $content .= $tpl_lists->parse();
        break;

    default:
        exit();
}

$tpl->addDataItem('CONTENT', $content);
echo $tpl->parse();
