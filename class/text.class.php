<?php
/**
 * @version $Revision: 306 $
 * @package modera_net
 */

require_once(SITE_PATH . '/class/ModeraTranslator.php');

/**
 * Text class, returns strings and data from language files, based on the chosen language
 *
 * @global Database $GLOBALS['database']
 * @global string SITE_PATH
 * @global string LANGUAGES_PATH
 * @access public
 * @deprecated deprecated since Modera 4.0, use {@link ModeraTranslator} instead
 */
class Text
{
    /**
     * Translator instance
     *
     * @var Translator
     * @access private
     */
    var $_translator;

    /**
     * @param string $language
     * @param string $area
     * @return Text
     */
    function Text($language, $area)
    {
        $this->_translator =& ModeraTranslator::instance($language, $area);
        $this->_translator->set_format('text');
    }

    /**
     * Get translation
     *
     * @param string $field token (translation identificator)
     * @return string
     */
    function display($field)
    {
        return $this->_translator->tr($field);
    }

    /**
     * Return translator instance used as backend engine
     *
     * @return Translator
     */
    function &getTranslator()
    {
        return $this->_translator;
    }
}
