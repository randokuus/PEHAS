<?php
set_time_limit(0);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicCommon.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicCommon::getInstance();
echo "<pre>\n";

$sql = 'SELECT * FROM module_user_users WHERE module_user_users.pic = ?';
$res = $database->query($sql, '');
if ($res) {
    while ($data = $res->fetch_assoc()) {
        $sql = '
        	SELECT *
        	FROM
        		module_isic_application
        	WHERE
        		module_isic_application.person_number = ? AND
        		module_isic_application.pic <> ?
        	ORDER BY
        		id DESC
    		LIMIT 1';
        $res2 = $database->query($sql, $data['user_code'], '');
        if ($res2) {
            $data2 = $res2->fetch_assoc();
            if ($data2) {
                echo $data['username'] . ': ' . $data2['pic'] . "\n";
                $ic->updateUserPic($data['user'], $data2['id']);
            }
        }
    }
}

echo 'done';
echo "</pre>\n";