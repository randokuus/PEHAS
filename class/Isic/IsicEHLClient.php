<?php
require_once(SITE_PATH . "/class/Webservice.php");
require_once(SITE_PATH . "/class/Webservice/WSParser.php");
require_once(SITE_PATH . "/class/Webservice/WSPartner.php");
require_once(SITE_PATH . "/class/Webservice/WSMessage.php");
require_once(SITE_PATH . "/class/Webservice/WSVersion.php");
require_once(SITE_PATH . "/class/Webservice/WSRequest.php");
require_once(SITE_PATH . "/class/Crypto.php");
require_once(SITE_PATH . '/class/Isic/IsicError.php');
require_once(SITE_PATH . '/class/IsicDB/Logs.php');

class IsicEHLClient {
    const WS_VERSION = '2.0';
    const WS_PARAMETER_NAME = 'txtXml';
    const STATUS_MEMBER = 'Liige';

    /**
     * @var Webservice
     */
    private $ws;

    /**
     * @var WSMessage
     */
    private $msg;

    /**
     * @var string
     */
    private $url;

    /**
     * @var IsicDB_Users
     */
    private $isicDbUsers = null;

    /**
     * @var IsicDB_Schools
     */
    private $isicDbSchools = null;

    /**
     * @var IsicDB_UserStatusTypes
     */
    private $isicDbUserStatusTypes = null;

    /**
     * @var IsicDB_UserGroups
     */
    private $isicDbUserGroups = null;

    /**
     * @var IsicError
     */
    private $error = null;

    /**
     * @var IsicDB_Logs
     */
    private $isicDbLogs = null;

    /**
     * @var IsicDB_UserStatuses
     */
    private $isicDbUserStatuses = null;

    private $overrideExternalCheckFlag = false;

    public function __construct($url = EHL_URL, $partnerId = EHL_PARTNER_ID, $locationId = EHL_LOCATION_ID) {
        $this->url = $url;
        $crypto = new crypto(true);
        $version = new WSVersion(self::WS_VERSION);
        $partner = new WSPartner($partnerId, $locationId);
        $this->ws = new Webservice($partner);
        $this->msg = new WSMessage($partner, $version, $crypto);

        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->isicDbLogs = IsicDB::factory('Logs');
        $this->assignStatusIdList();
        $this->error = new IsicError();
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatuses->setCurrentOrigin(IsicDB_UserStatuses::origin_ehl);
    }

    private function assignStatusIdList() {
        $this->statusIdList = array(
            self::STATUS_MEMBER => $this->isicDbUserStatusTypes->getRecordIdByEhlName(self::STATUS_MEMBER),
        );
    }

    public function getStatusListByUser($personNumber) {
        $query = array(0 => array('person_number' => $personNumber));
        $queryResult = $this->getEhlQueryResult($query);
        $statusList = array();
        if (!$this->isErrorInResult($queryResult)) {
            $parsedResult = $this->getQueryResultParsed($queryResult['profile']);
            $statusList = $this->getStatusListFromParsedResult($parsedResult);
        }
        return $statusList;
    }

    public function getStatusListFromParsedResult($parsedResults)
    {
        $statusIdList = array();
        $personData = false;
        foreach ($parsedResults as $parsedResult) {
            $statusIdList = array_merge($statusIdList, $this->addUserStatuses($parsedResult));
            $personData = $parsedResult;
        }
        if ($personData) {
            $delIdList = $this->removeUserStatuses($parsedResult, $statusIdList ? $statusIdList : array());
        }
        return $statusIdList;
    }

    private function addUserStatuses($data) {
        if (!$data['person']['ehl_status_check_allowed'] && !$this->getOverrideExternalCheckFlag()) {
            return false;
        }

        $userStatusIdList = array();
        foreach ($this->getStatusesMerged($data) as $status) {
            if (!is_array($status)) {
                continue;
            }
            $schoolStatusIdList = $this->getUserStatusesForGroup($data['person']['user_id'], $status['group_id']);
            $regionStatusIdList = $this->getUserStatusesForGroup($data['person']['user_id'], $status['region_group_id']);
            $userStatusIdList = array_merge($userStatusIdList, $regionStatusIdList, $schoolStatusIdList);
        }
        return $userStatusIdList;
    }

    private function getStatusesMerged($data) {
        return array($data['member']);
    }

    /**
     * @param $data
     * @param $status
     * @return array
     */
    private function getUserStatusesForGroup($userId, $groupId)
    {
        $userStatusIdList = array();
        $userStatuses = $this->isicDbUserStatuses->getAllAutomaticRecordsByGroupUser(
            $groupId, $userId, IsicDB_UserStatuses::origin_ehl
        );
        if (!$userStatuses) {
            $insertData = array(
                'user_id' => $userId,
                'group_id' => $groupId,
                'active' => 1,
            );
            $userStatusIdList[] = $this->isicDbUserStatuses->insertRecord($insertData);
        } else {
            foreach ($userStatuses as $userStatus) {
                $userStatusIdList[] = $userStatus['id'];
            }
        }
        return $userStatusIdList;
    }

    private function removeUserStatuses($data, $validIdList) {
        return $this->isicDbUserStatuses->deactivateAllAutomaticRecordsByUserExceptGivenIds(
            $data['person']['user_id'], $validIdList, IsicDB_UserStatuses::origin_ehl
        );
    }


