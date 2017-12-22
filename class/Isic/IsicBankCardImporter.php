<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/InvalidPersonNumberException.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDirectory.php");

abstract class IsicBankCardImporter {
    const LANGUAGE_ID = 3; // default language is estonian - 3
    const KIND_ID = 2; // unified card (isic + bank)

    protected $bankId = 0;

    protected $pic_path_bank = "";
    protected $pic_path_isic = "/upload/isic/";

    /**
     * @var IsicCommon
     */
    protected $isicCommon;

    /**
     * @var IsicDB_BankSchools
     */
    protected $isicDbBankSchools;

    /**
     * @var IsicDB_BankCardTypes
     */
    protected $isicDbBankCardTypes;

    /**
     * @var IsicDB_Cards
     */
    protected $isicDbCards;

    /**
     * @var IsicDB_Users
     */
    protected $isicDbUsers;

    /**
     * @var IsicDB_UserStatuses
     */
    protected $isicDbUserStatuses;

    /**
     * @var IsicDB_Schools
     */
    protected $isicDbSchools;

    /**
     * @var IsicDB_BankCardPic
     */
    protected $isicDbBankCardPic;

    public function __construct() {
        $this->db = &$GLOBALS['database'];
        $this->isicCommon = IsicCommon::getInstance();

        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatuses->setCurrentOrigin(IsicDB_UserStatuses::origin_bank);
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbBankCardPic = IsicDB::factory('BankCardPic');

        $this->initBankSchools();
        $this->initBankCardTypes();
    }

    protected function initBankSchools() {
        $this->isicDbBankSchools = IsicDB::factory('BankSchools');
        $this->isicDbBankSchools->setBankId($this->bankId);
        $this->isicDbBankSchools->setIsicDbSchools($this->isicDbSchools);
    }

    protected function initBankCardTypes() {
        $this->isicDbBankCardTypes = IsicDB::factory('BankCardTypes');
        $this->isicDbBankCardTypes->setBankId($this->bankId);
        $this->isicDbBankCardTypes->setIsicDbCardTypes(IsicDB::factory('CardTypes'));
    }

    abstract protected function getFileType($filePath);

    abstract protected function readCardData($filePath);

    abstract protected function readPicData($filePath);

    /**
     * Reads given folder and checks the file type (data-file or jpg)
     *
     * @param string $path path of the folder where bank files are located
     * @return int number of files processed
     */
    public function readFiles($path = '') {
        $opendir = addslashes($path);
        $fileList = IsicDirectory::getAsSortedList($path);
        foreach ($fileList as $file) {
            $filePath = $opendir . $file;
            $file_type = $this->getFileType($filePath);
            // managing the data by determined type
            if ($file_type == "card") { // card file
                $res = $this->readCardData($filePath);
            } else if ($file_type == "pic") {  // pic file
                $res = $this->readPicData($filePath);
            } else { // some unknown file, that we will just skip
                $res = true;
            }
            // moving all of the already handled files to imported subfolder
            $filePathImported = $opendir . "imported/" . $file;
            if ($res && rename($filePath, $filePathImported)) {
                @chmod($filePathImported, 0666);
            }
        }
    }

