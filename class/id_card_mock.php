<?php

class id_card {

    function id_card() {
    } 
    
    function getClientSDnCn () {
        return 'LASTNAME,FIRSTNAME,' . $_GET['username'];
    }
    
    function id_card_valid() {
        return true;
    }
}
