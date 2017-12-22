<?php

class WSRequest {
    /**
     * Generates request id
     *
     * @return int request id
    */
    function generateId() {
        $reqid = time();
        return $reqid;
    }
}
