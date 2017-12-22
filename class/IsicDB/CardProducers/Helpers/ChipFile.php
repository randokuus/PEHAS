<?php

class CardProducers_ChipFile {
    
    private $filename;
    private $chips = array();
    
    public function __constructor($filename) {
        $this->filename = $filename;
    }
    
    public function addChip($isicNumber, $chipNumber) {
        $this->chips[$isicNumber] = $chipNumber;
    }
    
    public function getFilename() {
        return $this->filename;
    }
    
    public function getChips() {
        return $this->chips;
    }
    
}

