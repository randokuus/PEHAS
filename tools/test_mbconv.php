<?php

require_once('../class/config.php');
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/module.isic_card_export.class.php");

$db = new DB();
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);

$cardExporter = new isic_card_export();

$sql = $cardExporter->getCommonSqlSelectFromJoin();
// $sql .= " WHERE module_isic_card.id = 615724";
$sql .= " WHERE module_isic_card.id = 615708";
// $sql .= " WHERE module_isic_card.id = 614819";

$res = $database->query($sql);
$data = $res->fetch_assoc();

print_r($data);
var_dump($data['person_name_last']);

echo mb_strlen($data['person_name_last']) . PHP_EOL;

function ordutf8($sym) {
    $ord = array();
    for ($i = 0; $i < strlen($sym); $i++) {
        $ord[] = ord(substr($sym, $i, 1));
    }
    return implode(', ', $ord);
}

for ($i = 0; $i < mb_strlen($data['person_name_last']); $i++) {
    $sym = mb_substr($data['person_name_last'], $i, 1);
    echo $sym . ': ' . ordutf8($sym) . PHP_EOL;
}


$cardHolderNames = $cardExporter->getCardHolderNames($data);
print_r($cardHolderNames);

$strPart = $cardExporter->formatString($cardHolderNames[0], 30, ' ', STR_PAD_RIGHT, true);
echo $strPart . PHP_EOL;

// $lastName = mb_convert_case($data['person_name_last'], MB_CASE_UPPER, 'UTF-8');
// echo $lastName . PHP_EOL;
//
// $lastName = mb_convert_encoding($lastName, 'Windows-1252', 'UTF-8');
// echo $lastName . PHP_EOL;

