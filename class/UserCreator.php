<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");


class UserCreator {
    private $db = false;
    /**
     * Folder for user pictures
     *
     * @var string
     * @access protected
     */
    var $u_pic_folder = "/user";

    /**
     * Tmp-folder for user pictures
     *
     * @var string
     * @access protected
     */
    var $u_pic_folder_tmp = "/user_tmp";

    /**
     * Picture prefix for user pics
     *
     * @var string
     * @access protected
     */
    var $u_pic_prefix = "USER";
    
    /**
     * Folder for application pictures
     *
     * @var string
     * @access protected
     */
    var $c_pic_folder = "/isic";

    /**
     * Tmp-folder for application pictures
     *
     * @var string
     * @access protected
     */
    var $c_pic_folder_tmp = "/isic_tmp";

    /**
     * Picture prefix for application pics
     *
     * @var string
     * @access protected
     */
    var $c_pic_prefix = "ISIC";
    
    private $infoFields = array (
        'faculty' => 'person_stru_unit2',
        'class' => 'person_class',
        'position' => 'person_position',
        'structure_unit' => 'person_stru_unit',
//        'course' => '',
    );
    
    public function __construct() {
        $this->db = &$GLOBALS['database'];
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
    }

    public function createFromCards() {
        $result = $this->getCards();
        $counter = 0;
        $total = $result->num_rows();
        while ($data = $result->fetch_assoc()) {
            echo (++$counter) . ' / ' . $total . ': ' . $data['id'] . "\n";
            $this->createUserAndStatuses($data);        
        }
        echo "Done ... \n";
    }
    
    private function getCards() {
        $r = &$this->db->query('
            SELECT 
                `module_isic_card`.* 
            FROM 
                `module_isic_card` 
            WHERE 
                `module_isic_card`.`active` = !
                
            ', 
            0
        );
        return $r;
    }

    public function createFromApplications() {
        $result = $this->getApplications();
        $counter = 0;
        $total = $result->num_rows();
        while ($data = $result->fetch_assoc()) {
            echo (++$counter) . ' / ' . $total . ': ' . $data['id'] . "\n";
            $this->createUserAndStatuses($data);        
        }
        echo "Done ... \n";
    }
    
    private function getApplications() {
        $r = &$this->db->query('
            SELECT 
                `module_isic_application`.* 
            FROM 
                `module_isic_application` 
            WHERE 
                `module_isic_application`.`type_id` <> !
                
            ', 
            0
        );
        return $r;
    }
    
    private function createUserAndStatuses($cardFields) {
        $userData = $this->isicDbUsers->getRecordByCode($cardFields['person_number']);
        if (!$userData) {
            $userFields = $this->getUserFieldsFromCardFields($cardFields);
            $userId = $this->isicDbUsers->insertRecord($userFields);
            $userData = $this->isicDbUsers->getRecord($userId);
        }
        $userUpdate = false;
        if (!trim($userData['name_first'])) {
            $userUpdate['name_first'] = $cardFields['person_name_first'];
        }
        if (!trim($userData['name_last'])) {
            $userUpdate['name_last'] = $cardFields['person_name_last'];
        }
        if (!trim($userData['email'])) {
            $userUpdate['email'] = $cardFields['person_email'];
        }
        if (!trim($userData['phone'])) {
            $userUpdate['phone'] = $cardFields['person_phone'];
        }
        if ($userData['birthday'] == '0000-00-00') {
            $userUpdate['birthday'] = $cardFields['person_birthday'];
        }
        if (!trim($userData['pic'])) {
            $userUpdate['pic'] = $this->updateUserPic($userData['user'], $cardFields['id']);
        }
        if ($userUpdate) {
            $this->isicDbUsers->updateRecord($userData['user'], $userUpdate);
        }
        
        $this->isicDbUserStatuses->setUserStatusesBySchoolCardType($userData['user'], $cardFields['school_id'], $cardFields['type_id'], 0);
    }
    
