<?php
/**
 * @version $Revision: 306 $
 */

require_once(SITE_PATH . '/class/Translator.php');
require_once(SITE_PATH . '/class/ModeraLocale.php');

/**
 * Static class for instantiating Translator appropriate to modera core settings
 *
 * <code>
 * $translator =& ModeraTranslator::instance('en', 'my_module_name');
 * </code>
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @static
 */
class ModeraTranslator
{
    /**
     * Singleton & Factory method for creating translator instance
     *
     * @param string $language 2 letter language code ISO 639-2
     * @param string $domain translations category
     * @return Translator
     * @global Database $GLOBAL['database']
     * @static
     */
    function &instance($language, $domain)
    {
        static $translators = array();

        if (!isset($translators[$language][$domain])) {
            $driver = $GLOBALS['site_settings']['translator'];
            if (!$driver) {
                trigger_error('Cannot get Translator backend parameter', E_USER_WARNING);
                $driver = 'database'; // fallback driver
            }

            $params = array();

            // select appropriate parameters for current driver
            switch ($driver) {
                case 'database':
                    // check if database available
                    if (isset($GLOBALS['database']) && is_object($GLOBALS['database'])) {
                        $params['database'] =& $GLOBALS['database'];
                    } else {
                        trigger_error('Couldn\t access Database object from ModeraTranslator'
                            , E_USER_ERROR);
                    }
                    break;

                case 'phparray':
                case 'gettext':
                    $params['directory'] = SITE_PATH . '/' . LANGUAGES_PATH . "/$driver";
                    break;
            }

            $translators[$language][$domain] =& Translator::driver($driver, new ModeraLocale($language)
                , $domain, $params);
        }

        return $translators[$language][$domain];
    }
}
