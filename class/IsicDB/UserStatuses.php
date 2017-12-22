<?php

class IsicDB_UserStatuses extends IsicDB {
    const origin_user = 1; // record added/modified by user manually
    const origin_ehis = 2; // record added/modified by ehis query automatically
    const origin_bank = 3; // record added/modified by bank automatically
    const origin_card_import = 4; // record added/modified by card import automatically
    const origin_ehl = 5; // record added/modified by ehl query automatically

    protected $table = 'module_user_status_user';

    protected $insertableFields = array(
        'status_id', 'user_id', 'active', 'school_id', 'faculty', 'class', 'course', 'position', 'structure_unit',
        'addtime', 'adduser', 'group_id', 'addtype', 'expiration_date'
    );

    protected $updateableFields = array(
        'status_id', 'user_id', 'active', 'school_id', 'faculty', 'class', 'course', 'position', 'structure_unit',
        'modtime', 'moduser', 'group_id', 'modtype', 'expiration_date'
    );

    protected $searchableFields = array(
        'status_id', 'user_id', 'active', 'school_id', 'group_id', 'addtype', 'expiration_date'
    );

    protected $requiredFields = array(
        'add' => array('group_id', 'user_id'),
        'modify' => array()
    );

    protected $currentOrigin = self::origin_user;

    protected $updateUserGroups = true;

    public function insertRecord($data) {
        $common = IsicCommon::getInstance();
        $data['addtime'] = $this->db->now();
        $data['adduser'] = $common->getLogUserId($this->userid);

        $group = IsicDB::factory('UserGroups');
        $groupData = $group->getRecord($data['group_id']);
        $data['status_id'] = $groupData['user_status_id'];
        $data['school_id'] = $groupData['isic_school'];
        if (!isset($data['addtype'])) {
            $data['addtype'] = $this->getCurrentOrigin();
        }

        $addedId = parent::insertRecord($data);
        if ($this->updateUserGroups) {
            $user = IsicDB::factory('Users');
            $user->updateGroups($data['user_id']);
        }
        $statusData = $this->getRecord($addedId);
        self::assert($statusData);
        $validities = IsicDB::factory('CardValidities');
        $validities->insertOrUpdateRecordByUserStatus($statusData);

        return $addedId;
    }

    public function updateRecord($id, $data) {
        $common = IsicCommon::getInstance();
        $data['modtime'] = $this->db->now();
        $data['moduser'] = $common->getLogUserId($this->userid);
        if (!isset($data['modtype'])) {
            $data['modtype'] = $this->getCurrentOrigin();
        }

        $oldUserStatusData = $this->getRecord($id);
        parent::updateRecord($id, $data);
        $newUserStatusData = $this->getRecord($id);
        self::assert($oldUserStatusData && $newUserStatusData);
        if ($this->updateUserGroups) {
            $user = IsicDB::factory('Users');
            $user->updateGroups($newUserStatusData['user_id']);
        }
        if($oldUserStatusData['active'] != $newUserStatusData['active']) {
            $validities = IsicDB::factory('CardValidities');
            $validities->insertOrUpdateRecordByUserStatus($newUserStatusData);
        }
    }

    public function getRecordByStatusUserSchool($status_id, $user_id, $school_id) {
        $r = $this->findRecords(
            array(
                'status_id' => $status_id,
                'user_id' => $user_id,
                'school_id' => $school_id,
                'active' => 1
            ),
            0,
            1
        );
        return count($r) == 1 ? $r[0] : false;
    }

