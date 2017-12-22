<?php
include_once("../class/config.php");
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
$sq2 = new sql;

$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$schools = array();

$count = 0;
$res =& $t_db->query("SELECT * FROM module_isic_school");
while ($data = $res->fetch_assoc()) {

    if ($data['id'] > 2300) {
        $schools[$data['name']][] = $data['id'];
    }
}

foreach ($schools as $name => $idList) {
    if (count($idList) > 0) {
        $count += count($idList);
        foreach ($idList as $schoolId) {

            $res2 = $t_db->query("select count(*) as card_count from module_isic_card where school_id = !", $schoolId);
            $data2 = $res2->fetch_assoc();
            $cardCount = $data2['card_count'];
            if ($cardCount > 0) {
                continue;
            }

            echo $name . ': ' . "\n";


            $res2 = $t_db->query("select count(*) as status_count from module_user_status_user where school_id = !", $schoolId);
            $data2 = $res2->fetch_assoc();
            $statusCount = $data2['status_count'];
            $t_db->query("delete from module_user_status_user where school_id = !", $schoolId);

            $res2 = $t_db->query("select count(*) as group_count from module_user_groups where isic_school = !", $schoolId);
            $data2 = $res2->fetch_assoc();
            $groupCount = $data2['group_count'];
            $t_db->query("delete from module_user_groups where isic_school = !", $schoolId);

            $res2 = $t_db->query("select count(*) as bank_count from module_isic_bank_school where school_id = !", $schoolId);
            $data2 = $res2->fetch_assoc();
            $bankCount = $data2['bank_count'];
            $t_db->query("delete from module_isic_bank_school where school_id = !", $schoolId);

            echo $schoolId . ': Cards: ' . $cardCount . ", Statuses: " . $statusCount . ", Groups: " . $groupCount . ", Banks: " . $bankCount . "\n";

            $t_db->query("delete from module_isic_school where id = !", $schoolId);
        }
        echo "==========\n";
    }
}

echo $count;