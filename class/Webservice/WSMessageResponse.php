<?php
require_once(SITE_PATH . "/class/Webservice/WSError.php");
require_once(SITE_PATH . "/class/Webservice/WSVersion.php");
require_once(SITE_PATH . "/class/Isic/IsicDate.php");

class WSMessageResponse {
    private $_partner = false;
    private $_error = false;
    private $header; // header parameters
    private $success = false; // logical value for showin if response should be true or false
    private $error = 0; // error Id
    private $parametersList = array(); // array with parameter name/value pairs
    private $paramErrorList = array(); // array of parameter errors
    private $cardList = array(); // array of cards
    private $userStatusList = array(); // array of user statuses
    private $personStatusList = array(); // array of person statuses
    private $pictureData = array(); // array of person picture data

    public function WSMessageResponse($partner) {
        $this->_partner = $partner;
        $this->_error = new WSError();
    }

    /**
     * Creates a response message for sending to other server
     *
     * @return string xml-formatted message
    */
    public function createMessageResponse() {
        $event = $this->header["event"];
        $reqId = $this->header["reqid"];
        $version = new WSVersion($this->header['ver']);

        $message = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
        $message .= "<isicmsgresp ver=\"" . $version->getVersion() . "\" partnerid=\"" . $this->_partner->getReceivedId() . "\" event=\"" . $event . "\" reqid=\"" . $reqId . "\" time=\"" . IsicDate::getCurrentTimeAsTimeStamp() . "\">\n";
        if ($this->error) {
            $message .= "<error error=\"" . $this->error . "\" message=\"" . $this->_error->getMessage("api", $this->error) . "\" />\n";
        } else {
            if ($event == "card_list") {
                for ($i = 0; $i < sizeof($this->cardList); $i++) {
                    $parameters = $this->cardList[$i];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<card>\n";
                    foreach ($parameters as $name => $value) {
                        $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                        $message .= " />\n";
                    }
                    $message .= "</card>\n";
                }
            } elseif ($event == "card_valid" && $version->getVersion() != '1.0') {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i]["param"][0];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<card>\n";
                    foreach ($parameters as $name => $value) {
                        if ($name == "card_number") {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            if (is_array($param_error) && sizeof($param_error)) {
                                foreach ($param_error as $ename => $evalue) {
                                    $message .= " error=\"" . $evalue . "\" message=\"" . $this->_error->getMessage($event, $evalue) . "\"";
                                }
                            } else {
                                $message .= " success=\"true\"";
                            }
                            $message .= " />\n";
                        }
                    }
                    $message .= "</card>\n";
                }
            } elseif ($event == "person_valid_card" || $event == "person_valid_pan") {
                if (sizeof($this->cardList)) {
                    for ($i = 0; $i < sizeof($this->cardList); $i++) {
                        $parameters = $this->cardList[$i];
                        $message .= "<card>\n";
                        foreach ($parameters as $name => $value) {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            $message .= " />\n";
                        }
                        $message .= "</card>\n";
                    }
                }
                if (sizeof($this->paramErrorList)) {
                    for ($i = 0; $i < sizeof($this->paramErrorList); $i++) {
                        $param_error = $this->paramErrorList[$i];
                        $message .= "<card>\n";
                        foreach ($param_error as $name => $value) {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value[0]) . "\"";
                            $message .= " error=\"" . $value[1] . "\" message=\"" . $this->_error->getMessage($event, $value[1]) . "\"";
                            $message .= " />\n";
                        }
                        $message .= "</card>\n";
                    }
                }
            } elseif ($event == "lock_list") {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i]["param"][0];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<lock>\n";
                    foreach ($parameters as $name => $value) {
                        if ($name == "lock_id") {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            if (is_array($param_error) && sizeof($param_error)) {
                                foreach ($param_error as $ename => $evalue) {
                                    $message .= " error=\"" . $evalue . "\" message=\"" . $this->_error->getMessage($event, $evalue) . "\"";
                                }
                            } else {
                                $message .= " success=\"true\"";
                            }
                            $message .= " />\n";
                        }
                    }
                    $message .= "</lock>\n";
                }
            } elseif ($event == "lock_access") {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i]["param"][0];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<event>\n";
                    foreach ($parameters as $name => $value) {
                        if ($name == "event_id") {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            if (is_array($param_error) && sizeof($param_error)) {
                                foreach ($param_error as $ename => $evalue) {
                                    $message .= " error=\"" . $evalue . "\" message=\"" . $this->_error->getMessage($event, $evalue) . "\"";
                                }
                            } else {
                                $message .= " success=\"true\"";
                            }
                            $message .= " />\n";
                        }
                    }
                    $message .= "</event>\n";
                }
            } elseif ($event == "last_event") {
                $message .= "<param ";
                if ($this->success !== false) {
                    $message .= "name=\"" . ("event_id") . "\" value=\"" . ($this->success) . "\"";
                } else {
                    $message .= "error=\"" . $this->paramErrorList[0] . "\" message=\"" . $this->_error->getMessage($event, $this->paramErrorList[0]) . "\"";
                }
                $message .= " />\n";
            } elseif ($event == "device_list") {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i]["param"][0];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<device>\n";
                    foreach ($parameters as $name => $value) {
                        if ($name == "device_id") {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            if (is_array($param_error) && sizeof($param_error)) {
                                foreach ($param_error as $ename => $evalue) {
                                    $message .= " error=\"" . $evalue . "\" message=\"" . $this->_error->getMessage($event, $evalue) . "\"";
                                }
                            } else {
                                $message .= " success=\"true\"";
                            }
                            $message .= " />\n";
                        }
                    }
                    $message .= "</device>\n";
                }
            } elseif ($event == "register_sale") {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i]["param"][0];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<event>\n";
                    foreach ($parameters as $name => $value) {
                        if ($name == "event_id") {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            if (is_array($param_error) && sizeof($param_error)) {
                                foreach ($param_error as $ename => $evalue) {
                                    $message .= " success=\"false\" error=\"" . $evalue . "\" message=\"" . $this->_error->getMessage($event, $evalue) . "\"";
                                }
                            } else {
                                $message .= " success=\"true\"";
                            }
                            $message .= " />\n";
                        }
                    }
                    $message .= "</event>\n";
                }
            } else if ($event == "status_list") {
                for ($i = 0; $i < sizeof($this->userStatusList); $i++) {
                    $parameters = $this->userStatusList[$i];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<status>\n";
                    foreach ($parameters as $name => $value) {
                        $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                        $message .= " />\n";
                    }
                    $message .= "</status>\n";
                }
            } else if ($event == "person_status_list") {
                for ($i = 0; $i < sizeof($this->personStatusList); $i++) {
                    $parameters = $this->personStatusList[$i];
                    $param_error = $this->paramErrorList[$i];
                    $message .= "<status>\n";
                    foreach ($parameters as $name => $value) {
                        $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                        $message .= " />\n";
                    }
                    $message .= "</status>\n";
                }
            } elseif ($event == "person_picture") {
                if (sizeof($this->pictureData)) {
                    for ($i = 0; $i < sizeof($this->pictureData); $i++) {
                        $parameters = $this->pictureData[$i];
                        $message .= "<picture>\n";
                        foreach ($parameters as $name => $value) {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                            $message .= " />\n";
                        }
                        $message .= "</picture>\n";
                    }
                }
                if (sizeof($this->paramErrorList)) {
                    for ($i = 0; $i < sizeof($this->paramErrorList); $i++) {
                        $param_error = $this->paramErrorList[$i];
                        $message .= "<picture>\n";
                        foreach ($param_error as $name => $value) {
                            $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value[0]) . "\"";
                            $message .= " error=\"" . $value[1] . "\" message=\"" . $this->_error->getMessage($event, $value[1]) . "\"";
                            $message .= " />\n";
                        }
                        $message .= "</picture>\n";
                    }
                }
            } else {
                for ($i = 0; $i < sizeof($this->parametersList); $i++) {
                    $parameters = $this->parametersList[$i];
                    $param_error = $this->paramErrorList[$i];
                    foreach ($parameters as $name => $value) {
                        $message .= "<param name=\"" . ($name) . "\" value=\"" . ($value) . "\"";
                        if ($param_error[$name]) {
                            $message .= " error=\"" . $param_error[$name] . "\" message=\"" . $this->_error->getMessage($event, $param_error[$name]) . "\"";
                        } else {
                            $message .= " success=\"true\"";
                        }
                        $message .= " />\n";
                    }
                }
            }
        }
        $message .= "</isicmsgresp>\n";

        return $message;
    }

    /**
     * @param $cardList the $cardList to set
     */
    public function setCardList($cardList) {
        $this->cardList = $cardList;
    }

    /**
     * @param $paramErrorList the $paramErrorList to set
     */
    public function setParamErrorList($paramErrorList) {
        $this->paramErrorList = $paramErrorList;
    }

    /**
     * @param $parametersList the $parametersList to set
     */
    public function setParametersList($parametersList) {
        $this->parametersList = $parametersList;
    }

    /**
     * @param $error the $error to set
     */
    public function setError($error) {
        $this->error = $error;
    }

    /**
     * @param $success the $success to set
     */
    public function setSuccess($success) {
        $this->success = $success;
    }

    /**
     * @param $header the $header to set
     */
    public function setHeader($header) {
        $this->header = $header;
    }

    /**
     * @return the $cardList
     */
    public function getCardList() {
        return $this->cardList;
    }

    /**
     * @return the $paramErrorList
     */
    public function getParamErrorList() {
        return $this->paramErrorList;
    }

    /**
     * @return the $parametersList
     */
    public function getParametersList() {
        return $this->parametersList;
    }

    /**
     * @return the $error
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @return the $success
     */
    public function getSuccess() {
        return $this->success;
    }

    /**
     * @return the $header
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @param $userStatusList the $userStatusList to set
     */
    public function setUserStatusList($userStatusList) {
        $this->userStatusList = $userStatusList;
    }

    /**
     * @return the $userStatusList
     */
    public function getUserStatusList() {
        return $this->userStatusList;
    }

    /**
     * @param $personStatusList the $personStatusList to set
     */
    public function setPersonStatusList($personStatusList) {
        $this->personStatusList = $personStatusList;
    }

    /**
     * @return the $personStatusList
     */
    public function getPersonStatusList() {
        return $this->personStatusList;
    }

	/**
     * @return the $pictureData
     */
    public function getPictureData() {
        return $this->pictureData;
    }

	/**
     * @param $pictureData the $pictureData to set
     */
    public function setPictureData($pictureData) {
        $this->pictureData = $pictureData;
    }
}
