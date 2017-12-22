<?php

class IsicDB_CardDataSync extends IsicDB {
    const SYNC_TYPE_ACTIVATE = 1;
    const SYNC_TYPE_DEACTIVATE = 2;
    const SYNC_TYPE_REMOVE_USER = 3;
    const SYNC_MAX_TRIES = 3;
    const RECORD_TYPE_CARD = 'card';
    const RECORD_TYPE_USER = 'user';

    protected $table = 'module_isic_card_data_sync';
    protected $searchableFields = array("id", "record_type", "record_id", "sync_type_id", "success", "tries");
    protected $insertableFields = array("record_type", "record_id", "addtime", "modtime", "sync_type_id",
        "success", "request", "response", "tries");
    protected $updateableFields = array("modtime", "sync_type_id", "success", "request", "response", "tries");

    protected $isicDbUsers;
    /**
     * @var IsicDB_CardTypes
     */
    protected $isicDbCardTypes;

    public function __construct() {
        parent::__construct();
    }

    public function insertRecord(array $data) {
        $data['addtime'] = $this->db->now();
        $addedId = parent::insertRecord($data);
        return $addedId;
    }

    public function updateRecord($id, array $data) {
        $data['modtime'] = $this->db->now();
        parent::updateRecord($id, $data);
    }

    public function schedule($newCardRecord, $oldCardRecord = null) {
        return $this->scheduleCard($newCardRecord, $oldCardRecord);
    }

    public function scheduleCard($newCardRecord, $oldCardRecord = null) {
        if (!$this->isScheduleCardNeeded($newCardRecord, $oldCardRecord)) {
            return false;
        }
        $syncType = $newCardRecord['active'] ? self::SYNC_TYPE_ACTIVATE : self::SYNC_TYPE_DEACTIVATE;
        $data = array(
            'record_type' => self::RECORD_TYPE_CARD,
            'record_id' => $newCardRecord['id'],
            'sync_type_id' => $syncType,
        );
        $this->insertRecord($data);
        return $syncType;
    }

    public function isScheduleCardNeeded($newCardRecord, $oldCardRecord = null) {
        // if pan number not set, then no need to schedule any data sync
        if (!$newCardRecord['pan_number']) {
            return false;
        }

        // if old card record was not active or present and
        // new card record is also not active, then no need for data sync
        if ((!$oldCardRecord || $oldCardRecord && !$oldCardRecord['active']) &&
            !$newCardRecord['active']) {
            return false;
        }

        if (!$this->isSyncAllowedByUser($newCardRecord['person_number'])) {
            return false;
        }

        if (!$this->isCardTypeWithChip($newCardRecord['type_id'])) {
            return false;
        }
        return $this->isOldCardRecordDifferent($newCardRecord, $oldCardRecord);
    }

    public function isOldCardRecordDifferent($newCardRecord, $oldCardRecord) {
        return
            !$oldCardRecord ||
            (
                $oldCardRecord['active'] != $newCardRecord['active'] ||
                $oldCardRecord['pan_number'] !== $newCardRecord['pan_number']
            )
        ;
    }

    public function isSyncAllowedByUser($userCode) {
        if (!$this->isicDbUsers) {
            $this->isicDbUsers = IsicDB::factory('Users');
        }

        $userRecord = $this->isicDbUsers->getRecordByCode($userCode);
        if (!$userRecord) {
            return false;
        }
        return $userRecord['data_sync_allowed'];
    }

    public function isCardTypeWithChip($typeId) {
        if (!$this->isicDbCardTypes) {
            $this->isicDbCardTypes = IsicDB::factory('CardTypes');
        }
        return $this->isicDbCardTypes->isWithChip($typeId);
    }

    public function scheduleUser($newUserRecord) {
        $data = array(
            'sync_type_id' => self::SYNC_TYPE_REMOVE_USER,
            'record_type' => self::RECORD_TYPE_USER,
            'record_id' => $newUserRecord['user'],
            'success' => 0
        );
        if (!$this->findRecord($data)) {
            $this->insertRecord($data);
        }
    }

    public function getScheduledRecords() {
        $records = $this->findRecords(
            array(
                'success' => 0,
            )
        );
        return $records;
    }

    public function getSyncTypeActivate() {
        return self::SYNC_TYPE_ACTIVATE;
    }

    public function getSyncTypeDeactivate() {
        return self::SYNC_TYPE_DEACTIVATE;
    }

    public function getSyncTypeRemoveUser() {
        return self::SYNC_TYPE_REMOVE_USER;
    }

    public function getSyncMaxTries() {
        return self::SYNC_MAX_TRIES;
    }

    public function getRecordTypeCard() {
        return self::RECORD_TYPE_CARD;
    }

    public function getRecordTypeUser() {
        return self::RECORD_TYPE_USER;
    }

    public function setIsicDbUsers($isicDbUsers)
    {
        $this->isicDbUsers = $isicDbUsers;
    }

    public function getIsicDbUsers()
    {
        return $this->isicDbUsers;
    }
}
