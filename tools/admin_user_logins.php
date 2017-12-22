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

echo "Username,Isikukood,Eesnimi,Perenimi,Logiaeg,Logiteade,Koolid\n";

$sql = '
    select 
        u.user,
        u.username,
        u.user_code,
        u.name_first,
        u.name_last,
        u.ggroup
    from 
        module_user_users as u
    where
        u.user_type = 1 and
        u.user > 1
';
$res = $database->query($sql);
while ($data = $res->fetch_assoc()) {
    // echo $data['username'] . "\n";
    $groupList = array();
    if ($data['ggroup']) {
        $sql = 'select s.name, count(*) from module_user_groups as g, module_isic_school as s where g.id in (!) and g.isic_school = s.id group by s.id order by s.name';
        $gres = $database->query($sql, $data['ggroup']);
        while ($gdata = $gres->fetch_assoc()) {
            $groupList[] = $gdata['name'];
        }
    } else {
        $groupList[] = $data['ggroup'];
    }
    $sql = 'select s.* from systemlog as s where s.time > ? and s.source = ? and s.message like ?';
    $logres = $database->query($sql, '2013-08-01', 'user_login', 'User "' . $data['username'] . '" logged%');
    // echo $database->show_query() . "\n";
    while ($log_data = $logres->fetch_assoc()) {
        $method_start = strpos($log_data['message'], 'with');
        $log_method = $method_start !== false ? substr($log_data['message'], $method_start + strlen('with '), -1) : 'Password';
        echo $data['username'] . ',' . $data['user_code'] . ',' . $data['name_first'] . ',' . $data['name_last'] . ',' . $log_data['time'] . ',' . $log_method . ',' . implode('; ', $groupList) . "\n";
    }
}
