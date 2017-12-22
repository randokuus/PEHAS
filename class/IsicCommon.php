<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/text.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicNameSplitter.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicLogger.php");
require_once SITE_PATH . "/" . $GLOBALS["directory"]["object"] . '/Isic/IsicImage.php';

class IsicCommon {
    private static $isicCommon = null;
    /**
     * @var array merged array with _GET and _POST data
     */
    var $vars = array();
    /**
     * Current language code
     *
     * @var string
     * @access protected
     */
    var $language;
    /**
     * Active template set
     *
     * @var int
     * @access private
     */
    var $tmpl;
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;
    /**
     * @var boolean does this module provide additional parameters to admin page admin
     */
    var $content_module = false;
    /**
     * @var array additional parameters set at the page admin for this template
     */
    var $module_param = array();
    /**
     * @var integer active user id
     */
    var $userid = false;
    /**
     * @var integer user group ID
     */
    var $usergroup = false;
    /**
     * @var array user groups
     */
    var $usergroups = false;
    /**
     * @var user type (1 - can view all cards from the school his/her usergroup belongs to, 2 - only his/her own cards)
     */
    var $user_type = false;

    /**
     * @var user code (personal number (estonian id-code for example))
     */
    var $user_code = false;
    /**
     * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
     */
    var $user_access = 0;
    /**
     * Users that are allowed to access the same cards as current user
     *
     * @var array
     * @access protected
     */
    var $allowed_users = array();
    /**
     * Schools that are allowed to current user
     *
     * @var array
     * @access protected
     */
    var $allowed_schools = array();
    var $allowed_schools_all = array();
    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */
    var $cachelevel = TPL_CACHE_NOTHING;
    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */
    var $cachetime = 1440;
    /**
     * Prefix of a card number
     *
     * @var string
     * @access protected
     */
    var $card_number_prefix = "999999";

    /**
     * image sizes
     *
     * @var string
     * @access protected
     */

    var $image_size = '307x372';
    var $image_size_x = '307';
    var $image_size_y = '372';

    /**
     * Image size - thumbnail
     *
     * @var string
     * @access protected
     */

    var $image_size_thumb = '83x100';

    /**
     * empty values in card log record
     *
     * @var array
     * @access protected
     */

    var $log_field_empty_values = array(
        "prev_card_id" => array(""),
        "status_id" => array("()"),
        "active" => array(""),
        "returned" => array(""),
        "language_id" => array(""),
        "kind_id" => array(""),
        "type_id" => array(""),
        "school_id" => array(""),
        "person_name_first" => array(""),
        "person_name_last" => array(""),
        "person_birthday" => array("", "--"),
        "person_number" => array(""),
        "person_email" => array(""),
        "person_newsletter" => array(""),
        "isic_number" => array(""),
        "activation_date" => array(""),
        "deactivation_time" => array(""),
        "expiration_date" => array(""),
        "returned_date" => array(""),
        "confirm_user" => array(""),
        "confirm_payment_collateral" => array(""),
        "confirm_payment_cost" => array(""),
        "confirm_admin" => array(""),
    );

    /**
     * fields to ignore in card log records
     *
     * @var array
     * @access protected
     */
     var $log_field_ignore = array(
        "adddate",
        "adduser",
        "moddate",
        "moduser",
        "confirm_payment",
        "prev_card_id"
    );

    /**
     * log fields with boolean type
     *
     * @var array
     * @access protected
     */
     var $log_field_boolean = array(
        "active",
        "returned",
        "person_newsletter",
        "confirm_user",
        "confirm_payment_collateral",
        "confirm_payment_cost",
        "confirm_admin"
    );

    /**
     * Possible auth-types for regular users
     *
     * @var string
     * @access protected
     */

     var $user_auth_types = '2,3,4,5,6';

    /**
     * User type - admin
     *
     * @var int
     * @access protected
     */
    var $user_type_admin = 1; // admin

    /**
     * User type - user
     *
     * @var int
     * @access protected
     */
    var $user_type_user = 2; // admin

    /**
     * Default user type
     *
     * @var int
     * @access protected
     */
    var $default_user_type = 2; // regular user

    /**
     * Default password
     *
     * @var string
     * @access protected
     */
    var $default_password = "-";

    /**
     * States for applications
     *
     * @var int
     * @access protected
     */
    var $a_state_not_done = 1;
    var $a_state_user_confirm = 2;
    var $a_state_parent_confirm = 3;
    var $a_state_status_check = 4;
    var $a_state_admin_confirm = 5;
    var $a_state_processed = 6;
    var $a_state_rejected = 7;

    /**
     * States for cards
     *
     * @var int
     * @access protected
     */
    var $c_state_ordered = 1;
    var $c_state_distributed = 2;
    var $c_state_activated = 3;
    var $c_state_deactivated = 4;

    /**
     * Card kinds (regular vs. union)
     *
     * @var int
     * @access protected
     */
    var $c_kind_regular = 1;
    var $c_kind_union = 2;

    /**
     * Empty date
     *
     * @var string
     * @access protected
     */
    var $empty_date = '0000-00-00';

    /**
     * Max date
     *
     * @var string
     * @access protected
     */
    var $max_date = '2222-12-31';

    /**
     * State which represents if application has been converted into card
     *
     * @var int
     * @access protected
     */
    var $state_application_into_card = 6;

    /**
     * Folder for application pictures
     *
     * @var string
     * @access protected
     */
    var $a_pic_folder = "/appl";

    /**
     * Tmp-folder for application pictures
     *
     * @var string
     * @access protected
     */
    var $a_pic_folder_tmp = "/appl_tmp";

    /**
     * Picture prefix for application pics
     *
     * @var string
     * @access protected
     */
    var $a_pic_prefix = "APPL";

    /**
     * Folder for user pictures
     *
     * @var string
     * @access protected
     */
    var $u_pic_folder = "/user";

    /**
     * Tmp-folder for user pictures
     *
     * @var string
     * @access protected
     */
    var $u_pic_folder_tmp = "/user_tmp";

    /**
     * Picture prefix for user pics
     *
     * @var string
     * @access protected
     */
    var $u_pic_prefix = "USER";

    /**
     * Template id for application modification page for admins
     *
     * @var int
     * @access protected
     */
    var $template_application_prolong_replace_admin = 860;

    /**
     * Template id for application modification page for admins
     *
     * @var int
     * @access protected
     */
    var $template_application_modify_admin = 860;

    /**
     * Template id for application modification page for users
     *
     * @var int
     * @access protected
     */
    var $template_application_modify_user = 864;

    /**
     * Template id for application modification page for users (hidden school)
     *
     * @var int
     * @access protected
     */
    var $template_application_modify_user_hidden_school = 865;

    /**
     * Template id for application listview
     *
     * @var int
     * @access protected
     */
    var $template_application_list = 860;

    /**
     * Template id for card list page
     *
     * @var int
     * @access protected
     */

    var $template_card_list = 870;

    /**
     * Log types
     *
     * @var int
     * @access protected
     */
    var $log_type_add = 1;
    var $log_type_mod = 2;
    var $log_type_del = 3;

    /**
     * User vs. application record field macthes
     *
     * @var array
     * @access protected
     */
    var $user_appl_match;

    /**
     * System user ID
     *
     * @var int
     * @access protected
     */
    var $system_user = 0;

    var $current_time = false;

    /**
     * @var IsicDB_Users
     */
    var $isicDbUsers;

    /**
     * @var IsicDB_Cards
     */
    var $isicDbCards;
    var $isicDbCardTypes = false;

    /**
     * @var IsicDB_CardTypeSchools
     */
    var $isicDbCardTypeSchools = null;

    /**
     * @var IsicDB_UserGroups
     */
    var $isicDbUserGroups = null;

    protected $userCodeList;
    /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */
    private function IsicCommon() {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];
        $this->user_email = $GLOBALS["user_data"][8];
        $this->user_active_school = $GLOBALS["user_data"][9];
        $this->children_list = $GLOBALS["user_data"][10];

