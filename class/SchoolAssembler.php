<?php

class SchoolAssembler {
    private $db = false;

    private $tables = array(
        'module_isic_application' => 'school_id',
        'module_isic_application_cost_school' => 'school_id',
        'module_isic_bank_school' => 'school_id',
        'module_isic_bypass_event' => 'school_id',
        'module_isic_bypass_lock' => 'school_id',
        'module_isic_card' => 'school_id',
        'module_isic_card_delivery' => 'school_id',
        'module_isic_card_status_school' => 'school_id',
        'module_isic_card_type_school' => 'school_id',
        'module_isic_card_type_school_cost' => 'school_id',
        'module_isic_card_validities' => 'school_id',
        'module_user_groups' => 'isic_school',
        'module_user_status_user' => 'school_id',
    );

    public function __construct() {
        $this->db = &$GLOBALS['database'];
    }

    public function assemble($srcId, $tarId) {
        $res = array();
        foreach ($this->tables as $tableName => $fieldName) {
            $oldRecords = $this->getRecords($tableName, $fieldName, $srcId);
            $newRecords = $this->getRecords($tableName, $fieldName, $tarId);
            if (sizeof($oldRecords) > 0 || sizeof($newRecords) > 0) {
                $res[$tableName][] = $tableName . '.' . $fieldName . ': ' . $srcId . ' => ' . $tarId;
                $idList = $this->getRecordIds($oldRecords);
                $res[$tableName][] = 'Records: O/N: ' . sizeof($oldRecords) . ' / ' .
                    sizeof($newRecords) . ': (' . implode(',', $idList) . ')';
                $this->fixRecords($tableName, $fieldName, $srcId, $tarId);
            }
        }
        $this->deleteRecord('module_isic_school', 'id', $srcId);
        return $res;
    }

    private function getRecords($tableName, $idField, $id) {
        $r = $this->db->query(
            "SELECT id FROM ?f WHERE ?f = !",
            $tableName, $idField, $id
        );
        return $r->fetch_all();
    }

    private function getRecordIds($records) {
        $idList = array();
        foreach ($records as $record) {
            $idList[] = $record['id'];
        }
        return $idList;
    }

    private function fixRecords($tableName, $idField, $srcId, $tarId) {
        $r = $this->db->query(
            "UPDATE ?f SET ?f = ! WHERE ?f = !",
            $tableName, $idField, $tarId, $idField, $srcId
        );
    }

    private function deleteRecord($tableName, $idField, $id) {
        $r = $this->db->query("DELETE FROM ?f WHERE ?f = !", $tableName, $idField, $id);
    }
}
