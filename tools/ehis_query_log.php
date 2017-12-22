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


function getErrorMessage($str) {
    $error_start = strpos($str, '[faultstring');
    $error_message = $error_start !== false ? substr($str, $error_start) : '';
    return $error_message;
}

$sql = 'SELECT * FROM 
`module_isic_log` 
WHERE 
module_name = ? and 
event_date >= ? and 
event_date < ? and
event_type = ? ORDER by id';
$logres = $database->query($sql, 'ehis_query', '2015-01-15', '2015-01-16', 5);
// echo $database->show_query();
while ($log_data = $logres->fetch_assoc()) {
    echo "================================================================================\n";
    echo $log_data['event_date'] . "\n";
    echo getErrorMessage($log_data['event_body']) . "\n";
}
