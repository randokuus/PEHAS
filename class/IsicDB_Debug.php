<?php

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB_Exception.php");

abstract class IsicDB_Debug {

    const MAX_DUMPS = 256;
    const MAX_ERRORS = 16;

    private static $dumps = array();
    private static $errors = array();
    private static $programFlow = array();

    final public static function throwException($message, $code, $skipTraceLines = 1) {
        if (defined('DEVELOPERS_EMAILS') && count(self::$errors) < self::MAX_ERRORS) {
            $trace = self::generateCallStackTraceText(array_slice(debug_backtrace(false), (int)$skipTraceLines));
            if(count(self::$errors) == 0) {
                register_shutdown_function(array('IsicDB_Debug', 'sendGatheredErrorInformation'));
            }
            self::$errors[] = array(
                'message' => $message,
                'trace' => $trace
            );
        }
        throw new IsicDB_Exception($message, $code);
    }

    final public static function dump(array $args, $skipTraceLines = 1) {
        if (defined('DEVELOPERS_EMAILS') && count(self::$dumps) < self::MAX_DUMPS) {
            if(count(self::$dumps) == 0) {
                register_shutdown_function(array('IsicDB_Debug', 'sendGatheredDumpInformation'));
            }
            self::$dumps[] = array(
                'args' => $args,
                'trace' => self::generateCallStackTraceText(array_slice(debug_backtrace(false), (int)$skipTraceLines))
            );
        }
    }

    final private static function sendEmailToDeveloper($subject, $body) {
        if (!defined('DEVELOPERS_EMAILS')) {
            return false;
        }
        include_once (SITE_PATH . "/class/mail/htmlMimeMail.php");
        $mail = new htmlMimeMail();
        $mail->setFrom("minukool@minukool.ee");
        $mail->setSubject($subject);
        $mail->setText($body);
        return $mail->send(explode(",", DEVELOPERS_EMAILS), 'mail');
    }

    final private static function generateValueDump($value) {
    	static $escape = array(
    	    "\r" => '\r',
    	    "\n" => '\n',
    	    "\\" => '\\\\',
    	    "\"" => '\\"',
    	    "\'" => '\\\'',
    	    "\0" => '\0'
    	);
        if (is_null($value)) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            return $value ? $value : '0';
        } elseif (is_string($value)) {
            return '"' . strtr($value, $escape) . '"';
        } elseif (is_object($value)) {
            return 'Object(' . get_class($value) . ')';
        } elseif (is_resource($value)) {
            return 'Resource(' . get_resource_type($value) . ')';
        } elseif (is_array($value)) {
        	$items = array();
        	foreach($value as $key => $item) {
        		$items[] = self::generateValueDump($key) . ':' . self::generateValueDump($item);
        	}
        	return '{' . implode(', ', $items) . '}';
        } else {
            return 'Unknown';
        }
    }

    final private static function generateTraceElementText(array $trace, $showFileAndLine = true) {
        if($trace['type']) {
            $result .= $trace['class'] . $trace['type'];
        }
        $args = array();
        foreach ($trace['args'] as $arg) {
            $args[] = self::generateValueDump($arg);
        }
        $result .= $trace['function'] . "(" . implode(", ", $args) . ")";
        if($showFileAndLine) {
            $result .= " in " . $trace['file'] . " on line " . $trace['line'];
        }
        return $result;
    }

    final private static function generateCallStackTraceText(array $backTrace) {
        $result = "Call stack:\n";
        foreach ($backTrace as $num => $trace) {
            $result .= str_pad($num + 1, 2, "0", STR_PAD_LEFT) . ". ";
            $result .= self::generateTraceElementText($trace) . "\n";
        }
        return $result;
    }

    final public static function followProgramFlow() {
        static $lastTraces = array();
        $backtrace = debug_backtrace(false);
        $count = count($backtrace);
        if ($count < 2 || !isset($backtrace[1]['class'])) {
            return;
        }
        $currentTrace = $backtrace[1]['file'] . "|" . $backtrace[1]['line'] . "|"
            . $backtrace[1]['class'] . "|" . $backtrace[1]['function'];
        if (isset($lastTraces[$count]) && $currentTrace == $lastTraces[$count]) {
        	return;
        }
        $lastTraces[$count] = $currentTrace;
        $level = 0;
        for($i = 2; $i < count($backtrace); $i++) {
        	if(isset($backtrace[$i]['class'])) {
        		$level++;
        	}
        }
        self::$programFlow[] = str_repeat("\t", $level) . self::generateTraceElementText($backtrace[1], false);
    }

    final public static function saveGatheredProgramFlowInformation() {
        file_put_contents(
            SITE_PATH . '/cache/flow/' . date('Y-m-d.H-i-s') . '.txt',
            '<pre>' . implode("\n", self::$programFlow)
        );
    }

    final public static function sendGatheredErrorInformation() {
        $text = "";
        foreach (self::$errors as $enum => $error) {
            $header = "-- ERROR #" . ($enum + 1) . " --";
            $text .= str_repeat('-', strlen($header)) . "\n";
            $text .= $header . "\n";
            $text .= str_repeat('-', strlen($header)) . "\n";
            $text .= "\n" . $error['message'] . "\n";
            $text .= "\n" . $error['trace'] . "\n\n";
        }
        self::sendEmailToDeveloper("[Minukool] Error(s) occured", $text);
    }

    final public static function sendGatheredDumpInformation() {
        $text = "";
        foreach (self::$dumps as $dnum => $dump) {
            $header = "-- DUMP #" . ($dnum + 1) . " --";
            $text .= str_repeat('-', strlen($header)) . "\n";
            $text .= $header . "\n";
            $text .= str_repeat('-', strlen($header)) . "\n";
            if (count($dump['args']) > 0) {
                $text .= "\nParameters:\n";
                foreach ($dump['args'] as $num => $arg) {
                    $text .= str_pad($num + 1, 2, "0", STR_PAD_LEFT) . ". "
                        . self::generateValueDump($arg) . "\n";
                }
            }
            $text .= "\n" . $dump['trace'] . "\n\n";
        }
        self::sendEmailToDeveloper("[Minukool] Debug information", $text);
    }

}

if(defined('LOG_PROGRAM_FLOW') && LOG_PROGRAM_FLOW) {
	register_tick_function(array('IsicDB_Debug', 'followProgramFlow'));
    register_shutdown_function(array('IsicDB_Debug', 'saveGatheredProgramFlowInformation'));
	declare(ticks = 1);
}
