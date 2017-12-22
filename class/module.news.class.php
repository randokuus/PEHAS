<?php

/**
 * News module
 * last modified 04.05.05 (siim)
 *
 * sample use in template:
 * <code>
 *  <TPL_OBJECT:news>
 *      <TPL_OBJECT_PARAM:setCount(20)>
 *      <TPL_OBJECT_PARAM:setGroup(1)>
 *      <TPL_OBJECT_PARAM:setDateSpan('2005-01-01','2005-05-01')>
 *      <TPL_OBJECT_PARAM:setTemplate('module_news_full.html')>
 *      <TPL_OBJECT_PARAM:setDetail('module_news_detail.html')>
 *      <TPL_OBJECT_OUTPUT:show()>
 *  </TPL_OBJECT:news>
 * </code>
 *
 * @package modera_net
 * @version 2.4
 * @access public
 */

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");

class news {

/**
 * @var integer article ID
 */
  var $articleid;
/**
 * @var integer active template set
 */
  var $tmpl = false;
/**
 * @var boolean modera debug
 */
  var $debug = false;
/**
 * @var string active language
 */
  var $language = false;
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
 * @var integer short news list count
 */
  var $short_count = 4;
/**
 * @var integer full news list count
 */
  var $full_count = 50;
/**
 * @var integer archive news list count
 */
  var $archive_count = 20;
/**
 * @var integer general count
 */
  var $count = 0;
/**
 * @var string module cache level
 */
  var $cachelevel = TPL_CACHE_ALL;
/**
 * @var integer cache expiry time in minutes
 */
  var $cachetime = 43200; //cache time in minutes
/**
 * @var array if you would like to use different style for even and odd rows, place style data here
 */
  var $rowstyle = array();
/**
 * @var string module indentifier for cache filename
 */
  var $tplfile = "news";
/**
 * @var string template to use
 */
  var $template = false;
/**
 * @var string detail view template to use
 */
  var $detail = false;
/**
 * @var integer group ID to show
 */
  var $group = false;
/**
 * @var string from date 2005-01-01
 */
  var $date1 = "";
/**
 * @var string to date 2005-01-01
 */
  var $date2 = "";
/**
 * @var integer start showing the results At position (0 is the first)
 */
  var $start_at = 0;
/**
 * @var string date format to use with dates
 */
  var $date_format = "d.m.Y";
/**
 * @var string optional Url to point the detail link to
 */
  var $url = "";


    /**
     * Class constructor
    */

  function news () {
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $GLOBALS["language"];
    $this->debug = $GLOBALS["modera_debug"];
    if (!is_object($GLOBALS["db"])) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $GLOBALS["db"]->con; }

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

// ####################################################

    /**
     * Main module display function
     * @return string html news content
    */

