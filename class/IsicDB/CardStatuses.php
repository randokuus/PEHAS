<?php

class IsicDB_CardStatuses extends IsicDB {

    const action_type_info = 0;
    const action_type_replace = 1;
    const action_type_prolong = 2;
    const action_type_expiration = 3;
    const action_type_activation = 4;
    const action_type_user_status_missing = 5;
    const action_type_return = 6;


    protected $table = 'module_isic_card_status';

    protected $insertableFields = array(
        'card_type', 'action_type', 'name', 'payment_required', 'payment_sum', 'should_return'
    );

    protected $updateableFields = array(
        'card_type', 'action_type', 'name', 'payment_required', 'payment_sum', 'should_return'
    );

    protected $searchableFields = array(
        'card_type', 'action_type'
    );

    public function findRecordsByCardTypeActionType($cardType, $actionType) {
        $r = $this->findRecords(array('card_type' => $cardType, 'action_type' => $actionType), 0, 1);
        return count($r) == 1 ? $r[0] : 0;
    }

    public function getActionTypeInfo() {
        return self::action_type_info;
    }

    public function getActionTypeReplace() {
        return self::action_type_replace;
    }

    public function getActionTypeProlong() {
        return self::action_type_prolong;
    }

    public function getActionTypeExpiration() {
        return self::action_type_expiration;
    }

    public function getActionTypeActivation() {
        return self::action_type_activation;
    }

    public function getActionTypeUserStatusMissing() {
        return self::action_type_user_status_missing;
    }

    public function getActionTypeReturn() {
        return self::action_type_return;
    }

    private function getCardStatusIdByCardTypeActionType($cardType = 0, $actionType = 0) {
        $statusRecord = $this->findRecordsByCardTypeActionType($cardType, $actionType);
        return $statusRecord ? $statusRecord['id'] : 0;
    }

    public function getCardStatusExpirationId($cardType = 0) {
        return $this->getCardStatusIdByCardTypeActionType($cardType, self::action_type_expiration);
    }

    public function getCardStatusActivationId($cardType = 0) {
        return $this->getCardStatusIdByCardTypeActionType($cardType, self::action_type_activation);
    }

    public function getCardStatusProlongId($cardType = 0) {
        return $this->getCardStatusIdByCardTypeActionType($cardType, self::action_type_prolong);
    }

    public function getCardStatusReturnId($cardType = 0) {
        return $this->getCardStatusIdByCardTypeActionType($cardType, self::action_type_return);
    }
    
    public function getCardStatusUserStatusMissingId($cardType = 0) {
        return $this->getCardStatusIdByCardTypeActionType($cardType, self::action_type_user_status_missing);
    }
}
