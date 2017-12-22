<?php
require_once("class/config.php");
require_once(SITE_PATH . "/class/common.php");
$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Webservice.php");
require_once(SITE_PATH . "/class/Webservice/WSParser.php");
require_once(SITE_PATH . "/class/Webservice/WSPartner.php");
require_once(SITE_PATH . "/class/Webservice/WSMessage.php");
require_once(SITE_PATH . "/class/Webservice/WSVersion.php");
require_once(SITE_PATH . "/class/Webservice/WSRequest.php");
require_once(SITE_PATH . "/class/Crypto.php");

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

$crypto = new crypto(true);
$version = new WSVersion('2.1');
$partner = new WSPartner(17, 1);
$ws = new Webservice($partner);
$msg = new WSMessage($partner, $version, $crypto);

$url = "https://tele2:Mh830Frv6@www.minukool.ee:443/khs/ws/";
// $url = "https://tele2smart:9DsU724kv@isic.vels.dev.modera.net:443/ws/";
// $url = "https://overall:h638gfw92d@isic.vels.dev.modera.net:443/ws/";
// $url = "https://teeninduskool:24hj7gy3c@www.minukool.ee:443/khs/ws/";
// $url = "https://itcollege:blabla@isic.vels.dev.modera.net:443/ws/";
//$url = "https://itcollege:k4hvnf8gs3@www.minukool.ee:443/khs/ws/";
//$url = "https://ttu:k3nf8g7x@www.minukool.ee:443/khs/ws/";
//$url = "https://kiiligym:k4g8bc2x@www.minukool.ee:443/khs/ws/";
//$url = "https://saaremaa:s8pl4rku9n@www.minukool.ee:443/khs/ws/";
// $url = "https://kuristikug:WUd5R03I@www.minukool.ee:443/khs/ws/";
// $url = "https://PiritaMG:R3w1uhsT@www.minukool.ee:443/khs/ws/";
// $url = "https://minukool:m1nyk00Lx9@www.ehl.liige.ee:443/ws/";
// $url = "https://minukool:m1nyk00Lx9@liige.vels.dev.modera.net:443/ws/";

$parameter_name = "txtXml";

////////////////////////////////////////////////////////
///                C A R D   V A L I D               ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("card_number" => "S372900400349F"),
    1 => array("card_number" => "S372900408328J"),
    // 2 => array('card_number' => 'T372500200315R')
);

//$reqid = WSRequest::generateId();
$reqid = 0;
$message = $msg->createMessage("card_valid", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///      C A R D   V A L I D   W I T H   T Y P E     ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("card" => array("card_number" => "S372900069026", "number_type" => 1)),
    1 => array("card" => array("card_number" => "S372500200306N", "number_type" => 1)),
    2 => array("card" => array("card_number" => "127118", "number_type" => 3)),
);

