<?php

class WSLock {
    var $_partner = false;
    var $db = false;
    
    function WSLock($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }
    
    
    /**
     * Get Lock ID
     *
     * @param string $id lock ID
     * @return int lock id
    */
    function getId($id) {
        $res =& $this->db->query("
            SELECT
                `id`
            FROM
                `module_isic_bypass_lock`
            WHERE
                `school_id` = ! AND
                `lock_id` = ?",
            $this->_partner->getLocationId(), $id
        );

        if ($data = $res->fetch_assoc()) {
            return $data["id"];
        }
        return false;
    }
    
    function deactivateUnlistedLocks($lockIdList) {
        $activeLocks = $this->getLocationActiveLocks();
        foreach ($activeLocks as $data) {
            if (!in_array($data["id"], $lockIdList)) {
                $this->deactivateLock($data['id']);
            }
        }
    }
    
    function getLocationActiveLocks() {
        $res =& $this->db->query("
            SELECT 
                `id`
            FROM
                `module_isic_bypass_lock`
            WHERE
                `school_id` = ! AND
                `active` = 1",
            $this->_partner->getLocationId()
        );
        if ($res->num_rows()) {
            return $res->fetch_all();
        }
        return array();
    }
    
    function deactivateLock($id) {
        $res2 =& $this->db->query("
            UPDATE
                `module_isic_bypass_lock`
            SET
                `active` = 0
            WHERE
                `id` = !",
            $id
        );
    }
        
    function saveLockRecord($param) {
        $lockRecord = $this->getLockRecord($param);
        if ($lockRecord) {
            return $this->updateLockRecord($lockRecord, $param);
        } else {
            return $this->insertLockRecord($param);
        }        
    }
    
    function getLockRecord($param) {
        $res =& $this->db->query("
            SELECT 
                *
            FROM
                `module_isic_bypass_lock`
            WHERE
                `school_id` = ! AND
                `lock_id` = ?",
            $this->_partner->getLocationId(), $param["lock_id"]
        );
        return $res->fetch_assoc();
    }
    
    function updateLockRecord($data, $param) {
        $res =& $this->db->query("
            UPDATE
                `module_isic_bypass_lock`
            SET
                `name` = ?,
                `description` = ?,
                `active` = 1
            WHERE
                `id` = !",
            $param["name"], $param["description"], $data["id"]
        );
        return $data['id'];
    }
    
    function insertLockRecord($param) {
        $res =& $this->db->query("
            INSERT INTO `module_isic_bypass_lock`
            (`school_id`, `lock_id`, `name`, `description`, `active`)
            VALUES (!, ?, ?, ?, 1)",
            $this->_partner->getLocationId(), $param["lock_id"], $param["name"], $param["description"]
        );
        return $this->db->insert_id();
    }    
}
