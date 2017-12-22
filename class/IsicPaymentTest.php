<?php

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");

class IsicPaymentTest {
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
     * Currency
     *
     * @var strin
     * @access protected
     */
    var $currency = "EEK";
    /**
     * Payment type - collateral
     *
     * @var int
     * @access protected
     */
    var $payment_type_collateral = 1;
    /**
     * Payment type - cost
     *
     * @var int
     * @access protected
     */
    var $payment_type_cost = 2;
    /**
     * Payment type - delivery
     *
     * @var int
     * @access protected
     */
    var $payment_type_delivery = 3;
    /**
     * Payment type - compensation
     *
     * @var int
     * @access protected
     */
    var $payment_type_compensation = 4;
    /**
     * Payment type - delivery compensation
     *
     * @var int
     * @access protected
     */
    var $payment_type_compensation_delivery = 5;

    /**
     * Payment type name
     *
     * @var array
     * @access protected
     */
    var $payment_type_name = array(
        1 => 'collateral',
        2 => 'cost',
        3 => 'delivery',
        4 => 'compensation',
        5 => 'compensation_delivery'
    );

    var $payment_method_name = array(
        1 => 'invoice',
        2 => 'external',
        3 => 'bank'
    );

    var $isicDbCurrency = false;
    var $isicDbPayments;
    var $isicDbCardDeliveries;

    /**
     * @var IsicDB_SchoolCompensationsUser
     */
    var $isicDbSchoolCompensationsUser;

    /**
     * @var IsicDB_ApplicationTypes
     */
    private $isicDbApplicationTypes;

    /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */
    function IsicPaymentTest() {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $GLOBALS['language'];
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        $this->isic_common = IsicCommon::getInstance();
        $this->isic_encoding = new IsicEncoding();

        $this->isicDbCurrency = IsicDB::factory('Currency');
        $this->isicDbPayments = IsicDB::factory('Payments');
        $this->isicDbCardDeliveries = IsicDB::factory('CardDeliveries');
        $this->isicDbSchoolCompensationsUser = IsicDB::factory('SchoolCompensationsUser');
        $this->isicDbApplicationTypes = IsicDB::factory('ApplicationTypes');
        $this->currency = $this->isicDbCurrency->getDefault();
        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->payment_collateral_required = $this->getCardTypePaymentRequired();
        $this->payment_first_required = $this->getCardTypeFirstPaymentRequired();
        $this->payment_cost_required = $this->getApplicationCostPaymentRequired();
        $this->collateral_sum = $this->getCardTypeCollateralSum();
        $this->first_sum = $this->getCardTypeFirstSum();
        $this->cost_sum = $this->getApplicationCostPaymentSum();
    }

    /**
     * Gets shcool short name
     *
     * @param int $school_id type ID
     * @return string
    */
    private function getSchoolNameShort($school_id = 0) {
        if ($school_id) {
            $r = &$this->db->query('SELECT `short_name`, `name` FROM `module_isic_school` WHERE `id` = !', $school_id);
            if ($data = $r->fetch_assoc()) {
                $t_name = trim($data["short_name"]) ? trim($data["short_name"]) : trim(mb_substr($data["name"], 0, 15));
                return $t_name ? $t_name : "-";
            }
        }

        return "";
    }

    /**
     * Gets card type short name
     *
     * @param int $type_id type ID
     * @return string
    */
    function getCardTypeNameShort($type_id = 0) {
        if ($type_id) {
            $r = &$this->db->query('SELECT `short_name`, `name` FROM `module_isic_card_type` WHERE `id` = !', $type_id);
            if ($data = $r->fetch_assoc()) {
                $t_name = trim($data["short_name"]) ? trim($data["short_name"]) : trim(mb_substr($data["name"], 0, 10));
                return $t_name ? $t_name : "-";
            }
        }

        return "";
    }

    /**
     * Gets application type short name
     *
     * @param int $type_id type ID
     * @return string
    */
    function getApplicationTypeNameShort($type_id = 0) {
        if ($type_id) {
            $r = &$this->db->query('SELECT `short_name`, `name` FROM `module_isic_application_type` WHERE `id` = !', $type_id);
            if ($data = $r->fetch_assoc()) {
                $t_name = trim($data["short_name"]) ? trim($data["short_name"]) : trim(mb_substr($data["name"], 0, 1));
                return $t_name ? $t_name : "-";
            }
        }

        return "";
    }

    /**
     * Gets payment type name
     *
     * @param int $school_id type ID
     * @return string
    */
    function getPaymentTypeName($status_id = 0, $first_card = false) {
        $txt = new Text($this->language, "module_isic_card");
        $payment_type = 0;
        if ($status_id) {
            $r = &$this->db->query('SELECT `action_type` FROM `module_isic_card_status` WHERE `id` = !', $status_id);
            if ($data = $r->fetch_assoc()) {
                switch ($data["action_type"]) {
                    case 1: // replace
                        $payment_type = 1;
                    break;
                    case 2: // prolong
                        $payment_type = 2;
                    break;
                    default : // unknown
                        $payment_type = 0;
                    break;
                }
            }
        } elseif ($first_card) {
            $payment_type = 3;
        }

        return $txt->display("payment_type_name" . $payment_type);
    }

    /**
     * Generates array of card_types with info about if payment is required or not
     *
     * @return array
    */
    function getCardTypePaymentRequired() {
        $payreq = array();

        // general rules
        $r = &$this->db->query('SELECT `id`, `payment_required` FROM `module_isic_card_type`');
        while ($data = $r->fetch_assoc()) {
            $payreq[0][$data["id"]] = $data["payment_required"];
        }

        // school-specific rules
        $r = &$this->db->query('SELECT `type_id`, `school_id`, `payment_required` FROM `module_isic_card_type_school`');
        while ($data = $r->fetch_assoc()) {
            $payreq[$data["school_id"]][$data["type_id"]] = $data["payment_required"];
        }

        return $payreq;
    }

