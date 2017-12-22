<?php

include("../../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
set_time_limit(0);
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicDB.php");

// ##############################################################

$db = new db;
$db->connect();
$sq = new sql;
$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

// ##############################################################

$users = IsicDB::factory('Users');
$cardTypes = IsicDB::factory('CardTypes');
$newsletters = IsicDB::factory('Newsletters');
$newslettersOrders = IsicDB::factory('NewslettersOrders');
$applications = IsicDB::factory('Applications');

$existsNewslettersList = $newsletters->listRecords();

$offset = 0;
do {
    $userList = $users->listRecords($offset, 1000);
    foreach ($userList as $userData) {
        $newsletterList = explode(",", $userData["newsletter"]);
        $newsletterList = array_diff($newsletterList, array(''));
        $userApplications = $applications->findRecords(array(
            "person_name_first" => $userData["name_first"],
            "person_name_last" => $userData["name_last"],
            "person_number" => $userData["user_code"]
        ));
        foreach ($newsletterList as $typeId) {
            if ($typeId == '0') {
                continue;
            }
            $applicationId = "";
            foreach ($userApplications as $applData) {
        	    $applNewsletters = explode(",", $applData["person_newsletter"]);
        	    $applNewsletters = array_diff($applNewsletters, array(''));
        	    if (in_array($typeId, $applNewsletters)) {
        	        $applicationId = $applData["id"];
        	        break;
                }
            }
            foreach ($existsNewslettersList as $newsletterData) {
                $newsletterCardTypes = explode(",", $newsletterData["card_types"]);
                $newsletterCardTypes = array_diff($newsletterCardTypes, array(''));
                if (in_array($typeId, $newsletterCardTypes)) {
                    $newslettersOrders->createIfNotExists($newsletterData["id"], $userData["user"], $applicationId);
                }
            }
	    }
    }
    $offset += count($userList);
    if (count($userList) > 0) {
        print "User profiles processed: $offset\n";
    }
}
while (count($userList) > 0);

print "Newsletters export is finished!\n";

?>


