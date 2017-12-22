<?php

class WSEvent {
    var $_partner = false;
    var $db = false;

   /**
     * list of possible events
     *
     * @var _$eventList
     * @access protected
     */
    var $_eventList = array(
        "card_valid",
        "card_list",
        "lock_list",
        "lock_access",
        "last_event",
        "person_valid_card",
        "device_list",
        "register_sale",
        'status_list',
        'person_status_list',
        'person_picture',
        'person_valid_pan'
    );

    function WSEvent($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }

    function isValidEventName($eventName) {
        return in_array($eventName, $this->_eventList);
    }

    function saveRegisterSaleRecordIfUnique($data) {
        if ($this->isSaleRecordEventUnique($data['device'], $data['event'])) {
            $this->saveSaleRecordEvent($data);
            return true;
        }
        return false;
    }

    function isSaleRecordEventUnique($device, $event) {
        $res =& $this->db->query("
            SELECT
                `id`
            FROM
                `module_isic_service_event`
            WHERE
                `location_id` = ! AND
                `device_id` = ! AND
                `event_id` = ?",
            $this->_partner->getLocationId(),
            $device,
            $event
        );
        return $res->num_rows() == 0;
    }

    function saveSaleRecordEvent($data) {
        $res =& $this->db->query("
            INSERT INTO
                `module_isic_service_event`
            (
                `location_id`,
                `event_id`,
                `device_id`,
                `card_id`,
                `event_time`,
                `sale_sum`,
                `discount_sum`,
                `currency`,
                `add_date`
            ) VALUES (
                !, !, !, !, ?, !, !, ?, NOW())",
            $this->_partner->getLocationId(),
            $data['event'],
            $data['device'],
            $data['card'],
            $data['time'],
            $data['sale_sum'],
            $data['discount_sum'],
            $data['currency']
        );
    }
}
