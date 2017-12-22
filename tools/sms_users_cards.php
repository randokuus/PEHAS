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
require_once(SITE_PATH . "/class/IsicDB.php");

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

class SMSUserCardReport {
    /**
     * @var Database
     */
    private $db;
    private $bankList;
    private $regionList;

    public $titleFields = array(
        'Eesnimi',
        'Perenimi',
        'Isikukood',
        'email',
        'Telefon',
        'Andmevahetus',
        'Kaarditüüp',
        'Liik',
        'Kool',
        'Regioon',
        'Pank'
    );
    private $personFields = array(
        'name_first',
        'name_last',
        'user_code',
        'email',
        'phone',
        'data_sync_allowed'
    );

    private $cardFields = array(
        'card_type_name',
        'card_kind_name',
        'school_name',
    );


    public function __construct($db) {
        $this->db = $db;
        $this->bankList = $this->getBankList();
        $this->regionList = $this->getRegionList();
    }

    public function showPersonList() {
        $sql = '
            SELECT *
            FROM
                `module_user_users` AS `u`
            WHERE
                `u`.`phone` <> ? AND
                `u`.`active` = 1

            ORDER BY
                `u`.`user`
            
        ';
        $res = $this->db->query($sql, '');
        $out = fopen('php://output', 'w');
        while ($data = $res->fetch_assoc()) {
            $person = array();
            foreach ($this->personFields as $field) {
                $person[$field] = $data[$field];
            }
            $cards = $this->getCardData($data['user_code']);
            foreach ($cards as $card) {
//                echo implode(',', array_merge($person, $card)) . PHP_EOL;
                fputcsv($out, array_merge($person, $card));
            }
        }
        fclose($out);
    }

    public function getCardData($personNumber) {
        $sqlCard = '
            SELECT
                `c`.`isic_number`,
                `c`.`bank_id`,
                `ct`.`name` AS `card_type_name`,
                `ck`.`name` AS `card_kind_name`,
                `s`.`name` AS `school_name`,
                `s`.`region_id`
            FROM
                `module_isic_card` AS `c`,
                `module_isic_card_type` AS `ct`,
                `module_isic_card_kind` AS `ck`,
                `module_isic_school` AS `s`
            WHERE
                `c`.`person_number` = ? AND
                `c`.`active` = 1 AND
                `c`.`type_id` = `ct`.`id` AND
                `c`.`kind_id` = `ck`.`id` AND
                `c`.`school_id` = `s`.`id`
        ';
        $list = array();
        $res = $this->db->query($sqlCard, $personNumber);
//        echo $this->db->show_query();
        while ($data = $res->fetch_assoc()) {
            $card = array();
            foreach ($this->cardFields as $field) {
                $card[$field] = $data[$field];
            }

            $card['region_name'] = $this->regionList[$data['region_id']];
            $card['bank_name'] = $this->bankList[$data['bank_id']];
            $list[] = $card;
        }
        if (count($list) == 0) {
            $list[] = array('');
        }
        return $list;
    }

    public function getDataList($table) {
        $list = array();
        foreach ($this->db->fetch_all('SELECT * FROM ?f', $table) as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    public function getBankList() {
        $list = array(0 => '-');
        return array_merge($list, $this->getDataList('module_isic_bank'));
    }

    public function getRegionList() {
        $list = array(0 => '-');
        return array_merge($list, $this->getDataList('module_isic_region'));
    }
}


$ucr = new SMSUserCardReport($database);

echo PHP_EOL . 'Start' . PHP_EOL;
echo implode(',', $ucr->titleFields) . PHP_EOL;
$ucr->showPersonList();
echo PHP_EOL . 'Done' . PHP_EOL;
exit();
