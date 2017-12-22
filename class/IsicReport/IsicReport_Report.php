<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicForm.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicNumber.php");

class IsicReport_Report
{
    const KIND_REGULAR = 1;
    const KIND_UNION = 2;
    const JOINED = 1;
    const NOT_JOINED = 2;

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
     * Maximum number of results in listview
     *
     * @var int
     * @access protected
     */
    var $maxresults = 50;

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
     * Available kard kinds (1 - regular, 2 - combined (with bank functions)
     *
     * @var int
     * @access protected
     */
    var $card_kind = array(self::KIND_REGULAR, self::KIND_UNION);

    var $isicTemplate = false;

    /**
     * @var IsicDB_CardStatuses
     */
    var $isicDbCardStatuses = null;

    /**
     * @var IsicDB_Currency
     */
    var $isicDbCurrency = null;

    /**
     * @var IsicDB_Payments
     */
    var $isicDbPayments = null;

    /**
     * @var IsicDB_Banks
     */
    protected $isicDbBanks;

    /**
     * @var IsicDB_Schools
     */
    protected $isicDbSchools;

    /**
     * @var IsicDB_SchoolCompensationsUser
     */
    protected $isicDbSchoolCompensationsUser;

    /**
     * @var IsicDB_Users
     */
    protected $isicDbUsers;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        // assigning common methods class
        $this->isic_common = IsicCommon::getInstance();
        $this->isic_payment = new IsicPayment();
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbCardStatuses = IsicDB::factory('CardStatuses');
        $this->isicDbCurrency = IsicDB::factory('Currency');
        $this->isicDbPayments = IsicDB::factory('Payments');
        $this->isicDbBanks = IsicDB::factory('Banks');
        $this->isicDbSchoolCompensationsUser = IsicDB::factory('SchoolCompensationsUser');

        $this->isicUser = new IsicUser($this->userid);

        $this->allowed_schools = $this->isicUser->getAllowedSchools();
        $this->allowed_card_types_view = $this->isicUser->getAllowedCardTypesForView();
        //$this->allowed_card_types_add = $this->isicUser->getAllowedCardTypesForAdd();
        $this->isicTemplate = new IsicTemplate('isic_report');
    }

    function showDebug($debugInfo) {
        if ($this->userid == 1) {
            echo "<!-- DEBUGDEBUG: " . $debugInfo . " -->\n";
        }
    }

