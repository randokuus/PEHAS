<?php
class IsicDB_CardTypes extends IsicDB {

    protected $table = 'module_isic_card_type';

    protected $cardTypeData = array();

    protected $insertableFields = array(
        'name'
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'name'
    );

    protected $cardTypesNeedingExternalCheck = false;
    protected $cardTypesNeedingEHLCheck = false;
    protected $cardTypeBindings = false;
    protected $cardTypeNames = false;

    public function getAllowedIdListForAdd() {
        return $this->getAllowedIdListForAction('add');
    }

    public function getAllowedIdListForView() {
        return $this->getAllowedIdListForAction('view');
    }

    public function getAllowedIdListForAddBySchool($schoolId) {
        return $this->getAllowedIdListForAction('add', $schoolId);
    }

    public function getAllowedIdListForViewBySchool($schoolId) {
        return $this->getAllowedIdListForAction('view', $schoolId);
    }

    private function getAllowedIdListForAction($action, $schoolId = 0) {
        $userGroups = IsicDB::factory('UserGroups');
        $groupList = $schoolId ? $userGroups->listAllowedRecordsBySchool($schoolId) : $userGroups->listAllowedRecords();
        return $this->getAllowedTypesFromGroupListByAction($groupList, $action);
    }

    public function getAllowedRecordIdsForNonJoinedSchools() {
        $idList = array();
        $res = &$this->db->query("
            SELECT
                `id`
            FROM
                ?f
            WHERE
                `order_not_joined_schools` = 1
            ORDER BY
                `name`",
            $this->table
        );
        while ($data = $res->fetch_assoc()) {
            $idList[] = $data['id'];
        }
        return $idList;
    }

    public function getAllowedRecordIdsBySchool($schoolId) {
        $userGroups = IsicDB::factory('UserGroups');
        $groupList = $userGroups->getRecordsBySchool($schoolId);
        return $this->getAllowedTypesFromGroupListByAction($groupList, 'add');
    }

    public function getAllowedRecordIdsByUserSchool($userId, $schoolId) {
        $userGroups = IsicDB::factory('UserGroups');
        $groupList = $userGroups->getRecordsByUserSchool($userId, $schoolId);
        return $this->getAllowedTypesFromGroupListByAction($groupList, 'add');
    }

    public function getAllowedRecordIdsByUser($userId) {
        $userGroups = IsicDB::factory('UserGroups');
        $groupList = $userGroups->getRecordsByUserStatuses($userId);
        return $this->getAllowedTypesFromGroupListByAction($groupList, 'add');
    }

    public function isAllowedByUserSchools($typeId, $schoolIds) {
        foreach ($schoolIds as $schoolId) {
            $types = $this->getAllowedRecordIdsBySchool($schoolId);
            if (in_array($typeId, $types)) {
                return true;
            }
        }
        return false;
    }

    private function getAllowedTypesFromGroupListByAction($groupList, $action) {
        $allowedTypes = array();
        foreach ($groupList as $group) {
            if ($group['allowed_card_types_' . $action]) {
                $tmpTypes = explode(',', $group['allowed_card_types_' . $action]);
                $allowedTypes = array_merge($allowedTypes, array_diff($tmpTypes, $allowedTypes));
            }
        }
        return $allowedTypes;
    }

    public function isExternalCheckNeeded($id) {
        if (!$this->cardTypesNeedingExternalCheck) {
            $isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
            $this->cardTypesNeedingExternalCheck = $isicDbUserStatusTypes->getAvailableCardTypesByExternalCheck();
        }
        return in_array($id, $this->cardTypesNeedingExternalCheck);
    }

    public function isEHLCheckNeeded($id) {
        if (!$this->cardTypesNeedingEHLCheck) {
            $isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
            $this->cardTypesNeedingEHLCheck= $isicDbUserStatusTypes->getAvailableCardTypesByExternalCheck(1, 'EHL');
        }
        return in_array($id, $this->cardTypesNeedingEHLCheck);
    }

    public function isAutoExported($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['auto_export']);
    }

    public function getRecord($id) {
        if (!array_key_exists($id, $this->cardTypeData)) {
            $this->cardTypeData[$id] = parent::getRecord($id);
        }
        return $this->cardTypeData[$id];
    }

    /**
     * Creates array for every card type with bound card types
     *
     * @return array list of card types
    */
    public function getBindings() {
        if ($this->cardTypeBindings) {
            return $this->cardTypeBindings;
        }
        $this->cardTypeBindings = array();
        $r = &$this->db->query("SELECT `id`, `binded_types` FROM `module_isic_card_type` WHERE `binded_types` <> ''");
        while ($data = $r->fetch_assoc()) {
            $this->cardTypeBindings[$data["id"]] = explode(",", $data["binded_types"]);
        }
        return $this->cardTypeBindings;
    }

    public function getNameById($id) {
        if ($this->cardTypeNames) {
            return $this->cardTypeNames[$id];
        }
        $this->cardTypeNames = array();
        $r = &$this->db->query("SELECT `id`, `name` FROM `module_isic_card_type`");
        while ($data = $r->fetch_assoc()) {
            $this->cardTypeNames[$data["id"]] = $data["name"];
        }
        return $this->cardTypeNames[$id];
    }

    public function getRecordsByIdsOrderedByPriorityName($ids) {
        $res = &$this->db->query(
            $this->getBaseQuery() . " WHERE ?f.?f IN (!@) ORDER BY `priority` ASC, name",
            $this->getTable(),
            $this->getPrimaryKey(),
            $this->getIdsAsArray($ids)
        );
        $this->assertResult($res);
        return $res->fetch_all();
    }

    public function getExpirationYears($id) {
        $data = $this->getRecord($id);
        return $data['expiration_year'] ? $data['expiration_year'] : 1;
    }

    public function isAgeRestricted($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['age_restricted']);
    }

    public function isAgeInAllowedRange($id, $ageInYears) {
        $cardTypeRecord = $this->getRecord($id);
        return $ageInYears >= $cardTypeRecord['age_lower_bound'] && $ageInYears <= $cardTypeRecord['age_upper_bound'];
    }

    public function isOrderForOthersAllowed($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['order_for_others_allowed']);
    }

    public function isPersonEmailRequired($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['person_email_required']);
    }

    public function isCCDBType($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['ccdb_name']);
    }

    public function isWithChip($id) {
        $cardTypeRecord = $this->getRecord($id);
        return ($cardTypeRecord && $cardTypeRecord['chip']);
    }

    public function getPictureExpiration($id) {
        $cardTypeRecord = $this->getRecord($id);
        if (!$cardTypeRecord) {
            return 0;
        }
        return intval($cardTypeRecord['picture_expiration']);
    }
}
