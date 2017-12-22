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

$cards = array(
    
);


class QueryContainer {
    private $db;
    private $statusCardTypes = array();
    private $outCsv;
    private $titleFields = array(
        'Isic number',
        'PAN',
        'Card active',
        'Card type',
        'Card kind',
        'Card Expiration',
        'Person number',
        'First name',
        'Last name',
        'Birthday',
        'Card school regcode',
        'Card school name',
        'Card school EHIS',
        'Status school regcode',
        'Status school name',
        'Status school EHIS',
        'Status',
        'Addtype',
        'Status expiration',
        'Status active',
        'E-mail'
    );
    
    private $sql = '
        SELECT 
            c.isic_number,
            c.pan_number,
            c.active as card_active,
            c.type_id,
            ct.name as card_type,
            ck.name as card_kind,
            c.expiration_date,
            c.person_number, 
            c.person_name_first, 
            c.person_name_last, 
            c.person_birthday, 
            sc.regcode as card_school_regcode,
            sc.name as card_school_name,
            sc.ehis_code as card_school_ehis_code,
            s.regcode as status_school_regcode,
            s.name as status_school_name,
            s.ehis_code as status_school_ehis_code,
            us.name as s_name,
            usu.status_id,
            usu.addtype,
            usu.expiration_date as status_expiration_date,
            usu.active as s_active,
            c.person_email
        FROM 
            `module_isic_card` AS c, 
            `module_user_status` AS us,
            `module_user_status_user` AS usu,
            `module_isic_school` as s,
            `module_isic_school` as sc,
            `module_user_users` as u,
            `module_isic_card_type` as ct,
            `module_isic_card_kind` as ck
        WHERE 
            c.kind_id = 2 and 
            c.bank_id = 1 and
            c.school_id = sc.id and
            c.type_id = ct.id and
            c.kind_id = ck.id and

            u.user_code = c.person_number and
            u.user = usu.user_id and

            usu.school_id = s.id and
            usu.status_id = us.id and

            c.person_number = ?';
    
    public function __construct($db) {
        $this->db = $db;
        $this->outCsv = $this->initOutput();
        $this->statusCardTypes = $this->initStatusCardTypes();
    }
    
    private function initStatusCardTypes() {
        $sql = 'SELECT * FROM module_user_status';
        $res = $this->db->query($sql);
        $statusCardTypes = array();
        while ($data = $res->fetch_assoc()) {
            $statusCardTypes[$data['id']] = explode(',', $data['card_types']);
        }
        return $statusCardTypes;
    }
    
    private function initOutput() {
        $outCsv = fopen('php://output', 'w');
        if (!$outCsv) {
            exit('Could not open php output');
        }
        return $outCsv;
    }
    
    public function run($data) {
        $this->output($this->titleFields);
        foreach ($data as $isic_number) {
            $this->querySingle($isic_number);
        }
        $this->closeOutput();
    }
    
    private function querySingle($isic_number) {
        $res = $this->db->query($this->sql, $isic_number);
        // echo $database->show_query();
        while ($data = $res->fetch_assoc()) {
            if (!$this->isAllowedCardType($data['status_id'], $data['type_id'])) {
                continue;
            }
            
            $outList = array(
                $data['isic_number'],
                $data['pan_number'],
                $data['card_active'],
                $data['card_type'],
                $data['card_kind'],
                $data['expiration_date'],
                $data['person_number'],
                $data['person_name_first'],
                $data['person_name_last'],
                $data['person_birthday'],
                $data['card_school_regcode'],
                $data['card_school_name'],
                $data['card_school_ehis_code'],
                $data['status_school_regcode'],
                $data['status_school_name'],
                $data['status_school_ehis_code'],
                $data['s_name'],
                $data['addtype'],
                $data['status_expiration_date'],
                $data['s_active'],
                $data['person_email']
            );
            $this->output($outList);
        }
    }
    
    private function isAllowedCardType($statusId, $cardType) {
        if (!array_key_exists($statusId, $this->statusCardTypes)) {
            return false;
        }
        return in_array($cardType, $this->statusCardTypes[$statusId]);
    }
    
    public function output($data) {
        fputcsv($this->outCsv, $data);
    }
    
    private function closeOutput() {
        fclose($this->outCsv);
    }
}

$qc = new QueryContainer($GLOBALS['database']);
$qc->run($cards);

// echo "\n";
// echo 'done ...' . date('H:i:s');
// echo "\n</pre>\n";
