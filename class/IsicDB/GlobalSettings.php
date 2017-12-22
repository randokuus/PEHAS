<?php

class IsicDB_GlobalSettings extends IsicDB {

    protected $table = 'module_isic_global_settings';

    protected $insertableFields = array();
    protected $updateableFields = array('value');
    protected $searchableFields = array();

    public function getRecord($name) {
        static $values = null;
        if(!is_array($values)) {
            $records = parent::listRecords();
            foreach ($records as $record) {
              $values[$record['id']] = $record['value'];
            }
        }
        if (!array_key_exists($name, $values)) {
            throw new IsicDB_Exception('Unknown setting requested: "' . $name . '"');
        }
        return $values[$name];
    }

    public function setValue($key, $value) {
        $this->updateRecord($key, array('value' => $value));
    }
}
