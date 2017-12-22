<?php

class IsicDB_Transfers extends IsicDB {
	
  protected $table = 'module_isic_card_transfer';
  protected $insertableFields = array("date", "sequence", "entrydate", "order_name", "producer");
  protected $updateableFields = array("chip_file", "import_time");
  protected $searchableFields = array("producer", "order_name");
  
  
  public function insertRecord(array $insertData) {
  	$seq = 1;
  	$date = date("Y-m-d");
    $r = &$this->db->query('SELECT MAX(?f.`sequence`) AS seq FROM ?f WHERE ?f.`date` = ?', $this->table, $this->table, $this->table, $date);
    if ($data = $r->fetch_assoc()) {
      $seq = $data["seq"] + 1;
      $order_name = 'ISIC' . date("Ymd", strtotime($date)) . str_pad($seq, 2, "0", STR_PAD_LEFT);
      $order_id = parent::insertRecord(array("date" => $date, "sequence" => $seq, "entrydate" => date("Y-m-d H:i:s"), "order_name" => $order_name. '.txt', "producer" => $insertData["producer"]));
    } 
    
    return array("order_id" => $order_id, "filename" => $order_name);
    
  }
  
    function getOrderId($name = '') {
        $order_id = 0;
        if (trim($name)) {
            $transferData = $this->findRecord(array("order_name" => $name));
            if (is_array($transferData) && count($transferData))$order_id = $transferData["id"];
        }
        return $order_id;
    }

}

?>
