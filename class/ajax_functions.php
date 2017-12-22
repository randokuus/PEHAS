<?php
/**
 * AJAX backend functions
 *
 * All functions defined in this file will be automatically exported by
 * sajax. This file should not contain any code out of function definitions.
 * Please be carefull with functions you create here, since they can be called
 * directly with ajax requests.
 *
 * @link http://www.modernmethod.com/sajax/
 * @package ajax
 */

/**
 * Get formatted date
 *
 * @param int|NULL $timestamp if timestamp is not passed than current time
 *  is used
 * @return string
 */
function get_date($timestamp = null)
{
    return $timestamp == null ? date('l dS of F Y h:i:s A') :
        date('l dS of F Y h:i:s A', $timestamp);
}


/**
 * Event changing
 *
 * @param int $eventid id of changed event
 * @param string $start_date start date of event
 * @param string $start_time start time of event
 * @param string $end_date end date of event
 * @param string $end_time end time of event
 * @return string
 */

function eventchange($eventid, $start_date, $start_time, $end_date, $end_time) {
    require_once(SITE_PATH . "/class/module.events.class.php");
    $event = new events();

    $result = $event->addEventSimple($eventid, $start_date, $start_time, $start_date, $end_time, false, "", "", 0, 0, 0, 0, 0);
    return $result;
}

/**
 * Event adding
 *
 * @param string $start_date start date of event
 * @param string $start_time start time of event
 * @param string $end_date end date of event
 * @param string $end_time end time of event
 * @param int $all_day indicates if event is all day event (1) or not (0)
 * @param string $name name of the event
 * @param string $text description of the event
 * @param int $active_month in month view we need this to keep in track of the active month
 * @param int $active_year in month view we need this to keep in track of the active year
 * @return string
 */

function eventadd($start_date, $start_time, $end_date, $end_time, $all_day, $name, $text, $priority = 0, $category = 0, $type = 0, $active_month = 0, $active_year = 0) {
    require_once(SITE_PATH . "/class/module.events.class.php");
    $event = new events();
    $result = $event->addEventSimple($eventid, $start_date, $start_time, $end_date, $end_time, $all_day, $name, $text, $priority, $category, $type, $active_month, $active_year);
    return $result;
}

/**
 * Event move
 *
 * @param int $eventid id of changed event
 * @param string $start_date start date of event
 */

function eventmove($eventid, $start_date) {
    require_once(SITE_PATH . "/class/module.events.class.php");
    $event = new events();

    $result = $event->moveEventSimple($eventid, $start_date);
    return $result;
}

/**
 * Event delete
 *
 * @param int $eventid id of changed event
 */

function eventdel($eventid) {
    require_once(SITE_PATH . "/class/module.events.class.php");
    $event = new events();

    $result = $event->deleteEventSimple($eventid);
    return $result;
}

/**
 * Reservation changing
 *
 * @param int $reservationid id of changed reservation
 * @param string $start_date start date of reservation
 * @param string $start_time start time of reservation
 * @param string $end_date end date of reservation
 * @param string $end_time end time of reservation
 * @return string
 */

function reservationchange($reservationid, $start_date, $start_time, $end_date, $end_time) {
    require_once(SITE_PATH . "/class/module.reservation.class.php");
    $reservation = new reservation();

    $result = $reservation->addReservationSimple($reservationid, $start_date, $start_time, $start_date, $end_time, false, "", 0, 0, 0);
    return $result;
}

/**
 * Reservation adding
 *
 * @param int $resource id of resource
 * @param string $start_date start date of reservation
 * @param string $start_time start time of reservation
 * @param string $end_date end date of reservation
 * @param string $end_time end time of reservation
 * @param int $all_day indicates if reservation is all day reservation (1) or not (0)
 * @param string $name name of the reservation
 * @param string $text description of the reservation
 * @param int $active_month in month view we need this to keep in track of the active month
 * @param int $active_year in month view we need this to keep in track of the active year
 * @return string
 */

function reservationadd($resource, $start_date, $start_time, $end_date, $end_time, $all_day, $name, $active_month = 0, $active_year = 0) {
    require_once(SITE_PATH . "/class/module.reservation.class.php");
    $reservation = new reservation();
    $result = $reservation->addReservationSimple(0, $start_date, $start_time, $end_date, $end_time, $all_day, $name, $active_month, $active_year, $resource);
    return $result;
}

