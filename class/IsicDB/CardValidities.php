<?php

class IsicDB_CardValidities extends IsicDB {

    protected $table = 'module_isic_card_validities';

    protected $insertableFields = array(
        'school_id', 'card_id', 'user_status_id', 'user_status_active', 'added'
    );

    protected $updateableFields = array(
        'user_status_id', 'user_status_active', 'modified'
    );

    protected $searchableFields = array(
        'school_id', 'card_id', 'user_status_id'
    );

    private $isicDbUsers;
    private $isicDbCards;

    public function __construct() {
        parent::__construct();
        $this->isicDbCards = IsicDB::factory('Cards');
    }

    public function insertRecord($data) {
        $data['added'] = $this->db->now();
        return parent::insertRecord($data);
    }

    public function updateRecord($id, $data) {
        $data['modified'] = $this->db->now();
        parent::updateRecord($id, $data);
    }

    public function insertOrUpdateRecordByUserStatus(array $userStatusData) {
        $users = IsicDB::factory('Users');
        $userData = $users->getRecord($userStatusData['user_id']);
        self::assert(is_array($userData));
        $cardList = $this->isicDbCards->findRecordsByStatusPersonNumber($userStatusData['status_id'], $userData['user_code']);
        foreach ($cardList as $cardData) {
            if ($this->isRecordRequiredForCardUserStatus($cardData, $userStatusData)) {
                $this->insertOrUpdateRecord($cardData, $userStatusData);
            }
        }
    }

    public function insertOrUpdateRecordByCard(array $cardData) {
        $users = IsicDB::factory('Users');
        $userId = $users->getIdByUserCode($cardData['person_number']);
        self::assert($userId);
        $userStatuses = IsicDB::factory('UserStatuses');
        $userStatusList = $userStatuses->findLastRecordsByUserCardType($userId, $cardData['type_id']);
        foreach ($userStatusList as $userStatusData) {
            if ($this->isRecordRequiredForCardUserStatus($cardData, $userStatusData)) {
                $this->insertOrUpdateRecord($cardData, $userStatusData);
            }
        }
    }

    private function isRecordRequiredForCardUserStatus(array $cardData, array $userStatusData) {
        // not if status was deactivated before the card's creation time
        if (!$userStatusData['active'] && strtotime($userStatusData['modtime']) < strtotime($cardData['adddate'])) {
            return false;
        }
        // not if status was added after the card's deactivation time
        if ($this->isicDbCards->isDeactivated($cardData) && strtotime($userStatusData['addtime']) > strtotime($cardData['deactivation_time'])) {
            return false;
        }

        // in case of card state is not activated of deactivated, only working with validities of the card school
        if (!$this->isicDbCards->isStateActivatedOrDeactivated($cardData['state_id'])) {
            return $cardData['school_id'] == $userStatusData['school_id'];
        }

        return true;
    }

    private function insertOrUpdateRecord(array $cardData, array $userStatusData) {
        $validityData = $this->getLastRecordBySchoolCardStatus($userStatusData['school_id'], $cardData['id'], $userStatusData['status_id']);
        if ($validityData) {
            $newData = $this->getRecordChanges($validityData, $userStatusData);
            if ($newData) {
                $this->updateRecord($validityData['id'], $newData);
            }
        } else {
            $this->insertRecord(array(
                'school_id' => $userStatusData['school_id'],
                'card_id' => $cardData['id'],
                'user_status_id' => $userStatusData['id'],
                'user_status_active' => $userStatusData['active']
            ));
        }
    }

    private function getRecordChanges(array $validityData, array $userStatusData) {
        $newData = false;
        if ($validityData['user_status_active'] != $userStatusData['active']) {
            $newData = array();
            $newData['user_status_active'] = $userStatusData['active'];
            if (!$validityData['user_status_active'] && $userStatusData['active']) {
                $newData['user_status_id'] = $userStatusData['id'];
            }
        }
        return $newData;
    }

    public function findActiveRecordsByCardSchools($cardId, array $schoolsIds) {
        return $this->findActiveAndOrInactiveRecordsByCardSchools($cardId, $schoolsIds, true);
    }

    public function findInactiveRecordsByCardSchools($cardId, array $schoolsIds) {
        return $this->findActiveAndOrInactiveRecordsByCardSchools($cardId, $schoolsIds, false);
    }

    public function findAllRecordsByCardSchools($cardId, array $schoolsIds) {
        return $this->findActiveAndOrInactiveRecordsByCardSchools($cardId, $schoolsIds);
    }

    private function findActiveAndOrInactiveRecordsByCardSchools($cardId, array $schoolsIds, $active = null) {
        if(count($schoolsIds) == 0) {
            return array();
        }
        $t = $this->getTableQuoted();
        if (is_null($active)) {
            $activeFilter = "";
        } else {
            $activeFilter = "AND $t.`user_status_active` = " . ($active ? "1" : "0");
        }
        $r = $this->db->query(
            $this->getBaseQuery() . "
            WHERE
                $t.`card_id` = ? AND
                $t.`school_id` IN (?@)
                $activeFilter
            ",
            $cardId,
            $schoolsIds
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function isValidCardForSchools($cardId, array $schoolsIds) {
        return count($this->findActiveRecordsByCardSchools($cardId, $schoolsIds)) > 0;
    }

    public function wasEverValidCardForSchools($cardId, array $schoolsIds) {
        return count($this->findAllRecordsByCardSchools($cardId, $schoolsIds)) > 0;
    }

    public function listRecordsByCard($cardId) {
        $r = $this->db->query("
            SELECT
                `v`.`id`,
                `v`.`school_id`,
                `s`.`name` AS 'school_name',
                `us`.`structure_unit` AS 'status_structure_unit',
                `us`.`class` AS 'status_class',
                `us`.`course` AS 'status_course',
                `us`.`position` AS 'status_position',
                `v`.`user_status_active`
            FROM
                ?f AS `v`
            LEFT JOIN
                `module_isic_school` AS `s` ON `v`.`school_id` = `s`.`id`
            LEFT JOIN
                `module_user_status_user` AS `us` ON `v`.`user_status_id` = `us`.`id`
            WHERE
                `v`.`card_id` = ?
            ORDER BY `v`.`id`",
            $this->table,
            $cardId
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    private function getLastRecordBySchoolCardStatus($schoolId, $cardId, $statusId) {
        $r = $this->db->query("
            SELECT
                `v`.*
            FROM
                ?f AS `v`
            LEFT JOIN
                `module_user_status_user` AS `us` ON `v`.`user_status_id` = `us`.`id`
            WHERE
                `v`.`school_id` = ? AND
                `v`.`card_id` = ? AND
                `us`.`status_id` = ?
            ORDER BY `id` DESC
            LIMIT 1",
            $this->table,
            $schoolId,
            $cardId,
            $statusId
        );
        self::assertResult($r);
        return $r->fetch_assoc();
    }

}
