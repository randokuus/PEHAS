<?php

class WSCard {
    const USER_TYPE_USER = 2; // regular user
    var $db = false;
    var $_partner = false;
    var $cardNumberTypeField = array(
            1 => 'isic_number',
            2 => 'chip_number',
            3 => 'id'
        );

    function WSCard($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }

    /**
     * Get Card ID
     *
     * @param string $card_id Card number
     * @param int $number_type number type: 1 - card serial number, 2 - card chip number
     * @return int card id
    */
    function getId($card_id, $number_type) {
        $data = $this->getCardRecord($card_id, $number_type);
        if ($data) {
            return $data['id'];
        }
        return false;
    }

    function getCardRecord($card_number, $number_type) {
        $res =& $this->db->query("
            SELECT
                `module_isic_card`.`id`,
                `module_isic_card`.`id` AS `card_id`,
                `module_isic_card`.`isic_number`,
                `module_isic_card`.`chip_number`,
                `module_isic_card`.`active`,
                `module_isic_card`.`expiration_date`,
                `module_isic_card_validities`.`user_status_active`
            FROM
                `module_isic_card`,
                `module_isic_card_validities`
            WHERE
                `module_isic_card`.`id` = `module_isic_card_validities`.`card_id` AND
                `module_isic_card`.`!` = ? AND
                (`module_isic_card_validities`.`school_id` IN (!) OR ? = '0') AND
                `module_isic_card`.`type_id` IN (!)
            ORDER BY
                `module_isic_card`.`active` DESC,
                `module_isic_card`.`id` DESC
            ",
            $this->cardNumberTypeField[$number_type],
            $card_number,
            $this->_partner->getSchools(),
            $this->_partner->getSchools(),
            $this->_partner->getCardTypes()
        );
        $cardList = $this->getCardRecordListFromQueryResult($res);
        return current($cardList);
    }

    function isActive($data) {
        return ($data["active"] && $data['user_status_active']) && strtotime($data["expiration_date"]) > time();
    }

    function getPersonCards($person_number) {
        $res =& $this->db->query("
            SELECT
                `module_isic_card`.`id` AS `card_id`,
                `module_isic_card`.`isic_number`,
                `module_isic_card`.`chip_number`,
                `module_isic_card`.`active`,
                `module_isic_card`.`expiration_date`,
                `module_isic_card`.`person_name_first`,
                `module_isic_card`.`person_name_last`,
                `module_isic_card`.`person_email`,
                `module_isic_card`.`person_phone`,
                `module_isic_card`.`person_number`,
                `module_isic_card`.`pan_number`,
                `module_isic_card_validities`.`user_status_active`,
                `module_isic_card_type`.`name` AS `card_type_name`,
                `module_isic_card_type`.`chip` AS `card_type_chip`
            FROM
                `module_isic_card`,
                `module_isic_card_type`,
                `module_isic_card_validities`
            WHERE
                `module_isic_card`.`type_id` = `module_isic_card_type`.`id` AND
                `module_isic_card`.`id` = `module_isic_card_validities`.`card_id` AND
                `module_isic_card`.`person_number` = ? AND
                (`module_isic_card_validities`.`school_id` IN (!) OR ? = '0') AND
                `module_isic_card`.`type_id` IN (!)
            ",
            $person_number,
            $this->_partner->getSchools(),
            $this->_partner->getSchools(),
            $this->_partner->getCardTypes()
        );
        return $this->getCardRecordListFromQueryResult($res);
    }

    function getActivatedDeactivatedExpiredCardsSinceDate($from, $until) {
        $res =& $this->db->query("
            SELECT
                `module_user_users`.`user`,
                `module_isic_card`.`id` AS `card_id`,
                `module_isic_card`.`isic_number`,
                `module_isic_card`.`person_number`,
                `module_isic_card`.`person_name_first`,
                `module_isic_card`.`person_name_last`,
                `module_isic_card`.`person_email`,
                `module_isic_card`.`person_phone`,
                `module_isic_card`.`chip_number`,
                `module_isic_card`.`active`,
                `module_isic_card_validities`.`user_status_active`,
                `module_isic_card`.`expiration_date`,
                `module_isic_card`.`activation_date`,
                DATE(`module_isic_card`.`deactivation_time`) AS `deactivation_date`,
                `module_isic_card_type`.`name` AS `card_type_name`,
                `module_isic_card_kind`.`name` AS `card_kind_name`,
                `module_isic_school`.`name` AS `school_name`,
                `module_isic_school`.`passcode` AS `school_passcode`,
                `module_isic_card`.`exported` AS `export_time`,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS `bank_name`
            FROM
                `module_isic_card`
                LEFT JOIN
                    `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`,
                `module_user_users`,
                `module_isic_card_validities`,
                `module_isic_card_type`,
                `module_isic_card_kind`,
                `module_isic_school`
            WHERE
                `module_isic_card`.`exported` > '0000-00-00 00:00:00' AND
                `module_isic_card`.`person_number` = `module_user_users`.`user_code` AND
                `module_isic_card`.`id` = `module_isic_card_validities`.`card_id` AND
                `module_isic_card`.`moddate` >= ? AND
                `module_isic_card`.`moddate` <= ? AND
                (`module_isic_card_validities`.`school_id` IN (!) OR ? = '0') AND
                `module_isic_card`.`type_id` IN (!) AND
                `module_user_users`.`user_type` = ? AND
                `module_isic_card`.`type_id` = `module_isic_card_type`.`id` AND
                `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id` AND
                `module_isic_card`.`school_id` = `module_isic_school`.`id`
            ORDER BY
                `module_isic_card`.`id` ASC",
            $from,
            $until,
            $this->_partner->getSchools(),
            $this->_partner->getSchools(),
            $this->_partner->getCardTypes(),
            self::USER_TYPE_USER
        );
        return $this->getCardRecordListFromQueryResult($res);
    }

	private function getCardRecordListFromQueryResult($res) {
        $cardList = array();
        while ($data = $res->fetch_assoc()) {
            $cardId = $data['card_id'];
            // if card is already in array and validity-based status is not active, assigning already existing
            // status value from cardlist array to current data-array so we could get any active value if it exists
            if (array_key_exists($cardId, $cardList) && !$data['user_status_active']) {
                $data['user_status_active'] = $cardList[$cardId]['user_status_active'];
            }
            $cardList[$cardId] = $data;
        }
        return $cardList;
    }
}
