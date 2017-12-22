<?php

class WSUserStatus {
    var $_partner = false;
    var $db = false;

    public function WSUserStatus($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }

    public function getList() {
        $res =& $this->db->query("
            SELECT
                `id`,
                `name`
            FROM
                `module_user_status`
        ");
        return $res->fetch_all();
    }

    public function isActive($data) {
        return $data["status_active"];
    }

    public function getUserStatusesSinceDate($from) {
        $res =& $this->db->query("
            SELECT
                `module_user_users`.`user_code` AS `person_number`,
                `module_isic_school`.`ehis_code` AS `school_ehis_code`,
                `module_user_status_user`.`user_id`,
                `module_user_status_user`.`school_id`,
                `module_user_status_user`.`status_id`,
                `module_user_status_user`.`faculty`,
                `module_user_status_user`.`class`,
                `module_user_status_user`.`course`,
                `module_user_status_user`.`position`,
                `module_user_status_user`.`structure_unit`,
                `module_user_status_user`.`active` AS `status_active`
            FROM
                `module_user_status_user`,
                `module_user_users`,
                `module_isic_school`
            WHERE
                `module_user_status_user`.`user_id` = `module_user_users`.`user` AND
                `module_user_status_user`.`school_id` = `module_isic_school`.`id` AND
                (`module_user_status_user`.`addtime` >= ? OR
                 `module_user_status_user`.`modtime` >= ?) AND
                `module_user_status_user`.`school_id` IN (!)
            ORDER BY
            	`module_user_status_user`.`active` DESC,
                `module_user_status_user`.`addtime` DESC",
            $from,
            $from,
            $this->_partner->getSchools()
        );
        return $this->getRecordListFromQueryResult($res);
    }

    private function getRecordListFromQueryResult($res) {
        $statusList = array();
        while ($data = $res->fetch_assoc()) {
            // only last status for every user + status + school combination
            $key = $data['user_id'] . '.' . $data['school_id'] . '.' . $data['status_id'];
            if (!array_key_exists($key, $statusList)) {
                $statusList[$key] = $data;
            }
        }
        return $statusList;
    }
}
