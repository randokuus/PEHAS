<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicBankCardImporter.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicError.php");

class IsicBankCardImporter_Swedbank extends IsicBankCardImporter {
    const SCHEMA_FILE = '/class/Isic/resources/swed_card_import.xsd';
    const FILE_TYPE_NEW_CARDS = 1;
    const FILE_TYPE_STATUS_CHANGES = 2;

    protected $bankId = 2; // Swedbank

    protected $pic_path_bank = "/upload/swedbank/";

    public function __construct() {
        parent::__construct();
    }

    public function getFileType($fileName) {
        $fp = fopen($fileName, "rb");
        if (!$fp) {
            return '';
        }
        fclose($fp);
        $pathInfo = pathinfo($fileName);
        switch (strtolower($pathInfo['extension'])) {
            case 'xml':
                return 'card';
                break;
            case 'png': // falls through
            case 'jpg':
                return 'pic';
                break;
            default:
                break;
        }
        return '';
    }

    protected function readCardData($filePath) {
        echo 'Filename: ' . $filePath . "\n";
        return $this->parseFile($filePath);
    }

    protected function readPicData($filePath) {
        echo $filePath . "\n";
        return $this->savePicFile(pathinfo($filePath, PATHINFO_BASENAME), file_get_contents($filePath));
    }

    public function parseFile($filename) {
        $xml = $this->loadFile($filename);
        if (!$xml) {
            return null;
        }

        $fileType = $this->getXMLType($xml);
        if (!$fileType) {
            return null;
        }

        $faultyCards = array();
        switch ($fileType) {
            case self::FILE_TYPE_NEW_CARDS:
                $faultyCards = $this->parseNewCards($xml);
            break;
            case self::FILE_TYPE_STATUS_CHANGES:
                $faultyCards = $this->parseStatusChanges($xml);
            break;
            default:
            break;
        }

        if (sizeof($faultyCards) > 0) {
            IsicMail::sendSwedImportFaultyCardsNotification($faultyCards, pathinfo($filename, PATHINFO_BASENAME));
            return false;
        }
        return true;
    }

    protected function parseNewCards($cards) {
        return $this->parseAndSaveCards($cards, self::FILE_TYPE_NEW_CARDS);
    }

    protected function parseStatusChanges($cards) {
        return $this->parseAndSaveCards($cards, self::FILE_TYPE_STATUS_CHANGES);
    }

    protected function parseAndSaveCards($cards, $type) {
        $faultyCards = array();
        foreach ($cards as $bankCard) {
            print_r($bankCard);
            $cardFields = null;
            if ($type == self::FILE_TYPE_NEW_CARDS) {
                $cardFields = $this->assignCardFields($bankCard);
            } else if ($type == self::FILE_TYPE_STATUS_CHANGES) {
                $cardFields = $this->assignCardFieldsStatus($bankCard);
                $card_id = null;
                if (is_array($cardFields)) {
                    $card_id = $this->isicDbCards->getIdByIsicNumberReceivedDate(
                        $cardFields['isic_number'],
                        $cardFields['received_date']
                    );
                }
                if (!$card_id) {
                    $faultyCards[] = $bankCard;
                    echo "Could not find such card, skipping.\n";
                    continue;
                }
                $cardRecord = $this->isicDbCards->getRecord($card_id);
                $cardFields['person_number']     = $cardRecord['person_number'];
                $cardFields['person_name_first'] = $cardRecord['person_name_first'];
                $cardFields['person_name_last']  = $cardRecord['person_name_last'];
                $cardFields['person_birthday']   = $cardRecord['person_birthday'];
                $cardFields['school_id']         = $cardRecord['school_id'];
            }
            if (is_array($cardFields)) {
                $res = $this->saveCardData($cardFields, $type);
                echo 'Result: ' . $res . "\n";
            } else {
                echo "Error: no cardFields assigned\n";
            }
        }
        return $faultyCards;
    }

    protected function assignCardFieldsStatus($bankCard) {
        $cardFields = array(
            'person_email' => $this->getNodeValue($bankCard->Email),
            'person_phone' => $this->getNodeValue($bankCard->Phone),
            'isic_number' => $this->getNodeValue($bankCard->ISICSserial),
            'type_id' => $this->isicDbBankCardTypes->getTypeIdByNameAndCreateIfNotFound($this->getNodeValue($bankCard->Type)),
            'bank_status_id' => $this->isicCommon->getBankStatusId($this->getNodeValue($bankCard->Status), $this->bankId),
            'received_date' => $this->getNodeValue($bankCard->OpenDate),
            'active' => $this->getNodeValue($bankCard->Status) == 'A' ? 1 : 0,
        );
        return $cardFields;
    }

    protected function getPersonNumberFromIdCode($serial) {
        return $serial;
//        if (strlen($serial) == 11) {
//            return $serial;
//        }
//        if (strlen($serial) == 8) {
//            return 'B0' . $this->bankId . $serial;
//        }
//        throw new InvalidPersonNumberException('Invalid Person Number: ' . $serial);
//        return null;
    }

    /**
     * @param $bankCard
     * @return array
     */
    protected function assignCardFields($bankCard)
    {
        try {
            $cardFields = array(
                'language_id' => self::LANGUAGE_ID,
                'kind_id' => self::KIND_ID,
                'bank_id' => $this->bankId,
                'person_name_first' => $this->getNodeValue($bankCard->FirstName),
                'person_name_last' => $this->getNodeValue($bankCard->LastName),
                'person_number' => $this->getPersonNumberFromIdCode($this->getNodeValue($bankCard->IdCode)),
                'person_birthday' => $this->getNodeValue($bankCard->BirthDate),
                'bank_pic' => $this->getNodeValue($bankCard->Photo),
                'school_id' => $this->isicDbBankSchools->getSchoolIdByNameAndCreateIfNotFound($this->getNodeValue($bankCard->SchoolName)),
                'expiration_date' => $this->getNodeValue($bankCard->ExpiryDate),
                'bank_description' => $this->getNodeValue($bankCard->Description),
            );
            return array_merge($cardFields, $this->assignCardFieldsStatus($bankCard));
        } catch (InvalidPersonNumberException $e) {
            echo $e->getMessage() . "\n";
            return null;
        }
    }

    private function getNodeValue($value) {
        return (string)$value;
    }

    public function loadFile($filename) {
        if (!$this->isValidXml($filename)) {
            return null;
        }
        $xml = simplexml_load_file($filename);
        return $xml;
    }

    public function isValidXml($filename) {
        libxml_use_internal_errors(true);
        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->load($filename);
        if ($this->hasXmlErrors()) {
            return false;
        }

        $xml->schemaValidate(SITE_PATH . self::SCHEMA_FILE);
        if ($this->hasXmlErrors()) {
            return false;
        }

        return true;
    }

    protected function hasXmlErrors() {
        $errors = libxml_get_errors();
        if (!empty($errors) && $errors[0]->level < LIBXML_ERR_FATAL) {
            print_r($errors);
            return true;
        }
        return false;
    }

    public function getXMLType(SimpleXMLElement $xml) {
        switch (strtolower($xml->getName())) {
            case 'newcards':
                return self::FILE_TYPE_NEW_CARDS;
            break;
            case 'statuschanges':
                return self::FILE_TYPE_STATUS_CHANGES;
            break;
            default:
                return null;
            break;
        }
    }

}
