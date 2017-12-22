<?php
require_once(SITE_PATH . '/class/Isic/IsicDate.php');

class IsicValidator {
    protected $lineElementsCount = 9;
    protected $allowedIsicNumberLetters = array('S', 'T');
    protected $errors = array();
    protected $isicDbSchools = false;
    protected $isicDbCardTypes = false;
    protected $isicDbCards = false;

    public function __construct() {
    }

    protected function addError($error, $data) {
        $this->errors[] = array($error, $data);
    }

    protected function isError() {
        return $this->getErrorsCount() > 0;
    }

    public function getErrorsCount() {
        return count($this->getErrors());
    }

    public function isElementsCountCorrect($elementsCount) {
        return $elementsCount == $this->getLineElementsCount();
    }

    public function isValidName($str) {
        return trim($str) != '';
    }

    public function isValidBirthday($str) {
        return $this->isValidDate($str);
    }

	protected function isValidDate($str) {
        return $this->getStringAsDate($str) != IsicDate::EMPTY_DATE;
    }

	protected function getStringAsDate($str) {
        $dStr = trim($str);
        if (strlen($dStr) != 10) {
            return IsicDate::EMPTY_DATE;
        }
        $dStrDbFormat = IsicDate::getDateFormattedFromEuroToDb($dStr);
        return IsicDate::getAsDate($dStrDbFormat);
    }

    public function isValidPersonNumber($str) {
        return trim($str) != '';
    }

    public function isValidSchoolName($str) {
        return trim($str) != '';
    }

    public function isValidSchoolEhisCode($str) {
        $tStr = trim($str);
        if (!$tStr) {
            return true;
        }
        if (!$this->isEveryElementNumber($tStr)) {
            return false;
        }
        $schoolRecord = $this->isicDbSchools->getRecordByEhisCode($tStr);
        return $schoolRecord ? true : false;
    }

    public function isValidExpiration($str) {
        if (!$this->isValidDate($str)) {
            return false;
        }
        return !IsicDate::isExpiredDate($this->getStringAsDate($str));
    }

    public function isValidIsicNumber($str) {
        return true; // always valid number no matter what
        $isicNumber = trim($str);
        if (strlen($isicNumber) != 14) {
            return false;
        }
        if (!in_array(substr($isicNumber, 0, 1), $this->allowedIsicNumberLetters)) {
            return false;
        }
        if (!$this->isEveryElementNumber(substr($isicNumber, 1, -1))) {
            return false;
        }
        return true;
    }

    protected function isEveryElementNumber($str) {
        $tStr = trim($str);
        if (!$tStr) {
            return false;
        }
        for ($i = 0; $i < strlen($tStr); $i++) {
            if (!is_numeric(substr($tStr, $i, 1))) {
                return false;
            }
        }
        return true;
    }

    public function isUniqueIsicNumber($str) {
        $cardId = $this->isicDbCards->getIdByIsicNumber($str);
        return $cardId ? false : true;
    }

    public function isValidEmail($str) {
        $tStr = trim($str);
        return $tStr == '' || validateEmail($tStr);
    }

    public function isValidCardType($str) {
        if (!is_numeric($str)) {
            return false;
        }
        $ctRecord = $this->isicDbCardTypes->getRecord($str);
        return $ctRecord ? true : false;
    }
	/**
     * @return the $errors
     */
    public function getErrors() {
        return $this->errors;
    }

	/**
     * @param $errors the $errors to set
     */
    public function setErrors($errors) {
        $this->errors = $errors;
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
	/**
     * @return the $isicDbCards
     */
    public function getIsicDbCards() {
        return $this->isicDbCards;
    }

	/**
     * @param $isicDbCards the $isicDbCards to set
     */
    public function setIsicDbCards($isicDbCards) {
        $this->isicDbCards = $isicDbCards;
    }
	/**
     * @return the $lineElementsCount
     */
    public function getLineElementsCount() {
        return $this->lineElementsCount;
    }
}
