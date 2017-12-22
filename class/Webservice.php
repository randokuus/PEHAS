<?php
require_once(SITE_PATH . "/class/Webservice/WSPartner.php");
require_once(SITE_PATH . "/class/Webservice/WSParser.php");
require_once(SITE_PATH . "/class/Webservice/WSDate.php");
require_once(SITE_PATH . "/class/Webservice/WSDevice.php");
require_once(SITE_PATH . "/class/Webservice/WSLock.php");
require_once(SITE_PATH . "/class/Webservice/WSCard.php");
require_once(SITE_PATH . "/class/Webservice/WSValidator.php");
require_once(SITE_PATH . "/class/Webservice/WSEvent.php");
require_once(SITE_PATH . "/class/Webservice/WSMessageResponse.php");
require_once(SITE_PATH . "/class/Webservice/WSVersion.php");
require_once(SITE_PATH . "/class/Webservice/WSLockEvent.php");
require_once(SITE_PATH . "/class/Webservice/WSUserStatus.php");
require_once(SITE_PATH . "/class/Webservice/WSPerson.php");
require_once(SITE_PATH . "/class/Webservice/WSChipNumberConverter.php");
require_once(SITE_PATH . "/class/IsicDB.php");

class Webservice {
    /**
     * Partner object
     *
     * @var _partner
     * @access protected
     */
    var $_partner = false;

    /**
     * @var _version
     */
    private $_version;

    /**
     * Class constructor
     *
     * @global $GLOBALS['database']
     */
    function Webservice($partner) {
        $this->_partner = $partner;
        $this->_version = new WSVersion();
    }

    function getResult($rawPostData) {
        if ($rawPostData) {
            $parsedMessage = WSParser::parse($rawPostData);
            return $this->processMessage($parsedMessage);
        }
    }

