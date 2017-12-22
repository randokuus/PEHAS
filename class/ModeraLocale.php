<?php
/**
 * @version $Revision: 253 $
 */

/**
 * Locale
 *
 * At the moment this class is very limited, it has only get_locale() method
 * for getting locale for current language. In future it should support
 * for retriving all locale information (currency, date format, numbers
 * format,...)
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 */
class ModeraLocale
{
    /**
     * 2 letter language code ISO 639-2
     *
     * @link http://www.loc.gov/standards/iso639-2/langhome.html Language Codes
     * @link http://www.w3.org/WAI/ER/IG/ert/iso639.htm ISO639
     * @var string
     * @access private
     */
    var $_lang_code;

    /**
     * Country code
     *
     * @var string
     * @access private
     */
    var $_country_code;

    /**
     * For some language codes we have exceptions
     *
     * @var string
     * @access private
     */
    var $_locale_map = array(
        'et' => array('et', 'EE'),
        'en' => array('en', 'US'),
        'ua' => array('ru', 'UA'),
        'se' => array('se', 'NO'),
    );

    /*Constructor**************************************************************/

    /**
     * @param stirng $lang_code
     * @param string|NULL $country_code
     * @return ModeraLocale
     */
    function ModeraLocale($lang_code, $country_code = null)
    {
        $lang_code = strtolower($lang_code);
        if (array_key_exists($lang_code, $this->_locale_map)) {
            list($this->_lang_code, $this->_country_code) = $this->_locale_map[$lang_code];

        } else {
            $this->_lang_code = $lang_code;
            if (!is_null($country_code)) {
                $this->_country_code = strtolower($country_code);
            } else {
                $this->_country_code = strtoupper($lang_code);
            }
        }
    }

    /*Public*******************************************************************/

    /**
     * Get locale string for current language
     *
     * @return string
     */
    function get_locale()
    {
        return $this->_lang_code . '_' . $this->_country_code;
    }

    /**
     * Get current language code
     *
     * Language code is always returned in lower case
     *
     * @return string
     */
    function lang_code()
    {
        return $this->_lang_code;
    }
}
