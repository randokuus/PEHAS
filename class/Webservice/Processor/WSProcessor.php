<?php

class WSProcessor {
    var $_error = false;
    var $_error_list = false;
    var $_card_list = false;
    
    function WSProcessor() {
        
    }
    
    function process() {
        
    }
    
    function getError() {
        return $this->_error;
    }
    
    function getErrorList() {
        return $this->_error_list;
    }
    
    function getCardList() {
        return $this->_card_list;
    }
}
