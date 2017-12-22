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

$methods = array('SEB', 'ID-card', 'Swedbank', 'Nordea', 'Sampo');

function getAuthMethod($str) {
    global $methods;
    foreach($methods as $method) {
        if (strpos($str, $method) !== false) {
            return $method;
        }
    }
    return 'Regular';
}

function isSuccess($str) {
    return (stripos($str, 'unsuccess') !== false) ? 'no' : 'yes';
}


function getLoginType($str) {
    return array('success' => isSuccess($str), 'method' => getAuthMethod($str));
}

$total = array();

echo "<pre>\n";
$sql = 'SELECT DATE(time) AS login_date, message FROM systemlog WHERE source = ? AND time > ? ORDER BY time ';

$res = $database->query($sql, 'user_login', '2011-09-01');
while ($data = $res->fetch_assoc()) {
    $loginType = getLoginType($data['message']);
//    echo $data['login_date'] . ': ' . $data['message'] . "\n";
//    print_r($loginType);
    $total[$data['login_date']][$loginType['success']][$loginType['method']] += 1;
//    echo "\n";
}


$successList = array('yes', 'no');
$methods[] = 'Regular';

$title = array('Date');
foreach ($successList as $success) {
    foreach ($methods as $method) {
        $title[] = $method . ': ' . $success;
    }
}

echo implode(',', $title) . "\n";

foreach($total as $date => $data) {
    $row = array($date);
    foreach ($successList as $success) {
        foreach ($methods as $method) {
            if (isset($data[$success][$method])) {
                $row[$method . '_' . $success] = $data[$success][$method];
            } else {
                $row[$method . '_' . $success] = 0;
            }
        }
    }
    echo implode(',', $row) . "\n";
}

echo "</pre>\n";