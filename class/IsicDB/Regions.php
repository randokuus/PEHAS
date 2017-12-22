<?php

class IsicDB_Regions extends IsicDB {

    protected $table = 'module_isic_region';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
    );

    public function getRecordsBySchoolIds($schoolIds) {
        return $this->db->fetch_all('
            SELECT
                `r`.*
            FROM
                ?f AS `r`,
                `module_isic_school` AS `s`
            WHERE
                `r`.`id` = `s`.`region_id` AND
                `s`.`id` IN (?@)
            GROUP BY
                `r`.`id`
            ORDER BY
                `r`.`name`
            ', $this->table, $schoolIds
        );
    }
}
