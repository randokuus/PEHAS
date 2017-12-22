<?php

class IsicDB_SchoolCompensations extends IsicDB {

    protected $table = 'module_isic_school_compensation';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'school_id', 'status_id', 'start_date', 'end_date'
    );

    public function getRecordBySchoolCardType($schoolId, $cardTypeId) {
        $r = $this->db->query("
            SELECT
                `sc`.*
            FROM
                ?f AS `sc`,
                ?f AS `us`
            WHERE
                `sc`.`school_id` = ? AND
                `sc`.`status_id` = `us`.`id` AND
                FIND_IN_SET(?, `us`.`card_types`) AND
                `sc`.`start_date` <= ? AND
                `sc`.`end_date` >= ?
            ORDER BY `start_date` ASC
            LIMIT 1",
            $this->table,
            'module_user_status',
            $schoolId,
            $cardTypeId,
            $this->db->now(),
            $this->db->now()
        );
        self::assertResult($r);
        return $r->fetch_assoc();
    }

    public function getUsedCompensationSum($compensationId, $personNumber) {
        $r = $this->db->query("
            SELECT
                SUM(`p`.`payment_sum`) AS `used_sum`
            FROM
                ?f AS `p`
            WHERE
                `p`.`compensation_id` = ! AND
                `p`.`person_number` = ?
            ",
            'module_isic_payment',
            $compensationId,
            $personNumber
        );
        self::assertResult($r);
        $data = $r->fetch_assoc();
        return $data['used_sum'];
    }

    public function getCompensationDataByPersonSchoolCardType($personNumber, $schoolId, $cardTypeId) {
        $result = array(
            'sum' => 0,
            'id' => 0,
            'compensation_types' => array()
        );
        $data = $this->getRecordBySchoolCardType($schoolId, $cardTypeId);
        if ($data) {
            $usedSum = $this->getUsedCompensationSum($data['id'], $personNumber);
            $dbCardType = IsicDB::factory('CardTypes');
            $cardYears = $dbCardType->getExpirationYears($cardTypeId);
            $dbCurrency = IsicDB::factory('Currency');
            $availableSum = $cardYears * $dbCurrency->getSumInDefaultCurrency($data["sum"], $data["currency"]) - $usedSum;
            $result['sum'] = $availableSum > 0 ? $availableSum : 0;
            $result['id'] = $data['id'];
            $result['compensation_types'] = explode(',', $data['compensation_type_list']);
        }
        return $result;
    }
}
