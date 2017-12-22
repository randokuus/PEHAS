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
require_once(SITE_PATH . "/class/IsicCommon.php");
require_once(SITE_PATH . "/class/IsicDB.php");
require_once(SITE_PATH . "/class/Isic/IsicUnitedTicketsSync.php");

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
$GLOBALS["language"] = &$language;


class FakeCard {
    public static function getCards(Database $db) {
        $sql = "select
          c.id,
          c.isic_number,
          ct.name as card_type,
          c.active,
          cs.sync_type_id,
          cs.addtime,
          cs.success,
          cs.tries
        from
          module_isic_card as c,
          module_isic_card_type as ct,
          module_isic_card_data_sync as cs
        where
          c.type_id = ct.id and
          c.bank_id = 1 and
          c.pan_number <> '' and
          ct.chip = 0 and
          c.id = cs.record_id and
          cs.record_type = 'card'";

        $cards = array();
        $res = $db->query($sql);
        while ($data = $res->fetch_assoc()) {
            if (!in_array($data['id'], $cards)) {
                $cards[] = $data['id'];
            }
        }
        return $cards;
    }

    public static function scheduleCards($cards) {
        /** @var IsicDB_CardDataSync $isicDbCardDataSync */
        $isicDbCardDataSync = IsicDB::factory('CardDataSync');
        foreach ($cards as $cardId) {
            echo $cardId . "\n";
            $data = array(
                'record_type' => $isicDbCardDataSync->getRecordTypeCard(),
                'record_id' => $cardId,
                'sync_type_id' => $isicDbCardDataSync->getSyncTypeDeactivate()
            );
            $isicDbCardDataSync->insertRecord($data);
//            return;
        }
    }
}


echo "<pre>\n";
echo date('H:i:s') . "\n";

$cards = FakeCard::getCards($database);
FakeCard::scheduleCards($cards);

echo 'done ...' . date('H:i:s');
echo "\n</pre>\n";