    /**
     * Parses the given card file line and saves the data into db
     *
     * @param array $cdata array of card data
     * @return int (0 - data was not saved, 1 - data inserted, 2 - data updated)
     */
    public function saveCardData($cardFields, $type = 1) {
        $isActive = $cardFields['active'];
        unset($cardFields['active']);

        $userData = $this->getUserDataForCard($cardFields);
        // only adding user status in case of active card
        if ($isActive) {
            $this->isicDbUserStatuses->setUserStatusesBySchoolCardType(
                $userData['user'],
                $cardFields['school_id'],
                $cardFields['type_id'],
                0
            );
        }

        if ($cardFields['pan_number']) {
            $card_id = $this->isicDbCards->getIdByIsicAndPanNumber($cardFields['isic_number'], $cardFields['pan_number']);
        } else {
            // in case of SwedBank, using ISIC number and received date to find card
            if ($this->bankId == 2) {
                $card_id = $this->isicDbCards->getIdByIsicNumberReceivedDate(
                    $cardFields['isic_number'],
                    $cardFields['received_date']
                );
            } else {
                $card_id = $this->isicDbCards->getIdByIsicNumber($cardFields['isic_number']);
            }
        }

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

    protected function getUserDataForCard($cardFields)
    {
        $userData = $this->isicDbUsers->getRecordByCode($cardFields['person_number']);
        if (!$userData) {
            $userFields = $this->getUserFieldsFromCardFields($cardFields);
            $userId = $this->isicDbUsers->insertRecord($userFields);
            $this->isicDbUsers->enableSpecialOffers($userId);
            $userData = $this->isicDbUsers->getRecord($userId);
        }
        return $userData;
    }

    protected function getUserFieldsFromCardFields($cardFields) {
        return array(
            'user_code' => $cardFields['person_number'],
            'name_first' => $cardFields['person_name_first'],
            'name_last' => $cardFields['person_name_last'],
            'email' => $cardFields['person_email'],
            'phone' => $cardFields['person_phone'],
            'birthday' => $cardFields['person_birthday'],
        );
    }

    protected abstract function assignCardFields($data);

    protected function savePicFile($pic_name, $pic_body) {
        if (file_put_contents(SITE_PATH . $this->pic_path_bank . $pic_name, $pic_body)) {
            $this->savePicDbRecord($pic_name);
            return true;
        } else {
            echo "could not write into the file ...\n";
        }
        return false;
    }

    protected function savePicDbRecord($pic_name) {
        if (!$this->isicDbBankCardPic->findRecord(array('pic' => $pic_name, 'bank_id' => $this->bankId))) {
            $this->isicDbBankCardPic->insertRecord(array('pic' => $pic_name, 'bank_id' => $this->bankId));
            return true;
        }
        return false;
    }

    /**
     * Assigns pictures for cards that have bank_pic assigned but have no pic field value set
     *
     * @return int amount of records changed
     */
    public function setCardPic() {
        $dir_path_bank = SITE_PATH . $this->pic_path_bank;
        $dir_path_isic = SITE_PATH . $this->pic_path_isic;

        $card_pic_count = 0;
        foreach ($this->isicDbCards->getBankCardsWithoutPictures($this->bankId) as $data) {
            $pic_filename = 'ISIC' . str_pad($data["id"], 10, '0', STR_PAD_LEFT);
            $pic_path_isic = $dir_path_isic . $pic_filename . ".jpg";
            $pic_path_isic_thumb = $dir_path_isic . $pic_filename . "_thumb.jpg";
            $pic_path_bank = $dir_path_bank . $data["bank_pic"];

            if (file_exists($pic_path_bank) && @copy($pic_path_bank, $pic_path_isic)) {
                $command = IMAGE_CONVERT . " -resize '" . $this->isicCommon->image_size_thumb . "' $pic_path_isic $pic_path_isic_thumb";
                exec($command, $_dummy, $return_val);
                if (!$return_val) {
                    $pic_filename = $this->pic_path_isic . $pic_filename . ".jpg";
                    $this->isicDbBankCardPic->updateRecord($data['bank_pic_id'],
                        array('isic_pic' => $pic_filename, 'bank_id' => $this->bankId)
                    );
                    $this->isicDbCards->updateRecord($data['id'], array('pic' => $pic_filename));
                    $card_pic_count++;
                } else {
                    echo "Error: " . $pic_path_bank . " -> " . $pic_path_isic . ", " . $return_val . print_r($_dummy, true) . "\n";
                }
            } else {
                echo 'Error finding or copying: ' . $pic_path_bank . ' -> ' . $pic_path_isic . "\n";
            }
        }

        return $card_pic_count;
    }
}