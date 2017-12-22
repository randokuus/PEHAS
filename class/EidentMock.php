<?php

require_once ('class/Eident.php');

class EidentMock extends Eident {
	function generateMacFields() {
	    return array(
            'B02K_CUSTID' => $_GET['B02K_CUSTID'],
            'B02K_CUSTNAME' => 'MOCKNAME',
	    );
	}
	
	
    /**
     * Check user activation
     *
     * @return bool|array
     */
    function checkActivation($type) {
        switch ($type) {
            case 'e-ident-response':
                return $this->generateMacFields();
            break;
        }
        return false;
    }
}
