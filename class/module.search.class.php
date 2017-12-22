<?php

/**
 * Search module
 * last modified 10.06.05 (lauri)
 *
 * @package modera_net
 * @version 1.0.1
 * @access public
 */

class search {

/**
 * @var integer active template set
 */
var $tmpl = false;
/**
 * @var string active language
 */
var $language = false;
/**
 * @var string site root url location
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
 * Database object.
 * @var Database
 */
var $_database;
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
 * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
 */
var $user_access = 0;
/**
 * @var string module indentifier for cache filename
 */
var $tplfile = "search";
/**
 * @var string template file
 */
var $template = false;
/**
 * @var string module cache level
 */
var $cachelevel = TPL_CACHE_NONE;
/**
 * @var integer cache expiry time in minutes
 */
var $cachetime = 60; //cache time in minutes
/**
 * @var integer lead size to show with results
 */
var $lead_length = 140; // number must be even
/**
 * @var string type to show, list or form
 */
var $type = "list";
/**
 * @var string scope to search, default all
 */
var $scope = "all";
/**
 * @var integer structure ID
 */
var $structure_id;
/**
 * @var string module to search from
 */
var $serach_method = false;
/**
 * @var string search module search method (defaults to global_site_search)
 */
var $serach_module = "global_site_search";

    /**
     * Class constructor
    */

    function search() {
        global $db, $language;
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $language;
        $this->debug = $GLOBALS["modera_debug"];
        $this->user_access = $GLOBALS["pagedata"]["login"];

        if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
        else { $this->dbc = $db->con; }

        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->type = "";
        if ($this->content_module == true) {
            $this->getParameters();
        }
        $this->_database =& $GLOBALS['database'];
    }


    /**
     * Set structure ID for top level, from where search will start
     * @param int - structure ID
     * @return boolean
     */

