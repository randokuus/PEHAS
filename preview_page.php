<?php
/**
 * Modera.net admin preview page functionality. Page details are given by means of _POST parameters.
 * when _POST is empty, the program will exit.
 * @access public
 */

// ##############################################################
error_reporting(0);
if (@sizeof($_POST) == 0 && !isset($_GET['changeset'])) exit;

include_once("class/config.php");
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
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Versioning.php");

// ##############################################################

$sq = new sql;
$db = new db;
$db->connect();

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

if (isset($_POST["active_language"])) {
	$language = $_POST["active_language"];
}

// init language object
$lan = new Language($GLOBALS['database'], $language);
$language = $lan->lan();
$GLOBALS["language"] = &$language;
load_site_name($language);
$data = $data_settings = $GLOBALS['site_settings'];

$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
	$GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
	$GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

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

//
// check is the page active, if not, check if logged in the admin
//

$asess = new Session;
$logged = $asess->returnID();
$user = $asess->returnUser();
$group = $asess->group;

if (!$logged) {
	redirect("error.php?error=401");
}

/**
 * If 'changeset' parameter is set, then retrive all data from Versioning.
 */
if ($_GET['changeset']) {
    $versioning =& new Versioning($database);
    $version_data = $versioning->getRawVersion((int)$_GET['changeset']);

    if ($version_data && $version_data['object_id']) {
        $_POST = $version_data['object_data'];
        $_POST['node_type'] = $version_data['object_type'];
        $_POST['id'] = $version_data['object_id'];

        $perm_group = $_POST['perm_group'];
        $perm_other = $_POST['perm_other'];
        $module = $_POST['module'];

        $_POST['perm_group'] = null;
        $_POST['perm_other'] = null;

        foreach (split(',', $perm_group) as $key => $val) {
            if ($val) {
                $_POST['perm_group'][] = $key;
            }
        }
        foreach (split(',', $perm_other) as $key => $val) {
            if ($val) {
                $_POST['perm_other'][] = $key;
            }
        }
        foreach (split(';', $module) as $val) {
            list($key, $val) = split('=', $val);
            $_POST['module_' . $key] = $val;
        }
    } else {
        exit();
    }
}

// handle preview_user
if (isset($_POST["preview_user_id"])) {
    $row = $database->fetch_first_row("
        SELECT
            `user`
            , `name`
            , `username`
        FROM
            `module_user_users`
        WHERE
            `user` = ?"
        , $_POST["preview_user_id"]);

    if ($row) {
        $user_data = array($row["user"], $row["name"], $row["username"]);
    }
}

$perm = new Rights($group, $user, "content", true);

// ##############################################################
// init main variables
$url_comps = parse_url(SITE_URL);
$_SERVER["PHP_SELF"] = $url_comps['path'] . '/';

