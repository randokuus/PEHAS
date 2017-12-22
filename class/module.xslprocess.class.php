<?php

/**
 * XSLprocess module, parse XML and XSLT
 *
 * example use inside template
 * <code>
 *  <TPL_OBJECT:xslprocess>
 *      <TPL_OBJECT_PARAM:setFiles('somefile.xml','somefile.xsl')>
 *      <TPL_OBJECT_OUTPUT:show()>
 *  </TPL_OBJECT:xslprocess>
 * </code>
 *
 * @package modera_net
 * @version 1.8
 * @access public
 */

require_once(SITE_PATH . '/class/Xslt.php');

class xslprocess {

/**
 * @var integer active template set
 */
var $tmpl = false;
/**
 * @var boolean modera debug
 */
var $debug = false;
/**
 * @var string template file
 */
var $language = false;
/**
 * @var string site root url
 */
var $siteroot = false;
/**
 * @var array merged array with _GET and _POST data
 */
var $vars = array();
/**
 * @var integer database connection identifier
 */
var $dbc = false;
/**
 * @var boolean does this module provide additional parameters to admin page admin
 */
var $content_module = false;
/**
 * @var array additional parameters set at the page admin for this template
 */
var $module_param = array();
/**
 * @var integer active user id
 */
var $userid = false;
/**
 * @var string module cache level
 */
var $cachelevel = TPL_CACHE_NOTHING;
/**
 * @var integer cache expiry time in minutes
 */
var $cachetime = 1;//1440; //cache time in minutes
/**
 * @var boolean caching state
 */
var $caching = false;
/**
 * @var string caching die phrase, don't touch
 */
var $cache_die_phrase = "<?die('cachefile');?>";
/**
 * @var string source xml file
 */
var $source_xml = false;
/**
 * @var string source xsl file
 */
var $source_xsl = false;
/**
 * @var mixed temporary data holder
 */
var $temporary = false;
/**
 * @var boolean menu parse to do
 */
var $menu_parse = false;
/**
 * @var array arguments to XSLT process
 */
var $arguments = array();
/**
 * @var array parameters to XSLT process
 */
var $parameters = array();
/**
 * @var string module indentifier for cache filename
 */
var $tplfile = "general";
/**
 * @var boolean retrieve site menu with all visible pages ?
 */
var $all_visible = false;
/**
 * @var mixed declare what type of disabled pages we must show
 */
var $show_disabled = false;
var $translator;

    /**
     * Class constructor
    */

  function xslprocess () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    $this->debug = $GLOBALS["modera_debug"];
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->translator =& ModeraTranslator::instance($this->language, 'admin_general');

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

// ####################################################

    /**
     * Main process/show function XML->XSLT=HTML/XML/TEXT/whatever
     * @return string
    */

