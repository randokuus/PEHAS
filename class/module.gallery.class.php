<?php

/**
 * Gallery module
 *
 * sample use in template:
 * <code>
 *  <TPL_OBJECT:gallery>
 *      <TPL_OBJECT_OUTPUT:show()>
 *  </TPL_OBJECT:gallery>
 * </code>
 *
 * @package modera_net
 * @version 1.2
 * @access public
 */

class gallery {


/**
 * @var integer active template set
 */
  var $tmpl = false;
/**
 * @var integer database connection identifier
 */
  var $dbc = false;
/**
 * @var boolean modera debug
 */
  var $debug = false;
/**
 * @var string active language
 */
  var $language = false;
/**
 * @var integer max results per page
 */
  var $maxresults = 12;
  //var $maxcolumn = 3; // NOT USED ANY MORE
/**
 * @var string template to use
 */
  var $template = false;
/**
 * @var boolean does this module provide additional parameters to admin page admin
 */
  var $content_module = true;
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
  var $cachetime = 60; //cache time in minutes
/**
 * @var boolean sort order descending
 */
  var $sort_order_desc = false; //sort order defaults to ascending

    /**
     * Class constructor
    */

  function gallery () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    $this->debug = $GLOBALS["modera_debug"];
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

// ######################################################

    /**
     * Main display function
    */

    function show() {

    $start = $this->vars["start"];
    $folder = $this->vars["folder"];
    $generate = $this->vars["generate"];

    if ($folder && substr($folder, -1) != "/") $folder .= "/";

    if ($this->checkAccess() == false) return "";

    if (!$start) { $start = 0; }

    //check hacks
    if (ereg("\.\.", $dir) || ereg("\.", $dir)) redirect("error.php?error=403");

    if (substr($this->module_param["gallery"],-1) != "/") $this->module_param["gallery"] = $this->module_param["gallery"] . "/";

    if ($generate != "") $this->generate(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->module_param["gallery"] . $folder);

    $sq = new sql;

    $txt = new Text($this->language, "module_gallery");

    // instantiate template class
    $tpl = new template;
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    if (!$this->template || $this->template == false) $this->template = "module_gallery_page.html";
    $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;

    $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=gallery&folder=".$this->module_param["gallery"]);
    $tpl->setTemplateFile($template);

    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "gallery";
        if ($GLOBALS["modera_debug"] == true) {
            return "<!-- module gallery cached -->\n" . $tpl->parse();
        }
        else {
            return $tpl->parse();
        }
    }

    $start_folder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->module_param["gallery"];

    // get subfolders
    $folders = $this->parse_folder("", array("" => $txt->display("folder0")));

    $tpl->addDataItem("TXT_PATH", $txt->display("path"));
    $tpl->addDataItem("TXT_FOLDER", $txt->display("folder"));

    $tpl->addDataItem("PATH", $GLOBALS["directory"]["upload"] . "/"  . $folder);

    $tpl->addDataItem("SELF", $_SERVER["PHP_SELF"]);
    $tpl->addDataItem("HIDDEN", $this->hidden($_SERVER["QUERY_STRING"], array("start","folder","generate")));

    // display folders
    while(list($key, $val) = each($folders)) {
        if ($key == $folder) $sel = "selected";
        else { $sel = ""; }
        $tpl->addDataItem("FOLDERS.VALUE", $key);
        $tpl->addDataItem("FOLDERS.NAME", $val);
        $tpl->addDataItem("FOLDERS.SEL", $sel);
    }
    //$check_folder = addslashes(substr($this->module_param["gallery"] . $folder , 0,-1));
    $check_folder = addslashes('/' . $this->module_param["gallery"] . $folder);
    // file descriptions
    $sql = "SELECT id, CONCAT(name, \".\", type) as file, text, folder FROM files WHERE folder = '" . $check_folder . "' ORDER BY file ASC";
    $sq->query($this->dbc, $sql);
    while ($data = $sq->nextrow()) {
        $desc[$data["file"]] = array($data["id"],$data["text"]);
    }
    //get files
    $opendir = $start_folder . addslashes($folder);
    if (@file_exists($start_folder . addslashes($folder)) && $dir = @opendir($opendir)) {
      // files
      $files = array();
      while (($file = readdir($dir)) !== false) {
          if (!is_dir($opendir . $file) && $file != "." && $file != ".." && $this->checkType($file) == true) {
                if ($filter != "") {
                    if (eregi(addslashes($filter),$file)) {
                      $files[] = $file;
                    }
                }
                else {
                  $files[] = $file;
                }
          }
      }
      if ($this->sort_order_desc) {
          rsort($files);
      } else {
          sort($files);
      }
      reset($files);
     }