    function setStructure($struct_id) {
        if( is_int($stuct_id) )
        {
            $this->structure_id = intval($struct_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set search scope - content, news, all (default)
     * @param string - available options (all/ content/ news)
     * @return boolean
     */

    function setScope($scope) {
        switch ($scope)
        {
            case "all":
                $this->scope = "all";
                return true;
                break;
            case "content":
                $this->scope = "content";
                return true;
                break;
            case "news":
                $this->scope = "news";
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Parse form
     * @access private
     */

    function show_form() {

        // All pages password protected, no user logged in, return nothing
        if ($this->user_access == 1 && !$this->userid) return "";

        // Language instance
        $txtf = new Text($this->language, "output");
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if (!$this->template) $this->setTemplate("module_search_form.html");
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=form&template=".$template);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true && false) {
            $GLOBALS["caching"][] = "search";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module search cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $tpl->addDataItem("SEARCH_QUERY", stripslashes(htmlspecialchars($this->vars['search_query'])));
        $tpl->addDataItem("SEARCH_INFO", $txtf->display("search_info"));
        $tpl->addDataItem("SEARCH", $txtf->display("search"));

        return $tpl->parse();
    }



    /**
     * Main module show function
     */

    function show() {
        if ($this->type == "form") {
            return $this->show_form();
        }
        else if ($this->type == "list") {
            return $this->show_results();
        }
        else {
            return $this->show_form();
        }
    }

    /**
     * Parse list
     * @access private
     */

    function show_results() {

        // All pages password protected, no user logged in, return nothing
        if ($this->user_access == 1 && !$this->userid) {
            return "";
        }

        $txtf = new Text($this->language, "output");

        // create template.
        $tpl  = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);

        $usecache = checkParameters();

        #---- GET URL and transform part
        if ($_GET["search_query"] == "") {
            $query = $_POST["search_query"];
        }
        else {
            $query = $_GET["search_query"];
        }

        //if (!$this->userid) {
        //  $usr_check = " AND content.login = 0 ";
        //}
        //else {
        //  $usr_check = "";
        //}

        if (!$this->template) {
            $this->setTemplate("module_search_list.html");
        }
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        $tpl->setInstance($_SERVER['PHP_SELF']
            . "?language=" . $this->language
            . "&module=search&template=" . $template
            . "&query=" . $query . "&usrcheck=" . $usr_check);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true && false)
        {
            $GLOBALS["caching"][] = "search";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module search cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        // #### SEARCH RESULTS
        // search too general
        if (strlen($query) < 3) {
            #----> And error messages
            //$tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_RESULTS", $txtf->display("search_specify"));
            return $tpl->parse();
        }

        $q = $query;
        $query = mysql_escape_string($query);
        $queryContains = '%' . $query . '%';

        #--> Modify guery .. set top level
        $subq = "";
        if (isset($_GET["content"]) && (int)$_GET["content"] > 0) {
            $_GET["content"] = (int)$_GET["content"];

            $_mpath = $this->_database->fetch_first_row(
                "SELECT `mpath` FROM `content` WHERE `content` = ?"
                , $_GET["content"]
            );

            if ($_mpath && strlen($_mpath['mpath'])) {
                $_mpath = $_mpath['mpath'] . '.' . $_GET['content'];
            } else {
                $_mpath = $_GET['content'];
            }
            $subq = " AND (`content` LIKE '" . $_mpath . "%' OR `content` = {$_GET['content']} )";
        }

        #--> get all matching pages
        $page_res = $this->_database->query("
            SELECT *
            FROM `content`
            WHERE
                `language` = ?
                AND `visible` = 1
                AND (`title` LIKE ?
                    OR `text` LIKE ?
                    OR `lead` LIKE ?
                    OR `keywords` LIKE ?
                )
                $subq;"
            , 
            $this->language,
            $queryContains,
            $queryContains,
            $queryContains,
            $queryContains
        );
        $pages_found = $page_res->num_rows();

        // if search result is 0, add message and return parsed template.
        if ($pages_found) {
            // process search result.
            $pages       = array();
            $parents     = array();

            while ($data = $page_res->fetch_assoc()) {
                // check authority.
                if (!$this->isAuthorisedGroup($data["login"], $data["logingroups"])) {
                    continue;
                }

                // if mpath is null, then this must be the root node.
                if (strlen($data["mpath"]) == 0) {
                    $data["mpath"] = 0;
                }

                $data['text'] =  strip_tags($data['text']);
                $pos = strpos($data['text'], $query);

                if ($pos === false) {
                    $valja = substr($data['text'], 0, $this->lead_length);

                } else {
                    if($pos - ($this->lead_length / 2) < 0)
                    {
                        $len = $this->lead_length - ($pos - ($this->lead_length / 2));
                        $pos = 0;
                    } else {
                            $len = $this->lead_length;
                        $pos = $pos - ($this->lead_length / 2);
                        /*
                        if (strlen($data['text'] < $pos + 100 ){
                            $pos = $pos -(($pos+100)- strlen($data['text']))
                        }
                        */

                    }
                    $valja = substr($data['text'], $pos ,$len);
                }

                if (strlen($valja) < 1) {
                    $valja = substr($data['text'], 0,$this->lead_length);
                } elseif (strlen($valja) > 1) {
                    $valja .= "...";
                } else {
                    $valja = "";
                }

                // get parent ID from mpath and store it.
                $parent = $data["mpath"];
                $parent = array_pop(explode('.', $parent));
                $parents[$parent] = array();

                // highlights
                $valja = str_replace ($query, "<span class=\"highlight\">".$query."</span>", $valja);

                $pages[$parent][$data["content"]]['text']  = $valja;
                $pages[$parent][$data["content"]]['title'] = $data['title'];
                $pages[$parent][$data["content"]]['content'] = $data['content'];
            }
            unset($parents[0]);

            #--> get parent nodes title, lead and keywrod
            $parents_list = join(',', array_keys($parents));
            if ($parents_list) {
                $parents_res = $this->_database->query("
                    SELECT  `title`, `structure`, `content`, `mpath`
                    FROM    `content`
                    WHERE   language = ?
                        AND `visible` = 1
                        AND `content` IN (" . $parents_list . ")"
                    , $this->language
                );
                while ($data = $parents_res->fetch_assoc()) {
                    $parents[$data['content']]['title']  = $data['title'];
                    $parents[$data['content']]['lead']   = $data['lead'];
                    $parents[$data['content']]['keywords']= $data['keywords'];
                    $parents[$data['content']]['content']= $data['content'];
                }
            }

            $_url = '<a href="' . $_SERVER["PHP_SELF"] . '?structure=%s&content=%s">%s </a>';

            $parents_count = count($pages[0]);

            $tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_RESULTS"
                , ereg_replace("{Q}", "$q", $txtf->display("search_query"))
                . "<br>"
                . ereg_replace("{NR1}", "$pages_found", ereg_replace("{NR}", "$parents_count", $txtf->display("search_results"))));

            $nr = 1; // pages counter

            // go through the root pages.
            if (isset($pages[0])) {
                #---- Add search page title and messages
                $tpl->addDataItem("SEARCH_LIST.TITLE", $txtf->display("search_topic"));
                
                foreach ($pages[0] as $page) {
                    $tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_LINK"
                        , "$nr ". sprintf($_url, $page['structure'], $page['content'], $page['title']));
                    $nr ++;
                }
            }

            // add all pages into template
            foreach ($parents as $_parent_id => $_parent_data) {
                $tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_LINK"
                    , "$nr ". sprintf($_url, '', $_parent_id, $_parent_data['title']));
                $nr++;

                if (!is_array($pages[$_parent_id])) continue;

                foreach ($pages[$_parent_id] as $key => $val) {
                    $tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_PAGE.SEARCH_LINK"
                        , sprintf($_url, $val["structure"], $key, $val["title"]));
                    $tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_STRUCTURE.SEARCH_PAGE.SEARCH_LEAD", $val['text']);
                }
            }
        }

        $modules_found = 0;
        if (class_exists($this->search_module)) {
            $tmp_object = new $this->search_module;
            if (method_exists($tmp_object, $this->search_method)) {
                $param = '$search_result = $tmp_object->' . $this->search_method . '("' . $query . '");';
                eval($param);
                if (sizeof($search_result["values"])) {
                    $tpl->addDataItem(strtoupper($this->search_template) . "_SEARCHSUB.TITLE", $search_result["title"]);
                    $modules_found++;

                    // going through every entry in result array
                    for ($i = 0; $i < sizeof($search_result["values"]); $i++) {
                        // every value of every result-row
                        foreach ($search_result["values"][$i] as $tmp_key => $tmp_val) {
                            $tpl->addDataItem(strtoupper($this->search_template) . "_SEARCHSUB.SEARCH_STRUCTURE." . strtoupper($tmp_key), stripslashes($tmp_val));
                        }
                    }
                } // if any results
            } // method exists
            unset($tmp_object);
        }

        if ($pages_found == 0 && $modules_found == 0) {
            //$tpl->addDataItem("SEARCH_LIST.SEARCHSUB.SEARCH_RESULTS", $txtf->display("search_notfound"));
        }

        return $tpl->parse();
    }