  function show () {
    global $txtf;

    // export articleid from user input
    $articleid = $this->vars['articleid'];

    if ($this->checkAccess() == false) return "";

    if ($articleid != "") {
        $this->articleid = addslashes($articleid);
    }
    if ($this->module_param["news"] != "" && !$this->group) $this->setGroup($this->module_param["news"]);
    if ($this->module_param["news1"] != "" && $this->count == 0) $this->setCount($this->module_param["news1"]);
    if ($this->module_param["news2"] != "" && !$this->date1) $this->setDateSpan($this->module_param["news2"], $this->module_param["news3"]);

    if ($this->vars["article_archive"] > 1900 && $this->vars["article_archive"] < 3000) {
        $this->vars["article_date1"] = $this->vars["article_archive"] . "-01-01";
        $this->vars["article_date2"] = $this->vars["article_archive"] . "-12-31";
    }

    if ($this->vars["article_date1"] != "") $this->setDateSpan($this->vars["article_date1"], $this->vars["article_date2"]);

    $sq = new sql;

    $txt = new Text($this->language, "module_news");

    // instantiate template class
    $tpl = new template;
    $tpl->tplfile = $this->tplfile;
    $tpl->setCacheLevel($this->cachelevel);
    $tpl->setCacheTtl($this->cachetime);
    $usecache = checkParameters();

    // creating correct url for news
    $sql = "SELECT content, structure FROM content WHERE template = 4 AND language = '" . addslashes($this->language) . "' LIMIT 1";
    $sq->query($this->dbc, $sql);
    if ($data = $sq->nextrow()) {
        $contact_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"];
        if ($data["content"]) {
            $contact_url .= "&content=" . $data["content"];
        }
        $tpl->addDataItem("NEWS_URL", $contact_url);
    }


    if ($this->count == 0) $this->count = $this->full_count;

    if (!$this->template) {
        $this->template = "module_news_full.html";
    }

    if ($this->vars["archive"] == true) {
        $this->template = "module_news_full_archive.html";
        $this->count = $this->archive_count;
    }

    if ($this->articleid && !$this->detail) $this->articleid = false;

    if ($this->articleid && $this->detail) {
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->detail;
    }
    else {
        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
    }


    $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=news&group=".$this->group."&articleid=".$this->articleid."&date1=".addslashes($this->date1)."&date2=".addslashes($this->date2));
    $tpl->setTemplateFile($template);

    // PAGE CACHED
    if ($tpl->isCached($template) == true && $usecache == true) {
        $GLOBALS["caching"][] = "news";
        if ($GLOBALS["modera_debug"] == true) {
            return "<!-- module news cached -->\n" . $tpl->parse();
        }
        else {
            return $tpl->parse();
        }

    }

    // #################################
    // find out the page to link the news to

    if ($this->url == "") {

        if ($this->group) {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE module LIKE '%news=".addslashes($this->group)."%' AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        else {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 4 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        if ($sq->numrows != 0) { $data = $sq->nextrow(); $general_url = $_SERVER["PHP_SELF"]."?structure=" . $data["structure"] . "&content=" . $data["content"];   }
        else {
            //$general_url = "#PLEASE_CREATE_NEWS_PAGE";
            $general_url = $_SERVER["PHP_SELF"] . "?structure=". $this->vars["structure"] . "&content=".$this->vars["content"];
        }
        $sq->free();

    }
    else {
        $general_url = $this->url;
    }

    // #################################

    if ($this->group) $grp = "AND ngroup = '".$this->group."'";
    else { $grp = ""; }

    if ($this->date1) $datespan = "AND module_news.entrydate >= '".addslashes($this->date1)."' AND module_news.entrydate <= '".addslashes($this->date2)."'";
    else { $datespan = ""; }

    if ($this->group) {
        //$sq->query($this->dbc, "SELECT name FROM module_news_groups WHERE language = '".$this->language."' AND id = '".$this->group."'");
        // 08.09 language reference in groups removed
        $sq->query($this->dbc, "SELECT name FROM module_news_groups WHERE id = '".$this->group."'");
        $group_name = $sq->column(0, "name");
    }

    // #################################

    if ($this->articleid) {

        $sq->query($this->dbc, "SELECT id, unix_timestamp(entrydate) as date, pic, title, author, lead, content FROM module_news WHERE language = '".$this->language."' $grp AND id = '" . $this->articleid . "'");
        $data = $sq->nextrow();
            $tpl->addDataItem("ARTICLE.GROUP_NAME", $group_name);
            $tpl->addDataItem("ARTICLE.DATE", formatDate($this->date_format, $data["date"]));
            $tpl->addDataItem("ARTICLE.TITLE", $data["title"]);
            $tpl->addDataItem("ARTICLE.AUTHOR", $data["author"]);
            $tpl->addDataItem("ARTICLE.LEAD", $data["lead"]);
            $tpl->addDataItem("ARTICLE.CONTENT", $data["content"]);
            $tpl->addDataItem("ARTICLE.PIC", $data["pic"]);
            $tpl->addDataItem("ARTICLE.BACKURL", $general_url . "&archive=" . $this->vars["archive"]);
            $tpl->addDataItem("ARTICLE.BACK", $txt->display("back"));
        $sq->free();
        $tpl->addDataItem("GROUP_NAME", $group_name);

    }

    // #################################

    else {

        if ($this->vars["filter_year"]) {
            $filt_year = " AND YEAR(module_news.entrydate) = '" . $this->vars["filter_year"] . "'";
        }

        if ($this->vars["filter_category"]) {
            $filt_category = " AND module_news.ngroup = '" . $this->vars["filter_category"] . "'";
        }

        $start = 0;
        if ($this->vars["start"]) {
            $start = $this->vars["start"];
        }
        $nr = 1;
        $firstarticle = false;
        $sq->query($this->dbc, "SELECT id, unix_timestamp(entrydate) as date, title, pic, author, lead, content FROM module_news WHERE language = '".$this->language."' $grp $datespan $filt_year $filt_category ORDER BY entrydate DESC LIMIT ".$this->start_at.",".$this->count);
        while ($data = $sq->nextrow()) {
            if ($firstarticle == false) $firstarticle = $data["id"];
            if ($style == $this->rowstyle[0]) $style = $this->rowstyle[1];
            else { $style = $this->rowstyle[0]; }

            $tpl->addDataItem("ARTICLE.STYLE", $style);
            $tpl->addDataItem("ARTICLE.NR", $nr);
            $tpl->addDataItem("ARTICLE.DATE", formatDate($this->date_format, $data["date"]));
            $tpl->addDataItem("ARTICLE.TITLE", $data["title"]);
            $tpl->addDataItem("ARTICLE.AUTHOR", $data["author"]);
            $tpl->addDataItem("ARTICLE.PIC", $data["pic"]);
            $tpl->addDataItem("ARTICLE.CONTENT", $data["content"]);
            $tpl->addDataItem("ARTICLE.LEAD", $data["lead"]);
            if ($this->vars["archive"]) {
                $tpl->addDataItem("ARTICLE.URL", $general_url . "&articleid=" . $data["id"] . "&archive=" . $this->vars["archive"]);
            }
            else{
                $tpl->addDataItem("ARTICLE.URL", $general_url . "&articleid=" . $data["id"]);
            }
            $tpl->addDataItem("ARTICLE.GROUP_NAME", $group_name);
            $nr++;
        }
        $sq->free();

        $tpl->addDataItem("GROUP_NAME", $group_name);

        $tpl->addDataItem("ALLNEWS", $txt->display("allnews"));
        $tpl->addDataItem("ALLURL", $general_url);

        $tpl->addDataItem("ARCHIVE_URL", $general_url . "&archive=true");
        $tpl->addDataItem("ARCHIVE_NAME", $txt->display("archive"));

        if ($this->vars["archive"] == true) {
            // page listing
            $sql = "SELECT COUNT(*) AS totalnews FROM module_news WHERE language = '".$this->language."' $grp $datespan $filt_year $filt_category";
            $sq->query($this->dbc, $sql);
            $data = $sq->nextrow();
            $total = $data["totalnews"];
            $sq->free();

//          $disp = ereg_replace("{NR}", "$total", $txt->display("results"));
            if ($results >= $this->count) {
                $end = $start + $this->count;
            } else {
                $end = $start + $results;
            }
            if ($end == 0) $start0 = 0;
            else { $start0 = $start + 1; }
//          $disp = ereg_replace("{DISP}", $start0 . "-$end", $disp);
//          $tpl->addDataItem("RESULTS", $disp);

            $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . "&nocache=true&archive=true&filter_category=".$this->vars["category"]."&filter_year=" . $this->vars["filter_year"], $this->count, $txt->display("prev"), $txt->display("next")));
        }
/*
        // archive links
        $sq->query($this->dbc, "SELECT date_format( entrydate, '%Y' ) AS year FROM module_news WHERE language =  '".$this->language."' GROUP BY year ORDER BY year DESC");
        while ($data = $sq->nextrow()) {
            $tpl->addDataItem("ARCHIVE.URL", $general_url . "&article_archive=" . $data["year"]);
            $tpl->addDataItem("ARCHIVE.NAME", $txt->display("archive") . " " . $data["year"]);
        }
        $sq->free();
*/

        // ###############################
        // Filter fields

        $filter_fields = array(
            "category" => array("select",0),
            "year" => array("select",0)
        );

        // #################################
        // YEARS
        $list = array();
        $sql = "SELECT DISTINCT(YEAR(module_news.entrydate)) AS news_year FROM module_news WHERE module_news.language = '" . $this->language . "' ORDER BY news_year DESC";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $list[$data["news_year"]] = $data["news_year"];
        }
        $sq->free();
        $list[0] = $txt->display("all_years");
        $filter_fields["year"][2] = $list;

        // #################################
        // categories
        $list = array();
        $sql = "SELECT module_news_groups.* FROM module_news_groups WHERE module_news_groups.language = '" . $this->language . "' ORDER BY name ASC";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            $list[$data["id"]] = $data["name"];
        }
        $sq->free();
        $list[0] = $txt->display("all_categories");
        $filter_fields["category"][2] = $list;

