<?php
/**
 * @version $Revision$
 */

require_once SITE_PATH . '/class/JsonEncoder.php';

/**
 * Template engine class
 *
 * @author Stanislav Chichkan <stas.chichkan@modera.net>
 */
class ModeraTemplate
{
    /**
     * template filename
     *
     * @var string
     * @access protected
     */
    var $_template_file;

    /**
     * Associative array with data to be used in parsing
     *
     * @var array
     * @access protected
     */
    var $_data = array();

    /**
     * Cache lifetime
     * @var integer cache expiry time in minutes
     * @access protected
     */
    var $_cache_ttl = 30;

    /**
     * This enables template caching.
     * <ul>
     *  <li>false - no caching</li>
     *  <li>true  - caching enabled</li>
     * </ul>
     * @var bool
     * @access protected
     */
    var $_caching;

    /**
     * Used or not, noncachable items
     * in templates ( <TPL_NOCACHE:VAR> )
     *
     * @var boolean use noncachable items
     * @access protected
     */
    var $_use_noncachable = false;

    /**
     * Dir where cached pages and compiled
     * templates is located
     * For modera.net must be allways cache/
     *
     * @var string
     * @access protected
     */
    var $_cache_path            = '';

    /**
     *
     * Bullean diagnostic var. Show:
     * <ul>
     *  <li>false - cache not used</li>
     *  <li>true  - cache used</li>
     * </ul>
     *
     * @var boolean
     * @access protected
     */
    var $_cache_used = false;


    /**
     * @var string cache die phrase, don't alter
     */
    var $cache_die_phrase = "<?php die('cachefile');?>";

    /**
     * @var string compile die phrase
     */
    var $compl_die_phrase ='<?php defined("MODERA_KEY")|| die(); ?>';

    /**
     * This forces templates to compile every time.
     * Useful for development or debugging.
     *
     * @var boolean
     * @access protected
     */
    var $_force_compile = false;

    /**
     * error handling warning level,
     * TPL_WARN_CRITICAL by default.
     *
     * @var string error handling warning level
     * @access public
     */
    var $warn_level = TPL_WARN_CRITICAL;

    var $error;

    /**
     * @var string language code.
     */
    var $_language;

    /**
     * Constructor
     *
     * @return ModeraTemplate
     * @access public
     */
    function ModeraTemplate()
    {
        // if force compile is true, then recompiling templates
        if (isset($GLOBALS["conf_tpl_force_compile"])) {
            $this->setForceCompile($GLOBALS["conf_tpl_force_compile"]);
        }

        if (!empty($GLOBALS["conf_tpl_cache_ttl"])) {
            $this->setCacheTtl($GLOBALS["conf_tpl_cache_ttl"]);
        }

        if (!empty($GLOBALS["conf_tpl_cache_use_noncachable"])) {
            $this->setCacheUseNoncachable($GLOBALS["conf_tpl_cache_use_noncachable"]);
        }

        if (!empty($GLOBALS["conf_tpl_cache_path"])) {
            $this->setCachePath($GLOBALS["conf_tpl_cache_path"]);
        }

        if (!empty($GLOBALS["conf_tpl_warn_level"])) {
            $this->setWarnLevel($GLOBALS["conf_tpl_warn_level"]);
        }
    }

    /**
     * Execute function defined in templates by
     *
     * <code>
     * <TPL_FUNC:funcname param1="value1" param2="value2">
     * </code>
     *
     * @return mixed function results
     * @access protected
     */
    function _functionCall($funcname, $param)
    {
        static $cacheholder = array('helpers' => array()
                                  , 'inst'    => array());
        $helpername = 'Helper_' . $funcname;
        $helper = null;
        if (!isset($cacheholder['inst'][$funcname])) {

            if (empty($cacheholder['helpers'])) {
                $cacheholder['helpers'] = FactoryPattern::available('ModeraTemplate', dirname(__FILE__));
            }

            if (in_array($helpername, $cacheholder['helpers'])) {
                $helper =& FactoryPattern::factory('ModeraTemplate', $helpername, dirname(__FILE__));
                $cacheholder['inst'][$funcname] =& $helper;
            }
        } else {
            $helper = $cacheholder['inst'][$funcname];
        }

        if (is_object($helper)) {
            return $helper->execute($this, $param);
        } else {
            return $this->reportWarningMessage('template helper ' . $funcname . ' - not found');
        }

    }