//$reqid = WSRequest::generateId();
$reqid = 0;
$message = $msg->createMessage("card_valid", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
print_r($proc_resp);
*/
////////////////////////////////////////////////////////
///                 C A R D   L I S T                ///
////////////////////////////////////////////////////////

$param = array(
    0 => array("from" => "20160117T000001Z", 'until' => '20160218T000001Z', 'person_code' => '44603130268', 'card_validity' => true),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("card_list", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));

print_r($proc_resp);


////////////////////////////////////////////////////////
///                 L O C K   L I S T                ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("lock" => array("lock_id" => "ABC123x", "name" => "This is lock namex", "description" => "This is lock description")),
    1 => array("lock" => array("lock_id" => "DEF456", "name" => "This is lock name 2")),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("lock_list", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/
////////////////////////////////////////////////////////
///                 L O C K   A C C E S S            ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("event" => array("event_id" => "10", "dir" => "in", "access" => "yes", "card_number" => "S372500200460U", "number_type" => "1", "lock_id" => "DEF456", "event_time" => "20080101000000Z")),
    1 => array("event" => array("event_id" => "5", "dir" => "out", "access" => "no", "card_number" => "9664C4E1", "number_type" => "2", "lock_id" => "ABC123", "event_time" => "20080101101010Z")),
    2 => array("event" => array("event_id" => "15", "dir" => "out", "access" => "no", "card_number" => "127118", "number_type" => "3", "lock_id" => "ABC123", "event_time" => "20110101101010Z")),
);

$reqid = WSRequest::generateId();
//$reqid = 0;
$message = $msg->createMessage("lock_access", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///                 L A S T    E V E N T             ///
////////////////////////////////////////////////////////
/*
$reqid = WSRequest::generateId();
//$reqid = 0;
$param = array();
$message = $msg->createMessage("last_event", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/
////////////////////////////////////////////////////////
///        P E R S O N   V A L I D   C A R D         ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("person_number" => "49003035217"),
    1 => array("person_number" => "38709234523"),
    2 => array("person_number" => "49108204831"),
    // 1 => array("person_number" => "49003035217"),
//    2 => array("person_number" => "99003035217"),
    // 0 => array("person_number" => "39406075249"),
    // 0 => array("person_number" => "39311275215"),
    // 1 => array("person_number" => "36601011234"),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("person_valid_card", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///        P E R S O N   V A L I D   P A N           ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("person_number" => "36705154220"),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("person_valid_pan", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///               D E V I C E   L I S T              ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("device" => array("device_id" => "ABC123", "type_id" => 2, "name" => "This is device name", "description" => "This is device description")),
    1 => array("device" => array("device_id" => "DEF456", "type_id" => 2, "name" => "This is device name 2")),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("device_list", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///             R E G I S T E R   S A L E            ///
////////////////////////////////////////////////////////
/*
$param = array(
    0 => array("event" => array("event_id" => "4", "card_number" => "S372500200460U", "number_type" => "1", "device_id" => "DEF456", "event_time" => "20080101000000Z", "sale_sum" => 10, "discount_sum" => 0, "currency" => "EEK")),
//    1 => array("event" => array("event_id" => "5", "card_number" => "9664C4E1", "number_type" => "2", "device_id" => "ABC123", "event_time" => "20080101101010Z", "sale_sum" => 15, "discount_sum" => 5, "currency" => "EEK")),
//    0 => array("event" => array("event_id" => "1", "card_number" => "S372500200242N", "number_type" => "1", "device_id" => "002", "event_time" => "20100519154047Z", "sale_sum" => "24.00", "discount_sum" => "0.00", "currency" => "EUR")),
 );

$reqid = WSRequest::generateId();
//$reqid = 0;
$message = $msg->createMessage("register_sale", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///               S T A T U S   L I S T              ///
////////////////////////////////////////////////////////
/*
$reqid = WSRequest::generateId();
//$reqid = 0;
$param = array();
$message = $msg->createMessage("status_list", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));
//print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///     P E R S O N   S T A T U S   L I S T          ///
////////////////////////////////////////////////////////
/*
$param = array(
   0 => array("from" => "20140130T000001Z"),
);

$reqid = WSRequest::generateId();
$message = $msg->createMessage("person_status_list", $reqid, $param);
print_r($message);
$response = $msg->sendMessage($url, $parameter_name, $message);
print_r($response);
$proc_resp = $ws->processMessageResponse(WSParser::parse($response));

print_r($proc_resp);
*/

////////////////////////////////////////////////////////
///           P E R S O N   P I C T U R E            ///
////////////////////////////////////////////////////////

// $param = array(
//     0 => array("person_number" => "39907020249"),
//     1 => array("person_number" => "39711240221"),
// );
//
// $reqid = WSRequest::generateId();
// $message = $msg->createMessage("person_picture", $reqid, $param);
// print_r($message);
// $response = $msg->sendMessage($url, $parameter_name, $message);
// print_r($response);
// $proc_resp = $ws->processMessageResponse(WSParser::parse($response));
// print_r($proc_resp);

////////////////////////////////////////////////////////
///           P E R S O N   P R O F I L E            ///
////////////////////////////////////////////////////////

// $param = array(
//     0 => array("person_number" => "45909105229"),
//     // 1 => array("person_number" => "39711240221"),
// );
//
// $reqid = WSRequest::generateId();
// $message = $msg->createMessage("person_profile", $reqid, $param);
// print_r($message);
// $response = $msg->sendMessage($url, $parameter_name, $message);
// print_r($response);
// $proc_resp = $ws->processMessageResponse(WSParser::parse($response));
// print_r($proc_resp);


echo time();