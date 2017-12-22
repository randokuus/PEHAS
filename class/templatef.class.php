<?php
/**
 * @version $Revision: 2346 $
 */

require_once SITE_PATH . '/class/ModeraTemplate.php';
require_once SITE_PATH . "/class/JsonEncoder.php";
require_once SITE_PATH . "/class/text.class.php";

/**
 * Template proxy class
 *
 * @author Stanislav Chichkan <stas.chichkan@modera.net>
 */
class template
{

  /**
   * Template object instance
   *
   * @var mixed object instance
   * @access protected
   */
  var $_template;


  /**
   * Instance is used to make cache files unique
   * for a given template,
   * set to REQUEST_URI in constructor
   *
   * @var string
   * @access public
   */
  var $instance;

  /**
   * Use instance to separate
   * one template to multiple caches
   *
   * @var boolean
   * @access public
   */
  var $cache_use_instance = true;

  /**
   * Diagnostics - start time
   *
   * @var array
   * @access public
   */
  var $start_time = array();

  /**
   * Diagnostics - stop time
   *
   * @var array diagnostics - stop time
   * @access public
   */
  var $stop_time = array();

  /**
   * Diagnostics - spent time
   *
   * @var array diagnostics - spent time
   * @access public
   */
  var $time_took = array();

  /**
   * Cache level
   * <ul>
   * <li>TPL_CACHE_ALL</li>
   * <li>TPL_CACHE_NOTHING</li>
   * </ul>
   *
   * @var string
   * @access public
   */
  var $cache_level = TPL_CACHE_NOTHING;

  /**
   * Error handling warning level, TPL_WARN_CRITICAL by default
   *
   * @var string
   * @access public
   */
  var $warn_level = TPL_WARN_CRITICAL;


  /**
   * Process inputs
   *
   * @var boolean
   * @access public
   */
  var $process_inputs = true;


  /**
   * Template object constructor
   *
   * @access public
   * @return mixed errors
   */
  function template()
  {

      $this->_template = new ModeraTemplate();
      $this->setInstance($_SERVER["REQUEST_URI"]);

      if (isset($GLOBALS['lan']) && is_object($GLOBALS['lan'])) {
          $this->setLanguage($GLOBALS['lan']->lan());
      } elseif ($GLOBALS['language']) {
          $this->setLanguage($GLOBALS['language']);
      } else {
          $this->setLanguage('EN');
      }

      if (!empty($GLOBALS["conf_tpl_cache_use_instance"])) {
          $this->setCacheUseInstance($GLOBALS["conf_tpl_cache_use_instance"]);
      }

      return $this->error();
  }


  /**
   * Set page instance for cache operations.
   * Instance can be anything ("&variable1=10" or "my instance" or "form" etc.)
   *
   * @param string instance name
   * @access public
   * @return null
   */
  function setInstance($instance)
  {
      $this->instance = $instance;
      $this->cache_use_instance = true;
  }


  /**
   * Shows if cache was used for the latest parsing operation.
   *
   * @access public
   * @return boolean true if cache was used, false if not
   */
  function cacheUsed()
  {
      return $this->_template->cacheUsed();
  }

  /**
   * Parse the template
   * Determine caching mechanisms and returns cached
   * version of the page or initiates template compile and render
   *
   * @param string full or relative path to template file
   * @param array data for parsing
   * @param string level of caching (optional)
   * @return mixed rendered page on success, FALSE on error
   */
  function parse($template_file_in = '', $data_in = ''
                 , $cache_level_in = '', $instance_in = '')
  {
      global $usecache, $nocache;

      // start the microtimer to calculate parsing performance
      $this->startTimer('parse');

      if (empty($template_file_in)) {
          $template_file = $this->_template->getTemplateFile();
      } else {
          $template_file = $template_file_in;
      }

      if (empty($instance_in)) {
          $instance = $this->instance;
      } else {
          $instance = $instance_in;
      }


      if (empty($data_in)) {
          $data = $this->_template->getAssigned();
      } else {
          $data = $data_in;
      }

      if (empty($cache_level_in)) {
          $cache_level = $this->cache_level;
      } else {
          $cache_level = $cache_level_in;
      }

      // global variable :( got from old version
      if (!$usecache) {
          $cache_level = TPL_CACHE_NOTHING;
      }

      $this->setCacheLevel($cache_level);

      // delete the cache file if parameter found
      if ($nocache == 1 || $nocache == true) {
          $this->_template->deleteCachedPage($template_file, $instance);
      }
      $res = $this->_template->render($template_file, $instance);
      $res = $this->populateLangCMS($res);
      // stop parsing microtimer
      $this->stopTimer('parse');

      return $res;

  }

