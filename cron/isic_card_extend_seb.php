<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");

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

$ic = IsicCommon::getInstance();

$expiration = "2010/12/31";

$count = 0;
$res =& $t_db->query("
    SELECT 
        module_isic_card.*
    FROM 
        module_isic_card 
    WHERE 
        module_isic_card.kind_id = 2 AND
        module_isic_card.adddate >= '2009-09-01' AND
        module_isic_card.expiration_date = '2009-12-31'
    ORDER BY
        module_isic_card.adddate");
while ($data = $res->fetch_assoc()) {
    $count++;
    echo $count . ". " . $data['isic_number'] . ": " . $ic->extendCardExpiration($data['id'], $expiration) . "<br>\n";
}
