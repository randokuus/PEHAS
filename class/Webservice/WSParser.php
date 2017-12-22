<?php
require_once(SITE_PATH . "/class/Xml2Array.php");

class WSParser {
    /**
     * Parses returned message
     *
     * @param string $message message body in XML-format
     * @return array of parameter-value pairs
    */
    function parse($message = '') {
        $xml2array = new Xml2Array();
        return $xml2array->parse($message);
    }
    
    /**
     * Returns header values of the message
     *
     * @param array $message parsed XML message as array
     * @return array of header values
    */
    function getHeader($message = array()) {
        $header = array();
        if (is_array($message)) {
            $header["name"] = $message[0]["_NAME"];
            $header["ver"] = $message[0]["ver"];
            $header["partnerid"] = $message[0]["partnerid"];
            $header["event"] = $message[0]["event"];
            $header["reqid"] = $message[0]["reqid"];
        }

        return $header;
    }

    /**
     * Returns body part of the message
     *
     * @param array $message parsed XML message as array
     * @return array of body of the message
    */
    function getBody($message = array()) {
        $body = array();
        if (is_array($message)) {
            $body = $message[0]["_ELEMENTS"];
        }

        return $body;
    }

    /**
     * Processes error-messages of parameters and returns array of errors
     *
     * @param array $elist array of parameter errors created from XML
     * @return array of errors
    */
    function getParameterError($elist = array()) {
        $errors = array();
        for ($i = 0; $i < sizeof($elist); $i++) {
            switch($elist[$i]["_NAME"]) {
                case "error":
                    $errors[$elist[$i]["code"]] = $elist[$i]["message"];
                break;
            }
        }
        if (isset($elist["error"])) {
            $errors[$elist["error"]] = $elist["message"];
        }
        return $errors;
    }

    /**
     * Processes message body and returns array of parameters
     *
     * @param array $plist array of parameters created from XML
     * @return array of parameters
    */
    function getParameters($plist) {
        $parameters = array();
        $par_count = array();

        for ($i = 0; $i < sizeof($plist); $i++) {
            $plistName = $plist[$i]["_NAME"];
            $plistElements = $plist[$i]["_ELEMENTS"];
            switch($plistName) {
                case "lock": // falls through
                case "device":  // falls through
                case "card": // falls through
                case 'profile': // falls through
                case "event":
                    $parameters[$plistName][$i] = WSParser::getParameters($plistElements);
                break;
                case "error": // falls through
                case "param":
                    $par_name = ($plist[$i]["name"]);
                    if (!$par_count[$par_name]) {
                        $par_count[$par_name] = 0;
                    }
                    if (isset($plist[$i]["value"])) {
                        $parameters["param"][$par_count[$par_name]][$par_name] = $plist[$i]["value"];
                    }
                    if (sizeof($plistElements) > 0) {
                        $parameters["param_error"][$par_count[$par_name]][$par_name] =
                            WSParser::getParameterError($plistElements);
                    } else if (isset($plist[$i]["error"])) {
                        $parameters["param"][$par_count[$par_name]]['error'] =
                            WSParser::getParameterError($plist[$i]);
                    }
                    $par_count[$par_name]++;
                break;
                default:
                    $parameters[$plistName] = $plist[$i]["_DATA"];
                break;
            }
        }
        return $parameters;
    }
}
