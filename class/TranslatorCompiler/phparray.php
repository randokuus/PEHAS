<?php
/**
 * @version $Revision: 466 $
 */

require_once(SITE_PATH . '/class/TranslatorHelpers.php');
require_once(SITE_PATH . '/class/FileSystem.php');

/**
 * Phparray language file compiler
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @see Translator_phparray
 */
class TranslatorCompiler_phparray extends TranslatorCompiler
{
    /**
     * Resource of opened language file
     *
     * @var resource
     * @access private
     */
    var $_fp;

    /**
     * Path to directory for current language files
     *
     * @var string
     * @access private
     */
    var $_lang_path;

    /*Private******************************************************************/

    /**#@+
     * @access private
     */

    /**
     * Escape string before using it in language file
     *
     * @param string $str
     * @return string
     */
    function _escape_str($str)
    {
        return str_replace("'", "\\'", $str);
    }

    /**
     * Create file with plural form expression
     *
     * @param string $fname path to file
     * @param string $expr plural form expression
     * @see TranslatorHelpers::gtpexpr_to_php()
     */
    function _create_plurals_file($fname, $expr)
    {
        if (false === $expr = TranslatorHelpers::gtpexpr_to_php($expr)) {
            trigger_error(sprintf('Unable to compile plural expression for'
                . ' language "%s"', $expr), E_USER_WARNING);
            return false;
        }

        if (false === $fp = @fopen($fname, 'w')) {
            trigger_error(sprintf('Unable to open "%s" for writing', $fname));
            return false;
        }

        fwrite($fp, "<?php $expr ?>");
        fclose($fp);
    }

    /**#@-*/

    /*Public*******************************************************************/

    function add_tr($token, $translation)
    {
        $this->add_ntr($token, array(-1 => $translation));
    }

    function add_ntr($token, $translations)
    {
        if (!is_resource($this->_fp)) return;

        $content = "\t'" . $this->_escape_str($token) . "' => array(\n";
        foreach ($translations as $plural => $translation) {
            if (!is_numeric($plural)) {
                trigger_error(sprintf('Plural form number should be numberic.'
                    . ' "%s" received instead.', $plural), E_USER_WARNING);
                return;
            }

            if ($plural >= $this->_params['nplurals']) {
                trigger_error('Extra plural form received', E_USER_WARNING);
            }

            $content .= "\t\t$plural => '" . $this->_escape_str($translation) . "',\n";
        }
        $content .= "\t),\n";
        fwrite($this->_fp, $content);
    }

    /**
     * Clear language
     *
     * @param Locale $language
     */
    function clear_language(&$locale)
    {
        @FileSystem::rmr($this->_params['targetdir'] . '/' . $locale->lang_code());
    }

    /**
     * Open language for translations
     *
     * Creates language directory if ot not exists and plural form php file
     * if $nplurals is more than 1
     */
    function open_language(&$locale, $nplurals = 1, $expr = '')
    {
        if (!parent::open_language($locale, $nplurals, $expr)) return false;

        // set target directory
        $dirname = $this->_params['targetdir'] . '/' . $locale->lang_code();

        // create target directory (if it was not created yet)
        if (!@FileSystem::mkdir($dirname, 0755, true, SITE_PATH . '/' . LANGUAGES_PATH)) {
            trigger_error('Unable to create target directory for compiled language files: '
                . $dirname, E_USER_WARNING);
            return false;
        }

        // plural form expression file
        if ($nplurals > 1) {
            $fname = $this->_params['targetdir'] . '/' . $locale->lang_code()
                . '_get_plural.php';
            $this->_create_plurals_file($fname, $expr);
        }

        // save path to lang directory
        $this->_lang_path = $dirname;

        return true;
    }

    /**
     * Open domain language file
     *
     * Open domain language file for writing and write special header into it.
     */
    function open_domain($domain)
    {
        if (!parent::open_domain($domain)) return false;

        $fname = $this->_lang_path . '/' . $domain . '.php';

        if (false === $this->_fp = @fopen($fname, 'w')) {
            trigger_error(sprintf('Unable to open "%s" for writing', $fname));
            return false;
        }

        // write file header
        fwrite($this->_fp, "<?php\n\n\$_ = array(\n");

        return true;
    }

    /**
     * Close domai language file
     *
     * Write footer in language file and close it.
     */
    function compile_domain()
    {
        if (!$this->_fp) return false;

        // write footer and close opened language file
        fwrite($this->_fp, ");\n\n?>");
        fclose($this->_fp);

        parent::compile_domain();
        return true;
    }
}