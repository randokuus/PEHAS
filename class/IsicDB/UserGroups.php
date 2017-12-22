<?php

class IsicDB_UserGroups extends IsicDB {

    const AUTO = '(auto)';
    const SUPER_GROUP = 1;

    protected $table = 'module_user_groups';
    protected $orderBy = 'id';

    protected $insertableFields = array(
        'name', 'isic_school', 'isic_card_type', 'user_status_id', 'automatic', 'allowed_card_types_view',
        'allowed_card_types_add', 'addtime', 'adduser', 'modtime', 'moduser'
    );

    protected $updateableFields = array(
        'modtime', 'moduser', 'name'
    );

    protected $searchableFields = array(
        'isic_school', 'user_status_id', 'automatic'
    );

    public function insertRecord($data) {
        $common = IsicCommon::getInstance();
        $data['addtime'] = $this->db->now();
        $data['adduser'] = $common->getLogUserId($this->userid);
        $data['modtime'] = $this->db->now();
        $data['moduser'] = $common->getLogUserId($this->userid);
        $addedId = parent::insertRecord($data);
        return $addedId;
    }

    public function updateRecord($id, $data) {
        $common = IsicCommon::getInstance();
        $data['modtime'] = $this->db->now();
        $data['moduser'] = $common->getLogUserId($this->userid);
        parent::updateRecord($id, $data);
    }

    function getNameListByIds($groupIds) {
        $rg = &$this->db->query("
            SELECT
                `name`
            FROM
                ?f
            WHERE
                `id` IN (!@)
            ORDER BY
                `name`
            ",
            $this->table,
            $this->getIdsAsArray($groupIds));

        $list = array();
        while ($data = $rg->fetch_assoc()) {
            $list[] = $data['name'];
        }
        return $list;
    }

