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
require_once(SITE_PATH . "/class/IsicCommon.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$t_db = $database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;


class TtyStudents {
    var $activeStudents = array();
    var $cardStudents = array();
    var $applStudents = array();
    var $nonActiveStudents = array();
    var $db = false;
    var $isic_common = false;
    var $system_user = 0;
    
    function TtyStudents() {
        $this->db = $GLOBALS['database'];
        $this->isic_common = IsicCommon::getInstance();
        $this->getActiveStudents("./tty_students.csv");
        $this->system_user = $this->isic_common->getUserIdByUsername(SYSTEM_USER);
        /*
        $this->getCardStudents();
        $this->getApplStudents();
        $this->getNonActiveStudents();
        */
    } 
    
    function getActiveStudents($fname) {
        $t_data = file_get_contents($fname);
        $this->activeStudents = explode("\n", $t_data);
    }

    function getStudentsData() {
        $cards = array();
        $appls = array();
        
        foreach ($this->activeStudents as $studentId) {
            /*
            $data = $this->getStudentCards($studentId);
            if ($data) {
                foreach ($data as $card) {
                    $this->deactivateCard($card['id']);
                    $cards[] = $card;
                }
            }
            */
            
            $data = $this->getStudentAppls($studentId);
            if ($data) {
                foreach ($data as $appl) {
                    $appls[] = $appl;
                    $this->deleteApplication($appl['id']);
                }
            }
        }
        print_r($cards);
        print_r($appls);
        
    }
    
    function getStudentCards($person_number) {
        $sql = "
            select 
                c.id,
                c.isic_number,
                c.person_name_first,
                c.person_name_last,
                c.person_number
            from 
                module_isic_card as c
            where 
                c.school_id = 6 and 
                c.kind_id = 1 and 
                c.type_id in (11, 12, 2, 18) and
                c.active = 1 and
                c.person_number = ? 
        ";
        $res =& $this->db->query($sql, $person_number);
        if ($res->num_rows()) {
            return $res->fetch_all();
        }
        return false;
    }
    
    function getStudentAppls($person_number) {
        $sql = "
            select
                a.id,
                a.person_name_first,
                a.person_name_last,
                a.person_number
            from 
                module_isic_application as a
            where 
                a.school_id = 6 and 
                a.type_id in (11, 12, 2, 18) and
                a.state_id = 1 and
                a.person_number = ?
        ";
        $res =& $this->db->query($sql, $person_number);
        if ($res->num_rows()) {
            return $res->fetch_all();
        }
        return false;
    }
    
    function deactivateCard($id) {
        $row_old = $this->isic_common->getCardRecord($id);
        $r2 = &$this->db->query("
        UPDATE
            module_isic_card
        SET
            `moddate` = NOW(),
            `moduser` = !,
            `active` = !,
            `state_id` = !,
            `deactivation_date` = NOW()
        WHERE
            `id` = !
        ", $this->system_user,
           0,
           $this->isic_common->c_state_deactivated,
           $id);
    
        // saving changes made to the card to log-table
        $this->isic_common->saveCardChangeLog(2, $id, $row_old, $this->isic_common->getCardRecord($id), $this->system_user);
    }
    
    function deleteApplication($id) {
        $check_data = $this->isic_common->getApplicationRecord($id);
        $this->isic_common->deleteApplication($check_data, $this->system_user);        
    }
    
    function getCardStudents() {
        $sql = "
            select 
                c.person_name_first,
                c.person_name_last,
                c.person_number,
                count(c.person_number) as tot
            from 
                module_isic_card as c
            where 
                c.school_id = 6 and 
                c.kind_id = 1 and 
                c.type_id in (11, 12, 2, 18) 
            group by
                c.person_number        
        ";
        $res =& $this->db->query($sql);
        while ($data = $res->fetch_assoc()) {
            $this->cardStudents[] = array(
                'id' => $data['person_number'],
                'first' => $data['person_name_first'],
                'last' => $data['person_name_last']
            );
        }
    }

    function getApplStudents() {
        $sql = "
            select
                a.person_name_first,
                a.person_name_last,
                a.person_number,
                count(a.person_number) as tot 
            from 
                module_isic_application as a
            where 
                a.school_id = 6 and 
                a.type_id in (11, 12, 2, 18) and
                a.state_id = 1
            group by
                a.person_number
        ";
        $res =& $this->db->query($sql);
        while ($data = $res->fetch_assoc()) {
            $this->applStudents[] = array(
                'id' => $data['person_number'],
                'first' => $data['person_name_first'],
                'last' => $data['person_name_last']
            );
        }
    }
    
    function getNonActiveStudents() {
        $this->processStudents($this->cardStudents);
        $this->processStudents($this->applStudents);
    }

    function processStudents($students) {
        foreach ($students as $student) {
            $this->assignNonActiveStudent($student);
        }
    }
    
    function assignNonActiveStudent($student) {
        if (!$this->isNonActiveStudent($student['id']) && !$this->isActiveStudent($student['id'])) {
            $this->nonActiveStudents[$student['id']] = $student;
        }
    }
    
    function isNonActiveStudent($student_id) {
        return array_key_exists($student_id, $this->nonActiveStudents);
    }
    
    function isActiveStudent($student_id) {
        return in_array($student_id, $this->activeStudents);
    }
    
    function getStudentList() {
        foreach ($this->nonActiveStudents as $student) {
            echo implode(',', $student) . "\n";
        }
    }
}

$tty = new TtyStudents();
//print_r($tty->activeStudents);
//print_r($tty->cardStudents);
//print_r($tty->applStudents);
//print_r($tty->nonActiveStudents);
//$tty->getStudentList();

$tty->getStudentsData();
//$tty->deactivateCard(105653);
//$tty->deleteApplication(25);