if(isset($_POST['news_content_id'])){


    $content            = $_POST["news_content_id"];
    $_GET['content']    = $content;
    $_GET['articleid']  =
    $_POST['articleid'] = $_POST['id'];
    $tmp = $_POST;
    $_POST = array('preview_vars'=>$tmp);
    unset($tmp);
    $pagedata = $database->fetch_first_row("SELECT * FROM `content`
                                            WHERE `content` = ?", $content);


}else{

$pagedata = $_POST;
$content = $_POST["id"];
$_GET["content"] = $content;

// retrive mpath for current page
if ("content" == $_POST["node_type"]) {
    if ($_POST["id"]) {
        $pagedata["mpath"] = $database->fetch_first_value("SELECT `mpath` FROM `content`"
            . " WHERE `content` = ?", $_POST["id"]);
    } else if ($_POST["parent_id"]) {
        $pagedata["mpath"] = $database->fetch_first_value("SELECT `mpath` FROM `content`"
            . " WHERE `content` = ?", $_POST["parent_id"]);
        $pagedata["mpath"] .= ($pagedata["mpath"] ? "." : "") . $_POST["parent_id"];
    }
}

$module_data = array();
if (@is_array($_POST) && @sizeof($_POST) > 0) {
	while (list($key, $val) = each($_POST)) {
		if (substr($key, 0, 7) == "module_") {
			$module_data[] = substr($key, 7) . "=" . $val;
		}
	}
	reset($_POST);
}
$pagedata["module"] = @join(";", $module_data);
}


// check admin access privileges
if ($id) {
	$perm->Access(null, $pagedata["id"], "m", null);
}

if ($pagedata["do"] == "update" && $pagedata["id"] && $pagedata["text"] == "") {
    if (MODERA_PENDING_CHANGES == $database->fetch_first_value('SELECT `pending`'
        . ' FROM `content` WHERE `content` = ?', $pagedata['id']))
    {
        $versioning = new Versioning($database);
        $vdata = $versioning->getCurrentData('content', $pagedata['id']);
        $pagedata['text'] = $vdata['text'];

    } else {
        $pagedata["text"] = $database->fetch_first_value("SELECT `text` FROM `content`"
            . " WHERE `content` = ?", $pagedata["id"]);

    }
}

$template = $GLOBALS["templates_".$language][$data["template"]][1] . "/"
    . $GLOBALS["templates_".$language][$data["template"]][2][$pagedata["template"]]
    . ".html";

// if print page ?
if ($_GET["print"] == "true") {
    $template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . "print_"
        . $GLOBALS["templates_".$language][$data["template"]][2][$pagedata["template"]]
        . ".html";
    if (!file_exists(SITE_PATH . "/" . $template)) {
        $template = $GLOBALS["templates_".$language][$data["template"]][1]
            . "/" . "print_content.html";
    }
}

// ##############################################################

// build a template
$tpl = new template;
$tpl->startTimer('page');

// set page instance for correct caching operations
$inst = $_SERVER['PHP_SELF'].url();
$tpl->setInstance($inst);
$usecache = false;
$tpl->conf_tpl_cache_level = TPL_CACHE_NOTHING;
$tpl->conf_tpl_cache_module = false;

// check if the page is cached
if ($tpl->isCached($template) && $usecache == true) {
	// do nothing
}
else {
    // if ajax flag set for current page or there are no page configuration
    // loaded (first page) include sajax js code
    if (is_null($pagedata) || $pagedata['ajax']) {
        $tpl->addDataItem('AJAX_JS', '<script type="text/javascript">'
            . sajax_get_javascript() . '</script>');
    }

	$tpl->addDataItem("SELF", $_SERVER['PHP_SELF']);
	if ($_SERVER['QUERY_STRING']) {
		$tpl->addDataItem("SELFFULL", $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
	}
	else {
		$tpl->addDataItem("SELFFULL", $_SERVER['PHP_SELF']);
	}

	if ($_SERVER['QUERY_STRING']) {
		$tpl->addDataItem("PRINTURL", $_SERVER['PHP_SELF'] . "?" . ereg_replace("&print=([^&])*", "", $_SERVER['QUERY_STRING']) . "&print=true");
	}
	else {
		$tpl->addDataItem("PRINTURL", $_SERVER['PHP_SELF'] . "?print=true");
	}
	$tpl->addDataItem("LANGUAGE", $language);
	$tpl->addDataItem("TOP", $txtf->display("top"));
	$tpl->addDataItem("SEARCH", $txtf->display("search"));
	$tpl->addDataItem("PRINT", $txtf->display("print"));

	$tpl->addDataItem("FOOTER", $txtf->display("footer"));
	$tpl->addDataItem("FIRSTPAGE", $txtf->display("firstpage"));

	$tpl->addDataItem("SITEMAP", $txtf->display("sitemap"));

    $sitemap_content_id = $database->fetch_first_value("SELECT `content` FROM `content`"
        . " WHERE `template` = 6 AND `language` = ? LIMIT 1", $language);
    if ($sitemap_content_id) {
        $tpl->addDataItem("SITEMAPURL", $_SERVER['PHP_SELF'] . "?content=" . $sitemap_content_id);
        unset($sitemap_content_id);

    } else {
        $tpl->addDataItem("SITEMAPURL", "#");
    }

	if ($pagedata["title"] != "" && !$search) {
		$tpl->addDataItem("PAGETITLE", htmlspecialchars($data["name"] . " / " . $pagedata["title"]));
	}

	$tpl->addDataItem("COPY", "&copy; " . date("Y") . " " . htmlspecialchars($data["name"]));
	$tpl->addDataItem("DATE", "<b>".$txtf->display("day_".date("w")) . "</b>, " . date("j") . ". " . $txtf->display("month_" . date("n")) . " " . date("Y"));
	$tpl->addDataItem("DATE_DAY", $txtf->display("day_".date("w")));
	$tpl->addDataItem("DATE_DATE", date("j") . ". " . $txtf->display("month_" . date("n")) . " " . date("Y"));

    /**##################
     * GENERATE SITE PATH
     */
    $sp_separator = "&nbsp;&raquo;&nbsp;";
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
                $sitepath .= "$sp_separator<a href=\"$_SERVER[PHP_SELF]\"?content=\"$data[content]\">"
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
    } else {
        $tpl->addDataItem("TITLEENC", urlencode($pagedata["title"]));
        $tpl->addDataItem("TITLE", htmlspecialchars($pagedata["title"]));
        $tpl->addDataItem("TEXT", $pagedata["text"]);
    }
}

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
