<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");

class IsicUser {
    private $id = false;
    private $allowedSchools = false;
    private $allowedCardTypesForAdd = false;
    private $allowedCardTypesForView = false;
    private $isic_common = false;
    
    public function __construct($id) {
        $this->setId($id);       
        $this->isic_common = IsicCommon::getInstance();         
    }
    
	/**
     * @return the $allowedCardTypesForView
     */
    public function getAllowedCardTypesForView() {
        if (!$this->allowedCardTypesForView) {
            $obj = IsicDB::factory('CardTypes');
            $this->allowedCardTypesForView = $obj->getAllowedIdListForView();
        }
        return $this->allowedCardTypesForView;
    }

	/**
     * @return the $allowedCardTypesForAdd
     */
    public function getAllowedCardTypesForAdd() {
        if (!$this->allowedCardTypesForAdd) {
            $obj = IsicDB::factory('CardTypes');
            $this->allowedCardTypesForAdd = $obj->getAllowedIdListForAdd();
        }
        return $this->allowedCardTypesForAdd;
    }
    
	/**
     * @return the $allowedSchools
     */
    public function getAllowedSchools() {
        return $this->isic_common->allowed_schools;
        /*
        if (!$this->allowedSchools) {
            $obj = IsicDB::factory('UserGroups');
            $this->allowedSchools = $obj->listAllowedSchools();
        }
        return $this->allowedSchools;
        */
    }
    
    function getActiveSchool() {
        $activeSchool = (int)$this->isic_common->user_active_school;
        return $activeSchool && in_array($activeSchool, $this->getAllowedSchools()) ? $activeSchool : null;
    }
    
	/**
     * @param $id the $id to set
     */
    public function setId($id) {
        $this->id = $id;
    }

	/**
     * @return the $id
     */
    public function getId() {
        return $this->id;
    }
}