    //display files
    if (sizeof($files) < $this->maxresults) $max = sizeof($files);
    else { $max = $start + $this->maxresults; }

    for ($c = $start; $c < $max; $c++) {

    if ($this->module_param["gallery"] == "/") $this->module_param["gallery"] = "";

        $file = $files[$c];

        if ($file != "") {
            $img = $GLOBALS["directory"]["upload"] . "/" . $this->module_param["gallery"] . $folder . $file;
            $url = "javascript:openPicture('" . $this->imgBig($this->module_param["gallery"] . $folder, $file) . "');";
            $url_plain = $this->imgBig($this->module_param["gallery"] . $folder, $file);
            if ($desc[eregi_replace("_thumb\.", ".", $file)][1] != "") { $title = $desc[eregi_replace("_thumb\.", ".", $file)][1]; }
            else { $title = eregi_replace("_thumb\.", ".", $file); }
            //$filedata = $this->imgDate($folder, $file) . " (" . $this->imgSize($folder, $file) . ")";
            $filedata = "(" . $this->imgSize($this->module_param["gallery"] . $folder, $file) . ")";
        //}
        //else {
        //  $img = "img/tyhi.gif"; $url = ""; $title = "&nbsp;"; $filedata = "";
        //}
        $tpl->addDataItem("ROW.IMG$i", $img);
        $tpl->addDataItem("ROW.URL$i", htmlspecialchars($url));
        $tpl->addDataItem("ROW.URL_PLAIN$i", htmlspecialchars($url_plain));
        $tpl->addDataItem("ROW.TITLE$i", $title);
        $tpl->addDataItem("ROW.FILEDATA$i", $filedata);

        }

        unset($file); unset($img); unset($title); unset($filedata);

    }


    // page listing
    $tpl->addDataItem("PAGES", resultPages($start, sizeof($files), processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("start")), $this->maxresults, $txt->display("prev"), $txt->display("next")));

    // ####
    return $tpl->parse();

    }

// ######################################################

    /**
     * Parse folder for files
     * @param string folder in the filesystem
     * @param array folder list data
     * @access private
     * @return array returns folder list data
    */

    function parse_folder($dir, $folder_list) {

        // let's check hacks
        if (strpos($dir, "..") != FALSE) redirect("error.php?error=403");

        $dh=@opendir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir);
            while ($file=@readdir($dh)){
                if ($file != "." && $file != ".." && is_dir(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $dir."/".$file)) {
                    if ($dir) $final = $dir."/".$file;
                    else { $final = $file; }
                    $folder_list[$final] = str_repeat("&nbsp;&nbsp;&nbsp;", substr_count($final, "/")) . $file;
                    $folder_list = $this->parse_folder($final, $folder_list);
                }
            }
        return $folder_list;
    }

// ######################################################

    /**
     * Check file type, only images allowed
     * @param string file location
     * @access private
     * @return boolean
    */

    function checkType($file) {
            if (eregi("_thumb\.", $file) && (strtolower(substr($file, -3)) == "gif" || strtolower(substr($file, -3)) == "jpg" || strtolower(substr($file, -3)) == "png")) {
                return true;
            }
            else {
                return false;
            }
    }