    /**
     * Has the user rights to show that page results
     * @access private
     * @return boolean
    */

    function isAuthorisedGroup($login, $logingroups) {
        if ($login == "1") {
            if ($this->userid) {
                if ($logingroups == "") {
                    return true;
                }
                else {
                    $a = split(",", $logingroups);
                    if (is_array($a) && in_array($this->usergroup, $a)) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

// ########################################

    /**
     * Set the template to show
     * @param string template filename
     */

    function setTemplate ($template) {
        if (ereg("\.\.", $template)) trigger_error("Module search: Template path is invalid !", E_USER_ERROR);
        $this->template = $template;
    }

    /**
     * Set the what to display
     * @param string form or list
     */

    function setDisplay ($type) {
        if ($type == "form") {
            $this->type = "form";
        }
        else if ($type == "list") {
            $this->type = "list";
        }
    }

    /**
     * Set the module to search from
     * @param string module name
     * @param string search_method name (defaults to global_site_search)
     */
    function setModule ($module, $method = "global_site_search", $template = "") {
        if (class_exists($module)) {
            $this->search_module = $module;
            $this->search_method = $method;
            $this->search_template = $template ? $template : $module;
        }
    }

// ##########################
// functions for content management

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
        return array("", "select", array());
        // name, type, list
    }
}
