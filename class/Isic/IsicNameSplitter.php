<?php

/**
 * Class IsicNameSplitter
 */
class IsicNameSplitter {
    /**
     * Current max string length for splitting
     *
     * @var int
     */
    private $maxLength = 30;

    /**
     * Current string encoding for mb_* functions
     *
     * @var string
     */
    private $encoding = 'UTF-8';

    /**
     * @param int $maxLength
     * @param string $encoding
     */
    public function __construct($maxLength = 0, $encoding = '') {
        if ($maxLength) {
            $this->setMaxLength($maxLength);
        }

        if ($encoding) {
            $this->setEncoding($encoding);
        }
    }

    /**
     * Tests if given string is shorter than current max possible length
     *
     * @param $str
     * @return bool
     */
    public function isLessThanMaxLength($str) {
        return mb_strlen($str, $this->getEncoding()) <= $this->getMaxLength();
    }

    /**
     * Splits given string into array of substrings with max length not longer than current max length
     *
     * @param $name
     * @return array
     */
    public function split($name, $separator = ' ') {
        if ($this->isLessThanMaxLength($name)) {
            return array($name);
        }

        $name = str_replace('-', '- ', $name);
        $nameList = array();
        $nameParts = explode($separator, $name);
        $tmpName = '';
        foreach ($nameParts as $namePart) {
            $tmpSeparator = substr($tmpName, -1) == '-' ? '' : $separator;
            $concatName = trim($tmpName . $tmpSeparator . $namePart);
            if ($this->isLessThanMaxLength($concatName)) {
                $tmpName = $concatName;
            } else {
                $nameList[] = $this->substring($tmpName ? $tmpName : $namePart);
                $tmpName = $tmpName ? $namePart : '';
            }
        }
        if ($tmpName) {
            $nameList[] = $this->substring($tmpName);
        }
        return $nameList;
    }

    private function substring($str) {
        return mb_substr($str, 0, $this->maxLength, $this->getEncoding());
    }

    /**
     * Splits two given strings into array of substrings with each elements not exceeding current max length
     *
     * @param $name1
     * @param $name2
     * @return array
     */
    public function splitDouble($name1, $name2) {
        if ($this->isLessThanMaxLength($name1 . ' ' . $name2)) {
            return array($name1 . ' ' . $name2);
        }
        if ($this->isLessThanMaxLength($name1) &&
            $this->isLessThanMaxLength($name2)) {
            return array($name1, $name2);
        }
        return $this->split($name1 . ' ' . $name2);
    }

    /**
     * @param int $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }
}