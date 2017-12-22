<?php

/**
 * Datafeed module. export XML and RSS feeds from news, site structure
 *
 * @package modera_net
 * @version 1.0
 * @access public
 */

class datafeed {

/**
 * @var integer active template set
 */
    var $tmpl = false;
/**
 * @var integer database connection identifier
 */
    var $dbc = false;
/**
 * @var boolean modera debug value
 */
    var $debug = false;
/**
 * @var string active language
 */
    var $language = false;
/**
 * @var string template to use
 */
    var $template = false;
/**
 * @var array merged array with _GET and _POST data
 */
    var $vars = array();
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
 * @var integer user group
 */
    var $usergroup = false;
/**
 * @var string module cache level
 */
    var $cachelevel = TPL_CACHE_NOTHING;
/**
 * @var integer cache expiry time in minutes
 */
    var $cachetime = 1440; //cache time in minutes
/**
 * @var string cache file partial filename, used to identify specific modules
 */
    var $tplfile = "datafeed";
/**
 * @var string feed name to fetch
 */
    var $feed_name = false;
/**
 * @var string feed password
 */
    var $password = false;

    /**
     * Class constructor
    */

    function datafeed($language) {
        global $db;
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $language;
        $this->debug = $GLOBALS["modera_debug"];

        if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
        else { $this->dbc = $db->con; }

        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];

