<?php

class SMSClient {
    /** @var RestCurlClient  */
    private $restClient;

    /** @var  SMSSendLogger */
    private $sendLogger;

    /** @var IsicDB_SchoolSMSCredit */
    private $schoolSMSCredit;

    private $apiUrl;

    private $apiKey;

    private $apiSecret;

    private $messageFields = array(
        'from' => 'from',
        'to' => 'to',
        'text' => 'text'
    );

    private $decodedResponse;

    public function __construct(RestCurlClient $restClient, $url, $key, $secret, SMSSendLogger $logger, $schoolSMSCredit) {
        $this->restClient = $restClient;
        $this->apiUrl = $url;
        $this->apiKey = $key;
        $this->apiSecret = $secret;
        $this->sendLogger = $logger;
        $this->schoolSMSCredit = $schoolSMSCredit;
    }

    public function sendAll(SMSSendQueue $sendQueue) {
        $records = $sendQueue->getQueuedRecords();
        foreach ($records as $record) {
            echo $record['id'] . ', from: ' . $record['from'] . ', to: ' . $record['to'];

            $request = $this->buildMessage($record);
            if (!$request) {
                echo ': missing fields';
                continue;
            }
            $sendResult = $this->send($request);

            if ($sendResult !== null) {
                $response =
                    "========== Response ==========\n" .
                    print_r($this->restClient->response_object, true) .
                    "\n========== Info ==========\n" .
                    print_r($this->restClient->response_info, true);
                $data = $record;
                $data['success'] = $sendResult ? 1 : 0;
                $data['request'] = $request;
                $data['response'] = $response;
                $data['tries']++;
                $sendQueue->save($data, $record['id']);
                echo ': ' . ($sendResult ? 'OK' : 'ERROR');
                $logRecords = $this->sendLogger->log($this->decodedResponse['messages'], $data);
                $this->schoolSMSCredit->useCreditBySendLog($logRecords);
                if (!$sendResult) {
//                    IsicMail::sendSMSSendFailedNotification(htmlspecialchars($request), $response);
                }
            }
            echo "\n";
        }
    }

    public function buildMessage($data) {
        $message = $this->apiUrl . 'api_key=' . $this->apiKey . '&api_secret=' . $this->apiSecret;
        foreach ($this->messageFields as $fieldSrc => $fieldTar) {
            if (!isset($data[$fieldTar])) {
                return false;
            }
            $message .= '&' . $fieldTar . '=' . urlencode($data[$fieldTar]);
        }
        return $message;
    }

    public function send($request) {
        try {
            $this->restClient->get($request);
            $this->decodedResponse = $this->getDecodedResponse($this->restClient->response_object);
            return $this->wasSendSuccessful();
        } catch (Exception $e) {
            $this->decodedResponse = array('messages' => array());
            return false;
        }
    }

    private function wasSendSuccessful() {
        if ($this->restClient->response_info['http_code'] != 200) {
            return false;
        }
        if (count($this->decodedResponse['messages']) == 0) {
            return false;
        }
        foreach ($this->decodedResponse['messages'] as $message) {
            if ($message['status'] != 0) {
                return false;
            }
        }

        return true;
    }

    private function getDecodedResponse($response) {
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !isset($decoded['messages'])) {
            return array('messages' => array());
        }
        return $decoded;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }

    /**
     * @param mixed $apiSecret
     */
    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }
}
