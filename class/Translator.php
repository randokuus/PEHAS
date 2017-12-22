<?php
/**
 * @version $Revision: 665 $
 */

require_once(SITE_PATH . '/class/FactoryPattern.php');

/**
 * Translator
 *
 * Universal translator system based on pluggable drivers. Main features are
 * derived from {@link http://www.gnu.org/software/gettext/ gettext}. Supports
 * virtually any number of plural forms. Dealing with plurals is done the same
 * way as in {@link http://www.gnu.org/software/gettext/ gettext}. Several
 * output formats supported: text, html and js.
 *
 * <code>
 * $translator =& Translator::driver('gettext', new Locale('en'), 'myAppDomain'
 *      , array('targetdir' => '/data/gettext'));
 *
 * // singular translations
 * echo $translator->tr('translate me');
 * echo $translator->tr('translate me', 'html'); // format overriding
 * echo $translator->tr('translate me', null, 'another_domain'); // domain overriding
 * echo $translator->tr('translate me', null, null, 'another_language'); // language overriding
 *
 * // plural translations
 * echo $tanslator->ntr('%d files', 2);
 * echo $tanslator->ntr('%d files', 2, 'js');
 * echo $tanslator->ntr('%d files', 2, 'js', 'another_domain');
 * echo $tanslator->ntr('%d files', 2, null, null, 'another_language');
 * </code>
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @abstract
 */
class Translator
{
    /**#@+
     * @access protected
     */

    /**
     * Default format of messages returned by translate methods
     *
     * @var string one of 'text', 'js' or 'html'
     */
    var $_format;

    /**
     * Default text domain
     *
     * @var string
     */
    var $_domain;

    /**
     * Locale object
     *
     * @var Locale
     */
    var $_locale;

    /**#@-*/

    /*Constructor**************************************************************/

    /**
     * @param Locale $locale
     * @param string $domain default domain
     */
    function Translator(&$locale, $domain)
    {
        $this->_locale =& $locale;
        $this->_domain = $domain;
        $this->_format = 'text';
    }

    /**
     * Get Translator instance
     *
     * @param string $driver
     * @param Locale $locale
     * @param string $domain
     * @param array $params
     * @return Translator
     * @static
     */
    function &driver($driver, &$locale, $domain, $params = array())
    {
        $obj =& FactoryPattern::factory('Translator', $driver, dirname(__FILE__)
            , array(&$locale, $domain, $params));
        return $obj;
    }

    /**
     * Array of available translator drivers
     *
     * @staticvar array $available cache
     * @return array
     * @static
     */
    function available()
    {
        static $available = null;
        if (is_null($available)) $available = FactoryPattern::available('Translator'
            , dirname(__FILE__));
        return $available;
    }

    /*Private******************************************************************/

    /**#@+
     * @access private
     */

    /**
     * Format translated strings
     *
     * @param string $text
     * @param string $format one of 'text', 'js' or 'html'
     * @return string
     */
    function _format($text, $format)
    {
        switch ($format) {
            case 'html':
                return htmlspecialchars($text);

            case 'js':
                return strtr($text, array("'"=> "\\'", '\\' => '\\\\', '"' => '\\"'
                    , "\r" => '\\r', "\n" => '\\n', '</' => '<\/'));


            case 'text':
            default:
                return $text;
        }
    }

    /**
     * Get formatted token name for displaying it in case of translation was not found
     *
     * @param string $domain
     * @param string $token
     * @return string
     */
    function _missed_translation($domain, $token)
    {
        return "*$domain|$token*";
    }

    /**
     * Translate token
     *
     * If argument $n passed and not NULL than translated token will be passed to
     * {@link http://www.php.net/manual/en/function.sprintf.php sprintf()} function
     * for formatting with $n argument.
     *
     * @param string $token
     * @param int|NULL $n if set than translate will return correct plural form
     *  for count n
     * @param array|NULL $args array of arguments passed to {@link vsprintf()} function
     * @param string|NULL $format override output format
     * @param string|NULL $domain override default domain
     * @param string|NULL $lang override default language
     * @return string
     */
    function _translate($token, $n, $args, $format, $domain, $lang)
    {
        if (is_null($format)) $format = $this->_format;
        if (is_null($domain)) $domain = $this->_domain;
        if (!is_null($n)) $n = abs($n);

        if (is_null($lang)) {
            $lang = $this->_locale->lang_code();
        } else {
            $lang = strtolower($lang);
        }

        $translation = $this->_raw_translate($token, $n, $domain, $lang);

        if (is_null($translation)) {
            $translation = $this->_missed_translation($domain, $token);
        } else if (!empty($args)) {
            $translation = vsprintf($translation, $args);
        }

        return $this->_format($translation, $format);
    }

    /**#@-*/

    /*Protected****************************************************************/

    /**
     * Real translation method
     *
     * @param string $token
     * @param int|NULL $n if set than translate will return correct plural form
     *  for count n
     * @param string $domain domain
     * @param string $lang language
     * @return string|NULL translated token or NULL if no translation was found
     * @abstract
     * @access protected
     */
    function _raw_translate($token, $n, $domain, $lang) { trigger_error(
        'You have to implement abstract method _raw_translate()', E_USER_ERROR); }

    /*Public*******************************************************************/

    /**
     * Plural version of {@link tr()}
     *
     * Translated token will be passed to {@link http://www.php.net/manual/en/function.sprintf.php sprintf()}
     * function for formatting with $n argument.
     *
     * @param string $token
     * @param int $n
     * @param array|NULL $args array of arguments passed to {@link vsprintf()} function
     * @param string|NULL $format
     * @param string|NULL $domain
     * @param string|NULL $lang
     * @return string
     */
    function ntr($token, $n, $args = null, $format = null, $domain = null, $lang = null)
    {
        return $this->_translate($token, $n, $args, $format, $domain, $lang);
    }

    /**
     * Translate token
     *
     * @param string $token
     * @param array|NULL $args array of arguments passed to {@link vsprintf()} function
     * @param string|NULL $format
     * @param string|NULL $domain
     * @param string|NULL $lang
     * @return string
     */
    function tr($token, $args = null, $format = null, $domain = null, $lang = null)
    {
        return $this->_translate($token, null, $args, $format, $domain, $lang);
    }

    /**
     * Format setter
     *
     * @param string $format
     */
    function set_format($format)
    {
        $this->_format = $format;
    }
}
