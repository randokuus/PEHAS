<?php

class IsicDB_Payments extends IsicDB {
    /**
     * Types of payments
     */
    const TYPE_COLLATERAL = 1;
    const TYPE_COST = 2;
    const TYPE_DELIVERY = 3;
    const TYPE_COMPENSATION = 4;
    const TYPE_COMPENSATION_DELIVERY = 5;

    /**
     * Payment methods
     */
    const METHOD_INVOICE = 1;
    const METHOD_EXTERNAL = 2;
    const METHOD_BANK = 3;

    protected $table = 'module_isic_payment';
    protected $primary = 'id';

    protected $insertableFields = array(
        'prev_id', 'adddate', 'application_id', 'card_id', 'active', 'free', 'person_number', 'type_id', 'deposit_id',
        'payment_type', 'payment_sum', 'should_share', 'payment_returned', 'should_share_date', 'payment_returned_date',
        'currency', 'autoreturn', 'autoreturn_date', 'payment_method', 'bank_id'
    );

    protected $updateableFields = array(
        'prev_id', 'application_id', 'card_id', 'active', 'free', 'person_number', 'type_id', 'deposit_id',
        'payment_type', 'payment_sum', 'should_share', 'payment_returned', 'should_share_date', 'payment_returned_date',
        'currency', 'autoreturn', 'autoreturn_date', 'payment_method', 'bank_id'
    );

    protected $searchableFields = array(
        'prev_id', 'application_id', 'card_id', 'active', 'free', 'person_number', 'type_id', 'deposit_id',
        'payment_type', 'payment_sum', 'should_share', 'payment_returned', 'autoreturn', 'autoreturn_date'
    );

    private $isicDbGlobalSettings;
    private $isicDbCurrency;

    public function __construct() {
        parent::__construct();
        $this->isicDbGlobalSettings = IsicDB::factory('GlobalSettings');
        $this->isicDbCurrency = IsicDB::factory('Currency');
    }

    public function insertRecord(array $data) {
        $data['adddate'] = $this->db->now();
        return parent::insertRecord($data);
    }

    public function getFreeCollateralRecordByUserSum($person_number, $payment_sum, $payment_currency) {
        $r = $this->findRecords(
            array(
                'person_number' => $person_number,
                'payment_type' => self::TYPE_COLLATERAL,
                'free' => '1'
            )
        );
        $paymentData1 = array(
            'payment_sum' => $payment_sum,
            'currency' => $payment_currency
        );
        foreach ($r as $paymentData2) {
            if ($this->isCollateralSumEqual($paymentData1, $paymentData2)) {
                return $paymentData2;
            }
        }
        return false;
    }

    public function isReturned(array $paymentData) {
        return (bool)$paymentData["payment_returned"];
    }

    public function isExpectedToBeShared(array $paymentData) {
        return (bool)$paymentData["should_share"];
    }

    public function isFree(array $paymentData) {
        return (bool)$paymentData["free"];
    }

    public function isCollateralSumEqual($paymentData1, $paymentData2) {
        return (
            $this->isicDbCurrency->getSumInDefaultCurrency($paymentData1['payment_sum'], $paymentData1['currency']) ==
            $this->isicDbCurrency->getSumInDefaultCurrency($paymentData2['payment_sum'], $paymentData2['currency'])
        );
    }

    public function getCollateralPaymentByCard(array $cardData) {
        $cardPayment = $this->getPaymentByCard($cardData['id'], self::TYPE_COLLATERAL);
        // first check if payment for this card exists and if that payment is free or last in chain (no decendants)
        if ($cardPayment) {
            if ($cardPayment['free'] ||
                !$cardPayment['free'] && $this->isLastInChain($cardPayment)) {
                return $cardPayment;
            }
            // look for free payment which has needed sum
            return $this->getFreeCollateralRecordByUserSum($cardData['person_number'], $cardPayment['payment_sum'], $cardPayment['currency']);
        }
        return false;
    }

    function getPaymentByApplication($id, $payment_type) {
        return $this->getPayment($payment_type, 1, $id);
    }

    function getPaymentByCard($id, $payment_type) {
        return $this->getPayment($payment_type, 2, $id);
    }

    function getPayment($payment_type = 1, $type = 1, $id = 0) {
        $sql = '
            SELECT
                *
            FROM
                `module_isic_payment`
            WHERE
                `payment_returned` = 0 AND
                `should_share` = 0 AND
                `payment_type` = ! AND
        ';
        if ($type == 1) { // by application
            $sql .= ' `application_id` = !';
        } else if ($type == 2) { // by card
            $sql .= ' `card_id` = !';
        }
        $sql .= ' ORDER BY `adddate` DESC LIMIT 1';
        $res =& $this->db->query($sql, $payment_type, $id);
        return $res->fetch_assoc();
    }

    public function isLastInChain(array $paymentData) {
        $r = $this->findRecord(array('prev_id' => $paymentData['id']));
        return !is_array($r);
    }

