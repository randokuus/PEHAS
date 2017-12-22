<?php

class IsicDB_PaymentLogs extends IsicDB {
    protected $system_user = false;
    protected $table = 'module_isic_payment_log';

    protected $insertableFields = array(
        'addtime', 'bank_id', 'application_id', 'payment_type'
    );

    protected $updateableFields = array(
        'modtime', 'send_message', 'receive_message'
    );

    protected $searchableFields = array(
    );

    public function __construct() {
        parent::__construct();
        $isicDbUsers = IsicDB::factory('Users');
        $this->system_user = $isicDbUsers->getIdByUsername(SYSTEM_USER);
    }

    public function insertRecord($data) {
        $data['addtime'] = $this->db->now();

        $addedId = parent::insertRecord($data);
        return $addedId;
    }

    public function updateRecord($id, $data) {
        $data['modtime'] = $this->db->now();
        parent::updateRecord($id, $data);
    }
}
