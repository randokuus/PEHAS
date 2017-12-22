<?php
/**
 * @version $Revision: 466 $
 */

require_once(SITE_PATH . '/class/FactoryPattern.php');
require_once(SITE_PATH . '/class/FileSystem.php');

/**
 * Language file compiler
 *
 * Universal language file compiler based on pluggable drivers. Drivers should
 * use streams to write data into language files, to make it possible to
 * create big lanuage files.
 *
 * <code>
 * $compiler =& TranslatorCompiler::driver('gettext', '/data/languages', array('msgfmt'
 *  => '/usr/bin/msgfmt');
 * $compiler->open_language(new Locale('en'), 2, 'n != 1');
 * $compiler->open_domain('language_domain');
 *
 * $compiler->add_tr('message', 'translated message');
 * $compiler->add_ntr('message', array(0 => 'translated message'
 *  , 1 => 'translated messages');
 *
 * $compiler->compile_domain();
 * $compiler->compile_language();
 * $compiler->compile_all();
 * </code>
 *
 * Before adding translations it's needed to call {@link open_language()} to pass
 * language specific information and than call {@link open_domain()}. After adding
 * translations {@link compile_domain()}, {@link compile_language()} and finally
 * {@link compile_all()} should be called. {@link open_domain()} will automatically
 * call {@link compile_domain()} to compile previously opened and not compiled
 * domain. {@link open_language()} will call to {@link compile_domain()} and
 * {@link compile_language()}.
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @abstract
 */
class TranslatorCompiler
{
    /**#@+
     * @access protected
     */

    /**
     * Parameters array
     *
     * @var array
     */
    var $_params;

    /**#@-*/

    /*Constructor**************************************************************/

    /**
     * @param string $targetdir target directory for storing language files
     * @param array $params associative array of compiler parameters
     * @return LangCompiler
     */
    function TranslatorCompiler($targetdir, $params)
    {
        $this->_params['locale'] = null;
        $this->_params['targetdir'] = $targetdir;
        unset($params['targetdir']);

        // remove all files in target directory

        // try to create targetdir if it is not exists
        FileSystem::mkdir($targetdir, 0755, true, SITE_PATH . '/' . LANGUAGES_PATH);

        foreach ($params as $param => $value) {
            $this->set_param($param, $value);
        }
    }

    /**
     * Factory method
     *
     * @param string $driver
     * @param string $targetdir
     * @param array $params
     * @return LangCompiler
     * @static
     */
    function &driver($driver, $targetdir, $params = array())
    {
        $obj =& FactoryPattern::factory('TranslatorCompiler', $driver, dirname(__FILE__)
            , array($targetdir, &$params));
        return $obj;
    }

    /*Protected****************************************************************/

    /**
     * Get array of allowed parameters with their default values
     *
     * Array keys are case sensitive parameter names
     *
     * @return array
     * @access protected
     */
    function _def_params()
    {
        return array();
    }

    /*Public*******************************************************************/

    /**#@+
     * @abstract
     */

    /**
     * Add singular translation
     *
     * @param string $token
     * @param string $translation
     */
    function add_tr($token, $translation) { trigger_error(
        'You have to implement abstract method add_tr()', E_USER_ERROR); }

    /**
     * Add translation with diffrent plural forms
     *
     * @param string $token
     * @param array $translations array with diffrent plurla forms translations
     *  keys are plural form numbers, values are translations
     */
    function add_ntr($token, $translations) { trigger_error(
        'You have to implement abstract method add_ntr()', E_USER_ERROR); }

    /**#@-*/

    /**
     * Get array of available drivers
     *
     * @staticvar array $available cache
     * @return array
     * @static
     */
    function available()
    {
        static $available = null;
        if (is_null($available)) $available = FactoryPattern::available('TranslatorCompiler'
            , dirname(__FILE__));
        return $available;
    }

    /**
     * Set parameter
     *
     * NB! Parameter name are case sensitive
     *
     * @param string $name
     * @param mixed $value
     */
    function set_param($name, $value)
    {
        if (array_key_exists($name, $this->_def_params())) {
            $this->_params[$name] = $value;
        }
    }

    /**
     * Start adding phrases for specified language
     *
     * @param Locale $locale
     * @param int $nplurals
     * @param string $expr
     * @return bool TRUE if language was opened successfully, FALSE otherwise
     */
    function open_language(&$locale, $nplurals = 1, $expr = '')
    {
        // automatically call compile_domain() in case previous domain was not compiled
        if (array_key_exists('domain', $this->_params) && !is_null($this->_params['domain'])) {
            $this->compile_domain();
        }

        // automatically call compile_language(in case previous language was not compiled)
        if (array_key_exists('locale', $this->_params) && !is_null($this->_params['locale'])) {
            $this->compile_language();
        }

        $this->_params['locale'] =& $locale;
        $this->_params['nplurals'] = $nplurals;
        $this->_params['expr'] = $expr;
        return true;
    }

    /**
     * Clear language storage
     *
     * @param Locale $locale
     */
    function clear_language(&$locale) {}

    /**
     * Start adding phrases for specified domain
     *
     * @param string $domain
     * @return bool TRUE if domain was opened successfully, FALSE otherwise
     */
    function open_domain($domain)
    {
        // if language was not opened
        if (is_null($this->_params['locale'])) {
            trigger_error('Trying to open domain before opening language'
                , E_USER_WARNING);
            return false;
        }

        // automatically call compile_domain() in case previous domain was not compiled
        if (array_key_exists('domain', $this->_params) && !is_null($this->_params['domain'])) {
            $this->compile_domain();
        }

        $this->_params['domain'] = $domain;
        return true;
    }

    /**
     * Finish populating current domain translations
     *
     * Might be overriden in descendants
     *
     * @return bool status of compiling
     */
    function compile_domain()
    {
        $this->_params['domain'] = null;
        return true;
    }

    /**
     * Finish populating current language translations
     *
     * Might be overriden in descendants
     *
     * @return bool status of compiling
     */
    function compile_language()
    {
        $this->_params['locale'] = null;
        return true;
    }

    /**
     * Finish populating all phrases for all languages
     *
     * Might be overriden in descendants
     *
     * @return bool status of compiling
     */
    function compile_all()
    {
        $this->compile_domain();
        $this->compile_language();
        return true;
    }
}
