<?php

class IsicDB_Banks extends IsicDB {

    protected $table = 'module_isic_bank';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
    );

    public function getBankList($allBanksTitle = '', $onlyFilter = true) {
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_bank`.*
            FROM
                `module_isic_bank`
            ORDER BY
                `module_isic_bank`.`id`
            ');

        if ($allBanksTitle) {
            $list[0] = $allBanksTitle;
        }
        while ($data = $r->fetch_assoc()) {
            if ($onlyFilter && $data['show_in_filter'] || !$onlyFilter) {
                $list[$data["id"]] = $data["name"];
            }
        }
        return $list;
    }
}