    /**
     *
     */
    function getSchoolList($allTitle) {
        $list = array();
        if ($allTitle) {
            $list[0] = $allTitle;
        }

        $r = &$this->db->query('
            SELECT
                `module_isic_school`.`id`,
                `module_isic_school`.`name`,
                `module_isic_school`.`ehl_code`
            FROM
                `module_isic_school`
            WHERE
                `module_isic_school`.`id` IN (!@)
            ORDER BY
                `module_isic_school`.`name`
            ',
            IsicDB::getIdsAsArray($this->allowed_schools)
        );

        while ($data = $r->fetch_assoc()) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    /**
     *
     */
    function getRegionList($allTitle) {
        $list = array();
        if ($allTitle) {
            $list[0] = $allTitle;
        }

        $dbRegions = IsicDB::factory('Regions');
        $regions = $dbRegions->getRecordsBySchoolIds($this->allowed_schools);
        foreach ($regions as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    function getCardTypeList($allTitle) {
        $list = array();
        if ($allTitle) {
            $list[0] = $allTitle;
        }
        $r = &$this->db->query('
            SELECT
                `module_isic_card_type`.`id`,
                `module_isic_card_type`.`name`
            FROM
                `module_isic_card_type`
            WHERE
                `module_isic_card_type`.`id` IN (!@)
            ORDER BY
                `module_isic_card_type`.`name`
            ',
            IsicDB::getIdsAsArray($this->allowed_card_types_view)
        );

        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    protected function getCardTypeListData($filterId, $txt) {
        $cardTypeList = array();
        $rct = &$this->db->query('
            SELECT
                `module_isic_card_type`.*
            FROM
                `module_isic_card_type`
            WHERE
                `id` IN (!@) AND
                (`id` = ! OR ! = 0)
            ORDER BY
                `module_isic_card_type`.`name`',
            IsicDB::getIdsAsArray($this->allowed_card_types_view),
            $filterId,
            $filterId
        );
        while ($data = $rct->fetch_assoc()) {
            $data['prolong_status_id'] = $this->isicDbCardStatuses->getCardStatusProlongId($data['id']);
            $cardTypeList[] = $data;
        }
        return $cardTypeList;
    }

    protected function showFormFields($fields, $tpl, $txt, $sub = "") {
        foreach ($fields as $key => $val) {
            $fdata = array(
                "type" => $val[0],
                "size" => $val[1],
                "cols" => $val[1],
                "rows" => $val[2],
                "list" => $val[4],
                "java" => $val[5],
                "class" => $val[6],
            );

            if ($this->vars['print']) {
                if (is_array($fdata["list"])) {
                    $field_data = $fdata["list"][$val[3]];
                } else {
                    if ($fdata["type"] == "checkbox") {
                        $field_data = $txt->display("active" . ($val[3] ? $val[3] : 0));
                    } else {
                        $field_data = $val[3];
                    }
                }
            } else {
                $f = new AdminFields($key, $fdata);
                $field_data = $f->display($val[3]);
                $field_data = str_replace('name="' . $key . '"', 'id="' . $key . '" ' . 'name="' . $key . '"', $field_data);
            }
            $tpl->addDataItem(($sub ? $sub . '.' : '') . "FIELD_{$key}", $field_data);
            unset($fdata);
        }
    }

    /**
     * Creates a list of start-dates for preset dates used in report filters
     *
     * @access private
     * @return array
     */
    function getPresetDatesStart()
    {
        $dates = array();
        $curtime = time();
        $mon = date("n", $curtime);
        $day = date("j", $curtime);
        $yea = date("Y", $curtime);
        $wee = date("w", $curtime);
        if (!$wee) {
            $wee = 7;
        }

        // empty
        $dates[] = '';
        // this week
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon, $day - $wee + 1, $yea));
        // prev week
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon, $day - $wee + 1 - 7, $yea));
        // this month
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon, 1, $yea));
        // prev month
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon - 1, 1, $yea));
        return $dates;
    }

    /**
     * Creates a list of end-dates for preset dates used in report filters
     *
     * @access private
     * @return array
     */
    function getPresetDatesEnd()
    {
        $dates = array();
        $curtime = time();
        $mon = date("n", $curtime);
        $day = date("j", $curtime);
        $yea = date("Y", $curtime);
        $wee = date("w", $curtime);
        if (!$wee) {
            $wee = 7;
        }

        // empty
        $dates[] = '';
        // this week
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon, $day + (7 - $wee), $yea));
        // prev week
        $dates[] = date("d/m/Y", mktime(0, 0, 0, $mon, $day + (7 - $wee) - 7, $yea));
        // this month
        $dates[] = date("t/m/Y", mktime(0, 0, 0, $mon, 1, $yea));
        // prev month
        $dates[] = date("t/m/Y", mktime(0, 0, 0, $mon - 1, 1, $yea));
        return $dates;
    }

    /**
     * Creates a list of start-date names for preset dates used in report filters
     *
     * @access private
     * @return string
     */
    function getPresetDatesName()
    {
        $txt = new Text($this->language, "module_isic_report");
        $str = array();
        for ($i = 1; $i <= 4; $i++) {
            $str[] = "['" . $i . "', '" . $txt->display("preset_dates" . $i) . "']";
        }
        return implode(",", $str);
    }

    /**
     * Creates a list of start-date names for preset dates used in report filters
     *
     * @access private
     * @return string
     */
    function getPresetDatesList()
    {
        $txt = new Text($this->language, "module_isic_report");
        $str = array();
        for ($i = 0; $i <= 4; $i++) {
            $str[$i] = $txt->display("preset_dates" . $i);
        }
        return $str;
    }

    protected function getKinds($txt) {
        return array("0" => $txt->display("all"), self::KIND_REGULAR => $txt->display("regular"), self::KIND_UNION => $txt->display("union"));
    }

    protected function getSchoolJoined($txt) {
        return array("0" => $txt->display("all"), self::JOINED => $txt->display("joined"), self::NOT_JOINED => $txt->display("not_joined"));
    }

    protected function getCurrencyFilter($filterCurrency) {
        if (!$filterCurrency || !in_array($filterCurrency, $this->isicDbCurrency->getNameList())) {
            return $this->isicDbCurrency->getDefault();
        }
        return $filterCurrency;
    }

    /**
     * @return array
     */
    protected function getFilterDates() {
        $cur_time = time();
        if ($this->vars["filter_start_date"]) {
            $this->vars["filter_start_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_start_date"]);
            $beg_date = IsicDate::getDateFormatted($this->vars["filter_start_date"], 'Y-m-d');
        } else {
            $beg_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), date("d", $cur_time) - 1, date("Y", $cur_time)), 'Y-m-d');
        }

        if ($this->vars["filter_end_date"]) {
            $this->vars["filter_end_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_end_date"]);
            $end_date = IsicDate::getDateFormatted($this->vars["filter_end_date"], 'Y-m-d');
        } else {
            $end_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), date("d", $cur_time) - 1, date("Y", $cur_time)), 'Y-m-d');
        }
        return array($beg_date, $end_date);
    }
}
