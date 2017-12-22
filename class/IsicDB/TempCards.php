<?php

class IsicDB_TempCards extends IsicDB {
    /**
     * States for cards
     */
    const state_ordered = 1;
    const state_distributed = 2;
    const state_activated = 3;
    const state_deactivated = 4;

    /**
     *  Card kinds
     */
    const kind_regular = 1;
    const kind_union = 2;

    protected $table = 'module_isic_card';
    protected $primary = 'id';
    protected $orderBy = 'id';

    protected $insertableFields = array(
        'state_id', 'prev_card_id', 'adddate', 'adduser', 'moddate', 'moduser', 'status_id',
        'bank_status_id', 'active', 'returned', 'language_id', 'kind_id', 'bank_id', 'type_id',
        'school_id', 'person_name', 'person_name_first', 'person_name_last', 'person_birthday',
        'person_number',
        'person_email', 'person_phone', 'person_position', 'person_class', 'person_stru_unit',
        'person_stru_unit2', 'person_staff_number', 'person_bankaccount', 'person_bankaccount_name',
        'person_newsletter', 'isic_code', 'isic_number', 'card_number', 'chip_number',
        'creation_date', 'received_date', 'activation_date', 'expiration_date', 'deactivation_time',
        'deactivation_user', 'returned_date', 'pic', 'bank_pic', 'exported', 'confirm_user',
        'confirm_payment_collateral', 'confirm_payment_cost', 'confirm_admin', 'collateral_returned',
        'collateral_returned_date', 'order_id', 'confirm_payment_delivery', 'delivery_id', 'delivery_addr1',
        'delivery_addr2', 'delivery_addr3', 'delivery_addr4', 'pan_number', 'bank_description'
    );

    protected $updateableFields = array(
        'moddate', 'moduser', 'status_id', 'deactivation_time', 'deactivation_user', 'active', 'state_id',
        'returned_date', 'returned', 'bank_status_id', 'language_id', 'kind_id', 'bank_id', 'type_id',
        'school_id', 'person_name', 'person_name_first', 'person_name_last', 'person_birthday',
        'person_number', 'person_email', 'person_phone', 'isic_number', 'received_date', 'expiration_date',
        'pic', 'bank_pic', 'activation_date', 'order_id', 'chip_number', 'isic_number', 'exported', 'pan_number',
        'bank_description'
    );

    protected $searchableFields = array(
        'isic_number', 'person_number', 'state_id', 'kind_id', 'isic_number', 'order_id', 'exported', 'pan_number',
        'pic', 'bank_pic', 'received_date'
    );

    private $isicDbCardStatuses;
    private $isicDbUserStatuses;
    private $isicDbUserStatusTypes;
    private $isicDbPayments;

    /**
     * @var IsicDB_CardDataSync
     */
    private $isicDbCardDataSync;

    /**
     * @var IsicDB_CardDataSyncCCDB
     */
    private $isicDbCardDataSyncCCDB;

    public function __construct() {
        parent::__construct();
        $this->isicDbCardStatuses = IsicDB::factory('CardStatuses');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbPayments = IsicDB::factory('Payments');
        $this->isicDbCardDataSync = IsicDB::factory('CardDataSync');
        $this->isicDbCardDataSyncCCDB = IsicDB::factory('CardDataSyncCCDB');
    }

