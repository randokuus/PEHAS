<?php

class IsicDB_Logs extends IsicDB {

    const log_type_add = 1;
    const log_type_mod = 2;
    const log_type_del = 3;
    const log_type_success = 4;
    const log_type_error = 5;

    protected $system_user = false;
    protected $table = 'module_isic_log';

    protected $insertableFields = array(
        'module_name', 'event_date', 'event_user', 'record_id', 'event_type', 'event_body'
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
    );

    public function __construct() {
        parent::__construct();
        $isicDbUsers = IsicDB::factory('Users');
        $this->system_user = $isicDbUsers->getIdByUsername(SYSTEM_USER);
    }

    public function insertRecord(array $data) {
        $data['event_date'] = $this->db->now();
        $data['event_user'] = $this->getLogUserId($this->userid);

        $addedId = parent::insertRecord($data);
        return $addedId;
    }

    public function getLogUserId($userId) {
        if ($userId) {
            return $userId;
        }
        if ($this->userid) {
            return $this->userid;
        }
        return $this->system_user;
    }
}
