<?php
/**
 * @version $Revision: 306 $
 */

require_once(SITE_PATH . '/class/TranslatorCompiler.php');
require_once(SITE_PATH . '/class/Locale.php');
require_once(SITE_PATH . '/class/FileSystem.php');

/**
 * Class used for compiling language files from database data
 *
 * Relies on {@link TranslatorCompiler} class. Uses diffrent {@link TranslatorCompiler} objects
 * simultaneously to compile different language files executing only one database query for data.
 * Since {@link TranslatorCompiler} drivers write data into files using streaming way it's
 * possible to write very big language files.
 *
 * <code>
 * $compiler =& new DbTranslatorCompiler($db, dirname(__FILE__) . '/data');
 * $compiler->set_params(array('common_parameter' => 'common_parameter_value'));
 * $compiler->set_params(array('msgfmt' => '/usr/bin/msgfmt'), 'gettext');
 * $compiler->compile();
 * </code>
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 */
class DbTranslatorCompiler
{
    /**
     * Database object
     *
     * @var Database
     * @access private
     */
    var $_db;

    /**
     * Array of parameters
     *
     * Multidimensional array of parameters. Parameter are separated between
     * diffrent drivers. Keys of this array are drivers names, values are
     * associative array of parameters. There are special key name: "_common",
     * this element contains array with parameters common for all drivers.
     *
     * @var array
     * @see _params()
     * @access private
     */
    var $_params;

    /*Constructor**************************************************************/

    /**
     * @param Database $db
     * @param string $targetdir
     * @return DbLangCompiler
     */
    function DbTranslatorCompiler(&$db, $targetdir = null)
    {
        $this->_db =& $db;
        $this->_params = array('_common' => array('targetdir' => $targetdir));
    }

    /*Private******************************************************************/

    /**#@+
     * @access private
     */

    /**
     * Get array of parameters for specified compiler
     *
     * @param string|NULL driver name or NULL if common parameters needed
     * @param string|NULL param
     * @return mixed
     */
    function _params($driver = null, $param = null)
    {
        if (is_null($driver) || !array_key_exists($driver, $this->_params)) {
            $params = $this->_params['_common'];
        } else {
            $params = array_merge($this->_params['_common'], $this->_params[$driver]);
        }

        if (is_null($param)) {
            return $params;
        } else {
            return $params[$param];
        }
    }

    /**#@-*/

    /*Public*******************************************************************/

    /**
     * Set parameters
     *
     * @param array $params
     * @param string|NULL $driver driver for which parameters will be set or NULL
     *  if parameters should be applied to all drivers
     */
    function set_params($params, $driver = null)
    {
        if (is_null($driver)) $driver = '_common';
        if (array_key_exists($driver, $this->_params) && is_array($this->_params[$driver])) {
            $this->_params[$driver] = array_merge($this->_params[$driver], $params);
        } else {
            $this->_params[$driver] = $params;
        }
    }

