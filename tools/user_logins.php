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


function getUsername($str) {
    $beg = strpos($str, 'User') + strlen('User "');
    $end = strpos($str, 'logged') - 2;
    $username = substr($str, $beg, $end - $beg);
    return $username;
}

function getLogMethod($str) {
    $method_start = strpos($str, 'with');
    $log_method = $method_start !== false ? substr($str, $method_start + strlen('with '), -1) : 'Password';
    return $log_method;
}

function getUserData($username) {
    global $database;
    $sql = '
        select 
            u.user,
            u.username,
            u.user_code,
            u.name_first,
            u.name_last,
            u.user_type
        from 
            module_user_users as u
        where
            u.username = ?
    ';
    $res = $database->query($sql, $username);
    if ($data = $res->fetch_assoc()) {
        return $data;
    }
    
    return false;
}

echo "Aeg;Roll;Username;Isikukood;Eesnimi;Perenimi;Viis\n";
$user_list = array();
$sql = 'select s.* from systemlog as s where s.time >= ? and s.time < ? and s.source = ? and message like ?';
$logres = $database->query($sql, '2013-09-01', '2013-10-01', 'user_login', '%logged%');
while ($log_data = $logres->fetch_assoc()) {
    $username = getUsername($log_data['message']);
    // echo $username . "\n";
    if (!array_key_exists($username, $user_list)) {
        $user_list[$username] = getUserData($username);
    }
    //
    $data = $user_list[$username];
    if ($data) {
        $log_method = getLogMethod($log_data['message']);
        echo $log_data['time'] . ';' . ($data['user_type'] == 1 ? 'Admin' : 'Tava') . ';' . $data['username'] . ';' . $data['user_code'] . ';' . $data['name_first'] . ';' . $data['name_last'] . ';' . $log_method . "\n";
    }
}
