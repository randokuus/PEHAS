<?php

class IsicLogger {
    private $output;
    private $allowedIps = array(
        '213.35.228.180',
        '90.190.114.59',
        '212.47.219.48'
    );

    public function __construct($output = '') {
        if ($output) {
            $this->output = $output;
        } else {
            $this->output = SITE_PATH . '/cache/debug.log';
        }
    }

    public function addDebug($varData, $varName = '') {
        if (!in_array($_SERVER['REMOTE_ADDR'], $this->allowedIps) &&
            PHP_SAPI != 'cli') {
            return;
        }
        $text = 'Caller: ' . self::getCallingMethodName(2);
        if ($varName) {
            $text .= ' Var: ' . $varName;
        }
        $text .= "\n";
        if (is_array($varData)) {
            $text .= print_r($varData, true);
        } else {
            $text .= var_export($varData, true);
        }
        $this->saveMessage($text, 'DEBUG');
    }

    private function saveMessage($data, $type) {
        $message = "========= " . $type . ': ' . date('d.m.Y H:i:s') . ' (' . $_SERVER['REMOTE_ADDR'] . ") ==========\n";
        $message .= $data . "\n";
        file_put_contents($this->output, $message, FILE_APPEND);
    }

    public static function getCallingMethodName($caller = 1) {
        $callers = debug_backtrace();
        return $callers[$caller]['class'] . '::' . $callers[$caller]['function'];
    }
}