    private function getUserFieldsFromCardFields($cardFields) {
        return array(
            'user_code' => $cardFields['person_number'],
            'name_first' => $cardFields['person_name_first'],
            'name_last' => $cardFields['person_name_last'],
            'email' => $cardFields['person_email'],
            'phone' => $cardFields['person_phone'],
            'birthday' => $cardFields['person_birthday'],
        );
    }
    
    function updateUserPic($user, $card) {
        if ($user && $card) {
            $user_pic = '/' . $GLOBALS["directory"]["upload"] .  $this->u_pic_folder . '/' . $this->u_pic_prefix . str_pad($user, 10, '0', STR_PAD_LEFT);
            $card_pic = '/' . $GLOBALS["directory"]["upload"] . $this->c_pic_folder . '/' . $this->c_pic_prefix . str_pad($card, 10, '0', STR_PAD_LEFT);

            $u_pic_filename = SITE_PATH . $user_pic . '.jpg';
            $u_pic_filename_t = SITE_PATH . $user_pic . '_thumb.jpg';

            $c_pic_filename = SITE_PATH . $card_pic . '.jpg';
            $c_pic_filename_t = SITE_PATH . $card_pic . '_thumb.jpg';

            if (file_exists($c_pic_filename)) {
                @copy($c_pic_filename, $u_pic_filename);
            }
            if (file_exists($c_pic_filename_t)) {
                @copy($c_pic_filename_t, $u_pic_filename_t);
            }

            return $user_pic . '.jpg';
        }
        return '';
    }
    
    public function assignStatusInfoFields() {
        $this->isicDbUserStatuses->setUpdateUserGroups(false);
        $result = $this->getStatuses();
        $counter = 0;
        $total = $result->num_rows();
        while ($data = $result->fetch_assoc()) {
            echo (++$counter) . ' / ' . $total . ': ' . $data['id'] . "\n";
            $cardData = $this->getTableRecordByStatus('module_isic_card', $data, '');
            $applData = $this->getTableRecordByStatus('module_isic_application', $data, 'AND state_id >= 5');
            if ($applData || $cardData) {
                $this->updateStatusRecord($data, $applData ? $applData : $cardData);        
            }
        }
        echo "Done ... \n";
    }
    
    private function getStatuses() {
        $r = &$this->db->query('
            SELECT 
                `module_user_status_user`.*,
                `module_user_users`.`user_code`,
                `module_user_status`.`card_types`
            FROM 
                `module_user_status_user`,
                `module_user_users`,
                `module_user_status`
            WHERE
                `module_user_status_user`.`user_id` = `module_user_users`.`user` AND
                `module_user_status_user`.`status_id` = `module_user_status`.`id`
            ORDER BY 
                `module_user_status_user`.`id` 
            ' 
        );
        return $r;
    }
    
    private function getTableRecordByStatus($table, $statusRecord, $filter) {
        $r = &$this->db->query('
            SELECT 
                *
            FROM 
                ?f
            WHERE
                `person_number` = ? AND
                `school_id` = ! AND
                `type_id` IN (!)
                !
            ORDER BY 
                `id` DESC
            LIMIT 1
            ',
            $table,
            $statusRecord['user_code'],
            $statusRecord['school_id'],
            $statusRecord['card_types'],
            $filter
        );
        return $r->fetch_assoc();
    }

    private function updateStatusRecord($statusRecord, $updateRecord) {
        $updateData = false;
        foreach ($this->infoFields as $statusField => $updateField) {
            if (!$statusRecord[$statusField] && $updateRecord[$updateField]) {
                $updateData[$statusField] = $updateRecord[$updateField];
            } 
        }
        if ($updateData) {
            echo "\n==== STATUS ===\n";
            print_r($statusRecord);
            echo "\n==== UPDATE ===\n";
            print_r($updateRecord);
            echo "\n==== N E W  ===\n";
            print_r($updateData);
            echo "\n";
            $this->isicDbUserStatuses->updateRecord($statusRecord['id'], $updateData);
        }
    }
}