    public function setFree(array $paymentData, $autoReturnBaseDate = null) {
        self::assert(!$this->isExpectedToBeShared($paymentData) && !$this->isReturned($paymentData));
        $data = array('free' => 1);
        if (IsicDate::isDefined($autoReturnBaseDate)) {
            $collateralReturnDays = (int)$this->isicDbGlobalSettings->getRecord('collateral_return_days');
            $autoReturnTime = IsicDate::getTimeStampFormatted(strtotime("+$collateralReturnDays days", strtotime($autoReturnBaseDate)));
            $data['autoreturn'] = 1;
            $data['autoreturn_date'] = IsicDate::getAsDate($autoReturnTime);
        }
        $this->updateRecord($paymentData[$this->getPrimaryKey()], $data);
    }

    public function setNotFree(array $paymentData, $keepAutoreturn = false) {
        self::assert(!$this->isExpectedToBeShared($paymentData) && !$this->isReturned($paymentData));
        $data = array(
            'free' => 0,
        );
        if (!$keepAutoreturn) {
            $data['autoreturn'] = 0;
            $data['autoreturn_date'] = IsicDate::EMPTY_DATE;
        }
        $this->updateRecord($paymentData[$this->getPrimaryKey()], $data);
    }

    public function setExpectedToBeShared(array $paymentData) {
        self::assert(!$this->isExpectedToBeShared($paymentData) && !$this->isReturned($paymentData));
        $data = array(
            'should_share' => 1,
            'should_share_date' => $this->db->now(),
            'free' => 0,
            'autoreturn' => 0,
            'autoreturn_date' => IsicDate::EMPTY_DATE
        );
        $this->updateRecord($paymentData[$this->getPrimaryKey()], $data);
    }

    public function getPaymentSumInCurrency($payments, $currency) {
        if (is_array($payments) && count($payments) > 0) {
            return $this->isicDbCurrency->getSumInGivenCurrency($payments['payment_sum'], $payments['currency'], $currency);
        }
        return 0;
    }

    public function getCardPaymentSumInCurrency($cardId, $paymentType, $currency) {
        return $this->getPaymentSumInCurrency($this->getPaymentByCard($cardId, $paymentType), $currency);
    }

    public function getApplicationPaymentSumInCurrency($applId, $paymentType, $currency) {
        return $this->getPaymentSumInCurrency($this->getPaymentByApplication($applId, $paymentType), $currency);
    }

    public function setReturnablePaymentsNotFree() {
        $r = $this->db->query(
            $this->getBaseQuery() . "
                WHERE
                    `autoreturn` = 1 AND
                     CURDATE() >= `autoreturn_date` AND
                    `free` = 1 AND
                    `should_share` = 0 AND
                    `payment_returned` = 0
                "
        );
        $total = 0;
        while ($paymentData = $r->fetch_assoc()) {
            try {
               $this->setNotFree($paymentData, true);
               $total++;
            } catch (Exception $e) {}
        }
        return $total;
    }

    public function sharePaymentsOfDeactivatedCards() {
        $cards = IsicDB::factory('Cards');
        $p = $this->getTableQuoted();
        $c = $cards->getTableQuoted();
        $r = $this->db->query(
            $this->getBaseQuery() . "
            LEFT JOIN
                $c ON $p.`card_id` = $c.`id`
            LEFT JOIN
                `module_isic_card_status` ON $c.`status_id` = `module_isic_card_status`.`id`
            LEFT JOIN
                `module_isic_card_status_school` ON
                    $c.`status_id` = `module_isic_card_status_school`.`status_id` AND
                    $c.`school_id` = `module_isic_card_status_school`.`school_id`
            LEFT JOIN
                `module_isic_card_type` ON $c.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                $p AS `next_payment` ON `next_payment`.`prev_id` = $p.`id`
            WHERE
                $c.`state_id` = ! AND
                $c.`kind_id` = ! AND
                $p.`payment_type` = ! AND
                $p.`should_share` = 0 AND
                $p.`payment_returned` = 0 AND
                DATE($c.`deactivation_time`) < DATE_SUB($c.`expiration_date`, INTERVAL `module_isic_card_type`.`collateral_free_days_until_expiration` DAY) AND
                IF(`module_isic_card_status_school`.`id`, `module_isic_card_status_school`.`should_return`, `module_isic_card_status`.`should_return`) = 1 AND
                IF($c.`returned`, $c.`returned_date`, CURDATE()) > DATE_ADD(DATE($c.`deactivation_time`), INTERVAL `module_isic_card_type`.`should_return_in` DAY) AND
                `next_payment`.`id` IS NULL
            ",
            $cards->getStateDeactivated(),
            $cards->getKindRegular(),
            self::TYPE_COLLATERAL
        );
        $this->assertResult($r);
        $count = 0;
        while ($paymentData = $r->fetch_assoc()) {
            try {
                $this->setExpectedToBeShared($paymentData);
                $count++;
            } catch (Exception $e) {}
        }
        return $count;
    }

    public function getTypeCollateral() {
        return self::TYPE_COLLATERAL;
    }

    public function getTypeCost() {
        return self::TYPE_COST;
    }

    public function getTypeDelivery() {
        return self::TYPE_DELIVERY;
    }

    public function getTypeCompensation() {
        return self::TYPE_COMPENSATION;
    }

    public function getTypeCompensationDelivery() {
        return self::TYPE_COMPENSATION_DELIVERY;
    }

    public function getMethodInvoice() {
        return self::METHOD_INVOICE;
    }

    public function getMethodExternal() {
        return self::METHOD_EXTERNAL;
    }

    public function getMethodBank() {
        return self::METHOD_BANK;
    }
}