    /**
     * Generates array of card_types with info about first card payment sums
     *
     * @return array
    */
    function getCardTypeFirstSum() {
        $firstsum = array();

        // general sums
        $r = &$this->db->query('SELECT `id`, `first_payment_sum` FROM `module_isic_card_type`');
        while ($data = $r->fetch_assoc()) {
            $firstsum[0][$data["id"]] = $data["first_payment_sum"];
        }

        // school-specific sums
        $r = &$this->db->query('SELECT `type_id`, `school_id`, `first_payment_sum` FROM `module_isic_card_type_school`');
        while ($data = $r->fetch_assoc()) {
            $firstsum[$data["school_id"]][$data["type_id"]] = $data["first_payment_sum"];
        }

        return $firstsum;
    }

    /**
     * Generates array of card_types with info about if first payment is required or not
     *
     * @return array
    */
    function getCardTypeFirstPaymentRequired() {
        $payreq = array();

        // general rules
        $r = &$this->db->query('SELECT `id`, `first_payment_required` FROM `module_isic_card_type`');
        while ($data = $r->fetch_assoc()) {
            $payreq[0][$data["id"]] = $data["first_payment_required"];
        }

        // school-specific rules
        $r = &$this->db->query('SELECT `type_id`, `school_id`, `first_payment_required` FROM `module_isic_card_type_school`');
        while ($data = $r->fetch_assoc()) {
            $payreq[$data["school_id"]][$data["type_id"]] = $data["first_payment_required"];
        }

        return $payreq;
    }

    /**
     * Generates array of card_types with info about collateral sums
     *
     * @return array
    */
    function getCardTypeCollateralSum() {
        $collateral = array();

        // general sums
        $r = &$this->db->query('SELECT `id`, `collateral_sum` FROM `module_isic_card_type`');
        while ($data = $r->fetch_assoc()) {
            $collateral[0][$data["id"]] = $data["collateral_sum"];
        }

        // school-specific sums
        $r = &$this->db->query('SELECT `type_id`, `school_id`, `payment_sum` FROM `module_isic_card_type_school`');
        while ($data = $r->fetch_assoc()) {
            $collateral[$data["school_id"]][$data["type_id"]] = $data["payment_sum"];
        }

        return $collateral;
    }

    /**
     * Generates array of application costs with info about if payment is required or not
     *
     * @return array
    */
    function getApplicationCostPaymentRequired() {
        $payreq = array();

        // general rules
        $r = &$this->db->query('SELECT `application_type`, `card_type`, `payment_required` FROM `module_isic_application_cost`');
        while ($data = $r->fetch_assoc()) {
            $payreq[0][$data["application_type"]][$data["card_type"]] = $data["payment_required"];
        }

        // school-specific rules
        $r = &$this->db->query(
            'SELECT
                `module_isic_application_cost`.`application_type`,
                `module_isic_application_cost`.`card_type`,
                `module_isic_application_cost_school`.`school_id`,
                `module_isic_application_cost_school`.`payment_required`
            FROM
                `module_isic_application_cost_school`,
                `module_isic_application_cost`
            WHERE
                `module_isic_application_cost_school`.`cost_id` = `module_isic_application_cost`.`id`
            '
        );
        while ($data = $r->fetch_assoc()) {
            $payreq[$data["school_id"]][$data["application_type"]][$data["card_type"]] = $data["payment_required"];
        }

        return $payreq;
    }

    /**
     * Generates array of application cost with info about replacement sums
     *
     * @return array
    */
    function getApplicationCostPaymentSum() {
        $cost = array();

        // general sums
        $r = &$this->db->query('SELECT `application_type`, `card_type`, `payment_sum` FROM `module_isic_application_cost`');
        while ($data = $r->fetch_assoc()) {
            $cost[0][$data["application_type"]][$data["card_type"]] = $data["payment_sum"];
        }

        // school-specific rules
        $r = &$this->db->query(
            'SELECT
                `module_isic_application_cost`.`application_type`,
                `module_isic_application_cost`.`card_type`,
                `module_isic_application_cost_school`.`school_id`,
                `module_isic_application_cost_school`.`payment_sum`
            FROM
                `module_isic_application_cost_school`,
                `module_isic_application_cost`
            WHERE
                `module_isic_application_cost_school`.`cost_id` = `module_isic_application_cost`.`id`
            '
        );
        while ($data = $r->fetch_assoc()) {
            $cost[$data["school_id"]][$data["application_type"]][$data["card_type"]] = $data["payment_sum"];
        }

        return $cost;
    }

    /**
     * Generates array of card_statuses with info about if payment is required or not
     *
     * @return array
    */
    function getCardStatusPaymentRequired() {
        $payreq = array();

        // general rules
        $r = &$this->db->query('SELECT `id`, `payment_required` FROM `module_isic_card_status`');
        while ($data = $r->fetch_assoc()) {
            $payreq[0][$data["id"]] = $data["payment_required"];
        }

        // school-specific rules
        $r = &$this->db->query('SELECT `status_id`, `school_id`, `payment_required` FROM `module_isic_card_status_school`');
        while ($data = $r->fetch_assoc()) {
            $payreq[$data["school_id"]][$data["status_id"]] = $data["payment_required"];
        }

        return $payreq;
    }

    /**
     * Generates array of card_types with info about replacement sums
     *
     * @return array
    */
    function getCardStatusCostSum() {
        $cost = array();

        // general sums
        $r = &$this->db->query('SELECT `id`, `payment_sum` FROM `module_isic_card_status`');
        while ($data = $r->fetch_assoc()) {
            $cost[0][$data["id"]] = $data["payment_sum"];
        }

        // school-specific sums
        $r = &$this->db->query('SELECT `status_id`, `school_id`, `payment_sum` FROM `module_isic_card_status_school`');
        while ($data = $r->fetch_assoc()) {
            $cost[$data["school_id"]][$data["status_id"]] = $data["payment_sum"];
        }

        return $cost;
    }