  function show () {

    $xslt =& Xslt::instance();

    $content = $this->vars['content'];
    $structure = $GLOBALS['pagedata']['mpath'];
    if ($structure) $structure .= '.';
    $structure .= $content;

    $nocache = $_REQUEST["nocache"];

    //if (!$this->source_xml || !$this->source_xsl) { trigger_error("Module xslprocess: no input files found", E_USER_ERROR); }

    //modified 03.08, siim, XML file does not need to be, we can use the argument instead
    if (!$this->source_xsl) { trigger_error("Module xslprocess: no input files found", E_USER_ERROR); }

    //$sq = new sql;

    $usecache = checkParameters();
    if ($this->caching == true) $usecache = true;

    if (false !== strpos($_SERVER['PHP_SELF'], '/admin/')) {
        $tmpl_dir = SITE_PATH . '/admin/tmpl/';
    } else {
        $tmpl_dir = SITE_PATH . '/' . $GLOBALS['directory']['tmpl'] . '/';
    }

    // check if XSL is passed as plain text in agruments
    if ('arg:/_xsl' == $this->source_xsl && $this->arguments['/_xsl']) {
        $xslt->set_xsl($this->arguments['/_xsl']);
    } else {
        if (file_exists($tmpl_dir . $this->source_xsl)) {
            $xslt->set_xsl_file($tmpl_dir . $this->source_xsl);
        } else {
            $xslt->set_xsl_file($this->source_xsl);
        }
    }

    // process XML parameter
    if ('sitemenumodera' == $this->source_xml) {
        $file = 'sitemenu';
        $usecache = true;
        $xslt->set_xml($this->siteMenu());
    } else {
        $file = $this->tplfile;

        if ($this->arguments['/_xml']) {
            $xslt->set_xml($this->arguments['/_xml']);
        } else {
            // we have to check if supplied xml path argument is a full
            // path pr relative to tmpl_dir one
            if (file_exists($tmpl_dir . $this->source_xml)) {
               $xslt->set_xml_file($tmpl_dir . $this->source_xml);
            } else {
               $xslt->set_xml_file($this->source_xml);
            }
        }
    }

    //apparently our XML is pretty much empty
    if ($this->arguments['/_xml'] == "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n") {
        return "";
    }

    // set parameters passed to XSLT processor
    $xslt->set_parameter('structure', $structure);
    $xslt->set_parameter('content', $content);
    $xslt->set_parameter('language', $this->language);
    $xslt->set_parameter('publishing_token', $this->translator->tr('site_map_publishing'));


    if (sizeof($this->parameters) > 0) {
        foreach ($this->parameters as $_key => $_val) {
            $xslt->set_parameter($_key, $_val);
        }
        $this->parameters = array_merge($xslt->get_parameters(), $this->parameters);
    }
    else {
        $this->parameters = $xslt->get_parameters();
    }

    $instance = $_SERVER["PHP_SELF"]."?language=".$this->language."&module=xslprocess&parameters=".urlencode(serialize($this->parameters))."&xml=".urlencode($this->source_xml)."&xsl=".urlencode($this->source_xsl);

    // delete the cache file if parameter found
    if ($nocache == 1 || $nocache == true || $this->menu_parse == true) {
        $this->deleteCachedPage($file, $instance);
    }

    if ($this->cachelevel === TPL_CACHE_ALL && $this->isCached($file, $instance) && $usecache == true) {
      // get rendered page from the cache and return it
      if ($return = $this->getCachedPage($file, $instance)) {
        //$GLOBALS["caching"][] = "xslprocess";
        //return "<!-- module xslprocess cached -->\n" . $return;
        return $return;
      }
      else {
        //SOMETHINGS WRONG
        return "";
      }
    }

    // ####
    // cache was not used. proceed with the parsing

    $rendered = $xslt->process();
    if (!$rendered) {
        triggerError(sprintf("Cannot process XSLT document : %s", $xslt->get_error()));
    }

    // template parsed and rendered successefully
    if ($this->cachelevel == TPL_CACHE_ALL && $usecache == true) {
      $this->saveCachedPage($rendered, $file, $instance);
    }

    // ####

    return $rendered;

  }

    // ########################################

    /**
     * Process site menu xml, calling show() function
     *
     * @param string $xsl xsl file name
     * @param mixed $show_disabled list width types of disabled pages for show
     * @return string call show() function
     */

    function menu($xsl, $show_disabled = false) {
        $this->show_disabled = $show_disabled;
        if (ereg("\.\.", $xsl)) trigger_error("Module xslprocess: XSL file path is invalid !", E_USER_ERROR);
        if (ereg("^".$GLOBALS["directory"]["tmpl"], $xsl)) {
            $xsl = substr($xsl, strlen($GLOBALS["directory"]["tmpl"])+1);
        }
        //$this->source_xsl = SITE_PATH . "/" . $xsl;
        $this->source_xsl = $xsl;
        //echo "<h1>$this->source_xsl</h1>";
        $this->source_xml = "sitemenumodera";
        return $this->show();

    }

    // ########################################

