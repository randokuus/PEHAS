<?php

class IsicCCDBClient {
    const ISSUER_NAME = 'FESU';
    const STATUS_VALID = 'VALID';
    const STATUS_VOIDED = 'VOIDED';
    /**
     * @var RestCurlClient
     */
    private $restClient;

    private $apiUrl;

    private $apiUsername;

    private $apiPassword;

    private $cardFields = array(
        'isic_number' => 'cardNumber',
        'card_type_ccdb' => 'cardType',
        'card_status_ccdb' => 'cardStatus',
        'person_name' => 'printedName',
        'person_name_first' => 'firstName',
        'person_name_last' => 'lastName',
        'person_gender' => 'gender',
        'exported_date' => 'validFrom',
        'expiration_date' => 'validTo',
        'school_name' => 'institutionName',
        'issuer_name_ccdb' => 'issuedBy',
    );

    public function __construct(RestCurlClient $restClient, $url, $username, $password) {
        $this->restClient = $restClient;
        $this->apiUrl = $url;
        $this->apiUsername = $username;
        $this->apiPassword = $password;
    }

    public function sync(IsicDB_CardDataSyncCCDB $isicDbCardDataSync, IsicDB_Cards $isicDbCards) {
        $records = $isicDbCardDataSync->getScheduledRecords();
        foreach ($records as $record) {
            echo $record['record_id'];

            if ($record['tries'] >= $isicDbCardDataSync->getSyncMaxTries()) {
                echo ": max tries exceeded, skipping\n";
                continue;
            }

            $cardRecord = $this->convertRecord($isicDbCards->getRecord($record['record_id']));
            $xml = $this->getCardXml($cardRecord);
            $sendResult = $this->send($xml);

            if ($sendResult !== null) {
                $response = print_r($this->restClient->response_info, true) . "\n\n" .
                    print_r($this->restClient->response_object, true);
                $data = $record;
                $data['success'] = $sendResult ? 1 : 0;
                $data['request'] = $xml;
                $data['response'] = $response;
                $data['tries']++;
                $isicDbCardDataSync->updateRecord($record['id'], $data);
                echo ': ' . ($sendResult ? 'OK' : 'ERROR');
                if (!$sendResult) {
                    IsicMail::sendCardSyncCCDBFailedNotification(htmlspecialchars($xml), $response);
                }
            }
            echo "\n";
        }
    }

    private function convertRecord($card) {
        $card['card_status_ccdb'] = $card['active'] ? self::STATUS_VALID : self::STATUS_VOIDED;
        $card['person_name'] = $card['person_name_first'] . ' ' . $card['person_name_last'];
        $card['person_gender'] = '';
        $card['exported_date'] = date('Y-m-d', strtotime($card['exported']));
        $card['issuer_name_ccdb'] = self::ISSUER_NAME;
        $card['card_type_ccdb'] = $this->getCardTypeForCardNumber($card['card_type_ccdb'], $card['isic_number']);
        return $card;
    }

    public function getCardTypeForCardNumber($conf, $cardNumber) {
        $confList = explode(';', $conf);
        foreach ($confList as $confData) {
            $typeData = explode(':', $confData);
            $typeName = $typeData[0];
            if (count($typeData) == 1) {
                return $typeName;
            }
            $prefixList = explode(',', $typeData[1]);
            foreach ($prefixList as $prefix) {
                if (substr($cardNumber, 0, strlen($prefix)) == $prefix) {
                    return $typeName;
                }
            }
        }
        return null;
    }

    public function send($xml) {
        try {
            $this->restClient->post(
                $this->apiUrl,
                $xml,
                array(
                    CURLOPT_HTTPHEADER => array('Content-Type: application/xml'),
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_USERPWD => $this->apiUsername . ':' . $this->apiPassword
                )
            );
            echo 'Response: ' .  $this->restClient->response_info['http_code'] . "\n";
            return true;
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }

    public function getCardXml(array $cardData) {
        $dom = new DOMDocument('1.0', 'utf-8');
        $card = $dom->createElement('card');
        foreach ($this->cardFields as $fieldName => $nodeName) {
            $element = $dom->createElement($nodeName, htmlspecialchars($cardData[$fieldName]));
            $card->appendChild($element);
        }
        $dom->appendChild($card);
        $dom->formatOutput = true;
        return $dom->saveXML();
    }
}