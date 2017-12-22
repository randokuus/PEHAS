<?php
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
require_once(SITE_PATH . "/class/Isic/IsicCrypto.php");

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

$txt = new Text($language, "module_isic_application");

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$template = "content_isic_application_confirm.html";

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$template = $GLOBALS["templates_".$language][$data["template"]][1] . "/" . $template;
$tpl->setTemplateFile($template);

$tpl->addDataItem("PAGETITLE", $txt->display("application_confirm"));

$applId = 0;
if ($_GET['id']) {
    try {
        $applId = IsicCrypto::decrypt(
            urldecode($_GET['id']),
            ISIC_CRYPTO_KEY,
            true
        );
        $error = false;
    } catch (Exception $e) {
        //
    }
}

/** @var IsicDB_GlobalSettings $isicGlob */
$isicGlob = IsicDB::factory('GlobalSettings');
/** @var IsicDB_Applications $isicAppl */
$isicAppl = IsicDB::factory('Applications');
$applData = $isicAppl->getRecord($applId);
if (!$applData) {
    $error = true;
    $message = $txt->display('confirm_error_1');
} else if ($applData['confirm_user']) {
    $error = true;
    $message = $txt->display('confirm_error_2');
} else {
    $isicAppl->updateRecord($applData['id'], array('confirm_user' => 1));

    /** @var IsicDB_Users $isicDbUsers */
    $isicDbUsers = IsicDB::factory('Users');
    $userData = $isicDbUsers->getRecordByCodeUserType($applData['person_number'], $isicDbUsers->getUserTypeUser());
    if ($userData) {
        // assign newsletters
        $isicDbNewsLetters = IsicDB::factory('Newsletters');
        $newsletterList = array_keys(
            $isicDbNewsLetters->getNameListByAllowedNewsletters(
                array($applData['type_id'])
            )
        );

        $newsLettersOrders = IsicDB::factory('NewslettersOrders');
        $newsLettersOrders->updateUserOrders(
            $userData['user'],
            $applData["id"],
            $newsletterList
        );

        // enable special offers via email and sms
        $isicDbUsers->enableSpecialOffers($userData['user']);

        // enable data sync
        $isicDbUsers->updateRecord(
            $userData['user'],
            array('data_sync_allowed' => 1)
        );
    }
    $message = $txt->display('confirm_success');
}

$message = str_replace('{ADMIN_EMAIL}', $isicGlob->getRecord('admin_email'), $message);
if ($error) {
    $tpl->addDataItem("ERROR.TEXT", $message);
} else {
    $tpl->addDataItem("SUCCESS.TEXT", $message);
}
echo $tpl->parse();
$db->disconnect();
