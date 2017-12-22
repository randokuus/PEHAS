<?php

class IsicDB_CardTransfers extends IsicDB {

    protected $table = 'module_isic_card_transfer';
    
    protected $insertableFields = array(
        "producer_id", "date", "sequence", "entrydate"
    );
    
    protected $updateableFields = array(
        "order_name", "chip_file", "import_time", "success"
    );
    
    protected $searchableFields = array(
        "producer_id", "order_name", "success"
    );
    
    public function insertRecord(array $data) {
        $date = date("Y-m-d");
        $t = $this->getTableQuoted();
        $r = &$this->db->query("SELECT MAX($t.`sequence`) AS seq FROM $t WHERE $t.`date` = ?", $date);
        $this->assertResult($r);
        $row = $r->fetch_assoc();
        $seq = $row["seq"] + 1;
        $data['date'] = $date;
        $data['sequence'] = $seq;
        $data['entrydate'] = date("Y-m-d H:i:s");
        return parent::insertRecord($data);
    } 
    
    public function findUnsuccessfulRecordsOlderThan($date) {
        $t = $this->getTableQuoted();
        $r = $this->db->query(
            $this->getBaseQuery() . " WHERE $t.`success` = 0 AND $t.`entrydate` <= ?",
            $date . " 23:59:59"
        );
        $this->assertResult($r);
        return $r->fetch_all();
    }

    /**
     * @param $orderName
     * @param $isSuccessful
     */
    public function setCardTransferSuccess($orderName, $isSuccessful) {
        $this->db->query(
            'UPDATE ?f SET `success` = ! WHERE `order_name` = ?',
            $this->table,
            (bool)$isSuccessful,
            $orderName
        );
    }

    public function getOrderId($name = '', $chip_file = '') {
        if (!trim($name)) {
            return 0;
        }
        $r = $this->db->query(
            'SELECT `id` FROM ?f WHERE `order_name` = ? LIMIT 1',
            $this->table,
            $name
        );
        if ($data = $r->fetch_assoc()) {
            $this->updateRecord($data['id'], array(
                'chip_file' => $chip_file,
                'import_time' => $this->db->now()
            ));
            return $data["id"];
        }
        return 0;
    }

}
