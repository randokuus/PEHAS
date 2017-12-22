<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");

/*
 example use inside template
                <TPL_OBJECT:links>
                  <TPL_OBJECT_OUTPUT:show()>
                </TPL_OBJECT:links>
*/
class links {


  var $tmpl = false;
  var $dbc = false;
  var $language = false;
  var $template = false;
  var $structure = false;
  var $vars = array();
  var $fields = array();
  var $content_module = true;
  var $module_param = array();
  var $userid = false;
  var $maxresults = 25;
  var $cachelevel = TPL_CACHE_NOTHING;
  var $cachetime = 1440; //cache time in minutes

/** Constructor
    */

  function links () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->db = &$GLOBALS['database'];

    $this->userid = $GLOBALS["user_data"][0];
    $this->usergroups = $GLOBALS["user_data"][5];

    if ($this->content_module == true) {
        $this->getParameters();
    }

    $this->isic_common = IsicCommon::getInstance();

    $this->allowed_schools = $this->isic_common->allowed_schools;
  }

    // ########################################

    // Main function to call

    function show() {
        if ($this->checkAccess() == false) return "";

        if (!$this->userid) {
            trigger_error("Module 'links' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        $result = $this->showLinks();
        return $result;
    }


  // ########################################

function showLinks() {
    $sq = new sql;

    if ($this->module_param["links"]) {
        $link_filter = "AND module_links.lgroup = '" . $this->module_param["links"] . "'";
    } else {
        $link_filter = "";
    }

    // instantiate template class
    $tpl = new template;
    $this->cachelevel = TPL_CACHE_ALL;
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_links.html";

    $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=links&today=".urlencode(date("Y-m-d")));
    $tpl->setTemplateFile($template);

    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "links";
        if ($GLOBALS["modera_debug"] == true) {
            return "<!-- module links cached -->\n" . $tpl->parse();
        }
        else {
            return $tpl->parse();
        }
    }

    // #################################

    $sql = "SELECT module_links.* FROM module_links WHERE module_links.active = 1 AND module_links.language = '$this->language' $link_filter ORDER BY linkid";
    $sq->query($this->dbc, $sql);
    if ($sq->numrows == 0) return "";
    else {
        while ($data = $sq->nextrow()) {
            $school_list = explode(",", $data["school_list"]);
            if (!sizeof($school_list) || in_array(0, $school_list) || sizeof(array_intersect($school_list, $this->allowed_schools))) {
                $tpl->addDataItem("DATA.URL", $data["redirectto"]);
                $tpl->addDataItem("DATA.NAME", stripslashes($data["linkid"]));
                $tpl->addDataItem("DATA.DESCRIPTION", stripslashes($data["description"]));
            }
        }
        $sq->free();
    }

    // ####
    return $tpl->parse();
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
        $txt = new Text($this->language, "module_links");

        $sql = "SELECT * FROM module_links_groups WHERE module_links_groups.language = '" . $this->language . "' ORDER BY module_links_groups.name";
        $sq->query($this->dbc, $sql);

        $list = array();
        $list[0] = "---";

        while ($data = $sq->nextrow()) {
            $list[$data["id"]] = $data["name"];
        }

        return array($txt->display("lgroup"), "select", $list);
        // name, type, list
    }

}
