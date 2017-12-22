<?php

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/class/SMS/MobileNumberValidator.php");

class IsicDB_Users extends IsicDB
{
    const user_auth_types = '2,3,4,5,6,7,8';
    const user_type_admin = 1; // admin
    const user_type_user = 2; // regular user
    const default_user_type = self::user_type_user;
    const default_password = "-";
    const SPECIAL_OFFERS_ALL = '1,2';

    protected $table = 'module_user_users';
    protected $primary = 'user';
    protected $orderBy = 'username';

    protected $insertableFields = array(
        'user_type', 'ggroup', 'auth_type', 'username', 'password', 'user_code', 'name', 'name_first', 'name_last',
        'email', 'phone', 'birthday', 'delivery_addr1', 'delivery_addr2', 'delivery_addr3', 'delivery_addr4',
        'bankaccount', 'bankaccount_name', 'newsletter', 'ips', 'type', 'active', 'added', 'skin', 'rightcolhidden',
        'pic', 'appl_confirmation_mails', 'special_offers'
    );

    protected $updateableFields = array(
        'user_type', 'ggroup', 'auth_type', 'username', 'password', 'name', 'name_first', 'name_last',
        'email', 'phone', 'delivery_addr1', 'delivery_addr2', 'delivery_addr3', 'delivery_addr4', 'bankaccount',
        'bankaccount_name', 'newsletter', 'ips', 'type', 'active', 'added', 'skin', 'rightcolhidden', 'pic',
        'external_status_check_allowed', 'active_school_id', 'appl_confirmation_mails', 'last_pan_query', 'pan_queries',
        'data_sync_allowed', 'children_list', 'ehl_status_check_allowed', 'special_offers'
    );

    protected $searchableFields = array(
        'user_type', 'ggroup', 'auth_type', 'username', 'user_code', 'name_first', 'name_last', 'email', 'active'
    );

    protected $requiredFields = array(
        'add' => array("name_first", "name_last", "birthday", "user_code"),
        'modify' => array("name_first", "name_last")
    );

    public $applicationMatchFields = array(
        "name_first" => "person_name_first",
        "name_last" => "person_name_last",
        "user_code" => "person_number",
        "birthday" => "person_birthday",
        "delivery_addr1" => "delivery_addr1",
        "delivery_addr2" => "delivery_addr2",
        "delivery_addr3" => "delivery_addr3",
        "delivery_addr4" => "delivery_addr4",
        "email" => "person_email",
        "phone" => "person_phone",
        "position" => "person_position",
        "class" => "person_class",
        "stru_unit" => "person_stru_unit",
        "stru_unit2" => "person_stru_unit2",
        "staff_number" => "person_staff_number",
        "bankaccount" => "person_bankaccount",
        "bankaccount_name" => "person_bankaccount_name",
    );

    private $common;
    private $isicDbCards;
    /**
     * @var IsicDB_UserGroups
     */
    private $isicDbUserGroups;
    /**
     * @var IsicDB_UserStatuses
     */
    private $isicDbUserStatuses;
    private $isicDbUserStatusTypes;
    private $isicDbCardValidities;
    private $isicDbCardTypes;
    private $allowedSchools;

    public function __construct()
    {
        parent::__construct();
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbCardValidities = IsicDB::factory('CardValidities');
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');
        $this->allowedSchools = $this->isicDbUserGroups->listAllowedSchools();
    }

    public function insertRecord(array $data)
    {
        $common = IsicCommon::getInstance();
        $data['added'] = $this->db->now();
        $data['name'] = $data['name_first'] . ' ' . $data['name_last'];
        if (!isset($data['username'])) {
            $data['username'] = $data['user_code'];
        }
        if (!isset($data['password'])) {
            $data['password'] = self::default_password;
        }
        if (!isset($data['user_type'])) {
            $data['user_type'] = self::default_user_type;
        }
        if (!isset($data['auth_type'])) {
            $data['auth_type'] = self::user_auth_types;
        }
        if (!isset($data['pic'])) {
            $data['pic'] = '';
        }
        if (!isset($data['active'])) {
            $data['active'] = 1;
        }
        $addedId = parent::insertRecord($data);
        $common->saveUserChangeLog(1, $addedId, array(), $this->getRecord($addedId));
        return $addedId;
    }

