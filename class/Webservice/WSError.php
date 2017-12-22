<?php

class WSError {
   /**
     * list of possible errormessages
     *
     * @var $_errorMessage
     * @access protected
     */
    var $_errorMessage = array(
        "api" => array(
            1 => "Invalid XML",
            2 => "Version number not given",
            3 => "Unknown version number",
            4 => "Event not given",
            5 => "Event not recognized",
            6 => "Partner ID not given",
            7 => "Partner ID not recognized/disabled",
            8 => "IP address not recognized",
            9 => "Request ID is not given",
            10 => "Username/password incorrect",
            11 => "Account disabled",
            99 => "Unknown error",
        ),
        "card_valid" => array(
            100 => "Card number not specified",
            101 => "Unknown card number",
            102 => "Card not valid",
            103 => "Number type not valid",
        ),
        "lock_list" => array(
            200 => "Lock ID not specified",
            201 => "Lock name not specified",
        ),
        "lock_access" => array(
            300 => "Record id not specified",
            301 => "Record id is not unique",
            310 => "Direction not specified",
            311 => "Unknown direction",
            320 => "Access not specified",
            321 => "Unknown access",
            330 => "Card number not specified",
            331 => "Unknown card number",
            332 => "Number type not specified",
            333 => "Unknown number type",
            340 => "Lock ID not specified",
            341 => "Unknown lock ID",
            350 => "Event time not specified",
            351 => "Invalid event time",
        ),
        "last_event" => array(
            400 => "No events found",
        ),
        "person_valid_card" => array(
            500 => "Person number not specified",
            501 => "Unknown person number",
            502 => "No valid cards for this person",
        ),
        "status_list" => array(
            600 => "No user status records found",
        ),
        "register_sale" => array(
            700 => "Event id not specified",
            701 => "Event id is not unique",
            710 => "Card number not specified",
            711 => "Unknown card number",
            712 => "Number type not specified",
            713 => "Unknown number type",
            720 => "Device ID not specified",
            721 => "Unknown device ID",
            730 => "Event time not specified",
            731 => "Invalid event time",
            740 => "Sales sum not specified",
            741 => "Sales sum invalid",
            750 => "Discount sum not specified",
            751 => "Discount sum invalid",
            760 => "Currency not specified",
            761 => "Currency invalid",
        ),
        "device_list" => array(
            800 => "Device ID not specified",
            801 => "Device name not specified",
            802 => "Device type not specified",
            803 => "Device type invalid",
        ),
        "person_picture" => array(
            900 => "Person number not specified",
            901 => "Unknown person number",
            902 => "No picture for this person",
        ),
        "person_valid_pan" => array(
            1000 => "Person number not specified",
            1001 => "Unknown person number",
            1002 => "No valid PAN-s for this person",
        ),
    );

    function getMessage($message, $error) {
        return $this->_errorMessage[$message][$error];
    }
}