    /**
     * Check if given card has collateral checkbox checked
     *
     * @access public
     *
     * @param int $card_id card ID
     * @return bool true|false
     */
    function cardCollateralPaid($card_id = 0) {
        if ($card_id) {
            $res =& $this->db->query('
                SELECT * FROM
                    `module_isic_card`
                WHERE
                    `id` = !
                LIMIT 1',
                $card_id
            );
            if ($data = $res->fetch_assoc()) {
                if ($data["confirm_payment_collateral"]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Finds school-specific card collateral requirement value for card type
     *
     * @param int $school_id school ID
     * @param int $type_id type ID
     * @return int
    */
    function getCardCollateralRequired($school_id = 0, $type_id = 0) {
        $t_val = $this->payment_collateral_required[$school_id][$type_id];
        if ($t_val == NULL) {
            $t_val = $this->payment_collateral_required[0][$type_id];
        }
        if ($t_val !== NULL) {
            return $t_val;
        }
        return false;
    }

    /**
     * Finds school-specific card cost value for card type
     *
     * @param int $school_id school ID
     * @param int $type_id type ID
     * @return int
    */
    function getCardCollateralSum($school_id = 0, $type_id = 0) {
        $t_val = $this->collateral_sum[$school_id][$type_id];
        if ($t_val == NULL) {
            $t_val = $this->collateral_sum[0][$type_id];
        }
        if ($t_val !== NULL) {
            return $t_val;
        }
        return 0;
    }

    /**
     * Finds school-specific requirement value for application cost
     *
     * @param int $school_id school ID
     * @param int $application_id application type ID
     * @param int $type_id type ID
     * @return int
    */
    function getApplicationCostRequired($school_id = 0, $application_type_id = 0, $type_id = 0) {
        $t_val = $this->payment_cost_required[$school_id][$application_type_id][$type_id];
        if ($t_val == NULL) {
            $t_val = $this->payment_cost_required[0][$application_type_id][$type_id];
        }
        if ($t_val !== NULL) {
            return $t_val;
        }

        return false;
    }

    /**
     * Finds school-specific cost value for application cost
     *
     * @param int $school_id school ID
     * @param int $application_type_id status ID
     * @param int $type_id type ID
     * @return int
    */
    function getApplicationCostSum($school_id = 0, $application_type_id = 0, $type_id = 0) {
        $t_val = $this->cost_sum[$school_id][$application_type_id][$type_id];
        if ($t_val == NULL) {
            $t_val = $this->cost_sum[0][$application_type_id][$type_id];
        }
        if ($t_val !== NULL) {
            return $t_val;
        }
        return 0;
    }

    /**
     * Finds school-specific card cost requirement value for card status
     *
     * @param int $school_id school ID
     * @param int $status_id status ID
     * @param int $type_id type ID
     * @param bool $first first card
     * @return int
    */
    function getCardCostRequired($school_id = 0, $status_id = 0, $type_id = 0, $first = false) {
        if ($status_id) {
            $t_val = $this->payment_cost_required[$school_id][$status_id];
            if ($t_val == NULL) {
                $t_val = $this->payment_cost_required[0][$status_id];
            }
            if ($t_val !== NULL) {
                return $t_val;
            }
        } elseif ($type_id && $first) {
            $t_val = $this->payment_first_required[$school_id][$type_id];
            if ($t_val == NULL) {
                $t_val = $this->payment_first_required[0][$type_id];
            }
            if ($t_val !== NULL) {
                return $t_val;
            }
        }

        return false;
    }

    /**
     * Finds school-specific card cost value for card status
     *
     * @param int $school_id school ID
     * @param int $status_id status ID
     * @param int $type_id type ID
     * @param bool $first first card
     * @return int
    */
    function getCardCostSum($school_id = 0, $status_id = 0, $type_id = 0, $first = false) {
        if ($status_id) {
            $t_val = $this->cost_sum[$school_id][$status_id];
            if ($t_val == NULL) {
                $t_val = $this->cost_sum[0][$status_id];
            }
            if ($t_val !== NULL) {
                return $t_val;
            }
        } elseif ($type_id && $first) {
            $t_val = $this->first_sum[$school_id][$type_id];
            if ($t_val == NULL) {
                $t_val = $this->first_sum[0][$type_id];
            }
            if ($t_val !== NULL) {
                return $t_val;
            }
        }
        return 0;
    }

    /**
     * Creates array of all the needed data for exact person's school and card type combination
     * both cost and collateral as well as type (first, prolong, replace) are found
     *
     * @access private
     *
     * @param string $person_number person number (isikukood)
     * @param int $school_id school ID
     * @param int $cardTypeId card type ID
     * @param string $until_date until date cards should be looked at
     * @return string
     */
    function getCardCostCollData($person_number, $school_id, $cardTypeId, $until_date = '') {
        if ($until_date) {
            $last_card = $this->isic_common->getUserLastCardByDate($person_number, $school_id, $cardTypeId, $until_date);
        } else {
            $last_card = $this->isic_common->getUserLastCard($person_number, $school_id, $cardTypeId);
        }

        if (!$last_card) {
            $applicationType = $this->isicDbApplicationTypes->getTypeFirst(); // first
            $last_card_id = 0;
        } else {
            $last_card_id = $last_card["id"];
            $t_type_expiration = $this->isic_common->getCardExpiration($cardTypeId, $last_card["expiration_date"], false);
            if ($last_card["expiration_date"] < $t_type_expiration) {
                $applicationType = $this->isicDbApplicationTypes->getTypeProlong(); // prolong
            } else {
                $applicationType = $this->isicDbApplicationTypes->getTypeReplace(); // replace
            }
        }

        $costSum = $this->getApplicationCostSum($school_id, $applicationType, $cardTypeId);
        $costRequired = $this->getApplicationCostRequired($school_id, $applicationType, $cardTypeId);

        // First trying to find compensation for EHL and only after that regular school-based compensation
        $compensationData = $this->isicDbSchoolCompensationsUser->getEHLCompensationDataByPersonCardType($person_number, $cardTypeId);
        if (!$compensationData) {
            $compensationData = $this->isicDbSchoolCompensationsUser->getCompensationDataByPersonSchoolCardType(
                $person_number,
                $school_id,
                $cardTypeId
            );
        }
        if (!in_array($applicationType, $compensationData['application_types'])) {
            $compensationData['sum'] = 0;
            $compensationData['id'] = 0;
        }

        $costCompensationData = $compensationData;
        if (!in_array($this->payment_type_cost, $compensationData['compensation_types'])) {
            $costCompensationData['sum'] = 0;
        } else {
            $costCompensationData['sum'] = min($costSum, $compensationData['sum']);
            $compensationData['sum'] -= min($costSum, $compensationData['sum']);
        }
        $cost_data = array(
            'currency' => $this->currency,
            'last_card_id' => $last_card_id,
            'type' => $applicationType,
            'collateral' => array(
                'required' => $this->getCardCollateralRequired($school_id, $cardTypeId),
                'sum' => $this->getCardCollateralSum($school_id, $cardTypeId)
            ),
            'cost' => array(
                'required' => $costRequired,
                'sum' => number_format($costSum - $costCompensationData['sum'], 2),
            ),
            'compensation' => array(
                'required' => $costRequired,
                'sum' => $costCompensationData['sum'],
                'id' => $costCompensationData['id'],
                'hidden' => true
            ),
            'compensation_total' => array(
                'sum' => $compensationData['sum'],
                'id' => $compensationData['id'],
                'compensation_types' => $compensationData['compensation_types'],
                'hidden' => true
            ),
        );

        return $cost_data;
    }

    public function getCardCostCollDeliveryData($applRecord) {
        $cost_data = $this->getCardCostCollData($applRecord['person_number'], $applRecord['school_id'], $applRecord['type_id']);
        if (array_key_exists('delivery_id', $applRecord)) {
            $cost_data = array_merge($cost_data, $this->getDeliveryData($cost_data['compensation_total'], $applRecord['delivery_id']));
        }

        foreach ($this->payment_type_name as $typeName) {
            if ($applRecord['confirm_payment_' . $typeName]) {
                $cost_data[$typeName]['required'] = false;
            }
        }
        return $cost_data;
    }

    /**
     * @param $applRecord
     * @param $cost_data
     * @return mixed
     */
    public function getDeliveryData($compensationData, $deliveryId) {
        $cost_data = array();
        $deliverySum = $this->isicDbCardDeliveries->getDeliverySum($deliveryId);
        $deliveryCompensationData = $compensationData;
        if (!in_array($this->payment_type_delivery, $compensationData['compensation_types'])) {
            $deliveryCompensationData['sum'] = 0;
        } else {
            $deliveryCompensationData['sum'] = min($deliverySum, $compensationData['sum']);
            $compensationData['sum'] -= min($deliverySum, $compensationData['sum']);
        }
        $deliveryRequired = $this->isicDbCardDeliveries->isDeliverable($deliveryId);
        $cost_data['delivery'] = array(
            'required' => $deliveryRequired,
            'sum' => number_format($deliverySum - $deliveryCompensationData['sum'], 2),
        );

        $cost_data['compensation_delivery'] = array(
            'required' => $deliveryRequired,
            'sum' => $deliveryCompensationData['sum'],
            'id' => $deliveryCompensationData['id'],
            'hidden' => true
        );
        return $cost_data;
    }


    /**
     * Creates general_url value for card view
     *
     * @access private
     * @return string
     */
    function getGeneralUrl($card_id = 0) {
        $general_url = $_SERVER["PHP_SELF"] . "#PLEASE_CREATE_ISIC_CARD_CONTENT_PAGE";

        $template_id_list = array(808, 801, 801);

        foreach ($template_id_list as $template_id) {
            $res =& $this->db->query("
                SELECT
                    `content`.*
                FROM
                    `content`
                WHERE
                    language = ? AND
                    template = !
                LIMIT 1", $this->language, $template_id);

            if ($data = $res->fetch_assoc()) {
                $general_url = SITE_URL . "/?content=" . $data["content"];
                if ($data["structure"]) {
                    $general_url .= "&structure=" . $data["structure"];
                }
                if ($card_id) {
                    $general_url .= "&card_id=" . $card_id;
                }
                return $general_url;
            }
        }

        return $general_url;
    }

    /**
     * Creates general_url value for application view
     *
     * @access private
     * @return string
     */
    function getGeneralUrlAppl($appl_id = 0) {
        $general_url = $this->isic_common->getGeneralUrlByTemplate(
            $this->user_type == $this->isic_common->user_type_admin ?
                $this->isic_common->template_application_modify_admin :
                $this->isic_common->template_application_modify_user
        );
        if ($appl_id) {
            $general_url .= "&appl_id=" . $appl_id;
        }
        return $general_url;
    }

    /**
     * Creating an array of payment information for sending to bank
     *
     * @access public
     *
     * @param int $appl application ID
     * @param int $type payment type: 1 - collateral, 2 - cost, 3 - delivery
     * @return array payment info, or false, if error
     */
    function getPaymentInfoAppl($appl = 0, $type = '') {
        $data = $this->isic_common->getApplicationRecord($appl);
        if (!$data) {
            return false;
        }
        
        // check if this card belongs to current user and if this card requires payment and according sum is set
        if (!$this->isic_common->canModifyApplication($data)
            || !$this->isPaymentRequired($t_cost_data)) {
            return false;
        }

        $txt = new Text($this->language, "module_isic_card");
        $card_type_name = $this->getCardTypeNameShort($data["type_id"]);
        $appl_type_name = $this->getApplicationTypeNameShort($t_cost_data["type"]);
        $school_name = $this->getSchoolNameShort($data["school_id"]);
        $t_pay_message = $txt->display('pay_message');

        $src_phrase = array(
            "{PAYMENT_SUM}",
            "{CARD_TYPE}",
            "{APPLICATION_TYPE}",
            "{PERSON_NUMBER}",
            "{PERSON_NAME_LAST}",
            "{PERSON_NAME_FIRST}",
            "{SCHOOL_NAME}"
        );

        $tar_phrase = array(
            $type ? $t_cost_data[$type]['sum'] : ($t_cost_data['collateral']['sum'] . '+' .
                $t_cost_data['cost']['sum'] . '+' .
                $t_cost_data['delivery']['sum']),
            $card_type_name,
            $appl_type_name,
            $data['person_number'],
            trim(mb_substr($data['person_name_last'], 0, 15)),
            trim(mb_substr($data['person_name_first'], 0, 15)),
            $school_name
        );
        $t_pay_message = trim(mb_substr(str_replace($src_phrase, $tar_phrase, $t_pay_message), 0, 70));

        $pay_info = array(
            'pay_message' => $t_pay_message,
            'pay_amount' => $this->getPaymentSum($t_cost_data, $type)
        );
        return $pay_info;
    }

    public function isPaymentRequired($t_cost_data) {
        return
            $t_cost_data['collateral']["required"] && $t_cost_data['collateral']["sum"] ||
            $t_cost_data['cost']["required"] && $t_cost_data['cost']["sum"] ||
            $t_cost_data['delivery']["required"] && $t_cost_data['delivery']["sum"]
        ;
    }

    public function getPaymentSum($t_cost_data, $type = '') {
        $paySum = 0;
        $payTypes = array('collateral', 'cost', 'delivery');
        foreach ($payTypes as $payType) {
            if ($type && $payType != $type) {
                continue;
            }
            if ($t_cost_data[$payType]['required']) {
                $paySum += $t_cost_data[$payType]['sum'];
            }
        }
        return $paySum;
    }

    public function isApplicationPaymentComplete($appl_data, $cost_data) {
        return (
                $cost_data["cost"]["required"] && $appl_data["confirm_payment_cost"]
                || !$cost_data["cost"]["required"]
                || $cost_data['cost']['sum'] == 0
            ) && (
                $cost_data["collateral"]["required"] && $appl_data["confirm_payment_collateral"]
                || !$cost_data["collateral"]["required"]
                || $cost_data['collateral']['sum'] == 0
            ) && (
                $cost_data["delivery"]["required"] && $appl_data["confirm_payment_delivery"]
                || !$cost_data["delivery"]["required"]
                || $cost_data['delivery']['sum'] == 0
            );
    }

    /**
     * Returns deposit record from db
     *
     * @param int $appl
     * @param int $bank
     * @param array $payment payment info array
     * @return int record id, if record was found, false otherwise
     */
    function getPaymentDeposit($appl, $bank, $payment) {
        $res =& $this->db->query('
            SELECT `id` FROM
                `module_isic_payment_deposit`
            WHERE
                `card_appl` = ! AND
                `card_id` = ! AND
                `bank_id` = ! AND
                `transaction_number` = ? AND
                `transaction_date` = ?
            LIMIT 1',
            2,
            $appl,
            $bank,
            $payment['VK_T_NO'],
            $payment['VK_T_DATETIME']
        );
        if ($data = $res->fetch_assoc()) {
            return $data["id"];
        }
        return false;
    }

    /**
     * Saving payment data into deposit table
     *
     * @param int $bank
     * @param int $appl
     * @param array $payment payment info array
     * @return int insert id
     */
    function savePaymentDeposit($applData, $bank, $payment) {
        $this->db->query('
            INSERT INTO
                `module_isic_payment_deposit`
            (
                `card_appl`,
                `card_id`,
                `event_time`,
                `event_user`,
                `bank_id`,
                `transaction_number`,
                `amount`,
                `currency`,
                `rec_account`,
                `rec_name`,
                `snd_account`,
                `snd_name`,
                `ref_number`,
                `message`,
                `transaction_date`
            ) VALUES (
                !,
                !,
                NOW(),
                !,
                !,
                ?,
                !,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )',
            2,
            $applData['id'],
            $this->userid,
            $bank,
            $payment['VK_T_NO'],
            $payment['VK_AMOUNT'],
            $payment['VK_CURR'],
            $payment['VK_REC_ACC'],
            $this->isic_encoding->convertStringEncoding($payment['VK_REC_NAME']),
            $payment['VK_SND_ACC'],
            $this->isic_encoding->convertStringEncoding($payment['VK_SND_NAME']),
            $payment['VK_REF'],
            $this->isic_encoding->convertStringEncoding($payment['VK_MSG']),
            $payment['VK_T_DATETIME']
        );
        return $this->db->insert_id();
    }

    /**
     * @param int $cost_required
     * @param int $confirm_payment_cost
     * @return boolean
     */
    function isCostSumPayed($cost_required, $confirm_payment_cost) {
        return !$cost_required || $cost_required && $confirm_payment_cost;
    }

    /**
     * @param int $collateral_required
     * @param int $confirm_payment_collateral
     * @return boolean
     */
    function isCollateralSumPayed($collateral_required, $confirm_payment_collateral) {
        return !$collateral_required || $collateral_required && $confirm_payment_collateral;
    }

    function areAllSumsPayed($type, $cost_data, $row_old) {
        switch ($type) {
            case $this->payment_type_collateral:
                return $this->isCostSumPayed($cost_data["cost"]["required"], $row_old["confirm_payment_cost"]);
            break;
            case $this->payment_type_cost:
                return $this->isCollateralSumPayed($cost_data["collateral"]["required"], $row_old["confirm_payment_collateral"]);
            break;
            default :
                return false;
            break;
        }
    }

    /**
     * Saving payment info that was received from bank into db
     *
     * @param int $bank
     * @param int $appl
     * @param array $payment payment info array
     * @param int $type: 1 - collateral, 2 - cost
     * @access public
     * @return boolean true, if payment was saved, false otherwise
     */
    function savePaymentInfoAppl($bank, $appl = 0, $payment = false, $paymentType = 0) {
        $general_url = $this->getGeneralUrlAppl($appl) .
            ($this->user_type == $this->isic_common->user_type_user ? '&action=modify' : '')
        ;

        if (!is_array($payment) || !$appl) {
            redirect($general_url . '&error=bank_payment_failed');
            return false;
        }

        $deposit_id = $this->getPaymentDeposit($appl, $bank, $payment);
        // if no such record was found, saving the transaction data
        if (!$deposit_id) {
            $dbApplications = IsicDB::factory('Applications');
            $row_old = $dbApplications->getRecord($appl);
            $dbUser = IsicDB::factory('Users');
            $userRecord = $dbUser->getRecordByCode($row_old['person_number']);
            if ($userRecord) {
                $this->userid = $userRecord['user'];
            }
            if (!$this->userid) {
                $this->userid = 0;
            }
            $deposit_id = $this->savePaymentDeposit($row_old, $bank, $payment);
            // also setting application payment status to paid
            $cost_data = $this->getCardCostCollDeliveryData($row_old);

            if ($this->user_type == $this->isic_common->user_type_user) {
                $state_id = $this->isic_common->a_state_user_confirm;
                $t_request_date = date("Y-m-d H:i:s");
                $user_step = $row_old["user_step"] + 1;
            } else {
                $state_id = $row_old["state_id"];
                $user_step = $row_old["user_step"];
                $t_request_date = '';
            }

            $applData = array(
                "state_id" => $state_id,
                "user_step" => $user_step,
                "user_request_date" => $t_request_date ? $t_request_date : $row_old["user_request_date"],
                "currency" => $this->currency,
            );

            if (isset($payment['VK_T_DATETIME'])) {
                $actual_payment_date = IsicDate::getDateFormattedFromEuroToDb($payment['VK_T_DATETIME']);
            } else {
                $actual_payment_date = $this->db->now();
            }

            $paymentTypes = $paymentType ? array($paymentType, 4) : array(1, 2, 3, 4);
            foreach ($paymentTypes as $type) {
                if ($cost_data[$this->payment_type_name[$type]]['required']) {
                    $payment_info = array(
                        "prev_id" => 0,
                        "deposit_id" => $deposit_id,
                        "payment_type" => $type,
                        "payment_sum" => $cost_data[$this->payment_type_name[$type]]["sum"],
                        "actual_payment_date" => $actual_payment_date,
                        "compensation_id" => array_key_exists('id', $cost_data[$this->payment_type_name[$type]]) ?
                            $cost_data[$this->payment_type_name[$type]]['id'] :
                            0,
                        'payment_method' => $this->isicDbPayments->getMethodBank(),
                        'bank_id' => $bank
                    );

                    $this->setPayment($row_old, $payment_info);

                    $applData["confirm_payment_{$this->payment_type_name[$type]}"] = 1;
                    $applData["{$this->payment_type_name[$type]}_sum"] = $cost_data[$this->payment_type_name[$type]]["sum"];
                }
            }

            $dbApplications->setUserid($this->userid);
            $dbApplications->updateRecord($appl, $applData);
            $dbApplications->sendConfirmNotificationToAdmin($dbApplications->getRecord($appl));
        }

        redirect($general_url);
    }

    /**
     * Getting the payment sum from the deposit table for a card
     *
     * @param int $card_id card ID
     * @param int $type: 1 - collateral, 2 - cost
     * @access public
     * @return decimal sum
     */
    function getCardPaymentSum($card_id = 0, $type = 1) {
        $type_string = "%agatis%";
        if ($type == 1) {
            $not = '';
        } else {
            $not = 'NOT';
        }
        $sum = 0;

        if ($card_id) {
            $res =& $this->db->query('
                SELECT * FROM
                    `module_isic_card_deposit`
                WHERE
                    `card_id` = ! AND
                    ! `message` LIKE ?
                ',
                $card_id, $not, $type_string
            );

            while ($data = $res->fetch_assoc()) {
                $sum += $data["amount"];
            }
        }

        return $sum;
    }

    /**
     * Getting the payment info from the deposit table for a card
     *
     * @param int $card_id card ID
     * @param int $type: 1 - collateral, 2 - cost
     * @access public
     * @return array
     */
    function getCardPaymentInfo($card_id = 0, $type = 1) {
        $type_string = "%agatis%";
        if ($type == 1) {
            $not = '';
        } else {
            $not = 'NOT';
        }

        if ($card_id) {
            $res =& $this->db->query('
                SELECT * FROM
                    `module_isic_card_deposit`
                WHERE
                    `card_id` = ! AND
                    ! `message` LIKE ?
                LIMIT 1',
                $card_id, $not, $type_string
            );

            if ($data = $res->fetch_assoc()) {
                return $data;
            }
        }

        return false;
    }

    /**
     * Getting the payment info from the deposit table for a card
     *
     * @param int $card_id card ID
     * @param int $type: 1 - collateral, 2 - cost
     * @access public
     * @return array
     */
    function getApplicationPaymentInfo($card_id = 0, $type = 1) {
        $type_string = "%agatis%";
        if ($type == 1) {
            $not = '';
        } else {
            $not = 'NOT';
        }

        if ($card_id) {
            $res =& $this->db->query('
                SELECT * FROM
                    `module_isic_application_deposit`
                WHERE
                    `card_id` = ! AND
                    ! `message` LIKE ?
                LIMIT 1',
                $card_id, $not, $type_string
            );

            if ($data = $res->fetch_assoc()) {
                return $data;
            }
        }

        return false;
    }

    function getPaymentByApplication($id, $payment_type) {
        return $this->getPayment($payment_type, 1, $id);
    }

    function getPaymentByCard($id, $payment_type) {
        return $this->getPayment($payment_type, 2, $id);
    }

    function getPayment($payment_type = 1, $type = 1, $id = 0) {
        return $this->isicDbPayments->getPayment($payment_type, $type, $id);
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPaymentCollateral($appl, $payment) {
        if (is_array($appl)) {
            $payment["payment_type"] = 1;
            $this->setPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPaymentCost($appl, $payment) {
        if (is_array($appl)) {
            $payment["payment_type"] = 2;
            $this->setPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPaymentDelivery($appl, $payment) {
        if (is_array($appl)) {
            $payment["payment_type"] = 3;
            $this->setPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPaymentCompensation($appl, $payment) {
        if (is_array($appl)) {
            $payment["payment_type"] = 4;
            $this->setPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPaymentCompensationDelivery($appl, $payment) {
        if (is_array($appl)) {
            $payment["payment_type"] = 5;
            $this->setPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function setPayment($appl, $payment) {
        $payment_data = $this->getPaymentByApplication($appl["id"], $payment["payment_type"]);
        $this->updateCompensationSum($payment);
        $this->updateCompensationDeliverySum($payment);
        if ($payment_data) {
            $payment["id"] = $payment_data["id"];
            $this->updatePayment($payment);
        } else {
            $this->createPayment($appl, $payment);
        }
    }

    private function updateCompensationSum(&$payment) {
        if ($payment['payment_type'] == $this->payment_type_compensation) {
            $this->isicDbSchoolCompensationsUser->updateUsedSumById($payment['compensation_id'], $payment['payment_sum']);
        }
    }

    private function updateCompensationDeliverySum(&$payment) {
        if ($payment['payment_type'] == $this->payment_type_compensation_delivery) {
            $this->isicDbSchoolCompensationsUser->updateUsedSumById($payment['compensation_id'], $payment['payment_sum']);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function createPayment($appl, $payment) {
        if (!isset($payment["actual_payment_date"])) {
            $payment["actual_payment_date"] = $this->db->now();
        }
        $this->db->query('
            INSERT INTO
                `module_isic_payment`
            (
                `prev_id`,
                `adddate`,
                `application_id`,
                `card_id`,
                `free`,
                `person_number`,
                `type_id`,
                `deposit_id`,
                `compensation_id`,
                `payment_type`,
                `payment_sum`,
                `payment_method`,
                `bank_id`,
                `currency`,
                `rejected`,
                `returned`,
                `should_share`,
                `expired`,
                `payment_returned`,
                `rejected_date`,
                `returned_date`,
                `should_share_date`,
                `expired_date`,
                `payment_returned_date`,
                `actual_payment_date`
                ) VALUES (
                !,
                NOW(),
                !,
                !,
                !,
                ?,
                !,
                !,
                !,
                !,
                !,
                ?,
                ?,
                ?,
                !,
                !,
                !,
                !,
                !,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )',
            $payment["prev_id"],
            $appl["id"],
            $appl["card_id"],
            0,
            $appl["person_number"],
            $appl["type_id"],
            $payment["deposit_id"],
            $payment["compensation_id"],
            $payment["payment_type"],
            $this->isicDbCurrency->getSumInDefaultCurrency($payment["payment_sum"], $payment["currency"]),
            $payment["payment_method"],
            $payment["bank_id"],
            $this->currency,
            0,
            0,
            0,
            0,
            0,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $payment["actual_payment_date"]
        );
    }

    /**
     * @param array $payment
     */
    function updatePayment($payment) {
        $sql_set = false;
        foreach ($payment as $pkey => $pval) {
            $sql_set[] = $this->db->quote_field_name($pkey) . ' = ' . $this->db->quote($pval);
        }
        if ($sql_set) {
            $sql = 'UPDATE `module_isic_payment` SET' . implode(", ", $sql_set) . ' WHERE `id` = !';
            $this->db->query($sql, $payment["id"]);
        }
    }

    /**
     * @todo Move this method to IsicDB_Payments class
     */
    function createPaymentFromPayment($costData, $appl) {
        $payment = $this->getPaymentByCard($costData['last_card_id'], $this->payment_type_collateral);
        $paymentData = array(
            'payment_sum' => $costData['collateral']['sum'],
            'currency' => $costData['currency']
        );
        if ($appl && $payment && $payment["free"] && $this->isicDbPayments->isCollateralSumEqual($paymentData, $payment)) {
            $newPayment = $payment;
            $newPayment["prev_id"] = $payment["id"];
            $this->createPayment($appl, $newPayment);
            $payment["free"] = 0;
            $payment["autoreturn"] = 0;
            $payment["autoreturn_date"] = IsicDate::EMPTY_DATE;
            $this->updatePayment($payment);
        }
    }

    function setPaymentCard($appl_id, $card_id) {
        foreach ($this->payment_type_name as $paymentType => $paymentTypeName) {
            $payment = $this->getPaymentByApplication($appl_id, $paymentType);
            if ($payment) {
                $payment["card_id"] = $card_id;
                $this->updatePayment($payment);
            }
        }
    }

    function getCardApplication($card_id = 0) {
        $r = &$this->db->query('SELECT * FROM `module_isic_application` WHERE `card_id` = ! LIMIT 1', $card_id);
        if ($data = $r->fetch_assoc()) {
            return $data;
        }
        return false;
    }

    function getPaymentData($field_name = '', $payment_type = 0) {
        $pay_data = array();

        $r = &$this->db->query("SELECT * FROM `module_isic_card` WHERE `{$field_name}` = ! ORDER BY `id`", 1);
        while ($data = $r->fetch_assoc()) {
            $appl_data = $this->getCardApplication($data['id']);
            $deposit = $this->getCardPaymentInfo($data['id'], $payment_type);
            if ($deposit) {
                $deposit['card_appl'] = 1;
            }
            if (!$deposit && $appl_data) {
                $deposit = $this->getApplicationPaymentInfo($appl_data['id'], $payment_type);
                if ($deposit) {
                    $deposit['card_appl'] = 2;
                }
            }

            $prev_card_id = $data['prev_card_id'];
            if (!$prev_card_id && $appl_data) {
                $prev_card_id = $appl_data['prev_card_id'];
            }

            $pay_data[] = array(
                'person_number' => $data['person_number'],
                'card_id' => $data['id'],
                'prev_card_id' => $prev_card_id,
                'returned' => $data['returned'],
                'returned_date' => $data['returned_date'],
                'payment_returned' => $data['collateral_returned'],
                'payment_returned_date' => $data['collateral_returned_date'],
                'expiration_date' => $data['expiration_date'],
                'type_id' => $data['type_id'],
                'payment_type' => $payment_type,
                'application_id' => $appl_data ? $appl_data['id'] : false,
                'deposit' => $deposit,
            );
        }
        return $pay_data;

    }

    function getPaymentCostData() {
        return $this->getPaymentData('confirm_payment_cost', $this->payment_type_cost);
    }

    function getPaymentCollData() {
        $payment_data = $this->getPaymentData('confirm_payment_collateral', $this->payment_type_collateral);
        foreach ($payment_data as $key => $data) {
            $payment_data[$key]['free'] = $this->isCollateralFree($data['card_id'], $payment_data);
        }
        return $payment_data;
    }

    function isCollateralFree($card_id, $payment_data) {
        foreach ($payment_data as $key => $data) {
            if ($data['card_id'] == $card_id) {
                if ($data['payment_returned']) {
                    return false;
                }
                if ($data['returned']) {
                    return $this->isCollateralFree($data['prev_card_id'], $payment_data);
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    function createPaymentDeposit($data) {
        $this->db->query('
            INSERT INTO
                `module_isic_payment_deposit`
            (
                `card_appl`,
                `card_id`,
                `event_time`,
                `event_user`,
                `bank_id`,
                `transaction_number`,
                `amount`,
                `currency`,
                `rec_account`,
                `rec_name`,
                `snd_account`,
                `snd_name`,
                `ref_number`,
                `message`,
                `transaction_date`
            ) VALUES (
                !,
                !,
                ?,
                !,
                !,
                ?,
                !,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )',
            $data['card_appl'],
            $data['card_id'],
            $data['event_time'],
            $data['event_user'],
            $data['bank_id'],
            $data['transaction_number'],
            $data['amount'],
            $data['currency'],
            $data['rec_account'],
            $data['rec_name'],
            $data['snd_account'],
            $data['snd_name'],
            $data['ref_number'],
            $data['message'],
            $data['transaction_date']
            );
//        echo $this->db->show_query();
        return $this->db->insert_id();
    }

    function createPaymentRecord($data) {
        $this->db->query('
            INSERT INTO
                `module_isic_payment`
            (
                `prev_id`,
                `adddate`,
                `application_id`,
                `card_id`,
                `free`,
                `person_number`,
                `type_id`,
                `deposit_id`,
                `payment_type`,
                `payment_sum`,
                `returned`,
                `should_share`,
                `expired`,
                `payment_returned`,
                `returned_date`,
                `should_share_date`,
                `expired_date`,
                `payment_returned_date`
                ) VALUES (
                !,
                NOW(),
                !,
                !,
                !,
                ?,
                !,
                !,
                !,
                !,
                !,
                !,
                !,
                !,
                ?,
                ?,
                ?,
                ?
            )',
            $data["prev_id"] ? $data["prev_id"] : 0,
            $data["application_id"] ? $data["application_id"] : 0,
            $data["card_id"] ? $data["card_id"] : 0,
            $data['free'] ? $data['free'] : 0,
            $data["person_number"] ? $data["person_number"] : '',
            $data["type_id"] ? $data["type_id"] : 0,
            $data["deposit_id"] ? $data["deposit_id"] : 0,
            $data["payment_type"] ? $data["payment_type"] : 0,
            $data["payment_sum"] ? $data["payment_sum"] : 0,
            $data['returned'] ? $data['returned'] : 0,
            0,
            $data['expired'] ? $data['expired'] : 0,
            $data['payment_returned'] ? $data['payment_returned'] : 0,
            $data['returned'] ? $data['returned_date'] : $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $data['expired'] ? $data['expiration_date'] : $this->isic_common->empty_date,
            $data['payment_returned'] ? $data['payment_returned_date'] : $this->isic_common->empty_date
            );
    }

    function createPaymentRecords($payment_data) {
        $current_date = date("Y-m-d");
        foreach ($payment_data as $data) {
            $data['deposit_id'] = 0;
            $data['payment_sum'] = 0;
            $data['expired'] = 0;

            if ($data['deposit']) {
                $data['deposit_id'] = $this->createPaymentDeposit($data['deposit']);
                $data['payment_sum'] = $data['deposit']['amount'];
            }

            if ($data['expiration_date'] < $current_date) {
                $data['expired'] = 1;
            }
            $this->createPaymentRecord($data);
        }
    }

    function setApplicationPaymentRejectedCollateral($appl_id) {
        $this->setApplicationPaymentRejected($appl_id, $this->payment_type_collateral);
    }

    function setApplicationPaymentRejectedCost($appl_id) {
        $this->setApplicationPaymentRejected($appl_id, $this->payment_type_cost);
    }

    function setApplicationPaymentRejected($appl_id, $type) {
        $paymentData = $this->isicDbPayments->getPaymentByApplication($appl_id, $type);
        if ($paymentData && !$paymentData["payment_returned"] && !$paymentData["should_share"]) {
            $this->isicDbPayments->setFree($paymentData, $this->db->now());
        }
    }

    function setApplicationPaymentRejectedCompensation($appl_id) {
        $paymentData = $this->isicDbPayments->getPaymentByApplication($appl_id, $this->payment_type_compensation);
        if ($paymentData) {
            $this->isicDbSchoolCompensationsUser->updateUsedSumById($paymentData['compensation_id'], -1 * $paymentData['payment_sum']);
            $this->isicDbPayments->deleteRecord($paymentData['id']);
        }
    }

    function setApplicationPaymentRejectedCompensationDelivery($appl_id) {
        $paymentData = $this->isicDbPayments->getPaymentByApplication($appl_id, $this->payment_type_compensation_delivery);
        if ($paymentData) {
            $this->isicDbSchoolCompensationsUser->updateUsedSumById($paymentData['compensation_id'], -1 * $paymentData['payment_sum']);
            $this->isicDbPayments->deleteRecord($paymentData['id']);
        }
    }

    /**
     * @param $card_id
     * @param $appl_id
     */
    public function setApplicationCollateralPayment($costData, $appl_id) {
        $dbAppl = IsicDB::factory('Applications');
        $this->createPaymentFromPayment($costData, $dbAppl->getRecord($appl_id));
        $payment = $this->getPaymentByApplication($appl_id, $this->payment_type_collateral);
        if ($payment) {
            $dbAppl->updateRecord($appl_id, array(
                'confirm_payment_collateral' => 1,
                'collateral_sum' => $payment["payment_sum"],
                'currency' => $this->currency
            ));
        }
    }

    public function getPaymentTypeByName($name) {
        if (in_array($name, $this->payment_type_name)) {
            return array_search($name, $this->payment_type_name);
        }
        return 0;
    }
}