<?php

class IsicDB_CardSendCosts extends IsicDB {

    protected $table = 'module_isic_card_send_cost';

    protected $sendCostData = array();

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
    );

    public function getRecord($id) {
        if (!$id) {
            return array('send_type_id' => 0, 'sum' => 0, 'currency' => '');
        }
        if (!array_key_exists($id, $this->sendCostData)) {
            $this->sendCostData[$id] = parent::getRecord($id);
        }
        return $this->sendCostData[$id];
    }
}
