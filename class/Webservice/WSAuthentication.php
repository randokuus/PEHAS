<?php

class WSAuthentication {
    /**
     * displays auth-window
     * @access public
    */
    function authenticate()  {
        Header( "WWW-authenticate: Basic realm=\"Enter usename and password\"");
        Header( "HTTP/1.0  401  Unauthorized");
        echo  "You need to enter username and password to access Webservice ...\n";
        exit;
    }
}
