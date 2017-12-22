<?php

class WSPerson {
    var $db = false;
    var $_partner = false;

    function WSPerson($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }

    public function getPersonRecord($person_number) {
        $res =& $this->db->query("
            SELECT
                `module_user_users`.*
            FROM
                `module_user_users`
            WHERE
                `module_user_users`.`user_code` = ? AND
                `module_user_users`.`active` = 1 AND
                `module_user_users`.`user_type` = 2
            ",
            $person_number
        );
        return $this->getRecordFromQueryResult($res);
    }

    function isActive($data) {
        return $data["active"];
    }

	private function getRecordFromQueryResult($res) {
        $personData = array();
        if ($data = $res->fetch_assoc()) {
            $data['picture_data'] = $this->getPictureData($data['pic']);
            $personData[] = $data;
        }
        return $personData;
    }

    private function getPictureData($picPath = '') {
        $picData = false;
        if ($picPath && is_file(SITE_PATH . $picPath) && is_readable(SITE_PATH . $picPath)) {
            $picData = chunk_split(base64_encode(file_get_contents(SITE_PATH . $picPath)), 64, "\n");
        }
        return $picData;
    }
}
