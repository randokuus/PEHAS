<?php
require_once(SITE_PATH . "/class/cHTTP.php");

class WSMessage {
    var $partner;
    var $version;
    var $crypto;
    
    function WSMessage($partner, $version, $crypto) {
        $this->partner = $partner;
        $this->version = $version;
        $this->crypto = $crypto;
    }
    
    /**
     * Creates a message for sending to other server
     *
     * @param string $event eventname
     * @param int $reqId request id for current messge
     * @param array $parameters array with parameter name and value pairs
     * @return string xml-formatted message
    */
    function createMessage($event = '', $reqId = 0, $parameters_list = array()) {
        $message = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
        $message .= "<isicmsg ver=\"" . $this->version->getVersion() . "\" partnerid=\"" . $this->partner->getId() . "\" event=\"" . $event . "\" reqid=\"" . $reqId . "\">\n";
        $message .= $this->createBody($parameters_list);
        $message .= "</isicmsg>\n";
        return $message;
    }

    function createBody($parameters_list) {
        $message = '';
        foreach ($parameters_list as $parameters) {
            foreach ($parameters as $name => $value) {
                $message .= $this->createRow($name, $value);
            }
        }
        return $message;
    }
    
    function createRow($name, $value) {
        $message = '';
        if (is_array($value)) {
            $message .= "<" . $name . ">\n";
            foreach ($value as $vname => $vvalue) {
                $message .= $this->createParamElement($vname, $vvalue);
            }
            $message .= "</" . $name . ">\n";
        } else {
            $message .= $this->createParamElement($name, $value);
        }
        return $message;
    }

    function createParamElement($name, $value) {
        return "<param name=\"" . $this->crypto->encryptData($name) . "\" value=\"" . $this->crypto->encryptData($value) . "\" />\n";
    }
    
    /**
     * Sends message to given address
     *
     * @param string $target target address
     * @param string $message message body
     * @return string xml-formatted answer from target
    */
    function sendMessage($target = '', $parameter = '', $message = '') {
        if (!$target || !$message) {
            return false;
        }
        $http = new cHTTP();
        $http->clearFields();
//        $http->addField($parameter, $message);
        $http->addFieldRaw($message);
        $http->postPage($target);

        return $http->getContent();
    }
}
