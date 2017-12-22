<?php

class SMSSendLogger {
    const TABLE_NAME = 'module_messages_send_log';

    /** @var Database */
    private $db;

    private $fields = array(
        'user_id' => 'user_id',
        'school_id' => 'school_id',
        'from' => 'from',
        'to' => 'to',
        'text' => 'text',
        'request' => 'request',
        'response' => 'response',
        'message-id' => 'message-id',
        'status' => 'status',
        'remaining-balance' => 'remaining_balance',
        'message-price' => 'message_price',
        'network' => 'network',
        'error-text' => 'error_text'
    );

    public function __construct($db) {
        $this->db = $db;
    }

    public function getRecord($id) {
        $sql = 'SELECT * FROM ?f WHERE `id` = ?';
        return $this->db->fetch_first_row($sql, self::TABLE_NAME, $id);
    }

    public function log($messages, $metaData) {
        $logRecords = array();
        foreach ($messages as $message) {
            $data = $this->buildRecord(array_merge($message, $metaData));
            $id = $this->save($data);
            if ($id) {
                $logRecords[] = $this->getRecord($id);
            }
        }
        return $logRecords;
    }

    private function buildRecord($message) {
        $data = array();
        foreach ($this->fields as $srcField => $tarField) {
            if (array_key_exists($srcField, $message)) {
                $data[$tarField] = $message[$srcField];
            }
        }
        return $data;
    }

    public function save($data) {
        $data['sendtime'] = $this->db->now();
        $sql = 'INSERT INTO ?f (?@f) VALUES (?@)';
        $res = $this->db->query($sql, self::TABLE_NAME, array_keys($data), array_values($data));
        return $res ? $this->db->insert_id() : false;
    }
}