    /**
     * Generate/load from cache site menu XML
     *
     * @return string xml format
     */
    function siteMenu() {

        $nocache   = $_REQUEST["nocache"];

        //$usecache = checkParameters();
        $usecache = true;
        $file = "menuxml";
        $instance = $_SERVER["PHP_SELF"]."?language=".$this->language."&module=xslprocess&function=menu";

        // delete the cache file if parameter found
        if ($nocache == 1 || $nocache == true) {
            $this->deleteCachedPage($file, $instance);
        }

        if ($this->cachelevel === TPL_CACHE_ALL
            && $this->isCached($file, $instance) && $usecache == true && !$this->display_not_published_pages)
        {
            // get rendered page from the cache and return it
            if ($return = $this->getCachedPage($file, $instance)) {
                return $return;
            }
            else {
                //SOMETHINGS WRONG
                return "";
            }
        }

        // ####
        // cache was not used. proceed with the parsing
        $this->menu_parse = true;

        /**
         * Load ContentStructure class. Move to proper place.
         * @since 2007-03-09
         */
        require_once(SITE_PATH . '/class/ContentStructure.php');
        $content_structure = new ContentStructure($GLOBALS['database']);
        $rendered = $content_structure->getAsXml($this->language, $this->all_visible, $this->show_disabled);

        // template parsed and rendered successefully
        if ($this->cachelevel == TPL_CACHE_ALL && $usecache == true) {
            $this->saveCachedPage($rendered, $file, $instance);
        }
        return $rendered;
    }



    // ########################################

    /**
     * Set files to use for the processing function
     * @param string xml file location, can also be an url to xml file
     * @param string xsl file location
    */

      function setFiles ($xml, $xsl) {
        if (ereg("\.\.", $xml) || ereg("\.\.", $xsl)) trigger_error("Module xslprocess: XML or XSL file path is invalid !", E_USER_ERROR);

        if (ereg("^".$GLOBALS["directory"]["tmpl"], $xsl)) {
            $xsl = substr($xsl, strlen($GLOBALS["directory"]["tmpl"])+1);
        }

        // source XML is an URL
        if (ereg("^http", $xml)) {
            $handle = fopen($xml, "rb");
            $content = '';
            while (!feof($handle)) {
              $content .= fread($handle, 8192);
            }
            fclose($handle);

            $xml = "";
            if ($content == false || strtolower(substr($content,0,5)) != "<?xml") trigger_error("Module xslprocess: External URL for XML data inaccessible or not found !", E_USER_ERROR);
            $this->arguments['/_xml'] = $content;
        }
        else {
            if (ereg("^".$GLOBALS["directory"]["tmpl"], $xml)) {
                $xml = substr($xml, strlen($GLOBALS["directory"]["tmpl"])+1);
            }
        }

        $this->source_xml = $xml;
        $this->source_xsl = $xsl;

        //$this->source_xml = SITE_PATH . "/" . $xml;
        //$this->source_xsl = SITE_PATH . "/" . $xsl;
      }

// ########################################

    /**
     * Set process caching use
     * @param string true for TPL_CACHE_ALL
     * @param integer cache expiry in minutes
    */

      function setCache ($cache, $time) {
        if ($cache == "true" || $cache == "yes") {
            $this->cachelevel = TPL_CACHE_ALL;
        }
        else {
            $this->cachelevel = TPL_CACHE_NOTHING;
        }
        if ($time > 0 && $time < 999999999) {
            $this->cachetime = $time;
        }
      }

// ####################################################

    /**
     * Retrieve data from cache
     * @param string filename part
     * @param string unique instance to separate cache files
     * @access private
     * @return string cache content
    */

function getCachedPage ($file='', $instance='') {

  $cache_file = SITE_PATH . "/cache/"
                . 'xslp_'.$file
                . md5("$instance")
                . '.php';

  // read cache
  if (file_exists($cache_file)) {
    if (!($tf=@fopen($cache_file,"r"))) {
      // error reading cache
      return false;
    }
    else {
      // read and return cache file contents
      $return = fread($tf,filesize($cache_file));
      $return = trim(str_replace($this->cache_die_phrase, '', $return));
      fclose($tf);
      return $return;
    }
  }
  else {
    // cache file does not exist. Return FALSE
    return false;
  }
}

// ####################################################

