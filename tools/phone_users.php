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
require_once(SITE_PATH . '/class/IsicDB.php');

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


class PhoneUser {
    private $db;
    private $scholarCards = '1, 19, 25, 27, 28, 29, 30, 31';
    private $studentCards = '2, 11, 12, 18, 20';
    private $schoolNames = array(
        'Eesti Esimene Erakosmeetikakool',
        'Eesti Iluteeninduse Erakool',
        'G. Otsa nim Tallinna Muusikakool',
        'H. Elleri nim Tartu Muusikakool',
        'Haapsalu Kutsehariduskeskus',
        'Hiiumaa Ametikool',
        'Ida-Virumaa Kutsehariduskeskus',
        'Iluravi Rahvusvaheline Erakool',
        'Informaatika ja Arvutustehnika Kool',
        'Juuksurite Erakool "Maridel"',
        'Järvamaa Kutsehariduskeskus',
        'Kehtna Majandus- ja Tehnoloogiakool',
        'Kuressaare Ametikool',
        'Luua Metsanduskool',
        'M. I. Massaažikool',
        'Narva Kutseõppekeskus',
        'Olustvere Teenindus- ja Maamajanduskool',
        'Põltsamaa Ametikool',
        'Pärnu Saksa Tehnoloogiakool',
        'Pärnumaa Kutsehariduskeskus',
        'Rakvere Ametikool',
        'Räpina Aianduskool',
        'Sillamäe Kutsekool',
        'Tallinna Balletikool',
        'Tallinna Ehituskool',
        'Tallinna Erateeninduskool',
        'Tallinna Kopli Ametikool',
        'Tallinna Lasnamäe Mehaanikakool',
        'Tallinna Majanduskool',
        'Tallinna Polütehnikum',
        'Tallinna Teeninduskool',
        'Tallinna Transpordikool',
        'Tallinna Tööstushariduskeskus',
        'Tartu Kunstikool',
        'Tartu Kutsehariduskeskus',
        'Valgamaa Kutseõppekeskus',
        'Vana-Vigala Tehnika- ja Teeninduskool',
        'Viljandi Ühendatud Kutsekeskkool',
        'Võrumaa Kutsehariduskeskus',
        'Väike-Maarja Õppekeskus',
        'Eesti Hotelli- ja Turismikõrgkool',
        'Eesti Mereakadeemia',
        'Kaitseväe Ühendatud Õppeasutused',
        'Lääne-Viru Rakenduskõrgkool',
        'Sisekaitseakadeemia',
        'Tallinna Tervishoiu Kõrgkool',
        'Tartu Tervishoiu Kõrgkool'
    );
    
    private $workSchools;
    
    public function __construct($db) {
        $this->db = $db;
        $this->workSchools = implode(',', $this->findSchoolIds($this->schoolNames));
    }
    
    public function getParents() {
        $tmpSql = 'SELECT * FROM `module_user_users` WHERE phone <> ? AND children_list <> ? LIMIT 10000';
        $res = $this->db->query($tmpSql, '', '');
        $parentCount = 0;
        while ($data = $res->fetch_assoc()) {
            $children = explode(',', $data['children_list']);
            $hasScholarChildren = false;
            foreach ($children as $child) {
                if ($this->isScholarCardChild($child)) {
                    $hasScholarChildren = true;
                    break;
                }
            }
            if (!$hasScholarChildren) {
                echo $data['user_code'] . "\n";
                $parentCount++;
            }
        }
        echo "Parents: " . $parentCount . "\n";
    }
    
    public function isScholarCardChild($personNumber) {
        $sql = 'SELECT COUNT(*) AS tot FROM module_isic_card 
            WHERE 
                person_number = ? AND 
                type_id IN (?) AND
                active = 1 AND
                kind_id = 2
        ';
        $res = $this->db->query($sql, $personNumber, $this->scholarCards);
        // echo $this->db->show_query();
        while ($data = $res->fetch_assoc()) {
            return $data['tot'] > 0;
        }
    }
    

    public function getHighSchoolStudents() {
        $tmpSql = 'SELECT * FROM `module_user_users` WHERE birthday < ? AND phone <> ? ORDER BY birthday DESC';
        $res = $this->db->query($tmpSql, '1997-04-25', '');
        $okCount = 0;
        while ($data = $res->fetch_assoc()) {
            if ($this->isHighSchoolCardStudent($data['user_code']) &&
                !$this->isWorkSchoolCardStudent($data['user_code'])) {
                echo $data['user_code'] . "\n";
                $okCount++;
            }
        }
        echo "Students: " . $okCount . "\n";
    }