    public function updateRecord($id, array $data)
    {
        $common = IsicCommon::getInstance();
        $rowOld = $this->getRecord($id);
        if (!isset($data['pic'])) {
            $data['pic'] = $rowOld['pic'];
        }
        parent::updateRecord($id, $data);
        $common->saveUserChangeLog(2, $id, $rowOld, $this->getRecord($id));
    }

    public function updateRecordFromApplication($id, $applicationRecord)
    {
        $data = array();
        foreach ($this->applicationMatchFields as $fkey => $fval) {
            if (!empty($applicationRecord[$fval])) {
                $data[$fkey] = $applicationRecord[$fval];
            }
        }
        if (count($data)) {
            $this->updateRecord($id, $data);
            return true;
        }
        return false;
    }

    public function getRecordByCode($user_code)
    {
        return $this->getRecordByCodeUserType($user_code, self::user_type_user);
    }

    public function getRecordByCodeUserType($user_code, $user_type = 0)
    {
        if (!trim($user_code)) {
            return false;
        }
        $query = array('user_code' => $user_code);
        if ($user_type) {
            $query['user_type'] = $user_type;
        }
        $r = $this->findRecords($query, 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getIdByUserCode($user_code)
    {
        $userRecord = $this->getRecordByCode($user_code);
        if ($userRecord) {
            return $userRecord[$this->getPrimaryKey()];
        }
        return 0;
    }

    public function getRecordByUsername($username)
    {
        if (!$username) {
            return false;
        }
        $r = $this->findRecords(array('username' => $username), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getIdByUsername($username)
    {
        $userRecord = $this->getRecordByUsername($username);
        if ($userRecord) {
            return $userRecord[$this->getPrimaryKey()];
        }
        return 0;
    }

    public function listAllowedRecords()
    {
        $r = $this->db->query(
            "SELECT * FROM ?f ORDER BY ?f, ?f",
            $this->table,
            'name_last',
            'name_first'
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function updateGroups($id)
    {
        $userData = $this->getRecord($id);
        if (!$userData) {
            return null;
        }

        $userGroups = IsicDB::factory('UserGroups');
        if ($userData['user_type'] == self::user_type_user) {
            $this->isicDbCards->activateRecordsPreviouslyDeactivatedForMissingPrivileges($userData);
            $this->isicDbCards->deactivateRecordsWithMissingPrivileges($userData);
            $groupData = $userGroups->getRecordsByUserStatuses($id);
        } else if ($userData['user_type'] == self::user_type_admin && $userData['use_region_list']) {
            $groupData = $userGroups->getRecordsByRegionList($userData['region_list']);
        } else {
            return null;
        }
        $groupIds = array();
        foreach ($groupData as $group) {
            $groupIds[] = $group['id'];
        }
        $groupList = implode(',', $groupIds);
        if ($userData['ggroup'] != $groupList) {
            $this->updateRecord($id, array('ggroup' => $groupList));
            return $groupList;
        }
        return false;
    }

    public function findRecordsBySchool(array $schoolData)
    {
        return $this->findUserTypeFilteredRecordsBySchool($schoolData);
    }

    public function findAdminRecordsBySchool(array $schoolData)
    {
        return $this->findUserTypeFilteredRecordsBySchool($schoolData, self::user_type_admin);
    }

    public function findAdminRecordsBySchoolStatus(array $schoolData, $statusId)
    {
        return $this->findUserTypeFilteredRecordsBySchoolStatus($schoolData, self::user_type_admin, $statusId);
    }

    public function findUserRecordsBySchool(array $schoolData)
    {
        return $this->findUserTypeFilteredRecordsBySchool($schoolData, self::user_type_user);
    }

    private function findUserTypeFilteredRecordsBySchool(array $schoolData, $userTypeFilter = null)
    {
        return $this->findUserTypeFilteredRecordsBySchoolStatus($schoolData, $userTypeFilter);
    }

    private function findUserTypeFilteredRecordsBySchoolStatus(array $schoolData, $userTypeFilter = null, $statusId = null)
    {
        $t = $this->getTableQuoted();
        $groupList = $this->isicDbUserGroups->getRecordsBySchoolStatus($schoolData['id'], $statusId);
        $groupList[] = array('id' => $this->isicDbUserGroups->getSuperGroupId());
        $userTypeSql = $userTypeFilter === null ? "" : "AND $t.`user_type` = " . (int)$userTypeFilter;
        $userList = array();
        foreach ($groupList as $groupData) {
            $r = $this->db->query(
                $this->getBaseQuery() . " WHERE FIND_IN_SET(?, $t.`ggroup`) !",
                $groupData['id'],
                $userTypeSql
            );
            $this->assertResult($r);
            while ($userData = $r->fetch_assoc()) {
                $userList[$userData[$this->getPrimaryKey()]] = $userData;
            }
        }
        return array_values($userList);
    }

    public function hasOrderedApplicationConfirmationNotifications(array $userData)
    {
        return (bool)$userData['appl_confirmation_mails'];
    }

    public function getUserTypeAdmin()
    {
        return self::user_type_admin;
    }

    public function getUserTypeUser()
    {
        return self::user_type_user;
    }

    public function isUserCurrentUser(array $userData)
    {
        return $this->userid == $userData['user'];
    }

    public function isUserSuperAdmin(array $userData)
    {
        return in_array($this->isicDbUserGroups->getSuperGroupId(), explode(',', $userData['ggroup']));
    }

    public function isCurrentUserSuperAdmin()
    {
        return in_array($this->isicDbUserGroups->getSuperGroupId(), $this->isicDbUserGroups->usergroups);
    }

    public function isCurrentUserAdmin()
    {
        return $this->user_type == $this->getUserTypeAdmin();
    }

    public function isCurrentUserRegular()
    {
        return $this->user_type == $this->getUserTypeUser();
    }

    public function isCurrentPersonNumber(array $cardData)
    {
        return $this->user_code == $cardData['person_number'];
    }

    public function isCurrentPersonOrChildNumber(array $cardData)
    {
        $common = IsicCommon::getInstance();
        return in_array($cardData['person_number'], $common->getCurrentUserCodeList());
    }

    private function isAdminAllowedToViewCard($cardData)
    {
        return $this->isicDbCardValidities->wasEverValidCardForSchools($cardData['id'], $this->allowedSchools);
    }

    private function isAdminAllowedToModifyCard($cardData)
    {
        return $this->isicDbCardValidities->isValidCardForSchools($cardData['id'], $this->allowedSchools);
    }

    public function canViewCard(array $cardData)
    {
        if ($this->isCurrentUserAdmin()) {
            return $this->isAdminAllowedToViewCard($cardData);
        } else if ($this->isCurrentUserRegular()) {
            return $this->isCurrentPersonOrChildNumber($cardData);
        }
        return false;
    }

    public function canDistributeCard(array $cardData)
    {
        // card can be distributed if the expiration date is yet to come and card is ordered
        if ($this->isicDbCards->isAllowedKind($cardData["kind_id"])
            && !IsicDate::isExpiredDate($cardData["expiration_date"])
            && $this->isicDbCards->isStateOrdered($cardData["state_id"])
        ) {
            // admin users can distribute if the school is allowed
            if ($this->isCurrentUserAdmin()) {
                return $this->isAdminAllowedToModifyCard($cardData);
            } else if ($this->isCurrentUserRegular()) {
                // regular user can never distribute card
                return false;
            }
        }
        return false;
    }

    public function canActivateCard(array $cardData)
    {
        // card can be activated if the expiration date is yet to come and card is not already active
        if ($this->isicDbCards->isAllowedKind($cardData["kind_id"])
            && $this->isicDbCards->canBeActivated($cardData)
        ) {
            if ($this->isCurrentUserAdmin()) {
                return $this->isAdminAllowedToModifyCard($cardData);
            } else if ($this->isCurrentUserRegular()) {
                return $this->canActivateCardUser($cardData);
            }
        }
        return false;
    }

    public function canActivateCardUser(array $cardData)
    {
        $isicDbSchools = IsicDB::factory('Schools');
        $isicDbCardDeliveries = IsicDB::factory('CardDeliveries');
        // regular users can only activate their own cards if these were deactivated by themselves
        // or if card has never been deactivated and school is not joined or it is deliverable card (can be sent home)
        return ($this->isCurrentPersonOrChildNumber($cardData) &&
            (
                $cardData['deactivation_user'] == $this->userid ||
                $cardData['deactivation_user'] == 0 &&
                (
                    !$isicDbSchools->isJoined($cardData['school_id']) ||
                    $isicDbCardDeliveries->isDeliverable($cardData['delivery_id'])
                )
            ) &&
            $this->isicDbUserStatuses->getRecordByUserSchoolCardType($this->userid, $cardData['school_id'], $cardData['type_id'])
        );
    }

    public function canDeactivateCard(array $cardData)
    {
        // card can be deactivated if card is currently active
        if ($this->isicDbCards->canBeDeactivated($cardData)) {
            // admin users can activate if the school is allowed
            if ($this->isCurrentUserAdmin()) {
                return $this->isAdminAllowedToModifyCard($cardData);
            } else if ($this->isCurrentUserRegular()) {
                // regular users can deactivate their own cards
                return $this->isCurrentPersonOrChildNumber($cardData);
            }
        }
        return false;
    }

    public function canReturnCard(array $cardData)
    {
        if ($this->isicDbCards->isAllowedKind($cardData["kind_id"]) && !$this->isicDbCards->isReturned($cardData)) {
            if ($this->isicDbCards->isActivated($cardData) || $this->isicDbCards->isDeactivated($cardData)) {
                // admin users can activate it if the card type is allowed for their schools
                if ($this->isCurrentUserAdmin()) {
                    return $this->isAdminAllowedToViewCard($cardData)
                    && $this->isicDbCardTypes->isAllowedByUserSchools($cardData['type_id'], $this->allowedSchools);
                }
            }
        }
        return false;
    }

    public function canReplaceCard(array $cardData)
    {
        // card can be replaced if card is currently active or non-active
        return $this->canReplaceOrProlongCard($cardData, false);
    }

    public function canProlongCard(array $cardData)
    {
        // card can be prolonged if card is currently active or non-active
        // and calculated expiration date is larger than current card expiration date
        return $this->canReplaceOrProlongCard($cardData, true);
    }

    private function canReplaceOrProlongCard(array $cardData, $isExpiredCard)
    {
        $common = IsicCommon::getInstance();
        $new_expiration = $common->getCardExpiration($cardData["type_id"], $cardData["expiration_date"], false);
        if ($this->isicDbCards->isAllowedKind($cardData["kind_id"])
            && IsicDate::isExpiredDate($cardData["expiration_date"], $new_expiration) == $isExpiredCard
            && $this->isicDbCards->isStateActivatedOrDeactivated($cardData["state_id"])
        ) {
            // admin users can start prolonging if the school is allowed
            if ($this->isCurrentUserAdmin()) {
                return $this->isAdminAllowedToModifyCard($cardData);
            } else if ($this->isCurrentUserRegular()) {
                // regular users can prolong their own cards
                return $this->isCurrentPersonOrChildNumber($cardData);
            }
        }
        return false;
    }

    public function canDeleteCard(array $cardData)
    {
        return false;
    }

    public function getRecordsByGroupsWithFilter(array $groups, $sendType = 1, $faculty = '')
    {
        $filter = array('1 = 1');
        switch ($sendType) {
            case messages::SEND_TYPE_EMAIL: // e-mail
                $filter[] = "`u`.`email` <> ''";
                // regular users can only see administrators
                if ($this->isCurrentUserRegular()) {
                    $filter[] = "`u`.`user_type` = 1";
                }
                break;
            case messages::SEND_TYPE_SMS: // sms
                $filter[] = "`u`.`phone` <> ''";
                break;
            default:
                break;
        }

        if ($faculty) {
            /*
            $tFilter = array(
                '`s`.`class` = ' . $this->db->quote($course),
                '`s`.`course` = ' . $this->db->quote($course),
                '`s`.`structure_unit` = ' . $this->db->quote($course)
            );*/
            $tFilter = array(
                '`s`.`faculty` = ' . $this->db->quote($faculty)
            );
            $filter[] = '(' . implode(' OR ', $tFilter) . ')';
        }

        $filterSql = implode(' AND ', $filter);
        $users = array();
        $userIds = array();
        foreach ($groups as $groupId) {
            if (!in_array($groupId, $this->usergroups)) {
                continue;
            }
            $sql = '
            SELECT
                `u`.`user`,
                `u`.`name_first`,
                `u`.`name_last`,
                `u`.`email`,
                `u`.`phone`
            FROM
                ?f AS `u`,
                ?f AS `s`
            WHERE
                `u`.`active` = 1 AND
                `u`.`user` = `s`.`user_id` AND
                `s`.`active` = 1 AND
                `s`.`group_id` = ? AND
                !
            GROUP BY
                `u`.`user`
            ORDER BY
                `u`.`name_last`,
                `u`.`name_first`
          ';
            $res = $this->db->query(
                $sql,
                $this->table,
                'module_user_status_user',
                $groupId,
                $filterSql
            );

            while ($row = $res->fetch_assoc()) {
                if (!in_array($row['user'], $userIds)) {
                    if (!$this->isValidContact($sendType, $row)) {
                        continue;
                    }
                    $userIds[] = $row['user'];
                    $users[] = $this->getShortUserData($row);
                }
            }
        }
        return array($userIds, $users);
    }

    public function getRecordsByGroups(array $groups)
    {
        return $this->getRecordsByGroupsWithFilter($groups);
    }

    public function getIdListByUserCodeList($userCodeList)
    {
        $sql = 'SELECT ?f FROM ?f WHERE user_code IN (!@)';
        $res = $this->db->query($sql, $this->getPrimaryKey(), $this->table, $userCodeList);
        $userIdList = array();
        while ($row = $res->fetch_assoc()) {
            $userIdList[] = $row[$this->getPrimaryKey()];
        }
        return $userIdList;
    }

    public function getUsersWithChildren()
    {
        $sql = 'SELECT * FROM ?f WHERE children_list <> ?';
        return $this->db->fetch_all($sql, $this->getTable(), '');
    }

    public function getUserBirthday($id)
    {
        $data = $this->getRecord($id);
        if ($data) {
            return $data['birthday'];
        }
        return IsicDate::EMPTY_DATE;
    }

    public function getUsersWithRegionList()
    {
        $sql = 'SELECT * FROM ?f WHERE use_region_list = !';
        return $this->db->fetch_all($sql, $this->getTable(), 1);
    }

    /**
     * @param $sendType
     * @param $row
     * @return bool
     */
    public function isValidContact($sendType, $row)
    {
        switch ($sendType) {
            case messages::SEND_TYPE_EMAIL: // email
                $validUser = validateEmail($row['email']);
                break;
            case messages::SEND_TYPE_SMS: // sms
                $tMobile = MobileNumberValidator::convertNumber($row['phone']);
                $validUser = MobileNumberValidator::isValid($tMobile);
                break;
            default:
                $validUser = true;
                break;
        }
        return $validUser;
    }

    /**
     * @param $row
     * @return array
     */
    public function getShortUserData($row)
    {
        return array(
            'id' => $row['user'],
            'name' => $row['name_first'] . ' ' . $row['name_last'],
            'email' => $row['email'],
            'phone' => $row['phone'],
        );
    }

    public function enableSpecialOffers($userId)
    {
        $this->updateRecord($userId, array('special_offers' => self::SPECIAL_OFFERS_ALL));
    }
}