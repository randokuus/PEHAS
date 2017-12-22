<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");

class EhisStatus {
    private $clientResult = null;
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
    private $statusIdList = false;

    const status_teach = 'teach';
    const status_study_general = 'study_general';
    const status_study_university = 'study_university';

    public function __construct() {
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->assignStatusIdList();
    }

    private function assignStatusIdList() {
        $this->statusIdList = array(
            self::status_teach => $this->isicDbUserStatusTypes->getRecordIdByEhisName(self::status_teach),
            self::status_study_general => $this->isicDbUserStatusTypes->getRecordIdByEhisName(self::status_study_general),
            self::status_study_university => $this->isicDbUserStatusTypes->getRecordIdByEhisName(self::status_study_university)
        );
    }

    public function getParsedResult($clientResult) {
        $this->clientResult = $clientResult;
        return array(
            'person' => $this->getPerson(),
            'teach' => $this->getStatusTeach(),
            'study_general' => $this->getStatusStudyGeneral(),
            'study_university' => $this->getStatusStudyUniversity()
        );
    }

    private function getPerson() {
        $personData = array(
            'user_code' => @$this->clientResult->isikukood,
            'name_first' => @$this->clientResult->eesnimi,
            'name_last' => @$this->clientResult->perenimi,
            'birthday' => @$this->clientResult->synni_kp,
        );
        if ($personData['user_code']) {
            $userData = $this->isicDbUsers->getRecordByCode($personData['user_code']);
            if ($userData) {
                $personData['user_id'] = $userData['user'];
                $personData['external_status_check_allowed'] = $userData['external_status_check_allowed'];
            }
        }
        return $personData;
    }

    private function getStatusTeach() {
        return $this->getStatus($this->clientResult->opetamine, self::status_teach);
    }

    private function getStatusStudyGeneral() {
        return $this->getStatus($this->clientResult->oppimine_yld, self::status_study_general);
    }

    private function getStatusStudyUniversity() {
        return $this->getStatus($this->clientResult->oppimine_korg, self::status_study_university);
    }

    private function getStatus($data, $type) {
        $result = array();
        $count = count(@$data);
        for ($i = 0; $i < $count; $i++) {
            if (is_array($data)) {
                $result[] = $this->parseStatus($data[$i], $type);
            } else if ($data != null) {
                $result[] = $this->parseStatus($data, $type);
            }
        }
        return $result;
    }

    private function parseStatus($data, $type) {
        $result = $this->parseSchool($data);
        $result['status_id'] = $this->statusIdList[$type];
        $result['group_id'] = $this->getGroupId($result['school_id'], $result['status_id']);

        switch ($type) {
            case self::status_study_general: // falls through
            case self::status_study_university:
                $result = array_merge($result,
                    array(
                        'class' => @$data->klass,
                        'ok_code' => @$data->ok_kood,
                        'ok_name' => @$data->ok_nimetus,
                        'studyType' => @$data->oppevorm,
                        'load' => @$data->koormus,
                        'course' => @$data->kursus,
                    )
                );
            break;
            case self::status_teach:
                $result = array_merge($result,
                    array(
                        'position' => @$data->ametikohad->ametikoht,
                    )
                );
            break;
        }
        return $result;
    }

    private function parseSchool($data) {
        $schoolData = array(
            'school_name' => @$data->oas_nimetus,
            'school_ehis_code' => @$data->oas_id,
            'school_regno' => @$data->oas_regnr,
        );
        if ($schoolData['school_ehis_code']) {
            $schoolData['school_id'] = $this->getSchoolId($schoolData);
        }
        return $schoolData;
    }

    private function getSchoolId($schoolData) {
        $school = $this->isicDbSchools->getRecordByEhisCode($schoolData['school_ehis_code']);
        if (!$school) {
            $newSchool = array(
                'name' => $schoolData['school_name'],
                'ehis_name' => $schoolData['school_name'],
                'ehis_code' => $schoolData['school_ehis_code'],
                'regcode' => $schoolData['school_regno'] ? $schoolData['school_regno'] : ''
            );
            $schoolId = $this->isicDbSchools->insertRecord($newSchool);
            $school = $this->isicDbSchools->getRecord($schoolId);
        }
        return $school['id'];
    }

    private function getGroupId($schoolId, $statusId) {
        // creating both groups (manual and automatic) if not existing
        $groupManual = $this->isicDbUserGroups->getRecordBySchoolStatusAutomaticAndAddIfNotFound($schoolId, $statusId, 0, 1);
        $groupAutomatic = $this->isicDbUserGroups->getRecordBySchoolStatusAutomaticAndAddIfNotFound($schoolId, $statusId, 1, 1);
        return $groupAutomatic['id'];
    }

    public function getStatusBySchoolAndCardType($resultList, $schoolId, $cardTypeId) {
        $cardTypeStatuses = $this->isicDbUserStatusTypes->getIdListByCardType($cardTypeId);
        if (!$cardTypeStatuses || count($cardTypeStatuses) == 0) {
            return null;
        }

        // Iterating over all given query results
        foreach ($resultList as $result) {
            // iteraing over all possible statuses
            foreach ($this->statusIdList as $statusName => $statusId) {
                // iterating over
                foreach ($result[$statusName] as $status) {
                    if ($schoolId == $status['school_id'] &&
                        in_array($status['status_id'], $cardTypeStatuses)
                    ) {
                        $statusFound = array(
                            'person' => $result['person'],
                            'status' => $status
                        );
                        return $statusFound;
                    }
                }
            }
        }
    }
}
