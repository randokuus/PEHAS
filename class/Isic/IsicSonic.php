<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicBankCardImporter.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicSonicValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileIterator.php");

class IsicSonic extends IsicBankCardImporter {
    protected $bankId = 1; // SEB
    protected $pic_path_bank = "/upload/sonic/";
    protected $expiration_date = "2011-12-31";


    protected $errorList = false;

    /**
     * @var IsicSonicValidator
     */
    protected $isicSonicValidator;

    /**
    * Class constructor
    */
    public function __construct() {
        parent::__construct();
        $this->isic_encoding = new IsicEncoding();
        $this->isicSonicValidator = new IsicSonicValidator();
    }

    protected function getFileType($fileName) {
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

    protected function assignCardFields($cdata) {
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

    protected function assignCardFieldsVer1($cdata) {
        $cardFields = array(
            'language_id' => self::LANGUAGE_ID,
            'kind_id' => self::KIND_ID,
            'bank_id' => $this->bankId,
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
        $cardFields['type_id'] = $this->isicCommon->getBankTypeId($this->isic_encoding->convertStringEncoding($cdata[7]), $this->bankId);
        $cardFields['bank_status_id'] = $this->isicCommon->getBankStatusId($this->isic_encoding->convertStringEncoding($cdata[8]), $this->bankId);
        $cardFields['received_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[9]);
        $cardFields['active'] = trim($cdata[10]);
        return $cardFields;
    }

    protected function assignCardFieldsVer2($cdata) {
        if (!$this->isicSonicValidator->isValidLine($cdata)) {
            $this->errorList[] = $this->isicSonicValidator->getErrors();
            return false;
        }

        $cardFields = array(
            'language_id' => self::LANGUAGE_ID,
            'kind_id' => self::KIND_ID,
            'bank_id' => $this->bankId,
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
        $cardFields['type_id'] = $this->isicCommon->getBankTypeId($this->isic_encoding->convertStringEncoding($cdata[11]), $this->bankId);
        $cardFields['expiration_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[12]);
        $cardFields['bank_status_id'] = $this->isicCommon->getBankStatusId($this->isic_encoding->convertStringEncoding($cdata[13]), $this->bankId);
        $cardFields['received_date'] = IsicDate::getDateFormattedFromEuroToDb($cdata[15]);
        // activity flag is calculated as conjuction of card issued (given to user) and card activated
        $cardFields['active'] = trim($cdata[14]) && trim($cdata[16]);
        $cardFields['pan_number'] = str_replace(" ", "", $cdata[17]); // removing spaces from the number;
        return $cardFields;
    }

    /**
     * Reading the given card data file line by line and sending the data into saveCardData method
     *
     * @param string $filename filename to read from
     * @return int line count
    */
    protected function readCardData($filename = '') {
        echo 'Filename: ' . $filename . "\n";
        $iter = new FileIterator($filename, ';');
        foreach ($iter as $line_count => $t_line) {
            if (strpos($t_line[0], "SEBMF") === false) {
                $res = 0;
                $cardFields = $this->assignCardFields($t_line);
                if ($cardFields) {
                    $res = $this->saveCardData($cardFields);
                }
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

    /**
     * Reading given folder and inserting info about all of the .jpg-files into module_isic_bank_pic
     *
     * @param string $path folder where are the picture files
     * @return amount of pictures inserted
    */
    public function createPicDb($path = '') {
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