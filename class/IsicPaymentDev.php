<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");

class IsicPaymentDev {
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
     * Payment type name
     *
     * @var array
     * @access protected
     */
    var $payment_type_name = array(
        1 => "collateral",
        2 => "cost"
    );

   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */
    function IsicPaymentDev () {
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

        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->payment_collateral_required = $this->getCardTypePaymentRequired();
        $this->payment_first_required = $this->getCardTypeFirstPaymentRequired();
        $this->payment_cost_required = $this->getCardStatusPaymentRequired();
        $this->collateral_sum = $this->getCardTypeCollateralSum();
        $this->first_sum = $this->getCardTypeFirstSum();
        $this->cost_sum = $this->getCardStatusCostSum();
    }

    /**
     * Gets shcool short name
     *
     * @param int $school_id type ID
     * @return string
    */
    function getSchoolNameShort($school_id = 0) {
        if ($school_id) {
            $r = &$this->db->query('SELECT `short_name`, `name` FROM `module_isic_school` WHERE `id` = !', $school_id);
            if ($data = $r->fetch_assoc()) {
                $t_name = trim($data["short_name"]) ? trim($data["short_name"]) : trim(substr($data["name"], 0, 10));
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
                $t_name = trim($data["short_name"]) ? trim($data["short_name"]) : trim(substr($data["name"], 0, 10));
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
     * @param int $type_id type ID
     * @param string $until_date until date cards should be looked at
     * @return string
     */
    function getCardCostCollData($person_number, $school_id, $type_id, $until_date = '') {
        $cost_data = array(
            "currency" => $this->currency,
            "last_card_id" => 0
        );

        $cost_data["collateral"] = array(
            "required" => $this->getCardCollateralRequired($school_id, $type_id),
            "sum" => $this->getCardCollateralSum($school_id, $type_id)
        );

        if ($until_date) {
            $last_card = $this->isic_common->getUserLastCardByDate($person_number, $school_id, $type_id, $until_date);
        } else {
            $last_card = $this->isic_common->getUserLastCard($person_number, $school_id, $type_id);
        }
        if (!$last_card) {
            $cost_data["type"] = 3; // first
            $is_card_first = true;
            $last_card_id = 0;
            $last_card_status = 0;
        } else {
            $is_card_first = false;
            $last_card_id = $last_card["id"];
            $last_card_status = $last_card["status_id"];
            $cost_data["last_card_id"] = $last_card_id;
            $t_type_expiration = $this->isic_common->getCardExpiration($type_id, $last_card["expiration_date"], false);

            if ($last_card["expiration_date"] < $t_type_expiration) {
                $cost_data["type"] = 2; // prolong
                $last_card_status = $this->isic_common->getCardStatusProlongId($type_id);
            } else {
                $cost_data["type"] = 1; // replace
                if (!$last_card_status) {
                    $replace_error = true;
                }
            }
        }

        $cost_data["cost"] = array(
            "error" => $replace_error,
            "required" => $this->getCardCostRequired($school_id, $last_card_status, $type_id, $is_card_first),
            "sum" => $this->getCardCostSum($school_id, $last_card_status, $type_id, $is_card_first),
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
        $general_url = $_SERVER["PHP_SELF"] . "#PLEASE_CREATE_ISIC_APPLICATION_CONTENT_PAGE";

        $template_id_list = array($this->isic_common->template_application_modify_user);

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
                if ($appl_id) {
                    $general_url .= "&appl_id=" . $appl_id;
                }
                return $general_url;
            }
        }

        return $general_url;
    }

    /**
     * Creating an array of payment information for sending to bank
     *
     * @access public
     *
     * @param int $appl application ID
     * @param int $type payment type: 1 - collateral, 2 - cost
     * @return array payment info, or false, if error
     */
    function getPaymentInfoAppl($appl = 0, $type = 1) {
        $txt = new Text($this->language, "module_isic_card");
        $pay_info = array();

        $data = $this->isic_common->getApplicationRecord($appl);
        if ($data) {
            $t_cost_data = $this->getCardCostCollData($data["person_number"], $data["school_id"], $data["type_id"]);
            $card_type_name = $this->getCardTypeNameShort($data["type_id"]);
            $school_name = $this->getSchoolNameShort($data["school_id"]);

            if ($type == $this->payment_type_collateral) { // collateral payment
                $type_name = "collateral";
            } else if ($type == $this->payment_type_cost) { // card cost payment
                $type_name = "cost";
                $payment_type_name = $txt->display("payment_type_name" . $t_cost_data["type"]);
            }

            // check if this card belongs to current user and if this card requires payment and according sum is set
            if ($data['person_number'] == $this->user_code
                && $t_cost_data[$type_name]["required"]
                && $t_cost_data[$type_name]["sum"]) {

                $t_pay_message = $txt->display('pay_message_' . $type_name);

                $src_phrase = array(
                    "{PERSON_NAME}",
                    "{CARD_TYPE}",
                    "{PAYMENT_TYPE}",
                    "{SCHOOL_NAME}"
                );

                $tar_phrase = array(
                    $data['person_name_first'] . ' ' . $data['person_name_last'],
                    $card_type_name,
                    $payment_type_name,
                    $school_name
                );
                $t_pay_message = str_replace($src_phrase, $tar_phrase, $t_pay_message);

                $pay_info['pay_message'] = $t_pay_message;
                $pay_info['pay_amount'] = $t_cost_data[$type_name]["sum"];
                return $pay_info;
            }
        }
        return false;
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
    function savePaymentDeposit($appl, $bank, $payment) {
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
            $appl,
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
//        echo $this->db->show_query();
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
     * @param int $will_return_card
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
    function savePaymentInfoAppl($bank, $appl = 0, $payment = false, $type = 1) {
        if (!is_array($payment) || !$appl) {
            return false;
        }

        $deposit_id = $this->getPaymentDeposit($appl, $bank, $payment);
        // if no such record was found, saving the transaction data
        if (!$deposit_id) {
            $deposit_id = $this->savePaymentDeposit($appl, $bank, $payment);
        }

        // also setting application payment status to paid
        $row_old = $this->isic_common->getApplicationRecord($appl);
        $cost_data = $this->getCardCostCollData($row_old["person_number"], $row_old["school_id"], $row_old["type_id"]);
        $state_id = $row_old["state_id"];
        $user_step = $row_old["user_step"];

        if ($this->areAllSumsPayed($type, $cost_data, $row_old)) {
            $state_id = $this->isic_common->a_state_user_confirm;
            $t_request_date = date("Y-m-d H:i:s");
            $user_step = $user_step + 1;
        }

        $payment_info = array(
            "prev_id" => 0,
            "deposit_id" => $deposit_id,
            "payment_type" => $type,
            "payment_sum" => $cost_data[$this->payment_type_name[$type]]["sum"],
        );

        $this->setPayment($row_old, $payment_info);

        $this->db->query("
        UPDATE
            `module_isic_application`
        SET
            `module_isic_application`.`moddate` = NOW(),
            `module_isic_application`.`moduser` = !,
            `confirm_payment_{$this->payment_type_name[$type]}` = 1,
            `{$this->payment_type_name[$type]}_sum` = !,
            `state_id` = !,
            `user_step` = !,
            `user_request_date` = ?
        WHERE
            `id` = !
        ",
        $this->userid,
        $payment['VK_AMOUNT'],
        $state_id,
        $user_step,
        $t_request_date ? $t_request_date : $row_old["user_request_date"],
        $appl);

        // saving changes into log-table
        $this->isic_common->saveApplicationChangeLog(2, $appl, $row_old, $this->isic_common->getApplicationRecord($appl));

        $general_url = $this->getGeneralUrlAppl($appl);
        // redirecting to modify view
        redirect($general_url . "&action=modify");
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
        $sql = 'SELECT * FROM `module_isic_payment` WHERE `payment_type` = ! AND';
        if ($type == 1) { // by application
            $sql .= ' `application_id` = !';
        } else if ($type == 2) { // by card
            $sql .= ' `card_id` = !';
        }
        $sql .= ' ORDER BY `adddate` DESC LIMIT 1';
        $res =& $this->db->query($sql, $payment_type, $id);
        return $res->fetch_assoc();
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
    function setPayment($appl, $payment) {
        $payment_data = $this->getPaymentByApplication($appl["id"], $payment["payment_type"]);
        if ($payment_data) {
            $payment["id"] = $payment_data["id"];
            $this->updatePayment($payment);
        } else {
            $this->createPayment($appl, $payment);
        }
    }

    /**
     * @param array $appl
     * @param array $payment
     */
    function createPayment($appl, $payment) {
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
                `shared`,
                `expired`,
                `payment_returned`,
                `returned_date`,
                `shared_date`,
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
            $payment["prev_id"],
            $appl["id"],
            $appl["card_id"],
            0,
            $appl["person_number"],
            $appl["type_id"],
            $payment["deposit_id"],
            $payment["payment_type"],
            $payment["payment_sum"],
            0,
            0,
            0,
            0,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date,
            $this->isic_common->empty_date
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

    function setPaymentExpired($card_id) {
        $payment = $this->getPaymentByCard($card_id, $this->payment_type_collateral);
        if ($payment && !$payment["returned"] && !$payment["shared"]) {
            $payment["expired"] = 1;
            $payment["expired_date"] = date("Y-m-d H:i:s");
            $payment["free"] = 1;
            $this->updatePayment($payment);
        }
    }
    
    function setPaymentReturned($card_id) {
        $payment = $this->getPaymentByCard($card_id, $this->payment_type_collateral);
        if ($payment && !$payment["returned"] && !$payment["shared"]) {
            $payment["returned"] = 1;
            $payment["returned_date"] = date("Y-m-d H:i:s");
            $payment["free"] = 1;
            $this->updatePayment($payment);
        }
    }

    function setPaymentShared($card_id) {
        $payment = $this->getPaymentByCard($card_id, $this->payment_type_collateral);
        if ($payment && $payment["free"]) {
            $payment["shared"] = 1;
            $payment["shared_date"] = date("Y-m-d H:i:s");
            $payment["free"] = 0;
            $this->updatePayment($payment);
        }
    }
    
    function createPaymentFromPayment($card_id, $appl) {
        $payment = $this->getPaymentByCard($card_id, $this->payment_type_collateral);
        if ($payment && $payment["free"] && $appl) {
            $newPayment = $payment;
            $newPayment["prev_id"] = $payment["id"];
            $this->createPayment($appl, $newPayment);
            $payment["free"] = 0;
            $this->updatePayment($payment);
        }
    }
    
    function setPaymentCard($appl_id, $card_id) {
        $payment = $this->getPaymentByApplication($appl_id, $this->payment_type_collateral);
        if ($payment) {
            $payment["card_id"] = $card_id;
            $this->updatePayment($payment);
        }
        
        $payment = $this->getPaymentByApplication($appl_id, $this->payment_type_cost);
        if ($payment) {
            $payment["card_id"] = $card_id;
            $this->updatePayment($payment);
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
                `shared`,
                `expired`,
                `payment_returned`,
                `returned_date`,
                `shared_date`,
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
}