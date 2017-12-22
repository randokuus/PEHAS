<?php
/**
 * Modera.net user manager. Will manage login and user session creation, if default login form is used
 * @access public
 */

// ##############################################################
error_reporting(0);
require_once("class/config.php");
require_once(SITE_PATH . "/class/common.php");

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
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);

// init language object
$lan = new Language($database, defined('LANGUAGE_OVERRIDE') ? LANGUAGE_OVERRIDE : "");
$language = $lan->lan();
load_site_name($language);
$data_settings = $data = $GLOBALS['site_settings'];

$txt = new Text($language, "module_user");
$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

// ##############################################################

// extract some request parameters
foreach (array('structure', 'content', 'print', 'nocache','username','password'
    ,'login_check', 'href') as $v)
{
    if (array_key_exists($v, $_GET)) $$v = $_GET[$v];
    if (array_key_exists($v, $_POST)) $$v = $_POST[$v];
}

$login = $_REQUEST["login"];
$logout = $_REQUEST["logout"];
$auth_type = $_REQUEST["auth_type"];
$auth_error = $_REQUEST["auth_error"];

if ($login && !$logout) {
    if ($auth_error) {
        $template = "content_login.html";
    } else {
        // Checking if user record exists before doing any external authentication
        if (defined('AUTH_CHECK_USER_ACCOUNT_RECORD') && AUTH_CHECK_USER_ACCOUNT_RECORD) {
            $dbUser = IsicDB::Factory('Users');
            if ($_REQUEST['login_check'] && !$dbUser->getIdByUsername($username)) {
                unset($auth_type); // not allowing external auth-checks if user record does not exist
                unset($_REQUEST['login_check']);
                $_REQUEST['auth_error'] = true;
                $_REQUEST['error_code'] = 20; // user not found
            }
        }

        switch ($auth_type) {
            case 2: // id-card
                if (strpos(SITE_URL, "dev.modera.net") !== false) {
                    header('Location: ' . SITE_URL . '/id/?username=' . urlencode($username));
                } else {
                    header('Location: https://id.minukool.ee/?username=' . urlencode($username));
                }
                exit();
            break;
            case 3: // hansapank
                header('Location: ' . SITE_URL . '/hansa/?username=' . urlencode($username));
                exit();
            break;
            case 4: // SEB
                header('Location: ' . SITE_URL . '/seb/?username=' . urlencode($username));
                exit();
            break;
            case 5: // Danske
                header('Location: ' . SITE_URL . '/danske/?username=' . urlencode($username));
                exit();
            break;
            case 6: // Nordea
                header('Location: ' . SITE_URL . '/nordea/?username=' . urlencode($username));
                exit();
            break;
            case 7: // Krediidi
                header('Location: ' . SITE_URL . '/krediidi/?username=' . urlencode($username));
                exit();
            break;
            case 8: // LHV
                header('Location: ' . SITE_URL . '/lhv/?username=' . urlencode($username));
                exit();
                break;
            default: // regular username and password
                $template = "content_login.html";
            break;
        }
    }
}
else if ($logout && !$login) {
    $template = "content_login.html";
}
else {
    redirect("?module_error=true");
}

$GLOBALS["userlogin"] = 1 == $data_settings["userlogin"] ? true : false;
$GLOBALS["loginform"] = 1 == $data_settings["loginform"] ? true : false;

// set login to 1 so we will check login and autohorize
$GLOBALS["pagedata"]["login"] = 1;

//if ($GLOBALS["userlogin"] == true) {
$usr = new user;

if ($usr->sid && $logout == "true") {
    $usr->logOut();
}

if ($login == "true" && $username && $password) {
    $status = $usr->setSession($username, $password);
    //$_COOKIE["SID"] = $SID;
}

$GLOBALS["user_logged"] = $usr->status;
$usr->returnUser();
$GLOBALS["user_data"] = array($usr->user, $usr->user_name, $usr->username
    , $usr->company, $usr->group, $usr->groups);
$GLOBALS["user_show"] = $usr->isAuthorisedGroup();
unset($usr);

// Logged in, redirect
if ($GLOBALS["user_logged"] == true) {
    $href = substr($href, strrpos($href, "/")+1);

    if (substr($href, -1) == "#") {
        $href = substr($href, 0, -1) . "?";
    }

    if ($href != "") {
        redirect("$href&nocache=true");
        exit;
    }
    else {
        redirect("?nocache=true");
        exit;
    }
}
//}

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . $template;
$tpl->setTemplateFile($template);

if ($login && !$logout) {
    $tpl->addDataItem("PAGETITLE", $txt->display("head_login"));
}
else if ($logout && !$login) {
    $tpl->addDataItem("PAGETITLE", $txt->display("head_logout"));
    $tpl->addDataItem("TITLE", $txt->display("head_logout"));
    $tpl->addDataItem("TEXT", ereg_replace("{SITE}", $data_settings["name"], $txt->display("logout_text")));
}

// ##############################################################

echo $tpl->parse();
$db->disconnect();
