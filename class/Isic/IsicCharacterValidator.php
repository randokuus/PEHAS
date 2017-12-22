<?php

class IsicCharacterValidator {
    private $validCharacters = false;

    public function __construct($validCharacters = false) {
        $this->setValidCharacters($validCharacters);
    }

    public function hasValidCharacters($str) {
        for ($i = 0; $i < strlen($str); $i++) {
            if (!$this->isValidCharacter($str[$i])) {
                return false;
            }
        }
        return true;
    }

    private function isValidCharacter($char) {
        return in_array(ord($char), $this->validCharacters);
    }

    /**
     * @return the $validCharacters
     */
    public function getValidCharacters() {
        return $this->validCharacters;
    }

    /**
     * @param $validCharacters the $validCharacters to set
     */
    public function setValidCharacters($validCharacters) {
        $charList = array();
        $charBlocks = explode(',', $validCharacters);
        foreach ($charBlocks as $charBlock) {
            $charList = array_merge($charList, $this->getCharacterListFromBlock($charBlock));
        }
        $this->validCharacters = $charList;
    }

    private function getCharacterListFromBlock($charBlock) {
        if (strpos($charBlock, '-') !== false) {
            return $this->getCharacterListFromRange(explode('-', $charBlock));
        } else {
            return array($charBlock);
        }
    }

    private function getCharacterListFromRange($charRange) {
        $charList = array();
        for ($i = $charRange[0]; $i <= $charRange[1]; $i++) {
            $charList[] = $i;
        }
        return $charList;
    }
}
