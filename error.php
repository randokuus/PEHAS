<?php
/**
 * Modera.net error program. This script will display general modera.net page errors
 * @access public
 * @var integer $error Takes a _GET parameter named $error as the error to show (404,401,403,999)
 */

// ##############################################################
if (!array_key_exists('error', $_GET)) $_GET['error'] = '404';
$error = $_GET['error'];
error_reporting(0);
require_once("class/config.php");
require_once(SITE_PATH . "/class/common.php");
$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$translator = & ModeraTranslator::instance($language, "output_error");//Translator::driver('gettext', new Locale($language), "output_error");
$txt = new Text($language, "output_error");
$txtf = new Text($language, "output");

// ##############################################################

$error = $_GET["error"];
$template = "tmpl/error.html";

// build a template
$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);

// set page instance for correct caching operations
$inst = $_SERVER["PHP_SELF"].url();
$tpl->setInstance($inst);

// check if the page is cached
if (!$tpl->isCached($template)) {

    $admin_email = htmlspecialchars($data["admin_email"]);
    $admin_name = htmlspecialchars($data["admin"]);
    $email_text = "<br />$admin_name, <a href=\"mailto:$admin_email\">$admin_email</a>";
    $site_name = htmlspecialchars($data["name"]);

    switch (true){
        case in_array($error, array(404, 403, 401, 'expired')):
            $tpl->addDataItem("TITLE", $txt->display($error . "_title"));
            $tpl->addDataItem("COPY", "&copy; " . date("Y") . " " . $site_name);
            $tpl->addDataItem("TEXT", $txt->display($error . "_text") . $email_text);
            $tpl->addDataItem("BACK", "<a href=\"history.back()\">"
                . $txt->display("back") . "</a>");
            break;
        case $error == 999:
            $tpl->addDataItem("TITLE", $txt->display($error . "_title"));
            $tpl->addDataItem("COPY", "<br /><br />" . $txtf->display("copy_modera2"));
            $tpl->addDataItem("TEXT", $txt->display($error . "_text") . $email_text);
            $tpl->addDataItem("BACK", "");
            break;
        case $error == 'unpublished':
            $published_date = is_numeric($_GET["published_date"]) ? date("d/m/Y H:i:s", intval($_GET["published_date"])) : '';
            $content = $translator->tr($error . '_text', array('date'=> $published_date));
            $tpl->addDataItem("TITLE", $txt->display($error . "_title"));
            $tpl->addDataItem("COPY", "&copy; " . date("Y") . " " . $site_name);
            $tpl->addDataItem("TEXT", $content. $email_text);
            $tpl->addDataItem("BACK", "<a href=\"javascript:history.back()\">"
                . $txt->display("back") . "</a>");
            break;
        default:
            exit;
    }
}

// ##############################################################

echo $tpl->parse($template);
$db->disconnect();