        if ($this->content_module == true) {
            $this->getParameters();
        }
    }

    /**
     * Return datafeed, general function to call
     * @return string feed contents
    */
    function show() {
        $sq = new sql();

        $this->feed_name = $this->vars['feed'];
        $this->password = $this->vars['password'];

        // check mysql version, if it is >= 4.1.0 we will use old_password function
        $hash_funct = $sq->pass_funct($this->dbc);
        $sq->query($this->dbc, "SELECT *, $hash_funct('".addslashes($this->password)."') as password_check FROM module_datafeed WHERE feed_name = '".addslashes($this->feed_name)."' AND active = 1");

        if($sq->numrows > 0) {
            if ($sq->column(0, "password") != "") {
                if ($sq->column(0, "password") != $sq->column(0, "password_check")) {
                    trigger_error("Module datafeed: feed requires a password. the one given is incorrect !", E_USER_ERROR);
                    exit;
                }
            }
            if($sq->column(0, "feed_source") == "news") {
                $output = $this->showNews($sq->column(0, "feed_type"), $sq->column(0, "feed_group"));
            }
            else if($sq->column(0, "feed_source") == "site_menu") {
                $output = $this->showMenu($sq->column(0, "feed_type"));
            }
        } else {
            trigger_error("Module datafeed: Unknown feed source !", E_USER_ERROR);
            exit;
        }
        echo $output;
        //return "";
    }

    /**
     * Return menu feed data
     * @return string feed contents
    */

    function showMenu() {

        // XML header
        if (!headers_sent()) {
            header("Content-type: application/xml");
        }
        else {
            trigger_error("Module datafeed: XML header cannot be sent. Headers sent !", E_USER_ERROR);
        }
        $xslp = new xslprocess();
        $xslp->cachelevel = TPL_CACHE_NOTHING;
        return $xslp->siteMenu();
    }

    /**
     * Return news feed data, main function to call in case of News
     * @return string feed contents
    */

    function showNews($type, $group) {

        if($type == 1)//XML
        {
            return $this->newsXML($group);
        } else if($type == 2){
            return $this->newsRSS($group);
        } else {
            trigger_error("Module datafeed: Feed type unspecified !", E_USER_WARNING);
            return "";
        }
    }

    /**
     * Return news feed data RSS
     * @return string feed contents
    */

    function newsRSS($group_id = false) {

        $sq = new sql();

        // we need to know the page url where the news are
        if ($group_id) {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE module LIKE '%news=".addslashes($group_id)."%' AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        else {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 4 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        if ($sq->numrows != 0) { $data = $sq->nextrow(); $general_url = SITE_URL."/?structure=" . $data["structure"] . "&content=" . $data["content"];  }
        else {
            $general_url = SITE_URL . "/?";
        }

        $sq->query($this->dbc, "SELECT `feed_title` FROM `module_datafeed` WHERE `feed_name` = '".addslashes($this->feed_name)."'");
        $row = $sq->nextrow();

        $feed_title = $GLOBALS["site_settings"]["name"];
        if (isset($row) && $row['feed_title']) {
            $feed_title = $row['feed_title'];
        }

        if ($group_id) {
            $sq->query($this->dbc,
                "SELECT module_news.*, unix_timestamp(module_news.entrydate) as date, module_news_groups.name as groupname
                FROM module_news
                LEFT JOIN module_news_groups ON module_news.ngroup = module_news_groups.id
                WHERE module_news.language = '".addslashes($this->language) . "' AND module_news.ngroup = '".addslashes($group_id)."' ORDER BY module_news.entrydate DESC");
        }
        else {
                $sq->query($this->dbc,
                "SELECT module_news.*, unix_timestamp(module_news.entrydate) as date, module_news_groups.name as groupname
                FROM module_news
                LEFT JOIN module_news_groups ON module_news.ngroup = module_news_groups.id
                WHERE module_news.language = '".addslashes($this->language) . "' ORDER BY module_news.entrydate DESC");
        }

            $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
                        "<rss version=\"2.0\"\n".
                        "       xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n".
                        "       xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n".
                        "       xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n".
                        "> \n".
                        "   <channel>\n".
                        "       <title>".$this->textToEntities($feed_title)."</title>\n".
                        "       <link>".SITE_URL."</link>\n".
                        "       <description>".$this->textToEntities($GLOBALS["site_settings"]["name"])." news</description>\n".
                        "       <language>".strtolower($this->language)."</language>\n".
                        "       <pubDate>".$this->textToEntities(strftime("%a, %d %b %Y %H:%M:%S %z"))."</pubDate>\n";

        while ($data = $sq->nextrow()) {

            $link = $general_url . "&articleid=".$data['id'] . "&language=". $data["language"];

            $output .=  "       <item>\n".
                        "           <title>".$this->textToEntities($data['title'])."</title> \n".
                        "           <link>".$this->textToEntities($link)."</link> \n".
                        "           <description><![CDATA[".strip_tags($data['content'])."]]></description> \n".
                        "           <pubDate>".formatDate("D, d M Y H:i:s O", $data["date"])."</pubDate>\n".
                        "           <dc:creator>".$this->textToEntities($data["author"])."</dc:creator>\n".
                        "       <category>".$this->textToEntities($data["groupname"])."</category>\n".
                        "           <guid>".$this->textToEntities($link)."</guid>\n".
                        "       </item>\n";
        }

            $output .=  "   </channel> \n".
                        "</rss> \n";


        // RSS XML header
        if (!headers_sent()) {
            header("Content-type: application/xml");
        }
        else {
            trigger_error("Module datafeed: XML header cannot be sent. Headers sent !", E_USER_ERROR);
        }
        return $output;
    }

    /**
     * Return news feed data XML
     * @return string feed contents
    */

    function newsXML($group_id = false) {

        $sq = new sql();

        // we need to know the page url where the news are
        if ($group_id) {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE module LIKE '%news=".addslashes($group_id)."%' AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        else {
            $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 4 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        }
        if ($sq->numrows != 0) { $data = $sq->nextrow(); $general_url = SITE_URL."/?structure=" . $data["structure"] . "&content=" . $data["content"];  }
        else {
            $general_url = SITE_URL . "/?";
        }

        if ($group_id) {
            $sq->query($this->dbc,
                "SELECT module_news.*, unix_timestamp(module_news.entrydate) as date, module_news_groups.name as groupname
                FROM module_news
                LEFT JOIN module_news_groups ON module_news.ngroup = module_news_groups.id
                WHERE module_news.language = '".addslashes($this->language) . "' AND module_news.ngroup = '".addslashes($group_id)."' ORDER BY module_news.entrydate DESC");
        }
        else {
                $sq->query($this->dbc,
                "SELECT module_news.*, unix_timestamp(module_news.entrydate) as date, module_news_groups.name as groupname
                FROM module_news
                LEFT JOIN module_news_groups ON module_news.ngroup = module_news_groups.id
                WHERE module_news.language = '".addslashes($this->language) . "' ORDER BY module_news.entrydate DESC");
        }

        $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $output .= "<news> \n";
        while ($data = $sq->nextrow())
        {
            $output .= "    <item> \n".
                        "       <id>".$data['id']."</id>\n".
                        "       <link>".$this->textToEntities($general_url . "&articleid=". $data["id"] . "&language=".$data["language"])."</link>\n".
                        "       <language>".$data['language']."</language>\n".
                        "       <group_id>".$data['ngroup']."</group_id>\n".
                        "       <group_name>".$this->textToEntities($data['groupname'])."</group_name>\n".
                        "       <date>".formatDate("D, d M Y H:i:s O", $data["date"])."</date>\n".
                        "       <timestamp>".$data["date"]."</timestamp>\n".
                        "       <title>".$this->textToEntities($data['title'])."</title>\n".
                        "       <author>".$this->textToEntities($data['author'])."</author>\n".
                        "       <lead><![CDATA[".$data['lead']."]]></lead>\n".
                        "       <content><![CDATA[".$this->searchFixUrls($data['content'])."]]></content>\n".
                        "       <pic>".$this->_fixUrl($this->textToEntities($data['pic']))."</pic>\n".
                    "   </item>\n";
        }
        $output .= "</news>";
        if (!headers_sent()) {
            header("Content-type: application/xml");
        }
        else {
            trigger_error("Module datafeed: XML header cannot be sent. Headers sent !", E_USER_ERROR);
        }
        return $output;
    }


    /**
     * Searches all src="xxx" and href="xxx" variants in the given text
     * and fixe links in it with proper ones.
     *
     * @uses $this->_fixUrl()
     * @param string $content
     * @return string
     */
    function searchFixUrls($content){
        $reg = '@(src=|href=)(["\'])?([\w+_=\/\.\/\:\?&#\-]{0,})["\']?@';
        $replacements = array();
        $m = array();
        $i = 1;
        while(true){
            $m = array();
            if (preg_match($reg, $content, $m)) {
                $_index = ";;;$i;;;";
                $replacements[$_index] = $m[1] . '"' . $this->_fixUrl($m[3]) . '"';
                $content = str_replace($m[0], $_index, $content);
            } else {
                break;
            }
            $i ++;
        }
        // if there is no replacements, return text without changes.
        if(!count($replacements)) {
            return $content;
        }
        // process url replacement in the text.
        foreach ($replacements as $_index => $replace) {
            $content = str_replace($_index, $replace, $content);
        }

        return $content;
    }

    /**
     * Fixes url by transfering it to absolute path
     *
     * @param string $url
     * @return string
     */
    function _fixUrl($url){
        if (!$url) return '';
        $reg =
              "@(?:(http|https|mailto|ftp|)(?:\:\/\/|))" // protocol: http
            . "([\w\.\-]{1,}\:[\w\.\-]{1,}\@)?" // username and password: username:password@
            . "(?:([w0-9_]+)(\.))?" // : www.
            . "([\w\-\.]*)" // site name, but must be checked.
            . "(.*)@"; // other part of url, script path and quary.

        $def_protocol = 'http';
        $m = array();
        if (!preg_match($reg, $url, $m)) {
            return $url;
        }

        //check, if some protocol is set. if it set, then possible, that url is valid.
        if ($m[1]){
            return $url;
        }

        // check if site name is correct.
        if (strpos($m[5], '.') === false){
            $m[5] = $m[4] . $m[5];
            $m[4] = '';
        }
        // Check, if last element: script path or query starts with '/'. if not, add it.
        if ($m[6][0] != '/') {
            $m[6] = '/' . $m[6];
        }

        if ($m[5]) {
            $new_url = "$def_protocol://{$m['2']}{$m['3']}{$m['4']}{$m['5']}{$m['6']}";
        } else {
            $new_url = SITE_URL . $m[6];
        }

        return $new_url;
    }

    /**
     * Convert text to entities and for xml safe utf
     * @access private
     * @return string
    */

    function textToEntities ($input) {
        return validXML($input);
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

        $list = array();
        $list[0] = "---";

        return array();
    }

}