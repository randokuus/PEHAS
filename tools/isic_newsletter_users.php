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
load_site_settings($database);
$data = $data_settings = $GLOBALS['site_settings'];

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;


$typeList = array();

$res = $database->query("SELECT id, name FROM module_isic_card_type ORDER BY module_isic_card_type.name");
while ($tdata = $res->fetch_assoc()) {
    $typeList[$tdata['id']] = $tdata['name'];
}

echo "Email,Eesnimi,Perenimi,Kaarditüüp\n";

$res = $database->query("
    SELECT
        name_first,
        name_last,
        email,
        newsletter
    FROM
        module_user_users
    WHERE
        module_user_users.newsletter <> '' AND
        module_user_users.email <> ''
    ORDER BY
        module_user_users.name_last,
        module_user_users.name_first
");
while ($udata = $res->fetch_assoc()) {
    foreach (explode(',', $udata['newsletter']) as $typeId) {
        if (array_key_exists($typeId, $typeList)) {
            echo
                $udata['name_first'] . ',' .
                $udata['name_last'] . ',' .
                $udata['email'] . ',' .
                $typeList[$typeId] . "\n";
        }
    }
}

echo "Done\n";