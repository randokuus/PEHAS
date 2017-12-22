<?php
//require_once(SITE_PATH . '/class/Isic/IsicValidator.php');
require_once(SITE_PATH . '/class/Isic/IsicCharacterValidator.php');

class IsicApplicationValidator {
    const VALID_SYMBOL_CODES_PERSON_NAME = '32,39,45,65-90,96-122,138,142-146,150,151,154,158,192-214,216-246,248-255';
    const VALID_SYMBOL_CODES_PERSON_NUMBER = '45,48-57,65-90';
    const VALID_SYMBOL_CODES_ADDRESS = '32,39,44-57,65-90,97-122,130,138,142,154,158,196,213,214,220,228,245,246,252';
    const VALID_SYMBOL_CODES_CITY    = '32,39,44-46,65-90,97-122,130,138,142,154,158,196,213,214,220,228,245,246,252';
    const VALID_SYMBOL_CODES_COUNTRY = '32,39,44-46,65-90,97-122,130,138,142,154,158,196,213,214,220,228,245,246,252';
    const VALID_SYMBOL_CODES_ZIP = '48-57';
    const ENCODING_TARGET = "Windows-1252";
    const ENCODING_SOURCE = "UTF-8";

    private $validationFields = array();

    public function __construct() {
        $this->initFieldValidators();
    }

    public function initFieldValidators()
    {
        $nameValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_PERSON_NAME);
        $personNumberValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_PERSON_NUMBER);
        $addressValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_ADDRESS);
        $cityValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_CITY);
        $countryValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_COUNTRY);
        $zipValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_ZIP);
        $this->validationFields['person_name_first'] = $nameValidator;
        $this->validationFields['person_name_last'] = $nameValidator;
        $this->validationFields['person_number'] = $personNumberValidator;
        $this->validationFields['person_addr1'] = $addressValidator;
        $this->validationFields['person_addr2'] = $cityValidator;
        $this->validationFields['person_addr3'] = $countryValidator;
        $this->validationFields['person_addr4'] = $zipValidator;
        $this->validationFields['delivery_addr1'] = $addressValidator;
        $this->validationFields['delivery_addr2'] = $cityValidator;
        $this->validationFields['delivery_addr3'] = $countryValidator;
        $this->validationFields['delivery_addr4'] = $zipValidator;
    }

    public function hasNonValidFields(array $vars, array $required, &$errorFields) {
        $isValid = true;
        $checkFields = array_intersect($required, array_keys($this->validationFields));
        foreach ($checkFields as $field) {
            $validator = $this->validationFields[$field];
            if (!$validator->hasValidCharacters(
                mb_convert_encoding($vars[$field], self::ENCODING_TARGET, self::ENCODING_SOURCE))
            ) {
                $errorFields[] = $field;
                $isValid = false;
            }
        }
        return !$isValid;
    }
}