    /**
     * Set template variable
     *
     * @param mixed $varname associative array of template variables assignments
     *              varname => value or string specifying one template variable name
     * @param mixed $value template variable value
     */
    function assign($varname, $value = null)
    {
        if (is_array($varname)) {
            foreach ($varname as $var => $value) {
                if ($var != '') {
                    $this->_data[$var] = $value;
                }
            }
        } else {
            if ($varname != '') {
                $this->_data[$varname] = $value;
            }
        }
    }

    /**
     * Add Data to be used in template.
     *
     * @param array an associative array with data to be used in parsing
     * @access public
     * @return boolean true if data loaded suscessefully, FALSE on error
     */
    function assignAll($data)
    {
        if (!is_array($data)) {
            $this->setError('$data is not an associative array.');
            return false;
        } else {
            $this->_data = $data;
            return true;
        }
    }

    /**
     * Get array of assigned vars or
     * single assigned var value, if it name is declared in args.
     *
     * @param string - var name
     * @return array an associative array with data to be used in parsing, or single var value
     * @access public
     */
    function getAssigned($varname = null)
    {
        if (null !== $varname) {
            return @$this->_data[$varname];
        } else {
            return $this->_data;
        }
    }

    /**
     * test to see if valid compiled version of template
     * is exists for this template
     *
     * @param string - templete filename
     * @return bool - true if compiled version of
     *                template is exists, false otherwise
     * @access protected
     */
    function _isCompiled($template_file = null)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }
        $compl_file = $this->_compiledFilepath($template_file);

        // read cache
        if (is_readable($compl_file)) {
            $compl_time  = filemtime($compl_file);
            $templ_time  = filemtime($template_file);
            if ($compl_time >= $templ_time) {
                // valid compiled template found
                return true;
            } else {
                // compile is out dated
                @unlink($compl_file);
                return false;
            }
        } else {
            // compiled template does not exist. So does compile.
           return false;
        }
    }

    /**
     * appends values to template variables
     *
     * @param array|string $var the template variable name(s)
     * @param mixed $value the value to append
     * @access public
     */
    function append($varname, $value=null, $merge=false)
    {
        if (is_array($varname)) {
            // $varname is an array, ignore $value
            foreach ($varname as $_key => $_val) {
                if ($_key != '') {
                    if (!@is_array($this->_data[$_key])) {
                        settype($this->_data[$_key], 'array');
                    }
                    if ($merge && is_array($_val)) {
                        foreach ($_val as $_mkey => $_mval) {
                            $this->_data[$_key][$_mkey] = $_mval;
                        }
                    } else {
                        $this->_data[$_key][] = $_val;
                    }
                }
            }
        } else {
            if ($varname != '' && isset($value)) {
                if (!@is_array($this->_data[$varname])) {
                    settype($this->_data[$varname], 'array');
                }
                if ($merge && is_array($value)) {
                    foreach ($value as $_mkey => $_mval) {
                        $this->_data[$varname][$_mkey] = $_mval;
                    }
                } else {
                    $this->_data[$varname][] = $value;
                }
            }
        }
    }

    /**
     * Reset all template variables
     * @access public
     */
    function clear_all_assign()
    {
        $this->_data = array();
    }

    /**
     * get compiler object
     * @access public
     */
    function getCompiler()
    {
        $compiler =& FactoryPattern::factory('ModeraTemplate', 'Compiler', dirname(__FILE__));
        $compiler->setWarnLevel($this->warn_level);
        return $compiler;
    }

    /**
     * test to see if valid cache exists for this template
     *
     * @param string $template_file name of template file
     * @param string $instance
     * @return bool
     * @access public
     */
    function isCached($template_file = null, $instance = null)
    {
        // return FALSE if caching is turned off without any further work
        if (!$this->_caching) {
            return false;
        }

        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        // cache filename
        $cache_file = $this->_cacheFilepath($template_file, $instance);

        return $this->isValidCache($cache_file);
    }

    /**
     * Return path of template file
     *
     * @return string path to template file
     * @access public
     */
    function getTemplateFile()
    {
        return $this->_template_file;
    }

    /**
     * test to see if valid cache file exists
     *
     * @param string $cache_file file
     * @return bool
     * @access public
     */
    function isValidCache($cache_file)
    {
        // read cache
        if (is_readable($cache_file)) {
            $cache_time = filemtime($cache_file);
            $cache_age = (time() - $cache_time) / 60;
            if ($cache_age <= $this->_cache_ttl) {
                // valid cache found
                return true;
            } else {
                // cache is outdated
                return false;
            }
        } else {
            // file does not exist. So does cache.
            return false;
        }
    }

    /**
     * Return full path of cache file
     *
     * @param string a full or relative path to template file.
     * @param string instance name
     * @return string full path to cache file
     * @access private
     */
    function _cacheFilepath($template_file, $instance)
    {
        $cwd = getcwd();
        return $this->_cache_path . 'tpl_' . md5("$cwd|$template_file|$instance") . '.php';
    }

    /**
     * Return full path of compiled file
     *
     * @param string a full or relative path to template file.
     * @return string full path to compiled file
     * @access private
     */
    function _compiledFilepath($template_file)
    {
        $cwd = getcwd();
        return $this->_cache_path . 'compiled_tpl_' . md5("$cwd|$template_file") . '.php';
    }

    /**
     * Return rendered page from cache
     * cache file is constructed tpl_ + $this->tplfile + md5("$template_file|$instance") + .php
     *
     * @param string a full or relative path to template file. Not required if it was already set using method setTemplateFile()
     * @param string instance name (optional)
     * @return mixed rendered page, FALSE on error
     * @access public
     */
    function getCachedPage($template_file = null, $instance = null)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        $cache_file = $this->_cacheFilepath($template_file, $instance);
        // read cache
        return $this->read_file($cache_file);
    }

    /**
     * Return prepared file content
     *
     * @param string $cache_file
     * @return mixed rendered page, FALSE on error
     * @access public
     */
    function read_file($cache_file)
    {
        if (is_readable($cache_file)) {
            if (!($tf=@fopen($cache_file,"r"))) {
                $this->setError('error reading cache file: ' . $cache_file);
                // error reading cache
                return false;
            } else {
                // read and return cache file contents
                $return = fread($tf, filesize($cache_file));
                $return = substr($return, (strlen($this->cache_die_phrase)));
                fclose($tf);
                return $return;
            }
        } else {
            // cache file does not exist. Return FALSE
            $this->setError('cache file is not readable: ' . $cache_file);
            return false;
        }
    }

    /**
     * Execute cahed page.
     * Used if noncacheble vars exists in template.
     *
     * @param string template filename
     * @param string instance name
     * @param mixed array of vars used for rendering
     * @param bool display or not rendered page
     * @return mixed rendered page, false on error
     * @access private
     */
    function _executeCachedPage($template_file = null, $instance, &$data, $display = false)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }
        $cache_file = $this->_cacheFilepath($template_file, $instance);
        // read cache
        if (is_readable($cache_file)) {
            return $this->_execute($cache_file, $data, $display);
        } else {
           // cache file does not exist. Return false
           $this->setError('cache file is not readable: ' . $cache_file);
           return false;
        }
    }

    /**
     * Delete page from cache
     *
     * @param string a full or relative path to template file.
     *               Not required if it was already set using
     *               method setTemplateFile()
     * @param string instance name (optional)
     * @access private
     * @return mixed false on error
     */
    function deleteCachedPage($template_file = null, $instance = null)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        // filename of cached page
        $cache_file = $this->_cacheFilepath($template_file, $instance);

        // delete file
        if (is_readable($cache_file)) {
            @unlink($cache_file);
        } else {
            // cache file does not exist. Return false
            $this->setError('cache file does not exist: ' . $cache_file);
            return false;
        }
    }

    /**
     * Delete page from cache
     *
     * @param string a full or relative path to template file.
     *               Not required if it was already set using
     *               method setTemplateFile()
     * @param string instance name (optional)
     * @access private
     * @return mixed false on error
     */
    function clear_cache($template_file = null, $instance = null)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        // filename of cached page
        $cache_file = $this->_cacheFilepath($template_file, $instance);

        // delete file
        if (is_file($cache_file)) {
            @unlink($cache_file);
        } else {
            // cache file does not exist. Return false
            $this->setError('cache file does not exist: ' . $cache_file);
            return false;
        }
    }

    /**
     * Save rendered content of page to cache
     *
     * @param string rendered page
     * @param string a full or relative path to template file. Not required if it was already set using method setTemplateFile()
     * @param string instance name (optional)
     * @access private
     * @return boolean true on success, FALSE on error
     */
    function saveCachedPage($rendered_page, $template_file, $instance)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        $cache_file = $this->_cacheFilepath($template_file, $instance);
        return $this->saveFile($rendered_page, $cache_file);
    }

    /**
     * Save data in file
     *
     * @param string rendered page
     * @param string a full or relative path to cache file.
     * @access private
     * @return boolean true on success, FALSE on error
     */
    function saveFile($rendered_page, $cache_file)
    {
        // delete old cache file
        if (is_file($cache_file)) {
            @unlink($cache_file);
        }

        // create new file
        if (!($tf=@fopen($cache_file,"w"))) {
            // error creating cache file
            $this->setError('error creating cache file: ' . $cache_file);
            return false;
        } else {
            fwrite($tf, $this->cache_die_phrase . $rendered_page);
            fclose($tf);
            return true;
        }
    }

    /**
     * Set template file to be used and load its contents.
     *
     * @param string full or relative path to template file
     * @access public
     * @return boolean true if file loaded suscessefully, false on error
     */
    function setTemplateFile($template_file)
    {
        // check for compiling  version
        if (!$this->_force_compile && is_readable($template_file)
                      && $this->_isCompiled($template_file)){
            $this->_template_file = $template_file;
            return true;
        }

        if (!$contents = $this->readTemplate($template_file)) {
            // error opening template file. set the error
            $this->setError('error opening template file: ' . $template_file);
            return false;
        } else {
            // file opened successefuly. load contents
            $this->_template_file = $template_file;
            return true;
        }
    }

    /**
     * Read template file contents
     *
     * @param string full or relative path to template file
     * @access private
     * @return mixed template contents, false on error
     */
    function readTemplate($template_file = '')
    {
        if (!($tf = @fopen($template_file, "r"))) {
            // error opening template file.
            $this->setError('error opening template file: ' . $template_file);
            return false;
        } else {
            // file opened successefuly. load contents
            $return = @fread($tf,filesize($template_file));
            fclose($tf);
            return $return;
        }
    }

    /**
     * return rendered page
     *
     * @param string full or relative path to template file (optional)
     * @param string level of caching (optional)
     * @return mixed rendered page on success, FALSE on error
     * @access public
     */
    function render($template_file = null, $instance = null)
    {
        return $this->_render($template_file, $instance, false);
    }

    /**
     * display rendered page.
     * Its better solution instead of
     * <code>
     * echo $tpl->render($template_file, $instance);
     * </code>
     * , especially if caching mechanism is not used
     *
     * @param string full or relative path to template file (optional)
     * @param string level of caching (optional)
     * @return bool FALSE on error, true otherwise
     * @access public
     */
    function display($template_file = null, $instance = null)
    {
        return $this->_render($template_file, $instance, true);
    }

    /**
     * Parse the template
     * Determine caching mechanisms and returns cached
     * version of the page or initiates template compile and render
     *
     * @param string full or relative path to template file
     * @param array data for parsing
     * @param bool display or return rendered page.
     * @return mixed rendered page or true(if $display = true) on success, FALSE on error
     * @access private
     */
    function _render($template_file = null, $instance = null, $display = false)
    {
        if (null == $template_file) {
            $template_file = $this->_template_file;
        }

        // shortcut
        $data =& $this->_data;

        if ($this->_caching && $this->isCached($template_file, $instance)) {
            // get rendered page from the cache and return it
            if (!$this->_use_noncachable && $display){
                $cache_file = $this->_cacheFilepath($template_file, $instance);
                require($cache_file);
                $this->_cache_used = true;
                return true;
            } elseif ($return = $this->getCachedPage($template_file, $instance)) {
                // process non-cachable items
                if ($this->_use_noncachable
                    && (strpos($return, '<?php echo $data') !== false)) {
                    $return = $this->_executeCachedPage($template_file, $instance, $data, $display);
                }
                $this->_cache_used = true;
                return $return;
            } else {
                // there was an error reading cache.
                // falling through to parsing
                // unable to read cache is not a critical error,
                // so we do not set an error state and do not stop
                // rendering
            }
        }

        // cache was not used. proceed with the parsing
        $rendered_page = false;
        if ($this->_force_compile || !$this->_isCompiled($template_file)){
            if (!$template_contents = $this->readTemplate($template_file)) {
                // CRITICAL ERROR!
                // there was an error setting template file.
                // return with an error (FALSE)
                return false;
            }

            // check for using non cachable vars
            $use_noncachable = $this->_caching && $this->_use_noncachable;

            // Compile template to php script
            $compiler = $this->getCompiler();

            if (!$compiled_content = $compiler->compile($template_contents, $use_noncachable)) {
                // there was an error compiling template
                // return with an error (FALSE)
                $this->setError('there was an error compiling template.');
                return false;
            }

            //And save compiled Version on server
            if (!$this->_saveCompiledTmpl($compiled_content, $template_file)){
                // there was an error saving compiled version
                // return with an error (FALSE)
                return false;
            }

            // clear unused vars
            unset($template_contents, $compiled_content, $use_noncachable);
        }

        // template parsed and rendered successefully
        if ($this->_caching) {
            $rendered_page = $this->_execute($template_file, $data);
            // on error
            if (!$rendered_page) {
                return false;
            }
            $this->saveCachedPage($rendered_page, $template_file, $instance);
            // process non-cachable items
            if ($this->_use_noncachable
                    && (strpos($rendered_page, '<?php echo $data') !== false)){
                $rendered_page = $this->_executeCachedPage($template_file, $instance, $data, $display);
            } else if ($display) {
                echo $rendered_page;
                return true;
            }
        } else {
            $rendered_page = $this->_execute($template_file, $data, $display);
        }

        return $rendered_page;
    }

    /**
     * Execute compiled Template
     *
     * @param string full relative path to template which was compiled
     * @param mixed data var container to replacing
     * @param bool display or return rendered page
     * @access private
     * @return mixed rendered templte
     */
    function _execute($template_file, &$data, $display = false)
    {
        $compl_file = $this->_compiledFilepath($template_file);

        if (!$display) {
            ob_start();
            // include to output compiled file
            include($compl_file);
            // get output to var
            $return = ob_get_contents();
            // clean output
            ob_end_clean();
            return $return;
        } else {
            include($compl_file);
            return true;
        }
    }

    /**
     * Shows if cache was used for the latest parsing operation.
     *
     * @access public
     * @return boolean true if cache was used, false if not
     */
    function cacheUsed()
    {
        return $this->_cache_used;
    }

    /**
     * Set or unset force compile mode
     * If force_compile is true - on each execute template must be recompiled
     *
     * @param boolean
     * @access public
     * @return null
     */
    function setForceCompile($compile)
    {
        $this->_force_compile = (bool) $compile;
    }

    /**
     * Set or unset force compile mode
     * If force_compile is true - on each execute template must be recompiled
     *
     * @param mixed $comp_content - compiled version of template
     * @param string $template_file - template filename which are compiled
     * @access protected
     */
    function _saveCompiledTmpl($comp_content, $template_file)
    {
        // full path to compiled template
        $compl_file = $this->_compiledFilepath($template_file);

        if (!is_writable(dirname($compl_file))) {
            $this->setError('Cache dir must be writeable. '
                     . 'Compiled template must be saved');
            return false;
        }

        // delete old cache file
        if (is_file($compl_file)) {
            @unlink($compl_file);
        }

        // create new file
        if (!($tf=@fopen($compl_file,"w"))) {
            // error creating compiled file
            $this->setError('error creating compiled file');
            return false;
        } else {
            // file created successefuly
            $comp_content = $this->compl_die_phrase . $comp_content;
            fwrite($tf, $comp_content);
            fclose($tf);
            return true;
        }
    }

    /**
     * Set cache expiry TTL (time to live) in minutes
     * Argument passed must be integer number of minutes. If caching is on, cached
     * version of page will be checked if it was created with TTL number of
     * minutes. If it was, no further parsing will occur and cached version will
     * be returned by parse method.
     * Note: if set to 0, all caching will be truned off!
     * @param integer minutes to keep cached page
     * @access public
     * @return boolean true if value is valid, FALSE if value is NOT valid
     */
    function setCacheTtl($cache_ttl)
    {
        if (!is_int($cache_ttl)) {
            // error - parameter must be an integer
            $this->setError('setCacheTtl: Cache time to live must be integer.');
            return false;

        } else if ($cache_ttl == 0){
            $this->setCaching(false);

        } else {
            $this->_cache_ttl = $cache_ttl;
        }

        return true;
    }

    /**
     * Set cache mode
     * enable if true, disable if false
     *
     * @param bool mode
     * @access public
     */
    function setCaching($caching)
    {
        $this->_caching = (bool) $caching;
    }

    /**
     * Turn on/off using of noncachable items in templates
     * If this option is turned on (default - off) by setting it to TRUE, template
     * parser will check for special noncachable tags <TPL_NOCACHE:xxxxxx> in
     * templates EVEN if template cache was used. It is disabled by default beause
     * it significantly slows down cache performance. It is still faster than not
     * use cache at all but significantly slower than without noncachable items -
     * even if there are no noncachable items in particular template.
     * @param boolean TRUE - use noncachable, FALSE - do not use them
     * @access public
     * @return boolean TRUE always
     */
    function setCacheUseNoncachable($use_noncachable)
    {
        if ($use_noncachable === true) {
            $use_noncachable = true;
        } else {
            $use_noncachable = false;
        }

        $this->_use_noncachable = $use_noncachable;
    }

    /**
     * Set and validate template cache path.
     *
     * @param string full or relative path to store templace cache files
     * @access public
     * @return boolean TRUE on success, FALSE on error
     */
    function setCachePath($cache_path)
    {
        if (!($td=@opendir($cache_path))) {
            // cannot open directory. set the error
            $this->setError('Wrong directory for save caching pages' . $cache_path);
            return false;
        } else {
            // directory opened successefuly. set cache path
            $this->_cache_path = $cache_path;
            closedir($td);
            return true;
        }
    }

    /**
     * Get template cache path.
     *
     * @access public
     * @return string
     */
    function getCachePath()
    {
        return $this->_cache_path;
    }

    /**
     * search in all globals variables on key $varname
     *
     * @param string $key to search
     * @return value of found variable
     */
    function getGlobals($varname)
    {
        if ($varname=='language' || $varname=='LANGUAGE' ) {
            global $lan;
            return $lan->lan();
        } else if ($varname=='PHP_SELF') {
            return $_SERVER["PHP_SELF"];
        }

        if (is_array($GLOBALS["parameters"]["template"]) &&
            isset($GLOBALS["parameters"]["template"][$varname]))
        {
            return $GLOBALS["parameters"]["template"][$varname];
        }

        if (is_array($_GET) && isset($_GET[$varname]) && !is_array($_GET[$varname])) {
            return $_GET[$varname];
        }

        if (is_array($_POST) && isset($_POST) && !is_array($_POST[$varname])) {
            return $_POST[$varname];
        }

        return '';
    }

    /**
     * Get translate to token, function executes by compiled template
     *
     * @access public
     * @return string - translate
     */
    function getTranslate($name)
    {
        global $txtf;
        if ($this->_language) {
            $text = '';
            $tags = array();
            if (strpos($name, "|")) {
                $ar = explode("|", $name);
                $t = new Text($this->_language, $ar[0]);
                $text = $t->display($ar[1]);
            } else {
                $text = $txtf->display($name);
            }
        } else {
            // language object $lng has not been instantiated. issue warning
            $text = $this->reportWarningMessage('Language object has not been '
                        .'instantiated. Unable to process  unused tags.')
                        . $text;
        }
        return $text;
    }




    /**
     * Set last error
     *
     * @param string $error
     * @param bool $fail_on_error if set to TRUE than E_USER_ERROR will be generated,
     *  otherwise error string will be saved in internal variable, default set in false
     * @access public
     */
    function setError($error, $fail_on_error = false)
    {
        if ($fail_on_error) {
            trigger_error($error, E_USER_ERROR);
        } else {
            $this->error = $error;
        }
    }

    /**
     * Check if there was an error
     *
     * @access public
     * @return boolean TRUE if there was an error, FALSE if not
     */
    function error()
    {
        if ($this->error != '') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get last error
     *
     * @access public
     * @return string
     */
    function getError()
    {
        if ($this->error()) {
            return $this->error;
        } else {
            return '';
        }
    }

    /**
     * Reset error as if there was no error
     *
     * @access public
     * @return boolean TRUE always
     */
    function resetError()
    {
        return $this->error = '';
    }

    /**
     * Get error message. Checks against set warning level. Used for template
     * parsing-time error reporting.
     *
     * @param string error text
     * @param string which part generated a warning (optional)
     * @access public
     * @return string warning message
     */
    function reportErrorMessage($error, $part='')
    {
        if ($this->warn_level != TPL_WARN_IGNORE) {
            return $this->formatErrorMessage($error, $part);
        }

        return '';
    }

    /**
     * Format error message. Errors are reported inside HTML comments
     *
     * @param string warning text
     * @param string which part generated an error (optional)
     * @access public
     * @return string warning message
     */
    function formatErrorMessage($error, $part='')
    {
        if (!empty($part)) {
            $part = " ($part)";
        }

        return "<!-- TEMPLATE ERROR$part: $error -->\n";
    }

    /**
     * Get warning message. Checks against set warning level. Used for template
     * parsing-time error reporting.
     *
     * @param string error text
     * @param string which part generated a warning (optional)
     * @access public
     * @return string warning message
     */
    function reportWarningMessage($warning, $part='')
    {
        if ($this->warn_level != TPL_WARN_IGNORE && $this->warn_level != TPL_WARN_CRITICAL) {
            return $this->formatWarningMessage($warning, $part);
        }

        return '';
    }

    /**
     * Format warning message. Errors are reported inside HTML comments
     *
     * @param string warning text
     * @param string which part generated an error (optional)
     * @access public
     * @return string warning message
     */
    function formatWarningMessage($warning, $part='')
    {
        if (!empty($part)) {
            $part = " ($part)";
        }

        return "<!-- TEMPLATE WARNING$part: $warning -->\n";
    }

    /**
     * Set warning level
     * Warning level indicates what and if warnings should be shown in final rendered page
     * - TPL_WARN_IGNORE - will show no warnings
     * - TPL_WARN_CRITICAL - will show only critical errors (could not create an object, object returned error, data is incorrect)
     * - TPL_WARN_ALL - will show all warnings including dataset present in template but no corresponding data was supplied
     * @param string warning level
     * @access public
     * @return boolean TRUE always
     */
    function setWarnLevel($warn_level)
    {
        $this->warn_level = $warn_level;
        return true;
    }

    /**
     * Set language code.
     *
     * @param string $language
     */
    function setLanguage($language)
    {
        if ($language) {
            $this->_language = $language;
        }
    }

    /**
     * Get language code.
     *
     * @param string $language
     */
    function getLanguage()
    {
        return $this->_language;
    }
}