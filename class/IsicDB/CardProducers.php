<?php

require_once(SITE_PATH . "/class/Isic/IsicDate.php");
require_once(SITE_PATH . "/class/FactoryPattern.php");
require_once(SITE_PATH . "/class/IsicDB/CardProducers/Helpers/Interface.php");
require_once(SITE_PATH . "/class/IsicDB/CardProducers/Helpers/ChipFile.php");


class IsicDB_CardProducers extends IsicDB {
    
    protected $table = 'module_isic_card_producers';
    
    protected $searchableFields = array('name');
    
    protected $insertableFields = array('name', 'config');
    
    protected $updateableFields = array('name', 'config');
    
    
    // PUBLIC METHODS
    
    public function exportCardsArray($card_type = 0, $school_id = 0, $card_ids = false) {
        $card_list = array();
        if (!$card_type) {
            $card_type = 0;
        }
        if (!$school_id) {
            $school_id = 0;
        }
        $cards = IsicDB::factory('Cards'); 
        if (is_array($card_ids) && sizeof($card_ids) > 0) {
        	$cardList = $cards->findRecordsToOrder($card_ids);
        } else {
        	$cardList = $cards->findRecordsToOrderWithoutIds($card_type, $school_id);
        }
        return $cardList;
    }
    
	public function exportRecordCards(array $producerData, array $cardIds) {
		$cards = IsicDB::factory('Cards');
		$transfers = IsicDB::factory('CardTransfers');
		$producer = self::loadProducer($producerData['name'], $producerData['config']);
		$transferData = $transfers->getRecord(
		    $transfers->insertRecord(array("producer_id" => $producerData['id']))
		);
		$transfers->updateRecord($transferData['id'], array(
		    	'order_name' => $producer->getTransferName(
		            $transferData['id'], $transferData['date'], $transferData['sequence']
                )
        ));
		$transferData = $transfers->getRecord($transferData['id']);
		$allowedCardIds = array();
		foreach ($cards->findRecordsToOrder($cardIds) as $cardData) {
		    $allowedCardIds[] = $cardData['id'];
		}
		$cardList = $cards->getRecordsForOrderByIds($allowedCardIds);
		$export_timestamp = IsicDate::getCurrentTimeFormatted("Y-m-d H:i:s");
		try {
            $resultList = $producer->exportCards($cardList, $transferData);
		} catch (Exception $e) {
		    self::assert(false, $e->getMessage());
		}
		foreach ($resultList as $cardData) {
    	    $cards->updateRecord($cardData['id'], array("order_id" => $transferData['id'], "exported" => $export_timestamp));
		}
		return $resultList;
	}
	
	public function importRecordChips(array $producerData) {
	    self::log("Import started for " . $producerData['name'] . "...");
		$cards = IsicDB::factory('Cards');
		$transfers = IsicDB::factory('CardTransfers');
		$transferList = $transfers->findRecords(array("producer_id" => $producerData['id'], "success" => "0"));
		$producer = self::loadProducer($producerData['name'], $producerData['config']);
		foreach ($transferList as $transferData) {
		    if (!$transferData['order_name']) {
		        continue;
		    }
		    self::log("  Looking for any updates of order " . $transferData['order_name'] . "...");
 		    $cardList = $cards->findRecords(array('order_id' => $transferData['id']));
		    try {
		        $chipFile = $producer->importChips($cardList, $transferData);
		    } catch (Exception $e) {
		        self::log("  An error occured while searching for updates: " . $e->getMessage());
		        continue;
		    }
		    if ($chipFile === null) {
		        self::log("  Done. No updates found.");
		        continue;  // no file for this transfer yet
		    }
		    $chiplessIsicList = array();
		    $chipList = $chipFile->getChips();
		    foreach ($cardList as $cardData) {
		        $isicNumber = $cardData['isic_number'];
		        if (isset($chipList[$isicNumber])) {
		            $re = $cardData['chip_number'] ? "re" : "";
		            $cards->updateRecord($cardData['id'], array('chip_number' => $chipList[$isicNumber]));
		            self::log(
		                "    Success: Chip number " . $chipList[$isicNumber] . " " . $re . "assigned " .
		                "for card number " . $isicNumber
		            );
		            unset($chipList[$isicNumber]);
		        } else {
		            $chiplessIsicList[] = $isicNumber;
		        }
		    }
		    foreach ($chipList as $isicNumber => $chipNumber) {
		        self::log("    Error: Couldn't find card '$isicNumber' to assign the chip number '$chipNumber' for.");
		    }
		    if (count($chiplessIsicList) > 0) {
		        IsicMail::sendCardTransferFailedNotification($transferData['order_name'], $chiplessIsicList);
                self::log(
                	"  Done. Unfortunately, some cards didn't get a chip number: " . implode(", ", $chiplessIsicList) . "."
                );
		    } else {
		        self::log("  Done.");
		    }
			$transfers->updateRecord($transferData['id'], array(
			    "chip_file" => $chipFile->getFilename(),
			    'import_time' => IsicDate::getCurrentTimeFormatted("Y-m-d H:i:s")
			));
		}
        self::log("Done!\n");
	}
	
