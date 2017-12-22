<?php

class ElapsedTime {
    private $begTime = array();
    private $endTime = array();
    private $elapsedTime = array();
    private $userid = 0;
    private $outputDevice = 'screen';

    public function __construct($output = '') {
        $this->userid = $GLOBALS["user_data"][0];
        $this->setOutput($output);
    }

    function setOutput($output = '') {
        if ($output) {
            $this->outputDevice = $output;
        }
    }

    function startTimer($timer = 'default', $reset = true) {
        if ($reset) {
            $this->resetTimer($timer);
        }
        $this->begTime[$timer] = microtime(true);
        $this->endTime[$timer] = microtime(true);
    }

    function stopTimer($timer = 'default') {
        $this->endTime[$timer] = microtime(true);
        $this->elapsedTime[$timer] += $this->endTime[$timer] - $this->begTime[$timer];
    }

    function resetTimer($timer = 'default') {
        $this->elapsedTime[$timer] = 0;
    }

    function getElapsedTime($timer = 'default') {
        return $this->elapsedTime[$timer];
    }

    function showElapsedTime($timer = 'default', $description = '') {
        $this->stopTimer($timer);
//        if ($this->userid == 1) {
            $outString = "<!-- ELAPSED TIME (" . $timer . "): " . $this->getElapsedTime($timer) . " -->\n";
            if ($description) {
                $outString .= "<!-- DESCRIPTION: " . $description . " -->\n";
            }
            if ($this->outputDevice == 'file') {
                $this->log_id_action($outString);
            } else {
                echo $outString;
            }
//        }
    }

    function log_id_action($data) {
        if ($fp_log = fopen(SITE_PATH . "/cache/id/elapsedtimelog.txt", "a+")) {
            fwrite($fp_log, "______________________________" . date("d.m.Y H:i:s") . "______________________________\n");
            fwrite($fp_log, $data);
            //fwrite($fp_log, "==============================" . date("d.m.Y H:i:s") . "==============================\n");
            fclose($fp_log);
        }
    }
 }
