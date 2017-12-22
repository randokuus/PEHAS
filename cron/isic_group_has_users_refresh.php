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
require_once(SITE_PATH . "/class/admin.session.class.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

echo "<pre>\n";
echo date('H:i:s') . "\n";
echo "Recalc group has_users:\n";

$sql = 'UPDATE module_user_groups SET has_users_updated = 0';
$database->query($sql);

$sql = "SELECT DISTINCT(u.ggroup) AS group_list FROM `module_user_users` as u WHERE u.ggroup <> ''";
$res = $database->query($sql);
if ($res) {
    $groupList = array();
    while ($row = $res->fetch_assoc()) {
        $tmpGroupList = explode(',', $row['group_list']);
        foreach ($tmpGroupList as $groupId) {
            if (!in_array($groupId, $groupList)) {
                $sql = 'UPDATE module_user_groups SET has_users = 1, has_users_updated = 1 WHERE id = ?';
                $database->query($sql, $groupId);
                $groupList[] = $groupId;
                echo $groupId . "\n";
            }
        }
    }
}

$sql = 'UPDATE module_user_groups SET has_users = 0 WHERE has_users_updated = 0';
$database->query($sql);

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
