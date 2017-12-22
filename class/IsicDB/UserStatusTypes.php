<?php

class IsicDB_UserStatusTypes extends IsicDB {

    protected $table = 'module_user_status';

    protected $insertableFields = array(
        'name', 'card_types'
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'ehis_check', 'ehis_name', 'ehl_check', 'ehl_name'
    );

    public function listAllowedRecords() {
        $r = $this->db->query("
            SELECT
                `us`.*
            FROM
                ?f AS `us`,
                ?f AS `g`
            WHERE
                `us`.`id` = `g`.`user_status_id` AND
                `g`.`id` IN (!@)
            GROUP BY
                `us`.`id`
            ORDER BY
                `us`.?f",
            $this->table,
            'module_user_groups',
            $this->getIdsAsArray($this->usergroups),
            'name'
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getIdListByCardType($cardTypeId) {
        $recordList = $this->getRecordsByCardType($cardTypeId);
        if (!$recordList) {
            return false;
        }
        $idList = array();
        foreach ($recordList as $record) {
            $idList[] = $record['id'];
        }
        return $idList;
    }

    public function getRecordsByCardType($cardTypeId) {
        $match = false;
        $data = $this->listRecords();
        foreach ($data as $statusType) {
            $cardTypeList = explode(',', $statusType['card_types']);
            if (in_array($cardTypeId, $cardTypeList)) {
                $match[] = $statusType;
            }
        }
        return $match;
    }

    public function getRecordsByCardTypeAndAddIfNotFound($cardTypeId) {
        $statusList = $this->getRecordsByCardType($cardTypeId);
        if (!$statusList) {
            $insertData = array('name' => '-- Unknown status --', 'card_types' => $cardTypeId);
            $statusId = $this->insertRecord($insertData);
            $statusList[] = $this->getRecord($statusId);
        }
        return $statusList;
    }

    public function getRecordsByExternalCheck($externalCheck = 1) {
        return $this->findRecords(
            array(
                'ehis_check' => $externalCheck,
            )
        );
    }

    public function getRecordIdByEhisName($ehisName) {
        $r = $this->findRecords(
            array(
                'ehis_name' => $ehisName,
            ), 0, 1
        );
        return is_array($r) && count($r) == 1 ? $r[0]['id'] : false;
    }

    public function getRecordsByEHLCheck($externalCheck = 1) {
        return $this->findRecords(
            array(
                'ehl_check' => $externalCheck,
            )
        );
    }

    public function getRecordIdByEhlName($ehlName) {
        $r = $this->findRecords(
            array(
                'ehl_name' => $ehlName,
            ), 0, 1
        );
        return is_array($r) && count($r) == 1 ? $r[0]['id'] : false;
    }

    public function getAvailableCardTypesByExternalCheck($externalCheck = 1, $checkType = null) {
        $cardTypes = array();
        if ($checkType == 'EHL') {
            $statusTypes = $this->getRecordsByEHLCheck($externalCheck);
        } else {
            $statusTypes = $this->getRecordsByExternalCheck($externalCheck);
        }
        foreach ($statusTypes as $status) {
            $tmpTypes = explode(',', $status['card_types']);
            $cardTypes = array_merge($cardTypes, array_diff($tmpTypes, $cardTypes));
        }
        return $cardTypes;
    }
}
