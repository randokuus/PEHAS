<?php
/*
 example use inside template
                <TPL_OBJECT:clock>
                  <TPL_OBJECT_OUTPUT:show()>
                </TPL_OBJECT:clock>
*/
class clock {


  var $tmpl = false;
  var $dbc = false;
  var $language = false;
  var $template = "module_clock.html";
  var $structure = false;
  var $vars = array();
  var $fields = array();
  var $content_module = false;
  var $module_param = array();
  var $userid = false;
  var $cachelevel = TPL_CACHE_NOTHING;
  var $cachetime = 1440; //cache time in minutes


/** Constructor
    */

  function clock () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

    // ########################################

    // Main function to call

    function show() {
        if ($this->checkAccess() == false) return "";

        if (!$this->userid) {
            trigger_error("Module 'clock' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        // instantiate template class
        $tpl = new template;
        //$this->cachelevel = TPL_CACHE_ALL;
        $this->cachelevel = TPL_CACHE_NOTHING;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=clock");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "clock";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module clock cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $count = 1;
        $sq = new sql;
        $sql = "SELECT * FROM module_clock ORDER BY sort_order LIMIT 10";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $tpl->addDataItem("CLOCK.NUMBER", str_pad($count++, 2, "0", STR_PAD_LEFT));
            $tpl->addDataItem("CLOCK.NAME", $data["name"]);
            $tpl->addDataItem("CLOCK.OFFSET", $data["offset"]);
        }
        $sq->free();

        return $tpl->parse();
    }

    /**
     * Set the template to show
     * @param string template filename
     */

      function setTemplate ($template) {
        if (ereg("\.\.", $template)) trigger_error("Module clock: Template path is invalid !", E_USER_ERROR);
        $this->template = $template;
      }

// ########################################

    /**
     * Check does the active user have access to the page/form
     * @access private
     * @return boolean
     */

    function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
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

    // #####################
    // functions for content management

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    function moduleOptions() {
        $sq = new sql;

        return array();
        // name, type, list
    }
}