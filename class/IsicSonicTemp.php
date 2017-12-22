<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicSonicValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileIterator.php");

class IsicSonicTemp {
    private $bank_id = 1; // default bank is SEB

    /**
     * @var IsicDB_Cards
     */
    private $isicDbCards;

    /**
     * @var IsicDB_Users
     */
    private $isicDbUsers;

    /**
     * @var IsicDB_UserStatuses
     */
    private $isicDbUserStatuses;

    /**
     * @var IsicDB_Schools
     */
    private $isicDbSchools;

    /**
     * @var IsicDB_BankSchools
     */
    private $isicDbBankSchools;

    /**
     * @var IsicDB_BankCardPic
     */
    private $isicDbBankCardPic;

    private $errorList = false;

    /**
     * @var IsicSonicValidator
     */
    private $isicSonicValidator;

    /**
    * Class constructor
    */
    public function __construct() {
        $this->db = &$GLOBALS['database'];
        try {
            $this->isic_common = IsicCommon::getInstance();
            $this->isic_encoding = new IsicEncoding();
            $this->isicDbCards = IsicDB::factory('Cards');
            $this->isicDbUsers = IsicDB::factory('Users');
            $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
            $this->isicDbUserStatuses->setCurrentOrigin(IsicDB_UserStatuses::origin_bank);
            $this->isicDbSchools = IsicDB::factory('Schools');
            $this->isicDbBankSchools = IsicDB::factory('BankSchools');
            $this->isicDbBankCardPic = IsicDB::factory('BankCardPic');
            $this->isicDbBankSchools->setBankId($this->bank_id);
            $this->isicDbBankSchools->setIsicDbSchools($this->isicDbSchools);
            $this->isicSonicValidator = new IsicSonicValidator();
        } catch (Exception $e) {
            print_r($e);
        }
    }

    /**
     * Parses the given card file line and saves the data into db
     *
     * @param array $cdata array of card data
     * @return int (0 - data was not saved, 1 - data inserted, 2 - data updated)
    */
    function saveCardData($cdata = false) {
        $cardFields = $this->assignCardFields($cdata);
        if (!$cardFields) {
            return 0;
        }
        $userData = $this->getUserDataForCard($cardFields);

        $isActive = $cardFields['active'];
        unset($cardFields['active']);
        // only adding user status in case of active card
        if ($isActive) {
            $this->isicDbUserStatuses->setUserStatusesBySchoolCardType($userData['user'], $cardFields['school_id'], $cardFields['type_id'], 0);
        }

        $card_id = $this->isicDbCards->getIdByIsicNumber($cardFields['isic_number']);
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

    private function getUserDataForCard($cardFields)
    {
        $userData = $this->isicDbUsers->getRecordByCode($cardFields['person_number']);
        if (!$userData) {
            $userFields = $this->getUserFieldsFromCardFields($cardFields);
            $userId = $this->isicDbUsers->insertRecord($userFields);
            $userData = $this->isicDbUsers->getRecord($userId);
        }
        return $userData;
    }

    private function assignCardFields($cdata) {
        $cardFields = $this->isicDbCards->findRecord(array('isic_number' => $cdata['isic_number']));
        $cardFields['active'] = true;
        return $cardFields;
    }

    private function getUserFieldsFromCardFields($cardFields) {
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