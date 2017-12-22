<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");

class IsicCardImporter {
    private $bank_id = 2; // default "bank" is CardMaster
    private $language_id = 3; // default language is estonian - 3
    private $kind_id = 1; // regular card
    private $isicDbCards = false;
    private $isicDbUsers = false;
    private $isicDbUserStatuses = false;
    private $isicDbBankSchools = false;
    private $isicDbSchools = false;

    /**
    * Class constructor
    */
    function IsicCardImporter() {
        $this->db = &$GLOBALS['database'];
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatuses->setCurrentOrigin(IsicDB_UserStatuses::origin_card_import);

        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbBankSchools = IsicDB::factory('BankSchools');
        $this->isicDbBankSchools->setBankId($this->bank_id);
        $this->isicDbBankSchools->setIsicDbSchools($this->isicDbSchools);
    }

    /**
     * Parses the given card file line and saves the data into db
     *
     * @param array $cdata array of card data
     * @return int (0 - data was not saved, 1 - data inserted, 2 - data updated)
    */
    function saveCardData($cdata = false) {
        if (!$cdata) {
            return 0;
        }

        $cardFields = $this->assignCardFields($cdata);
        $isActive = $cardFields['active'];
        unset($cardFields['active']);

        $userData = $this->isicDbUsers->getRecordByCode($cardFields['person_number']);
        if (!$userData) {
            $userFields = $this->getUserFieldsFromCardFields($cardFields);
            $userId = $this->isicDbUsers->insertRecord($userFields);
            $userData = $this->isicDbUsers->getRecord($userId);
        }

        // only adding user status in case of active card
        if ($isActive) {
            $this->isicDbUserStatuses->setUserStatusesBySchoolCardType($userData['user'], $cardFields['school_id'], $cardFields['type_id'], 0);
        }

        $card_id = $this->isicDbCards->getIdByIsicNumber($cardFields['isic_number']);
        if ($card_id) { // if card exists, then update existing record
            $this->isicDbCards->updateRecord($card_id, $cardFields);
        } else { // if card does not exist, then creating a new record
            $cardFields['exported'] = $this->db->now();
            $card_id = $this->isicDbCards->insertRecord($cardFields);
        }
        $cardRecord = $this->isicDbCards->getRecord($card_id);
        if ($cardRecord && $isActive != $cardRecord['active']) {
            if ($isActive && !$cardRecord['status_id'] && $this->isicDbCards->canBeActivated($cardRecord)) {
                $this->isicDbCards->activate($cardRecord['id']);
            } else if (!$isActive && $this->isicDbCards->canBeDeactivated($cardRecord)) {
                $this->isicDbCards->deactivate($cardRecord['id']);
            }
        }

        return $card_id;
    }

    function assignCardFields($cdata) {
        $cardFields = array(
            'language_id' => $this->language_id,
            'kind_id' => $this->kind_id,
            'person_name_first' => $cdata[0],
            'person_name_last' => $cdata[1],
            'person_birthday' => IsicDate::getDateFormattedFromEuroToDb($cdata[2]),
            'person_number' => $cdata[3],
            'school_id' => $this->isicDbBankSchools->getSchoolIdByNameOrEhisCode($cdata[4], $cdata[5]),
            'expiration_date' => IsicDate::getDateFormattedFromEuroToDb($cdata[6]),
            'isic_number' => $cdata[7],
            'person_email' => $cdata[8],
            'type_id' => $cdata[9],
            'active' => 1,
        );
        return $cardFields;
    }

    function getUserFieldsFromCardFields($cardFields) {
        return array(
            'user_code' => $cardFields['person_number'],
            'name_first' => $cardFields['person_name_first'],
            'name_last' => $cardFields['person_name_last'],
            'email' => $cardFields['person_email'],
            'phone' => $cardFields['person_phone'],
            'birthday' => $cardFields['person_birthday'],
        );
    }
}