/**
 * reservation move
 *
 * @param int $reservationid id of changed reservation
 * @param string $start_date start date of reservation
 */

function reservationmove($reservationid, $start_date) {
    require_once(SITE_PATH . "/class/module.reservation.class.php");
    $reservation = new reservation();

    $result = $reservation->moveReservationSimple($reservationid, $start_date);
    return $result;
}

/**
 * reservation delete
 *
 * @param int $reservationid id of changed reservation
 */

function reservationdel($reservationid) {
    require_once(SITE_PATH . "/class/module.reservation.class.php");
    $reservation = new reservation();

    $result = $reservation->deleteReservationSimple($reservationid);
    return $result;
}

/**
 * use profile data query
 *
 * @param string $user_code user number (isikukood)
 */

function getprofiledata($user_code, $typeId, $schoolId) {
    require_once(SITE_PATH . "/class/IsicCommon.php");
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbUsers = IsicDB::factory('Users');
    $result = $isicDbUsers->getRecordByCode($user_code);
    if ($result) {
        $ic = IsicCommon::getInstance();
        $personFields = $ic->getPersonFieldsFromUserData($result);
        if (!$ic->isValidPictureAge($personFields['person_pic'], $typeId, $schoolId)) {
            $personFields['person_pic'] = '';
        }
        return $personFields;
    }
    return false;
}

/**
 * use profile data query
 *
 * @param string $user_code user number (isikukood)
 */
function getuserdata($user_code) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbUsers = IsicDB::factory('Users');
    return $isicDbUsers->getRecordByCode($user_code);
}

/**
 * use profile data query
 *
 * @param string $user_code user number (isikukood)
 */
function getuserstatusdata($group_id, $user_id) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbUserStatuses = IsicDB::factory('UserStatuses');
    return $isicDbUserStatuses->getRecordByGroupUser($group_id, $user_id);
}

/**
 * all schools
 */
function get_all_schools_data() {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbSchools = IsicDB::factory('Schools');
    return $isicDbSchools->listRecordsFields(array('id', 'name'));
}

function get_user_status_data_by_school_cardtype($user_code, $school_id, $card_type_id) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbUserStatuses = IsicDB::factory('UserStatuses');
    $isicDbUsers = IsicDB::factory('Users');
    $userData = $isicDbUsers->getRecordByCode($user_code);
    if ($userData) {
        return $isicDbUserStatuses->getRecordByUserSchoolCardType($userData['user'], $school_id, $card_type_id);
    }
    return false;
}

function get_card_types_by_school($schoolId) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbSchools = IsicDB::factory('Schools');
    $isicDbCardTypes = IsicDB::factory('CardTypes');
    $schoolData = $isicDbSchools->getRecord($schoolId);
    if ($schoolData) {
        $cardTypeIds = $isicDbCardTypes->getAllowedIdListForAddBySchool($schoolId);
        return $isicDbCardTypes->getRecordsByIdsOrderedByPriorityName($cardTypeIds);
    }
    return false;
}

function get_newsletters_by_card_type($card_type_id) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbNewsletters = IsicDB::factory('Newsletters');
    return $isicDbNewsletters->getAllowedNewslettersByCardType($card_type_id);
}

function get_card_deliveries_by_school($schoolId, $showHomeDelivery = true) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbCardDeliveries = IsicDB::factory('CardDeliveries');
    $cardDeliveries = $isicDbCardDeliveries->getRecordsBySchool($schoolId, $showHomeDelivery);
    return sizeof($cardDeliveries) > 0 ? $cardDeliveries : false;
}

function get_card_deliveries_by_school_card_type($schoolId, $cardTypeId, $showHomeDelivery = true) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    $isicDbCardDeliveries = IsicDB::factory('CardDeliveries');
    $cardDeliveries = $isicDbCardDeliveries->getRecordsBySchoolCardType($schoolId, $cardTypeId, $showHomeDelivery);
    return sizeof($cardDeliveries) > 0 ? $cardDeliveries : false;
}

function get_users_by_groups($groupIds, $sendType, $faculty) {
    require_once(SITE_PATH . "/class/IsicDB.php");
    /** @var IsicDB_Users $isicDbUsers */
    $isicDbUsers = IsicDB::factory('Users');
    $groupList = explode(',', $groupIds);
    list($userIds, $users) = $isicDbUsers->getRecordsByGroupsWithFilter($groupList, $sendType, $faculty);
    return sizeof($users) > 0 ? $users : false;
}
