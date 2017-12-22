<?php

class WSLockEvent {
    var $db = false;
    var $_partner = false;
    
   /**
     * list of possible lock access directions
     *
     * @var $_lock_access_dir
     * @access protected
     */
    var $_lock_access_dir = array("in" => 1, "out" => 2);

   /**
     * list of possible lock access accesses
     *
     * @var $_lock_access_access
     * @access protected
     */
    var $_lock_access_access = array("no" => 1, "yes" => 2);
    
    function WSLockEvent($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }
    
    function getLocationMaxEventId() {
        $res =& $this->db->query("
            SELECT 
                event_id AS max_event
            FROM
                `module_isic_bypass_event`
            WHERE
                `school_id` = !
            ORDER BY
                `event_id` DESC 
            LIMIT 1",
            $this->_partner->getLocationId()
        );
        $data = $res->fetch_assoc();
        if ($data) {
            return $data['max_event'];
        } 
        return 0;
    }
    
    function getDirId($name) {
        return $this->_lock_access_dir[$name];
    }
    
    function getAccessId($name) {
        return $this->_lock_access_access[$name];
    }
    
    function saveLockEventIfUnique($data) {
        if ($this->isLockEventUnique($data['event'])) {
            $this->saveLockEvent($data);
            return true;
        }
        return false;
    }
    
    function isLockEventUnique($event) {
        $res =& $this->db->query("
            SELECT 
                *
            FROM
                `module_isic_bypass_event`
            WHERE
                `school_id` = ! AND
                `event_id` = ?",
            $this->_partner->getLocationId(), $event
        );
        return $res->num_rows() == 0;        
    }
    
    function saveLockEvent($data) {
        $res =& $this->db->query("
            INSERT INTO 
                `module_isic_bypass_event`
            (
                `school_id`, 
                `event_id`, 
                `lock_id`, 
                `card_id`, 
                `event_time`, 
                `direction`, 
                `access`, 
                `add_date`
            ) VALUES (
                !, !, !, !, ?, !, !, NOW()
            )",
            $this->_partner->getLocationId(), 
            $data['event'], 
            $data['lock'], 
            $data['card'], 
            $data['time'], 
            $data['dir'], 
            $data['access']
        );
    }    
}
