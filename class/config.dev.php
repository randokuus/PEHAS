<?php
/**
 * Modera.net configuration file
 * @access public
 * @package modera_net
 * @version 2.0
 */

ini_set("session.use_cookies", "1");
ini_set("session.cache_limiter", "nocache");
ini_set('error_reporting', E_ERROR | E_PARSE);
ini_set("session.cache_expire", "1800");
ini_set("session.gc_maxlifetime", "86400");
ini_set('auto_detect_line_endings',TRUE);

define("MODERA_CONFIGURED", true);

// ##### CHANGE THESE MODERA.NET PARAMETERS #####

define("MODERA_VERSION", "4.72.1"); // modera.net platform version
define("MODERA_PRODUCT", "extranet-pro"); //modera product, webmaster, intranet, extranet
define("ERRORS_DEFINE", serialize(array(E_ERROR, E_WARNING, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_USER_ERROR, E_USER_WARNING)));
define("ERRORS_LOG", true); // Log errors ?
define("ERRORS_EMAIL_ADMIN", false); // Email admin in case of error ?
define("MAILLOG", true); // flag for logging all the outgoing emails into db

define("COOKIE_URL", "isic.vels.dev.modera.net"); // domain where site is running, for example "www.mysite.com"
define("COOKIE_SECURE", "0"); // secure site or not, boolean value [true|false]
define("USER_AUTH_COOKIE_LENGTH", 3600); // length of a front-end user auth-cookie in seconds (default is one year)

define("MODERA_INFO", true); // display modera.net version and other info in html comment at the end of output
define("MODERA_NOWEBCACHE", true); // prevent pages from being cached, recommended as true
define("MODERA_ENABLE_SITEMAP", true); // enable or disable xml sitemap (/sitemap.php)
define("IMAGE_CONVERT", "/usr/bin/convert"); // location to convert binary for image manipulation

define("MODERA_KEY", "8FE4-E84A-3E3C-6C45-56EA-5AE4-162F-5BC0"); // modera product key

// Database access properties
define("DB_TYPE", "mysql");  // DB type, mysql supported
define("DB_USER", "vels"); // DB username
define("DB_PASS", "Ww7GNJLRLAPhCv9B"); // DB password
define("DB_HOST", "127.0.0.1"); // host to connect to, localhost by default
define("DB_DB", "vels_isic"); // DB name to connect to

//full path, where the site is installed
define("SITE_PATH", "/home/vels/sites/isic.htdocs"); // no trailing slash - /var/www/www.mysite.com/site
// URL where the site is running
define("SITE_URL", "http://isic.vels.dev.modera.net"); // no trailing slash - http://www.mysite.com/site

// ISIC-specific constants
define("ISIC_PATH", SITE_PATH . "/cache/isic/"); // path to generated isic-files
define("SSH_PATH", SITE_PATH . "/cron/.ssh/"); // path to ssh-keys and host-file
//define("ID_FILE", SSH_PATH . "identity"); // identity-file name
define("ID_FILE", SSH_PATH . "id_rsa"); // identity-file name
define("HOST_FILE", SSH_PATH . "known_hosts"); // known_hosts filename
define("TARGET_USERNAME", 'isic'); // username for scp-server
//define("TARGET_HOSTNAME", '213.180.22.122'); // hostname of scp-server
define("TARGET_HOSTNAME", '213.35.252.226'); // hostname of scp-server
define("TARGET_PATH", './share/isic2tryb/'); // target path for files
define("SOURCE_USERNAME", 'isic'); // username for scp-server
//define("SOURCE_HOSTNAME", '213.180.22.122'); // hostname of scp-server
define("SOURCE_HOSTNAME", '213.35.252.226'); // hostname of scp-server
//define("SOURCE_PATH", './share/tryb2isic/'); // source path for files
//define("SOURCE_PATH", './share/test/'); // source path for files
define("SOURCE_PATH", './share/temp/'); // source path for files
define("SONIC_PATH", SITE_PATH . "/sonic/IN/"); // path to sonic data-files