        while (list($key, $val) = each($filter_fields)) {
            $fdata["type"] = $val[0];
            $fdata["size"] = $val[1];
            $fdata["list"] = $val[2];
            $f = new AdminFields("filter_$key", $fdata);
            $field_data = $f->display($this->vars["filter_$key"]);
            $tpl->addDataItem("FIELD_filter_$key", $field_data);
            unset($fdata);
        }

        $tpl->addDataItem("SELF", $general_url);
        $tpl->addDataItem("HIDDEN", "<input type=\"hidden\" name=\"archive\" value=\"true\">");

        // get 1st article to show just in case
        if ($firstarticle) {
            $sq->query($this->dbc, "SELECT id, unix_timestamp(entrydate) as date, pic, title, author, lead, content FROM module_news WHERE language = '".$this->language."' $grp AND id = '" . $firstarticle . "'");
            $data = $sq->nextrow();
            $tpl->addDataItem("FIRSTARTICLE.DATE", formatDate($this->date_format, $data["date"]));
            $tpl->addDataItem("FIRSTARTICLE.TIME", $data["datetime"]);
            $tpl->addDataItem("FIRSTARTICLE.TITLE", $data["title"]);
            $tpl->addDataItem("FIRSTARTICLE.CONTENT", $data["content"]);
            $tpl->addDataItem("FIRSTARTICLE.LEAD", $data["lead"]);
            $tpl->addDataItem("FIRSTARTICLE.PIC", $data["pic"]);
            $sq->free();
        }
    }

    // ####

    return $tpl->parse();

  }

