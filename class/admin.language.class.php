<?php
/**
 * @version $Revision: 306 $
 */

require_once(SITE_PATH . '/class/language.class.php');

/**
 * Administration language class. sets and retrieves both active content language and
 * interface language.
 *
 * @package modera_net
 * @access public
 */

class AdminLanguage extends Language
{
    /**
     * Process input language and save it into $this->_language
     *
     * @param string $language
     * @access protected
     */
    function _set_language($language)
    {
        session_name("ADM_LANG_SID");
        session_set_cookie_params(0, $this->_engine_url(), '.' . COOKIE_URL, COOKIE_SECURE);
		session_start();

        if (empty($_SESSION['alanguage']) && empty($language)
            && !empty($GLOBALS['site_settings']['lang']))
        {
            // get default site language
            $language = $GLOBALS['site_settings']['lang'];

        } else if (!empty($language)) {
            // process input language
            $language = $this->_process_language($language);

        } else if (!empty($_SESSION['alanguage'])) {
            // get language from session
            $language = $_SESSION['alanguage'];

        } else {
            // default language (seems that this core is never executed because default
            // site language always is retrived...)
            $language = $this->_process_language('EN');
        }

        $_SESSION['alanguage'] = $language;
        $this->_language = $language;
    }

	/**
	 * Get/Set interface language
	 *
	 * @param string $language
	 * @return string
	 */
	function interfaceLanguage ($language)
	{
        if (empty($_SESSION['alanguage2']) && empty($language))
        {
            // set default site language
            $language = $this->_process_language('EN');

        } else if (!empty($language)) {
            // process input language
            $language = $this->_process_language($language);

        } else if (!empty($_SESSION['alanguage2'])) {
            // get language from session
            $language = $_SESSION['alanguage2'];
        }

        $_SESSION['alanguage2'] = $language;
        return $language;
	}
}
