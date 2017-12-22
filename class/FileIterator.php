<?php

class FileIterator implements Iterator {
    protected $separator = ',';
    protected $contents = array();
    protected $position = 0;

    public function __construct($filename, $separator = ',') {
        $this->open($filename);
        $this->separator = $separator;
    }

    private function open($filename) {
        $this->contents = array();
        $this->position = 0;
        if ($filename && is_readable($filename)) {
            $this->contents = file($filename, FILE_IGNORE_NEW_LINES);
        }
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        if ($this->separator) {
            return explode($this->separator, $this->contents[$this->position]);
        }
        return $this->contents[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->contents[$this->position]);
    }
}
