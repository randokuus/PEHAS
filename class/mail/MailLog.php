<?php

class MailLog {
    const LOG_TABLE = 'maillog';
    const LOG_FILE = '/cache/maillog.log';
    private $logMethod = 'file';
    private $logData;
    private $db;

    public function MailLog($logMethod = 'db') {
        $this->setLogMethod($logMethod);
        $this->db = &$GLOBALS['database'];
    }

    public function setLogMethod($logMethod) {
        $this->logMethod = $logMethod;
    }

    public function clearLogData() {
        $this->logData = array();
    }

    public function setValue($key, $val) {
        $this->logData[$key] = $val;
    }

    public function save() {
        if ($this->isLogNeeded()) {
            switch ($this->logMethod) {
                case 'db':
                    $this->saveDb();
                break;
                case 'file':
                    $this->saveFile();
                break;
                default:
                break;
            }
        }
    }

    private function saveDb() {
        if ($this->isDbTableAvailable()) {
            $this->db->query('
                INSERT INTO `maillog`
                (
                    `event_time`,
                    `subject`,
                    `recipient`,
                    `headers`,
                    `message`,
                    `result`
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    !
                )',
                $this->db->now(),
                $this->logData['Subject'],
                $this->logData['To'],
                $this->logData['Headers'],
                $this->logData['Message'],
                $this->logData['Result']
            );
        }
    }

    private function isDbTableAvailable() {
        $r = &$this->db->query('SHOW TABLES LIKE ?', self::LOG_TABLE);
        return $r === false ? 0 : $r->num_rows();
    }

    private function saveFile() {
        if ($this->isFileAvailable()) {
            file_put_contents(SITE_PATH . self::LOG_FILE, $this->getLogDataAsString(), FILE_APPEND);
        }
    }

    private function getLogDataAsString() {
        $str = "---------- " . $this->db->now() . " ----------\n";
        foreach ($this->logData as $key => $val) {
            $str .= $key . ': ' . $val . "\n";
        }
        $str .= "---------------------------------------\n";
        return $str;
    }

    private function isFileAvailable() {
        $filePath = SITE_PATH . self::LOG_FILE;
        return (file_exists($filePath) && is_file($filePath) && is_writable($filePath));
    }

    private function isLogNeeded() {
        return (defined('MAILLOG') && MAILLOG == 1);
    }
}