  /**
   * Return rendered page from cache
   * cache file is constructed tpl_ + $this->tplfile + md5("$template_file|$instance") + .php
   *
   * @param string a full or relative path to template file.
   *               Not required if it was already set using
   *               method setTemplateFile()
   * @param string instance name (optional)
   * @return mixed rendered page, false on error
   * @access public
   */
  function getCachedPage($template_file='', $instance='')
  {
      if (empty($instance) && $this->cache_use_instance) {
          $instance = $this->instance;
      }
      return $this->_template->getCachedPage ($template_file, $instance);
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
  function deleteCachedPage($template_file='', $instance='')
  {
       return $this->_template->deleteCachedPage($template_file, $instance);
  }

  /**
   * Save rendered content of page to cache
   *
   * @param string rendered page
   * @param string a full or relative path to template file. Not required if it was already set using method setTemplateFile()
   * @param string instance name (optional)
   * @access private
   * @return boolean true on success, false on error
   */
  function saveCachedPage($rendered_page, $template_file='', $instance='')
  {
      if (empty($instance) && $this->cache_use_instance) {
          $instance = $this->instance;
      }
      return $this->_template->saveCachedPage($rendered_page, $template_file, $instance);
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
      return $this->_template->setTemplateFile($template_file);
  }


  /**
   * Read template file contents
   *
   * @param string full or relative path to template file
   * @access private
   * @return mixed template contents, false on error
   */
  function readTemplateFile($template_file = '')
  {
       return $this->_template->readTemplate($template_file);
  }

  /**
   * Add Data to be used in template.
   *
   * @param array an associative array with data to be used in parsing
   * @access public
   * @return boolean true if data loaded suscessefully, false on error
   */
  function addData($data)
  {
      return $this->_template->assignAll($data);
  }

  /**
   * Add single variable of data to a current row
   * Parameter $key should hold a string representing the path to the data. 'FEATURES.ITEMS.PRICE'
   * Parameter $data should hold an associative array or string value.
   * If the $key is a block item, then $data should hold an associative array of all the subelements.
   *
   * @param string key to add datarow to
   * @param mixed string or an associative array with data to be used in parsing
   * @access public
   * @return boolean true if data loaded successfully, false on error
   */
  function addDataItem($key, $data)
  {
      $_data = $this->_template->getAssigned();

      $path_arr = &$_data;
      $last_key = 0;
      $path = explode('.', $key);
      // build an appropriate array key for data assignment
      for ($i=0; $i<sizeof($path); $i++) {
          $keytmp = $path[$i];
          if (!is_numeric($keytmp) && !is_numeric($last_key)) {
              // get the specific part of the array
              $data_arr = $path_arr;

              if (is_array($data_arr)) {
                  end($data_arr);
                  $line = key($data_arr);
                  if (isset($data_arr[$line][$keytmp]) && !isset($path[$i+1])) {
                      $line++;
                  }
                  $path_arr = &$path_arr[$line];
              } else {
                  $path_arr = &$path_arr[0];
              }
          }
          // check for special tags in key
          // [N] - start a new row
          // [C] - use current last row
          if ($keytmp == '[N]' || $keytmp == '[C]') {
              $newline = 0;
              // get the specific part of the array
              $data_arr = &$path_arr;

              if (is_array($data_arr)) {
                  if ($keytmp == '[N]') {
                      // new line. increment number
                      $newline = 1;
                  }
                  // find out the key of the last element in the array
                  end($data_arr);
                  $keytmp = key($data_arr) + $newline;
              } else {
                  // the key is not an array. so it's empty. assign zero
                  $keytmp = 0;
              }
          }
          $last_key = $keytmp;
          $path_arr = &$path_arr[strtoupper($keytmp)];
      }
      // assign data
      $path_arr = $data;

      return $this->_template->assignAll($_data);
  }


  /**
   * Reset data array
   *
   * @access public
   * @return boolean true always
   */
  function resetData()
  {
      $this->_template->clear_all_assign();
      return true;
  }


  /**
   * Set cache expiry TTL (time to live) in minutes
   * Argument passed must be integer number of minutes. If caching is on, cached
   * version of page will be checked if it was created with TTL number of
   * minutes. If it was, no further parsing will occur and cached version will
   * be returned by parse method.
   * Note: if set to 0, all caching will be truned off!
   *
   * @param integer minutes to keep cached page
   * @access public
   * @return boolean true if value is valid, false if value is NOT valid
   */
  function setCacheTtl($cache_ttl)
  {
      return $this->_template->setCacheTtl($cache_ttl);
  }


  /**
   * Set cache level
   * Sets cache level for a template. TPL_CACHE_ALL,TPL_CACHE_OBJECTS, TPL_CACHE_NOTHING
   *
   * @param string cache level (TPL_CACHE_ALL, etc)
   * @access public
   * @return boolean true always
   */
  function setCacheLevel($cache_level)
  {
      $this->cache_level = $cache_level;
      if ($cache_level === TPL_CACHE_ALL) {
          $this->_template->setCaching(true);
      } else {
          $this->_template->setCaching(false);
      }
      return true;
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
      $this->_template->setForceCompile($compile);
  }


  /**
   * Turn on/off using of noncachable items in templates
   * If this option is turned on (default - off) by setting it to TRUE, template
   * parser will check for special noncachable tags <TPL_NOCACHE:xxxxxx> in
   * templates EVEN if template cache was used. It is disabled by default beause
   * it significantly slows down cache performance. It is still faster than not
   * use cache at all but significantly slower than without noncachable items -
   * even if there are no noncachable items in particular template.
   *
   * @param boolean TRUE - use noncachable, FALSE - do not use them
   * @access public
   * @return boolean true always
   */
  function setCacheUseNoncachable($use_noncachable)
  {
      $this->_template->setCacheUseNoncachable($use_noncachable);
      return true;
  }



  /**
   * Set and validate template cache path.
   *
   * @param string full or relative path to store templace cache files
   * @access public
   * @return boolean true on success, false on error
   */
  function setCachePath($cache_path)
  {
      return $this->_template->setCachePath($cache_path);
  }



  /**
   * Set language code.
   *
   * @param string $language
   */
  function setLanguage($language)
  {

      $this->_template->setLanguage($language);

  }

  /**
   * Set if to use language class for special template items
   *
   * @param boolean
   * @access public
   * @return boolean TRUE always
   */
  function setUseLanguageClass($use_language_class)
  {
      return $this->_template->setUseLanguageClass($use_language_class);
  }

  /**
   * test if page is cached
   * - faceplate, for old versions of template class
   *
   * @param string $template_file
   * @param string $instance
   * @access public
   * @return boolean
   */
  function isCached($template_file = '', $instance = '')
  {
      if (empty($instance) && $this->cache_use_instance)
          $instance = $this->instance;

      return $this->_template->isCached($template_file, $instance);
  }



  /**
   * Turn on/off using of instance modifier for caching operations.
   * If instance modifier is turned on (TRUE), caching will be performed for
   * this particular instance. Instance can be explicitly set using setInstance() method. The name/content of an instance can be anything you like
   * @param boolean TRUE - use instance, FALSE - do not use them
   * @access public
   * @return boolean TRUE always
   */
  function setCacheUseInstance($use_instance)
  {
      if ($use_instance === true) {
          $use_instance = true;
      } else {
          $use_instance = false;
      }
      $this->cache_use_instance = $use_instance;
      return true;
  }

  /**
   * Start internal microtimer
   *
   * @param string named timer (optional)
   * @access private
   * @return boolean always true
   */
  function startTimer($timer = 'main')
  {
      $time = gettimeofday();
      $start_time = $time['sec'] . '.' . $time['usec'];
      $this->start_time[$timer] = $start_time;
      return true;
  }


  /**
   * Stop internal microtimer and calculate the total microtime
   *
   * @param string named timer (optional)
   * @access private
   * @return boolean always true
   */
  function stopTimer($timer = 'main')
  {
      $time = gettimeofday();
      $stop_time = $time['sec'] . '.' . $time['usec'];
      $this->stop_time[$timer] = $stop_time;
      $this->time_took[$timer] =   $this->stop_time[$timer]
                                   - $this->start_time[$timer];
      return true;
  }


  /**
   * Show time in microseconds for the particular timer
   *
   * @param string named timer (optional)
   * @access public
   * @return string time of called timer
   */
  function showTimer($timer = 'main')
  {
      return $this->time_took[$timer];
  }





  /**
   * Set last error
   *
   * @param string $error
   * @param bool $fail_on_error if set to TRUE than E_USER_ERROR will be generated,
   *  otherwise error string will be saved in internal variable, default set in false
   * @access public
   */
  function setError ($error)
  {
      return $this->_template->setError($error);
  }


  /**
   * Check if there was an error
   *
   * @access public
   * @return boolean TRUE if there was an error, FALSE if not
   */
  function error()
  {
      return $this->_template->error();
  }


  /**
   * Get last error
   *
   * @access public
   * @return string
   */
  function getError()
  {
      return $this->_template->getError();
  }


  /**
   * Reset error as if there was no error
   *
   * @access public
   * @return boolean TRUE always
   */
  function resetError()
  {
      return $this->_template->resetError();
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
  function reportErrorMessage($error, $part = '')
  {
      return $this->_template->reportErrorMessage($error, $part);
  }


  /**
   * Format error message. Errors are reported inside HTML comments
   *
   * @param string warning text
   * @param string which part generated an error (optional)
   * @access public
   * @return string warning message
   */
  function formatErrorMessage($error, $part = '')
  {
      return $this->_template->formatErrorMessage($error, $part);
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
  function reportWarningMessage($warning, $part = '')
  {
      return $this->_template->reportWarningMessage($warning, $part);
  }


  /**
   * Format warning message. Errors are reported inside HTML comments
   *
   * @param string warning text
   * @param string which part generated an error (optional)
   * @access public
   * @return string warning message
   */
  function formatWarningMessage($warning, $part = '')
  {
      return $this->_template->formatWarningMessage ($warning, $part);
  }



  /**
   * Set warning level
   * Warning level indicates what and if warnings should be shown in final rendered page
   * - TPL_WARN_IGNORE - will show no warnings
   * - TPL_WARN_CRITICAL - will show only critical errors (could not create an object, object returned error, data is incorrect)
   * - TPL_WARN_ALL - will show all warnings including dataset present in template but no corresponding data was supplied
   * @param string warning level
   * @access public
   */
  function setWarnLevel($warn_level)
  {
      return $this->_template->setWarnLevel($warn_level);
  }


  /**
   *
   * This functions declared in old templatef.class
   *
   */


  function setCacheType(){}

  function setMaxIncludeLevels(){}


  /**
   * Check if there is valid cached version of the object available
   *
   * @param string object name
   * @param string object instance
   * @access private
   * @return boolean TRUE if valid cache exists, FALSE if there is no cache or it has expired
   */
  function isObjectCached($object_name, $object_instance)
  {
      // return FALSE if caching is turned off without any further work
      if ($this->cache_level === TPL_CACHE_NOTHING) {
          return FALSE;
      }
      $cache_file = $this->_template->getCachePath()
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
      return $this->_template->is_valid_cache($cache_file);
  }

  /**
   * Return rendered object from cache
   *
   * @param string name of the object
   * @param string object instance set in template
   * @access private
   * @return mixed rendered object, FALSE on error
   */
  function getCachedObject($object_name, $object_instance)
  {
      $cache_file = $this->_template->getCachePath()
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
      return $this->_template->read_file($cache_file);
  }



  /**
   * Save rendered object to cache
   *
   * @param string content of the processed object
   * @param string name of the object
   * @param string object instance set in template
   * @access private
   * @return boolean true on success, FALSE on error
   */
  function saveCachedObject($object_content, $object_name, $object_instance)
  {
      $cache_file = $this->_template->getCachePath()
                  . 'obj_'
                  . md5("$object_name|$object_instance")
                  . '.php';
      $this->_template->saveFile($object_content, $cache_file);
  }

  /**
   * Set cache database.
   *
   * @param string cache database
   * @access public
   * @return boolean TRUE always
   */
  function setCacheDatabase($cache_database)
  {
      $this->cache_database = $cache_database;
      return TRUE;
  }

  /**
   * Set cache table.
   *
   * @param string cache table
   * @access public
   * @return boolean TRUE always
   */
  function setCacheTable($cache_table)
  {
      $this->cache_table = $cache_table;
      return TRUE;
  }


  /**
   * Set database server
   *
   * @param string database server
   * @access public
   * @return boolean TRUE always
   */
  function setDbServer($db_server)
  {
      $this->db_server = $db_server;
      return TRUE;
  }



  /**
   * Set database user
   *
   * @param string database user
   * @access public
   * @return boolean TRUE always
   */
  function setDbUser($db_user)
  {
      $this->db_user = $db_user;
      return TRUE;
  }



  /**
   * Set database password
   *
   * @param string database password
   * @access public
   * @return boolean TRUE always
   */
  function setDbPassword($db_password)
  {
      $this->db_password = $db_password;
      return TRUE;
  }


  /**
   * Get the message of the last error. NOT USED
   *
   * @access private
   * @return string error message, NULL if there was no error
   */
  function getErrorMessage()
  {
      if ($this->error()) {
          // --- NOT IMPLEMENTED YET ---
          return $this->error;
      } else {
          return NULL;
      }
  }



  /**
   * Log a hit to a page cache. NOT USED
   *
   * @param string a full or relative path to template file. Not required if it was already set using setTemplateFile()
   * @param string page instance (optional)
   * @access private
   * @return boolean TRUE on success, FALSE on error
   */
  function logPageCacheHit($template_file='', $instance='')
  {

      // --- NOT IMPLEMENTED YET ---
      return TRUE;
  }



  /**
   * Log a hit to a page cache. NOT USED
   *
   * @param string object name
   * @param string object instance (optional)
   * @access private
   * @return boolean TRUE on success, FALSE on error
   */
  function logObjectCacheHit($object_name, $object_instance)
  {
      // --- NOT IMPLEMENTED YET ---
      return TRUE;
  }



  /**
   * Populate parsed template with data
   *
   * @param string block to be parsed
   * @param array data for block pupulation
   * @access private
   * @return mixed rendered block, FALSE on error
   */
  function populateBlock($block, $data, $prefix='')
  {

      if (is_array($data)) {
          while (list($key, $val) = each($data)) {
              // replace global variables
              if (empty($prefix)) {
                  if (!is_array($val)) {
                      $block = @preg_replace("/<_TPL:$key>/is", $val, $block);
                  }
              }

              // added by siim
              // process global special template tags
              //$this->processGlobals($block);

              // replace normal variables and blocks
              if (is_array($val)) {
                  // block
                  $regex  = "/<TPL_SUB:$prefix$key>(.*?)<\/TPL_SUB:$prefix$key>/is";
                  if (preg_match ($regex, $block, $arr)) {
                      $block_part = $arr[1];
                      $block_hash = '';
                      while (list($key2, $val2) = each($val)) {
                          $block_hash .= $this->populateBlock ($block_part, $val2,
                                                       $prefix.$key.'.');
                      }
                      $block_hash = str_replace('$', '__dollar__', $block_hash);
                      $block = preg_replace($regex, $block_hash, $block);
                      $block = str_replace('__dollar__', '$', $block);
                  } else {
                      // no corresponding item found in template for this value
                      // maybe set warning? (to be decided)
                  }
              } else {
                  // single value
                  $block = str_replace("<TPL:$prefix$key>", $val, $block);
              }
         }
    }
    return $block;
  }

  /**
   * Process object.
   * Instantiates an object, executes its paramaters, returns output.
   *
   * @param string name of the object to instantiate
   * @param array array of methods with parameters to be passed to object
   * @param string name of the method which generates the output
   * @access private
   * @return mixed output of the object, FALSE on error
   */
  function procObject($object_name, $object_params, $object_output)
  {
      $return = '';
      if (empty($object_output)) {
          // No <TPL_OBJECT_OUTPUT:xxx> parameter specified
          $return =   "No <TPL_OBJECT_OUTPUT:xxx> parameter "
                . " specified for object [$object_name]!";
          return $this->reportErrorMessage($return);
      }

      if (!class_exists($object_name)) {
         // no such class has been defined
         $return = "Unable to instantiate object [$object_name]!";
         return $this->reportErrorMessage($return);
      }

      $obj = new $object_name;
      for ($i=0; $i < sizeof($object_params); $i++) {
          $method = preg_replace('/\(.*\)/i', '', $object_params[$i]);
          if (@method_exists($obj, $method)) {
              $param = '$obj->'.$object_params[$i].';';
              eval($param);
          } else {
              // method does not exist. Set a warning
              $warn = "Method [$method] does not exist in class [$object_name]!";
              $return .= $this->reportErrorMessage($warn);
          }
      }

      $param = '$return .= $obj->'.$object_output.';';
      eval($param);
      return $return;
  }



  /**
   * Replace any special global template tags found also GET and POST ( <_TPL:language>, <_TPL:PHP_SELF> etc.)
   *
   * @access private
   * @param string the block of a template to be parsed
   */
  function processGlobals($block)
  {

      global $lan;

      $language = $lan->lan();

      // added by siim / replace language globally
      $block = str_replace("<_TPL:language>", $language, $block);
      $block = str_replace("<_TPL:LANGUAGE>", $language, $block);

      // global $_SERVER["PHP_SELF"];
      $block = preg_replace("/<_TPL:PHP_SELF>/is", $_SERVER["PHP_SELF"], $block);

      // global template specific information
      if (is_array($GLOBALS["parameters"]["template"]) && sizeof($GLOBALS["parameters"]["template"]) > 0) {
          while (list($kkey, $vval) = each($GLOBALS["parameters"]["template"])) {
             $block = @preg_replace("/<_TPL:".$kkey.">/is", "$vval", $block);
          }
          reset($GLOBALS["parameters"]["template"]);
      }

      if (is_array($this->get_variables)) {
          while (list($gey, $wal) = each($this->get_variables)) {
              if (!is_array($wal)) {
                  $block = @preg_replace("/<_TPL:".str_replace("/", "\/", $gey).">/is", $wal, $block);
              }
          }
          reset($this->get_variables);
      }
      if (is_array($this->post_variables)) {
          while (list($gey, $wal) = each($this->post_variables)) {
              if (!is_array($wal)) {
                  $block = @preg_replace("/<_TPL:".str_replace("/", "\/", $gey).">/is", $wal, $block);
              }
          }
          reset($this->post_variables);
      }

      return $block;
  }



  /**
   * Populates unused items using language class and modera language file
   *
   * @param string the block of a template to be parsed
   * @access private
   * @return mixed rendered block, FALSE on error
   */
  function populateLangCMS($block)
  {


      if (!$this->_template->getLanguage()) {
          // language object $lng has not been instantiated. issue warning
          $block = $this->reportWarningMessage('Language object has not been '
                                           .'instantiated. Unable to process '
                                           .' unused tags.') . $block;
          return $block;
      }

      $tags = array();
      preg_match_all ("/<[\\_]?TPL:.{0,20}TXT(JS)?_([^.>]*)>/is",
                      $block, $tags, PREG_SET_ORDER);

      for ($i=0; $i < sizeof($tags); $i++) {
          $tag = $tags[$i][0];
          $name = strtolower($tags[$i][2]);
          $text = $this->_template->getTranslate($name);

          if ($tags[$i][1] == 'JS') {
            $text = JsonEncoder::encode($text);
          }

          if ($text != '') {
                 $block = str_replace($tag, $text, $block);
          }
      }

      return $block;
  }


  /**
   * NOT USED
   * @access private
   */
  function setProcessInputs($process_inputs)
  {
      $this->process_inputs = $process_inputs;
      return TRUE;
  }


  /**
   * Set wether to allow global variables in templates
   * If set to TRUE global tags will be processed (<_TPL:XXX>). Global means
   * that they will bear the same value when used alone or in any block or
   * subblock
   * @param boolean TRUE/FALSE
   * @access public
   * @return boolean TRUE always
   */
  function setAllowGlobal($allow_global)
  {
      $this->allow_global = $allow_global;
      return TRUE;
  }

}