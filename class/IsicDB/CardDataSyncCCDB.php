<?php
require_once SITE_PATH . '/class/Isic/IsicLogger.php';

class IsicDB_CardDataSyncCCDB extends IsicDB {
    const SYNC_TYPE_ACTIVATE = 1;
    const SYNC_TYPE_DEACTIVATE = 2;
    const SYNC_MAX_TRIES = 3;

    protected $table = 'module_isic_card_data_sync_ccdb';
    protected $searchableFields = array("id", "record_id", "sync_type_id", "success", "tries");
    protected $insertableFields = array("record_id", "addtime", "modtime", "sync_type_id",
        "success", "request", "response", "tries");
    protected $updateableFields = array("modtime", "sync_type_id", "success", "request", "response", "tries");

    /**
     * @var IsicDB_CardTypes
     */
    protected $isicDbCardTypes;

    public function __construct() {
        parent::__construct();
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');
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
            'record_id' => $newCardRecord['id'],
            'sync_type_id' => $syncType,
        );
        $this->insertRecord($data);
        return $syncType;
    }

    public function isScheduleCardNeeded($newCardRecord, $oldCardRecord = null) {
        if (!$this->isicDbCardTypes->isCCDBType($newCardRecord['type_id'])) {
            return false;
        }
        return $this->isOldCardRecordDifferent($newCardRecord, $oldCardRecord);
    }

    public function isOldCardRecordDifferent($newCardRecord, $oldCardRecord) {
        $old = $oldCardRecord && $oldCardRecord['active'];
        $new = $newCardRecord['active'];
        return $old != $new;
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

    public function getSyncMaxTries() {
        return self::SYNC_MAX_TRIES;
    }

    /**
     * @param mixed $isicDbCardTypes
     */
    public function setIsicDbCardTypes($isicDbCardTypes)
    {
        $this->isicDbCardTypes = $isicDbCardTypes;
    }

    /**
     * @return mixed
     */
    public function getIsicDbCardTypes()
    {
        return $this->isicDbCardTypes;
    }
}
