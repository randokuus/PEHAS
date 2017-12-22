<?php
require_once('../class/config.php');
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

$db = new DB();
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);

function cardsForSchool($schoolId) {
    global $database;

    $sql = 'SELECT count(*) as total FROM module_isic_card WHERE school_id = ?';
    $res = $database->query($sql, $schoolId);
    if ($data = $res->fetch_assoc()) {
       return $data['total'];
    }
    return 0;
}

echo "<pre>\n";
//$sql = 'SELECT * FROM module_isic_school WHERE LENGTH(ehis_code) > 4';
$sql = 'SELECT name, count(*) AS total FROM module_isic_school WHERE 1 GROUP BY name';

$res = $database->query($sql);
while ($data = $res->fetch_assoc()) {
    if ($data['total'] < 2) {
        continue;
    }
    //echo $data['id'] . ',' . $data['ehis_code'] . ',' . $data['name'] . "\n";
    //echo $data['name'] . "\n";
    //$sql = 'SELECT * FROM module_isic_school WHERE name = ? AND id <> ?';
    $sql = 'SELECT * FROM module_isic_school WHERE name = ?';
    $res2 = $database->query($sql, $data['name'], $data['id']);
    while ($data2 = $res2->fetch_assoc()) {

        echo $data2['id'] . ',' . $data2['ehis_code'] . ',' . $data2['name'] . ',' . cardsForSchool($data2['id']) . "\n";
    }
}



echo "</pre>\n";