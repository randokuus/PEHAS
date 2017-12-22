<?php

class WSParsedMessage {

    var $_header = false;
    var $_param = false;
    
    function WSParsedMessage($header, $param) {
        $this->_header = $header;
        $this->_param = $param;
    }
    
    function getHeader($name) {
        return $this->_header[$name];
    }
    
    function getParam($name) {
        return $this->_param[$name];
    }
}
