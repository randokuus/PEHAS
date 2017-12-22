<?php

class SMSSendQueue {
    const TABLE_NAME = 'module_messages_send_queue';

    const SEND_MAX_TRIES = 3;

    const MESSAGE_TYPE_SMS = 'sms';

    /** @var Database */
    private $db;

    /**
     * @param $db
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param array $data
     * @param null $id
     * @return bool|int
     */
    public function save(array $data, $id = null) {
        return $id ? $this->update($data, $id) : $this->insert($data);
    }

    /**
     * @param array $data
     * @return int|FALSE
     */
    public function insert(array $data) {
        $data['addtime'] = $this->db->now();
        $sql = 'INSERT INTO ?f (?@f) VALUES (?@)';
        $res = $this->db->query($sql, self::TABLE_NAME, array_keys($data), array_values($data));
        return $res ? $this->db->insert_id() : false;
    }

    /**
     * @param array $data
     * @param $id
     * @return int|FALSE
     */
    public function update(array $data, $id) {
        $data['modtime'] = $this->db->now();
        $sql = 'UPDATE ?f SET ?% WHERE `id` = ?';
        $res = $this->db->query($sql, self::TABLE_NAME, $data, $id);
        return $res ? $id : false;
    }

    /**
     * @param $message
     * @return bool|int
     */
    public function addToQueue($message) {
        $data = array(
            'message_type' => self::MESSAGE_TYPE_SMS,
            'success' => 0,
            'user_id' => $message['user_id'],
            'school_id' => $message['school_id'],
            'from' => $message['from'],
            'to' => $message['to'],
            'text' => $message['text'],
            'request' => '',
            'response' => '',
            'tries' => 0
        );
        return $this->save($data);
    }

    /**
     * @return array|bool
     */
    public function getQueuedRecords() {
        $sql = 'SELECT * FROM ?f WHERE `success` = 0 AND `tries` < ?';
        $res = $this->db->query($sql, self::TABLE_NAME, self::SEND_MAX_TRIES);
        if ($res) {
            return $res->fetch_all();
        }
        return false;
    }

    /**
     * @return int
     */
    public function getSendMaxTries() {
        return self::SEND_MAX_TRIES;
    }
}