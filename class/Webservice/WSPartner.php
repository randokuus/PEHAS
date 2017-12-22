<?php
require_once(SITE_PATH . "/class/Webservice/WSIp.php");

class WSPartner {
    var $db = false;

    /**
     * Partner ID
     *
     * @var _id
     * @access protected
     */
    var $id = 0;
    var $receivedId = 0;
    var $locationId = 0;
    var $username = '';
    var $password = '';
    var $ipAllowed = false;
    var $passwordCorrect = false;
    var $schools = '';
    var $cardTypes = '';
    var $active = false;

    function WSPartner($id = 0, $locationId = 0) {
        $this->id = $id;
        $this->locationId = $locationId;
        $this->initVariables();
    }

    function initVariables() {
        $this->db = &$GLOBALS['database'];
        $this->username = $_SERVER['PHP_AUTH_USER'];
        $this->password = $_SERVER['PHP_AUTH_PW'];
        $currentIp = $_SERVER['REMOTE_ADDR'];

        $data = $this->getRecordFromDatabase();
        if ($data) {
            $this->setId($data['provider_id']);
            $this->setLocationId($data['id']);
            $this->setSchools($data['school_list']);
            $this->setCardTypes($data['card_type_list']);
            $this->setActive($data['active']);
            $this->ipAllowed = WSIp::isIpAllowed(explode(',', $data['allowed_ip']), $currentIp);
            $this->passwordCorrect = ($data['password'] == $this->password);
        }
    }

    function getRecordFromDatabase() {
        $res =& $this->db->query("
            SELECT
                *
            FROM
                `module_isic_service_location`
            WHERE
                `username` = ?
            LIMIT 1",
            $this->username
        );

        return $res->fetch_assoc();
    }

    /**
     * Changes partner ID
     *
     * @param string $id partner id
    */
    function setId($id = '') {
        $this->id = $id;
    }

    /**
     * Changes location ID
     *
     * @param string $id location id
    */
    function setLocationId($id = '') {
        $this->locationId = $id;
    }

    function isIpAllowed() {
        return $this->ipAllowed;
    }

    function getId() {
        return $this->id;
    }

    function getLocationId() {
        return $this->locationId;
    }

    function isAuthenticationCorrect() {
        return $this->passwordCorrect;
    }

    /**
     * @param $schools the $schools to set
     */
    function setSchools($schools) {
        $schoolList = explode(',', $schools);
        if (in_array(0, $schoolList)) {
            $this->schools = 0;
        } else {
            $this->schools = $schools;
        }
    }

    /**
     * @return the $schools
     */
    function getSchools() {
        return $this->schools;
    }

    /**
     * @param $receivedId the $receivedId to set
     */
    function setReceivedId($receivedId) {
        $this->receivedId = $receivedId;
    }

    /**
     * @return the $receivedId
     */
    function getReceivedId() {
        return $this->receivedId;
    }

    /**
     * @param $cardTypes the $cardTypes to set
     */
    public function setCardTypes($cardTypes) {
        $this->cardTypes = $cardTypes ? $cardTypes : '-1';
    }

    /**
     * @return the $cardTypes
     */
    public function getCardTypes() {
        return $this->cardTypes;
    }

    /**
     * @param $active the $active to set
     */
    public function setActive($active) {
        $this->active = $active;
    }

    /**
     * @return the $active
     */
    public function isActive() {
        return $this->active;
    }
}
