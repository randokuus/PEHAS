<?php
set_time_limit(0);
/*
echo "<!-- \n";
print_r($_POST);
print_r($_GET);
echo "-->\n";

exit();
*/
if (isset($_GET['faction']) && $_GET["faction"] == 'savefile' && (isset($_GET['SID']))) {
    $_COOKIE["PHPSESSID"] = $_GET['PHPSESSID'] ? $_GET['PHPSESSID'] : $_GET['SID'];
    $_COOKIE["USR_SESS_SID"] = $_GET['SID'];
    $_SESSION["USR_SESS_SID"] = $_GET['SID'];
}

/**
 * Modera.net main program file. This script controls all of the site output
 * public variables made available are (also see config.php for other variables and constants:
 * $GLOBALS["site_settings"] - associative array of site settings (DB settings) (admin_email => 'support@modera.net') etc.
 * $GLOBALS["pagedata"] - associative array of current page data (DB content)
 * $GLOBALS["user_logged"]  - boolean true/false if the user is logged in
 * $GLOBALS["user_data"] - logged in user data array(user_ID, users_NAME, user_USERNAME, NOT_USED, user_GROUP_ID);
 * $GLOBALS["user_show"] - boolean true/false, is the current page accessible to the existing logged in user (is he in the allowed group)
 * $GLOBALS["db"] - reference to DB object with created connection
 * $GLOBALS["language"] - currently active language
 * @access public
 */

// ##############################################################
//error_reporting(0);

include("class/config.php");
// redirect to installer/ if site is not configured yet
if (defined('MODERA_CONFIGURED') && !MODERA_CONFIGURED) {
    header('Location: installer/');
    exit();
}

////////////////////////////////////////////////////
//////// DEBUG DEBUG DEBUG DEBUG DEBUG DEBUG ///////
////////////////////////////////////////////////////
/*
    if ($fp_log = fopen(SITE_PATH . "/isic_pic.txt", "a+")) {
        fwrite($fp_log, date("d.m.Y H:i:s") . " (" . $_SERVER["REMOTE_ADDR"] . ", " . $_SERVER["HTTP_USER_AGENT"] . "): -> ");
        fwrite($fp_log, print_r($_GET, true));
        fclose($fp_log);
    }
*/
////////////////////////////////////////////////////
//////// DEBUG DEBUG DEBUG DEBUG DEBUG DEBUG ///////
////////////////////////////////////////////////////

require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
    hokusPokus();
}
else {
    trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");

/**
 * Perform language initialization actions
 *
 * @param string $language input language
 * @global Language $GLOBALS['lan']
 * @global string GLOBALS['language']
 * @global array $GLOBALS['data']
 * @global array $GLOBALS['data_settings']
 * @global array $GLOBALS['site_settings']
 * @global array $GLOBALS['templates_*']
 * @global array $GLOBALS['temp_desc_*']
 * @global Text $GLOBALS['txtf']
 */
function init_language($language)
{
    $GLOBALS['lan'] = new Language($GLOBALS['database'], $language);
    $GLOBALS['language'] = $GLOBALS['lan']->lan();
    load_site_name($GLOBALS['language']);
    $GLOBALS['data'] = $GLOBALS['data_settings'] = $GLOBALS['site_settings'];

    // if array with templates for current language do not exists templates for
    // english language are taken
    if (!$GLOBALS["templates_".$GLOBALS['language']]) {
        $GLOBALS["templates_".$GLOBALS['language']] = $GLOBALS["templates_EN"];
    }

    // if array with template descriptions for current language do not exists
    // english descriptions are taken
    if (!$GLOBALS["temp_desc_".$GLOBALS['language']]) {
        $GLOBALS["temp_desc_".$GLOBALS['language']] = $GLOBALS["temp_desc_EN"];
    }

    $GLOBALS['txtf'] = new Text($GLOBALS['language'], "output");
}

/**
 * Check page on expiration or not published and display error page
 *
 * @param array $pdata
 * @param bool $update_flag
 */
function check_page_availability($pdata, $update_flag = false)
{
    if (!$pdata) {
        redirect('error.php?error=404');
    }

    $now = time();
    if ($now < $pdata['unix_publishing_date']
        || ($pdata['unix_expiration_date'] > 0 && $now > $pdata['unix_expiration_date']))
    {
        // set 'visible' flag in content table to 0
        if ($pdata['visible'] && $update_flag) {
            global $database;

            $database->query("UPDATE `content` SET `visible` = 0 WHERE `language` = ?"
                . " AND `content` = ?", $language, $content);

            // clear menu cache
            $tmp_xsl = new xslprocess();
            $tmp_xsl->deleteCachedPage('menuxml', false);
            unset($tmp_xsl);
        }

        if ($now < $pdata['unix_publishing_date']) {
            redirect('error.php?error=unpublished&published_date='
                . $pdata['unix_publishing_date']);
        } else {
            redirect('error.php?error=expired');
        }
    }
}

//
// extract some request parameters
//
foreach (array('content', 'print', 'nocache', 'username', 'password', 'login'
    , 'login_check', 'language', 'search_query', 'send', 'code', 'search_tmpl') as $v)
{
    if (array_key_exists($v, $_GET)) $$v = $_GET[$v];
    if (array_key_exists($v, $_POST)) $$v = $_POST[$v];
}
/*
if (false !== strpos($_SERVER["PHP_SELF"], "index.php")) {
    $_SERVER["PHP_SELF"] = "/";
}
else {
    $_SERVER["PHP_SELF"] = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/")+1);
}
*/
$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0 , strrpos($_SERVER['PHP_SELF'], '/') + 1);
// check WAP
if (false !== strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml')) {
    redirect(SITE_URL . "/wap/");
}

