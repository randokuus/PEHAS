<?php

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicCrypto.php");

class IsicDB_Applications extends IsicDB {
    /**
     * States for applications
     */
    const state_not_done = 1;
    const state_user_confirm = 2;
    const state_parent_confirm = 3;
    const state_status_check = 4;
    const state_admin_confirm = 5;
    const state_processed = 6;
    const state_rejected = 7;

    /**
     * Default language for new isic application
     */
    const language_default = 3;

    /**
     * Default kind type for new isic application
     */
    const kind_default = 1;

    /**
     * Default bank for new isic application
     */
    const bank_default = 0;


    protected $table = 'module_isic_application';
    protected $primary = 'id';

    protected $insertableFields = array(
        'application_type_id', 'prev_card_id', 'adddate', 'adduser', 'moddate', 'moduser', 'state_id',
        'language_id', 'kind_id', 'bank_id', 'type_id', 'school_id', 'person_name_first', 'person_name_last',
        'person_birthday', 'person_number',
        'person_email', 'person_phone', 'person_position', 'person_class', 'person_stru_unit', 'person_stru_unit2',
        'person_bankaccount', 'person_bankaccount_name', 'person_newsletter', 'pic', 'confirm_payment_collateral',
        'confirm_payment_cost', 'user_step', 'confirm_payment_delivery', 'delivery_id', 'delivery_addr1',
        'delivery_addr2', 'delivery_addr3', 'delivery_addr4', 'order_for_others', 'parent_user_id',

    );

    protected $updateableFields = array(
        'application_type_id', 'prev_card_id', 'moddate', 'moduser', 'state_id', 'pic', 'confirm_user', 'user_step',
        'school_id', 'type_id', 'person_name_first', 'person_name_last', 'person_birthday', 'person_number',
        'person_email', 'person_phone',
        'person_position', 'person_class', 'person_stru_unit', 'person_stru_unit2', 'person_bankaccount',
        'person_bankaccount_name', 'person_newsletter', 'agree_user', 'user_request_date', 'will_return_card',
        'confirm_admin', 'reject_reason_id', 'reject_reason_text', 'date_payment_collateral','date_payment_cost',
        'confirm_payment_collateral', 'collateral_sum', 'confirm_payment_cost', 'cost_sum', 'currency', 'delivery_id',
        'delivery_addr1', 'delivery_addr2', 'delivery_addr3', 'delivery_addr4', 'confirm_payment_delivery',
        'delivery_sum', 'compensation_sum', 'payment_started', 'order_for_others', 'parent_user_id',
        'expiration_date', 'card_id', 'compensation_sum_delivery'
    );

    protected $searchableFields = array(
        'person_number', 'person_name_first', 'person_name_last',
    );