    /**
     * Delete data from cache
     * @param string filename part
     * @param string unique instance to separate cache files
     * @access private
    */

  function deleteCachedPage ($file='', $instance='') {
      switch (true){
          case ($instance === false) && !empty($file):
              $name = 'xslp_'.$file;
              $tmp_size = strlen($name);
              $path = SITE_PATH . "/cache";
              if ($dir = @opendir($path)){
                  while (($file = readdir($dir)) !== false) {
                      $tmp_path = $path . '/' . $file;
                      if (!is_dir($tmp_path) && $name == substr($file,0, $tmp_size) && is_writable($tmp_path)){
                          unlink($tmp_path);
                      }
                  }
                  closedir($dir);
              }
              break;
          case ($file === false) && !empty($instance):
              $name = 'xslp_';
              $instance = md5($instance) . '.php';
              $tmp_size = strlen($instance) * -1;
              $path = SITE_PATH . "/cache";
              if ($dir = @opendir($path)){
                  while (($file = readdir($dir)) !== false) {
                      $tmp_path = $path . '/' . $file;
                      if (!is_dir($tmp_path) && $name == substr($file,0, 5)
                              && $instance == substr($file, $tmp_size) && is_writable($tmp_path)){
                          unlink($tmp_path);
                      }
                  }
                  closedir($dir);
              }
              break;
          default:
              $cache_file = SITE_PATH . "/cache/"
                        . 'xslp_'.$file
                        . md5("$instance")
                        . '.php';
               // read cache
               if (file_exists($cache_file)) {
                   unlink($cache_file);
               } else {
                   // cache file does not exist. Return FALSE
                   return false;
               }
      }
  }


// ####################################################
    /**
     * Save data to cache
     * @param string data to save
     * @param string filename part
     * @param string unique instance to separate cache files
     * @access private
     * @return boolean
    */

  function saveCachedPage ($rendered_page, $file='', $instance='') {

  $cache_file = SITE_PATH . "/cache/"
                . 'xslp_'.$file
                . md5("$instance")
                . '.php';

    // delete old cache file
    if (file_exists($cache_file))
      unlink($cache_file);

    // create new file
    if (!($tf=@fopen($cache_file,"w"))) {
      // error creating cache file
      return false;
    }
    else {
      // file created successefuly
      @fwrite($tf, $this->cache_die_phrase . $rendered_page);
      @fclose($tf);
      return true;
    }
  }

// ####################################################

    /**
     * Check is the data/page cached
     * @param string filename part
     * @param string unique instance to separate cache files
     * @access private
     * @return boolean
    */

  function isCached ($file='', $instance='') {
    // return FALSE if caching is turned off without any further work
    if ($this->cachelevel === TPL_CACHE_NOTHING)
      return false;

  $cache_file = SITE_PATH . "/cache/"
                . 'xslp_'.$file
                . md5("$instance")
                . '.php';

    // read cache
    if (file_exists($cache_file)) {
      $cache_create_time = filemtime($cache_file);
      $cache_age = (time()-$cache_create_time) / 60;
      if ($cache_age <= $this->cachetime) {
        // valid cache found
        return true;
      }
      else {
        // cache is outdated
        return false;
      }
    }
    else {
      // file does not exist. So doesnt cache.
      return false;
    }
  }

// ########################################

    /**
     * Get parameters from page data
     * @access private
     */

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Provide addtional parameters to page admin
     * @access private
     */

    function moduleOptions() {
        $sq = new sql;
        $list = array();
        $list[0] = "---";
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

}