// ######################################################

    /**
     * Check file type 2, images, but no thumbs allowed
     * @param string file location
     * @access private
     * @return boolean
    */

    function checkType1($file) {
            if (!eregi("_thumb\.", $file) && (strtolower(substr($file, -3)) == "gif" || strtolower(substr($file, -3)) == "jpg" || strtolower(substr($file, -3)) == "png")) {
                return true;
            }
            else {
                return false;
            }
    }

// ######################################################

    /**
     * Return large image location
     * @param string folder
     * @param string file
     * @access private
     * @return string large image location
    */

    function imgBig($folder, $file) {
        $fileb = str_replace("_thumb.", ".", $file);
        if (@file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $fileb)) {
            $big = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $fileb;
        }
        else {
            $big = SITE_URL . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $file;
        }
        return $big;
    }

// ######################################################

    /**
     * Return file size
     * @param string folder
     * @param string file
     * @access private
     * @return integer
    */

    function imgSize($folder, $file) {
        $fileb = str_replace("_thumb.", ".", $file);
        if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $fileb)) {
            $size = round(filesize(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $fileb)/1000) . " kb";
        }
        else {
            $size = round(filesize(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $file)/1000) . " kb";
        }
        return $size;
    }

// ######################################################

    /**
     * Return image modified date
     * @param string folder
     * @param string file
     * @access private
     * @return string
    */

    function imgDate($folder, $file) {
        $fileb = str_replace("_thumb.", ".", $file);
        if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/"  . $folder . $fileb)) {
            $date = date("d.m.Y", filemtime(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $fileb));
        }
        else {
            $date = date("d.m.Y", filemtime(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $folder . $file));
        }
        return $date;
    }

// ######################################################

    /**
     * Process query string and put parameters to hidden form fields
     * @param array query string
     * @param array parameters to exclude
     * @access private
     * @return string html form elements
    */

    function hidden($query, $exclude) {
        $ar = split("&", $query);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $ar1 = split("=", $ar[$c]);
            if (!in_array($ar1[0], $exclude) && $ar1[1] != "") {
                //$hidden[$ar1[0]] = $ar1[1];
                $hidden .= "<input type=hidden name=\"" . $ar1[0] . "\" value=\"" . $ar1[1] . "\">\n";
            }
        }
        return $hidden;
    }

// ######################################################

    /**
     * Generate thumbnails for images in a given folder
     * @access private
     * @param string folder
    */

    function generate($folder) {
    //get files
    $opendir = addslashes($folder);
    if ($dir = @opendir($opendir)) {
      // files
      while (($file = @readdir($dir)) !== false) {
          if (!is_dir($opendir . $file) && $file != "." && $file != ".." && $this->checkType1($file) == true) {
                $file_thumb = $opendir . substr($file, 0, -4) . "_thumb." . substr($file, -3);
                @system(IMAGE_CONVERT . " -geometry 120x100 ".$opendir .$file." $file_thumb", $kala);
          }
      }
     }

    }

    /**
     * Set sort order into descending
     */
      function setSortOrderDesc() {
          $this->sort_order_desc = true;
      }

 // ########################################

    /**
     * Check access to the given page/module
     * @return boolean
    */

      function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($GLOBALS["user_data"][0] && $GLOBALS["user_show"] == true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
      }

// ######################################################

    /**
     * Set the template to show
     * @param string template filename
     */

      function setTemplate ($template) {
        if (strpos($template, "..") != FALSE) trigger_error("Module gallery: Template path is invalid !", E_USER_ERROR);
        $this->template = $template;
      }

    // Set gallery parameters

    /*  function setParameters ($a, $b, $c) {
        $this->maxresults = $a;
        $this->maxcolumn = $b;
        $this->template  = $c;
      }*/


    /**
     * Set number of resutls to show
     * @param integer
     */

    function setCount ($count) {
        if ($count > 0 && $count < 10000) {
            $this->maxresults = $count;
        }
    }


// ######################################################

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
        $txt = new Text($this->language, "module_gallery");
        $list[""] = $txt->display("folder");
        $list = $this->parse_folder("", $list);
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

}
