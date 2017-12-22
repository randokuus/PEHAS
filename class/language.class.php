<?php
/**
 * @version $Revision: 253 $
 */

/**
 * Language object, check if the language is available and set the language. EN is default if all fails
 *
 * @package modera_net
 * @access public
 */
class Language
{
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $_db;

    /**
     * Currently selected language
     *
     * @var string
     * @access private
     */
    var $_language;

    /**
     * @param Database $db
     * @param string $language modera's language code
     * @return Language
     */
    function Language(&$db, $language)
    {
        $this->_db =& $db;
        $this->_set_language($language);
    }

    /**
     * Process input language and save it into $this->_language
     *
     * @param string $language
     * @access protected
     */
    function _set_language($language)
    {
        session_set_cookie_params(0, $this->_engine_url(), COOKIE_URL, COOKIE_SECURE);
		session_start();

        if (empty($_SESSION['language']) && empty($language)
            && !empty($GLOBALS['site_settings']['lang']))
        {
            // get default site language
            $language = $GLOBALS['site_settings']['lang'];

        } else if (!empty($language)) {
            // process input language
            $language = $this->_process_language($language);

        } else if (!empty($_SESSION['language'])) {
            // get language from session
            $language = $_SESSION['language'];

        } else {
            // default language (seems that this core is never executed because default
            // site language always is retrived...)
            $language = $this->_process_language('EN');
        }

        $_SESSION['language'] = $language;
        $this->_language = $language;
    }

    /**
     * Get "engine url" which actually is cookie path
     *
     * Logic stays from previous version of class
     *
     * @return string
     * @access protected
     */
    function _engine_url()
    {
        if (false !== $pos = strpos(SITE_URL, '/', 8)) {
            return substr(SITE_URL, $pos);
        } else {
            return '/';
        }
    }

    /**
     * Process input language
     *
     * Check if specified language is available and return it back, if not than check if default
     * English language is available and return it, if not than return input language.
     *
     * @param string $language
     * @return string uppercased language code
     * @access protected
     */
    function _process_language($language)
    {
        static $available_langs = null;

        // fetch all languages if was not fetched yet (we have to store languages in static variable
        // since this method might be called several times)
        if (is_null($available_langs)) {
            $res =& $this->_db->query('SELECT `language` FROM `languages`');
            while ($row = $res->fetch_assoc()) {
                $available_langs[] = $row['language'];
            }
        }

        $language = strtoupper($language);

        if (!$available_langs) return $language;

        if (in_array(strtolower($language), $available_langs)) {
            return $language;
        } else if (in_array('en', $available_langs)) {
            return 'EN';
        } else {
            return strtoupper(current($available_langs));
        }
    }

    /**
     * Get currently selected language
     *
     * @return string
     */
    function lan()
    {
        return $this->_language;
    }
}
