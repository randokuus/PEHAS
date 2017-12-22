<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicSonicValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileIterator.php");

class IsicSonic {
    var $bank_id = 1; // default bank is SEB
    var $language_id = 3; // default language is estonian - 3
    var $kind_id = 2; // unified card (isic + bank)
    var $adduser = 3; // default user - garry koort
    var $moduser = 3; // default user - garry koort
    var $pic_path_sonic = "/upload/sonic/";
    var $pic_path_isic = "/upload/isic/";
    var $expiration_date = "2011-12-31";

    /**
     * @var IsicDB_Cards
     */
    var $isicDbCards;

    /**
     * @var IsicDB_Users
     */
    var $isicDbUsers;

    /**
     * @var IsicDB_UserStatuses
     */
    var $isicDbUserStatuses;

    /**
     * @var IsicDB_Schools
     */
    var $isicDbSchools;

    /**
     * @var IsicDB_BankSchools
     */
    var $isicDbBankSchools;

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
    function IsicSonic () {
        $this->db = &$GLOBALS['database'];
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
    }

    /**
     * Reads given folder and checks the file type (data-file or jpg)
     *
     * @param string $path path of the folder where sonic files are located
     * @return int number of files processed
    */
    function readFiles($path = '') {
        $opendir = addslashes($path);
        $dir = @opendir($opendir);
        if (!$dir) {
            return;
        }
        while (($file = @readdir($dir)) !== false) {
            $filePath = $opendir . $file;
            if (is_dir($filePath) || $file == "." || $file == "..") {
                continue;
            }
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

    private function getFileType($fileName) {
        $file_type = '';
        if ($fp = fopen($fileName, "rb")) {
            // determining the file type by reading the first 200 characters from it
            if ($t_line = fread($fp, 200)) {
                if (strpos($t_line, "SEBMF") !== false) { // data file
                    $file_type = "card";
                } else if (strpos($t_line, ".jpg") !== false) { // pic file
                    $file_type = "pic";
                }
            }
            fclose($fp);
        }
        return $file_type;
    }

    /**
     * Assigns pictures for cards that have bank_pic assigned but have no pic field value set
     *
     * @return int amount of records changed
    */
    function setCardPic() {
        $dir_path_sonic = SITE_PATH . $this->pic_path_sonic;
        $dir_path_isic = SITE_PATH . $this->pic_path_isic;

        $card_pic_count = 0;
        foreach ($this->isicDbCards->getBankCardsWithoutPictures() as $data) {
            $pic_filename = 'ISIC' . str_pad($data["id"], 10, '0', STR_PAD_LEFT);
            $pic_path_isic = $dir_path_isic . $pic_filename . ".jpg";
            $pic_path_isic_thumb = $dir_path_isic . $pic_filename . "_thumb.jpg";
            $pic_path_sonic = $dir_path_sonic . $data["bank_pic"];

            if (file_exists($pic_path_sonic) && @copy($pic_path_sonic, $pic_path_isic)) {
                $command = IMAGE_CONVERT . " -resize '" . $this->isic_common->image_size_thumb . "' $pic_path_isic $pic_path_isic_thumb";
                exec($command, $_dummy, $return_val);
                if (!$return_val) {
                    $pic_filename = $this->pic_path_isic . $pic_filename . ".jpg";
                    $this->isicDbBankCardPic->updateRecord($data['bank_pic_id'],
                        array('isic_pic' => $pic_filename, 'bank_id' => $this->bank_id));
                    $this->isicDbCards->updateRecord($data['id'], array('pic' => $pic_filename));
                    $card_pic_count++;
                } else {
                    echo "Error: " . $pic_path_sonic . " -> " . $pic_path_isic . ", " . $return_val . print_r($_dummy, true) . "\n";
                }
            } else {
                echo 'Error finding or copying: ' . $pic_path_sonic . ' -> ' . $pic_path_isic . "\n";
            }
        }

        return $card_pic_count;
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
        $forceDeactivate = $cardFields['force_deactivate'];
        unset($cardFields['force_deactivate']);

        // only adding user status in case of active card
        if ($isActive) {
            $this->isicDbUserStatuses->setUserStatusesBySchoolCardType($userData['user'], $cardFields['school_id'], $cardFields['type_id'], 0);
        }

        if ($cardFields['pan_number']) {
            $card_id = $this->isicDbCards->getIdByIsicAndPanNumber($cardFields['isic_number'], $cardFields['pan_number']);
        } else {
            $card_id = $this->isicDbCards->getIdByIsicNumber($cardFields['isic_number']);
        }
        if ($card_id) { // if card exists, then update existing record
            // in case of existing card, card type is not overwritten
            unset($cardFields['type_id']);
            $this->isicDbCards->updateRecord($card_id, $cardFields);
        } else { // if card does not exist, then creating a new record
            $cardFields['exported'] = $this->db->now();
            $card_id = $this->isicDbCards->insertRecord($cardFields);
        }
        $cardRecord = $this->isicDbCards->getRecord($card_id);
        if ($cardRecord && ($isActive != $cardRecord['active'] || $forceDeactivate)) {
            if ($isActive && !$cardRecord['status_id'] && $this->isicDbCards->canBeActivated($cardRecord)) {
                $this->isicDbCards->activate($cardRecord['id']);
            } else if ($forceDeactivate || !$isActive && $this->isicDbCards->canBeDeactivated($cardRecord)) {
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
            $this->isicDbUsers->enableSpecialOffers($userId);
            $userData = $this->isicDbUsers->getRecord($userId);
        }
        return $userData;
    }

    function assignCardFields($cdata) {
        if (!$cdata) {
            return false;
        }

        if (sizeof($cdata) == 12) {
            return $this->assignCardFieldsVer1($cdata);
        } else if (sizeof($cdata) == 18) {
            return $this->assignCardFieldsVer2($cdata);
        }
        return false;
    }

    function assignCardFieldsVer1($cdata) {
        $cardFields = array(
            'language_id' => $this->language_id,
            'kind_id' => $this->kind_id,
            'bank_id' => $this->bank_id,
            'expiration_date' => $this->expiration_date
        );
        $cardFields['person_name'] = $this->isic_encoding->convertStringEncoding($cdata[0]);
        if (strpos($cardFields['person_name'], ' ') !== false) {
            $cardFields['person_name_first'] = substr($cardFields['person_name'], 0, strrpos($cardFields['person_name'], " "));
            $cardFields['person_name_last'] = substr($cardFields['person_name'], strrpos($cardFields['person_name'], " ") + 1);
        } else {
            $cardFields['person_name_first'] = '';
            $cardFields['person_name_last'] = $cardFields['person_name'];
        }

        $cardFields['person_number'] = $cdata[1];
        $cardFields['person_birthday'] = IsicDate::getDateFormattedFromEuroToDb($cdata[2]);

        $cardFields['bank_pic'] = $cdata[3];
        // if contact-field contains '@', then it's e-mail, otherwise it's phone number
        if (strpos($cdata[4], "@") !== false) {
            $cardFields['person_email'] = $this->isic_encoding->convertStringEncoding($cdata[4]);
            $cardFields['person_phone'] = "";
        } else {
            $cardFields['person_phone'] = $this->isic_encoding->convertStringEncoding($cdata[4]);
            $cardFields['person_email'] = "";
        }
        $cardFields['school_id'] = $this->isicDbBankSchools->getSchoolIdByNameAndCreateIfNotFound($this->isic_encoding->convertStringEncoding($cdata[5]));
        $cardFields['isic_number'] = str_replace(" ", "", $cdata[6]); // removing spaces from the number
        $cardFields['type_id'] = $this->isic_common->getBankTypeId($this->isic_encoding->convertStringEncoding($cdata[7]), $this->bank_id);
        $cardFields['bank_status_id'] = $this->isic_common->getBankStatusId($this->isic_encoding->convertStringEncoding($cdata[8]), $this->bank_id);
        $cardFields['received_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[9]);
        $cardFields['active'] = trim($cdata[10]);
        return $cardFields;
    }

    function assignCardFieldsVer2($cdata) {
        if (!$this->isicSonicValidator->isValidLine($cdata)) {
            $this->errorList[] = $this->isicSonicValidator->getErrors();
            return false;
        }

        $cardFields = array(
            'language_id' => $this->language_id,
            'kind_id' => $this->kind_id,
            'bank_id' => $this->bank_id,
        );

        $cardFields['person_name_first'] = $this->isic_encoding->convertStringEncoding($cdata[0]);
        $cardFields['person_name_last'] = $this->isic_encoding->convertStringEncoding($cdata[1]);
        $cardFields['person_number'] = $cdata[2];
        $cardFields['person_birthday'] = IsicDate::getDateFormattedFromEuroToDb($cdata[3]);
        $cardFields['bank_pic'] = $cdata[4];
        $cardFields['person_email'] = $this->isic_encoding->convertStringEncoding($cdata[5]);
        $cardFields['person_phone'] = $this->isic_encoding->convertStringEncoding($cdata[6]);
        $cardFields['school_id'] = $this->isicDbBankSchools->getSchoolIdByNameOrEhisCode($this->isic_encoding->convertStringEncoding($cdata[7]), $cdata[9]);

        $cardFields['isic_number'] = str_replace(" ", "", $cdata[10]); // removing spaces from the number
        $cardFields['type_id'] = $this->isic_common->getBankTypeId($this->isic_encoding->convertStringEncoding($cdata[11]), $this->bank_id);
        $cardFields['expiration_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[12]);
        $cardFields['bank_status_id'] = $this->isic_common->getBankStatusId($this->isic_encoding->convertStringEncoding($cdata[13]), $this->bank_id);
        $cardFields['received_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[15]);
        // activity flag is calculated as conjuction of card issued (given to user) and card activated
        $cardFields['active'] = trim($cdata[14]) && trim($cdata[16]);
        // check if both values are zeroes to force deactivation
        $cardFields['force_deactivate'] = !trim($cdata[14]) && !trim($cdata[16]);
        $cardFields['pan_number'] = str_replace(" ", "", $cdata[17]); // removing spaces from the number;
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

    /**
     * Reading the given card data file line by line and sending the data into saveCardData method
     *
     * @param string $filename filename to read from
     * @return int line count
    */
    function readCardData($filename = '') {
        echo 'Filename: ' . $filename . "\n";
        $iter = new FileIterator($filename, ';');
        foreach ($iter as $line_count => $t_line) {
            if (strpos($t_line[0], "SEBMF") === false) {
                $res = $this->saveCardData($t_line);
                echo 'Line: ' . $line_count . ': ' . implode(',', $t_line) . ': ' . $res . "\n";
            }
        }
        return $line_count;
    }

    /**
     * Reading the given pic data file and base64_decode()-ing it, afterward saving the file as .jpg
     *
     * @param string $filename filename to read from
     * @return the saved pic name
    */
    public function readPicData($filename = '') {
        if ($filename && is_readable($filename)) {
            $fdata = file($filename, FILE_IGNORE_NEW_LINES);
            if ($fdata[0] && $fdata[1]) {
                $pic_name = $fdata[0];
                $pic_body = base64_decode($fdata[1]);
                echo $pic_name . "\n";
                return $this->savePicFile($pic_name, $pic_body);
            }
        }
        return false;
    }

    private function savePicFile($pic_name, $pic_body) {
        if (file_put_contents(SITE_PATH . $this->pic_path_sonic . $pic_name, $pic_body)) {
            $this->savePicDbRecord($pic_name);
            return true;
        } else {
            echo "could not write into the file ...\n";
        }
        return false;
    }

    private function savePicDbRecord($pic_name) {
        if (!$this->isicDbBankCardPic->findRecord(array('pic' => $pic_name, 'bank_id' => $this->bank_id))) {
            $this->isicDbBankCardPic->insertRecord(array('pic' => $pic_name, 'bank_id' => $this->bank_id));
            return true;
        }
        return false;
    }

    /**
     * Reading given folder and inserting info about all of the .jpg-files into module_isic_bank_pic
     *
     * @param string $path folder where are the picture files
     * @return amount of pictures inserted
    */

    function createPicDb($path = '') {
        $ins_count = 0;
        $opendir = addslashes($path);
        if ($dir = @opendir($opendir)) {
            while (($file = @readdir($dir)) !== false) {
                if (!is_dir($opendir . $file) && $file != "." && $file != ".." && strpos($file, ".jpg") !== false) {
                    echo $file . "\n";
                    if ($this->savePicDbRecord($file)) {
                        $ins_count++;
                    }
                }
            }
        }
        return $ins_count;
    }
}