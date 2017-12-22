<?php
require_once(SITE_PATH . '/class/Ehis/EhisClient.php');
require_once(SITE_PATH . '/class/Ehis/EhisTypes.php');
require_once(SITE_PATH . '/class/Ehis/EhisStatus.php');
require_once(SITE_PATH . '/class/Isic/IsicError.php');
require_once(SITE_PATH . '/class/IsicDB/Logs.php');

class EhisUser {
    const queryWsdl = '/class/Ehis/adapter.wsdl';
    const queryPerson = '38112310344'; // Garry Koort
    const querySchool = '11458311'; // Koolisüsteemid

    private $ehisClient = false;
    private $ehisStatus = false;
    /**
     * @var IsicDB_UserStatuses
     */
    private $isicDbUserStatuses = false;
    private $isicDbUsers = false;
    private $isicDbLogs = false;
    /**
     * @var IsicDB_Schools
     */
    private $isicDbSchools = null;
    private $userInstance = false;
    private $queryInstance = false;
    private $queryResult = false;
    private $parsedResults = array();
    private $error = false;
    private $userid = false;
    private $overrideExternalCheckFlag = false;
    private $statusRemoveType = 'expire'; /* remove | expire */
    private $statusExpirationDate = null;

    public function __construct() {
        $this->userid = $GLOBALS["user_data"][0];
        $this->ehisClient = new EhisClient(SITE_PATH . self::queryWsdl, self::querySchool, self::queryPerson);
        $this->ehisStatus = new EhisStatus();
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbLogs = IsicDB::factory('Logs');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbUserStatuses->setCurrentOrigin(IsicDB_UserStatuses::origin_ehis);
        $this->error = new IsicError();
    }

    public function checkAllUserStatuses() {
        $currentDate = IsicDate::getCurrentTimeFormatted('Y-m-d');
        if ($currentDate == date('Y-m-d', mktime(0, 0, 0, 12, 31, date('Y')))) {
            $userList = $this->isicDbUserStatuses->getUsersWithAutomaticStatuses();
        } else {
            $schools = $this->isicDbSchools->getIdListWithEhisCheckOnGivenDate($currentDate);
            $userList = $this->isicDbUserStatuses->getUsersWithAutomaticStatusesForGivenSchools($schools);
        }
        echo "================================================================================\n";
        echo IsicDate::getCurrentTimeFormatted() . ": EHIS MassQuery Start\n";
        echo 'User codes to query: ' . sizeof($userList) . "\n";
        echo "--------------------------------------------------------------------------------\n";
        echo "Schools: \n";
        print_r($schools);
        echo "\n";
        echo "Users: \n";
        print_r($userList);
        echo "\n";
//        return;
        foreach ($userList as $user) {
            $statusList = $this->getStatusListByUser($user['user_code']);
            echo $user['user_code'] . ': ' . implode(',', $statusList ? $statusList : array()) . "\n";
            if ($this->error->isError()) {
                echo "EHIS Query error ...\n";
            }
        }
        echo "--------------------------------------------------------------------------------\n";
        echo IsicDate::getCurrentTimeFormatted() . ": EHIS MassQuery End\n";
        echo "================================================================================\n";
    }

    public function checkUserStatusesBankCard($userList = false) {
        $this->statusRemoveType = 'expire';
        if (!$userList) {
            // $userList = $this->isicDbUserStatuses->getBankCardUsers(1, 0, '2013-08-20');
            $userList = $this->isicDbUserStatuses->getRegularCardUsers(0, '2013-08-01');
        }
        $this->setOverrideExternalCheckFlag(true);
        echo "================================================================================\n";
        echo IsicDate::getCurrentTimeFormatted() . ": EHIS MassQuery Start\n";
        echo 'User codes to query: ' . sizeof($userList) . "\n";
        echo "--------------------------------------------------------------------------------\n";
        foreach ($userList as $user) {
            $userCode = is_array($user) ? $user['user_code'] : $user;
            if (IsicPersonNumberValidator::isValid($userCode)) {
                $statusList = $this->getStatusListByUser($userCode);
                echo $userCode . ': ' . implode(',', $statusList ? $statusList : array());
                if ($this->error->isError()) {
                    echo "; EHIS Query error ...";
                }
            } else {
                echo $user['user_code'] . ': INVALID PERSON NUMBER';
            }
            echo "\n";
        }
        echo "--------------------------------------------------------------------------------\n";
        echo IsicDate::getCurrentTimeFormatted() . ": EHIS MassQuery End\n";
        echo "================================================================================\n";
    }

    public function getStatusExpirationDate() {
        if (!$this->statusExpirationDate) {
            /** @var IsicDB_GlobalSettings $settings */
            $settings = IsicDB::factory('GlobalSettings');
            $days = (int)$settings->getRecord('auto_user_status_expiration_days');
            $curTime = time();
            $curDay = date('j', $curTime);
            $curMon = date('n', $curTime);
            $curYear = date('Y', $curTime);
            $this->statusExpirationDate = IsicDate::getTimeStampFormatted(
                mktime(0, 0, 0, $curMon, $curDay + $days, $curYear), IsicDate::DB_DATE_FORMAT);
        }
        return $this->statusExpirationDate;
    }

    public function getStatusListByUser($personNumber) {
        $this->performQueryAndParseResult($personNumber);
        return $this->getStatusListFromParsedResult();
    }

