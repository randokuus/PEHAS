<?php

class IsicDB_Schools extends IsicDB {

    protected $table = 'module_isic_school';

    protected $insertableFields = array(
        'name', 'ehis_code', 'ehis_name', 'regcode', 'ehl_code'
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'ehis_code', 'name', 'hidden', 'region_id', 'ehl_code'
    );

    protected $hiddenSchoolId;

    public function listAllowedRecords() {
        $r = $this->db->query("
            SELECT
                `s`.*
            FROM
                ?f AS `s`,
                ?f AS `g`
            WHERE
                `s`.`id` = `g`.`isic_school` AND
                `g`.`id` IN (!@)
            GROUP BY
                `s`.`id`
            ORDER BY
                `s`.?f
            ",
            $this->table,
            'module_user_groups',
            $this->getIdsAsArray($this->usergroups),
            'name'
        );
        self::assertResult($r);
        return $r->fetch_all();
    }

    public function getRecordByEhisCode($ehis_code) {
        $r = $this->findRecords(array('ehis_code' => $ehis_code), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getRecordByEhlCode($code) {
        $r = $this->findRecords(array('ehl_code' => $code), 0, 1);
        return is_array($r) && count($r) == 1 ? $r[0] : false;
    }

    public function getAllowedIdList() {
        $idList = array();
        $schools = $this->listAllowedRecords();
        foreach ($schools as $school) {
            $idList[] = $school['id'];
        }
        return $idList;
    }

    public function isJoined($idOrData) {
        if (!is_array($idOrData)) {
            $schoolData = $this->getRecord($idOrData);
        } else {
            $schoolData = $idOrData;
        }
        return ($schoolData && $schoolData['joined']);
    }

    public function getActiveRecordsByIds($schoolIds) {
        $schoolList = $this->getRecordsByIds($schoolIds);
        foreach ($schoolList as $schoolIndex => $schoolData) {
            if (!$schoolData["active"]) {
                unset($schoolList[$schoolIndex]);
            }
        }
        return $schoolList;
    }

    public function isActive($schoolId) {
        $schoolData = $this->getRecord($schoolId);
        return $schoolData["active"];
    }

    public function getIdListWithEhisCheckOnGivenDate($date) {
        $r = $this->db->query("
            SELECT
                `s`.*
            FROM
                ?f AS `s`
            WHERE
                `s`.`ehis_check` = 1
            ",
            $this->table
        );
        self::assertResult($r);
        $idList = array(0);
        while ($data = $r->fetch_assoc()) {
            $ruleDates = $this->getDatesFromRule($data['ehis_check_rule']);
            if (!$data['ehis_check_rule'] || in_array($date, $ruleDates)) {
                $idList[] = $data['id'];
            }
        }
        return $idList;
    }

    public function getDatesFromRule($rule) {
        $dates = array();
        if ($rule) {
            $tmpDates = explode(';', str_replace(' ', '', $rule));
            foreach ($tmpDates as $date) {
                $tmpDateParts = explode('/', $date);
                $tmpDate = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, $tmpDateParts[0], $tmpDateParts[1], date('Y')), 'Y-m-d');
                if (IsicDate::EMPTY_DATE != $tmpDate) {
                    $dates[] = $tmpDate;
                }
            }
        }
        return $dates;
    }

    public function getHiddenSchoolId() {
        if (!isset($this->hiddenSchoolId)) {
            $this->hiddenSchoolId = 0;
            $data = $this->findRecord(array('hidden' => 1));
            if ($data) {
                $this->hiddenSchoolId = $data['id'];
            }
        }
        return $this->hiddenSchoolId;
    }

    public function isHiddenSchool($schoolId) {
        return ($this->getHiddenSchoolId() && $this->getHiddenSchoolId() == $schoolId);
    }

    public function isExternalCheckNeeded($schoolId) {
        return true;
    }

    public function getIdListByRegion($regionId) {
        $idList = array(0);
        $schools = $this->findRecords(array('region_id' => $regionId));
        foreach ($schools as $school) {
            $idList[] = $school['id'];
        }
        return $idList;
    }

    public function getAllActiveRecords() {
        $schoolList = $this->listRecordsFields(array('id', 'name', 'active', 'ehl_code'));
        foreach ($schoolList as $schoolIndex => $schoolData) {
            if (!$schoolData["active"] || $this->isEhlRegion($schoolData)) {
                unset($schoolList[$schoolIndex]);
            }
        }
        return $schoolList;
    }

    public function isEhlRegion($data) {
        return
            is_array($data) &&
            array_key_exists('ehl_code', $data) &&
            strlen($data['ehl_code']) > 0 &&
            substr($data['ehl_code'], 0, 1) == 'R'
        ;
    }

    public function shouldOverwriteStatusFields($schoolId) {
        $schoolData = $this->getRecord($schoolId);
        return $schoolData['ehis_overwrite_status_fields'] == 1;
    }
}
