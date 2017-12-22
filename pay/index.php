<?php
error_reporting(0);
chdir('..');
include("class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");
if (function_exists("hokusPokus")) {
    hokusPokus();
}
else {
    trigger_error("Modera.net: Corrupt installation or invalid execution.", E_USER_ERROR);
}

$pangalinkPath = 'Pangalink';
if (defined('PANGALINK_TEST')) {
    $pangalinkPath .= '/Test';
} else if (defined('PANGALINK_MOCK')) {
    $pangalinkPath .= '/Mock';
}

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Swed_Pay.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Seb_Pay.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Danske_Pay.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Krediidi_Pay.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Nordea_Pay.php");
require_once(SITE_PATH . "/class/{$pangalinkPath}/Lhv_Pay.php");
require_once(SITE_PATH . "/class/IsicPayment.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data_settings = $data = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, "");
$language = $lan->lan();

$txt = new Text($language, "module_user");
$txtf = new Text($language, "output");

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$usr = new user;
$GLOBALS["user_logged"] = $usr->status;
if (!$usr->returnUser()) {
    header('Location: ' . SITE_URL . '/');
    exit();
}
$GLOBALS["user_data"] = array($usr->user, $usr->user_name, $usr->username, $usr->company,
    $usr->group, $usr->groups, $usr->user_type, $usr->user_code,
);
$GLOBALS["user_show"] = $usr->isAuthorisedGroup();

$isicPayment = new IsicPayment();
/** @var IsicDB_PaymentLogs $isicDbPaymentLog */
$isicDbPaymentLog = IsicDB::factory('PaymentLogs');
$isicDbApplication = IsicDB::factory('Applications');

// ##############################################################

if (isset($_GET['id'])) {
    $paymentLogData = $isicDbPaymentLog->getRecord(intval($_GET['id']));
    if ($paymentLogData) {
        $bankId = $paymentLogData['bank_id'];
        $applicationId = $paymentLogData['application_id'];
        $action = 'receive';
        $logId = $paymentLogData['id'];
        $paymentTypeName = $paymentLogData['payment_type'];
    }
} else if (isset($_GET['bank']) && isset($_GET['appl'])) {
    $bankId = intval($_GET['bank']);
    $applicationId = intval($_GET['appl']);
    $paymentTypeName = $_GET['payment_type'] ? $_GET['payment_type'] : '';
    $action = 'send';
    $logId = $isicDbPaymentLog->insertRecord(
        array(
            'bank_id' => $bankId,
            'application_id' => $applicationId,
            'payment_type' => $paymentTypeName
        )
    );

}

$sendForm = 1011;
$receiveForm = 1111;
$formTemplate = SITE_PATH . '/tmpl/module_pangalink_form_utf8.html';

switch ($bankId) {
    case 1:
        $pangalink = new Pangalink_Seb_Pay();
    break;
    case 2:
        $pangalink = new Pangalink_Swed_Pay();
    break;
    case 3:
        $pangalink = new Pangalink_Danske_Pay();
    break;
    case 4:
        $pangalink = new Epayment_Nordea($isicPayment);
        $sendForm = 'e-payment';
        $receiveForm = 'e-payment-response';
        $formTemplate = SITE_PATH . '/tmpl/module_pangalink_form.html';
    break;
    case 7:
        $pangalink = new Pangalink_Krediidi_Pay();
        $sendForm = 1012;
    break;
    case 8:
        $pangalink = new Pangalink_Lhv_Pay();
        break;
    default:
        $pangalink = false;
    break;
}

if (!$pangalink) {
    header('Location: ' . SITE_URL . '/');
}

switch ($action) {
    case 'send':
        $pay_info = $isicPayment->getPaymentInfoAppl($applicationId, $paymentTypeName);
        if (is_array($pay_info)) {
            $pangalink->setPayParameter('pay_id', $applicationId);
            $pangalink->setPayParameter('pay_amount', $pay_info['pay_amount']);
            $pangalink->setPayParameter('pay_currency', $pangalink->_getCurrency());
            $pangalink->setPayParameter('pay_account', $pangalink->_getBankAccount());
            $pangalink->setPayParameter('pay_name', $pangalink->_getRecipientName());
            $pangalink->setPayParameter('pay_ref_number', $pangalink->_getReferenceNumber($applicationId));
            $pangalink->setPayParameter('pay_message', $pay_info['pay_message']);
            $form = $pangalink->generateVKForm($pangalink->_getReturnUrl($logId), $sendForm);
//            $isicDbPaymentLog->updateRecord($logId, array('send_message' => utf8_encode($form)));
            $isicDbPaymentLog->updateRecord($logId, array('send_message' => $form));
            $isicDbApplication->updateRecord($applicationId, array('payment_started' => 1));
            if ($form) {
                $tpl = new template();
                $tpl->setCacheLevel(TPL_CACHE_NOTHING);
                $tpl->setTemplateFile($formTemplate);
                $tpl->addDataItem("FORM", $form);
                echo $tpl->parse();
                exit();
            }
        }
        break;
    case 'receive':
        $res = $pangalink->checkActivation($receiveForm);
        $logMessage = 'GET: ' . print_r($_GET, true) . "\n";
        $logMessage .= 'POST: ' . print_r($_POST, true) . "\n";
        $logMessage .= 'RES: ' . print_r($res, true) . "\n";
//        $isicDbPaymentLog->updateRecord($logId, array('receive_message' => utf8_encode($logMessage)));
        $isicDbPaymentLog->updateRecord($logId, array('receive_message' => $logMessage));
        $isicPayment->savePaymentInfoAppl($pangalink->_getBankId(), $applicationId, $res,
            $isicPayment->getPaymentTypeByName($paymentTypeName)
        );
        break;
    default:
        break;
}

header('Location: ' . SITE_URL . '/');
