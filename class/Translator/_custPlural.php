<?php
/**
 * @version $Revision: 696 $
 */

/**
 * Abstract translator class with custom plurals calculation support.
 *
 * Translators that should have plurala support and plural form calculation
 * is made in php code (drivers: phparray, database,...) must be extended
 * from this class. Plural form calculation in {@link Translator_gettext gettext
 * Translator} relies on internal gettext plural support, that's why it should
 * not be extended from this class.
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @abstract
 */
class Translator_custPlural extends Translator
{
    /**
     * Plurals cache
     *
     * @var array
     * @access private
     */
    var $_pcache = array();

    /**
     * Get plural group
     *
     * @param string $lang language code
     * @param int $n count
     * @return int plural group (0 if group coldn't be calculated)
     * @access protected
     */
    function _get_plural($lang, $n)
    {
        if (array_key_exists($lang, $this->_pcache) && is_null($this->_pcache[$lang])) {
            return 0;
        }

        if (!array_key_exists($lang, $this->_pcache) || !array_key_exists($n
            , $this->_pcache[$lang]))
        {
            $plural = $this->_raw_get_plural($lang, $n);

            if (is_null($plural)) {
                // save null in array element, it means that plural form cannot
                // be calculated and next time file will not be included
                $this->_pcache[$lang] = null;
                return 0;
            } else {
                $this->_pcache[$lang][$n] = $plural;
            }
        }

        return $this->_pcache[$lang][$n];
    }

    /**
     * Get plural group number
     *
     * @param string $lang
     * @param int $n
     * @return int|NULL plural group number, or NULL if couldnt evaluate plural
     *  form expression
     * @abstract
     * @access protected
     */
    function _raw_get_plural($lang, $n) { trigger_error(
        'You have to implement abstract method', E_USER_ERROR); }
}