    private function isErrorInResult($result) {
        if (!isset($result['profile'])) {
            return true;
        }
        $profiles = $result['profile'];
        foreach ($profiles as $profile) {
            if (!isset($profile['param'])) {
                return true;
            }

            if (isset($profile['param'][0]['error']) && !$this->isAllowedError($profile['param'][0]['error'])) {
                return true;
            }
        }
        return false;
    }

    private function isAllowedError($data) {
        return sizeof(array_intersect(array(1102, 1103), array_keys($data)) > 0);
    }

    public function performQueryAndParseResult($personNumber) {
    }

    private function getEhlQueryResult($query) {
        $this->error->reset();
        $result = false;
        $reqid = WSRequest::generateId();
        $message = $this->msg->createMessage('person_profile', $reqid, $query);
        $response = $this->msg->sendMessage($this->url, self::WS_PARAMETER_NAME, $message);
        if (!$response) {
            $errorResult = 'Empty response!';
        } else {
            $parsed = WSParser::parse($response);
            if (!$parsed) {
                $errorResult = 'Parse failed!';
            } else {
                $result = $this->ws->processMessageResponse($parsed);
                if ($this->isErrorInResult($result)) {
                    $errorResult = $result;
                }
            }
        }

        if ($errorResult) {
            $this->error->add('ehl_query');
        }

        $this->logEhlQueryResult($query, $result, $errorResult);
        return $result;
    }

    private function getQueryResultParsed($queryResultData) {
        $parsedResults = array();
        foreach ($queryResultData as $data) {
            $parsedResults[] = $this->getParsedResults($data['param'][0]);
        }
        return $parsedResults;
    }

    private function getParsedResults($data) {
        return array(
            'person' => $this->getPerson($data),
            'member' => $this->getStatusMember($data)

        );
    }

    private function getPerson($data) {
        $personData = array(
            'user_code' => $data['person_number'],
            'name_first' => $data['person_name_first'],
            'name_last' => $data['person_name_last'],
            'birthday' => $data['person_birthday'],
            'email' => $data['person_email'],
            'phone' => $data['person_phone'],
        );
        if ($personData['user_code']) {
            $userData = $this->isicDbUsers->getRecordByCode($personData['user_code']);
            if ($userData) {
                $personData['user_id'] = $userData['user'];
                $personData['ehl_status_check_allowed'] = $userData['ehl_status_check_allowed'];
            }
        }

        return $personData;
    }

    private function getStatusMember($data) {
        return $this->getStatus($data, self::STATUS_MEMBER);
    }

    private function getStatus($data, $type) {
        $result = $this->parseSchool($data);
        if (!isset($result['school_id'])) {
            return false;
        }
        $result['region_id'] = $this->getRegionId($data);
        $result['status_id'] = $this->statusIdList[$type];
        $result['group_id'] = $this->getGroupId($result['school_id'], $result['status_id']);
        $result['region_group_id'] = $this->getGroupId($result['region_id'], $result['status_id']);
        return $result;
    }

    private function parseSchool($data, $paramName = 'school') {
        $schoolData = array(
            'school_name' => $data[$paramName . '_name'],
            'school_ehl_code' => $data[$paramName . '_id'],
        );
        if ($schoolData['school_ehl_code']) {
            $schoolData['school_id'] = $this->getSchoolId($schoolData);
        }
        return $schoolData;
    }

    private function getRegionId($data) {
        $regionData = $this->parseSchool($data, 'region');
        return $regionData['school_id'];
    }

    private function getSchoolId($schoolData) {
        $school = $this->isicDbSchools->getRecordByEhlCode($schoolData['school_ehl_code']);
        if (!$school) {
            $newSchool = array(
                'name' => $schoolData['school_name'],
                'ehl_code' => $schoolData['school_ehl_code'],
            );
            $schoolId = $this->isicDbSchools->insertRecord($newSchool);
            $school = $this->isicDbSchools->getRecord($schoolId);
        }
        return $school['id'];
    }

    private function getGroupId($schoolId, $statusId) {
        $groupAutomatic = $this->isicDbUserGroups->getRecordBySchoolStatusAutomaticAndAddIfNotFound($schoolId, $statusId, 1, 1);
        return $groupAutomatic['id'];
    }

    private function logEhlQueryResult($query, $result, $errorResult) {
        $logData = array(
            'module_name' => 'ehl_query',
            'record_id' => $this->isicDbUsers->getIdByUserCode($query[0]['person_number']),
            'event_type' => $this->error->isError() ? IsicDB_Logs::log_type_error : IsicDB_Logs::log_type_success,
            'event_body' => print_r($this->error->isError() ? $errorResult : $result, true),
        );
        $this->isicDbLogs->insertRecord($logData);
    }

    /**
     * @return \IsicError
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param field_type $overrideExternalCheckFlag
     */
    public function setOverrideExternalCheckFlag($overrideExternalCheckFlag) {
        $this->overrideExternalCheckFlag = $overrideExternalCheckFlag;
    }
    /**
     * @return the $overrideExternalCheckFlag
     */
    public function getOverrideExternalCheckFlag() {
        return $this->overrideExternalCheckFlag;
    }
}
