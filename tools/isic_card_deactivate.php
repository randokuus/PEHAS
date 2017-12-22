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

$isicDbCardStatuses = IsicDB::factory('CardStatuses');
$isicDbCard = IsicDB::factory('Cards');

echo "<pre>\n";
echo date('H:i:s') . "\n";

$cards = array(
    'S372902283580L',
    'S372902283782F',
    'S372902283702J',
    'S372902283832X',
    'S372902283879C',
    'S372902283766N',
    'S372902283765L',
    'S372902283736U',
    'S372902283801H',
    'S372902283971T',
    'S372902283889H',
    'S372902283755H',
    'S372902283834G',
    'S372500243345Q',
    'S372500243328X',
    'S372226546663J',
    'S372902283798K',
    'S372226546657C',
    'S372902283794N',
    'S372902283734Q',
    'S372902283877K',
    'S372902283707H',
    'S372902283785K',
    'S372500243273Q',
    'S372226546670J',
    'S372500243418J',
    'S372902283720N',
    'S372226546692L',
    'S372500243292C',
    'S372500243277C',
    'S372902283955R',
    'S372500243337Q',
    'S372902283952L',
    'S372902283831K',
    'S372500243438G',
    'S372500243294R',
    'S372500243410C',
    'S372902283799M',
    'S372902283764J',
    'S372902283972K',
    'S372902283771J',
    'S372902283853D',
    'S372500243301K',
    'S372500243320A',
    'S372902283756J',
    'S372902283964U',
    'S372500243458G',
    'S372500243222M',
    'S372500243336N',
    'S372500243356M',
    'S372902283885A',
    'S372500243419U',
    'S372902283714R',
    'S372902283958N',
    'S372500243415M',
    'S372500243210N',
    'S372500243214K',
    'S372902283763H',
    'S372902283881N',
    'S372500243300J',
    'S372902283883R',
    'S372902283788G',
    'S372500243295J',
    'S372902284262L',
    'S372500243368K',
    'S372500243369M',
    'S372500243373F',
    'S372500243239J',
    'S372500243459J',
    'S372226546695R',
    'S372500243329N',
    'S372902283847G',
    'S372500243331N',
    'S372500243387J',
    'S372902283919F',
    'S372902283833N',
    'S372902283927R',
    'S372500243353R',
    'S372500243332F',
    'S372902283920N',
    'S372500243395J',
    'S372902283837L',
    'S372500243407L',
    'S372902283911L',
    'S372500243392N',
    'S372500243404F',
    'S372500243342K',
    'S372500243445F',
    'S372226263348N',
    'S372226263166J',
    'S372226258515L',
    'S372226263163N',
    'S372226263076N',
    'S372226258536M',
    'S372226258509N',
    'S372226263214N',
    'S372226263140T',
    'S372226263493R',
    'S372226258377M',
    'S372226263148N',
    'S372226263526K',
    'S372226258434J',
    'S372226263375L',
    'S372226263365H',
    'S372226263425J',
    'S372226263529F',
    'S372226258450K',
    'S372226263352R',
    'S372226258456L',
    'S372226263337R',
    'S372226263355N',
    'S372226263457F',
    'S372226263453T',
    'S372226263098G',
    'S372226263507N',
    'S372226263471N',
    'S372226263517J',
    'S372226263256N',
    'S372226258513H',
    'S372226258366H',
    'S372226258428L',
    'S372226263296C',
    'S372226263189D',
    'S372226263287K',
    'S372226263247L',
    'S372226263631Q',
    'S372226258485N',
    'S372226263242C',
    'S372226263338T',
    'S372226263113K',
    'S372226258371L',
    'S372226263118U',
    'S372226263388L',
    'S372226263437R',
    'S372226263054K',
    'S372226263103F',
    'S372226263069N',
    'S372226263351E',
    'S372226263260H',
    'S372226263400K',
    'S372226258424D',
    'S372226258508M',
    'S372226263068D',
    'S372226258406A',
    'S372226263123E',
    'S372226263111R',
    'S372226258531N',
    'S372226263200K',
    'S372500200451R',
    'S372226263062W',
    'S372226263329Q',
    'S372226258408M',
    'S372226263065R',
    'S372226263392E',
    'S372226258364M',
    'S372226263108F',
    'S372226263440J',
    'S372226258398N',
    'S372226263331F',
    'S372226263532H',
    'S372226258373P',
    'S372226263217J',
    'S372226263372Q',
    'S372226263288L',
    'S372226263283N',
    'S372226258511E',
    'S372226263468N',
    'S372226263485Q',
    'S372226263465R',
    'S372226263467K',
    'S372226263501C',
    'S372226263405J',
    'S372226258370J',
    'S372226263459U',
    'S372226263531Q',
    'S372226263430N',
    'S372226263492E',
    'S372226258491K',
    'S372226258417P',
    'S372226263084N',
    'T372150931695N',
    'T372151271298D',
    'T372151271583T',
    'T372151271380N',
    'T372151271614T',
    'T372151271592M',
    'T372151271600R',
    'T372150930733G',
    'T372150931671H',
    'T372151271412F',
    'T372151271350K',
    'T372151271617P',
    'T372150931676Q',
    'T372151271504N',
    'T372151271603D',
    'T372151271615K',
    'T372151271450U',
    'T372150931850Q'
        
);

$rowCount = 0;
foreach ($cards as $isic_number) {
    echo $isic_number . "\n";
    $sql = '
        select 
            c.* 
        from 
            module_isic_card as c
        where
            c.isic_number = ? and
            c.active = 1
    ';
    $res = $database->query($sql, $isic_number);
    if ($data = $res->fetch_assoc()) {
        $statusId = $isicDbCardStatuses->getCardStatusUserStatusMissingId($data['type_id']);
        echo ' ==> ' . $data['id'] . ': ' . $isic_number . ', '. $statusId . "\n";
        $isicDbCard->deActivate($data['id'], $statusId);
    } else {
        echo $isic_number . "\n";
        // echo ' ==> NONE' . "\n";
    }
    $rowCount++;
}

echo "\n";
echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