// ########################################

    /**
     * old mode function, for bacward compatibility
    */

      function setMode ($mode) {
        if ($mode == "short") {
            $this->detail = false;
            $this->count = $this->short_count;
            $this->template = "module_news_short.html";
        }
        else if ($mode == "full") {
            $this->count = $this->full_count;
            $this->detail = "module_news_detail.html";
            $this->template = "module_news_full.html";
        }
      }

    /**
     * Set group to show
     * @param integer set group ID to show
    */

      function setGroup ($group) {
        if ($group > 0 && $group < 10000) {
            $this->group = addslashes($group);
        }
      }

    /**
     * Set the template to show
     * @param string template filename
     */

      function setTemplate ($template) {
        if (ereg("\.\.", $template)) trigger_error("Module news: Template path is invalid !", E_USER_ERROR);
        $this->template = $template;
      }

    /**
     * Set the detail view template to show
     * @param string template filename
     */

      function setDetail ($template) {
        if (ereg("\.\.", $template)) trigger_error("Module news: Template path is invalid !", E_USER_ERROR);
        $this->detail = $template;
      }

    /**
     * Set number of resutls to show
     * @param integer
     */

      function setCount ($count) {
        if ($count > 0 && $count < 10000) {
            $this->count = $count;
        }
      }

    /**
     * Set the date span to show
     * @param string date from
     * @param string date to
     */

      function setDateSpan ($date1, $date2) {
        if ($date1) {
            $this->date1 = addslashes($date1);
        }
        if ($date1 && $date2) {
            $this->date2 = addslashes($date2);
        }
      }

    /**
     * Set rowstyles, one for evend, one for odd
     * @param string style 1
     * @param string style 1
     */

      function setRowStyle($style1, $style2) {
        $this->rowstyle = array($style1, $style2);
      }

    /**
     * Set start showing results At position
     * @param integer position, 0 is the first
     */

      function setStartAt($number) {
        if ($number > 0 && $number < 1000000) {
            $this->start_at = $number;
        }
      }

    /**
     * Set URL to point the detail links to
     * @param string url
     */

      function setUrl($url) {
        if ($url) {
            $this->url = $url;
        }
      }

    /**
     * Set date format, based on php date() function parameters
     * @param string format to use (do not use : in here, a known issue)
     */

      function setDateFormat($format) {
        $this->date_format = $format;
      }

    // #####################
    // global site search interface
    function global_site_search($search, $beg_date = "", $end_date = "") {
        $sq = new sql;
        $txt = new Text($this->language, "module_news");

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 4 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        if ($data = $sq->nextrow()) {
            $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"] . "&content=" . $data["content"];
        } else {
            return false; // no content-record found, will not continue
        }

        // creating array for search result
        $result = array(
            "title" => $txt->display("module_title"), // module title
            "fields" => array("entrydate", "title", "lead", "author", "source"), // array of fields with according titles
            "values" => array() // array of values will be stored here
        );

        if ($this->userid) {
            if ($beg_date) {
                $bd = date("d", strtotime($beg_date));
                $bm = date("m", strtotime($beg_date));
                $by = date("Y");
                $beg_date = date("Y-m-d", mktime(0, 0, 0, $bm, $bd, $by));

                $date_filter .= " AND entrydate >= '" . $beg_date . "'";
            }
            if ($end_date) {
                $ed = date("d", strtotime($end_date));
                $em = date("m", strtotime($end_date));
                $ey = date("Y");
                $end_date = date("Y-m-d 23:59:59", mktime(0, 0, 0, $em, $ed, $ey));

                $date_filter .= " AND entrydate <= '" . $end_date . "'";
            }

            $sql = "SELECT module_news.* FROM module_news WHERE (LOWER(module_news.title) LIKE LOWER('%" . $search . "%') OR LOWER(module_news.lead) LIKE LOWER('%" . $search . "%') OR LOWER(module_news.content) LIKE LOWER('%" . $search . "%')) $date_filter ORDER BY module_news.entrydate DESC";
            $sq->query($this->dbc, $sql);

            $row = 0;
            while ($data = $sq->nextrow()) {
                $data["entrydate"] = date("d.m.Y", strtotime($data["entrydate"]));
                $result["values"][$row]["url"] = $general_url . "&articleid=" . $data["id"];
                foreach ($result["fields"] as $key) {
                    $result["values"][$row][$key] = $data[$key];
                }
                $row++;
            }
        }

        $sq->free();

        return $result;
    }

    // #######################################################
    //      Functions for handling news XML

    /**
     * Method for receiving config-data for one xml-feed to import
     * @access private
     * @param int $id is the value of unique id of the record in config-table
     * @return array contents of a config-table record
    */

    function get_news_conf($id) {
        $sq = new sql;
        $sql = "SELECT * FROM module_news_config WHERE id = $id";
        $sq->query($this->dbc, $sql);
        return $sq->nextrow();
    }

    /**
     * Method for downloading xml into static-file
     * @access private
     * @param int $id is the value of unique id of the record in config-table
     * @param string $news_xmlfile contains path where downloaded xml should be saved in local filesystem
     * @return boolean true if something went bad and false if everything was OK
    */

    function download_news_xml($id, $news_xmlfile) {
        $news_data = $this->get_news_conf($id);
        $news_username = $news_data["username"];
        $news_password = $news_data["password"];
        $news_url = $news_data["url"];

        $contents = "";
        if ($fp = fopen($news_url, "r")) {
            while (!feof($fp)) {
                $contents .= fread($fp, 8192);
            }
            fclose($fp);

            if ($fp = fopen($news_xmlfile, "w+")) {
                fwrite($fp, $contents);
                fclose($fp);
            } else {
                echo "Can't write to local file ...";
                return false;
            }
        } else {
            echo "Can't open feed ...";
            return false;
        }

        return true;

/*
        $wget = "/usr/bin/wget";
        if ($news_username) {
            $news_username = " --http-user=" . $news_username;
        }
        if ($news_password) {
            $news_password = " --http-passwd=" . $news_password;
        }
        $timeout = " --timeout=0";
        $output = " --output-document=" . $news_xmlfile;

        $run_str = $wget . $timeout . $news_username . $news_password . $output . " '" . $news_url . "'";

        echo "RUN: " . $run_str . "<br>\n";

        $result = trim(system($run_str, $error_message));

        echo "ERROR: " . $error_message . "\n";
        return !$error_message;
*/
    }

    /**
     * Loads contents of a XML-file into memory
     * @access private
     * @param string $news_xmlfile contains path where XML-file resides in filesystem
     * @return string contents of a xml-file
    */

    function load_news_xml($news_xmlfile) {
        $handle = fopen ($news_xmlfile, "r");
        $contents = fread ($handle, filesize ($news_xmlfile));
        fclose ($handle);
        return $contents;
    }

    /**
     * Goes through array that was made from XML and creates new array with right values
     * @access private
     * @param array $i_data that holds parsed XML-file
     * @return array values in correct form for database writing
    */

    function get_news_objects($i_data) {
        $obj_data = array();

        for ($i = 0; $i < sizeof($i_data); $i++) {
            $elements = $i_data[$i]["_ELEMENTS"];
            for ($e = 0; $e < sizeof($elements); $e++) {
                $data = $elements[$e]["_DATA"];

                switch ($elements[$e]["_NAME"]) {
                    case "id":
                        $obj_data[$i]["orig_id"] = $data; // news ID
                    break;
/*
                    case "language":
                        $obj_data[$i]["language"] = $data;
                    break;
*/
                    case "timestamp":
                        $obj_data[$i]["entrydate"] = date("Y-m-d H:i:s", $data);
                    break;
                    case "title":
                        $obj_data[$i]["title"] = $data;
                    break;
                    case "author":
                        $obj_data[$i]["author"] = $data;
                    break;
                    case "lead":
                        $obj_data[$i]["lead"] = $data;
                    break;
                    case "content":
                        $obj_data[$i]["content"] = $data;
                    break;
                    case "pic":
                        $obj_data[$i]["pic"] = $data;
                    break;
                }
            }
        }
        return $obj_data;
    }

    /**
     * Saves array of news-items into database table module_news
     * @access private
     * @param array $obj_data that holds values for news-items
     * @param int $config_id unique value of config-table to differentiate the records from different feeds with the same orig_id-s
     * @param int $ngroup value that shows into what news group should this XML be saved
     * @param string $language language of the saved records
     * @param string $import_from date starting from which values should be imported
     * @return
    */

    function save_news_objects($obj_data, $config_id, $ngroup, $language, $import_from) {
        $sq = new sql;
        $table = "module_news";

        for ($i = 0; $i < sizeof($obj_data); $i++) {
            echo $i . ". " . $obj_data[$i]["entrydate"] . "\n";
            if ($obj_data[$i]["entrydate"] >= $import_from) {
                // checking if there is object in table with the same news_id, if not then insertin, else do nothing
                $sql_chk = "SELECT id FROM $table WHERE $table.config_id = '" . $config_id . "' AND $table.orig_id = '" . $obj_data[$i]["orig_id"] . "'";

                $sq->query($this->dbc, $sql_chk);
                // if object with this news id already existst then update-ing record
                if ($data = $sq->nextrow()) {
                    $id = $data["id"];

                    $sql = "UPDATE $table SET id = id";
                    while (list($key, $val) = each($obj_data[$i])) {
                        $sql .= ", " . $key . " = " . "'". addslashes($val) . "'";
                    }
                    $sql .= " WHERE id = '$id'";

                    $sq->query($this->dbc, $sql);
                } else { // if record did not exist then inserting new one
                    $sql_fields = "";
                    $sql_values = "";

                    while (list($key, $val) = each($obj_data[$i])) {
                        if ($sql_fields != "") {
                            $sql_fields .= ", ";
                            $sql_values .= ", ";
                        }
                        $sql_fields .= $key;
                        $sql_values .= "'". addslashes($val) . "'";
                    }

                    $sql = "INSERT INTO $table (" . $sql_fields . ", ngroup, config_id, language) VALUES (" . $sql_values . ", '" . $ngroup . "', '" . $config_id . "', '" . $language . "')";
                    $sq->query($this->dbc, $sql);
                }
            }
    //        echo "<br>\n";
        }

        return $del_where;
    }

    /**
     * Method that coordinates news-importing from XML
     * @access public
    */

    function refresh_news_objects() {

        require_once(SITE_PATH . "/class/Xml2Array.php");
        $xml2array = new Xml2Array();
        $sq = new sql;

        $sql = "SELECT id, path, ngroup, language, import_from FROM module_news_config WHERE module_news_config.active = 1";
        $sq->query($this->dbc, $sql);
        while ($data = $sq->nextrow()) {
            if ($this->download_news_xml($data["id"], SITE_PATH . $data["path"])) {
                $contents = $this->load_news_xml(SITE_PATH . $data["path"]);
                $root = $xml2array->parse($contents);

                foreach($root as $key => $value) {
                    if ($value["_NAME"] == "news") {
                        $this->save_news_objects($this->get_news_objects($value["_ELEMENTS"]), $data["id"], $data["ngroup"], $data["language"], $data["import_from"]);
                    }
                }
            }
        }
    }

 // ########################################

    /**
     * Check does the active user have access to the page/form
     * @access private
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
        $list[""] = "- - -";
        $txt = new Text($this->language, "module_news");
        $sq->query($this->dbc, "SELECT id, name FROM module_news_groups ORDER BY name ASC");
        while ($data = $sq->nextrow()) {
            $list[$data["id"]] = $data["name"];
        }
        $sq->free();

        // ####
        return array($txt->display("option1"), "select", $list,$txt->display("option2"), "textinput", "",$txt->display("option5")."<br>".$txt->display("option5_extra"), "textinput", "", $txt->display("option6")."<br>".$txt->display("option5_extra"), "textinput", "");
        // name, type, list
    }

}