        $this->assignUserCodeList();

        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->user_appl_match = $this->isicDbUsers->applicationMatchFields;
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');
        $this->isicDbCardTypeSchools = IsicDB::factory('CardTypeSchools');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->allowed_schools_all = $this->createAllowedSchools();
        $this->allowed_schools = $this->getActiveAllowedSchools();
        $this->allowed_groups = $this->createAllowedUserGroups($this->allowed_schools);
        $this->system_user = $this->isicDbUsers->getIdByUsername(SYSTEM_USER);
        $this->setCurrentTime();
    }

    static function getInstance() {
        if (null == self::$isicCommon) {
            self::$isicCommon = new IsicCommon();
        }
        return self::$isicCommon;
    }

    function getActiveAllowedSchools() {
        if ($this->user_active_school && in_array($this->user_active_school, $this->allowed_schools_all)) {
            return array($this->user_active_school);
        }
        return $this->allowed_schools_all;
    }

    /**
     * Creates an array of all the schools that current user has right to see
     *
     * @return array list of school id's
    */
    function createAllowedSchools() {
        return $this->isicDbUserGroups->listAllowedSchools();
    }

    /**
     * Creates an array of all the user groups that current user has right to see
     *
     * @param array $school_list school list
     * @return array list of group id's
    */

    function createAllowedUserGroups($school_list) {
        return $this->usergroups;
    }

    /**
     * Checks if current user can view application-record
     *
     * @param array $appldata application record
     * @return boolean true, if current user can view record, false otherwise
    */
    function canViewApplication($applData) {
        if ($this->user_type == $this->user_type_admin) {
            return in_array($applData['school_id'], $this->allowed_schools);
        } elseif ($this->user_type == $this->user_type_user) {
            return
                in_array($applData["person_number"], $this->getCurrentUserCodeList()) ||
                $applData['parent_user_id'] == $this->userid
            ;
        }
        return false;
    }

    /**
     * Checks if current user can modify application-record
     *
     * @param array $application application record
     * @return boolean true, if current user can modify record, false otherwise
    */
    public function canModifyApplication($applData) {
        if ($this->user_type == $this->user_type_admin && $applData['state_id'] != $this->a_state_processed) { // admins can only modify if not turned into card
            return in_array($applData['school_id'], $this->allowed_schools);
        } else if ($this->user_type == $this->user_type_user &&
            ($applData['state_id'] == $this->a_state_not_done ||
            $applData['state_id'] == $this->a_state_user_confirm)) { // users can only modify if still half done
            return
                in_array($applData["person_number"], $this->getCurrentUserCodeList()) ||
                $applData['parent_user_id'] == $this->userid
            ;
        }
        return false;
    }

    /**
     * Checks if current user can delete application-record
     *
     * @param int $appl_data application data array
     * @return boolean true, if current user can delete record, false otherwise
    */
    function canDeleteApplication($appl_data) {
        // no deleting is possible, if any of the payments has been done
        if ($appl_data["confirm_payment_collateral"] || $appl_data["confirm_payment_cost"]) {
            return false;
        }
        if ($this->user_type == $this->user_type_admin && $appl_data["state_id"] != $this->a_state_processed) { // admins can only delete if not turned into card
            return in_array($appl_data["school_id"], $this->allowed_schools);
        } elseif ($this->user_type == $this->user_type_user && $appl_data["state_id"] == $this->a_state_not_done) { // users can only delete if still half done
            return
                in_array($appl_data["person_number"], $this->getCurrentUserCodeList()) ||
                $appl_data["parent_user_id"] == $this->userid
            ;
        }
        return false;
    }

    /**
     * Checks if current user can view card-record
     *
     * @param int $card_school school id of a card
     * @param string $person_number id-code of a card
     * @return boolean true, if current user can view record, false otherwise
    */
    function canViewCard(array $cardData) {
        return $this->isicDbUsers->canViewCard($cardData);
    }

    /**
     * Checks if current user can modify card-record
     *
     * @param int $card_school school id of a card
     * @param string $person_number id-code of a card
     * @param int $state_id state id of a card
     * @return boolean true, if current user can modify record, false otherwise
    */
    function canModifyCard($card_school, $person_number, $state_id = 0) {
        throw new Exception('Deprecated method! Why are you using it?');
        //return $this->isicDbUsers->canModifyCard($card_school, $person_number, $state_id);
    }

    /**
     * Checks if current user can distribute card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can distribute card, false otherwise
    */
    function canDistributeCard($card_data) {
        return $this->isicDbUsers->canDistributeCard($card_data);
    }

    /**
     * Checks if current user can activate card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can activate card, false otherwise
    */
    function canActivateCard($card_data) {
        return $this->isicDbUsers->canActivateCard($card_data);
    }

    /**
     * Checks if current user can deactivate card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can deactivate card, false otherwise
    */
    function canDeactivateCard($card_data) {
        return $this->isicDbUsers->canDeactivateCard($card_data);
    }

    /**
     * Checks if current user can return card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can mark card as returned, false otherwise
    */
    function canReturnCard($card_data) {
        return $this->isicDbUsers->canReturnCard($card_data);
    }

    /**
     * Checks if current user can replace card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can replace card, false otherwise
    */
    function canReplaceCard($card_data) {
        return $this->isicDbUsers->canReplaceCard($card_data);
    }

    /**
     * Checks if current user can prolong card-record
     *
     * @param array $card_data array with card record data
     * @return boolean true, if current user can prolong card, false otherwise
    */
    function canProlongCard($card_data) {
        return $this->isicDbUsers->canProlongCard($card_data);
    }

    /**
     * Checks if current user can delete card-record
     *
     * @param int $card_school school if of a card
     * @return boolean true, if current user can delete record, false otherwise
    */
    function canDeleteCard(array $cardData) {
        return $this->isicDbUsers->canDeleteCard($cardData);
    }

    /**
     * Checks if current user can view user-record
     *
     * @param array $userData user record
     * @return boolean true, if current user can view record, false otherwise
    */
    function canViewUser($userData) {
        if ($this->user_type == $this->user_type_admin) {
            $t_groups = explode(',', $userData['ggroup']);
            return
                count(array_intersect($this->allowed_groups, $t_groups)) > 0 ||
                $userData['user_code'] == $this->user_code
            ;
        } else if ($this->user_type == $this->user_type_user) {
            return in_array($userData['user_code'], $this->getCurrentUserCodeList());
        }
        return false;
    }

    /**
     * Checks if current user can view user status record
     *
     * @param array $statusData array with status record data
     * @return boolean true, if current user can view record, false otherwise
    */
    function canViewUserStatus($statusData) {
        if ($this->user_type == $this->user_type_admin) {
            return
                in_array($statusData['group_id'], $this->allowed_groups) ||
                $statusData['user_id'] == $this->userid
            ;
        } elseif ($this->user_type == $this->user_type_user) {
            return
                $statusData['user_id'] == $this->userid ||
                in_array($statusData['user_id'], $this->getChildrenIdList())
            ;
        }
        return false;
    }

    /**
     * Checks if current user can modify user-record
     *
     * @param string $user_groups comma separated list of groups user belongs to
     * @param string $user_code personal number of an user
     * @return boolean true, if current user can modify record, false otherwise
    */
    function canModifyUser($userData) {
        if ($this->user_type == $this->user_type_admin) {
            $t_groups = explode(',', $userData['ggroup']);
            return
                count(array_intersect($this->allowed_groups, $t_groups)) > 0 ||
                $userData['user_code'] == $this->user_code
            ;
        } elseif ($this->user_type == $this->user_type_user) {
            return in_array($userData['user_code'], $this->getCurrentUserCodeList());
        }
        return false;
    }

    /**
     * Checks if current user can delete user-record
     *
     * @return boolean true, if current user can delete record, false otherwise
    */

    function canDeleteUser() {
        return false;
    }

    /**
     * Calculates birthday from social securit number (isikukood)
     *
     * @param str $socsecnum soscial security number
     * @return string birthday in format yyyy-mm-dd
    */

    function calcBirthdayFromNumber($socsecnum) {
        if (strlen($socsecnum) == 11) {
            if (substr($socsecnum, 0, 1) <= "2") {
                $century = "18";
            } elseif (substr($socsecnum, 0, 1) <= "4") {
                $century = "19";
            } else {
                $century = "20";
            }
            $t_date = $century . substr($socsecnum, 1, 2) . "-" . substr($socsecnum, 3, 2) . "-" . substr($socsecnum, 5, 2);
            if (strtotime($t_date) != false && strtotime($t_date) != -1 && strtotime($t_date) < time()) {
                return $t_date;
            }
        }
        return false;
    }

    /**
     * Generatates new card-number from given ISIC-number
     *
     * @param string $isic_number ISIC number
     * @return string new card number
    */

    function getCardNumber($isic_number) {
        $t_number = 0;
        if ($isic_number) {
            // if last symbol is not number, then skip last symbol
            if (substr($isic_number, -1) >= '0' && substr($isic_number, -1) <= '9') {
                $t_number = $this->card_number_prefix . substr($isic_number, -10);
            } else {
                $t_number = $this->card_number_prefix . substr($isic_number, -11, -1);
            }
        }
        return $t_number;
    }

    /**
     * Releases ISIC-number
     *
     * @param string $number reserved number
    */

    function releaseISICNumber($type = 0, $number = '') {
        if ($type) {
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_type`.*
                FROM
                    `module_isic_card_type`
                WHERE
                    `module_isic_card_type`.`id` = ?
                LIMIT 1
                ', $type);

            if ($trow = $r->fetch_assoc()) {
                // number from the number table
                if ($trow["number_type"] == 1) {
                    $r2 = &$this->db->query('
                        UPDATE `module_isic_card_number` SET
                            `module_isic_card_number`.`reserved` = 0,
                            `module_isic_card_number`.`reserved_date` = ?
                        WHERE
                            `module_isic_card_number`.`card_type` = ! AND
                            `module_isic_card_number`.`card_number` = ?',
                        '0000-00-00 00:00:00', $type, $number);
                }
            }
        }
    }

    /**
     * Generatates new ISIC-number
     *
     * @param int $type type of the card
     * @param int $school_id school of the card
     * @return string new ISIC number
    */

    function getISICNumber($type = 0, $school_id = 0) {
        $t_number = '';
        if ($type) {
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_type`.*
                FROM
                    `module_isic_card_type`
                WHERE
                    `module_isic_card_type`.`id` = ?
                LIMIT 1
                ', $type);

            if ($trow = $r->fetch_assoc()) {
                switch ($trow["number_type"]) {
                    case 1: // number from the number table
                        $school_code = '%';
                        if ($trow["use_school_code"]) {
                            $r = &$this->db->query('
                                SELECT
                                    `module_isic_school`.*
                                FROM
                                    `module_isic_school`
                                WHERE
                                    `module_isic_school`.`id` = !
                                LIMIT 1
                                ', $school_id);
                            if ($row = $r->fetch_assoc()) {
                                $school_code = str_pad($row["ehis_code"], 4, '0', STR_PAD_LEFT) . '%';
                            }
                        }
                        $r = &$this->db->query('
                            SELECT
                                `module_isic_card_number`.*
                            FROM
                                `module_isic_card_number`
                            WHERE
                                `module_isic_card_number`.`card_type` = ? AND
                                `module_isic_card_number`.`reserved` = 0 AND
                                `module_isic_card_number`.`card_number` LIKE ?
                            ORDER BY `card_number` ASC LIMIT 1
                            ', $type, $school_code);

                        if ($row = $r->fetch_assoc()) {
                            $t_number = $row['card_number'];

                            $r2 = &$this->db->query('
                                UPDATE `module_isic_card_number` SET
                                    `module_isic_card_number`.`reserved` = 1,
                                    `module_isic_card_number`.`reserved_date` = NOW()
                                WHERE `module_isic_card_number`.`id` = !
                            ', $row['id']);
                        }
                    break;
                    case 2: // number from the range table
                        $r = &$this->db->query('
                            SELECT
                                `module_isic_card_range`.*
                            FROM
                                `module_isic_card_range`
                            WHERE
                                `module_isic_card_range`.`card_type` = ? AND
                                `module_isic_card_range`.`last_number` < `module_isic_card_range`.`range_end`
                            ORDER BY entrydate ASC LIMIT 1
                            ', $type);

                        while ($row = $r->fetch_assoc()) {
                            if ($row['last_number']) {
                                $t_number = substr($row['last_number'], 0, 1) . str_pad(substr($row['last_number'], 1) + 1, strlen($row['last_number'] - 1), '0', STR_PAD_LEFT);
                            } else {
                                $t_number = $row['range_beg'];
                            }

                            $r2 = &$this->db->query('
                                UPDATE `module_isic_card_range` SET `module_isic_card_range`.`last_number` = ?
                                WHERE `module_isic_card_range`.`id` = !
                            ', $t_number, $row['id']);
                        }
                    break;
                    default:
                    break;
                }
            }
        }
        return $t_number;
    }

    /**
     * Gets card status, needed for receiving status of the parent card
     *
     * @return array
    */

    function getCardStatus($card_id) {
        $status = 0;

        if ($card_id) {
            $r = &$this->db->query('SELECT `status_id` FROM `module_isic_card` WHERE `id` = !', $card_id);
            if ($data = $r->fetch_assoc()) {
                $status = $data["status_id"];
            }
        }

        return $status;
    }

    /**
     * Generates array of card_types with info about if card should be marked as exported
     * during admin-confirm automatically or not
     *
     * @return array
    */

    function getCardTypeAutoExport() {
        $autoexport = array();

        $r = &$this->db->query('SELECT `id`, `auto_export` FROM `module_isic_card_type`');
        while ($data = $r->fetch_assoc()) {
            $autoexport[$data["id"]] = $data["auto_export"];
        }

        return $autoexport;
    }

    /**
     * Returns card type record
     *
     * @param int $card_type_id card type ID
     * @param int $school_id school ID
     * @return array
    */

    function getCardTypeRecord($card_type_id, $school_id = 0) {
        if ($card_type_id) {
            $r = &$this->db->query("
                SELECT
                    `module_isic_card_type`.*,
                    IF (`module_isic_card_type_school`.`id`, `module_isic_card_type_school`.`description`, '') AS `description_school`
                FROM
                    `module_isic_card_type`
                LEFT JOIN
                    `module_isic_card_type_school`
                    ON
                        `module_isic_card_type`.`id` = `module_isic_card_type_school`.`type_id` AND
                        `module_isic_card_type_school`.`school_id` = !
                WHERE
                    `module_isic_card_type`.`id` = !",
                $school_id,
                $card_type_id
            );
            if ($data = $r->fetch_assoc()) {
                if (!$data["pic"]) {
                    $data["pic"] = "img/nopic.gif";
                }
                return $data;
            }
        }

        return false;
    }

    /**
     * Checks if user already has cards of given type
     *
     * @param string $person_number person number
     * @param int $type_id type ID
     * @return boolean
    */

    function getUserCardTypeExists($person_number = '', $type_id = 0) {
        // general sums
        if ($person_number && $type_id) {
            $r = &$this->db->query('SELECT `id` FROM `module_isic_card` WHERE `person_number` = ? AND `type_id` = ! LIMIT 1', $person_number, $type_id);
            if ($r->fetch_assoc()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generatates expiration date for the card type
     *
     * @param int $type_id card type ID
     * @param string $expiration_compare expiration to compare with
     * @param boolean $check_expiration_repl_card - true, if card type expiration
     * repl. card parameter should be checked during new expiration calcluation
     *
     * @return string expiration date
    */
    function getCardExpiration($type_id = 0, $expiration_compare = '', $check_expiration_repl_card = false) {
        $cur_year = date("Y", $this->getCurrentTime());
        $cur_mon = date("n", $this->getCurrentTime());
        $cur_day = date("j", $this->getCurrentTime());

        $expiration_month = 12;

        $data = $this->isicDbCardTypes->getRecord($type_id);
        if ($data) {
            $expiration_year = $data["expiration_year"] - 1;
            $expiration_break = $data["expiration_break"];
            $expiration_break_day = $data["expiration_break_day"];
            $expiration_type = $data["expiration_type"];
            $prolong_limit = $data["prolong_limit"];
            $expiration_repl_card = $data["expiration_repl_card"];
        }

        // if expiration break month was not set, then defaults to january
        if (!$expiration_break) {
            $expiration_break = 1;
        }

        // if expiration break day was not set, then defaults to 1
        if (!$expiration_break_day) {
            $expiration_break_day = 1;
        }

        // if breakpoint is larger than current month, then increasing expiration year by one
        if ($expiration_break < $cur_mon || $expiration_break == $cur_mon && $expiration_break_day <= $cur_day) {
            $expiration_year++;
        }

        // if expiration type is 0 then this means that card expires at the end of the calendar year
        if ($expiration_type == 0) {
        } else { // otherwise it expires the same month it was created (different year though)
            $expiration_month = $cur_mon;
        }

        $expiration = date("Y-m-t", mktime(0, 0, 0, $expiration_month, 1, $cur_year + $expiration_year));
        $expiration_prolong_limit = $this->calcExpirationProlongLimit($expiration_compare, $prolong_limit);
        $cur_date = date("Y-m-d", mktime(0, 0, 0, $cur_mon, $cur_day, $cur_year));

        if ($cur_date <= $expiration_prolong_limit && /*$check_expiration_repl_card &&*/ $expiration_repl_card == 1) {
            $expiration = $expiration_compare;
        }

        return $expiration;
    }

    function calcExpirationProlongLimit($expiration_compare = '', $prolong_limit = 0) {
        if ($expiration_compare) {
            $ec_time = strtotime($expiration_compare);
            if ($ec_time != false && $ec_time != -1) {
                $ec_year = date("Y", $ec_time);
                $ec_mon = date("n", $ec_time);
                $ec_day = date("j", $ec_time);
                return date("Y-m-d", mktime(0, 0, 0, $ec_mon - $prolong_limit, $ec_day, $ec_year));
            }
        }
        return '';
    }

    /**
     * Returns application record
     *
     * @param int $application application id
     * @return array of application data
    */
    function getApplicationRecord($application) {
        $applDb = IsicDB::factory('Applications');
        if ($application) {
            return $applDb->getRecord($application);
        }
        return false;
    }

    /**
     * Returns card record
     *
     * @param int $card card id
     * @return array of card data
    */
    function getCardRecord($card) {
        return $this->isicDbCards->getRecord($card);
    }

    /**
     * Returns user record
     *
     * @param int $user user id
     * @return array of user data
    */

    function getUserRecord($user) {
        if ($user) {
        $r = &$this->db->query("
            SELECT
                `module_user_users`.*
            FROM
                `module_user_users`
            WHERE
                `module_user_users`.`user` = !", $user);

            return $r->fetch_assoc();
        }
        return false;
    }

    /**
     * Returns given user's unfinished application record
     *
     * @param string $user_code user code
     * @param int $hiddenSchoolId shows if only hidden/non-hidden school applications should be shown
     * @return id of an application or 0 if none found
    */
    function getUserApplication($user_code, $onlyHiddenSchool = 0, $hiddenSchoolId = 0) {
        if ($user_code) {
            if ($onlyHiddenSchool) {
                $schoolQuery = "
                    AND `module_isic_application`.`school_id` = {$hiddenSchoolId}
                ";
            }  else {
                $schoolQuery = "
                    AND (`module_isic_application`.`school_id` <> {$hiddenSchoolId} OR $hiddenSchoolId = 0)
                ";
            }

            $r = &$this->db->query("
            SELECT
                `module_isic_application`.`id`
            FROM
                `module_isic_application`
            WHERE
                `module_isic_application`.`state_id` = ! AND
                `module_isic_application`.`person_number` = ?
                !
            ORDER BY `module_isic_application`.`moddate` DESC
            LIMIT 1",
            $this->a_state_not_done,
            $user_code,
            $schoolQuery);

            if ($data = $r->fetch_assoc()) {
                return $data["id"];
            }
        }
        return 0;
    }

    /**
     * Checks if user already has cards of given type
     *
     * @param string $person_number person number
     * @param int $card_id card ID
     * @param int $type_id type ID
     * @return boolean
    */

    function isUserCardTypeFirst($person_number = '', $card_id = 0, $type_id = 0) {
        // general sums
        if ($person_number && $card_id && $type_id) {
            $r = &$this->db->query('SELECT `id` FROM `module_isic_card` WHERE `person_number` = ? AND `id` <> ! AND `type_id` = ! LIMIT 1', $person_number, $card_id, $type_id);
            if ($r->fetch_assoc()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Finds last card of an user by person number, school and type
     * using activation date for deciding which card is the latest
     *
     * @param string $person_number person number
     * @param int $school_id school ID
     * @param int $type_id type ID
     * @return boolean
    */
    function getUserLastCard($person_number = '', $school_id = 0, $type_id = 0) {
        return $this->getUserLastCardByDate($person_number, $school_id, $type_id, $this->max_date);
    }

    /**
     * Finds last card of an user by person number, school and type
     * using activation date for deciding which card is the latest
     *
     * @param string $person_number person number
     * @param int $school_id school ID
     * @param int $type_id type ID
     * @param int $until_date date until cards should be looked at
     * @return boolean
    */
    function getUserLastCardByDate($person_number = '', $school_id = 0, $type_id = 0, $until_date = '') {
        // general sums
        if ($person_number && $school_id && $type_id) {
            $r = &$this->db->query('
                SELECT *
                FROM
                    `module_isic_card`
                WHERE
                    `person_number` = ? AND
                    `school_id` = ! AND
                    `type_id` = ! AND
                     `kind_id` = ! AND
                    `exported` > ? AND
                    `adddate` <= ?
                ORDER BY
                    `activation_date` DESC
                LIMIT 1',
                $person_number,
                $school_id,
                $type_id,
                $this->c_kind_regular,
                $this->empty_date,
                $until_date
            );
            if ($data = $r->fetch_assoc()) {
                return $data;
            }
        }
        return false;
    }

    /**
     * Finds state for application reject reason
     *
     * @param int $reason_id reason ID
     * @return int state id
    */
    function getApplicationRejectState($reason_id = 0) {
        if ($reject_data = $this->getApplicationRejectRecord($reason_id)) {
            return $reject_data['state_id'];
        }
        return false;
    }

    /**
     * Gets application reject reason record
     *
     * @param int $reason_id reason ID
     * @return array
    */
    function getApplicationRejectRecord($reason_id = 0) {
        if ($reason_id) {
            $r = &$this->db->query('SELECT * FROM `module_isic_application_reject_reason` WHERE `id` = ! LIMIT 1', $reason_id);
            if ($data = $r->fetch_assoc()) {
                return $data;
            }
        }
        return false;
    }

    /**
     * Finds application bindings with other cards / applications
     *
     * @param array $appl application record
     * @return array list of card / application id-s
    */

    function getApplicationBindings($appl) {
        $cardTypeBindings = $this->isicDbCardTypes->getBindings();
        if (!is_array($appl) || !is_array($cardTypeBindings[$appl["type_id"]])) {
            return false;
        }
        $bind = false;
        $bval = $cardTypeBindings[$appl["type_id"]];
        // bindings with cards
        $r = &$this->db->query("
            SELECT
                `id`,
                `type_id`
            FROM
                `module_isic_card`
            WHERE
                `person_number` = ? AND
                `type_id` IN (!@) AND
                `school_id` = ! AND
                `expiration_date` >= NOW()
            ORDER BY
                `type_id` ASC,
                `moddate` DESC
            ", $appl["person_number"], $bval, $appl["school_id"]);
        while ($data = $r->fetch_assoc()) {
            $bind["card"][$data["type_id"]][] = array("id" => $data["id"]);
        }

        // bindings with applications
        $r = &$this->db->query("
            SELECT
                `id`,
                `type_id`
            FROM
                `module_isic_application`
            WHERE
                `person_number` = ? AND
                `id` <> ! AND
                `type_id` IN (!@) AND
                `school_id` = ! AND
                NOT (`state_id` IN (!@))
            ORDER BY
                `type_id` ASC,
                `moddate` DESC
            ", $appl["person_number"], $appl["id"], $bval, $appl["school_id"], array($this->a_state_not_done, $this->a_state_processed, $this->a_state_rejected));
        while ($data = $r->fetch_assoc()) {
            $bind["appl"][$data["type_id"]][] = array("id" => $data["id"]);
        }
        return $bind;
    }


    /**
     * Finds group for given school
     *
     * @param int $school_id school id
     *
     * @return string group or groups for given school
    */

    function getSchoolGroup($school_id = 0) {
        $school_group = "";

        if ($school_id) {
            // find regular user groups for given school
            $r = &$this->db->query('SELECT `module_user_groups`.* FROM `module_user_groups` WHERE `isic_school` = ? AND `group_type` = !', $school_id, 2);
            while ($data = $r->fetch_assoc()) {
                if ($school_group) {
                    $school_group .= ",";
                }
                $school_group .= $data["id"];
            }
        }

        return $school_group;
    }

    /**
     * Creates general_url value for card view
     *
     * @access private
     * @return string
     */
    function getGeneralUrl($template_id = 0) {
        return $this->getGeneralUrlByTemplate($template_id);
    }

    /**
     * Creates general_url value for given template
     *
     * @access private
     * @return string
     */
    function getGeneralUrlByTemplate($template_id = 0, $userType = 0) {
        $general_url = $_SERVER["PHP_SELF"] . "#PLEASE_CREATE_ISIC_PAGE";
        $checkedUserType = $userType ? $userType : $this->user_type;

        if ($template_id) {
            $res =& $this->db->query("
                SELECT
                    `content`.*
                FROM
                    `content`
                WHERE
                    `language` = ? AND
                    `template` = ! AND
                    `visible` = 1 AND
                    (
                        `login` = 0 OR
                        `login` = 1 AND
                        `loginusertypes` LIKE '!'
                    )
                ORDER BY
                    `module` ASC
                LIMIT 1", $this->language, $template_id, '%' . $checkedUserType . '%');

            if ($data = $res->fetch_assoc()) {
                $general_url = /*$_SERVER["PHP_SELF"] .*/ "?content=" . $data["content"];
                if ($data["structure"]) {
                    $general_url .= "&structure=" . $data["structure"];
                }
            }
        }

        return $general_url;
    }

    /**
     * Creates an array of all the users who are belonging to the same group
     * as current user does
     *
     * @return array list of user id's
    */

    function createAllowedUsers() {
        $user_list = array();

        if ($this->user_type == $this->user_type_admin) { // in case of admin-users
            if (is_array($this->usergroups)) {
                $r = &$this->db->query('SELECT `module_user_users`.* FROM `module_user_users`');
                while ($data = $r->fetch_assoc()) {
                    $glist = explode(",", $data["ggroup"]);
                    if (sizeof(array_intersect($this->usergroups, $glist)) > 0) {
                        $user_list[] = $data["user"];
                    }
                }
            }
        }

        return $user_list;
    }

    /**
     * Creates a new user record for person_code if it doesn't already exist
     *
     * @param int $school_id if of the school
     * @param string $person_number person's number - using it as username as well
     * @param string $person_name_first person's first name
     * @param string $person_name_last person's last name
     * @param string $person_email person's email
     * @param string $person_phone person's phone
     * @param int $user_active: 0 - non-active users, 1 - active users
     * @param int $user_id: user responsible for adding/modifying the record (used for logging)
     *
     * @return int (1 - updated user, 2 - created new user, 0 - did nothing)
    */

    function createUserAccount($school_id = 0, $person_number = '', $person_name_first = '', $person_name_last = '', $person_email = '', $person_phone = '', $user_active = 0, $user_id = 0) {
        if ($person_number) {
            $group_list = $this->getSchoolGroup($school_id);
            $r = &$this->db->query('SELECT `module_user_users`.* FROM `module_user_users` WHERE `username` = ? LIMIT 1', $person_number);
            if ($data = $r->fetch_assoc()) {
                // if record already exists, then updating user data
                // updating group list as well, so all the new school groups will be added
                $u_glist = explode(",", $data["ggroup"]);
                $t_glist = explode(",", $group_list);
                $n_glist = array_unique(array_merge($u_glist, $t_glist));
                $g_list = implode(",", $n_glist);

                // getting record info before the change
                $row_old = $this->getUserRecord($data["user"]);

                $r = &$this->db->query('UPDATE `module_user_users` SET `active` = !, `ggroup` = ?, `name_first` = ?, `name_last` = ?, `email` = ?, `phone` = ? WHERE `user` = !', $user_active, $g_list, $person_name_first, $person_name_last, $person_email, $person_phone, $data["user"]);
                // logging info about changed user record
                $this->saveUserChangeLog($this->log_type_mod, $data["user"], $row_old, $this->getUserRecord($data["user"]), $user_id);
                return 1;
            } else {
                // if record didn't exist, then creating a new record
                $r = &$this->db->query('
                    INSERT INTO `module_user_users`
                    (
                        `user_type`,
                        `ggroup`,
                        `auth_type`,
                        `username`,
                        `password`,
                        `user_code`,
                        `name_first`,
                        `name_last`,
                        `email`,
                        `phone`,
                        `active`,
                        `added`,
                        `skin`
                     ) VALUES (
                        !,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        !,
                        NOW(),
                        ?
                     )',
                        $this->default_user_type, // user_type - regular user
                        $group_list,
                        $this->user_auth_types,
                        $person_number,
                        $this->default_password, // password - regular users can not log in with password
                        $person_number,
                        $person_name_first,
                        $person_name_last,
                        $person_email,
                        $person_phone,
                        $user_active, // user active
                        'skin_blue' // default skin
                    );
                    // adding log-info about created record
                    $added_id = $this->db->insert_id();
                    $this->saveUserChangeLog($this->log_type_add, $added_id, array(), $this->getUserRecord($added_id), $user_id);
                return 2;
            }
        }

        return 0;
    }

    /**
     * Creates user-accounts for all the cards
     *
     * @param int $school_id school ID
     * @param int $user_id user ID
     * @return array statistics about created accounts (0 - no db action, 1 - update, 2 - insert)
    */

    function createAllUserAccounts($school_id = 0, $user_id = 0) {
        $accounts = array(0 => 0, 1 => 0, 2 => 0);
        $users_active = 0;
        if ($school_account = $this->schoolAutoUserAccounts($school_id)) {
            $users_active = $school_account[1];
        }
        $r = &$this->db->query('SELECT * FROM `module_isic_card` WHERE `school_id` = ! OR ! = 0', $school_id, $school_id);
        while ($data = $r->fetch_assoc()) {
            $result = $this->createUserAccount($data["school_id"], $data["person_number"], $data["person_name_first"], $data["person_name_last"], $data["person_email"], $data["person_phone"], $users_active, $user_id);
            $accounts[$result]++;
        }
        return $accounts;
    }

    /**
     * Checks if user accounts can be automatically created for given school
     *
     * @param int $school_id school id
     * @return array with 2 boolean values (1st - weather the accounts should be created, 2nd - if accounts should be active)
    */

    function schoolAutoUserAccounts($school_id = 0) {
        $r = &$this->db->query('SELECT `auto_user_accounts`, `users_active` FROM `module_isic_school` WHERE `id` = !', $school_id);
        if ($data = $r->fetch_assoc()) {
            return array($data["auto_user_accounts"], $data["users_active"]);
        }
        return false;
    }

    /**
     * Creates an array of all the card types that current user has right to see
     *
     * @return array list of card type id's
    */

    function createAllowedCardTypes() {
        $type_list = array();

        if (is_array($this->usergroups)) {
            for ($i = 0; $i < sizeof($this->usergroups); $i++) {
                if ($this->usergroups[$i]) {
                    $r = &$this->db->query('SELECT `module_user_groups`.* FROM `module_user_groups` WHERE `id` = !', $this->usergroups[$i]);
                    if ($data = $r->fetch_assoc()) {
                        if ($data["isic_card_type"]) {
                            $t_type = explode(",", $data["isic_card_type"]);
                            $type_list = array_unique(array_merge($type_list, $t_type));
                        }
                    }
                }
            }
        }

        if (sizeof($type_list) == 0) {
            $type_list[] = 0;
        }

        return $type_list;
    }

    /**
     * Creates a new card record from application record
     *
     * @param int $application_id application id
     * @param int $user_id: user responsible for creating the card record (used for logging)
     *
     * @return int (1 - updated card, 2 - created new card, 0 - did nothing, -1 - problem with isic-number creation)
    */
    function createCardFromApplication($application_id = 0, $user_id = 0) {
        /** @var IsicDB_Applications $applDb */
        $applDb = IsicDB::factory('Applications');
        if ($application_id && ($appl = $applDb->getRecord($application_id))) {
            if (!$this->isApplNameLengthValid($appl)) {
                return -2;
            }

            $last_card = $this->getUserLastCard($appl["person_number"], $appl["school_id"], $appl["type_id"]);
            /** @var IsicDB_ApplicationTypes $dbApplType */
            $dbApplType = IsicDB::factory('ApplicationTypes');
            // In case of replacement card, re-using previous card number
            if ($last_card && $appl['application_type_id'] == $dbApplType->getTypeReplace()) {
                $isic_number = $last_card['isic_number'];
            } else {
                $isic_number = $this->getISICNumber($appl["type_id"], $appl["school_id"]);
            }
            $card_number = $this->getCardNumber($isic_number);

            $expiration_date = $this->getCardExpiration($appl["type_id"], $last_card ? $last_card["expiration_date"] : "", true);
            if (!($isic_number && $card_number && $expiration_date)) {
                return -1; // couldn't create isic-number
            }

            $cardData = array(
                'prev_card_id'         => $appl["prev_card_id"],
                'active'               => '0',
                'state_id'             => $this->isicDbCards->getStateOrdered(),
                'language_id'          => $appl["language_id"],
                'kind_id'              => $appl["kind_id"],
                'bank_id'              => $appl["bank_id"],
                'type_id'              => $appl["type_id"],
                'school_id'            => $appl["school_id"],
                'person_name_first'    => $appl["person_name_first"],
                'person_name_last'     => $appl["person_name_last"],
                'person_birthday'      => $appl["person_birthday"],
                'person_number'        => $appl["person_number"],
                'person_email'         => $appl["person_email"],
                'person_phone'         => $appl["person_phone"],
                'person_position'      => $appl["person_position"],
                'person_class'         => $appl["person_class"],
                'person_stru_unit'     => $appl["person_stru_unit"],
                'person_stru_unit2'    => $appl["person_stru_unit2"],
                'person_bankaccount'   => $appl["person_bankaccount"],
                'person_bankaccount_name' => $appl["person_bankaccount_name"],
                'isic_number'          => $isic_number,
                'card_number'          => $card_number,
                'expiration_date'      => $expiration_date,
                'pic'                  => $appl["pic"],
                'confirm_user'         => $appl["confirm_user"] ? '1' : '0',
                'confirm_payment_collateral' => $appl["confirm_payment_collateral"] ? '1' : '0',
                'confirm_payment_cost' => $appl["confirm_payment_cost"] ? '1' : '0',
                'confirm_payment_delivery' => $appl["confirm_payment_delivery"] ? '1' : '0',
                'confirm_admin'        => $appl["confirm_admin"] ? '1' : '0',
                'exported'             => $this->isicDbCardTypes->isAutoExported($appl["type_id"]) ? date("Y-m-d H:i:s") : $this->empty_date,
                'delivery_id'          => $appl["delivery_id"],
                'delivery_addr1'       => $appl["delivery_addr1"],
                'delivery_addr2'       => $appl["delivery_addr2"],
                'delivery_addr3'       => $appl["delivery_addr3"],
                'delivery_addr4'       => $appl["delivery_addr4"],
            );
            $added_id = $this->isicDbCards->insertRecord($cardData, $user_id);

            if ($added_id) {
                // copying application picture to card picture
                $this->isicDbCards->copyAndAssignPicture($added_id, $appl['pic'], $user_id);
                // setting application's card_id value, appl. state change and loging the change event
                $applDb->updateRecord($application_id, array(
                    'card_id' => $added_id,
                    'state_id' => $applDb->getStateProcessed(),
                    'expiration_date' => $expiration_date
                ), $user_id);

                // updating payment record with card_id
                $isic_payment = new IsicPayment();
                $isic_payment->setPaymentCard($application_id, $added_id);
                return 2;
            }
        }

        return 0;
    }

    private function isApplNameLengthValid($appl) {
        $nameSplitter = new IsicNameSplitter();
        // check if this card type should be split between fields
        if ($appl['tryb_export_name_split']) {
            return
                $nameSplitter->isLessThanMaxLength($appl['person_name_first']) &&
                $nameSplitter->isLessThanMaxLength($appl['person_name_last'])
            ;
        }
        return $nameSplitter->isLessThanMaxLength($appl['person_name_first'] . ' ' . $appl['person_name_last']);
    }

    /**
     * Creates a new application record from card record
     *
     * @param int $card_id card id
     * @param int $user_id: user responsible for creating the application record (used for logging)
     *
     * @return int application id if creation was success, 0 - otherise
    */

    function createApplicationFromCard($card_id = 0, $user_id = 0) {
        if ($card_id && ($card = $this->getCardRecord($card_id))) {
            $isic_payment = new IsicPayment();
            $cost_data = $isic_payment->getCardCostCollDeliveryData($card);

            $applDb = IsicDB::factory('Applications');
            $applData = array(
                'application_type_id' => $cost_data["type"],
                'prev_card_id' => $card_id,
                'type_id' => $card["type_id"],
                'school_id' => $card["school_id"],
                'user_step' => 1,
                'person_name_first' => $card["person_name_first"],
                'person_name_last' => $card["person_name_last"],
                'person_birthday' => $card["person_birthday"],
                'person_number' => $card["person_number"],
                'delivery_addr1' => $card["delivery_addr1"],
                'delivery_addr2' => $card["delivery_addr2"],
                'delivery_addr3' => $card["delivery_addr3"],
                'delivery_addr4' => $card["delivery_addr4"],
                'person_email' => $card["person_email"],
                'person_phone' => $card["person_phone"],
                'person_position' => $card["person_position"],
                'person_class' => $card["person_class"],
                'person_stru_unit' => $card["person_stru_unit"],
                'person_stru_unit2' => $card["person_stru_unit2"],
                'person_bankaccount' => $card["person_bankaccount"],
                'person_bankaccount_name' => $card["person_bankaccount_name"],
            );

            $added_id = $applDb->insertRecord($applData);

            if ($added_id) {
                // copying card picture to application picture
                $old_name = $card["pic"];
                $new_name = "/" . $GLOBALS["directory"]["upload"] . "/appl/APPL" . str_pad($added_id, 10, '0', STR_PAD_LEFT) . ".jpg";
                if (copy(SITE_PATH . $old_name, SITE_PATH . $new_name)) {
                    @copy(SITE_PATH . str_replace(".jpg", "_thumb.jpg", $old_name), SITE_PATH . str_replace(".jpg", "_thumb.jpg", $new_name));
                    $applDb->updateRecord($added_id, array('pic' => $new_name));
                }
                return $added_id;
            }
        }

        return 0;
    }

    /**
     * Exports all of the applications into array
     *
     * @param int $card_type card type
     * @param int $school_id school id
     * @param array $appl_ids array of application id-s
     * @return array of application records to be used for card creation
    */

    function exportApplicationArray($card_type = 0, $school_id = 0, $appl_ids = false) {
        $appl_list = array();
        if (!$card_type) {
            $card_type = 0;
        }
        if (!$school_id) {
            $school_id = 0;
        }

        if (is_array($appl_ids) && sizeof($appl_ids) > 0) {
            $r = &$this->db->query('
                SELECT
                    `module_isic_application`.*,
                    `module_isic_school`.`name` as school_name,
                    `module_isic_card_type`.`name` as type_name
                FROM
                    `module_isic_application` JOIN
                    `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id` JOIN
                    `module_isic_card_type` ON `module_isic_application`.`type_id` = `module_isic_card_type`.`id`
                WHERE
                    `module_isic_application`.`state_id` = ! AND
                    `module_isic_application`.`id` IN (!@)
                ', 5, $appl_ids);
        } else {
            $r = &$this->db->query('
                SELECT
                    `module_isic_application`.*,
                    `module_isic_school`.`name` as school_name,
                    `module_isic_card_type`.`name` as type_name
                FROM
                    `module_isic_application` JOIN
                    `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id` JOIN
                    `module_isic_card_type` ON `module_isic_application`.`type_id` = `module_isic_card_type`.`id`
                WHERE
                    (`module_isic_application`.`type_id` = ! OR ! = 0) AND
                    (`module_isic_application`.`school_id` = ! OR ! = 0) AND
                    `module_isic_application`.`state_id` = ! AND
                    `module_isic_application`.`confirm_user` = 1 
                ', $card_type, $card_type, $school_id, $school_id, 5);
        }

        while ($row = $r->fetch_assoc()) {
            $appl_list[] = $row;
        }
        return $appl_list;
    }
    /**
     * Displays error message for user
     *
     * @param string $message message to show
     * @return boolean
     */

    function showErrorMessage ($message, $translation_module = "module_isic_card") {
        $txt = new Text($this->language, $translation_module);
        $txtf = new Text($this->language, "output");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_error.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=isic&type=error");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module isic cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display($message));

        return $tpl->parse();
    }

    /**
     * Removes all the empty values from the log record
     *
     * @param string $log log body
     * @return boolean
     */

    function fixCardLogRecord($log) {
        $field_empty_values = $this->log_field_empty_values;
        $field_ignore = $this->log_field_ignore;
        $new_log = "";

        $parsed = explode(";", $log);
        foreach ($parsed as $key => $val) {
            $empty = false;
            $field_name = trim(substr($val, 0, strpos($val, ":")));
            $field_val = substr($val, strpos($val, ":") + 2);
            $field_parsed = explode(" -> ", $field_val);

            if (in_array($field_name, $field_ignore) || array_key_exists($field_name, $field_empty_values) && (in_array($field_parsed[1], $field_empty_values[$field_name]))) {
                $empty = true;
            }
            if (!$empty) {
                if ($new_log) {
                    $new_log .= "; ";
                }
                $new_log .= $field_name . ": " . $field_parsed[0] . " -> " . $field_parsed[1];
            }
        }
        return $new_log;
    }

    /**
     * Parsing the card log add type record body
     *
     * @param string $log log body
     * @return array
     */

    function parseCardLogAdd($log) {
        $txt = new Text($this->language, "module_isic_card");
        $parsed = false;
        if ($log) {
            $t_parsed = explode(";", $log);
            foreach ($t_parsed as $key => $val) {
                if (!in_array($key, $this->log_field_ignore)) {
                    $field_name = trim(substr($val, 0, strpos($val, ":")));
                    $field_val = substr($val, strpos($val, ":") + 2);
                    if (in_array($field_name, $this->log_field_boolean)) {
                        $field_val = $txt->display("active" . $field_val);
                    }
                    $parsed[$field_name] = array("", $field_val);
                }
            }
        }
        return $parsed;
    }

    /**
     * Parsing the card log mod type record body
     *
     * @param string $log log body
     * @return array
     */

    function parseCardLogMod($log) {
        $txt = new Text($this->language, "module_isic_card");
        $parsed = false;
        if ($log) {
            $t_parsed = explode(";", $log);
            foreach ($t_parsed as $key => $val) {
                $field_name = trim(substr($val, 0, strpos($val, ":")));
                $field_val = substr($val, strpos($val, ":") + 2);
                $field_parsed = explode(" -> ", $field_val);
                if (in_array($field_name, $this->log_field_boolean)) {
                    $field_parsed[0] = $txt->display("active" . $field_parsed[0]);
                    $field_parsed[1] = $txt->display("active" . $field_parsed[1]);
                }
                $parsed[$field_name] = $field_parsed;
            }
        }
        return $parsed;
    }

    /**
     * Comparing two application records and saving the differences into application log table
     *
     * @param int $event_type, event type 1 - add application, 2 - modify application, 3 - delete application
     * @param int $application application id
     * @param array $row_old - old application record
     * @param array $row_cur - current application record
     * @param int $user_id - user, who made the add/mod (if 0, then using $this->userid)
     * @return null
     */

    function saveApplicationChangeLog($event_type = 2, $application, $row_old, $row_cur, $user_id = 0)
    {
        $change = '';
        $fields = array_keys($row_cur);
        if (!sizeof($fields)) {
            $fields = array_keys($row_old);
        }
        foreach ($fields as $field) {
            if ($row_old[$field] === $row_cur[$field]) {
                continue;
            }
            if ($change) {
                $change .= ";\n ";
            }

            switch ($field) {
                case "application_type_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_application_type WHERE id = ?', $row_cur['application_type_id']);
                    $appl_type_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[application_type_name](" . $row_old[$field] . ") -> $appl_type_new[name](" . $row_cur[$field] . ')';
                break;
                case "language_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_language WHERE id = ?', $this->vars['language_id']);
                    $language_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[language_name](" . $row_old[$field] . ") -> $language_new[name](" . $row_cur[$field] . ')';
                break;
                case "state_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_application_state WHERE id = ?', $row_cur['state_id']);
                    $state_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[state_name](" . $row_old[$field] . ") -> $state_new[name](" . $row_cur[$field] . ')';
                break;
                case "kind_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_kind WHERE id = ?', $row_cur['kind_id']);
                    $kind_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[kind_name](" . $row_old[$field] . ") -> $kind_new[name](" . $row_cur[$field] . ')';
                break;
                case "bank_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_bank WHERE id = ?', $row_cur['bank_id']);
                    $bank_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[bank_name](" . $row_old[$field] . ") -> $bank_new[name](" . $row_cur[$field] . ')';
                break;
                case "type_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_type WHERE id = ?', $row_cur['type_id']);
                    $type_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[type_name](" . $row_old[$field] . ") -> $type_new[name](" . $row_cur[$field] . ')';
                break;
                case "school_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_school WHERE id = ?', $row_cur['school_id']);
                    $school_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[school_name](" . $row_old[$field] . ") -> $school_new[name](" . $row_cur[$field] . ')';
                break;
                default:
                    $change .= $field . ': ' . $row_old[$field] . ' -> ' . $row_cur[$field];
                break;
            }
        }

        if ($change) {
            $r = &$this->db->query("INSERT INTO module_isic_application_log (
                application_id, event_type, event_user, event_date, event_body) VALUES (?, ?, ?, now(), ?)",
                $application, $event_type, $this->getLogUserId($user_id), $change);
        }
    }

    /**
     * Comparing two card records and saving the differences into card log table
     *
     * @param int $event_type, event type 1 - add card, 2 - modify card
     * @param int $card card id
     * @param array $row_old - old card record
     * @param array $row_cur - current card record
     * @param int $user_id - user, who made the add/mod (if 0, then using $this->userid)
     * @return null
     */

    function saveCardChangeLog($event_type = 2, $card, $row_old, $row_cur, $user_id = 0)
    {
        $change = '';
        $fields = array_keys($row_cur);
        if (!sizeof($fields)) {
            $fields = array_keys($row_old);
        }
        foreach ($fields as $field) {
            if ($row_old[$field] === $row_cur[$field]) {
                continue;
            }

            if ($change) {
                $change .= ";\n ";
            }

            switch ($field) {
                case "language_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_language WHERE id = ?', $this->vars['language_id']);
                    $language_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[language_name](" . $row_old[$field] . ") -> $language_new[name](" . $row_cur[$field] . ')';
                break;
                case "status_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_status WHERE id = ?', $row_cur['status_id']);
                    $status_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[status_name](" . $row_old[$field] . ") -> $status_new[name](" . $row_cur[$field] . ')';
                break;
                case "kind_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_kind WHERE id = ?', $row_cur['kind_id']);
                    $kind_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[kind_name](" . $row_old[$field] . ") -> $kind_new[name](" . $row_cur[$field] . ')';
                break;
                case "bank_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_bank WHERE id = ?', $row_cur['bank_id']);
                    $bank_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[bank_name](" . $row_old[$field] . ") -> $bank_new[name](" . $row_cur[$field] . ')';
                break;
                case "type_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_card_type WHERE id = ?', $row_cur['type_id']);
                    $type_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[type_name](" . $row_old[$field] . ") -> $type_new[name](" . $row_cur[$field] . ')';
                break;
                case "school_id":
                    $r = &$this->db->query('SELECT id, name FROM module_isic_school WHERE id = ?', $row_cur['school_id']);
                    $school_new = $r->fetch_assoc();
                    $change .= $field . ": $row_old[school_name](" . $row_old[$field] . ") -> $school_new[name](" . $row_cur[$field] . ')';
                break;
                default:
                    $change .= $field . ': ' . $row_old[$field] . ' -> ' . $row_cur[$field];
                break;
            }
        }

        if ($change) {
            $r = &$this->db->query("INSERT INTO module_isic_card_log (
                card_id, event_type, event_user, event_date, event_body) VALUES (?, ?, ?, now(), ?)",
                $card, $event_type, $this->getLogUserId($user_id), $change);
        }
    }


    /**
     * Comparing two user records and saving the differences into user log table
     *
     * @param int $event_type, event type 1 - add user, 2 - modify user
     * @param int $user user id
     * @param array $row_old - old user record
     * @param array $row_cur - current user record
     * @param int $user_id - user, who made the add/mod (if 0, then using $this->userid)
     * @return null
     */

    function saveUserChangeLog($event_type = 2, $user, $row_old, $row_cur, $user_id = 0)
    {
        $txt = new Text($this->language, "module_user");

        $change = '';
        $fields = array_keys($row_cur);
        if (!sizeof($fields)) {
            $fields = array_keys($row_old);
        }
        foreach ($fields as $field) {
            if ($row_old[$field] === $row_cur[$field]) {
                continue;
            }
            if ($change) {
                $change .= ";\n ";
            }

            switch ($field) {
                case "user_type":
                    $user_type_name = array();
                    $user_type_name[1] = $txt->display("user_type1");
                    $user_type_name[2] = $txt->display("user_type2");

                    $change .= $field . ": " . $user_type_name[$row_old[$field]] . "(" . $row_old[$field] . ") -> " . $user_type_name[$row_cur[$field]] . "(" . $row_cur[$field] . ')';
                break;
                case "region_list":
                    $group_name = array();
                    $r = &$this->db->query('SELECT id, name FROM module_isic_region');
                    while ($gdata = $r->fetch_assoc()) {
                        $group_name[$gdata['id']] = $gdata['name'];
                    }

                    $g_old = explode(',', $row_old[$field]);
                    $g_old_name = array();
                    foreach ($g_old as $gval) {
                        $g_old_name[] = $group_name[$gval];
                    }

                    $g_cur = explode(',', $row_cur[$field]);
                    $g_cur_name = array();
                    foreach ($g_cur as $gval) {
                        $g_cur_name[] = $group_name[$gval];
                    }

                    $change .= $field . ": " . implode(',', $g_old_name) . "(" . $row_old[$field] . ") -> " . implode(',', $g_cur_name)  . "(" . $row_cur[$field] . ')';
                    break;
                case "ggroup":
                    $group_name = array();
                    $r = &$this->db->query('SELECT id, name FROM module_user_groups');
                    while ($gdata = $r->fetch_assoc()) {
                        $group_name[$gdata['id']] = $gdata['name'];
                    }

                    $g_old = explode(',', $row_old[$field]);
                    $g_old_name = array();
                    foreach ($g_old as $gval) {
                        $g_old_name[] = $group_name[$gval];
                    }

                    $g_cur = explode(',', $row_cur[$field]);
                    $g_cur_name = array();
                    foreach ($g_cur as $gval) {
                        $g_cur_name[] = $group_name[$gval];
                    }

                    $change .= $field . ": " . implode(',', $g_old_name) . "(" . $row_old[$field] . ") -> " . implode(',', $g_cur_name)  . "(" . $row_cur[$field] . ')';
                break;
                case "auth_type":
                    $auth_type_name = array();
                    for ($i = 1; $i < 7; $i++) {
                        $auth_type_name[$i] = $txt->display("auth_type" . $i);
                    }

                    $a_old = explode(',', $row_old[$field]);
                    $a_old_name = array();
                    foreach ($a_old as $aval) {
                        $a_old_name[] = $auth_type_name[$aval];
                    }

                    $a_cur = explode(',', $row_cur[$field]);
                    $a_cur_name = array();
                    foreach ($a_cur as $aval) {
                        $a_cur_name[] = $auth_type_name[$aval];
                    }

                    $change .= $field . ": " . implode(',', $a_old_name) . "(" . $row_old[$field] . ") -> " . implode(',', $a_cur_name)  . "(" . $row_cur[$field] . ')';
                break;
                default:
                    $change .= $field . ': ' . $row_old[$field] . ' -> ' . $row_cur[$field];
                break;
            }
        }

        if ($change) {
            $r = &$this->db->query("INSERT INTO module_user_users_log (
                user_id, event_type, event_user, event_date, event_body) VALUES (?, ?, ?, now(), ?)",
                $user, $event_type, $this->getLogUserId($user_id), $change);
        }
    }

    function getLogUserId($userId) {
        if ($userId) {
            return $userId;
        }
        if ($this->userid) {
            return $this->userid;
        }
        return $this->system_user;
    }

    /**
     * Comparing user record with application record
     *
     * @param array $user_data - user data
     * @param array $appl_data - application data
     * @param array $fields - fields to compare
     * @return array with fields that were different
     */

    function diffUserWithApplication($user_data, $appl_data, $fields)
    {
        $diff = false;
        foreach ($fields as $fkey => $fval) {
            if ($user_data[$fkey] != $appl_data[$fval]) {
                $diff[$fkey] = $fval;
            }
        }
        return $diff;
    }

    /**
     * Updating user pic with given application pic
     *
     * @param int $userid - user id
     * @param int $application application id
     * @return bool (true - update was success, false - otherwise)
     */

    function updateUserPic($user, $application) {
        if ($user && $application) {
            $user_pic = '/' . $GLOBALS["directory"]["upload"] . $this->u_pic_folder . '/' . $this->u_pic_prefix .
                str_pad($user, 10, '0', STR_PAD_LEFT);
            $appl_pic = '/' . $GLOBALS["directory"]["upload"] . $this->a_pic_folder . '/' . $this->a_pic_prefix .
                str_pad($application, 10, '0', STR_PAD_LEFT);

            $u_pic_filename = SITE_PATH . $user_pic . '.jpg';
            $u_pic_filename_t = SITE_PATH . $user_pic . '_thumb.jpg';

            $a_pic_filename = SITE_PATH . $appl_pic . '.jpg';
            $a_pic_filename_t = SITE_PATH . $appl_pic . '_thumb.jpg';

            if (file_exists($a_pic_filename)) {
                @copy($a_pic_filename, $u_pic_filename);
            }
            if (file_exists($a_pic_filename_t)) {
                @copy($a_pic_filename_t, $u_pic_filename_t);
            }

            $this->isicDbUsers->updateRecord($user, array('pic' => $user_pic . '.jpg'));
            return true;
        }
        return false;
    }

    /**
     * Finds school id from bank school table according to given name
     *
     * @param string $name school name
     * @param int $bank_id bank id
     * @return int school id
    */

    function getBankSchoolId($name = '', $bank_id = 0) {
        $school_id = 0;
        if (trim($name)) {
            $r = &$this->db->query('SELECT * FROM `module_isic_bank_school` WHERE `name` = ? AND `bank_id` = !', $name, $bank_id);
            if ($data = $r->fetch_assoc()) {
                $school_id = $data["school_id"];
                if (!$school_id) {
                    $r = &$this->db->query('INSERT INTO `module_isic_school` (`name`) VALUES (?)', $name);
                    $school_id = $this->db->insert_id();
                    $r = &$this->db->query('UPDATE `module_isic_bank_school` SET `school_id` = ! WHERE `id` = !', $school_id, $data["id"]);
                }
            } else {
                $r = &$this->db->query('INSERT INTO `module_isic_school` (`name`) VALUES (?)', $name);
                $school_id = $this->db->insert_id();
                $r = &$this->db->query('INSERT INTO `module_isic_bank_school` (`name`, `bank_id`, `school_id`) VALUES (?, !, !)', $name, $bank_id, $school_id);
            }
        }
        return $school_id;
    }

    /**
     * Finds card type id from bank type table according to given name
     *
     * @param string $name type name
     * @param int $bank_id bank id
     * @return int type id
     * * @return int expiration year
    */
    public function getBankTypeId($name = '', $bank_id = 0, $expirationYear = 0) {
        if (!trim($name)) {
            return 0;
        }
        $r = &$this->db->query('
            SELECT
                `type_id`
            FROM
                `module_isic_bank_type`
            WHERE
                `name` = ? AND
                `bank_id` = ! AND
                (`expiration_year` = ! OR ! = 0)
            LIMIT 1',
            $name,
            $bank_id,
            $expirationYear, $expirationYear
        );
        if ($data = $r->fetch_assoc()) {
            return $data["type_id"];
        }
        $r = &$this->db->query('INSERT INTO `module_isic_card_type` (`name`) VALUES (?)', $name);
        $type_id = $this->db->insert_id();
        $r = &$this->db->query('
            INSERT INTO `module_isic_bank_type` (
                `name`, `bank_id`, `type_id`, `expiration_year`
            ) VALUES (
                ?, !, !, ?
            )',
            $name, $bank_id, $type_id, $expirationYear);
        return $type_id;
    }

    /**
     * Finds card status id from bank status table according to given name
     *
     * @param string $name type name
     * @param int $bank_id bank id
     * @return int status id
    */

    function getBankStatusId($name = '', $bank_id = 0) {
        $status_id = 0;
        if (trim($name)) {
            $r = &$this->db->query('SELECT `id` FROM `module_isic_bank_status` WHERE `name` = ? AND `bank_id` = !', $name, $bank_id);
            if ($data = $r->fetch_assoc()) {
                $status_id = $data["id"];
            } else {
                $r = &$this->db->query('INSERT INTO `module_isic_bank_status` (`name`, `bank_id`) VALUES (?, !)', $name, $bank_id);
                $status_id = $this->db->insert_id();
            }
        }
        return $status_id;
    }

    /**
     * Finds cards without chip number from given order
     *
     * @param int $order_id orde ID
     * @return array of card numbers
    */
    public function getCardsWithoutChipNumber($order_id) {
        return $this->isicDbCards->getCardsWithoutChipNumber($order_id);
    }

    /**
     * Checks if all the required fields are filled
     *
     * @param array $vars variable array with user-submitted data
     * @param array $required list of field names to check
     * @param array $bad by reference passed array for "bad" fields
     * @return array with error-flag and "bad" fields
    */

    function checkRequired($vars, $required = false, &$bad)
    {
        $error = false;
        if (is_array($required)) {
            for ($c = 0; $c < sizeof($required); $c++) {
                if (trim($vars[$required[$c]]) == "") {
                    $bad[] = $required[$c];
                    $error = true;
                }
            }
        }
        return $error;
    }

    /**
     * Checks if user already has active applications of given type
     *
     * @param string $person_number person number
     * @param int $type_id type ID
     * @param int $appl_id application ID that should be ignored
     * @return boolean
    */

    function getUserApplicationTypeExists($person_number = '', $type_id = 0, $appl_id = 0) {
        if ($person_number && $type_id) {
            $r = &$this->db->query('SELECT `id` FROM `module_isic_application` WHERE `person_number` = ? AND `type_id` = ! AND NOT(`state_id` IN (!@)) AND `id` <> ! LIMIT 1', $person_number, $type_id, array($this->a_state_rejected, $this->a_state_processed), $appl_id);
            if ($r->fetch_assoc()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if user already has card that is orderd or distributed
     *
     * @param string $person_number person number
     * @param int $type_id type ID
     * @return boolean
    */

    function getUserCardTypeExistsOrderedDistributed($person_number = '', $type_id = 0) {
        if ($person_number && $type_id) {
            $r = &$this->db->query('SELECT `id` FROM `module_isic_card` WHERE `kind_id` = 1 AND `person_number` = ? AND `type_id` = ! AND `state_id` IN (!@) LIMIT 1', $person_number, $type_id, array($this->c_state_ordered, $this->c_state_distributed));
            if ($r->fetch_assoc()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts person data fields from given user data array
     *
     * @param array $user_data user data array
     * @return array with only person data
    */

    function getPersonFieldsFromUserData($user_data) {
        $p_data = false;
        foreach ($this->user_appl_match as $ukey => $pkey) {
            $p_data[$pkey] = $user_data[$ukey];
        }
        if ($user_data["pic"]) {
            $p_data["person_pic"] = SITE_URL . $user_data["pic"];
        }
        return $p_data;
    }

    /**
     * Gets user ID by it's username
     *
     * @param string $username username
     *
     * @return int user id
    */

    function getUserIdByUsername($username = '') {
        return $this->isicDbUsers->getIdByUsername($username);
    }

    /**
     * Deactivating all the active cards with expiration date less than given date
     *
     * @param string $cur_date current date
     *
     * @return int number of cards de-activated
    */
    function deactivateExpiredCards($cur_date = '') {
        $cards = IsicDB::factory('Cards');
        return $cards->deactivateExpiredCards($cur_date);
    }

    /**
     * Finding card Id by it's isic_number
     *
     * @param string $number card isic number
     *
     * @return card id or false if card record not found
    */

    function getCardIdByNumber($number) {
        $r = &$this->db->query('SELECT `id` FROM `module_isic_card` WHERE `isic_number` = ? LIMIT 1', $number);
        if ($data = $r->fetch_assoc()) {
            return $data["id"];
        }
        return false;
    }

    /**
     * Extending the expiration date of the given card
     *
     * @param int $card_id card id
     * @param string $expiration expiration date
     *
     * @return boolean
    */

    function extendCardExpiration($card_id, $expiration) {
        if ($card_id && $expiration) {
            $card_data = $this->getCardRecord($card_id);
            $r = &$this->db->query('UPDATE `module_isic_card` SET `expiration_date` = ?, `moddate` = NOW(), `moduser` = ! WHERE `id` = !', $expiration, $this->system_user, $card_id);
            $this->saveCardChangeLog($this->log_type_mod, $card_id, $card_data, $this->getCardRecord($card_id), $this->system_user);
            return true;
        }
        return false;
    }

    function getUserCard($user_code) {
        if ($user_code) {
            $r = &$this->db->query("
            SELECT
                `module_isic_card`.`id`
            FROM
                `module_isic_card`
            WHERE
                `module_isic_card`.`person_number` = ? 
            ORDER BY `module_isic_card`.`moddate` DESC
            LIMIT 1",
            $user_code);

            if ($data = $r->fetch_assoc()) {
                return $data["id"];
            }
        }
        return 0;
    }

    function setUserPicFromCard($user_id = 0, $mod_user_id = 0) {
        $user_data = $this->getUserRecord($user_id);
        $card_data = $this->getCardRecord($this->getUserCard($user_data['user_code']));

        if (!$user_data['pic'] && $card_data && $card_data['pic']) {
            // copying card picture to user picture
            $old_name = $card_data["pic"];
            $new_name = "/" . $GLOBALS["directory"]["upload"] . "/user/USER" . str_pad($user_data['user'], 10, '0', STR_PAD_LEFT) . ".jpg";
            if (copy(SITE_PATH . $old_name, SITE_PATH . $new_name)) {
                @copy(SITE_PATH . str_replace(".jpg", "_thumb.jpg", $old_name), SITE_PATH . str_replace(".jpg", "_thumb.jpg", $new_name));
                $r = &$this->db->query('UPDATE `module_user_users` SET `pic` = ? WHERE `user` = !', $new_name, $user_data['user']);
                $this->saveUserChangeLog($this->log_type_mod, $user_data['user'], $user_data, $this->getUserRecord($user_data['user']), $mod_user_id);
                return true;
            }
        }
        return false;
    }

    function setUserPicFromCardAll($mod_user_id = 0) {
        $count = 0;
        $r = &$this->db->query("SELECT `user` FROM `module_user_users` WHERE `pic` = '' ORDER BY `user`");
        while ($data = $r->fetch_assoc()) {
            if ($this->setUserPicFromCard($data['user'], $mod_user_id)) {
                $count++;
            }
        }
        return $count;
    }

    function setCurrentTime($time = 0) {
        if ($time) {
            $this->current_time = $time;
        } else {
            $this->current_time = time();
        }
    }

    function getCurrentTime() {
        return $this->current_time;
    }

    function getDateFromTime($time = 0) {
        return date("Y-m-d", $time);
    }

    function deleteApplication($appl_data = false, $user_id = 0) {
        if ($appl_data) {
            $this->saveApplicationChangeLog($this->log_type_del, $appl_data['id'], $appl_data, array(), $user_id);
            $r = &$this->db->query('DELETE FROM `module_isic_application` WHERE `module_isic_application`.`id` = !', $appl_data['id']);
            return true;
        }
        return false;
    }

    function assignUserCodeList() {
        $this->userCodeList = array_merge(
            array($this->user_code),
            is_array($this->children_list) ? $this->children_list : array()
        );
    }

    function getCurrentUserCodeList() {
        return $this->userCodeList;
    }

    function getChildrenIdList() {
        return $this->isicDbUsers->getIdListByUserCodeList($this->children_list);
    }

    public function getArrayQuoted($array) {
        $quotedArray = array();
        foreach ($array as $element) {
            $quotedArray[] = $this->db->quote($element);
        }
        return $quotedArray;
    }

    public function copyApplicationPictureToUser($applicationData) {
        $userData = $this->isicDbUsers->getRecordByCode($applicationData["person_number"]);
        if ($userData && $applicationData["pic"]) {
            $a_pic_filename = str_replace("_thumb.", ".", $applicationData["pic"]);
            $a_pic_filename_t = str_replace(".", "_thumb.", $a_pic_filename);
            $uploadDir = '/' . $GLOBALS["directory"]["upload"];

            if (file_exists(SITE_PATH . $a_pic_filename)) {
                $pic_filename = $this->u_pic_prefix . str_pad($userData['user'], 10, '0', STR_PAD_LEFT);
                $u_pic_filename = $uploadDir . $this->u_pic_folder . '/' . $pic_filename . '.jpg';
                $u_pic_filename_t = $uploadDir . $this->u_pic_folder . '/' . $pic_filename . '_thumb.jpg';
                @copy(SITE_PATH . $a_pic_filename, SITE_PATH . $u_pic_filename);
                @copy(SITE_PATH . $a_pic_filename_t, SITE_PATH . $u_pic_filename_t);
//                $this->vars["pic"] = Filenames::constructPath($pic_filename, 'jpg', $uploadDir . $this->u_pic_folder);
                $this->isicDbUsers->updateRecord($userData['user'], array('pic' => $u_pic_filename));
            }
        }
    }

    public function isValidPictureAge($pic, $cardType, $schoolId) {
        $expiration = $this->isicDbCardTypeSchools->getPictureExpiration($cardType, $schoolId);
        if (!$expiration) {
            return true;
        }
        $age = IsicImage::getAgeInMonths(SITE_PATH . str_replace(SITE_URL, '', $pic));
        return $age < $expiration;
    }
}