    public function getWorkSchoolStudents() {
        $tmpSql = 'SELECT * FROM `module_user_users` WHERE birthday < ? AND phone <> ? ORDER BY birthday DESC';
        $res = $this->db->query($tmpSql, '1997-04-25', '');
        $okCount = 0;
        while ($data = $res->fetch_assoc()) {
            if (!$this->isHighSchoolCardStudent($data['user_code']) &&
                $this->isWorkSchoolCardStudent($data['user_code'])) {
                echo $data['user_code'] . "\n";
                $okCount++;
            }
        }
        echo "Students: " . $okCount . "\n";
    }

    public function isHighSchoolCardStudent($personNumber) {
        $sql = 'SELECT kind_id, count(*) FROM module_isic_card 
            WHERE 
                person_number = ? AND 
                type_id IN (!) AND
                active = 1 AND
                NOT school_id IN (!)
            GROUP BY kind_id
        ';
        $res = $this->db->query($sql, $personNumber, $this->scholarCards, $this->workSchools);
        // echo $this->db->show_query();
        $kindList = array();
        while ($data = $res->fetch_assoc()) {
            $kindList[] = $data['kind_id'];
        }
        return (in_array(1, $kindList) && !in_array(2, $kindList));
    }

    public function isWorkSchoolCardStudent($personNumber) {
        $sql = 'SELECT kind_id, COUNT(*) AS tot FROM module_isic_card 
            WHERE 
                person_number = ? AND 
                type_id IN (!) AND
                active = 1 AND
                school_id IN (!)
            GROUP BY kind_id
        ';
        $res = $this->db->query($sql, $personNumber, $this->scholarCards, $this->workSchools);
        // echo $this->db->show_query();
        $kindList = array();
        while ($data = $res->fetch_assoc()) {
            $kindList[] = $data['kind_id'];
        }
        return (in_array(1, $kindList) && !in_array(2, $kindList));
    }
    
    public function findSchoolIds($schoolNames) {
        $schoolIds = array();
        foreach ($schoolNames as $schoolName) {
            $sql = 'SELECT id FROM module_isic_school WHERE name = ?';
            $res = $this->db->query($sql, $schoolName);
            while ($data = $res->fetch_assoc()) {
                $schoolIds[] = $data['id'];
            }
        }
        return $schoolIds;
    }
    
    public function findScholarCards() {
        $sql = 'SELECT * FROM module_isic_card_type WHERE name like ?';
        $res = $this->db->query($sql, '% õpilas%');
        $types = array();
        while ($data = $res->fetch_assoc()) {
            $types[] = $data['name'];
        }
        return $types;
    }
    
    public function getUniversityStudents() {
        $sql = 'SELECT person_number FROM `module_isic_card` WHERE active = 1 and type_id in (!) group by person_number';
        $res = $this->db->query($sql, $this->studentCards);
        $okCount = 0;
        while ($data = $res->fetch_assoc()) {
            if ($this->isUniversityCardStudent($data['person_number']) && 
                $this->hasPhone($data['person_number'])) {
                echo $data['person_number'] . "\n";
                $okCount++;
            }
        }
        echo "Students: " . $okCount . "\n";
    }
    
    public function isUniversityCardStudent($personNumber) {
        $sql = 'SELECT kind_id, COUNT(*) AS tot FROM module_isic_card 
            WHERE 
                person_number = ? AND 
                type_id IN (!) AND
                active = 1
            GROUP BY kind_id
        ';
        $res = $this->db->query($sql, $personNumber, $this->studentCards);
        // echo $this->db->show_query();
        $kindList = array();
        while ($data = $res->fetch_assoc()) {
            $kindList[] = $data['kind_id'];
        }
        return (in_array(1, $kindList) && !in_array(2, $kindList));
    }
    
    private function hasPhone($personNumber) {
        $sql = 'SELECT phone FROM module_user_users WHERE user_code = ?';
        $res = $this->db->query($sql, $personNumber, '');
        while ($data = $res->fetch_assoc()) {
            if (trim($data['phone'])) {
                return true;
            }
        }
        return false;
    }
}

$puser = new PhoneUser($database);
$puser->getParents();
// $puser->getHighSchoolStudents();
// $puser->getWorkSchoolStudents();
// $puser->getUniversityStudents();
print_r($t);