<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/" . DB_TYPE . ".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = & $language;

$isicDbUserStatus = IsicDB::factory('UserStatuses');
$isicDbCard = IsicDB::factory('Cards');
$isicDbUser = IsicDB::factory('Users');
$isicDbCardValidity = IsicDB::factory('CardValidities');

echo "<pre>\n";
echo date('H:i:s') . "\n";
$statusId = 325170;
$userStatusData = $isicDbUserStatus->getRecord($statusId);
print_r($userStatusData);

$userData = $isicDbUser->getRecord($userStatusData['user_id']);
print_r($userData);


$cardList = $isicDbCard->findRecordsByStatusPersonNumber($userStatusData['status_id'], $userData['user_code']);
// print_r($cardList);

foreach ($cardList as $cardData) {
	print_r($cardData);
	echo "\n";
	$canActCard = $isicDbCard->canBeActivated($cardData);
	echo $canActCard ? 'cyes' : 'cno';	
	echo "\n";
	$canAct = $isicDbUser->newCanActivateCard($cardData);
	echo $canAct ? 'uyes' : 'uno';
	// echo $isicDbCardValidity->newIsRecordRequiredForCardUserStatus($cardData, $userStatusData);
	echo "\n";
}

// $userId = 14550;
// $cardTypeId = 2;
// $cardId = 381251;
// 
// $rowCount = 1;
// 
// $sql = 'SELECT c.id, c.isic_number, v.id AS validity_id FROM module_isic_card as c, `module_isic_card_validities` as v WHERE c.id = v.card_id and c.active and v.user_status_active = 0 ORDER BY c.id';
// $res = $database->query($sql);
// while ($data = $res->fetch_assoc()) {
// 	echo $rowCount . '. ' . $data['id'] . ': ' . $data['isic_number'] . ', '. $data['validity_id'] . "\n";
// 	$cardRecord = $isicDbCard->getRecord($data['id']);
// 	$isicDbCardValidity->insertOrUpdateRecordByCard($cardRecord);
// 	$rowCount++;
// }

//print_r($cardRecord);

// echo "\n";
// 
// // $usList = $isicDbUserStatus->newFindLastRecordsByUserCardType($userId, $cardTypeId);
// $usList = $isicDbUserStatus->findLastRecordsByUserCardType($userId, $cardTypeId);
// echo $database->show_query();
// foreach ($usList as $usRecord) {
//     print_r($usRecord);
// //    $isRequired = $isicDbCardValidity->isRecordRequiredForCardUserStatus($cardRecord, $usRecord);
// //    echo 'Required: ' . $isRequired;
//     echo "\n";
// }
// // print_r($usList);

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
