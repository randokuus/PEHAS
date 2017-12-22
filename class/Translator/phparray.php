<?php
/**
 * @version $Revision: 696 $
 */

require_once(SITE_PATH . '/class/Translator/_custPlural.php');

/**
 * Php array translator driver.
 *
 * Translation strings are stored as php arrays in files. There are diffrent
 * directories for each language. Directory name is two letters language code
 * in lower case. There are separate language file for each domain. Each
 * language file contains $_ variable. $_ is array of form:
 * <code>
 * $_ = array(
 *      // token with plural support
 *      'token1' => array(
 *          0 => 'translated token',
 *          1 => 'translated tokens',
 *      ),
 *
 *      // simple token
 *      'token2' => array(
 *          -1 => 'translated token',
 *      ),
 * )
 * </code>
 * For plural support there should be nn_get_plural.php file in languages
 * catalog directory, where 'nn' is language code. This file must contain
 * expression for calculation plural form. Expression should use $n local
 * variable and save result in $plural variable.
 * <br />
 * Sample file:
 * <code>
 * <?php
 *     $plural = $n != 1;
 * ?>
 * </code>
 * Sample plural form expressions for diffrent language families can be found at
 * {@link http://www.gnu.org/software/gettext/manual/html_node/gettext_150.html#SEC150
 * gettext manual}
 *
 * @author Alexandr Chertkov <s6ruik@modera.net>
 */
class Translator_phparray extends Translator_custPlural
{
    /**#@+
     * @access private
     */

    /**
     * Path to directory with language files
     *
     * @var string
     */
    var $_directory;

    /**
     * Complex array of translations
     *
     * @var string
     */
    var $_translations;

    /**#@-*/

    /*Constructor**************************************************************/

    /**
     * @param Locale $locale
     * @param string $domain
     * @param array $params
     * @return Translator_phparray
     */
    function Translator_phparray(&$locale, $domain, $params)
    {
        parent::Translator($locale, $domain);
        $this->_directory = $params['directory'];
        $this->_translations = array();
    }

    /*Private******************************************************************/

    /**#@+
     * @access private
     */

    /**
     * Load language file
     *
     * @param string $lang
     * @param string $domain
     */
    function _load_data($lang, $domain)
    {
        @include_once($this->_directory . "/$lang/$domain.php");
        if (isset($_) && is_array($_)) {
            $this->_translations[$lang][$domain] = $_;
        } else {
            $this->_translations[$lang][$domain] = array();
        }
    }

    /**#@-*/

    /*Protected****************************************************************/

    /**#@+
     * @access protected
     */

    /**
     * @param string $lang
     * @param int $n
     * @return int|NULL
     */
    function _raw_get_plural($lang, $n)
    {
        @include($this->_directory . "/{$lang}_get_plural.php");
        return isset($plural) ? (int)$plural : null;
    }

    /**
     * @param string $token
     * @param int|NULL $n
     * @param string $domain
     * @param string $lang
     * @return string|NULL
     */
    function _raw_translate($token, $n, $domain, $lang)
    {
        if (!array_key_exists($lang, $this->_translations) || !array_key_exists(
            $domain, $this->_translations[$lang]))
        {
            $this->_load_data($lang, $domain);
        }

        if (isset($this->_translations[$lang][$domain][$token])
            && is_array($this->_translations[$lang][$domain][$token]))
        {
            if (is_null($n)) {
                if (isset($this->_translations[$lang][$domain][$token][-1])) {
                    return $this->_translations[$lang][$domain][$token][-1];
                } else {
                    // singular was not found, try to use plural for 1 (simulating
                    // gettext behaviour)
                    return $this->_raw_translate($token, 1, $domain, $lang);
                }
            } else {
                $plural = $this->_get_plural($lang, $n);
                if (isset($this->_translations[$lang][$domain][$token][$plural])) {
                    return $this->_translations[$lang][$domain][$token][$plural];
                } else {
                    // plural was not found, try to use singular (simulating
                    // gettext behaviour)
                    return $this->_raw_translate($token, null, $domain, $lang);
                }
            }
        }

        return null;
    }

    /**#@-*/
}
