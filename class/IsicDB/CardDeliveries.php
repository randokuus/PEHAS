<?php

class IsicDB_CardDeliveries extends IsicDB {

    protected $table = 'module_isic_card_delivery';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'school_id', 'active', 'shipment_id'
    );

    public function getRecordsBySchool($schoolId, $showHomeDelivery = true) {
        return $this->getRecordsBySchoolCardType($schoolId, 0, $showHomeDelivery);
    }

    public function getRecordsBySchoolCardType($schoolId, $cardTypeId = 0, $showHomeDelivery = true) {
        $dbSchools = IsicDB::factory('Schools');
        $schoolRecord = $dbSchools->getRecord($schoolId);
        if (!$schoolRecord || !$schoolRecord['active']) {
            return array();
        }

        $list = array();
        // if card home delivery allowed, then filling list with active records without school
        if ($this->isHomeDeliveryAllowed($schoolRecord, $cardTypeId, $showHomeDelivery)) {
            $r = $this->findRecords(array('shipment_id' => 0, 'active' => 1));
            if (is_array($r)) {
                $list = array_merge($list, $r);
            }
        }

        // adding all records that are not for home delivery but where school is not set
        if ($this->isEylDeliveryAllowed($schoolRecord, $cardTypeId)) {
            $r = $this->findRecords(array('school_id' => 0, 'active' => 1));
            if (is_array($r)) {
                foreach ($r as $data) {
                    // ignoring all the previously added records
                    if ($data['shipment_id'] == 0) {
                        continue;
                    }
                    $exist = false;
                    foreach ($list as $list_record) {
                        if ($data['id'] == $list_record['id']) {
                            $exist = true;
                            break;
                        }
                    }
                    if (!$exist) {
                        $list[] = $data;
                    }
                }
            }
        }

        // finally adding all the records for the given school
        $r = $this->findRecords(array('school_id' => $schoolId, 'active' => 1));
        if (is_array($r)) {
            $list = array_merge($list, $r);
        }

        return $list;
    }

    private function isHomeDeliveryAllowed($schoolRecord, $cardTypeId, $showHomeDelivery) {
        $deliveryAllowed = $schoolRecord['card_home_delivery'];
        if ($cardTypeId) {
            /** @var IsicDB_CardTypeSchools $dbCardTypeSchools */
            $dbCardTypeSchools = IsicDB::factory('CardTypeSchools');
            $cardTypeSchool = $dbCardTypeSchools->findRecord(array(
                'type_id' => $cardTypeId,
                'school_id' => $schoolRecord['id']
            ));
            if ($cardTypeSchool) {
                $deliveryAllowed = $cardTypeSchool['card_home_delivery'];
            }
        }
        return $showHomeDelivery && $deliveryAllowed;
    }

    private function isEylDeliveryAllowed($schoolRecord, $cardTypeId) {
        $deliveryAllowed = $schoolRecord['card_eyl_delivery'];
        if ($cardTypeId) {
            /** @var IsicDB_CardTypeSchools $dbCardTypeSchools */
            $dbCardTypeSchools = IsicDB::factory('CardTypeSchools');
            $cardTypeSchool = $dbCardTypeSchools->findRecord(array(
                'type_id' => $cardTypeId,
                'school_id' => $schoolRecord['id']
            ));
            if ($cardTypeSchool) {
                $deliveryAllowed = $cardTypeSchool['card_eyl_delivery'];
            }
        }
        return $deliveryAllowed;
    }

    public function isDeliverable($id) {
        $record = $this->getRecord($id);
        $dbCardSendCosts = IsicDB::factory('CardSendCosts');
        $costRecord = $dbCardSendCosts->getRecord($record['send_cost_id']);
        return $costRecord['send_type_id'] == 1;
    }

    public function getDeliverySum($id) {
        $deliveryRecord = $this->getRecord($id);
        if ($deliveryRecord) {
            $dbCardSendCosts = IsicDB::factory('CardSendCosts');
            $sendCostRecord = $dbCardSendCosts->getRecord($deliveryRecord['send_cost_id']);
        }
        if ($sendCostRecord && $sendCostRecord['sum']) {
            $deliverySum = $sendCostRecord['sum'];
        } else {
            $deliverySum = 0;
        }
        return $deliverySum;
    }
}
