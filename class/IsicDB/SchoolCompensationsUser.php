<?php

class IsicDB_SchoolCompensationsUser extends IsicDB {

    protected $table = 'module_isic_school_compensation_user';

    protected $insertableFields = array(
        'compensation_id', 'person_number', 'school_id', 'status_id', 'start_date', 'end_date', 'sum', 'sum_used',
        'currency', 'compensation_type_list', 'active', 'application_type_list'
    );

    protected $updateableFields = array(
        'sum_used', 'active'
    );

    protected $searchableFields = array(
        'person_number', 'school_id', 'status_id', 'start_date', 'end_date'
    );

    public function getRecordByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId) {
        $r = $this->db->query("
            SELECT
                `sc`.*
            FROM
                ?f AS `sc`,
                ?f AS `us`
            WHERE
                `sc`.`person_number` = ? AND
                `sc`.`school_id` = ? AND
                `sc`.`status_id` = `us`.`id` AND
                FIND_IN_SET(?, `us`.`card_types`) AND
                `sc`.`start_date` <= ? AND
                `sc`.`end_date` >= ?
            ORDER BY `start_date` ASC
            LIMIT 1",
            $this->table,
            'module_user_status',
            $personNumber,
            $schoolId,
            $cardTypeId,
            $this->db->now(),
            $this->db->now()
        );
        self::assertResult($r);
        return $r->fetch_assoc();
    }

    public function getEHLCompensationDataByPersonCardType($personNumber, $cardTypeId) {
        $log = new IsicLogger();
        /** @var IsicDB_CardTypes $dbCardTypes */
        $dbCardTypes = IsicDB::factory('CardTypes');
        // First, check if current card type has EHL check assigned (via user status)
        if (!$dbCardTypes->isEHLCheckNeeded($cardTypeId)) {
            return false;
        }

        /** @var IsicDB_Users $dbUsers */
        $dbUsers = IsicDB::factory('Users');
        $userId = $dbUsers->getIdByUserCode($personNumber);
        if (!$userId) {
            return false;
        }

        /** @var IsicDB_UserStatuses $dbUserStatuses */
        $dbUserStatuses = IsicDB::factory('UserStatuses');
        $userStatusList = $dbUserStatuses->getAllAutomaticRecordsByUser($userId, IsicDB_UserStatuses::origin_ehl);

        $activeSchools = array();
        $maxCompensation = false;
        foreach ($userStatusList as $userStatus) {
            if (!in_array($userStatus['school_id'], $activeSchools)) {
                $activeSchools[] = $userStatus['school_id'];
            }
            $this->findAndSetActivity(
                array(
                    'person_number' => $personNumber,
                    'card_type_id' => $cardTypeId,
                    'school_id' => $userStatus['school_id'],
                    'active' => 1
                )
            );

            $compensation = $this->getCompensationDataByPersonSchoolCardType($personNumber, $userStatus['school_id'], $cardTypeId);
            if ($compensation['sum'] <= 0) {
                continue;
            }
            if (!$maxCompensation || $maxCompensation['sum'] < $compensation['sum']) {
                $maxCompensation = $compensation;
            }
        }

        // deactivating all compensation records that are given to schools without active EHL user status
        $userStatusList = $dbUserStatuses->getAllAutomaticDeactivatedRecordsByUser($userId, IsicDB_UserStatuses::origin_ehl);
        foreach ($userStatusList as $userStatus) {
            if (in_array($userStatus['school_id'], $activeSchools)) {
                continue;
            }
            $this->findAndSetActivity(
                array(
                    'person_number' => $personNumber,
                    'card_type_id' => $cardTypeId,
                    'school_id' => $userStatus['school_id'],
                    'active' => 0
                )
            );
        }

        return $maxCompensation;
    }

    public function getCompensationDataByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId) {
        $result = array(
            'sum' => 0,
            'id' => 0,
            'compensation_types' => array(),
            'application_types' => array()
        );
        $data = $this->getRecordByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId);
        if (!$data) {
            $dbSchoolCompensations = IsicDB::factory('SchoolCompensations');
            $data = $dbSchoolCompensations->getRecordBySchoolCardType($schoolId, $cardTypeId);
            if ($data) {
                $insertData = array(
                    'active' => 1,
                    'compensation_id' => $data['id'],
                    'person_number' => $personNumber,
                    'school_id' => $data['school_id'],
                    'status_id' => $data['status_id'],
                    'start_date' => $this->db->now(),
                    'end_date' => date('Y-m-d', strtotime('+' . $data['length_in_years'] . ' year')),
                    'sum' => $data['sum'],
                    'sum_used' => 0,
                    'currency' => $data['currency'],
                    'compensation_type_list' => $data['compensation_type_list'],
                    'application_type_list' => $data['application_type_list']
                );
                $this->insertRecord($insertData);
            }
        }

        $data = $this->getRecordByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId);
        if ($data && $data['active']) {
            $usedSum = $data['sum_used'];
            $dbCurrency = IsicDB::factory('Currency');
            $availableSum = $dbCurrency->getSumInDefaultCurrency($data["sum"], $data["currency"]) - $usedSum;
            $result['sum'] = $availableSum > 0 ? $availableSum : 0;
            $result['id'] = $data['id'];
            $result['compensation_types'] = explode(',', $data['compensation_type_list']);
            $result['application_types'] = explode(',', $data['application_type_list']);
        }
        return $result;
    }

    public function getCompensationDataByPersonSchoolCardTypeCompensationType(
        $personNumber, $schoolId, $cardTypeId, $compensationType) {
        $data = $this->getCompensationDataByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId);
        if (!in_array($compensationType, $data['compensation_types'])) {
            $data['sum'] = 0;
            $data['id'] = 0;
        }
        return $data;
    }

    public function updateUsedSumById($id, $usedSum) {
        $data = $this->getRecord($id);
        if ($data) {
            // calculating new used sum, checking that it will be in range 0 <= newSum <= sum possible
            $newSum = min($data['sum'], max(0, $data['sum_used'] + $usedSum));
            $newData = array('sum_used' => $newSum);
            $this->updateRecord($id, $newData);
        }
    }

    /**
     * @param array $par
     */
    protected function findAndSetActivity($par)
    {
        $data = $this->getRecordByPersonSchoolCardType(
            $par['person_number'],
            $par['school_id'],
            $par['card_type_id']
        );
        if (!$data) {
            return;
        }
        $this->updateRecord($data['id'], array('active' => $par['active']));
    }
}