    /**
     * Processes Card Valid message
     *
     * @param array $param_list array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if authentication was successful, false otherwise
    */
    function processCardValid ($param_list = array(), &$error_list) {
        $success = true;
        $card = new WSCard($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];
            if (!$param["card_number"]) {
                $error_list[$i]["card_number"] = 100; // card number not specified
            } else  {
                $data = $card->getCardRecord($param["card_number"], 1);
                if (!$data) {
                    $error_list[$i]["card_number"] = 101; // unknown card number
                } else {
                    if (!$card->isActive($data)) {
                        $error_list[$i]["card_number"] = 102; // card not valid
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Processes Card Valid message
     *
     * @param array $param_list array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if authentication was successful, false otherwise
    */
    function processCardValidNumberType ($param_list = array(), &$error_list) {
        $success = true;
        $card = new WSCard($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i]["param"][0];
            // if number type is not set, then assigning default type to it (1 == card serial number)
            if (WSValidator::isEmpty($param["number_type"])) {
                $param["number_type"] = 1;
            }
            if (!$param["card_number"]) {
                $error_list[$i]["card_number"] = 100; // card number not specified
            } else if (!WSValidator::isValidNumberType($param["number_type"])) {
                $error_list[$i]["number_type"] = 103; // unknown card number type id
            } else  {
                $data = $card->getCardRecord($param["card_number"], $param['number_type']);
                if (!$data) {
                    $error_list[$i]["card_number"] = 101; // unknown card number
                } else {
                    if (!$card->isActive($data)) {
                        $error_list[$i]["card_number"] = 102; // card not valid
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Processes Person Valid Card message
     *
     * @param array $param_list array of parameters
     * @param array $error_list array for storing error-numbers
     * @param array $card_list array for storing card infos
     * @return boolean true if authentication was successful, false otherwise
    */
    function processPersonValidCard ($param_list = array(), &$error_list, &$card_list) {
        $success = true;
        $card_list = array();
        $error_count = 0;
        $card_count = 0;
        $card = new WSCard($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];
            if (!$param["person_number"]) {
                $error_list[$error_count++]["person_number"] = array($param["person_number"], 500); // person number not specified
            } else {
                $personCards = $card->getPersonCards($param['person_number']);

                if (!$personCards) {
                    $error_list[$error_count++]["person_number"] = array($param["person_number"], 501); // unknown person number
                } else {
                    $hasValidCards = false;
                    foreach ($personCards as $data) {
                        if ($card->isActive($data)) {
                            $card_list[$card_count]["card_id"] = $data["card_id"];
                            $card_list[$card_count]["card_number"] = $data["isic_number"];
                            $card_list[$card_count]["chip_number"] = WSChipNumberConverter::convert($data["chip_number"]);
                            $card_list[$card_count]["person_number"] = $data["person_number"];
                            $card_list[$card_count]["person_name"] = $data["person_name_first"] . ' ' . $data["person_name_last"];
                            $card_list[$card_count]["person_name_first"] = $data["person_name_first"];
                            $card_list[$card_count]["person_name_last"] = $data["person_name_last"];
                            $card_list[$card_count]["person_email"] = $data["person_email"];
                            $card_list[$card_count]["person_phone"] = $data["person_phone"];
                            $card_list[$card_count]["card_type"] = $data["card_type_name"];
                            $card_count++;
                            $hasValidCards = true;
                        }
                    }
                    if (!$hasValidCards) {
                        $error_list[$error_count++]["person_number"] = array($param["person_number"], 502); // person has no valid cards
                    }
                }
            }
        }
        return $success;
    }

    /**
     * Processes Card List message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @param array $card_list array for storing card infos
     * @return boolean true if register was successful, false otherwise
    */
    function processCardList($param_list = array(), &$error_list, &$card_list) {
        $success = true;
        $card_list = array();
        $card_count = 0;
        if (!sizeof($param_list)) {
            $param_list = array(0 => array());
        }
        $card = new WSCard($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];

            $validFromDate = WSDate::getValidFromDate($param['from']);
            $validUntilDate = WSDate::getValidUntilDate($param['until']);
            $cardsADE = $card->getActivatedDeactivatedExpiredCardsSinceDate($validFromDate, $validUntilDate);

            if (!$cardsADE) {
                continue;
            }
            foreach ($cardsADE as $data) {
                // check if person_code parameter is set and current card belongs to given person
                if (isset($param['person_code']) && $data['person_number'] != $param['person_code']) {
                    continue;
                }
                // check if card_validity parameter is set and if it matches current card active state
                if (isset($param['card_validity']) && $param['card_validity'] != $card->isActive($data)) {
                    continue;
                }
                $card_list[$card_count]["card_id"] = $data["card_id"];
                $card_list[$card_count]["card_number"] = $data["isic_number"];
                $card_list[$card_count]["chip_number"] = WSChipNumberConverter::convert($data["chip_number"]);
                $card_list[$card_count]["person_number"] = $data["user"];
                $card_list[$card_count]["person_code"] = $data["person_number"];
                $card_list[$card_count]["person_name"] = $data["person_name_first"] . ' ' . $data["person_name_last"];
                $card_list[$card_count]["person_name_first"] = $data["person_name_first"];
                $card_list[$card_count]["person_name_last"] = $data["person_name_last"];
                $card_list[$card_count]["person_email"] = $data["person_email"];
                $card_list[$card_count]["person_phone"] = $data["person_phone"];
                $card_list[$card_count]["card_active"] = $card->isActive($data) ? "true" : "false";
                if (version_compare($this->_version->getVersion(), '2.1', '>=')) {
                    $card_list[$card_count]["expiration_date"] = $data["expiration_date"];
                    $card_list[$card_count]["card_type"] = $data["card_type_name"];
                    $card_list[$card_count]["card_kind"] = $data["card_kind_name"];
                    $card_list[$card_count]["school_name"] = $data["school_name"];
                    $card_list[$card_count]["export_time"] = $data["export_time"];
                    $card_list[$card_count]["bank_name"] = $data["bank_name"];
                    $card_list[$card_count]["activation_date"] = $data["activation_date"];
                    $card_list[$card_count]["deactivation_date"] = $data["deactivation_date"];
                    $card_list[$card_count]["school_passcode"] = $data["school_passcode"];
                }
                $card_count++;
            }
        }
        return $success;
    }

    /**
     * Processes Lock List message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if register was successful, false otherwise
    */
    function processLockList ($param_list = array(), &$error_list) {
        $success = true;
        $lockIdList = array();
        $lock = new WSLock($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i]["param"][0];
            if (!WSValidator::isValidId($param["lock_id"])) {
                $error_list[$i]["lock_id"] = 200; // lock not specified
            } elseif (!$param["name"]) {
                $error_list[$i]["name"] = 201; // name not specified
            } else {
                if (!$param["description"]) {
                    $param["description"] = "";
                }
                $lockIdList[] = $lock->saveLockRecord($param);
            }
        }

        if (sizeof($lockIdList)) {
            $lock->deactivateUnlistedLocks($lockIdList);
        }

        return $success;
    }

    /**
     * Processes Lock Access message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if register was successful, false otherwise
    */
    function processLockAccess ($param_list = array(), &$error_list) {
        $success = true;
        $event_id_list = array();
        $card = new WSCard($this->_partner);
        $lock = new WSLock($this->_partner);
        $lockEvent = new WSLockEvent($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i]["param"][0];
            $t_data = array();

            if (!WSValidator::isValidId($param["event_id"])) {
                $error_list[$i]["event_id"] = 300; // record id not specified
            } elseif (in_array($param["event_id"], $event_id_list)) {
                $error_list[$i]["event_id"] = 301; // record id not unique
            } elseif (!$param["dir"]) {
                $error_list[$i]["dir"] = 310; // direction not specified
            } elseif (!($t_data['dir'] = $lockEvent->getDirId($param["dir"]))) {
                $error_list[$i]["dir"] = 311; // direction not correct
            } elseif (!$param["access"]) {
                $error_list[$i]["access"] = 320; // access not specified
            } elseif (!($t_data['access'] = $lockEvent->getAccessId($param["access"]))) {
                $error_list[$i]["access"] = 321; // unknown access
            } elseif (!$param["number_type"]) {
                $error_list[$i]["number_type"] = 332; // number type not specified
            } elseif (!WSValidator::isValidNumberType($param["number_type"])) {
                $error_list[$i]["number_type"] = 333; // unknown number type
            } elseif (!$param["card_number"]) {
                $error_list[$i]["card_number"] = 330; // card number not specified
            } elseif (!($t_data['card'] = $card->getId($param["card_number"], $param["number_type"]))) {
                $error_list[$i]["card_number"] = 331; // unknown card number
            } elseif (!$param["lock_id"] && $param["lock_id"] !== 0 && $param["lock_id"] !== "0") {
                $error_list[$i]["lock_id"] = 340; // lock id not specified
            } elseif (!($t_data['lock'] = $lock->getId($param["lock_id"]))) {
                $error_list[$i]["lock_id"] = 341; // unknown lock id
            } elseif (!$param["event_time"]) {
                $error_list[$i]["event_time"] = 350; // event time not specified
            } elseif (!($t_data['time'] = WSDate::string2date($param["event_time"]))) {
                $error_list[$i]["event_time"] = 351; // invalid event time
            } else {
                $t_data['access']--; // decrease acces by 1 for getting correct value for db
                $t_data['event'] = $event_id_list[] = $param["event_id"]; // list of rec id-s
                if (!$lockEvent->saveLockEventIfUnique($t_data)) {
                    $error_list[$i]["event_id"] = 301; // record id not unique
                }
            }
        }

        return $success;
    }

    /**
     * Processes Device List message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if register was successful, false otherwise
    */
    function processDeviceList ($param_list = array(), &$error_list) {
        $success = true;
        $deviceIdList = array();
        $device = new WSDevice($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i]["param"][0];
            if (!WSValidator::isValidId($param["device_id"])) {
                $error_list[$i]["device_id"] = 800; // device not specified
            } elseif (!$param["name"]) {
                $error_list[$i]["name"] = 801; // name not specified
            } elseif (!WSValidator::isValidId($param["type_id"])) {
                $error_list[$i]["type_id"] = 802; // device type not specified
            } elseif (!WSValidator::isValidDeviceType($param["type_id"])) {
                $error_list[$i]["type_id"] = 803; // device type invalid
            } else {
                if (!$param["description"]) {
                    $param["description"] = "";
                }
                $deviceIdList[] = $device->saveDeviceRecord($param);
            }
        }

        if (sizeof($deviceIdList)) {
            $device->deactivateUnlistedDevices($deviceIdList);
        }

        return $success;
    }

    /**
     * Processes Register Sale message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @return boolean true if register was successful, false otherwise
    */
    function processRegisterSale ($param_list = array(), &$error_list) {
        $success = true;
        $event_id_list = array();
        $device = new WSDevice($this->_partner);
        $card = new WSCard($this->_partner);
        $event = new WSEvent($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i]["param"][0];
            $t_data = array();

            if (!WSValidator::isValidId($param["event_id"])) {
                $error_list[$i]["event_id"] = 700; // event id not specified
            } elseif (in_array($param["event_id"], $event_id_list)) {
                $error_list[$i]["event_id"] = 701; // event id not unique
            } elseif (!$param["number_type"]) {
                $error_list[$i]["number_type"] = 712; // number type not specified
            } elseif (!WSValidator::isValidNumberType($param["number_type"])) {
                $error_list[$i]["number_type"] = 713; // unknown number type
            } elseif (!$param["card_number"]) {
                $error_list[$i]["card_number"] = 710; // card number not specified
            } elseif (!($t_data['card'] = $card->getId($param["card_number"], $param["number_type"]))) {
                $error_list[$i]["card_number"] = 711; // unknown card number
            } elseif (!WSValidator::isValidId($param["device_id"])) {
                $error_list[$i]["device_id"] = 720; // device id not specified
            } elseif (!($t_data['device'] = $device->getId($param["device_id"]))) {
                $error_list[$i]["device_id"] = 721; // unknown device id
            } elseif (!$param["event_time"]) {
                $error_list[$i]["event_time"] = 730; // event time not specified
            } elseif (!($t_data['time'] = WSDate::string2date($param["event_time"]))) {
                $error_list[$i]["event_time"] = 731; // invalid event time
            } elseif (WSValidator::isEmpty($param["sale_sum"])) {
                $error_list[$i]["sale_sum"] = 740; // sales sum not specified
            } elseif (!WSValidator::isValidSum($param["sale_sum"])) {
                $error_list[$i]["sale_sum"] = 741; // sales sum invalid
            } elseif (WSValidator::isEmpty($param["discount_sum"])) {
                $error_list[$i]["discount_sum"] = 750; // discount sum not specified
            } elseif (!WSValidator::isValidSum($param["discount_sum"])) {
                $error_list[$i]["discount_sum"] = 751; // discount sum invalid
            } elseif (!$param["currency"]) {
                $error_list[$i]["currency"] = 760; // currency not specified
            } elseif (!WSValidator::isValidCurrency($param["currency"])) {
                $error_list[$i]["currency"] = 761; // currency invalid
            } else {
                $t_data['event'] = $event_id_list[] = $param["event_id"]; // list of rec id-s
                $t_data['sale_sum'] = $param['sale_sum'];
                $t_data['discount_sum'] = $param['discount_sum'];
                $t_data['currency'] = $param['currency'];
                if (!$event->saveRegisterSaleRecordIfUnique($t_data)) {
                    $error_list[$i]["event_id"] = 701; // record id not unique
                }
            }
        }

        return $success;
    }


    /**
     * Processes User statusList message
     *
     * @param array $error_list array for storing error-numbers
     * @return array of user statuses
    */
    function processUserStatusList(&$error_list, &$userStatusList) {
        $success = true;
        $userStatus = new WSUserStatus($this->_partner);
        $userStatusList = $userStatus->getList();
        if (sizeof($userStatusList) == 0) {
            $error_list[0] = 600;
        }
        return $success;
    }

    /**
     * Processes Person Status List message
     *
     * @param_list array $param array of parameters
     * @param array $error_list array for storing error-numbers
     * @param array $person_status_list array for storing status infos
     * @return boolean true if register was successful, false otherwise
    */
    function processPersonStatusList ($param_list = array(), &$error_list, &$person_status_list) {
        $success = true;
        $person_status_list = array();
        $status_count = 0;
        if (!sizeof($param_list)) {
            $param_list = array(0 => array());
        }
        $userStatus = new WSUserStatus($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];

            $validFromDate = WSDate::getValidFromDate($param['from']);
            $statusList = $userStatus->getUserStatusesSinceDate($validFromDate);

            if ($statusList) {
                foreach ($statusList as $data) {
                    $person_status_list[$status_count]["person_number"] = $data["person_number"];
                    $person_status_list[$status_count]["status_id"] = $data["status_id"];
                    $person_status_list[$status_count]["school_ehis_code"] = $data["school_ehis_code"];
                    $person_status_list[$status_count]["faculty"] = $data["faculty"];
                    $person_status_list[$status_count]["class"] = $data["class"];
                    $person_status_list[$status_count]["course"] = $data["course"];
                    $person_status_list[$status_count]["position"] = $data["position"];
                    $person_status_list[$status_count]["structure_unit"] = $data["structure_unit"];
                    $person_status_list[$status_count]["status_active"] = $userStatus->isActive($data) ? "true" : "false";
                    $status_count++;
                }
            }
        }
        return $success;
    }

    /**
     * Processes Person Picture message
     *
     * @param array $param_list array of parameters
     * @param array $error_list array for storing error-numbers
     * @param array $picture_data array for storing picture data
     * @return boolean true
    */
    function processPersonPicture($param_list = array(), &$error_list, &$picture_data) {
        $success = true;
        $picture_data = array();
        $picture_count = 0;
        $error_count = 0;
        $person = new WSPerson($this->_partner);

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];
            if (!$param["person_number"]) {
                $error_list[$error_count++]["person_number"] = array($param["person_number"], 900); // person number not specified
            } else {
                $personData = $person->getPersonRecord($param['person_number']);

                if (!$personData) {
                    $error_list[$error_count++]["person_number"] = array($param["person_number"], 901); // unknown person number
                } else {
                    foreach ($personData as $data) {
                        if ($data['picture_data']) {
                            $picture_data[$picture_count]["person_number"] = $data["user_code"];
                            $picture_data[$picture_count]["picture_data"] = $data["picture_data"];
                            $picture_count++;
                        }
                    }
                    if (!$picture_count) {
                        $error_list[$error_count++]["person_number"] = array($param["person_number"], 902); // person has no picture
                    }
                }
            }
        }
        return $success;
    }

    /**
     * Processes Last Event message
     *
     * @param array $error_list array for storing error-numbers
     * @return last event ID if found any event records, false otherwise
    */
    function processLastEvent (&$error_list) {
        $lockEvent = new WSLockEvent($this->_partner);
        $locationMaxEvent = $lockEvent->getLocationMaxEventId();

        if (!$locationMaxEvent) {
            $error_list[0] = 400;
        }

        return $locationMaxEvent;
    }


    /**
     * Processes Person Valid PAN message
     *
     * @param array $param_list array of parameters
     * @param array $error_list array for storing error-numbers
     * @param array $card_list array for storing card infos
     * @return boolean true if authentication was successful, false otherwise
    */
    function processPersonValidPan($param_list = array(), &$error_list, &$card_list) {
        $success = true;
        $card_list = array();
        $error_count = 0;
        $card_count = 0;
        $card = new WSCard($this->_partner);
        $isicDbUser = IsicDB::factory('Users');

        for ($i = 0; $i < sizeof($param_list); $i++) {
            $param = $param_list[$i];
            if (!$param["person_number"]) {
                $error_list[$error_count++]["person_number"] = array($param["person_number"], 1000); // person number not specified
            } else {
                $personCards = $card->getPersonCards($param['person_number']);
                $user = $isicDbUser->getRecordByCode($param['person_number']);
                if ($user) {
                    $isicDbUser->updateRecord(
                        $user['user'],
                        array(
                            'pan_queries' => $user['pan_queries'] + 1,
                            'last_pan_query' => IsicDate::getCurrentTimeFormatted(IsicDate::DB_DATETIME_FORMAT),
                            'data_sync_allowed' => 1
                        )
                    );
                }

                if (!$personCards) {
                    $error_list[$error_count++]["person_number"] = array($param["person_number"], 1001); // unknown person number
                } else {
                    $hasValidCards = false;
                    foreach ($personCards as $data) {
                        if ($card->isActive($data) && $data['card_type_chip'] /*strlen($data['pan_number']) == 19*/) {
                            $card_list[$card_count]["pan_number"] = $data["pan_number"];
                            $card_list[$card_count]["card_number"] = $data["isic_number"];
                            $card_list[$card_count]["person_number"] = $data["person_number"];
                            $card_list[$card_count]["card_expiration"] = $data["expiration_date"];
                            $card_count++;
                            $hasValidCards = true;
                        }
                    }
                    if (!$hasValidCards) {
                        $error_list[$error_count++]["person_number"] = array($param["person_number"], 1002); // person has no valid cards
                    }
                }
            }
        }
        return $success;
    }
    /**
     * Processes parsed XML message
     *
     * @param array $message parsed XML message as array
     * @return string XML-formatted response message
    */
    function processMessage($message = array()) {
        $_header = WSParser::getHeader($message);
        $_param = WSParser::getParameters(WSParser::getBody($message));
        $_error = 0;
        $_error_list = array();
        $_card_list = array();
        $_success = false;
        $_user_status_list = array();
        $_person_status_list = array();
        $_picture_data = array();
        $response = "";

        $event = new WSEvent($this->_partner);

        if (!$message) {
            $_error = 1; // no XML
        } else if (!$_header["ver"]) {
            $_error = 2; // no version number
        } else if (!$this->_version->isValidVersion($_header['ver'])) {
            $_error = 3; // unknown version number
        } else if (!$_header["event"]) {
            $_error = 4; // event not given
        } else if (!$event->isValidEventName($_header["event"])) {
            $_error = 5; // event not recognised
        } else if (!$_header["partnerid"]) {
            $_error = 6; // partner id is not given
//        } else if ($_header["partnerid"] != $this->_partner->getId()) {
//            $_error = 7; // partner id is not recognized or is disabled
        } else if (!$this->_partner->isIpAllowed()) {
            $_error = 8; // ip address not recognised
        } else if (!WSValidator::isValidId($_header["reqid"])) {
            $_error = 9; // request ID is not given
        } else if (!$this->_partner->isActive()) {
            $_error = 11; // partner disabled
        } else {
            $this->_partner->setReceivedId($_header['partnerid']);
            $this->_version->setVersion($_header['ver']);
            switch($_header["event"]) {
                case "card_valid":
                    if ($this->_version->getVersion() == '1.0') {
                        $_success = $this->processCardValid($_param["param"], $_error_list);
                    } else {
                        $_success = $this->processCardValidNumberType($_param["card"], $_error_list);
                    }
                break;
                case "card_list":
                    $_success = $this->processCardList($_param["param"], $_error_list, $_card_list);
                break;
                case "lock_list":
                    $_success = $this->processLockList($_param["lock"], $_error_list);
                break;
                case "lock_access":
                    $_success = $this->processLockAccess($_param["event"], $_error_list);
                break;
                case "last_event":
                    $_success = $this->processLastEvent($_error_list);
                break;
                case "person_valid_card":
                    $_success = $this->processPersonValidCard($_param["param"], $_error_list, $_card_list);
                break;
                case "device_list":
                    $_success = $this->processDeviceList($_param["device"], $_error_list);
                break;
                case "register_sale":
                    $_success = $this->processRegisterSale($_param["event"], $_error_list);
                break;
                case "status_list":
                    $_success = $this->processUserStatusList($_error_list, $_user_status_list);
                break;
                case "person_status_list":
                    $_success = $this->processPersonStatusList($_param["param"], $_error_list, $_person_status_list);
                break;
                case "person_picture":
                    $_success = $this->processPersonPicture($_param["param"], $_error_list, $_picture_data);
                break;
                case "person_valid_pan":
                    $_success = $this->processPersonValidPan($_param["param"], $_error_list, $_card_list);
                break;
                default:
                    $_error = 99; // Unknown error
                break;
            }
        }

        $paramName = $this->getParameterListName($_header['event'], $this->_version->getVersion());
        $messageResponse = new WSMessageResponse($this->_partner);
        $messageResponse->setHeader($_header);
        $messageResponse->setSuccess($_success);
        $messageResponse->setError($_error);
        $messageResponse->setParametersList($_param[$paramName]);
        $messageResponse->setParamErrorList($_error_list);
        $messageResponse->setCardList($_card_list);
        $messageResponse->setUserStatusList($_user_status_list);
        $messageResponse->setPersonStatusList($_person_status_list);
        $messageResponse->setPictureData($_picture_data);
        $response = $messageResponse->createMessageResponse();

        return $response;
    }

    function getParameterListName($event, $version) {
        switch($event) {
            case 'card_valid':
                if ($version == '1.0') {
                    return 'param';
                } else {
                    return 'card';
                }
            break;
            case 'lock_list':
                return 'lock';
            break;
            case 'lock_access':
                return 'event';
            break;
            case 'device_list':
                return 'device';
            break;
            case 'register_sale':
                return 'event';
            break;
            default:
                return 'param';
            break;
        }
    }

    /**
     * Processes parsed XML reponse message
     *
     * @param array $message parsed XML message as array
     * @return array of parameters and errors (if they occured)
    */
    function processMessageResponse($message = array()) {
        $_header = WSParser::getHeader($message);
        $_param = WSParser::getParameters(WSParser::getBody($message));
        $_error = 0;
        $_error_list = array();
        $_success = false;

        return $_param;
    }
}