    /**
     * Returns a record
     *
     * @param int|string $id record id
     * @return array record data
    */
    public function getRecord($id) {
        $r = &$this->db->query("
            SELECT
                `a`.*,
                `module_isic_application_type`.`name` AS appl_type_name,
                `module_isic_card_language`.`name` AS language_name,
                `module_isic_application_state`.`name` as state_name,
                `module_isic_card_kind`.`name` as kind_name,
                `module_isic_bank`.`name` AS bank_name,
                `module_isic_card_type`.`name` as type_name,
                `module_isic_card_type`.`tryb_export_name_split`,
                `module_isic_school`.`name` AS school_name,
                `module_isic_card_delivery`.`name` AS delivery_name
            FROM
                ?f AS `a`
            LEFT JOIN
                `module_isic_application_type` ON `a`.`application_type_id` = `module_isic_application_type`.`id`
            LEFT JOIN
                `module_isic_card_language` ON `a`.`language_id` = `module_isic_card_language`.`id`
            LEFT JOIN
                `module_isic_application_state` ON `a`.`state_id` = `module_isic_application_state`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `a`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `a`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `a`.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                `module_isic_school` ON `a`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_delivery` ON `a`.`delivery_id` = `module_isic_card_delivery`.`id`
            WHERE
                `a`.?f = ?",
            $this->table,
            $this->primary,
            $id
        );
        self::assertResult($r);
        return $r->fetch_assoc();
    }

    public function getRejectReasonTitle(array $applData) {
        $r = &$this->db->query("
            SELECT
                `module_isic_application_reject_reason`.`name`
            FROM
                `module_isic_application_reject_reason`
            WHERE
                `module_isic_application_reject_reason`.`id` = !
            LIMIT 1",
            (int)$applData["reject_reason_id"]
        );
        $this->assertResult($r);
        if ($data = $r->fetch_assoc()) {
            return stripslashes($data["name"]);
        }
        return '';
    }

    /**
     * @return the state_rejected
     */
    public function getStateRejected() {
        return self::state_rejected;
    }

    /**
     * @return the state_processed
     */
    public function getStateProcessed() {
        return self::state_processed;
    }

    /**
     * @return the state_admin_confirm
     */
    public function getStateAdminConfirm() {
        return self::state_admin_confirm;
    }

    /**
     * @return the state_status_check
     */
    public function getStateStatusCheck() {
        return self::state_status_check;
    }

    /**
     * @return the state_parent_confirm
     */
    public function getStateParentConfirm() {
        return self::state_parent_confirm;
    }

    /**
     * @return the state_user_confirm
     */
    public function getStateUserConfirm() {
        return self::state_user_confirm;
    }

    /**
     * @return the state_not_done
     */
    public function getStateNotDone() {
        return self::state_not_done;
    }

    public function isConfirmable(array $applData) {
        if ($applData["state_id"] == self::state_admin_confirm) {
            return false;
        }
        $isicPayment = new IsicPayment();
        $costData = $isicPayment->getCardCostCollDeliveryData($applData);
        $res = $isicPayment->isApplicationPaymentComplete($applData, $costData);
        return $res;
    }

    public function isConfirmableUser(array $applData)
    {
        return $applData["state_id"] == self::state_admin_confirm;
    }

    public function insertRecord($data, $userId = 0) {
        $common = IsicCommon::getInstance();
        $userId = $common->getLogUserId($userId ? $userId : $this->userid);
        $data['adddate'] = $this->db->now();
        $data['adduser'] = $userId;
        $data['moddate'] = $this->db->now();
        $data['moduser'] = $userId;
        $data['state_id'] = self::state_not_done;

        if (!isset($data['pic'])) {
            $data['pic'] = '';
        }
        if (!isset($data['language_id'])) {
            $data['language_id'] = self::language_default;
        }

        if (!isset($data['kind_id'])) {
            $data['kind_id'] = self::kind_default;
        }

        if (!isset($data['bank_id'])) {
            $data['bank_id'] = self::bank_default;
        }
        if (!isset($data['person_stru_unit'])) {
            $data['person_stru_unit'] = '';
        }
        if (!isset($data['person_stru_unit2'])) {
            $data['person_stru_unit2'] = '';
        }
        if (!isset($data['person_bankaccount'])) {
            $data['person_bankaccount'] = '';
        }
        if (!isset($data['person_bankaccount_name'])) {
            $data['person_bankaccount_name'] = '';
        }

        if (!isset($data['person_birthday'])) {
            $data['person_birthday'] = IsicDate::calcBirthdayFromNumber($data['person_number']);
        }

        $addedId = parent::insertRecord($data);
        $common->saveApplicationChangeLog(1, $addedId, array(), $this->getRecord($addedId), $userId);
        return $addedId;
    }

    public function updateRecord($id, $data, $userId = 0) {
        $common = IsicCommon::getInstance();
        $userId = $common->getLogUserId($userId ? $userId : $this->userid);
        $oldApplication = $this->getRecord($id);
        $data['moddate'] = $this->db->now();
        $data['moduser'] = $userId;
        parent::updateRecord($id, $data);
        $newApplication = $this->getRecord($id);
        $common->saveApplicationChangeLog(2, $id, $oldApplication, $newApplication, $userId);
    }

    public function deleteRecord($id, $userId = 0) {
        $common = IsicCommon::getInstance();
        $common->saveApplicationChangeLog(3, $id, $this->getRecord($id), array(), $userId);
        parent::deleteRecord($id);
    }

    public function getURI(array $applData, $userType = 0) {
        $generalUrl = IsicCommon::getInstance()->getGeneralUrlByTemplate(
            IsicTemplate::getModuleTemplateId('content_isic_application'),
            $userType
        );
        return SITE_URL . $generalUrl . "&appl_id=" . $applData[$this->getPrimaryKey()];
    }

    public function getURIUser($applData)
    {
        return SITE_URL . '/applconfirm.php?id=' .
            urlencode(IsicCrypto::encrypt(
                $applData['id'],
                ISIC_CRYPTO_KEY,
                true
            ));
    }

    public function sendConfirmNotificationToAdmin(array $applData) {
        if (!$this->isConfirmable($applData)) {
            return;
        }

        $isicDbSchools = IsicDB::factory('Schools');
        /** @var IsicDB_Users $isicDbUsers */
        $isicDbUsers = IsicDB::factory('Users');
        /** @var IsicDB_UserStatusTypes $isicDbUserStatusTypes */
        $isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');

        $schoolData = $isicDbSchools->getRecord($applData['school_id']);
        $statusIdList = $isicDbUserStatusTypes->getIdListByCardType($applData['type_id']);
        $userList = $isicDbUsers->findAdminRecordsBySchoolStatus($schoolData, $statusIdList ? $statusIdList[0] : null);
        foreach ($userList as $userData) {
            if (!$isicDbUsers->hasOrderedApplicationConfirmationNotifications($userData)) {
                continue;
            }
            $shouldNotify = !$isicDbUsers->isUserSuperAdmin($userData) ||
                ($isicDbUsers->isUserSuperAdmin($userData) && !$isicDbSchools->isJoined($schoolData));
            if ($shouldNotify) {
                IsicMail::sendAdminConfirmationPendingNotification($userData, $applData);
            }
        }
    }

    public function sendConfirmNotificationToUser($applId) {
        $applData = $this->getRecord($applId);
        if (!$applData || !$this->isConfirmableUser($applData)) {
            return;
        }
        IsicMail::sendUserConfirmationPendingNotification($applData);
    }

    public function deleteUnfinishedApplications($date) {
        $sql = '
            SELECT
                `id`
            FROM
                `module_isic_application`
            WHERE
                `module_isic_application`.`state_id` = ! AND
                `module_isic_application`.`moddate` < ? AND
                `module_isic_application`.`payment_started` = 0 AND
                `module_isic_application`.`confirm_payment_cost` = 0 AND
                `module_isic_application`.`confirm_payment_collateral` = 0 AND
                `module_isic_application`.`confirm_payment_delivery` = 0
        ';
        $r = &$this->db->query($sql, $this->getStateNotDone(), $date);
        $this->assertResult($r);
        $count = 0;
        while ($data = $r->fetch_assoc()) {
            $this->deleteRecord($data['id']);
            $count++;
        }
        return $count;

    }

    public function getRecordsWithCampaignCode($begTime, $endTime) {
        $sql = '
            SELECT
                `module_isic_application`.`person_name_first`,
                `module_isic_application`.`person_name_last`,
                `module_isic_application`.`person_number`,
                `module_isic_school`.`name` AS school_name,
                `module_isic_card_type`.`name` as type_name,
                `module_isic_application`.`campaign_code`,
                `module_isic_application`.`moddate`
            FROM
                `module_isic_application`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_application`.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id`
            WHERE
                `module_isic_application`.`state_id` > ! AND
                `module_isic_application`.`moddate` >= ? AND
                `module_isic_application`.`moddate` <= ? AND
                `module_isic_application`.`campaign_code` <> ?
        ';
        $res = $this->db->query($sql, $this->getStateNotDone(), $begTime, $endTime . ' 23:59:59', '');
        $this->assertResult($res);
        return $res;
    }
}