    public function performQueryAndParseResult($personNumber) {
        $this->userInstance = $this->getEhisPersonInstance($personNumber);
        $this->queryInstance = $this->getEhisQueryInstance($this->userInstance);
        $this->queryResult = $this->getEhisQueryResult($this->queryInstance);
        return $this->getQueryResultParsed();
    }

    private function getQueryResultParsed() {
        if (!$this->queryResult) {
            return array();
        }
        $this->parsedResults = array();
        foreach ($this->queryResult->data as $data) {
            $this->parsedResults[] = $this->ehisStatus->getParsedResult($data);
        }
        return $this->parsedResults;
    }

    /**
     * @return array|bool
     */
    public function getStatusListFromParsedResult()
    {
        $statusIdList = false;
        foreach ($this->parsedResults as $parsedResult) {
            $statusIdList = $this->addUserStatuses($parsedResult);
            if ($this->statusRemoveType == 'expire') {
                $expirationIdList = $this->setExpirationForUserStatuses($parsedResult, $statusIdList ? $statusIdList : array());
            } else {
                $delIdList = $this->removeUserStatuses($parsedResult, $statusIdList ? $statusIdList : array());
            }
        }
        return $statusIdList;
    }

    private function getEhisPersonInstance($personNumber) {
        $user = new EYL_IsikParing();
        $user->isikukood = $personNumber;
        $user->eesnimi = null;
        $user->perenimi = null;
        $user->synni_kp = null;
        return $user;
    }

    private function getEhisQueryInstance($user) {
        $query = new eyl_isic_paring();
        $query->data[] = $user;
        return $query;
    }

    private function getEhisQueryResult($query) {
        $this->error->reset();
        $result = false;
        try {
            $result = $this->ehisClient->eyl_isic($query);
        } catch(Exception $ex) {
            $errorResult = $ex;
        }
        if (!$result || $errorResult) {
            $this->error->add('ehis_query');
        }
        $this->logEhisQueryResult($query, $result, $errorResult);
        return $result;
    }

    private function logEhisQueryResult($query, $result, $errorResult) {
        $logData = array(
            'module_name' => 'ehis_query',
            'record_id' => $this->isicDbUsers->getIdByUserCode($query->data[0]->isikukood),
            'event_type' => $this->error->isError() ? IsicDB_Logs::log_type_error : IsicDB_Logs::log_type_success,
            'event_body' => print_r($this->error->isError() ? $errorResult : $result, true),
        );
        $this->isicDbLogs->insertRecord($logData);
    }

    private function addUserStatuses($data) {
        if (!$data['person']['external_status_check_allowed'] && !$this->getOverrideExternalCheckFlag()) {
            return false;
        }
        $userStatusIdList = false;
        foreach ($this->getStatusesMerged($data) as $status) {
            $userStatuses = $this->isicDbUserStatuses->getAllAutomaticRecordsByGroupUser($status['group_id'],
                $data['person']['user_id']);
            $insUpdData = array(
                'user_id' => $data['person']['user_id'],
                'group_id' => $status['group_id'],
                'active' => 1,
                'course' => $status['course'] ? $status['course'] : '',
                'class' => $status['class'] ? $status['class'] : '',
                'position' => $status['position'] ? $status['position'] : '',
            );
            if (!$userStatuses) {
                $userStatusIdList[] = $this->isicDbUserStatuses->insertRecord($insUpdData);
            } else {
                $insUpdData['expiration_date'] = IsicDate::EMPTY_DATE;
                foreach ($userStatuses as $userStatus) {
                    $userStatusIdList[] = $userStatus['id'];
                    $savedClass = $insUpdData['class'];
                    $savedCourse = $insUpdData['course'];
                    if (!$this->isicDbSchools->shouldOverwriteStatusFields($userStatus['school_id'])) {
                        $insUpdData['class'] = $userStatus['class'];
                        $insUpdData['course'] = $userStatus['course'];
                    }
                    $this->isicDbUserStatuses->updateRecord($userStatus['id'], $insUpdData);
                    $insUpdData['class'] = $savedClass;
                    $insUpdData['course'] = $savedCourse;
                }
            }
        }
        return $userStatusIdList;
    }

    private function getStatusesMerged($data) {
        return array_merge($data['teach'], $data['study_general'], $data['study_university']);
    }

    private function removeUserStatuses($data, $validIdList) {
        return $this->isicDbUserStatuses->deactivateAllAutomaticRecordsByUserExceptGivenIds($data['person']['user_id'], $validIdList);
    }

    private function setExpirationForUserStatuses($data, $validIdList) {
        return $this->isicDbUserStatuses->setExpirationForAllAutomaticRecordsByUserExceptGivenIds(
            $data['person']['user_id'], $validIdList, IsicDB_UserStatuses::origin_ehis, $this->getStatusExpirationDate()
        );
    }

    /**
     * @return the $parsedResult
     */
    public function getParsedResults() {
        return $this->parsedResults;
    }

    /**
     * @return the $queryResult
     */
    public function getQueryResult() {
        return $this->queryResult;
    }

    /**
     * @return the $queryInstance
     */
    public function getQueryInstance() {
        return $this->queryInstance;
    }

    /**
     * @return the $ehisClient
     */
    public function getEhisClient() {
        return $this->ehisClient;
    }

    /**
     * @param $error the $error to set
     */
    public function setError($error) {
        $this->error = $error;
    }

    /**
     * @return the $error
     */
    public function getError() {
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

    /**
     * @return EhisStatus
     */
    public function getEhisStatus()
    {
        return $this->ehisStatus;
    }

    public function setStatusRemoveType($newType) {
        $this->statusRemoveType = $newType;
    }
}
