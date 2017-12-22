<?php
/**
 * @version $Revision: 696 $
 */

require_once(SITE_PATH . '/class/Translator/_custPlural.php');
require_once(SITE_PATH . '/class/TranslatorHelpers.php');

/**
 * Database translator driver
 *
 * The slowest translator driver. Main advantage is that this driver do not
 * require compilation stage like, but takes translations directly from
 * database.
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 */
class Translator_database extends Translator_custPlural
{
    /**#@+
     * @access private
     */

    /**
     * Database instance
     *
     * @var Database
     */
    var $_db;

    /**
     * Plural form expressions
     *
     * @var array
     */
    var $_pexpr;

    /**
     * Cache for translations
     *
     * @var array
     */
    var $_cache;

    /**#@-*/

    /*Constructor**************************************************************/

    /**
     * @param Locale $locale
     * @param string $domain
     * @param array $params
     * @return Translator_database
     */
    function Translator_database(&$locale, $domain, $params)
    {
        parent::Translator($locale, $domain);
        $this->_db =& $params['database'];
        $this->_pexpr = array();
        $this->_cache = array();
    }

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
        if (!array_key_exists($lang, $this->_pexpr)) {
            // retrive plural expression from database
            $db =& $this->_db;
            $expr = $db->fetch_first_value('SELECT `expr` FROM `languages`'
                .' WHERE `language`=?', $lang);
            if (!$expr) {
                // plural form expression was not found
                $this->_pexpr[$lang] = null;
                return null;
            }

            $expr = TranslatorHelpers::gtpexpr_to_php($expr);
            $this->_pexpr[$lang] = (false == $expr) ? null : $expr;
        }

        if (is_null($this->_pexpr[$lang])) return null;

        // evaluate plural expression for specified language
        eval($this->_pexpr[$lang]);

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
        if (is_null($n)) {
            $plural = -1;
        } else {
            $plural = $this->_get_plural($lang, $n);
        }

        if (!array_key_exists($lang, $this->_cache)
            || !array_key_exists($domain, $this->_cache[$lang])
            || !array_key_exists($token, $this->_cache[$lang][$domain])
            || !array_key_exists($plural, $this->_cache[$lang][$domain][$token]))
        {
            $db =& $this->_db;
            $translation = $db->fetch_first_value('SELECT `translation` FROM'
                .' `translations` WHERE ?%AND', array('language' => $lang
                , 'domain' => $domain, 'token' => $token, 'plural' => $plural));
            if (false === $translation) {
                $this->_cache[$lang][$domain][$token][$plural] = null;
                // right translation was not found, simulating gettext behaviour
                // trying to get reverse form (singular <=> plural)
                $n = -1 === $plural ? 1 : null;
                return $this->_raw_translate($token, $n, $domain, $lang);
            }

            $this->_cache[$lang][$domain][$token][$plural] = $translation;
        }

        return $this->_cache[$lang][$domain][$token][$plural];
    }

    /**#@-*/
}