    public function listAllowedRecords($automatic = 0) {
        $r = $this->db->query("
            SELECT
                *
            FROM
                ?f
            WHERE
                `id` IN (!@) AND
                `automatic` = !
            ORDER BY
                ?f",
            $this->table,
            $this->getIdsAsArray($this->usergroups),
            $automatic,
            'name'
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function listAllowedRecordsBySchool($schoolId, $automatic = 0) {
        $r = $this->db->query("
            SELECT
                *
            FROM
                ?f
            WHERE
                `id` IN (!@) AND
                `isic_school` = ! AND
                `automatic` = !
            ORDER BY
                ?f",
            $this->table,
            $this->getIdsAsArray($this->usergroups),
            $schoolId,
            $automatic,
            'name'
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function generateName($school_id, $status_id, $automatic) {
        $schools = IsicDB::factory('Schools');
        $statuses = IsicDB::factory('UserStatusTypes');
        $schoolData = $schools->getRecord($school_id);
        $statusData = $statuses->getRecord($status_id);
        $name = $schoolData['name'] . ' / ' . $statusData['name'];
        if ($automatic) {
            $name .= ' / ' . self::AUTO;
        }
        return $name;
    }

    public function getRecordsByUserStatuses($userId) {
        $r = $this->db->query("
            SELECT
                `g`.*
            FROM
                ?f AS `g`,
                ?f AS `us`
            WHERE
                 `g`.`isic_school` = `us`.`school_id` AND
                 `g`.`user_status_id` = `us`.`status_id` AND
                 `g`.`id` = `us`.`group_id` AND
                 `us`.`active` = 1 AND
                 `us`.`user_id` = !
            GROUP BY
                `g`.`id`
            ORDER BY
                `g`.`id`",
            $this->table,
            'module_user_status_user',
            $userId
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getRecordsByUserSchool($userId, $schoolId) {
        $r = $this->db->query("
            SELECT
                `g`.*
            FROM
                ?f AS `g`,
                ?f AS `us`
            WHERE
                 `g`.`isic_school` = `us`.`school_id` AND
                 `g`.`user_status_id` = `us`.`status_id` AND
                 `g`.`id` = `us`.`group_id` AND
                 `us`.`active` = 1 AND
                 `us`.`user_id` = ! AND
                 `g`.`isic_school` = !
            GROUP BY
                `g`.`id`
            ORDER BY
                `g`.`id`",
            $this->table,
            'module_user_status_user',
            $userId,
            $schoolId
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getRecordBySchoolStatusAutomatic($schoolId, $statusId, $automatic = 0) {
        $r = $this->findRecords(array(
            'isic_school' => $schoolId,
            'user_status_id' => $statusId,
            'automatic' => $automatic
        ), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getRecordBySchoolStatusAutomaticAndAddIfNotFound($schoolId, $statusId, $automatic = 0, $cardTypesForAddAllowed = 1) {
        $group = $this->getRecordBySchoolStatusAutomatic($schoolId, $statusId, $automatic);
        if (!$group) {
            $userStatusTypes = IsicDB::factory('UserStatusTypes');
            $statusTypeData = $userStatusTypes->getRecord($statusId);
            $insertData = array(
                'name' => $this->generateName($schoolId, $statusId, $automatic),
                'isic_school' => $schoolId,
                'user_status_id' => $statusId,
                'automatic' => $automatic,
                'allowed_card_types_view' => $statusTypeData['card_types'],
                'allowed_card_types_add' => ($cardTypesForAddAllowed ? $statusTypeData['card_types'] : '')
            );
            $groupId = $this->insertRecord($insertData);
            $group = $this->getRecord($groupId);
        }
        return $group;
    }

    public function getRecordsBySchoolAndCardType($schoolId, $cardTypeId) {
        $match = false;
        $data = $this->findRecords(array('isic_school' => $schoolId));
        foreach ($data as $group) {
            $cardTypeList = explode(',', $group['allowed_card_types_add']);
            if (in_array($cardTypeId, $cardTypeList)) {
                $match[] = $group;
            }
        }
        return $match ? $match : array();
    }

    public function getRecordsBySchool($schoolId) {
        return $this->getRecordsBySchoolStatus($schoolId);
    }

    public function getRecordsBySchoolStatus($schoolId, $statusId = null) {
        $queryParams = array('isic_school' => $schoolId);
        if ($statusId) {
            $queryParams['user_status_id'] = $statusId;
        }
        return $this->findRecords($queryParams);
    }

    public function listAllowedSchools() {
        static $school_list = null;
        if (is_array($school_list)) {
            return $school_list;
        }

        $school_list = array(-1);
        /** @var IsicDB_Schools $isicDbSchools */
        $isicDbSchools = IsicDB::factory('Schools');
        if ($isicDbSchools->getHiddenSchoolId()) {
            $school_list[] = $isicDbSchools->getHiddenSchoolId();
        }
        if (!is_array($this->usergroups)) {
            return $school_list;
        }
        $r = &$this->db->query('
            SELECT
                DISTINCT(`module_user_groups`.`isic_school`) AS `isic_school`
            FROM
                `module_user_groups`
            WHERE
                `id` IN (!@)',
            $this->getIdsAsArray($this->usergroups)
        );
        while ($data = $r->fetch_assoc()) {
            $school_list[] = $data["isic_school"];
        }
        return $school_list;
    }

    public function listAllowedUserGroups($school_list) {
        static $cache = array();
        $key = serialize($school_list);
        if(array_key_exists($key, $cache))
            return $cache[$key];
        $group_list = array();
        if (is_array($school_list) && count($school_list) > 0) {
            $r = &$this->db->query('SELECT `module_user_groups`.`id` FROM `module_user_groups` WHERE `isic_school` IN (!@) ORDER BY `id`', $school_list);
            while ($data = $r->fetch_assoc()) {
                $group_list[] = $data["id"];
            }
        }
        $cache[$key] = $group_list;
        return $group_list;
    }

    public function getAllRecordIds() {
        $idList = array();
        $r = $this->db->query("
            SELECT
                `g`.`id`
            FROM
                ?f AS `g`
            ORDER BY
                `g`.`id`",
            $this->table
        );
        while ($data = $r->fetch_assoc()) {
            $idList[] = $data['id'];
        }
        return $idList;
    }

    public function getSuperGroupId() {
        return self::SUPER_GROUP;
    }

    public function getRecordsByRegionList($regionList) {
        if (!$regionList) {
            return array();
        }
        $r = $this->db->query("
            SELECT
                `g`.*
            FROM
                ?f AS `g`,
                ?f AS `s`
            WHERE
                 `g`.`isic_school` = `s`.`id` AND
                 `s`.`region_id` IN (!)
            ORDER BY
                `g`.`id`",
            $this->table,
            'module_isic_school',
            $regionList
        );
        self::assertResult($r);
        return $r->fetch_all();
    }
}
