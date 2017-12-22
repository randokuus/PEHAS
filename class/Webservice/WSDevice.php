<?php

class WSDevice {
    var $_partner = false;
    var $db = false;
    
    function WSDevice($partner) {
        $this->db = &$GLOBALS['database'];
        $this->_partner = $partner;
    }
    
    /**
     * Get Device ID
     *
     * @param string $id device ID
     * @return int device id
    */
    function getId($id) {
        $res =& $this->db->query("
            SELECT
                `id`
            FROM
                `module_isic_service_device`
            WHERE
                `location_id` = ! AND
                `device_id` = ?",
            $this->_partner->getLocationId(), $id
        );

        if ($data = $res->fetch_assoc()) {
            return $data["id"];
        }
        return false;
    }
    
    function deactivateUnlistedDevices($device_id) {
        $activeDevices = $this->getLocationActiveDevices();
        foreach ($activeDevices as $data) {
            if (!in_array($data["id"], $device_id)) {
                $this->deactivateDevice($data['id']);
            }
        }
    }
    
    function getLocationActiveDevices() {
        $res =& $this->db->query("
            SELECT 
                `id`
            FROM
                `module_isic_service_device`
            WHERE
                `location_id` = ! AND
                `active` = 1",
            $this->_partner->getLocationId()
        );
        if ($res->num_rows()) {
            return $res->fetch_all();
        }
        return array();
    }
    
    function deactivateDevice($id) {
        $res =& $this->db->query("
            UPDATE
                `module_isic_service_device`
            SET
                `active` = 0
            WHERE
                `id` = !",
            $id
        );
    }
    
    function saveDeviceRecord($param) {
        $deviceRecord = $this->getDeviceRecord($param);
        if (is_array($deviceRecord)) {
            return $this->updateDeviceRecord($deviceRecord, $param);
        } else {
            return $this->insertDeviceRecord($param);
        }
    }    
    
    function getDeviceRecord($param) {
        $res =& $this->db->query("
            SELECT 
                *
            FROM
                `module_isic_service_device`
            WHERE
                `location_id` = ! AND
                `type_id` = ! AND
                `device_id` = ?",
            $this->_partner->getLocationId(), $param["type_id"], $param["device_id"]
        );
        return $res->fetch_assoc();
    }
    
    function updateDeviceRecord($data, $param) {
        $res =& $this->db->query("
            UPDATE
                `module_isic_service_device`
            SET
                `name` = ?,
                `description` = ?,
                `active` = 1
            WHERE
                `id` = !",
            $param["name"], $param["description"], $data["id"]
        );
        return $data["id"];
    }
    
    function insertDeviceRecord($param) {
        $res =& $this->db->query("
            INSERT INTO 
                `module_isic_service_device`
                (`location_id`, `type_id`, `device_id`, `name`, `description`, `active`)
            VALUES 
                (!, !, ?, ?, ?, 1)",
            $this->_partner->getLocationId(), $param["type_id"], $param["device_id"], $param["name"], $param["description"]
        );
        return $this->db->insert_id();
    }    
}
