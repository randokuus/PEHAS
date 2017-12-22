<?php

class IsicDB_BankCardTypes extends IsicDB {

    protected $table = 'module_isic_bank_type';

    protected $insertableFields = array(
        'bank_id', 'type_id', 'name'
    );

    protected $updateableFields = array(
        'type_id'
    );

    protected $searchableFields = array(
        'name', 'type_id'
    );

    private $bankId = 0;
    private $isicDbCardTypes = false;

    public function getRecordByName($name) {
        $r = $this->findRecords(array('name' => $name, 'bank_id' => $this->getBankId()), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getTypeIdByNameAndCreateIfNotFound($name) {
        $record = $this->getRecordByName($name);
        if (!$record) {
            $bankTypeId = $this->insertRecord(array('name' => $name, 'bank_id' => $this->getBankId()));
            $record = $this->getRecord($bankTypeId);
        }

        if (!$record['type_id']) {
            $typeRecord = $this->isicDbCardTypes->findRecord(array('name' => $name), 'id');
            if (!$typeRecord) {
                $typeId = $this->isicDbCardTypes->insertRecord(array('name' => $name));
                $typeRecord = $this->isicDbCardTypes->getRecord($typeId);
            }

            $this->updateRecord($record['id'], array('type_id' => $typeRecord['id']));
            $record = $this->getRecord($record['id']);
        }

        return $record['type_id'];
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
     * @return the $isicDbCardTypes
     */
    public function getIsicDbCardTypes() {
        return $this->isicDbCardTypes;
    }

    /**
     * @param $isicDbCardTypes the $isicDbCardTypes to set
     */
    public function setIsicDbCardTypes($isicDbCardTypes) {
        $this->isicDbCardTypes = $isicDbCardTypes;
    }
}