$sq = new sql;
$db = new db;
$db->connect();

// new database object (wrapper for the old one)
$sq->con = $db->con;
$database = new Database($sq);

// loading globally site settings
load_site_settings($database);

//admin Email
if (isset($GLOBALS["site_settings"]["admin_email"])) {
    $GLOBALS["site_admin"] = $GLOBALS["site_settings"]["admin_email"];
}
//admin name
if (isset($GLOBALS["site_settings"]["admin"])) {
    $GLOBALS["site_admin_name"] = $GLOBALS["site_settings"]["admin"];
}

// debug level
if (isset($GLOBALS["site_settings"]["debuglevel"])) {
    if ($GLOBALS["site_settings"]["debuglevel"] == 1) {
        $GLOBALS["modera_debug"] = true;
    }
    else if ($GLOBALS["site_settings"]["debuglevel"] == 2) {
        $GLOBALS["modera_debug"] = true;
        $GLOBALS["modera_debugsql"] = true;
    }
    else if ($GLOBALS["site_settings"]["debuglevel"] == 3) {
        $GLOBALS["modera_debug"] = false;
        $GLOBALS["modera_debugsql"] = false;
    }
}

// Set Locale
if (isset($GLOBALS["site_settings"]["sitelocale"])) {
    moderaSetLocale(LC_ALL, $GLOBALS["site_settings"]["sitelocale"]);
}

// Site cache
if (isset($GLOBALS["site_settings"]["sitecache"])) {
    if ($GLOBALS["site_settings"]["sitecache"] == 1) {
        $GLOBALS["conf_tpl_cache_level"] = TPL_CACHE_NOTHING;
        $GLOBALS["conf_tpl_cache_module"] = FALSE;
    }
    else if ($GLOBALS["site_settings"]["sitecache"] == 2) {
        $GLOBALS["conf_tpl_cache_level"] = TPL_CACHE_NOTHING;
        $GLOBALS["conf_tpl_cache_module"] = TRUE;
    }
    else if ($GLOBALS["site_settings"]["sitecache"] == 3) {
        $GLOBALS["conf_tpl_cache_level"] = TPL_CACHE_ALL;
        $GLOBALS["conf_tpl_cache_module"] = TRUE;
        if (isset($GLOBALS["site_settings"]["sitecache"])) {
            $GLOBALS["conf_tpl_cache_ttl"] = $GLOBALS["site_settings"]["cachetime"];
        }
    }
}

// check is site active, if not, check if logged in the admin
if (!$GLOBALS["site_settings"]["active"]) {
    $asess = new Session;
    if ($asess->sid == false) {
        redirect("error.php?error=999");
    }
}

