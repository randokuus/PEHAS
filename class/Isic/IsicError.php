<?php

class IsicError {
    private $error = false;
    private $errorList = array();
    private $badFields = array();
    private $emptyValues = array('');
    
    public function IsicError() {
    }
    
    public function reset() {
        $this->error = false;
        $errorList = array();
        $badFields = array();
        $emptyValues = array('');
    }
    
	/**
     * @param $emptyValues the $emptyValues to set
     */
    public function setEmptyValues($emptyValues) {
        $this->emptyValues = $emptyValues;
    }

	/**
     * @return the $emptyValues
     */
    public function getEmptyValues() {
        return $this->emptyValues;
    }
    
	/**
     * @param $badFields the $badFields to set
     */
    public function setBadFields($badFields) {
        $this->badFields = array();
        if (is_array($badFields)) {
            $this->badFields = $badFields;    
        }
    }

	/**
     * @return the $badFields
     */
    public function getBadFields() {
        return $this->badFields;
    }
    
    public function setError($error) {
        $this->error = $error;
    }
    
    public function isError() {
        return $this->error;
    }
    
    public function add($error, $flag = true) {
        if (!$this->get($error) && $flag) {
            $this->errorList[] = $error;
            $this->setError(true);
        }
    }
    
    public function get($error) {
        return in_array($error, $this->errorList);
    }
    
    public function addBadField($field) {
        if (!$this->isBadField($field)) {
            $this->badFields[] = $field;
        }
    }
    
    public function isBadField($field) {
        return in_array($field, $this->badFields);
    }
    
    /**
     * Checks if all the required fields are filled
     *
     * @param array $vars variable array with user-submitted data
     * @param array $required list of field names to check
     * @return boolean true|false of error state
    */
    public function checkRequired($vars, $required = false) {
        if (!is_array($required)) {
            return;
        }
        foreach ($required as $field) {
            if (in_array($vars[$field], $this->emptyValues)) {
                $this->addBadField($field);
                $this->setError(true);
            }
        }
    }
}

