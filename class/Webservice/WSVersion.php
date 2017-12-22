<?php

class WSVersion {
    var $version = '1.0';
    var $version_list = array(
        '1.0',
        '2.0',
        '2.1'
    );
    
    function WSVersion($version = '') {
        if ($version && $this->isValidVersion($version)) {
            $this->setVersion($version);
        }
    }
    
    function getVersion() {
        return $this->version;
    }
    
    function isValidVersion($version) {
        return in_array($version, $this->version_list);
    }
    
    /**
     * Changes API version
     *
     * @param string $version version number
    */
    function setVersion($version = '') {
        if ($version) {
            $this->version = $version;
        }
    }
}