//define("DEVELOPERS_EMAILS", "dmitri.peterson@modera.net"); // mail dumps and exceptions to this comma-separated list of email addresses
define("DEVELOPERS_EMAILS", "martin.vels@modera.net"); // mail dumps and exceptions to this comma-separated list of email addresses
define("TESTERS_EMAILS", DEVELOPERS_EMAILS . ",meeli.pallin@minukool.ee,liisa.alop@minukool.ee");  // mail captures are sent to this comma-separated list of email addresses
define("LOG_PROGRAM_FLOW", true);  // whether should the program flow be logged or not (warning!! turning it on makes the system 10 times slower)

define("SYSTEM_USER", "system_khs"); // system-user for all the activities done automatically (expired card deactivation etc.)
define("PANGALINK_MOCK", 1); // mocking pangalink behavior for testing purposes
define("PANGALINK_TEST", 1); // for testing purposes of pangalink behavior
define("NEWSLETTER_REPORT_FOLDER_NAME", "newsletters"); // saving newsletter orders csv reports to that folder in upload directory (may not contain slashes)
define("CAMPAIGN_REPORT_FOLDER_NAME", "campaign"); // saving campaign csv reports to that folder in upload directory (may not contain slashes)
//define('AUTH_CHECK_USER_ACCOUNT_RECORD', 1); // checking if user record exists before doing external authentication

define('UNITED_TICKETS_API_URL', 'http://server.unitedtickets.ee/viipe/box?');

// path to languages directory
define("LANGUAGES_PATH", "cache"); // relative path to SITE_PATH (no trailing slash)

// settings for diffrent translator drivers (passed to TranslatorCompiler constructor)
$GLOBALS["translator_settings"] = array(
    "gettext" => array('msgfmt' => '/usr/bin/msgfmt'),
);

// debug status, can be changed from admin/settings
$GLOBALS["modera_debug"] = true;
// debug all SQL queries, can be changed from admin/settings
$GLOBALS["modera_debugsql"] = false;

// site admin name, can be changed from admin/settings
$GLOBALS["site_admin_name"] = "Administrator";
// site admin email, can be changed from admin/settings
$GLOBALS["site_admin"] = "support@modera.net";

// #########################################

$GLOBALS["directory"] = array(
    "upload" => "upload",
    //"imgupload" => "pictures",
    "tmpl" => "tmpl",
    "img" => "img",
    "css" => "lib",
    "object" => "class",
    "bunch_upload" => SITE_PATH . "/cache/bunch_upload",
);

$GLOBALS["editor"] = true;

// permission standards
define("MODERA_PERM_GROUP", serialize(array(
    "a" => true, // a - add
    "m" => true, // m - modify, modifying an existing entry
    "d" => true, // d - delete, delete an entry
)));

define("MODERA_PERM_OTHER", serialize(array(
    "a" => false, // a - add
    "m" =>false, // m - modify, modifying an existing entry
    "d" => false, // d - delete, delete an entry
)));

define("MODERA_PERM_MODULE_GROUP", serialize(array(
    "a" => false, // a - add
    "m" => false, // m - modify, modifying an existing entry
    "d" => false, // d - delete, delete an entry
)));

define("MODERA_PERM_FILE_GROUP", serialize(array(
    "a" => true, // a - add
    "m" => false, // m - modify, modifying an existing entry
    "d" => false, // d - delete, delete an entry
)));


$GLOBALS["templates_EN"] = array(
    "1" => array(
        "Web", "tmpl",
        array(
            "1" => "content",
            "2" => "content_wmenu",
            "3" => "content_wnews",
            "4" => "content_news",
            //"5" => "content_feedbackform",
            "6" => "content_sitemap",
            "8" => "content_gallery"
        )
        )
);

$GLOBALS["temp_desc_ET"] = array(
    "1" => array(
        "1" => "Sisu",
        "2" => "Sisu men&uuml;&uuml;ga",
        "3" => "Sisu l&uuml;hiuudistega",
        "4" => "Uudised",
        //"5" => "Tagasiside vorm",
        "6" => "Serverikaart",
        "8" => "Galerii",
    )
);

$GLOBALS["temp_desc_EN"] = array(
    "1" => array(
        "1" => "Content",
        "2" => "Content with menu",
        "3" => "Content with news",
        "4" => "News",
        //"5" => "Feedback form",
        "6" => "Server map",
        "8" => "Gallery",
    )
);

