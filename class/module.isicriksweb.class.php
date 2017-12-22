<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicRiksHash.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");

class IsicRiksWeb {
    /**
     * @var array merged array with _GET and _POST data
     */
    var $vars = array();

    /**
     * Current language code
     *
     * @var string
     * @access protected
     */
    var $language;

    /**
     * Active template set
     *
     * @var int
     * @access private
     */
    var $tmpl;

    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;
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
     * @var integer user group ID
     */
    var $usergroup = false;
    /**
     * @var array user groups
     */
    var $usergroups = false;
    /**
     * @var user type (1 - can view all cards from the school his/her usergroup belongs to, 2 - only his/her own cards)
     */
    var $user_type = false;

    /**
     * @var user code (personal number (estonian id-code for example))
     */
    var $user_code = false;

    /**
     * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
     */
    var $user_access = 0;

    /**
     * Users that are allowed to access the same cards as current user
     *
     * @var array
     * @access protected
     */
    var $allowed_users = array();

    /**
     * Maximum number of results in listview
     *
     * @var int
     * @access protected
     */
    var $maxresults = 50;

    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */

    var $cachelevel = TPL_CACHE_NOTHING;

    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */

    var $cachetime = 1440;

   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function IsicRiksWeb () {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        $this->isic_common = IsicCommon::getInstance();
        $this->allowed_schools = $this->isic_common->allowed_schools;
    }

    /**
     * Main module display function
     *
     * @return string html RiksWeb content
    */

    function show () {
        if ($this->checkAccess() == false) return "";

        $action = @$this->vars["action"];
        $step = @$this->vars["step"];
        $card_id = @$this->vars["card_id"];

        if (!$this->userid) {
            trigger_error("Module 'IsicRiksWeb' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == 2 && !$this->user_code) {
            trigger_error("Module 'IsicRiksWeb' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }

        $result = $this->showLoginButton();
        return $result;
    }

    /**
     * Displays login button for entering RiksWeb
     *
     * @return string html detailview of a login screen
    */

    function showLoginButton() {
        $txt = new Text($this->language, "module_isic_riksweb");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_riksweb.html";

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isicriksweb&type=showloginbutton");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module isicriksweb cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        if ($this->allowed_schools) {
            $r = &$this->db->query("
                SELECT
                    `module_isic_school`.*
                FROM
                    `module_isic_school`
                WHERE
                    `module_isic_school`.`id` IN (!@)
                ", $this->allowed_schools);
    
            while ($data = $r->fetch_assoc()) {
                if ($data["riksweb_url"] && $this->user_code) {
                    $tpl->addDataItem("DATA.URL", $data["riksweb_url"]);
                    $tpl->addDataItem("DATA.NAME", str_replace("{SCHOOL_NAME}", $data["name"], $txt->display("login_button_text")));
                    $tpl->addDataItem("DATA.LOGIN_CODE", IsicRiksHash::encode($this->user_code));
                }
            }
        }

        return $tpl->parse();
    }

    /**
     * Displays error message for user
     *
     * @param string $message message to show
     * @return boolean
     */

    function showErrorMessage ($message) {
        $txt = new Text($this->language, "module_isic_riksweb");
        $txtf = new Text($this->language, "output");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_error.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=isicriksweb&type=error");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module isic cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display($message));

        return $tpl->parse();
    }

    /**
     * Check does the active user have access to the page/form
     *
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

    /**
     * Returns module parameters array
     *
     * @return array module parameters
    */

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Creates array of module parameter values for content admin
     *
     * @return array module parameters
    */

    function moduleOptions() {
        $sq = new sql;
        return array();
        // name, type, list
    }
}