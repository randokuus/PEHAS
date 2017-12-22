<?php

class WSRawPostData {
    function getData() {
        if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return file_get_contents("php://input");
        }
        return $GLOBALS['HTTP_RAW_POST_DATA'];
    }
}