$GLOBALS["modules"] = array(
    "user",
    "news",
    "xslprocess",
    //"form", // no longer used
    //"sitemap", // no longer used
    "gallery",
    "calendar",
    "redirect",
    "search",
    "datafeed",
    "tagcloud",
);

$GLOBALS["modules_sub"] = array();

// ###############################################

// definition of cache type constants
define('TPL_CACHE_NOTHING', 'TPL_CACHE_NOTHING');
define('TPL_CACHE_ALL', 'TPL_CACHE_ALL');
define('TPL_CACHE_FILE', 'TPL_CACHE_FILE');

// definition of warning levels
define('TPL_WARN_IGNORE', 'TPL_WARN_IGNORE');
define('TPL_WARN_ALL', 'TPL_WARN_ALL');
define('TPL_WARN_CRITICAL', 'TPL_WARN_CRITICAL');

// ###############################################

// Template configuration variables
$GLOBALS["conf_tpl_cache_level"]  = TPL_CACHE_NOTHING; //TPL_CACHE_ALL;      // level of the cache for entire page
$GLOBALS["conf_tpl_cache_module"]  = TRUE; // cache module specific subtemplates regardless of general cache
$GLOBALS["conf_tpl_cache_ttl"]  = 1440;               // TTL of cache in minutes, for page cache only
$GLOBALS["conf_tpl_cache_type"]   = TPL_CACHE_FILE;     // cache to file or db
$GLOBALS["conf_tpl_cache_use_instance"] = TRUE;               // use page instance
$GLOBALS["conf_tpl_cache_use_noncachable"] = FALSE;               // allow noncachable items
$GLOBALS["conf_tpl_cache_path"]  = SITE_PATH . "/cache/";           // path to directory to store cache files to
$GLOBALS["conf_tpl_warn_level"] = TPL_WARN_CRITICAL;  // warning level
$GLOBALS["conf_tpl_max_include_levels"]    = 10;   // maximum number of include levels
$GLOBALS["conf_tpl_process_inputs"]  = '';               // process form inputs

// ###############################################

$GLOBALS["modules_sub"]["news"] = array("news", "news_groups", "news_config");

$GLOBALS["templates_EN"][1][2][98] = "content_user_profile";
$GLOBALS["temp_desc_EN"][1][98] = "Change user profile";
$GLOBALS["temp_desc_ET"][1][98] = "Muuda kasutajaandmeid";

$GLOBALS["templates_EN"][1][2][99] = "content_register";
$GLOBALS["temp_desc_EN"][1][99] = "User registration";
$GLOBALS["temp_desc_ET"][1][99] = "Kasutajaks registreerimine";
$GLOBALS["modules_sub"]["user"] = array("user", "user_groups", "user_log", "user_status", "user_status_user");

$GLOBALS["templates_EN"][1][2][180] = "content_form2";
$GLOBALS["temp_desc_ET"][1][180] = "Ankeetvorm - tekst p&auml;rast";
$GLOBALS["temp_desc_EN"][1][180] = "Form page - text after";
$GLOBALS["templates_EN"][1][2][181] = "content_form2_1";
$GLOBALS["temp_desc_ET"][1][181] = "Ankeetvorm - tekst enne";
$GLOBALS["temp_desc_EN"][1][181] = "Form page - text before";
$GLOBALS["modules"][] = "form2";
$GLOBALS["modules_sub"]["form2"] = array("form2", "form2_fields");

require_once(SITE_PATH . "/class/EventManager.php");
include_once(SITE_PATH . "/class/SystemLog.php");

// INCLUDE additional modules and installed components
if (file_exists(SITE_PATH . "/class/config2.php")) include_once(SITE_PATH . "/class/config2.php");

// END
// ###############################################

// require the modules if available
for ($c = 0; $c < sizeof($GLOBALS["modules"]); $c++) {
    if (file_exists(SITE_PATH . "/class/module." . $GLOBALS["modules"][$c] . ".class.php")) {
        include_once(SITE_PATH . "/class/module." . $GLOBALS["modules"][$c] . ".class.php");
    }
}
