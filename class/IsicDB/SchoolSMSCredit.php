<?php

class IsicDB_SchoolSMSCredit extends IsicDB {

    protected $table = 'module_isic_school_sms_credit';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
        'credit', 'reserved'
    );

    protected $searchableFields = array(
        'school_id'
    );

    public function getRecordBySchoolId($schoolId) {
        $data = $this->findRecord(array('school_id' => $schoolId));
        return $data;
    }

    public function getCredit($schoolId) {
        $data = $this->getRecordBySchoolId($schoolId);
        if ($data && isset($data['credit'])) {
            return floatval($data['credit']);
        }
        return 0;
    }

    public function setCredit($schoolId, $cost) {
        $data = $this->getRecordBySchoolId($schoolId);
        $this->updateRecord($data['id'], array('credit' => $cost));
    }

    public function getReserved($schoolId) {
        $data = $this->getRecordBySchoolId($schoolId);
        if ($data && isset($data['reserved'])) {
            return floatval($data['reserved']);
        }
        return 0;
    }

    public function setReserved($schoolId, $cost) {
        $data = $this->getRecordBySchoolId($schoolId);
        $this->updateRecord($data['id'], array('reserved' => $cost));
    }

    public function reserveCredit($schoolId, $cost) {
        $cost = floatval($cost);
        $reserved = $this->getReserved($schoolId);
        $credit = $this->getCredit($schoolId);
        $credit -= $cost;
        $this->setCredit($schoolId, $credit);
        $reserved += $cost;
        $this->setReserved($schoolId, $reserved);
    }

    public function freeReservedCredit($schoolId) {
        $reserved = $this->getReserved($schoolId);
        $credit = $this->getCredit($schoolId);
        $credit += $reserved;
        $this->setReserved($schoolId, 0);
        $this->setCredit($schoolId, $credit);
    }

    public function getBigValue() {
        return floatval(PHP_INT_MAX);
    }

    public function useCreditBySendLog($logRecords) {
        $remainingBalance = $this->getBigValue();
        $messagePrice = $this->getBigValue();
        foreach ($logRecords as $logRecord) {
            $tPrice = floatval($logRecord['message_price']);
            if ($tPrice > 0) {
                $schoolId = $logRecord['school_id'];
                $reserve = $this->getReserved($schoolId);
                if ($reserve >= $tPrice) {
                    $this->setReserved($schoolId, $reserve - $tPrice);
                } else {
                    $credit = $this->getCredit($schoolId);
                    // credit may even go to negative !!!
                    $this->setCredit($schoolId, $credit - $tPrice);
                }
            }
            if ($logRecord['status'] == 0) {
                $remainingBalance = min($remainingBalance, floatval($logRecord['remaining_balance']));
                $messagePrice = min($messagePrice, floatval($logRecord['message_price']));
            }
        }
        $this->updateGlobalCredit($remainingBalance);
        $this->updateGlobalPrice($messagePrice);
    }

    /**
     * sets the global sms credit value
     *
     * @param $credit
     */
    public function updateGlobalCredit($credit) {
        if ($credit < $this->getBigValue()) {
            /** @var IsicDB_GlobalSettings $globalSettings */
            $globalSettings = IsicDB::factory('GlobalSettings');
            $globalSettings->setValue('sms_credit', $credit);
        }
    }

    /**
     * sets the global sms price value
     *
     * @param $price
     */
    public function updateGlobalPrice($price) {
        if ($price < $this->getBigValue()) {
            /** @var IsicDB_GlobalSettings $globalSettings */
            $globalSettings = IsicDB::factory('GlobalSettings');
            $globalSettings->setValue('sms_price', $price);
        }
    }
}