    public function listUnregisteredProducers() {
        static $available = null;
        if (is_null($available)) {
            $available = FactoryPattern::available('CardProducers', dirname(__FILE__));
        }
        $registered = array();
        foreach ($this->listRecords() as $producerData) {
            $registered[] = $producerData['name'];
        }
        return array_diff($available, $registered);
    }
	
    public function getConfigFields($producerName) {
        return self::loadProducer($producerName)->getConfigFields();
    }
    
    
    // PRIVATE METHODS
    
    private static function loadProducer($name, array $config = array()) {
        $obj = FactoryPattern::factory('CardProducers', $name, dirname(__FILE__), array($config));
        self::assert(is_object($obj), "No such producer: $name");
        return $obj;
    }
    
    private static function encodeRecord(array $producerData) {
        if (isset($producerData['config'])) {
            $producerData['config'] = serialize(
                array_intersect_key(
                    (array)$producerData['config'],
                    array_flip(self::loadProducer($producerData['name'])->getConfigFields())
                )
            );
        }
        return $producerData;
    }
    
    private static function decodeRecord(array $producerData) {
        if (isset($producerData['config'])) {
            $config = unserialize($producerData['config']);
            $producerData['config'] = is_array($config) ? $config : array();
        }
        return $producerData;
    }
    
    private static function decodeRecordsList(array $producersList) {
        $result = array();
        foreach ($producersList as $producerData) {
            $result[] = self::decodeRecord($producerData);
        }
        return $result;
    }
    
    private static function log($message) {
        print "\n[" . date("d.m.Y H:i:s") . "] $message ";
    }
    
    
    
    // OVERWRITTEN PUBLIC METHODS
    
    public function getRecord($id) {
        return self::decodeRecord(parent::getRecord($id));
    }
    
    public function listRecords($offset = parent::OFFSET, $limit = parent::LIMIT) {
        static $records = null;
        if (is_array($records)) {
            return $records;
        }
        return $records = self::decodeRecordsList(parent::listRecords($offset, $limit));
    }
    
    public function findRecords(array $fields, $offset = self::OFFSET, $limit = self::LIMIT) {
        return self::decodeRecordsList(parent::findRecords($fields, $offset, $limit));
    }
    
    public function findRecord(array $fields, $orderByField = null, $orderByTable = null) {
        return self::decodeRecord(parent::findRecord($fields, $orderByField, $orderByTable));
    }
    
    public function insertRecord(array $data) {
        return parent::insertRecord(self::encodeRecord($data));
    }
    
    public function updateRecord($id, array $data) {
        return parent::updateRecord($id, self::encodeRecord($data));
    }
    
    public function listRecordsFields($fields, $orderby = 'name') {
        return self::decodeRecordsList(parent::listRecordsFields($fields, $orderby));
    }
    
    public function getRecordsByIds($ids, $orderBy = null) {
        return self::decodeRecordsList(parent::getRecordsByIds($ids, $orderBy));
    }
    
}