/**
 * If content is defined, retrive all info about it from Database.
 */
if ($content) {

    // init language object and perform related actions
    init_language($pagedata['language']);

    //
    // language parameter specified by user is ignored hire and is taken from
    // content record of requested page
    //

    $pagedata = $database->fetch_first_row('
        SELECT
            `content`
            , `template`
            , `title`
            , `text`
            , `pics`
            , `files`
            , `redirect`
            , `redirectto`
            , `login`
            , `logingroups`
            , `loginusertypes`
            , `module`
            , `mpath`
            , `language`
            , `uri_alias`
            , `visible`
            , `public`
            , UNIX_TIMESTAMP(`publishing_date`) AS `unix_publishing_date`
            , UNIX_TIMESTAMP(`expiration_date`) AS `unix_expiration_date`
        FROM
            `content`
        WHERE
            `pending` \!= ?
            AND `content` = ?
        ORDER BY
            `mpath` ASC, `zort` ASC
        ', MODERA_PENDING_CREATION, $content);

    check_page_availability($pagedata, true);

    // check, if parents are visible. If some parent is not visible, show error page.
    if ('' != $pagedata['mpath']) {
        $parents = str_replace('.', "', '", $pagedata['mpath']);
        $invisible_parent = $database->fetch_first_row("
            SELECT
                `content`
                , UNIX_TIMESTAMP(`publishing_date`) AS `unix_publishing_date`
                , UNIX_TIMESTAMP(`expiration_date`) AS `unix_expiration_date`
            FROM
                `content`
            WHERE
                `content` IN ('!')
                AND (
                    `visible` = 0
                    OR `pending` = ?
                )
            LIMIT 1", $parents, MODERA_PENDING_CREATION);

        if ($invisible_parent) {
            check_page_availability($invisible_parent);
            redirect('error.php?error=404');
        }
    }

    // Check if it's a redirect page
    if ($pagedata["redirect"]) {

        if (preg_match('/^(http:\/\/)|(https:\/\/)|(ftp:\/\/)/i', $pagedata["redirectto"]))  {
            redirect($pagedata["redirectto"]);
        }
        else {
            //if (substr($pagedata["redirectto"],0,1) == "/") $pagedata["redirectto"] = substr($pagedata["redirectto"],1);
            //Header("Location: " . SITE_URL . "/" . $pagedata["redirectto"] . $radd);

            if (false !== strpos(substr(SITE_URL, 8), '/')) {
                $engine_url = substr(SITE_URL, 0, strpos(SITE_URL, "/", 8));
            }
            else {
                $engine_url = SITE_URL;
            }

            redirect($engine_url . $pagedata["redirectto"]);
        }
        exit;
    }

    $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/"
        . $GLOBALS["templates_".$language][$data["template"]][2][$pagedata["template"]]
        . ".html";

} else {
    // no content supplied
    // init language object
    init_language(defined('LANGUAGE_OVERRIDE') ? LANGUAGE_OVERRIDE : $language);
    $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "first.html";
}

// ##############################################################
// init main variables

$intro = $database->fetch_first_value('SELECT `intro` FROM `intro` WHERE `language` = ?'
    , $language);

$data["info"] = $intro;
$GLOBALS["site_settings"]["info"] = $intro;

$GLOBALS["userlogin"] = (bool) $data_settings["userlogin"];
$GLOBALS["loginform"] = (bool) $data_settings["loginform"];

// Check if the page requires authorisation
//if ($GLOBALS["userlogin"] == true || $pagedata["login"] == "1" || $GLOBALS["loginform"] == true) {

// INIT user object
$usr = new user();
if ($usr->sid && $_REQUEST["logout"] == "true") {
    $usr->logOut();
}


if ($GLOBALS["userlogin"] || $pagedata["login"] || $GLOBALS["loginform"]) {
    if ($login == "true" && $username && $password) {
        $status = $usr->setSession($username, $password);
    }
}

$txt_usr = new Text($language, "module_user");

$GLOBALS["user_logged"] = $usr->status;
$usr->returnUser();
$GLOBALS["user_data"] = array(
    $usr->user,
    $usr->user_name,
    $usr->username,
    $usr->company,
    $usr->group,
    $usr->groups,
    $usr->user_type,
    $usr->user_code,
    $usr->user_email,
    $usr->active_school_id,
    $usr->children_list,
);

$GLOBALS["user_show"] = $usr->isAuthorisedGroup() && $usr->isAuthorisedUserType();
if ($usr->skin) {
    $GLOBALS["user_skin"] = $usr->skin;
} else {
    $GLOBALS["user_skin"] = "skin_blue";
}

$GLOBALS["user_rightcolhidden"] = $usr->rightcolhidden;


//
// Sajax based ajax handler
//

require_once(SITE_PATH . '/class/Sajax.php');
$sajax_remote_uri = SITE_URL . '/';

// all functions defined in the class/ajax_functions.php will be
// exported via sajax_export() call
$old_functions = get_defined_functions();
require_once(SITE_PATH . '/class/ajax_functions.php');
$new_functions = get_defined_functions();
$ajax_functions = array_diff($new_functions['user'], $old_functions['user']);

sajax_init();
foreach ($ajax_functions as $function) {
    sajax_export($function);
}

// following variables are no more needed
unset($old_functions, $new_functions, $ajax_functions, $function);

// handle ajax calls
sajax_handle_client_request();

// if print page ?
if ($print == "true") {
    $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "print_"
        . $GLOBALS["templates_".$language][$data["template"]][2][$pagedata["template"]]
        . ".html";
    if (!file_exists(SITE_PATH . "/" . $template)) {
        $template = $GLOBALS["templates_".$language][$data["template"]][1]
            . "/" . "print_content.html";
    }
}

if (strlen($_GET["search_query"]) || strlen($_POST["search_query"])) {
    $search = true;

    if ($print == "true") {
        if ($search_tmpl && false === strpos($search_tmpl, '../')
            && file_exists(SITE_PATH . "/" . $GLOBALS["templates_".$language][$data["template"]][1] . "/print_" . $search_tmpl))
        {
            $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/print_" . $search_tmpl;
        }
        else {
            $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "print_content.html";
        }
    }
    else {
        if ($search_tmpl && false === strpos($search_tmpl, '../')
            && file_exists(SITE_PATH . "/" . $GLOBALS["templates_".$language][$data["template"]][1] . "/" . $search_tmpl))
        {
            $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . $search_tmpl;
        }
        else {
            $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "search.html";
        }
    }
}

if (strlen($_GET['search_tag']) || strlen($_POST['search_tag'])) {
    $searchtag = true;

    if ($print == 'true' && file_exists($GLOBALS["templates_".$language][$data["template"]][1]
        . "/" . "print_tagsearch.html"))
    {
        $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "print_tagsearch.html";

    } else {
        $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "tagsearch.html";
    }

    include(SITE_PATH . '/class/module.tagsearch.class.php');
}

// ##############################################################

// build a template
$tpl = new template;
$tpl->startTimer('page');

// set page instance for correct caching operations
$inst = $_SERVER['PHP_SELF'].url();
$tpl->setInstance($inst);
$usecache = checkParameters();

// check if the page is cached
if ($tpl->isCached($template) && $usecache == true) {
    // do nothing
}
else {

    // get uri_alias for current page
    if ($pagedata['uri_alias']) {
        $niceUrl = $pagedata['uri_alias'];
        if (false !== strpos($niceUrl, '|')) {
            $niceUrl = substr($niceUrl, 0, strpos($niceUrl, '|'));
        }

        $niceUrl = base_site_path() . ltrim($niceUrl, '/');

    } else {
        $niceUrl = '';
    }

    $tpl->addDataItem("NICEURL", $niceUrl);

    $tpl->addDataItem('AJAX_JS', '<script type="text/javascript">/* <![CDATA[ */'
        . sajax_get_javascript() . '/* ]]> */</script>');

    $tpl->addDataItem("SELF", htmlspecialchars($_SERVER['PHP_SELF']));
    if ($_SERVER['QUERY_STRING']) {
        $tpl->addDataItem("SELFFULL", htmlspecialchars($_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']));
    }
    else {
        $tpl->addDataItem("SELFFULL", htmlspecialchars($_SERVER['PHP_SELF']));
    }
    if ($_SERVER['QUERY_STRING']) {
        $tpl->addDataItem("PRINTURL", htmlspecialchars($_SERVER['PHP_SELF'] . "?"
            . ereg_replace("&print=([^&])*", "", $_SERVER['QUERY_STRING']) . "&print=true"));
    }
    else {
        $tpl->addDataItem("PRINTURL", htmlspecialchars($_SERVER['PHP_SELF'] . "?print=true"));
    }
    $tpl->addDataItem("LANGUAGE", $language);
    $tpl->addDataItem("TOP", $txtf->display("top"));
    $tpl->addDataItem("SEARCH", $txtf->display("search"));
    $tpl->addDataItem("PRINT", $txtf->display("print"));

    $tpl->addDataItem("FOOTER", $txtf->display("footer"));
    $tpl->addDataItem("FIRSTPAGE", $txtf->display("firstpage"));

    $tpl->addDataItem("SITEMAP", $txtf->display("sitemap"));
    $tpl->addDataItem("SKIN", $GLOBALS["user_skin"]);
    $tpl->addDataItem("RIGHTCOLSTATUS", $GLOBALS["user_rightcolhidden"] ? "none" : "block");

    //$tpl->addDataItem("TOPICS", $txtf->display("topics"));

    $sitemap_content_id = $database->fetch_first_value("SELECT `content` FROM `content`"
        . " WHERE `template` = 6 AND `language` = ? LIMIT 1", $language);
    if ($sitemap_content_id) {
        $tpl->addDataItem("SITEMAPURL", htmlspecialchars($_SERVER['PHP_SELF'] . "?content="
            . $sitemap_content_id));
        unset($sitemap_content_id);

    } else {
        $tpl->addDataItem("SITEMAPURL", "#");
    }

    if ($pagedata["title"] != "" && !$search) {
        $tpl->addDataItem("PAGETITLE", htmlspecialchars($data["name"] . " / "
            . $pagedata["title"]));

    } else if (!$search) {
        // must be the first page
        $tpl->addDataItem("INFO", $data["info"]);
        $tpl->addDataItem("PAGETITLE", htmlspecialchars($data["name"]));

    } else {
        $tpl->addDataItem("PAGETITLE", htmlspecialchars($data["name"]) . " / "
            . $txtf->display("search_topic"));
    }

    $tpl->addDataItem("COPY", "&copy; " . date("Y") . " " . htmlspecialchars($data["name"]));
    $tpl->addDataItem("DATE", "<b>".$txtf->display("day_".date("w")) . "</b>, "
        . date("j") . ". " . $txtf->display("month_" . date("n")) . " " . date("Y"));
    $tpl->addDataItem("DATE_DAY", $txtf->display("day_".date("w")));
    $tpl->addDataItem("DATE_DATE", date("j") . ". " . $txtf->display("month_" . date("n"))
        . " " . date("Y"));

    /**##################
     * GENERATE SITE PATH
     */
    //$sp_separator = "&nbsp;&raquo;&nbsp;";
    $sp_separator = "&nbsp;";
    $sitepath = "<a href=\"$_SERVER[PHP_SELF]\">" . htmlspecialchars($data["name"]) . "</a>";

    if ($content) {

        if ($pagedata["mpath"]) {
            $result = &$database->query('
                SELECT
                    `title`
                    , `content`
                FROM
                    `content`
                WHERE
                    `language` = ?
                    AND `content` IN ( ?@ )
                ORDER BY
                    `mpath` ASC
                ', $language, explode('.', $pagedata['mpath']));

            while ($data = $result->fetch_assoc()) {
                $sitepath .= "$sp_separator<a href=\"{$_SERVER['PHP_SELF']}?content={$data['content']}\">"
                    . htmlspecialchars($data["title"]) . "</a>";
            }
        }

        $sitepath .= $sp_separator . htmlspecialchars($pagedata["title"]);
    }

    if ($search) {
        $sitepath .= $sp_separator . $txtf->display("search_topic") . "&nbsp;-&nbsp;<i>"
            . htmlspecialchars($search_query) . "</i>";

    } else if ($searchtag) {
        $sitepath .= $sp_separator . $txtf->display("search_topic") . "&nbsp;-&nbsp;<i>"
            . htmlspecialchars($_GET['search_tag']) . "</i>";
    }

    $tpl->addDataItem("SITEPATH", $sitepath);

    // #### LOGO
    if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/SITELOGO.gif")) {
        $tpl->addDataItem("LOGO", $GLOBALS["directory"]["upload"] . "/SITELOGO.gif");
    }
    else if (file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/SITELOGO.jpg")) {
        $tpl->addDataItem("LOGO", $GLOBALS["directory"]["upload"] . "/SITELOGO.jpg");
    }
    else {
        $tpl->addDataItem("LOGO", $GLOBALS["directory"]["img"] . "/1.gif");
    }

    // #### SITE MENU
    include(SITE_PATH . "/class/menu.include.php");

    // #### PAGE CONTENTS
    if ($content && !$search) {
        $tpl->addDataItem("TITLEENC", urlencode($pagedata["title"]));
        $tpl->addDataItem("TITLE", htmlspecialchars($pagedata["title"]));

        // begin login check
        if (($pagedata["login"] != "1") || ($pagedata["login"] == "1" && $GLOBALS["user_logged"])) {        //replaced user_logged with _show
            if ($pagedata["login"] == "1" && !$GLOBALS["user_show"]) {
                $txtu = new Text($language, "module_user");
                if ($txtu->display("message_no_access") != "") {
                    $tpl->addDataItem("TEXT", $txtu->display("message_no_access"));
                }
                else {
                    $tpl->addDataItem("TEXT", "Active user has insufficient rights to view the page");
                }
            }
            else {
                $tpl->addDataItem("TEXT", $pagedata["text"]);
            }
        }
        // not logged in, display message
        else if ($pagedata["login"] == "1" && !$GLOBALS["user_logged"]) { // replaced user_logged with show
            $txtu = new Text($language, "module_user");
            $tpl->addDataItem("TEXT", $txtu->display("message"));
        }
        // end login check
    }
    else if ($search) {
        // #### SEARCH RESULTS
        include(SITE_PATH . "/class/search.include.php");
    }
}

// fire pageLoad event
$em =& EventManager::instance();
$em->fire("pageLoad", array(
    "remote_addr" => $_SERVER["REMOTE_ADDR"],
    "http_host" => $_SERVER['HTTP_HOST'],
    "request_uri" => $_SERVER['REQUEST_URI'],
    "http_referer" => $_SERVER['HTTP_REFERER'],
    "http_user_agent" => $_SERVER['HTTP_USER_AGENT'],
    "session_id" => $_COOKIE['PHPSESSID'],
    "admin_session_id" => $_COOKIE['ADM_SESS_ID'],
    "user_id" => $user_data[0],
    "language" => $GLOBALS["language"],
    "page_title" => $pagedata["title"],
    "search_query" => $search_query,
    "is_print" => $print ? true : false,
));
unset($em);

// ##############################################################

$result = $tpl->parse($template);

if (defined('MODERA_NOWEBCACHE') && MODERA_NOWEBCACE) {
    header('Expires: Thu, 19 Nov 1981 08:50:00 GMT');
    header('Cache-control: no-cache');
    header('Pragma: no-cache');
}

echo $result;

// general statistics
if ((defined('MODERA_INFO') && MODERA_INFO == true) || !defined('MODERA_INFO')) {
    echo "<!-- modera.net v" . MODERA_VERSION . " -->\n";
}
if ((isset($GLOBALS["modera_debug"]) && $GLOBALS["modera_debug"] == true) || !isset($GLOBALS["modera_debug"])) {
    if (is_array($GLOBALS["caching"]) && sizeof($GLOBALS["caching"]) > 0) $md_cache = " (module specific caching in use)";
    if ($tpl->cacheUsed())
      echo "<!-- report: general cache used $md_cache -->\n";
    else
      echo "<!-- report: general cache not used (".round($tpl->showTimer('parse'), 5).") $md_cache -->\n";
    $tpl->stopTimer('page');
      echo "<!-- full page: ".round($tpl->showTimer('page'), 5) . " -->\n";
}
$db->disconnect();