    public function getRecordByGroupUser($group_id, $user_id) {
        $r = $this->findRecords(
            array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'active' => 1
            ),
            0,
            1
        );
        //echo $this->db->show_query();
        return count($r) == 1 ? $r[0] : false;
    }

    public function listRecordsByUser($user_id) {
        $userStatusTypes = IsicDB::factory('UserStatusTypes');
        $schools = IsicDB::factory('Schools');
        $groups = IsicDB::factory('UserGroups');
        $r = $this->db->query(
            "
                SELECT
                    `s`.*,
                    `t`.`name` AS 'title',
                    `h`.`name` as 'school',
                    `g`.`name` as 'group'
                FROM
                    ?f AS `s`
                LEFT JOIN
                    ?f AS `t` ON `s`.?f = `t`.?f
                LEFT JOIN
                    ?f AS `h` ON `s`.?f = `h`.?f
                LEFT JOIN
                    ?f AS `g` ON `s`.?f = `g`.?f
                WHERE
                    ?f = ?
            ",
            $this->table,
            $userStatusTypes->getTable(),
            'status_id',
            $userStatusTypes->getPK(),
            $schools->getTable(),
            'school_id',
            $schools->getPK(),
            $groups->getTable(),
            'group_id',
            $groups->getPK(),
            'user_id',
            $user_id
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function activate($id) {
        $this->updateRecord($id, array('active' => '1'));
    }

    public function deActivate($id) {
        $this->updateRecord($id, array('active' => '0'));
    }

    /**
     * Returns user status record
     *
     * @param int $id user status  record id
     * @return array of user data
    */
    public function getRecordById($id) {
        if ($id) {
            return $this->getRecord($id);
        }
        return false;
    }

    function setUserStatusesBySchoolCardType($userId, $schoolId, $cardTypeId, $cardTypesForAddAllowed) {
        $userGroups = IsicDB::factory('UserGroups');
        $userStatusTypes = IsicDB::factory('UserStatusTypes');

        $statusTypeList = $userStatusTypes->getRecordsByCardTypeAndAddIfNotFound($cardTypeId);

        foreach ($statusTypeList as $status) {
            $userStatus = $this->getRecordByStatusUserSchool($status['id'], $userId, $schoolId);
            if (!$userStatus) {
                $group = $userGroups->getRecordBySchoolStatusAutomaticAndAddIfNotFound($schoolId, $status['id'], 0, $cardTypesForAddAllowed);
                $insertData = array(
                    'user_id' => $userId,
                    'group_id' => $group['id'],
                    'active' => 1,
                    'addtype' => $this->getCurrentOrigin()
                );
                $this->insertRecord($insertData);
            }
        }
    }

    public function getRecordByUserSchoolCardType($userId, $schoolId, $cardTypeId) {
        $userStatusList = $this->getAllRecordsByUserSchoolCardType($userId, $schoolId, $cardTypeId);
        if ($userStatusList && $userStatusList[0] != null) {
            return $userStatusList[0];
        }
        return false;
    }

    /**
     * Returns last statuses for each school of the appropriate card types
     */
    public function findLastRecordsByUserCardType($userId, $cardTypeId) {
        $userStatusTypes = IsicDB::factory('UserStatusTypes');
        $table = $this->getTableQuoted();
        $ustTable = $userStatusTypes->getTableQuoted();
        $ustPrimary = $userStatusTypes->getPrimaryKeyQuoted();
        $r = $this->db->query(
            "
                SELECT $table.*
                FROM $table, $ustTable
                WHERE
                    $table.`status_id` = $ustTable.$ustPrimary AND
                    $table.`user_id` = ! AND
                    FIND_IN_SET(!, $ustTable.`card_types`)
                ORDER BY $table.`school_id`, $table.`active` DESC, $table.`addtime` DESC
            ",
            (int)$userId,
            (int)$cardTypeId
        );
        self::assertResult($r);
        $prevSchoolId = 0;
        $statusList = array();
        foreach ($r->fetch_all() as $data) {
            if ($prevSchoolId != $data['school_id']) {
                $prevSchoolId = $data['school_id'];
                $statusList[] = $data;
            }
        }
        return $statusList;
    }

    public function getAllRecordsByUserSchoolCardType($userId, $schoolId, $cardTypeId) {
        $userStatusTypes = IsicDB::factory('UserStatusTypes');
        $statusTypeList = $userStatusTypes->getRecordsByCardType($cardTypeId);
        $userStatusList = array();
        foreach ($statusTypeList as $statusType) {
            $userStatusList = array_merge(
                $userStatusList,
                $this->getAllRecordsByStatusUserSchool($statusType['id'], $userId, $schoolId)
            );
        }
        return $userStatusList;
    }

    public function getAllRecordsByStatusUserSchool($status_id, $user_id, $school_id) {
        $filters = array(
            'status_id' => $status_id,
            'user_id' => $user_id,
            'school_id' => $school_id,
            'active' => 1
        );
        return $this->findRecords($filters);
    }

    public function getAllRecordsByUserSchool($user_id, $school_id) {
        return $this->findRecords(
            array(
                'user_id' => $user_id,
                'school_id' => $school_id,
                'active' => 1
            )
        );
    }

    public function getAllRecordsByStatusUser($status_id, $user_id) {
        return $this->findRecords(
            array(
                'status_id' => $status_id,
                'user_id' => $user_id,
                'active' => 1
            )
        );
    }

    public function getAllAutomaticRecordsByGroupUser($group_id, $user_id, $addType = self::origin_ehis) {
        return $this->findRecords(
            array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'addtype' => $addType,
                'active' => 1
            )
        );
    }

    public function getAllAutomaticRecordsByUser($user_id, $addType = self::origin_ehis) {
        return $this->findRecords(
            array(
                'user_id' => $user_id,
                'addtype' => $addType,
                'active' => 1
            )
        );
    }

    public function getAllAutomaticDeactivatedRecordsByUser($user_id, $addType = self::origin_ehis) {
        return $this->findRecords(
            array(
                'user_id' => $user_id,
                'addtype' => $addType,
                'active' => 0
            )
        );
    }

    public function deactivateAllAutomaticRecordsByUser($user_id, $addType = self::origin_ehis) {
        return $this->deactivateAllAutomaticRecordsByUserExceptGivenIds($user_id, array(), $addType);
    }

    public function deactivateAllAutomaticRecordsByUserExceptGivenIds($user_id, $validIds, $addType = self::origin_ehis) {
        $deactivatedIdList = false;
        $statusList = $this->getAllAutomaticRecordsByUser($user_id, $addType);
        foreach ($statusList as $status) {
            if (!in_array($status['id'], $validIds)) {
                $this->updateRecord($status['id'], array('active' => 0));
                $deactivatedIdList[] = $status['id'];
            }
        }
        return $deactivatedIdList;
    }

    public function setExpirationForAllAutomaticRecordsByUserExceptGivenIds($user_id, $validIds,
                                                                            $addType = self::origin_ehis,
                                                                            $expirationDate) {
        $expirationIdList = false;
        $statusList = $this->getAllAutomaticRecordsByUser($user_id, $addType);
        foreach ($statusList as $status) {
            if (!in_array($status['id'], $validIds) && $status['expiration_date'] == IsicDate::EMPTY_DATE) {
                $this->updateRecord($status['id'], array('expiration_date' => $expirationDate));
                $expirationIdList[] = $status['id'];
            }
        }
        return $expirationIdList;
    }

    /**
     * @param $currentOrigin the $currentOrigin to set
     */
    public function setCurrentOrigin($currentOrigin) {
        $this->currentOrigin = $currentOrigin;
    }

    /**
     * @return the $currentOrigin
     */
    public function getCurrentOrigin() {
        return $this->currentOrigin;
    }

    public function listOrigins() {
        return array(
            self::origin_user,
            self::origin_ehis,
            self::origin_bank,
            self::origin_card_import,
            self::origin_ehl
        );
    }

    public function getUsersWithAutomaticStatuses() {
        return $this->getUsersWithAutomaticStatusesForGivenSchools();
    }

    public function getUsersWithAutomaticStatusesForGivenSchools($schools = array(-1)) {
        $r = $this->db->query(
            "
                SELECT
                    `u`.`user_code`
                FROM
                    ?f AS `s`,
                    ?f AS `u`
                WHERE
                    `s`.`user_id` = `u`.`user` AND
                    `s`.`active` = 1 AND
                    `s`.`addtype` = ! AND
                    `u`.`external_status_check_allowed` = 1 AND
                    (`s`.`school_id` IN (!@) OR '-1' = '!@')
                GROUP BY
                    `u`.`user_code`
                ORDER BY
                    `u`.`user_code`
            ",
            $this->table,
            'module_user_users',
            self::origin_ehis,
            $schools, $schools
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    /**
     * @param $updateUserGroups the $updateUserGroups to set
     */
    public function setUpdateUserGroups($updateUserGroups) {
        $this->updateUserGroups = $updateUserGroups;
    }

    /**
     * @return the $updateUserGroups
     */
    public function getUpdateUserGroups() {
        return $this->updateUserGroups;
    }

    public function getBankCardUsers($bankId, $cardType, $addDate) {
        $r = $this->db->query(
            "
                SELECT
                    DISTINCT(`c`.`person_number`) AS `user_code`
                FROM
                    ?f AS `c`
                WHERE
                    `c`.`kind_id` = 2 AND
                    `c`.`bank_id` = ! AND
                    `c`.`state_id` < 4 AND
                    `c`.`adddate` < ? AND
                    length(`c`.`person_number`) = 11 AND
                    (`c`.`type_id` = ! OR ! = 0)
                ORDER BY
                    `c`.`person_number`
                LIMIT 100000
            ",
            'module_isic_card',
            $bankId,
            $addDate,
            $cardType,
            $cardType
        );
//        echo $this->db->show_query();
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getRegularCardUsers($cardType, $addDate) {
        $r = $this->db->query(
            "
                SELECT
                    DISTINCT(`c`.`person_number`) AS `user_code`
                FROM
                    ?f AS `c`
                WHERE
                    `c`.`kind_id` = 1 AND
                    `c`.`state_id` < 4 AND
                    `c`.`adddate` < ? AND
                    length(`c`.`person_number`) = 11 AND
                    (`c`.`type_id` = ! OR ! = 0)
                ORDER BY
                    `c`.`person_number`
                LIMIT 100000
            ",
            'module_isic_card',
            $addDate,
            $cardType,
            $cardType
        );
//        echo $this->db->show_query();
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getOriginEhl() {
        return self::origin_ehl;
    }

    public function isAutomaticStatus($addType) {
        return $addType == self::origin_ehis;
    }
}
