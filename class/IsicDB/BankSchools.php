<?php

class IsicDB_BankSchools extends IsicDB {

    protected $table = 'module_isic_bank_school';

    protected $insertableFields = array(
        'bank_id', 'school_id', 'name'
    );

    protected $updateableFields = array(
        'school_id'
    );

    protected $searchableFields = array(
        'name', 'bank_id'
    );

    private $bankId = 0;
    private $isicDbSchools = false;

    public function getRecordByName($name) {
        $r = $this->findRecords(array('name' => $name, 'bank_id' => $this->getBankId()), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getSchoolIdByNameAndCreateIfNotFound($name) {
        $record = $this->getRecordByName($name);
        if (!$record) {
            $bankSchoolId = $this->insertRecord(array('name' => $name, 'bank_id' => $this->getBankId()));
            $record = $this->getRecord($bankSchoolId);
        }

        if (!$record['school_id']) {
            $schoolRecord = $this->isicDbSchools->findRecord(array('name' => $name), 'id');
            if (!$schoolRecord) {
                $schoolId = $this->isicDbSchools->insertRecord(array('name' => $name));
                $schoolRecord = $this->isicDbSchools->getRecord($schoolId);
            }

            $this->updateRecord($record['id'], array('school_id' => $schoolRecord['id']));
            $record = $this->getRecord($record['id']);
        }

        return $record['school_id'];
    }

    public function getSchoolIdByNameOrEhisCode($name, $ehisCode) {
        if ($ehisCode) {
            $schoolRecord = $this->isicDbSchools->getRecordByEhisCode($ehisCode);
            if (is_array($schoolRecord) && $schoolRecord['id']) {
                return $schoolRecord['id'];
            }
        }
        return $this->getSchoolIdByNameAndCreateIfNotFound($name);
    }

    /**
     * @return the $bankId
     */
    public function getBankId() {
        return $this->bankId;
    }

    /**
     * @param $bankId the $bankId to set
     */
    public function setBankId($bankId) {
        $this->bankId = $bankId;
    }
    /**
     * @return the $isicDbSchools
     */
    public function getIsicDbSchools() {
        return $this->isicDbSchools;
    }

    /**
     * @param $isicDbSchools the $isicDbSchools to set
     */
    public function setIsicDbSchools($isicDbSchools) {
        $this->isicDbSchools = $isicDbSchools;
    }
}
