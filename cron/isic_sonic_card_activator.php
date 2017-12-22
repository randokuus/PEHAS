<?php
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
require_once(SITE_PATH . "/class/IsicSonicTemp.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
$GLOBALS['database'] =& $database;
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$sonic = new IsicSonicTemp();

$cardList = array(
    'S372900393586P',
    'S372900395433J',
    'S372900396560M',
    'S372900397172P',
    'S372900397303E',
    'S372900397531F',
    'S372900397979J',
    'S372900398115T',
    'S372900398129V',
    'S372900398172K',
    'S372900398235J',
    'S372900398523J',
    'S372900398976S',
    'S372900399045H',
    'S372900399079J',
    'S372900399086S',
    'S372900399312J',
    'S372900399408P',
    'S372900399410E',
    'S372900399576N',
    'S372900399676N',
    'S372900399952G',
    'S372900400396D',
    'S372900400587K',
    'S372900400694U',
    'S372900400930Q',
    'S372900400939L',
    'S372900401859F',
    'S372900401995H',
    'S372900402103J',
    'S372900402207P',
    'S372900402248Z',
    'S372900402433L',
    'S372900402597G',
    'S372900402769Q',
    'S372900402773J',
    'S372900403155K',
    'S372900403347J',
    'S372900403434J',
    'S372900404616J',
    'S372900404645K',
    'S372900404727P',
    'S372900405072N',
    'S372900405133J',
    'S372900405176L',
    'S372900405177N',
    'S372900405249D',
    'S372900405250L',
    'S372900405340R',
    'S372900405354J',
    'S372900405395J',
    'S372900405497N',
    'S372900405620J',
    'S372900405858F',
    'S372900406050R',
    'S372900406397J',
    'S372900406458L',
    'S372900406500E',
    'S372900406864J',
    'S372900407269N',
    'S372900409277P',
    'S372900409338J',
    'S372903345569N',
    'S372903346156J',
    'S372900412033L',
    'S372900415891W',
    'S372900416523J',
    'S372900416542Q',
    'S372900417053S',
    'S372900418890G',
    'S372900419268C',
    'S372900426343S',
    'S372900429970X',
    'S372900430283N',
    'S372900430524S',
    'S372900440130G',
    'S372903338933G',
    'S372903339102P',
    'T372900431459Q',
    'T372900432300M',
    'T372900433680N',
    'T372900433737N',
    'T372900435013J',
    'T372900435717Q',
);

//chdir('');

foreach ($cardList as $card) {
    echo $card . ": ";
    $sonic->saveCardData(array('isic_number' => $card));
    echo "\n";
}

echo "Done\n";