    /**
     * Compile language file(s)
     *
     * @param string|array|NULL $compiler compiler or array of compilers to use if not passed or NULL
     *  than all available compilers will be used
     * @param string|array|NULL $language language or array of languages to compile in not passed or
     *  NULL than all available languages will be compiled
     * @param string|array|NULL $domain domain or array of domains to compile if not passes or NULL
     *  than all domains will be compiled
     */
    function compile($compiler = null, $language = null, $domain = null)
    {
        //
        // process arguments
        //

        if (is_null($compiler)) {
            $compilers = TranslatorCompiler::available();
        } else {
            if (!in_array($compiler, TranslatorCompiler::available())) {
                trigger_error(sprintf('Unknown compiler driver "%s"', $compiler)
                    , E_USER_ERROR);
            }

            $compilers = array($compiler);

            // actually here we could remove all content in target directory since there might stay
            // some old drivers language files, we will not do it because it's probably wery rare
            // case and it's safer to not remove that files automatically and dedicate this
            // action to administator
        }

        if (is_string($language)) $language = array($language);
        if (is_string($domain)) $domain = array($domain);

        // initialize language compilers
        $c = array();
        foreach ($compilers as $comp_type) {
            $targetdir = $this->_params($comp_type, 'targetdir') . '/' . $comp_type;
            $c[] =& TranslatorCompiler::driver($comp_type, $targetdir, $this->_params($comp_type));

            // is language and domain is null, than we want to recompile all languages and domains
            // in this case we want to remove current driver language files directory
            // because probably some languages or domains was removed from database and should be
            // removed from fs as well
            if (is_null($language) && is_null($domain)) {
                @FileSystem::rmr($targetdir);
            } else if (is_null($domain)) {
                // some languages were passed, but domains not, it means that we are going to
                // recompile languages and for removing old domains data it's needed to
                // clean language directories (if they exists) this action is delegated to
                // leaf compiler
                end($c);
                $compiler =& $c[key($c)];
                foreach ($language as $cur_language) {
                    $compiler->clear_language(new Locale($cur_language));
                }
            }
        }

        // select language(s)
        if (is_null($language)) {
            $res_lang =& $this->_db->query('SELECT `language`, `nplurals`, `expr` FROM'
                . ' `languages`');
        } else {
            $res_lang =& $this->_db->query('SELECT `language`, `nplurals`, `expr` FROM'
                . ' `languages` WHERE `language` IN(?@)', $language);
        }

        // loop through languages
        while ($row = $res_lang->fetch_assoc()) {
            for (reset($c); list($i) = each($c);) {
                if (!$c[$i]->open_language(new Locale($row['language'])
                    , $row['nplurals'], $row['expr'])) unset($c[$i]);
            }

            if (is_null($domain)) {
                // select all translations for current language
                $res_tr =& $this->_db->query('SELECT `domain`, `token`, `translation`, `plural` FROM'
                    . ' `translations` WHERE `language` = ? ORDER BY `domain`, `token`, `plural`'
                    , $row['language']);
            } else {
                // select all translations for current language and domain(s)
                $res_tr =& $this->_db->query('SELECT `domain`, `token`, `translation`, `plural` FROM'
                    . ' `translations` WHERE `language` = ? AND `domain` IN(?@) ORDER BY `domain`'
                    . ', `token`, `plural`', $row['language'], $domain);
            }

            // repeat loop if there was no records selected from database
            if (!$res_tr->num_rows()) continue;

            // context array
            $context = array('domain' => null, 'token' => null, 'translations' => array());

            // loop through translations
            for ($exit = false;;) {
                if (false === $row = $res_tr->fetch_assoc()) {
                    // no translations left
                    $row = array('token' => null); // force token change
                    $exit = true;
                }

                // open first domain
                if (is_null($context['domain'])) {
                    for (reset($c); list($i) = each($c);) {
                        $c[$i]->open_domain($row['domain']);
                    }
                }

                if (!is_null($context['token']) && $context['token'] !== $row['token']) {
                    // add translation
                    if (1 === count($context['translations']) && -1 === key($context['translations'])) {
                        // single
                        for (reset($c); list($i) = each($c);) {
                            $c[$i]->add_tr($context['token'], current($context['translations']));
                        }
                    } else {
                        // plural
                        for (reset($c); list($i) = each($c);) {
                            $c[$i]->add_ntr($context['token'], $context['translations']);
                        }
                    }

                    // erase prev_translations
                    $context['translations'] = array();
                }

                // exit from loop if no more translations left
                if ($exit) break;

                // change domain (open new domain)
                if (!is_null($context['domain']) && $context['domain'] != $row['domain']) {
                    for (reset($c); list($i) = each($c);) {
                        $c[$i]->open_domain($row['domain']);
                    }
                }

                // save current translation and token
                $context['translations'][(int)$row['plural']] = $row['translation'];
                $context['token'] = $row['token'];
                $context['domain'] = $row['domain'];
            }
        }

        // compile opened language files
        for (reset($c); list($i) = each($c);) {
            $c[$i]->compile_all();
        }
    }
}
