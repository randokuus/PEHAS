<?php
require_once(SITE_PATH . '/class/UrlGetContents.php');

class IsicUnitedTicketsSync {
    private $apiUrl = UNITED_TICKETS_API_URL;
    const ACTION_ID_ACTIVATION = '446';
    const ACTION_ID_DEACTIVATION = '336';
    const ACTION_ID_REMOVE_USER = '337';
    const CHANNEL_ID = '9';
    const ORGANIZATION_USER_ID = '80059438';
    private $rawResponse;
    private $lastUrl;

    public function sendActivationMessage($message) {
        return $this->sendMessage($message, self::ACTION_ID_ACTIVATION);
    }

    public function sendDeactivationMessage($message) {
        return $this->sendMessage($message, self::ACTION_ID_DEACTIVATION);
    }

    public function sendRemoveUserMessage($message) {
        return $this->sendMessage($message, self::ACTION_ID_REMOVE_USER);
    }

    private function sendMessage($message, $activationType) {
        $this->lastUrl = $this->getMessageAsUrl($message, $activationType);
        if (!$this->lastUrl) {
            return false;
        }
        $this->rawResponse = UrlGetContents::getContents($this->lastUrl);
        $response = false;
        if ($this->isXml($this->rawResponse)) {
            $response = $this->getParsedResponse($this->rawResponse);
        }
        return $response;
    }

    private function getMessageAsUrl($message, $activationType) {
        $values = false;
        switch ($activationType) {
            case self::ACTION_ID_ACTIVATION:
                $values = $this->getActivationValues($message);
            break;
            case self::ACTION_ID_DEACTIVATION:
                $values = $this->getDeactivationValues($message);
            break;
            case self::ACTION_ID_REMOVE_USER:
                $values = $this->getRemoveUserValues($message);
            break;
            default:
            break;
        }

        if (!$values) {
            return false;
        }
        $url = implode('&', $values);
        return $this->apiUrl . $url;
    }

    private function getActivationValues($message) {
        $values = array(
            'arg1' => $message['person_number'],
            'arg2' => self::ACTION_ID_ACTIVATION,
            'arg3' => date('d.m.Y', strtotime($message['expiration_date'])),
            'arg4' => self::CHANNEL_ID,
            'arg5' => '',
            'arg6' => substr($message['pan_number'], -11),
            'arg7' => '',
            'arg8' => $message['transaction_id'],
            'arg9' => $message['isic_number'],
            'arg10' => self::ORGANIZATION_USER_ID,
        );
        return $values;
    }

    private function getDeactivationValues($message) {
        $values = array(
            'arg1' => substr($message['pan_number'], -11),
            'arg2' => self::ACTION_ID_DEACTIVATION,
            'arg3' => '',
            'arg4' => self::CHANNEL_ID,
            'arg5' => '',
            'arg6' => '',
            'arg7' => '',
            'arg8' => $message['transaction_id'],
            'arg9' => '',
            'arg10' => self::ORGANIZATION_USER_ID,
        );
        return $values;
    }

    private function getRemoveUserValues($message) {
        $values = array(
            'arg1' => $message['user_code'],
            'arg2' => self::ACTION_ID_REMOVE_USER,
            'arg3' => '',
            'arg4' => self::CHANNEL_ID,
            'arg5' => '',
            'arg6' => '',
            'arg7' => '',
            'arg8' => $message['transaction_id'],
            'arg9' => '',
            'arg10' => self::ORGANIZATION_USER_ID,
        );
        return $values;
    }

    private function getParsedResponse($xml) {
        $parsed = new SimpleXMLElement($xml);
        // result OK
        if ($parsed->rec && $parsed->rec->IS_LIVE == 'Y') {
            return true;
        }
        // error handling
        if ($parsed->CODE) {
            // handle
        }
        return false;
    }

    private function isXml($str) {
        if (strlen(trim($str)) == 0) {
            return false;
        }
        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadXML($str);
        $errors = libxml_get_errors();
        if (empty($errors)) {
            return true;
        }

        $error = $errors[0];
        if ($error->level < 3) {
            return true;
        }
        return false;
    }
    /**
     * @return the $rawResponse
     */
    public function getRawResponse() {
        return $this->rawResponse;
    }
    /**
     * @return the $lastUrl
     */
    public function getLastUrl() {
        return $this->lastUrl;
    }

}