    public function getBaseQuery() {
          $t = $this->getTableQuoted();
          return "
            SELECT
                $t.*,
                `module_isic_card_state`.`name` AS `state_name`,
                `module_isic_card_language`.`name` AS `language_name`,
                `module_isic_card_status`.`name` AS `status_name`,
                `module_isic_card_status`.`action_type` AS `status_action_type`,
                IF(`module_isic_card_status_school`.`id`, `module_isic_card_status_school`.`should_return`, `module_isic_card_status`.`should_return`) AS `status_should_return`,
                `module_isic_card_kind`.`name` AS `kind_name`,
                `module_isic_bank`.`name` AS `bank_name`,
                `module_isic_card_type`.`name` AS `type_name`,
                `module_isic_card_type`.`ccdb_name` AS `card_type_ccdb`,
                `module_isic_card_type`.`should_return_in` AS `type_should_return_in`,
                `module_isic_card_type`.`collateral_free_days_until_expiration` AS `type_collateral_free_days_until_expiration`,
                `module_isic_school`.`name` AS `school_name`,
                IF(`module_isic_card_delivery`.`id`, `module_isic_card_delivery`.`name`, '') AS `card_delivery_name`
            FROM
                $t
            LEFT JOIN
                `module_isic_card_state` ON $t.`state_id` = `module_isic_card_state`.`id`
            LEFT JOIN
                `module_isic_card_language` ON $t.`language_id` = `module_isic_card_language`.`id`
            LEFT JOIN
                `module_isic_card_status` ON $t.`status_id` = `module_isic_card_status`.`id`
            LEFT JOIN
                `module_isic_card_status_school` ON
                    $t.`status_id` = `module_isic_card_status_school`.`status_id` AND
                    $t.`school_id` = `module_isic_card_status_school`.`school_id`
            LEFT JOIN
                `module_isic_card_kind` ON $t.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON $t.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON $t.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                `module_isic_school` ON $t.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_delivery` ON $t.`delivery_id` = `module_isic_card_delivery`.`id`
                AND (`module_isic_card_delivery`.`school_id` <> 0 OR `module_isic_card_delivery`.`send_notification` = 1)
                AND `module_isic_card_delivery`.`active` = 1
          ";
    }

    /**
     * @return the kind_union
     */
    public function getKindUnion() {
        return self::kind_union;
    }

    /**
     * @return the kind_regular
     */
    public function getKindRegular() {
        return self::kind_regular;
    }


    public function getIdByIsicNumber($isic_number) {
        $r = $this->findRecords(array('isic_number' => $isic_number), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0]['id'] : false;
    }

    public function getIdByIsicAndPanNumber($isic_number, $pan_number) {
        $r = $this->findRecords(array(
            'isic_number' => $isic_number,
            'pan_number' => $pan_number
        ), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0]['id'] : false;
    }

    public function getIdByIsicNumberReceivedDate($isic_number, $received_date) {
        $r = $this->findRecords(array('isic_number' => $isic_number, 'received_date' => $received_date), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0]['id'] : false;
    }

    /**
     * @return the state_deactivated
     */
    public function getStateDeactivated() {
        return self::state_deactivated;
    }

    /**
     * @return the state_activated
     */
    public function getStateActivated() {
        return self::state_activated;
    }

    /**
     * @return the state_distributed
     */
    public function getStateDistributed() {
        return self::state_distributed;
    }

    /**
     * @return the state_ordered
     */
    public function getStateOrdered() {
        return self::state_ordered;
    }

    public function insertRecord(array $data, $userId = 0) {
        $common = IsicCommon::getInstance();
        $userId = $common->getLogUserId($userId ? $userId : $this->userid);
        $data['creation_date'] = $this->db->now();
        $data['adddate'] = $this->db->now();
        $data['adduser'] = $userId;
        $data['moddate'] = $this->db->now();
        $data['moduser'] = $userId;

        $addedId = parent::insertRecord($data);
        $cardData = $this->getRecord($addedId);
        $common->saveCardChangeLog(1, $addedId, array(), $cardData, $userId);
        // schedule card data sync
        $this->isicDbCardDataSync->scheduleCard($cardData);
        $this->isicDbCardDataSyncCCDB->scheduleCard($cardData);

        // update card validity if there is an appropriate status for a user in a school
        /** @var IsicDB_CardValidities $validities */
        $validities = IsicDB::factory('CardValidities');
        $validities->insertOrUpdateRecordByCard($cardData);

        return $addedId;
    }

    public function updateRecord($id, array $data, $userId = 0) {
        $common = IsicCommon::getInstance();
        $userId = $common->getLogUserId($userId ? $userId : $this->userid);
        $rowOld = $this->getRecord($id);
        $data['moddate'] = $this->db->now();
        $data['moduser'] = $userId;
        parent::updateRecord($id, $data);
        $common->saveCardChangeLog(2, $id, $rowOld, $this->getRecord($id), $userId);
        // schedule card data sync
        $this->isicDbCardDataSync->scheduleCard($this->getRecord($id), $rowOld);
        $this->isicDbCardDataSyncCCDB->scheduleCard($this->getRecord($id), $rowOld);
    }

    public function distribute($id) {
        $data = array(
            'state_id' => self::state_distributed
        );
        $this->updateRecord($id, $data);
    }

    public function activate($id) {
        $cardRecord = $this->getRecord($id);
        $cardPayment = $this->isCollateralNeededAndBindedOrFree($cardRecord);
        if (!$cardPayment) {
            return false;
        }
        if (is_array($cardPayment)) {
            $this->isicDbPayments->updateRecord($cardPayment['id'],
                array(
                    'free' => 0,
                    'autoreturn' => 0,
                    'autoreturn_date' => IsicDate::EMPTY_DATE,
                )
            );
            if ($cardPayment['card_id'] != $id) {
                $newPayment = $cardPayment;
                $newPayment['prev_id'] = $cardPayment['id'];
                $newPayment['application_id'] = 0;
                $newPayment['card_id'] = $id;
                $newPayment['free'] = 0;
                $newPayment['autoreturn'] = 0;
                $newPayment['autoreturn_date'] = IsicDate::EMPTY_DATE;
                $this->isicDbPayments->insertRecord($newPayment);
            }
        }
        $data = array(
            'activation_date' => $this->db->now(),
            'active' => 1,
            'state_id' => self::state_activated,
            'deactivation_time' => IsicDate::EMPTY_DATE,
            'deactivation_user' => 0
        );
        $this->updateRecord($id, $data);
        $cardData = $this->getRecord($id);
        // update card validity if there is an appropriate status for a user in a school
        $validities = IsicDB::factory('CardValidities');
        try {
            $validities->insertOrUpdateRecordByCard($cardData);
        } catch(Exception $ex) {
            //
        }
        return true;
    }

    public function prolong($id, $statusId = null) {
        $data = array();
        if (isset($statusId)) {
            $data['status_id'] = $statusId;
        }
        $this->updateRecord($id, $data);
        $paymentData = $this->isicDbPayments->getCollateralPaymentByCard($this->getRecord($id));
        if ($paymentData) {
            $this->isicDbPayments->setFree($paymentData);
        }
    }

    public function replace($id, $statusId = null) {
        $data = array();
        if (isset($statusId)) {
            $data['status_id'] = $statusId;
        }
        $this->updateRecord($id, $data);
    }

    public function hasBoundCollateral(array $cardData) {
        return (bool)$this->isicDbPayments->getCollateralPaymentByCard($cardData);
    }

    private function isDeactivatedBeforeCollateralFreeDaysUntilExpiration(array $cardData) {
        if(!$this->isDeactivated($cardData)) {
            return false;  // return on a specific date is not expected if card is not deactivated
        }
        $collateralFreeStartDate = strtotime(
            '-' . $cardData['type_collateral_free_days_until_expiration'] . ' days',
            strtotime($cardData['expiration_date'])
        );
        $deactivationDate = strtotime(IsicDate::getAsDate($cardData['deactivation_time']));
        return $deactivationDate < $collateralFreeStartDate;
    }

    private function isReturnExpectedAtDate(array $cardData, $dateOrDateTime) {
        if(!$this->isDeactivated($cardData) || !$this->isReturnRequired($cardData)) {
            return false;
        }
        $shouldReturnBefore = strtotime(
            '+' . $cardData['type_should_return_in'] . ' days',
            strtotime(IsicDate::getAsDate($cardData['deactivation_time']))
        );
        return strtotime(IsicDate::getAsDate($dateOrDateTime)) <= $shouldReturnBefore;
    }

    public function deactivate($id, $statusId = null) {
        $common = IsicCommon::getInstance();
        $cardData = $this->getRecord($id);
        $cardWasActivated = $this->isActivated($cardData);
        $data = array(
            'deactivation_time' => $this->db->now(),
            'deactivation_user' => $common->getLogUserId($this->userid),
            'active' => 0,
            'state_id' => self::state_deactivated
        );
        if (isset($statusId)) {
            $data['status_id'] = $statusId;
        }
        $this->updateRecord($id, $data);
        $cardData = $this->getRecord($id);

        // check if card was active and was not expired before deactivation and has chip
        if ($cardWasActivated && $cardData['expiration_date'] > $this->db->now() && $this->hasChip($cardData)) {
            IsicMail::sendCardWithChipDeactivationNotification($cardData);
        }

        if ($cardWasActivated && $this->isCardReturnNotificationNeeded($cardData)) {
            IsicMail::sendCardDeactivationNotification($cardData);
            return;
        }

        $paymentData = $this->isicDbPayments->getCollateralPaymentByCard($cardData);
        if ($paymentData && !$this->isicDbPayments->isFree($paymentData)) {
            $this->isicDbPayments->setFree($paymentData, IsicDate::getAsDate($cardData['deactivation_time']));
        }
    }

    private function isCardReturnNotificationNeeded(array $cardData) {
        return $this->isReturnRequired($cardData)
            && $this->isDeactivatedBeforeCollateralFreeDaysUntilExpiration($cardData)
            && !$this->isReturned($cardData)
        ;
    }

    private function hasChip(array $cardData) {
        return $cardData['chip_number'] != '';
    }

    public function returned($id) {
        // Mark card as returned
        $data = array(
            'returned_date' => $this->db->now(),
            'returned' => 1,
        );
        $this->updateRecord($id, $data);
        // Deactivate card if it's still activated
        $cardData = $this->getRecord($id);
        if ($this->isActivated($cardData)) {
            $this->deactivate($id, $this->isicDbCardStatuses->getCardStatusReturnId($cardData['type_id']));
        }
        // Check if collateral should be set as free
        $cardData = $this->getRecord($id);
        if ($this->isReturnExpectedAtDate($cardData, $cardData['returned_date'])) {
            $paymentData = $this->isicDbPayments->getCollateralPaymentByCard($cardData);
            if ($paymentData) {
                $this->isicDbPayments->setFree($paymentData, $cardData['returned_date']);
            }
        }
    }

    public function isReturnRequired(array $cardData) {
        return (bool)$cardData['status_should_return'];
    }

    /**
     * Checks if user already has card that is activated
     *
     * @param string $person_number person number
     * @param int $type_id type ID
     * @return array
    */
    function getActivatedRecordByUserCardType($person_number = '', $type_id = 0) {
        if ($person_number && $type_id) {
            $r = $this->findRecords(
                array(
                    'kind_id' => self::kind_regular,
                    'person_number' => $person_number,
                    'type_id' => $type_id,
                    'state_id' => self::state_activated,
                ), 0, 1
            );
            return is_array($r) && count($r) == 1 ? $r[0] : false;

        }
        return false;
    }

    public function getRecordsByPersonNumber($personNumber) {
        return $this->findRecords(array('person_number' => $personNumber));
    }

    function getActivatedRecordsByPersonNumber($person_number = '') {
        return $this->findRecords(
            array(
                'person_number' => $person_number,
                'state_id' => self::state_activated,
            )
        );
    }

    function getDeactivatedRecordsByPersonNumber($person_number) {
        return $this->findRecords(array(
            'person_number' => $person_number,
            'state_id' => self::state_deactivated,
        ));
    }

    function findRecordsByStatusPersonNumber($statusId, $personNumber) {
        $userStatusTypes = IsicDB::factory('UserStatusTypes');
        $userStatusTypeData = $userStatusTypes->getRecord($statusId);
        if (!is_array($userStatusTypeData) || !$userStatusTypeData['card_types']) {
            return array();
        }
        $t = $this->getTableQuoted();
        $r = $this->db->query(
            $this->getBaseQuery() . "
            WHERE
                $t.`person_number` = ? AND
                $t.`type_id` IN (!@)
            ",
            $personNumber,
            IsicDB::getIdsAsArray($userStatusTypeData['card_types'])
        );
        $this->assertResult($r);
        return $r->fetch_all();
    }

    function findRecordsToOrder(array $cardIds) {
        $r = $this->db->query("
            SELECT
                `module_isic_card`.*
            FROM
                `module_isic_card`
            WHERE
                `module_isic_card`.`exported` = ? AND
                `module_isic_card`.`pic` <> ? AND
                `module_isic_card`.`confirm_admin` = 1 AND
                `module_isic_card`.`id` IN (!@)
            ",
            '0000-00-00 00:00:00', '', $cardIds
        );
        $this->assertResult($r);
        return $r->fetch_all();
    }

    function findRecordsToOrderWithoutIds($card_type, $school_id) {
        $t = $this->getTableQuoted();
        $r = $this->db->query("
            SELECT
                $t.*
            FROM
                $t
            WHERE
                ($t.`type_id` = ! OR ! = 0) AND
                ($t.`school_id` = ! OR ! = 0) AND
                $t.`confirm_admin` = 1 AND
                $t.`exported` = ? AND
                $t.`pic` <> ''
            ",
            $card_type,
            $card_type,
            $school_id,
            $school_id,
            '0000-00-00 00:00:00'
        );
        $this->assertResult($r);
        return $r->fetch_all();
    }

    public function deactivateRecordsWithMissingPrivileges(array $userData) {
        $userCards = $this->getRecordsByPersonNumber($userData['user_code']);
        foreach ($userCards as $card) {
            if ($this->isDeactivated($card)) {
                continue;  // should not deactivate already deactivated cards
            }
            $statusTypes = $this->isicDbUserStatusTypes->getRecordsByCardType($card['type_id']);
            foreach ($statusTypes as $statusType) {
                $userStatuses = $this->isicDbUserStatuses->getAllRecordsByStatusUser($statusType['id'], $userData['user']);
                if (count($userStatuses) > 0) {
                    continue 2;  // there is a valid status for this card, skipping
                }
            }
            $status = $this->isicDbCardStatuses->findRecordsByCardTypeActionType(
                $card['type_id'],
                $this->isicDbCardStatuses->getActionTypeUserStatusMissing()
            );
            $this->deactivate($card['id'], $status ? $status['id'] : null);
            if ($this->isOrdered($card) || $this->isDistributed($card)) {
                $this->returned($card['id']);
            }
        }
    }

    public function activateRecordsPreviouslyDeactivatedForMissingPrivileges(array $userData) {
        $userCards = $this->getDeactivatedRecordsByPersonNumber($userData['user_code']);
        foreach ($userCards as $card) {
            if ($card['returned'] || strtotime($card['expiration_date']) < strtotime($this->db->now())) {
                continue;  // must be still a valid card
            }
            if (!$card['status_id']) {
                continue;  // no deactivation status set
            }
            $status = $this->isicDbCardStatuses->getRecord($card['status_id']);
            if (!$status || $status['action_type'] != $this->isicDbCardStatuses->getActionTypeUserStatusMissing()) {
                continue;  // no valid status found
            }
            if (!$this->isCollateralNeededAndBindedOrFree($card)) {
                continue; // problem with collateral (needed but not binded or not free)
            }
            $statusTypes = $this->isicDbUserStatusTypes->getRecordsByCardType($card['type_id']);
            foreach ($statusTypes as $statusType) {
                $userStatuses = $this->isicDbUserStatuses->getAllRecordsByStatusUser($statusType['id'], $userData['user']);
                if (count($userStatuses) > 0) {
                    $this->activate($card['id']);
                    continue 2;  // go on with the next card
                }
            }
        }
    }

    public function isCollateralNeededAndBindedOrFree(array $cardData) {
        if ($cardData['confirm_payment_collateral']) {
            $payment = $this->isicDbPayments->getCollateralPaymentByCard($cardData);
            return $payment;
        }
        return true;
    }

    public function isExpired(array $cardData) {
        return IsicDate::isExpiredDate($cardData["expiration_date"]);
    }

    public function isAllowedKind($kindId) {
        return $kindId == $this->getKindRegular();
    }

    public function isStateOrdered($stateId) {
        return $stateId == $this->getStateOrdered();
    }

    public function isStateDistributed($stateId) {
        return $stateId == $this->getStateDistributed();
    }

    public function isStateActivated($stateId) {
        return $stateId == $this->getStateActivated();
    }

    public function isStateDeactivated($stateId) {
        return $stateId == $this->getStateDeactivated();
    }

    public function isStateActivatedOrDeactivated($stateId) {
        return $this->isStateActivated($stateId) || $this->isStateDeactivated($stateId);
    }

    public function isOrdered(array $cardData) {
        return $cardData['state_id'] == self::state_ordered;
    }

    public function isDistributed(array $cardData) {
        return $cardData['state_id'] == self::state_distributed;
    }

    public function isActivated(array $cardData) {
        return $this->isStateActivated($cardData['state_id']);
    }

    public function isDeactivated(array $cardData) {
        $result = $this->isStateDeactivated($cardData['state_id']);
        if ($result) {
            try {
               self::assert(IsicDate::isDefined($cardData['deactivation_time']));
            } catch (IsicDB_Exception $e) {
                //
            }
        }
        return $result;
    }

    public function hasReplacementDeactivationReason(array $cardData) {
        return $cardData['status_action_type'] == $this->isicDbCardStatuses->getActionTypeReplace();
    }

    public function hasProlongationDeactivationReason(array $cardData) {
        return $cardData['status_action_type'] == $this->isicDbCardStatuses->getActionTypeProlong();
    }

    public function hasExpirationDeactivationReason(array $cardData) {
        return $cardData['status_action_type'] == $this->isicDbCardStatuses->getActionTypeExpiration();
    }

    public function hasUserStatusMissingDeactivationReason(array $cardData) {
        return $cardData['status_action_type'] == $this->isicDbCardStatuses->getActionTypeUserStatusMissing();
    }

    public function isRegular(array $cardData) {
        return $cardData['kind_id'] == self::kind_regular;
    }

    public function isUnion(array $cardData) {
        return $cardData['kind_id'] == self::kind_union;
    }

    public function isReturned(array $cardData) {
        $result = (bool)$cardData['returned'];
        if ($result) {
            self::assert(IsicDate::isDefined($cardData['returned_date']));
        }
        return $result;
    }

    /**
     * Deactivating all the active cards with expiration date less than given date
     *
     * @param string $cur_date current date
     *
     * @return int number of cards de-activated
    */
    public function deactivateExpiredCards($cur_date = '') {
        if (!$cur_date) {
            $cur_date = date("Y-m-d");
        }
        $expiration_status = array();
        $card_count = 0;
        $t = $this->getTableQuoted();
        $r = &$this->db->query(
            $this->getBaseQuery() . "
            WHERE
                $t.`state_id` <> ! AND
                $t.`expiration_date` < ?
            ",
            self::state_deactivated,
            $cur_date
        );
        while ($cardData = $r->fetch_assoc()) {
            if (!array_key_exists($cardData["type_id"], $expiration_status)) {
                $status_id = $this->isicDbCardStatuses->getCardStatusExpirationId($cardData["type_id"]);
                $expiration_status[$cardData["type_id"]] = $status_id;
            }
            $paymentData = $this->isicDbPayments->getCollateralPaymentByCard($cardData);
            if ($paymentData && !$this->isicDbPayments->isFree($paymentData)) {
                try {
                    $this->isicDbPayments->setFree($paymentData);
                }
                catch (Exception $e) {}
            }
            $this->deactivate($cardData['id'], $expiration_status[$cardData["type_id"]]);
            $card_count++;
        }
        return $card_count;
    }

    public function setCollateralFreeForSoonExpiringCards() {
        $cardTypes = IsicDB::factory('CardTypes');
        $card_count = 0;
        $t = $this->getTableQuoted();
        foreach ($cardTypes->listRecords() as $cardType) {
            $expirationDate = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j') + $cardType['collateral_free_days_until_expiration'], date('Y')));
            $r = &$this->db->query(
                $this->getBaseQuery() . "
                WHERE
                    $t.`active` = 1 AND
                    $t.`kind_id` = ! AND
                    $t.`type_id` = ! AND
                    $t.`expiration_date` <= ?
                ",
                self::kind_regular,
                $cardType['id'],
                $expirationDate
            );
            while ($cardData = $r->fetch_assoc()) {
                $paymentData = $this->isicDbPayments->getCollateralPaymentByCard($cardData);
                if ($paymentData && !$this->isicDbPayments->isFree($paymentData)) {
                    try {
                        $this->isicDbPayments->setFree($paymentData);
                        $card_count++;
                    }
                    catch (Exception $e) {}
                }
            }
        }

        return $card_count;
    }

    public function canBeActivated(array $cardData) {
        return
            !IsicDate::isExpiredDate($cardData["expiration_date"])
            && !$this->isStateActivated($cardData["state_id"])
            && !$this->isReturned($cardData)
            && $this->isCollateralNeededAndBindedOrFree($cardData)
        ;
    }

    public function canBeDeactivated(array $cardData) {
        return $this->isStateActivated($cardData["state_id"]);
    }

    public function getRecordsForOrderByIds(array $cardIds) {
        $r = &$this->db->query("
            SELECT
                `module_isic_card`.*,
                `module_isic_card_type_school`.*,
                IF(`module_isic_school`.`id`, IF(`module_isic_school`.`card_name` <> '', `module_isic_school`.`card_name`, `module_isic_school`.`name`), '') AS school_name,
                IF(`module_isic_school`.`id`, `module_isic_school`.`ehis_code`, '') AS school_ehis_code,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address1`, '') AS school_address1,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address2`, '') AS school_address2,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address3`, '') AS school_address3,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address4`, '') AS school_address4,
                IF(`module_isic_school`.`id`, `module_isic_school`.`email`, '') AS school_email,
                IF(`module_isic_school`.`id`, `module_isic_school`.`phone`, '') AS school_phone,
                IF(`module_isic_school`.`id`, `module_isic_school`.`web`, '') AS school_web,
                IF(`module_isic_school`.`id`, `module_isic_school`.`joined`, 0) AS school_joined,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`prefix`, '') AS card_type_prefix,
                `module_isic_card`.`id` AS 'id'
            FROM
                `module_isic_card`
            LEFT JOIN
                `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                `module_isic_card_type_school` ON
                      `module_isic_card`.`type_id` = `module_isic_card_type_school`.`type_id` AND
                      `module_isic_card`.`school_id` = `module_isic_card_type_school`.`school_id`
            WHERE
                `module_isic_card`.`id` IN (!@)
            ", $cardIds
        );
        $this->assertResult($r);
        return $r->fetch_all();
    }

    /**
     * Deactivates all other cards with same type and kind
     *
     * @param array $data card data array
     * @return int count of card deactivated
    */
    public function deActivateOtherCards($data) {
        if (!is_array($data)) {
            return 0;
        }
        $deactivate_count = 0;
        $t_activation_id = $this->isicDbCardStatuses->getCardStatusActivationId($data["type_id"]);
        $r = &$this->db->query("
            SELECT
                `c`.`id`
            FROM
                ?f AS `c`
            WHERE
                `c`.`person_number` = ? AND
                `c`.`id` <> ! AND
                `c`.`type_id` = ! AND
                `c`.`state_id` = ! AND
                `c`.`kind_id` = ! AND
                `c`.`active` = !
            ",
            $this->getTable(),
            $data["person_number"],
            $data["id"],
            $data["type_id"],
            $this->getStateActivated(),
            $this->getKindRegular(),
            1
        );
        while ($t_data = $r->fetch_assoc()) {
            $this->deactivate($t_data["id"], $t_activation_id);
            $deactivate_count++;
        }
        return $deactivate_count;
    }

    public function activateHomeOrderedCards($date) {
        $sql = '
            SELECT
                *
            FROM
                `module_isic_card`
            WHERE
                `module_isic_card`.`state_id` = ! AND
                `module_isic_card`.`delivery_id` = ! AND
                `module_isic_card`.`exported` <> ? AND
                `module_isic_card`.`exported` < ?
        ';
        $r = &$this->db->query($sql, $this->getStateOrdered(), 1, '0000-00-00', $date);
        $this->assertResult($r);
        $count = 0;
        while ($data = $r->fetch_assoc()) {
            echo $data['isic_number'] . "\n";
            $this->activate($data['id']);
            $this->deActivateOtherCards($data);
            $count++;
        }
        return $count;
    }

    public function saveChipAndPanNumber($isicNumber, $chipNumber, $panNumber, $orderId = 0) {
        $data = $this->findRecord(
            array(
                'isic_number' => $isicNumber,
                'pan_number' => $panNumber,
                'order_id' => $orderId
            )
        );
        if (!$data) {
            $data = $this->findRecord(
                array(
                    'isic_number' => $isicNumber,
                    'order_id' => $orderId
                ),
                'id',
                '',
                'DESC'
            );
        }
        if (!$data) {
            return "<b>Chip/Pan numbers not assigned.</b> Couldn't find the card {$isicNumber}.";
        }
        
        if ($data['chip_number']) {
            return "chip already assigned: " . $data['chip_number'];
        }
        
        $update = array(
            // 'pan_number' => $panNumber,
            'chip_number' => $chipNumber
        );

        $this->updateRecord($data['id'], $update);
        return "Chip: {$chipNumber}; Pan: {$panNumber} assigned to {$isicNumber} (id: {$data['id']}) (order: {$orderId}).";
    }

    /**
     * Adds chip number to a card record
     *
     * @param string $chip_number chip number
     * @param string $isic_number card serial number
     * @param int $order_id order id
     * @return string status what was done (chip number assigned / chip number already assigned)
     */
    public function saveChipNumber($chip_number, $isic_number, $order_id = 0) {
        return $this->saveFieldValue('chip', $chip_number, $isic_number, $order_id);
    }

    /**
     * Adds PAN number to a card record
     *
     * @param string $pan_number chip number
     * @param string $isic_number card serial number
     * @param int $order_id order id
     * @return string status what was done (PAN number assigned / PAN number already assigned)
     */
    public function savePanNumber($pan_number, $isic_number, $order_id = 0) {
        return $this->saveFieldValue('pan', $pan_number, $isic_number, $order_id);
    }

    private function saveFieldValue($fieldName, $fieldValue, $isicNumber, $orderId = 0) {
        $data = $this->findRecord(array('isic_number' => $isicNumber, 'order_id' => $orderId));

        if (!$data) {
            return "<b>{$fieldName} number not assigned.</b> Couldn't find the card.";
        }

        if ($data[$fieldName . "_number"] === $fieldValue) {
            return "{$fieldName} number already assigned.";
        }

        $this->updateRecord($data['id'], array($fieldName . '_number' => $fieldValue));
        return "{$fieldName} number assigned.";
    }

    public function findAllActiveRecordsWithPanWithoutDataSync() {
        $sql = '
            SELECT
                c.id
            FROM
                module_user_users AS u,
                module_isic_card AS c
                LEFT JOIN
                    module_isic_card_data_sync AS ds
                ON
                    c.id = ds.record_id AND
                    ds.record_type = ?
            WHERE
                u.user_code = c.person_number AND
                u.data_sync_allowed = 1 AND
                c.active = 1 AND
                c.pan_number <> ? and
                ds.id IS NULL
            GROUP BY
                c.id
        ';

        $cards = array();
        /**
         * @var DatabaseResult $res
         */
        $res = $this->db->query($sql, $this->isicDbCardDataSync->getRecordTypeCard(), '');
        while ($data = $res->fetch_assoc()) {
            $cards[] = $data['id'];
        }
        return $cards;
    }

    public function findCompoundCardsWithoutChipCreatedAt($exportDate, $bankId = 1) {
        $sql = '
            SELECT
                `c`.`isic_number`,
                `t`.`name` AS `card_type_name`
            FROM
                `module_isic_card` AS `c`,
                `module_isic_card_type` AS `t`
            WHERE
                `c`.`exported` >= ? AND
                `c`.`exported` <= ? AND
                (`c`.`chip_number` IS NULL OR `c`.`chip_number` = ?) AND
                `c`.`kind_id` = ! AND
                `c`.`bank_id` = ! AND
                `c`.`type_id` = `t`.`id` AND
                `t`.`chip` = 1
        ';

        /**
         * @var DatabaseResult $res
         */
        $res = $this->db->query($sql, $exportDate, $exportDate . ' 23:59:59', '', self::kind_union, $bankId);
       // echo $this->db->show_query();
        return $res->fetch_all();
    }

    public function getBankCardsWithoutPictures($bankId = 1) {
        return $this->db->fetch_all('
            SELECT
                `module_isic_card`.`id`,
                `module_isic_card`.`bank_pic`,
                `module_isic_bank_pic`.`id` AS `bank_pic_id`
            FROM
                `module_isic_card`,
                `module_isic_bank_pic`
            WHERE
                `module_isic_card`.`bank_id` = ! AND
                `module_isic_card`.`pic` = ? AND
                `module_isic_card`.`bank_pic` <> ? AND
                `module_isic_card`.`bank_pic` = `module_isic_bank_pic`.`pic` AND
                `module_isic_card`.`bank_id` = `module_isic_bank_pic`.`bank_id`
            ', $bankId, "", ""
        );
    }

    public function copyAndAssignPicture($id, $srcFile, $userId = 0) {
        $fileName = "/" . $GLOBALS["directory"]["upload"] . "/isic/ISIC" . str_pad($id, 10, '0', STR_PAD_LEFT) . ".jpg";
        if (copy(SITE_PATH . $srcFile, SITE_PATH . $fileName)) {
            @copy(SITE_PATH . str_replace(".jpg", "_thumb.jpg", $srcFile), SITE_PATH . str_replace(".jpg", "_thumb.jpg", $fileName));
            $this->updateRecord($id, array('pic' => $fileName), $userId);
        }
    }

    public function getBankCardsReceivedInBetweenDates($bankId, $begDate, $endDate) {
        return $this->db->fetch_all('
            SELECT
                `c`.`isic_number`,
                `c`.`person_name_first`,
                `c`.`person_name_last`,
                `c`.`person_number`,
                `c`.`person_birthday`,
                `s`.`name` AS `school_name`,
                `c`.`adddate`,
                `c`.`received_date`,
                `t`.`name` AS `type_name`,
                `bs`.`name` AS `bank_status_name`,
                `c`.`active`,
                `c`.`expiration_date`,
                `c`.`bank_description`
            FROM
                `module_isic_card` AS `c`,
                `module_isic_school` as `s`,
                `module_isic_card_type` as `t`,
                `module_isic_bank_status` as `bs`
            WHERE
              `c`.`school_id` = `s`.`id` and
              `c`.`type_id` = `t`.`id` and
              `c`.`bank_status_id` = `bs`.`id` and
              `c`.`kind_id` = ! and
              `c`.`bank_id` = ! and
              `c`.`received_date` >= ? and
              `c`.`received_date` <= ?
            ', self::kind_union, $bankId, $begDate, $endDate
        );
    }
}
