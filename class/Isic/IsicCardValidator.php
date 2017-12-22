<?php
require_once(SITE_PATH . '/class/Isic/IsicValidator.php');

class IsicCardValidator extends IsicValidator {
    protected $lineElementsCount = 10;

    public function __construct() {
    }

    public function isValidLine(array $line) {
        $this->setErrors(array());
        if (!$this->isElementsCountCorrect(count($line))) {
            $this->addError('elements', count($line));
            return !$this->isError();
        }
        if (!$this->isValidName($line[0])) {
            $this->addError('name_first', $line[0]);
        }
        if (!$this->isValidName($line[1])) {
            $this->addError('name_last', $line[1]);
        }
        if (!$this->isValidBirthday($line[2])) {
            $this->addError('birthday', $line[2]);
        }
        if (!$this->isValidPersonNumber($line[3])) {
            $this->addError('person_number', $line[3]);
        }
        if (!$this->isValidSchoolName($line[4])) {
            $this->addError('school_name', $line[4]);
        }
        if (!$this->isValidSchoolEhisCode($line[5])) {
            $this->addError('school_ehis_code', $line[5]);
        }
        if (!$this->isValidExpiration($line[6])) {
            $this->addError('expiration', $line[6]);
        }
        if (!$this->isValidIsicNumber($line[7])) {
            $this->addError('isic_number', $line[7]);
        }
        if (!$this->isUniqueIsicNumber($line[7])) {
            $this->addError('isic_number_exists', $line[7]);
        }
        if (!$this->isValidEmail($line[8])) {
            $this->addError('email', $line[8]);
        }
        if (!$this->isValidCardType($line[9])) {
            $this->addError('card_type', $line[9]);
        }
        return !$this->isError();
    }
}
