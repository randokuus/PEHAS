<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . '/JsonEncoder.php');
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicError.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Ehis/EhisUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImageUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicApplicationValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicLogger.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicPersonNumberValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicEHLClient.php");

class isic_application
{
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
    var $content_module = true;
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
     * @var user type (1 - can view all applications from the school his/her usergroup belongs to, 2 - only his/her own applications)
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
     * Users that are allowed to access the same applications as current user
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
     * Default language for new isic application
     *
     * @var int
     * @access protected
     */
    var $language_default = 3;

    /**
     * Default kind type for new isic application
     *
     * @var int
     * @access protected
     */
    var $kind_default = 1;

    /**
     * Default bank for new isic application
     *
     * @var int
     * @access protected
     */
    var $bank_default = 0;

    /**
     * CSV Import file separator
     *
     * @var string
     * @access protected
     */
    var $csv_import_separator = ';';

    /**
     * CSV Import minimum amount of fields
     *
     * @var int
     * @access protected
     */
    var $csv_import_min_fields = 1;

    /**
     * Collateral sum array for application_types
     *
     * @var array
     * @access protected
     */
    var $collateral_sum = false;

    /**
     * First payment sum array for application_types
     *
     * @var array
     * @access protected
     */
    var $first_sum = false;

    /**
     * Application cost sum array for application_statuses
     *
     * @var array
     * @access protected
     */
    var $cost_sum = false;

    /**
     * Fields that will be imported from CSV
     *
     * @var array
     * @access protected
     */
    var $csv_import_fields = array(
        "person_number", "person_name_first", "person_name_last", "person_email", "person_phone",
        "delivery_addr1", "delivery_addr2", "delivery_addr3", "delivery_addr4", "person_position",
        "person_stru_unit", "person_stru_unit2"
    );

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_card";

    /**
     * Info message match array
     *
     * @var array
     * @access protected
     */
    var $info_message = array(
        "add" => "info_add",
        "modify" => "info_modify",
        "confirm_admin" => "info_confirm_admin",
        "confirm_admin_multi" => "info_confirm_admin_multiple",
        "reject" => "info_reject",
        "confirm_payment_cost" => "info_confirm_payment_cost",
        "confirm_payment_collateral" => "info_confirm_payment_collateral",
        "confirm_payment_delivery" => "info_confirm_payment_delivery",
        "success_use_deposit" => "success_use_deposit",
        "application_added" => "info_application_added",
        "data_saved" => "info_data_saved",
        "delete" => "info_application_deleted",
        "discontinue" => "info_application_discontinued",
        "applicatin_done" => "info_application_done,"
    );

    var $error_message = array(
        'manual_confirm_needed' => 'error_manual_confirm_needed',
        'ehis_query' => 'error_ehis_query',
        'error_school_not_active' => 'error_school_not_active',
        "error_payment_date" => "error_payment_date",
        'bank_payment_failed' => 'bank_payment_failed'
    );

    var $listSortFields = array(
        "pic" => "module_isic_application.pic",
        "person_name_first" => "module_isic_application.person_name_first",
        "person_name_last" => "module_isic_application.person_name_last",
        "person_number" => "module_isic_application.person_number",
//        "collateral_paid" => "module_isic_application.confirm_payment_collateral",
        "cost_paid" => "module_isic_application.confirm_payment_cost",
        "card_type_name" => "card_type_name",
        "school_name" => "school_name",
        "person_stru_unit" => "module_isic_application.person_stru_unit",
        "person_stru_unit2" => "module_isic_application.person_stru_unit2",
        "application_type" => "appl_type_name",
        "state" => "state_name",
    );

    var $listSortFieldDefault = 'person_name_last';

    /**
     * Max amount of steps
     *
     * @var int
     * @access protected
     */
    var $max_steps = 7;

    var $txt = false;

    var $isicTemplate = false;

    /**
     * @var IsicDB_Applications
     */
    var $isicDbApplications = null;

    /**
     * @var IsicDB_Users
     */
    var $isicDbUsers = null;

    /**
     * @var IsicDB_UserStatuses
     */
    var $isicDbUserStatuses = null;

    /**
     * @var IsicDB_Schools
     */
    var $isicDbSchools = null;

    /**
     * @var IsicDB_Cards
     */
    var $isicDbCards = null;

    /**
     * @var IsicDB_CardStatuses
     */
    var $isicDbCardStatuses = null;

    /**
     * @var IsicDB_CardTypes
     */
    var $isicDbCardTypes = null;

    /**
     * @var IsicDB_ApplicationTypes
     */
    var $isicDbApplTypes = null;

    /**
     * @var IsicUser
     */
    var $isicUser = null;

    /**
     * @var IsicDB_UserGroups
     */
    var $isicDbUserGroups = null;

    /**
     * @var IsicDB_CardDeliveries
     */
    var $isicDbCardDeliveries = null;

    /**
     * @var EhisUser
     */
    private $ehisUser = null;
    /**
     * @var IsicDB_Payments
     */
    private $isicDbPayments = null;

    /**
     * @var IsicDB_Banks
     */
    private $isicDbBanks;

    var $allowed_schools = false;

    var $allowed_schools_all = false;

    var $allowed_card_types_view = false;

    var $allowed_card_types_add = false;

    var $allowed_card_types_user = false;

    var $ehisError = false;

    var $pictureUploader = false;

    var $isic_common;
    var $isic_payment;

    /**
     * @var IsicDB_Newsletters
     */
    private $isicDbNewsletters = null;

    /**
     * @var IsicDB_NewslettersOrders
     */
    private $isicDbNewslettersOrders = null;

    /**
     * @var IsicEHLClient
     */
    private $ehlClient = null;

    /**
     * @var IsicError
     */
    private $ehlError = null;

    const joined = 1;
    const not_joined = 2;
    const DEFAULT_COUNTRY = 'Eesti';

    const CSV_MAX_ROWS_NORMAL = 301;
    const CSV_MAX_ROWS_EHIS = 101;

    /**
     * Class constructor
     *
     * @global $GLOBALS ['site_settings']['template']
     * @global $GLOBALS ['language']
     * @global $GLOBALS ['database']
     */
    function isic_application()
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

        $this->txt = new Text($this->language, $this->translation_module_default);

        // assigning common methods class
        $this->isic_common = IsicCommon::getInstance();
        $this->isic_payment = new IsicPayment();

        $this->user_type_admin = $this->isic_common->user_type_admin;
        $this->user_type_user = $this->isic_common->user_type_user;

        $this->a_state_not_done = $this->isic_common->a_state_not_done;
        $this->a_state_user_confirm = $this->isic_common->a_state_user_confirm;
        $this->a_state_parent_confirm = $this->isic_common->a_state_parent_confirm;
        $this->a_state_status_check = $this->isic_common->a_state_status_check;
        $this->a_state_admin_confirm = $this->isic_common->a_state_admin_confirm;
        $this->a_state_processed = $this->isic_common->a_state_processed;
        $this->a_state_rejected = $this->isic_common->a_state_rejected;

        $this->user_appl_match = $this->isic_common->user_appl_match;

        $this->isicUser = new IsicUser($this->userid);

        $this->allowed_schools = $this->isicUser->getAllowedSchools();
        $this->allowed_schools_all = $this->isic_common->allowed_schools_all;
        $this->allowed_card_types_view = $this->isicUser->getAllowedCardTypesForView();
        $this->allowed_card_types_add = $this->isicUser->getAllowedCardTypesForAdd();
        $this->isicTemplate = new IsicTemplate('isic_application');
        $this->isicDbApplications = IsicDB::factory('Applications');
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbCardStatuses = IsicDB::factory('CardStatuses');
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');
        $this->isicDbNewsletters = IsicDB::factory('Newsletters');
        $this->isicDbNewslettersOrders = IsicDB::factory('NewslettersOrders');
        $this->isicDbApplTypes = IsicDB::factory('ApplicationTypes');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->isicDbPayments = IsicDB::factory('Payments');
        $this->isicDbCardDeliveries = IsicDB::factory('CardDeliveries');
        $this->isicDbBanks = IsicDB::factory('Banks');
        $this->ehisError = new IsicError();
        $this->ehlError = new IsicError();

        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    /**
     * Main module display function
     *
     * @return string html ISIC content
     */
    function show()
    {
        if ($this->checkAccess() == false) return "";
        $action = @$this->vars["action"];
        $step = @$this->vars["step"];
        $appl_id = @$this->vars["appl_id"];

        if (!$this->userid) {
            trigger_error("Module 'ISIC Application' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == $this->user_type_user && !$this->user_code) {
            trigger_error("Module 'ISIC Application' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }

        if ($action == "add") {
            $result = $this->addApplication(false, $action);
        } else if ($appl_id &&
            ($action == "modify" || $action == "confirm_admin" || $action == "cost" || $action == "collateral" ||
                $action == "deposit" || $action == "reject" || $action == "delivery" || $action == 'payment')
        ) {
            $result = $this->addApplication($appl_id, $action);
        } else if ($appl_id && $action == "delete") {
            $result = $this->deleteApplication($appl_id);
        } else if ($action == "addmass") {
            $result = $this->addApplicationMass($action, $step);
        } else if ($appl_id && !$action) {
            $result = $this->showApplication($appl_id);
        } else {
            $result = $this->showApplicationList();
        }
        return $result;
    }

    function showApplicationListFirst($count = 2)
    {
        $instanceParameters = 'type=applicationlistfirst';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_list_first.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $sql_base = "
            SELECT
                `module_isic_application`.*,
                IF(`module_isic_application_type`.`id`, `module_isic_application_type`.`name`, '') AS appl_type_name,
                IF(`module_isic_application_state`.`id`, `module_isic_application_state`.`name`, '') AS state_name,
                IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
            FROM
                `module_isic_application`
            LEFT JOIN
                `module_isic_application_type` ON `module_isic_application`.`application_type_id` = `module_isic_application_type`.`id`
            LEFT JOIN
                `module_isic_application_state` ON `module_isic_application`.`state_id` = `module_isic_application_state`.`id`
            LEFT JOIN
                `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `module_isic_application`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `module_isic_application`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_application`.`type_id` = `module_isic_card_type`.`id`
            WHERE
        ";
        if ($this->user_type == $this->user_type_admin) {
            $res =& $this->db->query("
                {$sql_base}
                    `module_isic_application`.`school_id` IN (!@) AND
                    `module_isic_application`.`type_id` IN (!@)
                ORDER BY
                    `module_isic_application`.`adddate` DESC
                LIMIT !, !",
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                0,
                $count
            );
        } else if ($this->user_type == $this->user_type_user) {
            $res =& $this->db->query("
                {$sql_base}
                    (`module_isic_application`.`school_id` IN (!@) OR ! = !) AND
                    (`module_isic_application`.`person_number` IN (?@) OR
                    `module_isic_application`.`parent_user_id` = !)
                ORDER BY
                    `module_isic_application`.`adddate` DESC
                LIMIT !, !",
                IsicDB::getIdsAsArray($this->allowed_schools),
                sizeof($this->allowed_schools),
                sizeof($this->allowed_schools_all),
                $this->isic_common->getCurrentUserCodeList(),
                $this->userid,
                0,
                $count
            );
        }

        $txt = new Text($this->language, $this->translation_module_default);
        $generalUrl = $generalUrlModify = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application'));
        $generalUrlModifyHiddenSchool = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user_hidden_school'));
        $generalUrlModifyRegular = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user'));

        while ($data = $res->fetch_assoc()) {
            if ($this->user_type == $this->user_type_user) {
                if ($this->isicDbSchools->getHiddenSchoolId() && $data['school_id'] == $this->isicDbSchools->getHiddenSchoolId()) {
                    $generalUrlModify = $generalUrlModifyHiddenSchool;
                } else {
                    $generalUrlModify = $generalUrlModifyRegular;
                }
            }
            $tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb')));
            $tpl->addDataItem("DATA.ACTIVE", $txt->display("active" . $data["active"]));
            $tpl->addDataItem("DATA.STATE_NAME", $data["state_name"]);
            $tpl->addDataItem("DATA.SCHOOL_NAME", $data["school_name"]);
            $tpl->addDataItem("DATA.CARD_TYPE_NAME", $data["card_type_name"]);
            $tpl->addDataItem("DATA.PERSON_NAME_FIRST", $data["person_name_first"]);
            $tpl->addDataItem("DATA.PERSON_NAME_LAST", $data["person_name_last"]);
            $tpl->addDataItem("DATA.PERSON_BIRTHDAY", IsicDate::getDateFormatted($data["person_birthday"]));
            $tpl->addDataItem("DATA.PERSON_NUMBER", $data["person_number"]);
            $tpl->addDataItem("DATA.ISIC_NUMBER", $data["isic_number"]);
            $tpl->addDataItem("DATA.URL_DETAIL", $generalUrl . "&appl_id=" . $data["id"]);

            if ($this->isic_common->canModifyApplication($data)) {
                $tpl->addDataItem("DATA.MOD.URL", $generalUrlModify . "&appl_id=" . $data["id"] . "&action=modify");
            }

            if ($this->isic_common->canDeleteApplication($data)) {
                $tpl->addDataItem("DATA.DEL.URL", "javascript:del('" . $generalUrl . "&appl_id=" . $data["id"] . "&action=delete" . "');");
            }

        }
        $tpl->addDataItem("URL_ALL", $generalUrl);
        if ($this->user_type == $this->user_type_admin) {
            $tpl->addDataItem("URL_ADD", $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add')));
        } else {
            $tpl->addDataItem("URL_ADD", $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user')));
        }
        $tpl->addDataItem("CONFIRMATION", $this->txt->display("application_delete_confirm"));

        return $tpl->parse();

    }

    public function showApplicationAddButtonMobile()
    {
        // in case it is not first page, returning empty string, otherwise order button
        if ($this->vars['content']) {
            return '';
        }
        $instanceParameters = 'type=applicationaddbuttonmobile';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_add_button_mobile.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        if ($this->user_type == $this->user_type_admin) {
            $tpl->addDataItem("URL_ADD", $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add')));
        } else {
            $tpl->addDataItem("URL_ADD", $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user')));
        }
        $tpl->addDataItem("BUTTON_TITLE", $this->txt->display("order_new_card"));
        return $tpl->parse();
    }

    /**
     * Displays list of applications
     *
     * @return string html listview of applications
     */
    function showApplicationList()
    {
        if ($this->checkAccess() == false) return "";
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];
        $confirm_appl = $this->vars["confirm_appl"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $general_url_modify = $general_url_plain = $general_url;
        $general_url_card = $this->isic_common->getGeneralUrlByTemplate($this->isic_common->template_card_list);
        if ($this->user_type == $this->user_type_user) {
            $generalUrlModifyHiddenSchool = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user_hidden_school'));
            $generalUrlModifyRegular = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user'));
        }
        $hiddenSchoolId = $this->isicDbSchools->getHiddenSchoolId();

        if (!$start || $confirm_appl) {
            $start = 0;
        }
        $hidden = IsicForm::getHiddenField('start', $start);

        if ($this->module_param["isic_application"]) {
            $this->list_type = $this->module_param["isic_application"];
        }

        $instanceParameters = 'type=applicationlist&sort=' . $this->vars["sort"] . "&sort_order=" . $this->vars["sort_order"];
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_list.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $confirm_states = array(
            $this->a_state_not_done,
            $this->a_state_user_confirm,
            $this->a_state_rejected
        );

        $condition_data = $this->getListSqlCondition();
        $condition_sql = $condition_data["condition_sql"];
        $hidden .= $condition_data["hidden"];
        $url_filter = $condition_data["url_filter"];
        $bind_url_filter = $condition_data["bind_url_filter"];

        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);
        $hidden .= IsicForm::getHiddenField('sort', $listSortOrder->getSort());
        $hidden .= IsicForm::getHiddenField('sort_order', $this->vars["sort_order"]);
        $order_by = $listSortOrder->getOrderBy();
        $sort_order = $listSortOrder->getSortOrder();
        $sort_order1 = $listSortOrder->getSortOrderReverse();

        $sql_base = "
            SELECT
                `module_isic_application`.*,
                IF(`module_isic_application_type`.`id`, `module_isic_application_type`.`name`, '') AS appl_type_name,
                IF(`module_isic_application_state`.`id`, `module_isic_application_state`.`name`, '') AS state_name,
                IF(`module_isic_application`.`school_id` = {$hiddenSchoolId}, '', IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '')) AS school_name,
                IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
            FROM
                `module_isic_application`
            LEFT JOIN
                `module_isic_application_type` ON `module_isic_application`.`application_type_id` = `module_isic_application_type`.`id`
            LEFT JOIN
                `module_isic_application_state` ON `module_isic_application`.`state_id` = `module_isic_application_state`.`id`
            LEFT JOIN
                `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `module_isic_application`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `module_isic_application`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_application`.`type_id` = `module_isic_card_type`.`id`
            WHERE
        ";

        if ($this->user_type == $this->user_type_admin) {
            $res =& $this->db->query("
                {$sql_base}
                    `module_isic_application`.`school_id` IN (!@) AND
                    `module_isic_application`.`type_id` IN (!@)
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $condition_sql,
                $order_by,
                $sort_order,
                $start,
                $this->maxresults
            );
        } else if ($this->user_type == $this->user_type_user) {
            $res =& $this->db->query("
                {$sql_base}
                    (`module_isic_application`.`school_id` IN (!@) OR ! = !)
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                IsicDB::getIdsAsArray($this->allowed_schools),
                sizeof($this->allowed_schools),
                sizeof($this->allowed_schools_all),
                $condition_sql,
                $order_by,
                $sort_order,
                $start,
                $this->maxresults
            );
        }
//        echo "<!-- SQL: " . $this->db->show_query() . " -->\n";

        if ($res !== false) {
            $error_confirm = false;
            $confirm_count = 0;
            $error_count = 0;
            if ($res->num_rows()) {
                while ($data = $res->fetch_assoc()) {
                    $error_confirm_row = false;
                    $error_ehis_query = false;
                    $show_confirm_box = false;
                    $show_record = true;
                    $can_modify = $this->isic_common->canModifyApplication($data);
                    $cost_data = $this->isic_payment->getCardCostCollDeliveryData($data);
                    if ($this->user_type == $this->user_type_admin && $can_modify && in_array($this->list_type, $confirm_states)) {
                        if ($this->vars["write_confirm"] && $confirm_appl[$data["id"]]) {
                            switch ($this->vars["confirm_type"]) {
                                case 'admin_confirm':
                                    if ($data["state_id"] != IsicDB_Applications::state_admin_confirm
                                        && $this->isic_payment->isApplicationPaymentComplete($data, $cost_data)
                                    ) {
                                        // performing EHL status retrieval if card type requires it
                                        if (!$this->vars['confirm_admin_confirm'] && $this->isicDbCardTypes->isEHLCheckNeeded($data['type_id'])) {
                                            $this->enableUserEhlDataCheck($this->isicDbUsers->getRecordByCode($data["person_number"]));
                                            $this->getEHLClient()->getStatusListByUser($data['person_number']);
                                        }

                                        if (!$this->vars['confirm_admin_confirm'] && $this->isicDbCardTypes->isExternalCheckNeeded($data['type_id'])) {
                                            // perform external check and see if needed groups are already present
                                            $error_confirm_row = !$this->isApplicationUserInNeededGroup($data);
                                            if ($this->ehisError->isError()) {
                                                $error_confirm_row = $error_ehis_query = true;
                                            }
                                        }
                                        //$error_confirm_row = $error_school_not_active = (!$this->isicDbSchools->isActive($data["school_id"]))?1:0;
                                        if (!$error_confirm_row) {
                                            $this->autoPayments($cost_data, $data);
                                            $updateData = array(
                                                'id' => $data['id'],
                                                'confirm_admin' => 1,
                                                'state_id' => IsicDB_Applications::state_admin_confirm
                                            );
                                            $this->isicDbApplications->updateRecord($data['id'], $updateData);
                                            $this->setUserAndGroupsByApplication($data);
                                            $this->isicDbApplications->sendConfirmNotificationToUser($data['id']);
                                            $this->vars["info"] = "confirm_admin_multi";
                                            $show_record = false; // not showing the record in list any more
                                            $confirm_count++;
                                        } else {
                                            $error_confirm = true;
                                            $error_count++;
                                        }
                                    }
                                    break;
                                default :
                                    break;
                            }
                        }

                        $show_confirm_box = true;
                    }

                    if ($show_record) {
                        $tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb')));
                        $tpl->addDataItem("DATA.URL_IMAGE", IsicImage::getPopUpForUrl(IsicImage::getPictureUrl($data['pic'], 'big')));

                        $cost_info = $this->formatCostData($cost_data);

                        $collPaymentData = $this->isic_payment->getPaymentByApplication($data["id"], 1);
                        if ($collPaymentData["payment_sum"] != 0) {
                            $confirm_payment_collateral = $this->txt->display("active1");
                        } else {
                            $confirm_payment_collateral = $cost_data["collateral"]["required"] ? $this->txt->display("active" . $data["confirm_payment_collateral"]) : "-";
                        }
                        $costPaymentData = $this->isic_payment->getPaymentByApplication($data["id"], 2);
                        if ($costPaymentData["payment_sum"] != 0) {
                            $confirm_payment_cost = $this->txt->display("active1");
                        } else {
                            $confirm_payment_cost = $cost_data["cost"]["required"] ? $this->txt->display("active" . $data["confirm_payment_cost"]) : "-";
                        }

                        $tpl->addDataItem("DATA.DATA_APPL_TYPE_NAME", $data["appl_type_name"]);
                        $tpl->addDataItem("DATA.DATA_STATE_NAME", $data["state_name"]);
                        $tpl->addDataItem("DATA.DATA_SCHOOL_NAME", $data["school_name"]);
                        $tpl->addDataItem("DATA.DATA_CARD_TYPE_NAME", $data["card_type_name"]);
                        $tpl->addDataItem("DATA.DATA_PERSON_NAME_FIRST", $data["person_name_first"]);
                        $tpl->addDataItem("DATA.DATA_PERSON_NAME_LAST", $data["person_name_last"]);
                        $tpl->addDataItem("DATA.DATA_PERSON_BIRTHDAY", IsicDate::getDateFormatted($data["person_birthday"]));
                        $tpl->addDataItem("DATA.DATA_PERSON_NUMBER", $data["person_number"]);
                        $tpl->addDataItem("DATA.DATA_PERSON_STRU_UNIT", $data["person_stru_unit"]);
                        $tpl->addDataItem("DATA.DATA_PERSON_STRU_UNIT2", $data["person_stru_unit2"]);
//                        $tpl->addDataItem("DATA.DATA_PERSON_CLASS", $data["person_class"]);
                        $tpl->addDataItem("DATA.DATA_CONFIRM_PAYMENT_COLLATERAL", $confirm_payment_collateral);
                        $tpl->addDataItem("DATA.DATA_CONFIRM_PAYMENT_COST", $confirm_payment_cost);
                        $tpl->addDataItem("DATA.URL_DETAIL", $general_url . "&appl_id=" . $data["id"] . $url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order);

                        // find all the binded applications / cards for current application
                        $bind_data = $this->isic_common->getApplicationBindings($data);
                        if (is_array($bind_data)) {
                            if (is_array($bind_data["card"])) {
                                foreach ($bind_data["card"] as $btype => $bval) {
                                    if (sizeof($bval) == 1) {
                                        $t_url = $general_url_card . "&card_id=" . $bval[0]["id"] . "&bind=1" . $bind_url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order . "&content_prev=" . $content;
                                    } else {
                                        $t_url = $general_url_card . "&bind_id=" . $data["id"] . "&filter_type_id=" . $btype . $bind_url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order . "&content_prev=" . $content;
                                    }
                                    $tpl->addDataItem("DATA.BIND.URL", $t_url);
                                    $tpl->addDataItem("DATA.BIND.NAME", $this->isicDbCardTypes->getNameById($btype));
                                }
                            }
                            if (is_array($bind_data["appl"])) {
                                foreach ($bind_data["appl"] as $btype => $bval) {
                                    if (sizeof($bval) == 1) {
                                        $t_url = $general_url . "&appl_id=" . $bval[0]["id"] . "&bind=1" . $bind_url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order;
                                    } else {
                                        $t_url = $general_url . "&bind_id=" . $data["id"] . "&filter_type_id=" . $btype . $bind_url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order;
                                    }
                                    $tpl->addDataItem("DATA.BIND.URL", $t_url);
                                    $tpl->addDataItem("DATA.BIND.NAME", $this->isicDbCardTypes->getNameById($btype) . " - " . $this->txt->display("application"));
                                }
                            }
                        }

                        if ($can_modify) {
                            if ($this->user_type == $this->user_type_user) {
                                if ($hiddenSchoolId && $data['school_id'] == $hiddenSchoolId) {
                                    $general_url_modify = $generalUrlModifyHiddenSchool;
                                } else {
                                    $general_url_modify = $generalUrlModifyRegular;
                                }

                            }
                            $tpl->addDataItem("DATA.MOD.URL_MODIFY", $general_url_modify . "&appl_id=" . $data["id"] . $url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order . "&action=modify");
                        }

                        if ($this->isic_common->canDeleteApplication($data)) {
                            $tpl->addDataItem("DATA.DEL.URL_DELETE", "javascript:del('" . $general_url . "&appl_id=" . $data["id"] . $url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order . "&action=delete" . "');");
                        }

                        if ($show_confirm_box) {
                            if ($this->isic_payment->isApplicationPaymentComplete($data, $cost_data)
                                && validateEmail($data['person_email'])
                                /*&& $data['pic']*/) {
                                $f = new AdminFields("confirm_appl[" . $data["id"] . "]", array("type" => "checkbox"));
                                $field_data = $f->display($confirm_appl[$data["id"]]);
                                $tpl->addDataItem("DATA.CONFIRM.DATA", $field_data);
                            } else {
                                $tpl->addDataItem("DATA.CONFIRM.DATA", "");
                            }
                        }
                    }
                }
            } else {
                $tpl->addDataItem("RESULTS", $this->txt->display("results_none"));
            }
            $res->free();
        } else {
            echo "Database error " . $this->db->error_code() . ": " . $this->db->error_string();
        }

        if ($this->user_type == $this->user_type_admin) {
            if (in_array($this->list_type, $confirm_states)) {
                $tpl->addDataItem("CONFIRM_TITLE.TITLE", $this->txt->display("check"));
                $tpl->addDataItem("CHECK_ALL.DUMMY", "");

                if ($this->list_type != $this->a_state_admin_confirm) {
                    if ($error_confirm) {
                        $hidden .= IsicForm::getHiddenField('confirm_admin_confirm', '');
                        $tpl->addDataItem("CONFIRM_BUTTON.CONFIRM_ADMIN_CONFIRM.TITLE", $this->txt->display("confirm"));
                    } else {
                        $tpl->addDataItem("CONFIRM_BUTTON.ADMIN_CONFIRM.TITLE", $this->txt->display("confirm"));
                    }
                }
                /*
                if ($this->list_type != $this->a_state_rejected) {
                    $tpl->addDataItem("CONFIRM_BUTTON.REJECT.TITLE", $this->txt->display("reject"));
                }
                */
            }
        }

        // page listing
        $sql_base_count = "
            SELECT
                COUNT(*) AS cards_total
            FROM
                `module_isic_application`
            LEFT JOIN
                `module_isic_school` ON `module_isic_application`.`school_id` = `module_isic_school`.`id`
            WHERE
        ";
        if ($this->user_type == $this->user_type_admin) {
            $res =& $this->db->query("
                {$sql_base_count}
                    `module_isic_application`.`school_id` IN (!@) AND
                    `module_isic_application`.`type_id` IN (!@)
                    !",
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $condition_sql
            );
        } elseif ($this->user_type == $this->user_type_user) {
            $res =& $this->db->query("
                {$sql_base_count}
                    1 = 1
                    !",
                $condition_sql
            );
        }
        $data = $res->fetch_assoc();
        $total = $results = $data["cards_total"];

        $disp = ereg_replace("{NR}", "$total", $this->txt->display("application_results"));
        if ($results >= $this->maxresults) {
            $end = $start + $this->maxresults;
        } else {
            $end = $start + $results;
        }
        if ($end == 0) {
            $start0 = 0;
        } else {
            $start0 = $start + 1;
        }
        $disp = str_replace("{DISP}", $start0 . "-$end", $disp);
        $tpl->addDataItem("RESULTS", $disp);

        $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . $url_filter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $sort_order, $this->maxresults, $this->txt->display("prev"), $this->txt->display("next")));

        // ####

        if ($error_ehis_query) {
            $this->vars['error'] = 'confirm_ehis_query';
        } else if ($error_confirm) {
            $this->vars['error'] = 'confirm';
        }

        switch ($this->vars["error"]) {
            case "modify":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_modify"));
                break;
            case "delete":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_delete"));
                break;
            case "view":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_view"));
                break;
            case "confirm":
                $tpl->addDataItem("AMESSAGE.MESSAGE", $this->txt->display("error_confirm"));
                break;
            case "confirm_ehis_query":
                $tpl->addDataItem("AMESSAGE.MESSAGE", $this->txt->display("error_confirm_ehis_query"));
                break;
        }

        // filter fields are only shown to admin-user
        if ($this->user_type == $this->user_type_admin) {
            $fields = $this->getFilterList();

            foreach ($fields as $key => $val) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];
                $fdata["class"] = $val[6];

                $f = new AdminFields("$key", $fdata);
                $field_data = $f->display($val[3]);
                $tpl->addDataItem("SEARCH.FIELD_$key", $field_data);
                unset($fdata);
            }
            if ($this->isicDbUsers->isCurrentUserSuperAdmin()) {
                $fdata["type"] = "select";
                $fdata["size"] = 40;
                $fdata["list"] = $this->getSchoolJoined();
                $fdata["value"] = $this->vars["filter_joined"];
                $f = new AdminFields("filter_joined", $fdata);
                $field_data = $f->display($this->vars["filter_joined"]);
                $tpl->addDataItem("SEARCH.JOINED.FIELD_filter_joined", $field_data);
            }
            $tpl->addDataItem("SEARCH.SELF", $general_url);
        }
        if ($this->vars["info"] && $this->info_message[$this->vars["info"]] && $confirm_count) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", str_replace('<CONFIRM_COUNT>', $confirm_count, $this->txt->display($this->info_message[$this->vars["info"]])));
        }

        $listSortOrder->showTitleFields($tpl, $this->txt, $general_url . $url_filter, array(
            'collateral_paid' => 'width-limited',
            'cost_paid' => 'width-limited',
        ));
        $tpl->addDataItem("URL_GENERAL_PLAIN", $general_url_plain);
        $tpl->addDataItem("URL_GENERAL", $general_url . $url_filter . "&sort_order=" . $sort_order1);
        $tpl->addDataItem("URL_ADD", $general_url . $url_filter . "&sort_order=" . $sort_order1 . "&action=add");
        $tpl->addDataItem("URL_IMPORT", $general_url . $url_filter . "&sort_order=" . $sort_order1 . "&action=addmass");
        $tpl->addDataItem("SELF", $general_url);
        $tpl->addDataItem("HIDDEN", $hidden);

        $tpl->addDataItem("CONFIRMATION", $this->txt->display("application_delete_confirm"));
        // ####
        return $tpl->parse();
    }

    function setUserAndGroupsByApplication($applData)
    {
        $userData = $this->getUserDataByApplicationAndAddNewIfNotFound($applData);
        if ($applData['order_for_others']) {
            // in case of application being ordered for other (parent to child)
            // updating profile data from application
            $this->isicDbUsers->updateRecordFromApplication($userData["user"], $applData);
            // updating also profile image
            $this->isic_common->updateUserPic($userData["user"], $applData['id']);
            $parentUserData = $this->isicDbUsers->getRecord($applData['parent_user_id']);
            $children = $parentUserData['children_list'] ? explode(',', $parentUserData['children_list']) : array();
            // adding child person number into parent children list
            if (!in_array($applData['person_number'], $children)) {
                $children[] = $applData['person_number'];
                $this->isicDbUsers->updateRecord($parentUserData['user'],
                    array('children_list' => implode(',', $children))
                );
            }
        }
        $this->setMissingUserGroups($applData, $userData);
        $this->updateStatusInfoFields($applData, $userData);
    }

    function getUserDataByApplicationAndAddNewIfNotFound($applData)
    {
        $userData = $this->isicDbUsers->getRecordByCode($applData["person_number"]);
        if (!$userData) {
            // adding user if it doesn't exist yet
            $userId = $this->isicDbUsers->insertRecord($this->getUserInsertData($applData));
            $userData = $this->isicDbUsers->getRecord($userId);
        }
        return $userData;
    }

    function setMissingUserGroups($applData, $userData)
    {
        $neededGroups = $this->isicDbUserGroups->getRecordsBySchoolAndCardType($applData['school_id'], $applData['type_id']);
        if (!$this->isGroupListInNeededGroups(IsicDB::getIdsAsArray($userData['ggroup']), $neededGroups)) {
            // adding missing user statuses for this school and card type
            $this->isicDbUserStatuses->setUserStatusesBySchoolCardType($userData['user'], $applData['school_id'], $applData['type_id'], 1);
        }
    }

    function isApplicationUserInNeededGroup($applData)
    {
        $userData = $this->getUserDataByApplicationAndAddNewIfNotFound($applData);
        if ($this->isicDbCardTypes->isExternalCheckNeeded($applData['type_id'])) {
            // first check data from ehis
            $this->enableUserExternalDataCheck($userData);
            $idList = $this->getEhisUser()->getStatusListByUser($userData['user_code']);
            $this->ehisError = $this->getEhisUser()->getError();
            $userData = $this->isicDbUsers->getRecord($userData['user']);
        }
        $neededGroups = $this->isicDbUserGroups->getRecordsBySchoolAndCardType($applData['school_id'], $applData['type_id']);
        return $this->isGroupListInNeededGroups(IsicDB::getIdsAsArray($userData['ggroup']), $neededGroups);
    }

    function isGroupListInNeededGroups($currentGroups, $neededGroups)
    {
        foreach ($neededGroups as $group) {
            if (in_array($group['id'], $currentGroups)) {
                return true;
            }
        }
        return false;
    }

    function updateStatusInfoFields($applRecord, $userRecord)
    {
        $statusRecords = $this->isicDbUserStatuses->getAllRecordsByUserSchoolCardType($userRecord['user'], $applRecord['school_id'], $applRecord['type_id']);
        foreach ($statusRecords as $statusRecord) {
            // excluding all automatic statuses from updtes
            if ($this->isicDbUserStatuses->isAutomaticStatus($statusRecord['addtype'])) {
                continue;
            }
            $saveData = array(
                'faculty' => $applRecord['person_stru_unit2'],
                'class' => $applRecord['person_class'],
                'position' => $applRecord['person_position'],
                'structure_unit' => $applRecord['person_stru_unit'],
                //'course' => $applRecord['person_course'],
            );
            $this->isicDbUserStatuses->updateRecord($statusRecord['id'], $saveData);
        }
    }

    /**
     *
     */
    function getListSqlCondition()
    {
        $ff_fields = array(
            "kind_id", "bank_id", "type_id", "school_id", "person_name_first", "person_name_last",
            "person_number", "person_stru_unit", "person_stru_unit2", "application_type_id", "joined"
        );

        $ff_fields_partial = array(
            "person_name_first", "person_name_last", "person_number", "person_stru_unit", "person_stru_unit2"
        );

        $ff_tables = array(
            "joined" => "module_isic_school"
        );
        $condition = array();
        $bcondition = array();
        $bind_data = array();
        $url_filter = '';
        $bind_url_filter = '';
        $hidden = '';
        for ($f = 0; $f < sizeof($ff_fields); $f++) {
            $t_filter_value = $this->vars["filter_" . $ff_fields[$f]] = urldecode($this->vars["filter_" . $ff_fields[$f]]);
            if ($t_filter_value != "" && $t_filter_value != "0") {
                if ($ff_fields[$f] == "joined" && $t_filter_value == '2') {
                    $t_filter_value = 0;
                }
                if (array_key_exists($ff_fields[$f], $ff_tables)) {
                    $tableName = $ff_tables[$ff_fields[$f]];
                } else {
                    $tableName = "module_isic_application";
                }
                if (in_array($ff_fields[$f], $ff_fields_partial)) {
                    $condition[] = $this->db->quote_field_name($tableName . "." . $ff_fields[$f]) . " LIKE " . $this->db->quote("%" . $t_filter_value . "%");
                } else {
                    $condition[] = $this->db->quote_field_name($tableName . "." . $ff_fields[$f]) . " = " . $this->db->quote($t_filter_value);
                }
                $url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($t_filter_value);
                if ($ff_fields[$f] != "type_id") {
                    $bind_url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($t_filter_value);
                }
                $hidden .= IsicForm::getHiddenField('filter_' . $ff_fields[$f], $t_filter_value);
            }
        }
        // different view-filters (so called list_types)
        if ($this->list_type) {
            $condition[] = "`module_isic_application`.`state_id` = " . $this->list_type;
        }

        // restrictions based on user-type
        switch ($this->user_type) {
            case $this->user_type_admin: // admin
                // do nothing, other filters will be in place
                break;
            case $this->user_type_user: // regular
                $subcondition = array(
                    "`module_isic_application`.`person_number` IN (" .
                    implode(',', $this->isic_common->getArrayQuoted($this->isic_common->getCurrentUserCodeList())) .
                    ")",
                    "`module_isic_application`.`parent_user_id` = " . $this->db->quote($this->userid)
                );
                $condition[] = '(' . implode(' OR ', $subcondition) . ')';
                break;
        }

        // if bind-query was requested then creating additional filter with only binded card id-s
        if ($this->vars["bind_id"] && $this->vars["filter_type_id"]) {
            $bind_data = $this->isic_common->getApplicationBindings($this->isicDbApplications->getRecord($this->vars["bind_id"]));
            if (is_array($bind_data) && is_array($bind_data["appl"]) && is_array($bind_data["appl"][$this->vars["filter_type_id"]])) {
                $bcondition = array();
                foreach ($bind_data["appl"][$this->vars["filter_type_id"]] as $bval) {
                    $bcondition[] = "module_isic_application.`id` = " . mysql_escape_string($bval["id"]) . "";
                }
                $condition[] = "(" . implode(" OR ", $bcondition) . ")";
            }
        }

        $confirm_appl = $this->vars["confirm_appl"];

        if ($confirm_appl) {
            $condition[] = '`module_isic_application`.`id` IN (' . implode(',', array_keys($confirm_appl)) . ')';
        }

        if ($this->vars['filter_region_id']) {
            $regionSchools = $this->isicDbSchools->getIdListByRegion($this->vars['filter_region_id']);
            $condition[] = "(`module_isic_application`.`school_id` IN (" . implode(",", $regionSchools) . "))";
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql = " AND " . $condition_sql;
        }

        return array(
            "condition_sql" => $condition_sql,
            "hidden" => $hidden,
            "url_filter" => $url_filter,
            "bind_url_filter" => $bind_url_filter,
        );
    }

    /**
     *
     */
    function getFilterList()
    {
        return array(
            "filter_application_type_id" => array("select", 0, 0, $this->vars["filter_application_type_id"], $this->getApplicationTypeList(), "", ""),
            "filter_language_id" => array("select", 0, 0, $this->vars["filter_language_id"], $this->getCardLanguageList(), "", ""),
            "filter_kind_id" => array("select", 0, 0, $this->vars["filter_kind_id"], $this->getCardKindList(), "", ""),
            "filter_bank_id" => array("select", 0, 0, $this->vars["filter_bank_id"], $this->isicDbBanks->getBankList($this->txt->display('all_bank')), "", ""),
            "filter_type_id" => array("select", 0, 0, $this->vars["filter_type_id"], $this->getCardTypeList('view'), "", ""),
            "filter_school_id" => array("select", 0, 0, $this->vars["filter_school_id"], $this->getSchoolList(), "", ""),
            "filter_region_id" => array("select", 0, 0, $this->vars["filter_region_id"], $this->getRegionList(), "", ""),
            "filter_person_name_first" => array("textinput", 40, 0, $this->vars["filter_person_name_first"], "", "", ""),
            "filter_person_name_last" => array("textinput", 40, 0, $this->vars["filter_person_name_last"], "", "", ""),
            "filter_person_number" => array("textinput", 40, 0, $this->vars["filter_person_number"], "", "", ""),
            "filter_delivery_addr1" => array("textinput", 40, 0, $this->vars["filter_delivery_addr1"], "", "", ""),
            "filter_delivery_addr2" => array("textinput", 40, 0, $this->vars["filter_delivery_addr2"], "", "", ""),
            "filter_delivery_addr3" => array("textinput", 40, 0, $this->vars["filter_delivery_addr3"], "", "", ""),
            "filter_delivery_addr4" => array("textinput", 40, 0, $this->vars["filter_delivery_addr4"], "", "", ""),
            "filter_person_stru_unit" => array("textinput", 40, 0, $this->vars["filter_person_stru_unit"], "", "", ""),
            "filter_person_stru_unit2" => array("textinput", 40, 0, $this->vars["filter_person_stru_unit2"], "", "", ""),
//            "filter_person_class" => array("textinput", 40, 0, $this->vars["filter_person_class"], "", "", ""),
        );
    }

    private function getSchoolJoined($txt = '')
    {
        $txt = new Text($this->language, "module_isic_school");
        return array("0" => $txt->display("all"), self::joined => $txt->display("joined"), self::not_joined => $txt->display("not_joined"));
    }

    /**
     *
     */
    function getRegionList()
    {
        $list[0] = $this->txt->display('all_regions');
        $dbRegions = IsicDB::factory('Regions');
        $regions = $dbRegions->getRecordsBySchoolIds($this->allowed_schools);
        foreach ($regions as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    /**
     *
     */
    function getSchoolList($all = true, $onlySchool = 0, $activeOnly = false)
    {
        if ($all) {
            $list[0] = $this->txt->display('all_schools');
        }
        if ($onlySchool) {
            $schools = $this->isicDbSchools->getRecordsByIds($onlySchool);
        } else {
            if ($activeOnly) {
                $schools = $this->isicDbSchools->getActiveRecordsByIds($this->allowed_schools);
            } else {
                $schools = $this->isicDbSchools->getRecordsByIds($this->allowed_schools);
            }
            //$schools = $this->isicDbSchools->listAllowedRecords();
        }

        $isSuperAdmin = $this->isicDbUsers->isCurrentUserSuperAdmin();
        //$isSuperAdmin = false;
        foreach ($schools as $data) {
            if (!$this->isicDbSchools->isEhlRegion($data) && ($isSuperAdmin || !$data['hidden'])) {
                $list[$data['id']] = $data['name'];
            }
        }
        return $list;
    }

    /**
     *
     */
    function getCardTypeList($action = 'view', $all = true, $onlyType = 0)
    {
        $list = array();
        if ($all) {
            $list[0] = $this->txt->display("all_types");
        }
        if ($onlyType) {
            $cardTypeList = $onlyType;
        } else {
            if ($this->user_type == IsicDB_Users::user_type_user) {
                $cardTypeList = $this->allowed_card_types_user;
            } else {
                $cardTypeList = $action == 'view' ? $this->allowed_card_types_view : $this->allowed_card_types_add;
            }
        }
        $cardTypes = $this->isicDbCardTypes->getRecordsByIdsOrderedByPriorityName($cardTypeList);
        foreach ($cardTypes as $data) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    function getCardTypeListBySchool($schoolId)
    {
        if ($this->isicDbSchools->isJoined($schoolId)) {
            if ($this->user_type == IsicDB_Users::user_type_admin) {
                $cardTypeList = $this->isicDbCardTypes->getAllowedIdListForAddBySchool($schoolId);
            } else {
                $cardTypeList = $this->isicDbCardTypes->getAllowedRecordIdsBySchool($schoolId);
            }
        } else {
            $cardTypeList = $this->isicDbCardTypes->getAllowedRecordIdsForNonJoinedSchools();
        }
        /* ordering card types by priority */
        $cardTypes = $this->isicDbCardTypes->getRecordsByIdsOrderedByPriorityName($cardTypeList);
        $cardTypeList = array();
        foreach ($cardTypes as $cardType) {
            $cardTypeList[] = $cardType['id'];
        }
        return $cardTypeList;
    }

    function isAllowedCardType($schoolId, $cardTypeId, $userId)
    {
        $userData = $this->isicDbUsers->getRecord($userId);
        // check data from ehis
        if ($userData['external_status_check_allowed'] && $this->isicDbCardTypes->isExternalCheckNeeded($cardTypeId)) {
            $idList = $this->getEhisUser()->getStatusListByUser($userData['user_code']);
            $this->ehisError = $this->getEhisUser()->getError();
        }

        // check data from EHL
        if ($userData['ehl_status_check_allowed'] && $this->isicDbCardTypes->isEHLCheckNeeded($cardTypeId)) {
            $idList = $this->getEHLClient()->getStatusListByUser($userData['user_code']);
            $this->ehlError = $this->getEHLClient()->getError();
        }

        if ($this->isicDbCardTypes->isAgeRestricted($cardTypeId)) {
            $cardTypeList = array();
            $ageInYears = IsicDate::diffInYears(time(), strtotime($userData['birthday']));
            if ($this->isicDbCardTypes->isAgeInAllowedRange($cardTypeId, $ageInYears)) {
                $cardTypeList[] = $cardTypeId;
            }
        } else {
            $cardTypeList = $this->isicDbCardTypes->getAllowedRecordIdsByUserSchool($userId, $schoolId);
        }
        return in_array($cardTypeId, $cardTypeList);
    }

    /**
     *
     */
    function getCardKindList()
    {
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_kind`.*
            FROM
                `module_isic_card_kind`
            ORDER BY
                `module_isic_card_kind`.`id`
            ');

        $list[0] = $this->txt->display("all_kinds");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    /**
     *
     */
    function getCardLanguageList()
    {
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_language`.*
            FROM
                `module_isic_card_language`
            ORDER BY
                `module_isic_card_language`.`name`
            ');

        $list[0] = $this->txt->display("all_languages");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    /**
     *
     */
    function getApplicationTypeList($all = true)
    {
        $list = array();
        if ($all) {
            $list[0] = $this->txt->display("all_types");
        }
        $r = &$this->db->query('
            SELECT
                `module_isic_application_type`.*
            FROM
                `module_isic_application_type`
            ORDER BY
                `module_isic_application_type`.`name`
            ');

        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    function getApplicationRejectReasonList()
    {
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_application_reject_reason`.*
            FROM
                `module_isic_application_reject_reason`
            ORDER BY
                `module_isic_application_reject_reason`.`name`
            ');

        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }

    function getPaymentMethodList()
    {
        $list = array();
        for ($i = 1; $i <= 2; $i++) {
            $list[$i] = $this->txt->display('payment_method' . $i);
        }
        return $list;
    }


    function getDeliveryList($schoolId, $showHomeDelivery = true, $cardTypeId = 0)
    {
        $list = array();
        $deliveries = $this->isicDbCardDeliveries->getRecordsBySchoolCardType($schoolId, $cardTypeId, $showHomeDelivery);
        foreach ($deliveries as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    /**
     * Displays detail view of a application
     *
     * @param int $application application id
     * @return string html detailview of a application
     */
    function showApplication($application)
    {
        if ($this->checkAccess() == false) return "";
        $action = @$this->vars["action"];
        //$txt = new Text($this->language, $this->translation_module_default);
        $instanceParameters = 'type=showapplication';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_show.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        $data = $this->isicDbApplications->getRecord($application);
        if (!$data) {
            redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=unknown_appl", array("appl_id", "info")));
        }
        if (!$this->isic_common->canViewApplication($data)) {
            redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=view", array("appl_id", "info")));
        }
        if (!$this->isicDbSchools->isHiddenSchool($data['school_id'])) {
            $tpl->addDataItem("SCHOOL.DATA_school_name", $data["school_name"]);
        }
        $tpl->addDataItem("DATA_appl_type_name", $data["appl_type_name"]);
        $tpl->addDataItem("DATA_state_name", $data["state_name"]);
        $tpl->addDataItem("DATA_language_name", $data["language_name"]);
        $tpl->addDataItem("DATA_kind_name", $data["card_kind_name"]);
        $tpl->addDataItem("DATA_bank_name", $data["bank_name"]);
        $tpl->addDataItem("DATA_type_name", $data["type_name"]);
        $tpl->addDataItem("DATA_delivery_name", $data["delivery_name"]);
        $tpl->addDataItem("DATA_person_name_first", $data["person_name_first"]);
        $tpl->addDataItem("DATA_person_name_last", $data["person_name_last"]);
        $tpl->addDataItem("DATA_person_birthday", IsicDate::getDateFormatted($data["person_birthday"]));
        $tpl->addDataItem("DATA_person_number", $data["person_number"]);
        $tpl->addDataItem("DATA_person_email", $data["person_email"]);
        $tpl->addDataItem("DATA_person_phone", $data["person_phone"]);
        $tpl->addDataItem("DATA_person_position", $data["person_position"]);
        $tpl->addDataItem("DATA_person_class", $data["person_class"]);
        $tpl->addDataItem("DATA_person_stru_unit", $data["person_stru_unit"]);
        $tpl->addDataItem("DATA_person_stru_unit2", $data["person_stru_unit2"]);
        $tpl->addDataItem("DATA_person_bankaccount", $data["person_bankaccount"]);
        $tpl->addDataItem("DATA_person_bankaccount_name", $data["person_bankaccount_name"]);
        $tpl->addDataItem("DATA_delivery_addr1", $data["delivery_addr1"]);
        $tpl->addDataItem("DATA_delivery_addr2", $data["delivery_addr2"]);
        $tpl->addDataItem("DATA_delivery_addr3", $data["delivery_addr3"]);
        $tpl->addDataItem("DATA_delivery_addr4", $data["delivery_addr4"]);
        $tpl->addDataItem("DATA_campaign_code", $data["campaign_code"]);
        $tpl->addDataItem("DATA_person_newsletter", $this->txt->display('active' . $data["person_newsletter"]));
        $tpl->addDataItem("DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($data['pic'], 'big'));

        $userData = $this->isicDbUsers->getRecordByCode($data["person_number"]);
        $cost_data = $this->getCostDataForApplication($data);
        $cost_info = $this->formatCostData($cost_data);

        $collPaymentData = $this->isic_payment->getPaymentByApplication($data["id"], 1);
        if ($collPaymentData["payment_sum"] != 0) {
            $confirm_payment_collateral = $this->txt->display("active1");
        } else {
            $confirm_payment_collateral = $cost_data["collateral"]["required"] ? $this->txt->display("active" . $data["confirm_payment_collateral"]) : false;
        }
        if ($confirm_payment_collateral) {
            $tpl->addDataItem("PAYMENT.COLL.DATA_confirm_payment_collateral", $confirm_payment_collateral);
        }
        $costPaymentData = $this->isic_payment->getPaymentByApplication($data["id"], 2);
        if ($costPaymentData["payment_sum"] != 0) {
            $confirm_payment_cost = $this->txt->display("active1");
        } else {
            $confirm_payment_cost = $cost_data["cost"]["required"] ? $this->txt->display("active" . $data["confirm_payment_cost"]) : false;
        }
        if ($confirm_payment_cost) {
            $tpl->addDataItem("PAYMENT.COST.DATA_confirm_payment_cost", $confirm_payment_cost);
        }
        $deliveryPaymentData = $this->isic_payment->getPaymentByApplication($data["id"], 3);
        if ($deliveryPaymentData["payment_sum"] != 0) {
            $confirm_payment_delivery = $this->txt->display("active1");
        } else {
            $confirm_payment_delivery = $cost_data["delivery"]["required"] ? $this->txt->display("active" . $data["confirm_payment_delivery"]) : false;
        }
        if ($confirm_payment_delivery) {
            $tpl->addDataItem("PAYMENT.DELIVERY.DATA_confirm_payment_delivery", $confirm_payment_delivery);
        }

        $t_expiration_date = $data["expiration_date"];
        if ($t_expiration_date == IsicDate::EMPTY_DATE) {
            $last_card = $this->isic_common->getUserLastCard($data["person_number"], $data["school_id"], $data["type_id"]);
            $t_expiration_date = $this->isic_common->getCardExpiration($data["type_id"], $last_card ? $last_card["expiration_date"] : "", true);
        }
        $tpl->addDataItem("DATA_expiration_date", IsicDate::getDateFormatted($t_expiration_date));

        // showing modify button in case of admin-users and card not being exported
        if ($this->isic_common->canModifyApplication($data)) {
            $tpl->addDataItem("MODIFY.MODIFY", $this->txt->display("modify_application"));
            if ($this->user_type == $this->user_type_user) {
                if ($this->isicDbSchools->isHiddenSchool($data['school_id'])) {
                    $url = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user_hidden_school'));
                } else {
                    $url = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_application_add_user'));
                }
                $tpl->addDataItem("MODIFY.URL_MODIFY", $url . "&appl_id=" . $data["id"] . "&action=modify");
            } else {
                $tpl->addDataItem("MODIFY.URL_MODIFY", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&appl_id=" . $data["id"] . "&action=modify", array("appl_id", "info")));
            }
        }

        if ($this->user_type == $this->user_type_admin) {
            if ($confirm_payment_collateral) {
                if ($collPaymentData["payment_sum"] != 0) {
                    $tpl->addDataItem("PAYMENT.COLL.SUM.SUM", $collPaymentData["payment_sum"]);
                    $tpl->addDataItem("PAYMENT.COLL.SUM.CURRENCY", $collPaymentData["currency"]);
                } else {
                    $tpl->addDataItem("PAYMENT.COLL.SUM.SUM", $cost_data["collateral"]["sum"]);
                    $tpl->addDataItem("PAYMENT.COLL.SUM.CURRENCY", $cost_data["currency"]);
                }
                if (isset($collPaymentData["actual_payment_date"]) && $collPaymentData["actual_payment_date"] != IsicDate::EMPTY_DATE) {
                    $tpl->addDataItem("PAYMENT.COLL.ACTUAL_PAYMENT_DATE.DATA", IsicDate::getDateFormatted($collPaymentData["actual_payment_date"]));
                }
            }
            if ($confirm_payment_cost) {
                if ($costPaymentData["payment_sum"] != 0) {
                    $tpl->addDataItem("PAYMENT.COST.SUM.SUM", $costPaymentData["payment_sum"]);
                    $tpl->addDataItem("PAYMENT.COST.SUM.CURRENCY", $costPaymentData["currency"]);
                } else {
                    $tpl->addDataItem("PAYMENT.COST.SUM.SUM", $cost_data["cost"]["sum"]);
                    $tpl->addDataItem("PAYMENT.COST.SUM.CURRENCY", $cost_data["currency"]);
                }
                if (isset($costPaymentData["actual_payment_date"]) && $costPaymentData["actual_payment_date"] != IsicDate::EMPTY_DATE) {
                    $tpl->addDataItem("PAYMENT.COST.ACTUAL_PAYMENT_DATE.DATA", IsicDate::getDateFormatted($costPaymentData["actual_payment_date"]));
                }
            }
            if ($confirm_payment_delivery) {
                if ($deliveryPaymentData["payment_sum"] != 0) {
                    $tpl->addDataItem("PAYMENT.DELIVERY.SUM.SUM", $deliveryPaymentData["payment_sum"]);
                    $tpl->addDataItem("PAYMENT.DELIVERY.SUM.CURRENCY", $deliveryPaymentData["currency"]);
                } else {
                    $tpl->addDataItem("PAYMENT.DELIVERY.SUM.SUM", $cost_data["delivery"]["sum"]);
                    $tpl->addDataItem("PAYMENT.DELIVERY.SUM.CURRENCY", $cost_data["currency"]);
                }
                if (isset($deliveryPaymentData["actual_payment_date"]) && $deliveryPaymentData["actual_payment_date"] != IsicDate::EMPTY_DATE) {
                    $tpl->addDataItem("PAYMENT.DELIVERY.ACTUAL_PAYMENT_DATE.DATA", IsicDate::getDateFormatted($deliveryPaymentData["actual_payment_date"]));
                }
            }

            if ($data["state_id"] != $this->a_state_processed) {
                if ($data["state_id"] != $this->a_state_rejected) {
                    $tpl->addDataItem("REJECT.BUTTON", $this->txt->display("reject"));
                }
                if ($data["state_id"] != $this->a_state_admin_confirm
                    && $this->isic_payment->isApplicationPaymentComplete($data, $cost_data)
                    && validateEmail($data['person_email'])
//                    && $data['pic']
                ) {
                    if ($this->vars['manual_confirm']) {
                        $tpl->addDataItem("CONFIRM_ADMIN_CONFIRM.BUTTON", $this->txt->display("confirm"));
                    } else {
                        $tpl->addDataItem("CONFIRM_ADMIN.BUTTON", $this->txt->display("confirm"));
                    }
                }

                if (!$data["confirm_payment_collateral"] && $cost_data["collateral"]["required"]) {
                    if ($confirm_payment_collateral) {
                        $tpl->addDataItem("PAYMENT.COLL.CONF_PAY_COLL.BUTTON", $this->txt->display("confirm_payment_collateral"));
                    }
                    $payment = $this->isicDbPayments->getFreeCollateralRecordByUserSum(
                        $data['person_number'],
                        $cost_data["collateral"]["sum"],
                        $cost_data["currency"]
                    );
                    if ($payment) {
                        $tpl->addDataItem("PAYMENT.DEPOSIT.AMOUNT", $cost_info['collateral']/*$payment['payment_sum']*/);
                        $tpl->addDataItem("PAYMENT.DEPOSIT.CONF_USE_DEPOSIT.BUTTON", $this->txt->display("use_deposit"));
                    }
                }

                if (!$data["confirm_payment_cost"] && $cost_data["cost"]["required"] && $cost_data["cost"]["sum"] > 0 && $confirm_payment_cost) {
                    $tpl->addDataItem("PAYMENT.COST.CONF_PAY_COST.BUTTON", $this->txt->display("confirm_payment_cost"));
                }
                if (!$data["confirm_payment_delivery"] && $cost_data["delivery"]["required"] && $cost_data["delivery"]["sum"] > 0 && $confirm_payment_delivery) {
                    $tpl->addDataItem("PAYMENT.DELIVERY.CONF_PAY_DELIVERY.BUTTON", $this->txt->display("confirm_payment_delivery"));
                }
            }
        }
        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('confirm_admin_confirm', '');
        $hidden .= IsicForm::getHiddenField('appl_id', $application);
        if ($action == "reject") {
            $hidden .= IsicForm::getHiddenField('confirm_reject', '1');
        }
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("appl_id", "action", "info")));

        if ($this->vars["error"] && $this->error_message[$this->vars["error"]]) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display($this->error_message[$this->vars["error"]]));
        }

        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        if (!$this->vars["bind"]) {
            $tpl->addDataItem("BACK.BACK", $this->txt->display("back"));
            $tpl->addDataItem("BACK.URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("appl_id", "action", "info")));
        }
        return $tpl->parse();
    }

    private function getCostDataForApplication($applData)
    {
        // until application is not processed, calculating cost data dynamically
        if ($applData['state_id'] < $this->isicDbApplications->getStateProcessed()) {
            return $this->isic_payment->getCardCostCollDeliveryData($applData);
        }
        return array(
            'currency' => $applData['currency'],
            'type' => $applData['application_type_id'],
            'collateral' => array(
                'required' => $applData['confirm_payment_collateral'],
                'sum' => $applData['collateral_sum'],
            ),
            'cost' => array(
                'required' => $applData['confirm_payment_cost'],
                'sum' => $applData['cost_sum'],
            ),
            'delivery' => array(
                'required' => $applData['confirm_payment_delivery'],
                'sum' => $applData['delivery_sum'],
            )
        );
    }

    /**
     * Import file in CSV-format with contacts
     *
     * @param string $action action (addmass)
     * @param int $step step
     * @return string html addform for csv-import
     */
    function addApplicationMass($action, $step = 0)
    {
        if ($this->checkAccess() == false) return "";
        if ($this->vars["step"]) {
            $step = $this->vars["step"];
        }
        if ($this->user_type == $this->user_type_user) {
            return $this->isic_common->showErrorMessage("error_csv_import_not_allowed");
        }
//        setlocale(LC_ALL, 'en_US.UTF-8');
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];
        $missing_data_from_profile = @$this->vars["missing_data_from_profile"];
        $missing_data_from_ehis = @$this->vars["missing_data_from_ehis"];
        if (!$step) {
            $step = 0;
        }

        if (!$this->vars["language_id"]) {
            $this->vars["language_id"] = $this->language_default;
        }

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        if (!$this->vars["separator"]) {
            $this->vars["separator"] = $this->csv_import_separator;
        } else {
            $this->vars["separator"] = urldecode($this->vars["separator"]);
        }

        $error = new IsicError();
        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            $error = $this->isValidData($step);

            if (!$error->isError()) {
                if ($action == "addmass") {
                    if (!$step && is_readable($this->vars["datafile"])) {
                        // first converting the whole file into UTF-8
                        IsicEncoding::convertFileEncoding($this->vars["datafile"]);
                    }
                    if ($fp = fopen($this->vars["datafile"], "rb")) {
                        $this->vars["title_row"] = $this->vars["title_row"] ? 1 : 0;
                        $tf = pathinfo($this->vars["datafile"]);
                        $this->vars["data_filename"] = $tf["basename"];
                        if (!$step) {

                            $csv_fields = array();
                            $csv_fields[-1] = "---";
                            if (($data = fgetcsv($fp, 1000, $this->vars["separator"])) !== false) {
                                $num = count($data);

                                for ($c = 0; $c < $num; $c++) {
                                    $t_data = $data[$c];

                                    if ($this->vars["title_row"]) {
                                        $csv_fields[] = $t_data;
                                    } else {
                                        $csv_fields[] = $this->txt->display("column") . " " . ($c + 1);
                                    }
                                }
                            }
                            if ($num < $this->csv_import_min_fields) {
                                $error->add('field_count', true);
                            }
                        } elseif ($step == 1) {
                            // importing data and showing it to user for confirmation
                            $csv_data = array();
                            $i_fields = $this->vars["datafield"];

                            $row = 0;
                            while (($data = fgetcsv($fp, 1000, $this->vars["separator"])) !== false) {
                                $row++;
                                $t_error_import = false;
                                if ($row == 1 && $this->vars["title_row"]) {
                                    continue;
                                }

                                $num = count($data);
                                for ($c = 0; $c < sizeof($this->csv_import_fields); $c++) {
                                    $t_data = "";
                                    if ($i_fields[$c] != -1 && $num >= $i_fields[$c]) {
                                        $t_data = $data[$i_fields[$c]];
                                    }
                                    $csv_data[$row][$this->csv_import_fields[$c]] = $t_data;
                                }

                                // filling all the empty fields with data from user profile
                                if ($missing_data_from_ehis) {
                                    $user_data = $this->getUserDataFromEhis($csv_data[$row]["person_number"],
                                        $this->vars['school_id'], $this->vars['type_id']);
                                } else if ($missing_data_from_profile) {
                                    $user_data = $this->isicDbUsers->getRecordByCode($csv_data[$row]["person_number"]);
                                }
                                if ($user_data) {
                                    foreach ($this->user_appl_match as $ukey => $akey) {
                                        if ($user_data[$ukey] /*&& !$csv_data[$row][$akey]*/) {
                                            $csv_data[$row][$akey] = $user_data[$ukey];
                                        }
                                    }
                                    if ($user_data["pic"] &&
                                        $this->isic_common->isValidPictureAge($user_data['pic'], $this->vars['type_id'], $this->vars['school_id'])
                                    ) {
                                        $csv_data[$row]["pic"] = $user_data["pic"];
                                    }
                                }

                                if (!$csv_data[$row]['person_name_first'] || !$csv_data[$row]['person_name_last']) {
                                    $t_error_import = $error_import = $error_name_empty = true;
                                    $csv_data[$row]["error"]["person_name_empty"] = true;
                                    continue;
                                }

                                $tmpApplData = array(
                                    'person_number' => $csv_data[$row]["person_number"],
                                    'school_id' => $this->vars["school_id"],
                                    'type_id' => $this->vars["type_id"],
                                    'delivery_id' => $this->vars["delivery_id"],
                                );
                                $cost_data = $this->isic_payment->getCardCostCollDeliveryData($tmpApplData);
                                // check if card type was chosen that requires the card to be deactivated first
                                if ($cost_data["cost"]["error"]) {
                                    $t_error_import = $error_import = $error_card_exists = true;
                                    $csv_data[$row]["error"]["card_exists"] = true;
                                    continue;
                                }

                                $t_error_import = $error_import = $error_card_type_exists_order_distr = $this->isic_common->getUserCardTypeExistsOrderedDistributed($csv_data[$row]["person_number"], $this->vars["type_id"]);
                                if ($t_error_import) {
                                    $csv_data[$row]["error"]["card_exists_order_distr"] = true;
                                    continue;
                                }

                                $t_error_import = $error_import = $error_appl_type_exists = $this->isic_common->getUserApplicationTypeExists($csv_data[$row]["person_number"], $this->vars["type_id"], 0);
                                if ($t_error_import) {
                                    $csv_data[$row]["error"]["application_exists"] = true;
                                    continue;
                                }
                            }
                        } elseif ($step == 2) {
                            // Saving data to database
                            $csv_data = $this->vars["csv_data"];
                            $this->vars["csv_data"] = "";
                            for ($c = 0; $c < sizeof($csv_data); $c++) {
                                $t_error_save = false;

                                if ($csv_data[$c]["confirm"]) {
                                    $this->vars['pic'] = '';
                                    foreach ($csv_data[$c] as $t_key => $t_val) {
                                        $this->vars[$t_key] = $t_val;
                                    }

                                    // filling all the empty fields with data from user profile
                                    if ($missing_data_from_profile || $missing_data_from_ehis) {
                                        $user_data = $this->isicDbUsers->getRecordByCode($this->vars["person_number"]);
                                        if ($user_data && $user_data["pic"] &&
                                            $this->isic_common->isValidPictureAge($user_data['pic'], $this->vars['type_id'], $this->vars['school_id'])
                                        ) {
                                            $csv_data[$c]["pic"] = $user_data["pic"];
                                        }
                                    }

                                    if (!$this->vars['person_name_first'] || !$this->vars['person_name_last']) {
                                        $t_error_save = $error_save = $error_name_empty = true;
                                        $csv_data[$c]["error"]["person_name_empty"] = true;
                                        continue;
                                    }

                                    $tmpApplData = array(
                                        'person_number' => $this->vars["person_number"],
                                        'school_id' => $this->vars["school_id"],
                                        'type_id' => $this->vars["type_id"],
                                        'delivery_id' => $this->vars["delivery_id"],
                                    );

                                    // first checking if this user already has the card of the same type that is active currently
                                    $cost_data = $this->isic_payment->getCardCostCollDeliveryData($tmpApplData);
                                    // check if card type was chosen that requires the card to be de-acitvated first
                                    if ($cost_data["cost"]["error"]) {
                                        $t_error_save = $error_save = $error_card_exists = true;
                                        $csv_data[$c]["error"]["card_exists"] = true;
                                        continue;
                                    }

                                    $t_error_save = $error_save = $error_appl_type_exists = $this->isic_common->getUserApplicationTypeExists($this->vars["person_number"], $this->vars["type_id"], 0);
                                    if ($t_error_save) {
                                        $csv_data[$c]["error"]["application_exists"] = true;
                                        continue;
                                    }

                                    $t_error_save = $error_save = $error_card_type_exists_order_distr = $this->isic_common->getUserCardTypeExistsOrderedDistributed($this->vars["person_number"], $this->vars["type_id"]);
                                    if ($t_error_save) {
                                        $csv_data[$c]["error"]["card_exists_order_distr"] = true;
                                        continue;
                                    }

                                    $this->vars["person_birthday"] = IsicDate::calcBirthdayFromNumber($this->vars["person_number"]);

                                    if ($this->vars["person_birthday"]) {
                                        $this->vars['application_type_id'] = $cost_data["type"];
                                        $this->vars['prev_card_id'] = $cost_data["last_card_id"];
                                        $added_id = $this->isicDbApplications->insertRecord($this->vars);

                                        $applRecord = $this->isicDbApplications->getRecord($added_id);
                                        if ($missing_data_from_profile || $missing_data_from_ehis) {
                                            $this->copyUserPictureToApplication($applRecord);
                                        }

                                        $userRecord = $this->getUserDataByApplicationAndAddNewIfNotFound($applRecord);
                                        if ($this->isicDbCardTypes->isEHLCheckNeeded($applRecord['type_id'])) {
                                            $this->enableUserEhlDataCheck($this->isicDbUsers->getRecordByCode($applRecord["person_number"]));
                                            $this->getEHLClient()->getStatusListByUser($applRecord['person_number']);
                                        }
                                        $this->updateStatusInfoFields($applRecord, $userRecord);
                                        // check if free collateral payment exists and assigning it to new created application
                                        $this->isic_payment->setApplicationCollateralPayment($cost_data, $added_id);

                                        $csv_data[$c]["application_created"] = true;
                                    } else {
                                        $error_save = $error_fields = true;
                                        $csv_data[$c]["error"]["person_birthday"] = $this->vars["person_birthday"] ? false : true;
                                    }
                                } else {
                                    $csv_data[$c]["error"]["not_confirmed"] = true;
                                }
                            }
                        }
                        fclose($fp);
                    }
                }

                if (!$error->isError() && !$error_save) {
                    $step++; // increasing step if there were no errors
                }
            }
        }

        // ###################################

        if (!$step) { // importing the csv
            if (!$write) {
                $this->vars["missing_data_from_ehis"] = true;
            }
            $schoolList = $this->getSchoolList(false);
            $schoolId = $this->vars['school_id'] ? $this->vars['school_id'] : array_shift(array_keys($schoolList));
            $this->allowed_card_types_add = $this->isicDbCardTypes->getAllowedIdListForAddBySchool($schoolId);
            $cardTypeId = $this->vars['type_id'] ? $this->vars['type_id'] : current($this->allowed_card_types_add);
            $fields = array(
                "title_row" => array("checkbox", 0, 0, $this->vars["title_row"], "", "", "", true),
                "school_id" => array("select", 0, 0, $this->vars["school_id"], $schoolList, "onChange=\"refreshTypeList();\"", "", false),
                "type_id" => array("select", 0, 0, $this->vars["type_id"], $this->getCardTypeList('add', false), "onChange=\"refreshDeliveryList();\"", "", false),
                "delivery_id" => array("select", 0, 0, $this->vars["delivery_id"], $this->getDeliveryList($schoolId, false, $cardTypeId), "", "", false),
                "datafile" => array("file", 40, 0, $this->vars["datafile"], "", "", "", true),
                "separator" => array("textinput", 1, 0, $this->vars["separator"], "", "", "", true),
                "missing_data_from_ehis" => array("checkbox", 0, 0, $this->vars["missing_data_from_ehis"], "", "", "", true),
                "missing_data_from_profile" => array("checkbox", 0, 0, $this->vars["missing_data_from_profile"], "", "", "", true),
            );
        } elseif ($step == 1) {
            $fields = array();
            $data_fields = array();

            for ($i = 0; $i < sizeof($this->csv_import_fields); $i++) {
                if ((sizeof($csv_fields) - 1) >= $i) {
                    $t_val = $i;
                }
                $data_fields[$i] = array("select", 40, 0, $t_val, $csv_fields, "", "", true);
            }
        }

        $instanceParameters = '&type=addapplications';
        $tpl = $this->isicTemplate->initTemplateInstance($this->getAddMassTemplate($step), $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        // #################################
        if ($error->isError()) {
            $this->showErrorMessage($error, $tpl);
        }

        if ($error_save == true) {
            if ($error_fields) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_fields"));
            } elseif ($error_card_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists"));
            }
        }
        if (is_array($fields) && sizeof($fields)) {
            foreach ($fields as $key => $val) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];
                $fdata["class"] = $val[6];

                if ($action == "addmass" || $action == "modify" && $val[7]) {
                    $f = new AdminFields("$key", $fdata);
                    if ($fdata['type'] == 'file') {
                        $f->setTitleAttr($this->txt->display('browse_file'));
                    }
                    $field_data = $f->display($val[3]);
                    $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);
                } else {
                    if (is_array($val[4])) {
                        $field_data = $val[4][$val[3]];
                    } else {
                        $field_data = $val[3];
                    }
                }
                $tpl->addDataItem("FIELD_$key", $field_data);
                unset($fdata);
            }
        }
        if (is_array($data_fields) && sizeof($data_fields)) {
            foreach ($data_fields as $key => $val) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];
                $fdata["class"] = $val[6];

                $f = new AdminFields("datafield[" . $key . "]", $fdata);
                $field_data = $f->display($val[3]);
                $tpl->addDataItem("FIELDS.TITLE", $this->txt->display($this->csv_import_fields[$key]));
                $tpl->addDataItem("FIELDS.DATA", $field_data);
                unset($fdata);
            }
        }

        // show all the imported rows together with according statuses
        if ($step == 2) {
            $this->showStep2($csv_data, $tpl);
        }

        // show all the imported rows together with according statuses
        if ($step == 3) {
            $this->showStep3($csv_data, $tpl);
        }

        if ($action == "addmass") {
            if (!$step) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_import"));
            } elseif ($step == 1) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_next"));
            } elseif ($step == 2) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_save"));
            }
        }

        $tpl->addDataItem("HIDDEN", $this->getAddMassHiddenFields($action, $step, $missing_data_from_profile, $missing_data_from_ehis));
        $tpl->addDataItem("SELF", $general_url);

        return $tpl->parse();
    }

    private function getUserInsertData($data)
    {
        return array(
            'user_code' => $this->getArrayValueByKeyOrEmptyString($data, 'person_number'),
            'name_first' => $this->getArrayValueByKeyOrEmptyString($data, 'person_name_first'),
            'name_last' => $this->getArrayValueByKeyOrEmptyString($data, 'person_name_last'),
            'email' => $this->getArrayValueByKeyOrEmptyString($data, 'person_email'),
            'phone' => $this->getArrayValueByKeyOrEmptyString($data, 'person_phone'),
            'birthday' => $this->getArrayValueByKeyOrEmptyString($data, 'person_birthday'),
            'delivery_addr1' => $this->getArrayValueByKeyOrEmptyString($data, 'delivery_addr1'),
            'delivery_addr2' => $this->getArrayValueByKeyOrEmptyString($data, 'delivery_addr2'),
            'delivery_addr3' => $this->getArrayValueByKeyOrEmptyString($data, 'delivery_addr3'),
            'delivery_addr4' => $this->getArrayValueByKeyOrEmptyString($data, 'delivery_addr4'),
        );
    }

    private function getArrayValueByKeyOrEmptyString($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return '';
    }

    private function copyUserPictureToApplication($applRecord)
    {
        $applicationId = $applRecord['id'];
        $user_data = $this->isicDbUsers->getRecordByCode($this->vars["person_number"]);
        if ($user_data && $user_data["pic"] &&
            $this->isic_common->isValidPictureAge($user_data['pic'], $applRecord['type_id'], $applRecord['school_id'])
        ) {
            $u_pic_filename = str_replace("_thumb.", ".", $user_data["pic"]);
            $u_pic_filename_thumb = SITE_PATH . str_replace(".", "_thumb.", $u_pic_filename);
            $u_pic_filename = SITE_PATH . $u_pic_filename;

            if (file_exists($u_pic_filename)) {
                $pic_filename = $this->isic_common->a_pic_prefix . str_pad($applicationId, 10, '0', STR_PAD_LEFT);
                @copy($u_pic_filename, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder . '/' . $pic_filename . '.jpg');
                @copy($u_pic_filename_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder . '/' . $pic_filename . '_thumb.jpg');
                $this->vars["pic"] = Filenames::constructPath($pic_filename, 'jpg', "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder);
                $this->isicDbApplications->updateRecord($applicationId, array('pic' => $this->vars['pic']));
            }
        }
    }

    function showErrorMessage($error, $tpl)
    {
        if ($error->isError()) {
            if ($error->get('datafile')) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_datafile"));
            } elseif ($error->get('field_count')) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_csv_field_count"));
            } elseif ($error->get('card_type_not_allowed')) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_type_not_allowed"));
            } elseif ($error->get('too_many_rows')) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_too_many_rows"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error"));
            }
        }
    }

    private function isValidData($step)
    {
        $error = new IsicError();
        if (!$step) {
            $required = array("type_id", "school_id", 'delivery_id');
            $this->vars['delivery_id'] = $this->vars['delivery_id'] ? $this->vars['delivery_id'] : '';
            $error->checkRequired($this->vars, $required);
            if (!$error->isError()) {
                if (!in_array($this->vars['type_id'], $this->getCardTypeListBySchool($this->vars['school_id']))) {
                    $error->add('card_type_not_allowed');
                    $error->addBadField('type_id');
                }
            }
            if (!$error->isError()) {
                $error = $this->setDataFilePath();
            }
        } elseif ($step == 1 || $step == 2) {
            $error->add('can_read', !$this->canReadDataFile($this->vars['data_filename']));
        } elseif ($step == 3) {
            // do nothing
        }
        return $error;
    }

    private function setDataFilePath()
    {
        $this->vars['datafile'] = '';
        $error = new IsicError();

        if (!$_FILES['datafile']['tmp_name'] || !$_FILES['datafile']['size']) {
            $error->add('upload', true);
            return $error;
        }
        $file_info = Filenames::pathinfo($_FILES['datafile']['name']);
        $file_info['extension'] = strtolower($file_info['extension']);

        if (!$this->isAllowedExtension($file_info['extension'])) {
            $error->add('extension', true);
            return $error;
        }

        $data_filename = md5(time());
        // create destination path string.
        $dest = Filenames::constructPath(
            $data_filename, $file_info['extension']
            , SITE_PATH . '/cache'
        );
        // process with file saving.
        $file_uploader = new FileUploader();
        $datafile_saved = $file_uploader->processUploadedFile(
            $_FILES['datafile']['tmp_name'], $dest, null, false);

        if ($datafile_saved === FALSE) {
            $error->add('datafile', $file_uploader->getLastError());
            return $error;
        }

        if (!$this->isAllowedNumberOfRows($dest, $this->vars["missing_data_from_ehis"])) {
            $error->add('too_many_rows', true);
            return $error;
        }

        $this->vars['datafile'] = $dest;

        return $error;
    }

    private function isAllowedExtension($extension)
    {
        return in_array($extension, array('csv', 'txt'));
    }

    private function isAllowedNumberOfRows($path, $missingDataFromEhis)
    {
        $fileLines = file($path);
        if ($fileLines &&
            count($fileLines) > ($missingDataFromEhis ? self::CSV_MAX_ROWS_EHIS : self::CSV_MAX_ROWS_NORMAL)
        ) {
            return false;
        }
        return true;
    }

    private function canReadDataFile($data_filename)
    {
        $this->vars['datafile'] = SITE_PATH . '/cache/' . $data_filename;
        if (is_file($this->vars['datafile']) && file_exists($this->vars['datafile']) && is_readable($this->vars['datafile'])) {
            return true;
        }
        return false;
    }

    private function getAddMassTemplate($step)
    {
        if ($step) {
            return 'module_isic_application_addmass_' . $step . '.html';
        }
        return 'module_isic_application_addmass.html';
    }

    private function showStep2($csv_data, $tpl)
    {
        $fdata = array(
            'type' => 'textinput',
//            'size' => 30,
//            'cols' => 30,
        );
        $fdata_conf = array('type' => 'checkbox');

        $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display("pic"));
        foreach ($this->csv_import_fields as $field_title) {
            $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display($field_title));
        }

        $row = 0;
        foreach ($csv_data as $key => $val) {
            $tpl->addDataItem("ROW.ROW", $row + 1);
            $tpl->addDataItem("ROW.COL.DATA", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($val["pic"], 'thumb')));

            foreach ($this->csv_import_fields as $field_name) {
                $f = new AdminFields("csv_data[" . $row . "][" . $field_name . "]", $fdata);
                $tpl->addDataItem("ROW.COL.DATA", $f->display($val[$field_name]));
            }
            if (is_array($val["error"])) {
                $tpl->addDataItem("ROW.COL.DATA", $this->getErrorTexts($val["error"]));
            } elseif ($val["application_created"]) {
                $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("application_created"));
            } else {
                $f = new AdminFields("csv_data[" . $row . "][confirm]", $fdata_conf);
                $tpl->addDataItem("ROW.COL.DATA", $f->display(1));
            }
            $row++;
        }
    }

    private function showStep3($csv_data, $tpl)
    {
        $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display("pic"));
        foreach ($this->csv_import_fields as $field_title) {
            $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display($field_title));
        }

        $row = 0;
        foreach ($csv_data as $key => $val) {
            $tpl->addDataItem("ROW.ROW", $row + 1);
            $tpl->addDataItem("ROW.COL.DATA", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($val["pic"], 'thumb')));
            foreach ($this->csv_import_fields as $field_name) {
                $tpl->addDataItem("ROW.COL.DATA", $val[$field_name]);
            }

            if (is_array($val["error"])) {
                $tpl->addDataItem("ROW.COL.DATA", $this->getErrorTexts($val["error"]));
            } elseif ($val["application_created"]) {
                $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("application_created"));
            } else {
                $tpl->addDataItem("ROW.COL.DATA", "-");
            }
            $row++;
        }
    }

    function getErrorTexts($error)
    {
        $err_txt = array();
        foreach ($error as $err_key => $err_val) {
            if ($err_val) {
                $err_txt[] = $this->txt->display("modify_error_" . $err_key);
            }
        }
        return implode("<br />", $err_txt);
    }

    private function getAddMassHiddenFields($action, $step, $missing_data_from_profile, $missing_data_from_ehis)
    {
        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('step', $step);
        $hidden .= IsicForm::getHiddenField('separator', $this->vars['separator']);

        if ($this->vars["data_filename"]) {
            $hidden .= IsicForm::getHiddenField('data_filename', $this->vars['data_filename']);
        }
        if ($this->vars["language_id"]) {
            $hidden .= IsicForm::getHiddenField('language_id', $this->vars['language_id']);
        }
        if ($this->vars["kind_id"]) {
            $hidden .= IsicForm::getHiddenField('kind_id', $this->vars['kind_id']);
        }
        if ($this->vars["type_id"] && $step) {
            $hidden .= IsicForm::getHiddenField('type_id', $this->vars['type_id']);
        }
        if ($this->vars["delivery_id"] && $step) {
            $hidden .= IsicForm::getHiddenField('delivery_id', $this->vars['delivery_id']);
        }
        if ($this->vars["school_id"] && $step) {
            $hidden .= IsicForm::getHiddenField('school_id', $this->vars['school_id']);
        }
        if ($this->vars["bank_id"]) {
            $hidden .= IsicForm::getHiddenField('bank_id', $this->vars['bank_id']);
        }
        if ($this->vars["title_row"]) {
            $hidden .= IsicForm::getHiddenField('title_row', $this->vars['title_row']);
        }
        if ($step && $missing_data_from_profile) {
            $hidden .= IsicForm::getHiddenField('missing_data_from_profile', 1);
        }

        if ($step && $missing_data_from_ehis) {
            $hidden .= IsicForm::getHiddenField('missing_data_from_ehis', 1);
        }
        return $hidden;
    }

    /**
     * Displays add/modify view of a application
     *
     * @param int $application application id
     * @param string $action action (add/modify)
     * @return string html addform for applications
     */
    function addApplication($application, $action)
    {
        if ($this->checkAccess() == false) return "";
        switch ($this->user_type) {
            case $this->user_type_admin:
                return $this->addApplicationAdmin($application, $action);
                break;
            case $this->user_type_user:
                return $this->addApplicationUser($application, $action);
                break;
            default :
                return false;
                break;
        }
    }

    /**
     * Displays appliction steps
     *
     * @param int $application application id
     * @param int $step current active step
     * @param int $user_step last available step for user
     * @param string $step_url url
     * @param string $action action (add/modify)
     * @param int $minStep - first step
     * @return string html steps for applications
     */
    function showApplicationSteps($application = 0, $step = 0, $user_step = 0, $step_url = "", $action = 'add', $minStep = 1)
    {
        //$txt = new Text($this->language, $this->translation_module_default);

        // instantiate template class
        $instanceParameters = 'type=addapplicationuser';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_user_steps.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        for ($i = $minStep; $i <= $this->max_steps; $i++) {
            $step_name = '<span>' . $this->txt->display("step" . $i) . '</span>';
            if ($i == $step) {
                $step_name = '<span class="active">' . $step_name . '</span>';
            } elseif ($i <= $user_step) {
                $step_name = "<a href=\"" . $step_url . "&appl_id=" . $application . "&step=" . $i . "&action=" . $action . "\">" . $step_name . "</a>";
            } else {
                $step_name = '<span>' . $step_name . '</span>';
            }
            $tpl->addDataItem("STEP.NAME", $step_name);
        }

        return $tpl->parse();
    }

    /**
     * Displays add/modify view of a application for regular user
     *
     * @param int $application application id
     * @param string $action action (add/modify)
     * @param int $step step user want's to go to
     * @param int $onlyHiddenSchool shows if only hidden/non-hidden school applications should be handled
     * @return string html addform for applications
     */
    function addApplicationUser($application = 0, $action = 'add', $step = 0, $onlyHiddenSchool = 0)
    {
        if ($this->checkAccess() == false) {
            return "";
        }

        $step = (int)$this->vars["step"];
        if (!$step) {
            $step = 100;
        }

        $firstStep = 1;
        $hiddenSchoolId = $this->isicDbSchools->getHiddenSchoolId();
        if ($onlyHiddenSchool) {
            $this->vars['school_id'] = $hiddenSchoolId;
            $firstStep = 2;
            if ($step < $firstStep) {
                $step = $firstStep;
            }
        }

        switch ($this->vars["action"]) {
            case "add":
            case "modify":
                $action = $this->vars["action"];
                break;
            default :
                $action = "add";
                break;
        }
        $application = (int)$this->vars["appl_id"];
        if (!$application) {
            if ($application = $this->isic_common->getUserApplication($this->user_code, $onlyHiddenSchool, $hiddenSchoolId)) {
                $action = "modify";
            } else {
                $action = "add";
            }
        }

        $content = @$this->vars["content"];
        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }
        $general_url_list = $this->isic_common->getGeneralUrlByTemplate($this->isic_common->template_application_list); // list-template

        $write = @$this->vars["write"];
        $prev_step = @$this->vars["prev_step"];
        $next_step = @$this->vars["next_step"];
        $discontinue = @$this->vars["discontinue"];
        $cancel = @$this->vars["cancel"];
        $delete = @$this->vars["adelete"];
        $info_message = @$this->vars["info"];

        //$txt = new Text($this->language, $this->translation_module_default);
        $check_data = $this->isicDbApplications->getRecord($application);
        if ($check_data) {
            if (!$this->isic_common->canModifyApplication($check_data)) {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=modify", array("appl_id", "action")));
            }
            // check if user's last step is less than wanted step
            if ($check_data["user_step"] < $step) {
                $step = $check_data["user_step"] ? $check_data["user_step"] : $firstStep;
                $write = false;
            }
        } else {
            $step = $firstStep;
            $action = "add";
            if ($onlyHiddenSchool && $hiddenSchoolId) {
                $write = true;
            }
        }

        // current user will be determined by the application person number
        // this way if the application is ordered for child, $user_data will be correct
        if (!$check_data || !$check_data['person_number'] || $check_data['person_number'] == $this->user_code) {
            $applUserId = $this->userid;
            $user_data = $this->isicDbUsers->getRecord($applUserId);
        } else {
            $user_data = $this->isicDbUsers->getRecordByCode($check_data['person_number']);
            $applUserId = $user_data['user'];
        }

        // ###################################
        // WRITE TO DB
        if ($write) {
            $error = false;
            $cur_step = $step;
            $bad_fields = array();

            if ($action == "modify" && $application && $check_data) {
                // check has the entry been renewed
            } else if ($action == "add") {
                // do nothing really
            } else {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "", array("appl_id", "action")));
            }

            if ($action == "add") {
                if (!$this->vars["type_id"]) {
                    $this->vars["type_id"] = 0;
                }
                $required = array("school_id");
                $error = $this->checkRequired($required, $bad_fields);
                if (!$error) {
                    $tmp_check_data = array(
                        'person_number' => $this->user_code,
                        'type_id' => $this->vars["type_id"],
                        'school_id' => $this->vars["school_id"],
                    );
                    $cost_data = $this->isic_payment->getCardCostCollDeliveryData($tmp_check_data);
                    $applData = array(
                        'application_type_id' => $cost_data["type"],
                        'prev_card_id' => $cost_data["last_card_id"],
                        'type_id' => $this->vars["type_id"],
                        'school_id' => $this->vars["school_id"],
                        'user_step' => 2,
                        'person_number' => $this->user_code,
                    );
                    $application = $this->isicDbApplications->insertRecord($applData);
                    $action = "modify";
                    //$info_message = "application_added";
                }
            } elseif ($action == "modify") {
                switch ($cur_step) {
                    case 1: // school
                        $required = array("school_id");
                        $error = $this->checkRequired($required, $bad_fields);
                        if (!$error) {
                            // if some money already payed then not allowing school change
                            if ($this->isPaymentConfirmed($check_data)) {
                                $this->vars["school_id"] = $check_data["school_id"];
                            }
                            $cur_step++;
                            $applData = array(
                                'confirm_user' => 0,
                                'school_id' => $this->vars['school_id'],
                                'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step)
                            );
                            $this->isicDbApplications->updateRecord($application, $applData);
                        }
                        break;
                    case 2: // card type
                        $orderForOthers = false;
                        $required = array('type_id', 'delivery_id');
                        if ($this->isicDbCardTypes->isOrderForOthersAllowed($this->vars['type_id']) &&
                            $this->vars['order_for_others']
                        ) {
                            $this->vars['person_number'] = $this->vars['person_number_child'];
                            $required[] = 'person_number';
                            $orderForOthers = true;
                        } else {
                            $applUserId = $this->userid;
                        }

                        $this->vars['delivery_id'] = $this->vars['delivery_id'] ? $this->vars['delivery_id'] : '';
                        $error = $this->checkRequired($required, $bad_fields);

                        if (!$error && $orderForOthers) {
                            // person number validity check
                            $error = $error_valid_person_number = !IsicPersonNumberValidator::isValid($this->vars['person_number']);
                            // age checks for current user and child
                            if (!$error) {
                                $ageCurrentUser = IsicDate::getAgeInYears($this->isicDbUsers->getUserBirthday($this->userid));
                                $error = $error_parent_age = $ageCurrentUser < CHILD_MAX_AGE;
                            }
                            $userBirthday = IsicDate::calcBirthdayFromNumber($this->vars['person_number']);
                            if (!$error) {
                                $ageApplUser = IsicDate::getAgeInYears($userBirthday);
                                $error = $error_child_age = $ageApplUser >= CHILD_MAX_AGE;
                            }

                            // child user existence check and record creation if needed
                            if (!$error) {
                                if (!array_key_exists('external_status_check_allowed', $this->vars)) {
                                    $this->vars['external_status_check_allowed'] = $user_data['external_status_check_allowed'];
                                }

                                if (!array_key_exists('ehl_status_check_allowed', $this->vars)) {
                                    $this->vars['ehl_status_check_allowed'] = $user_data['ehl_status_check_allowed'];
                                }

                                $user_data = $this->getUserDataByApplicationAndAddNewIfNotFound(array(
                                    'person_number' => $this->vars['person_number'],
                                    'person_birthday' => $userBirthday,
                                ));
                                // application vs. current user assigning
                                $applUserId = $user_data['user'];
                                $check_data['person_number'] = $user_data['user_code'];
                            }
                        }

                        if ($this->isicDbCardTypes->isExternalCheckNeeded($this->vars['type_id']) &&
                            $this->vars['external_status_check_allowed']
                        ) {
                            $this->isicDbUsers->updateRecord($applUserId, array('external_status_check_allowed' => 1));
                        }

                        if ($this->isicDbCardTypes->isEhlCheckNeeded($this->vars['type_id']) &&
                            $this->vars['ehl_status_check_allowed']
                        ) {
                            $this->isicDbUsers->updateRecord($applUserId, array('ehl_status_check_allowed' => 1));
                        }

                        $user_data = $this->isicDbUsers->getRecord($applUserId);

                        if (!$error) {
                            $error = $error_external_status_check_needed =
                                $this->isicDbCardTypes->isExternalCheckNeeded($this->vars['type_id']) &&
                                !$user_data['external_status_check_allowed'];
                        }

//                        if (!$error) {
//                            $error = $error_ehl_status_check_needed =
//                                $this->isicDbCardTypes->isEhlCheckNeeded($this->vars['type_id']) &&
//                                !$user_data['ehl_status_check_allowed'];
//                        }

                        if (!$error) {
                            $error = $error_card_type_allowed =
                                !$this->isAllowedCardType($check_data['school_id'], $this->vars['type_id'], $applUserId);
                            if ($this->ehisError->isError()) {
                                $error = $error_ehis_query = true;
                            } else {
                                $userDataEhis = $this->getUserDataFromParsedResult(null, $check_data['school_id'],
                                    $this->vars['type_id']
                                );
                            }
                        }

                        if (!$error) {
                            $tmpApplData = array(
                                'person_number' => $check_data["person_number"],
                                'school_id' => $check_data["school_id"],
                                'type_id' => $this->vars['type_id'],
                                'delivery_id' => $this->vars['delivery_id'],
                            );
                            $cost_data = $this->isic_payment->getCardCostCollDeliveryData($tmpApplData);
                            // check if card type was chosen that requires the card to be de-acitvated first
                            if ($cost_data["cost"]["error"]) {
                                $error = $error_cost = true;
                                $bad_fields[] = "type_id";
                            }
                        }

                        if (!$error) {
                            $error = $error_appl_type_exists =
                                $this->isic_common->getUserApplicationTypeExists($check_data["person_number"],
                                    $this->vars["type_id"], $application);
                            if ($error) {
                                $bad_fields[] = "type_id";
                            }
                        }

                        if (!$error) {
                            $error = $error_card_type_exists =
                                $this->isic_common->getUserCardTypeExistsOrderedDistributed(
                                    $check_data["person_number"], $this->vars["type_id"]);
                            if ($error) {
                                $bad_fields[] = "type_id";
                            }
                        }

                        if (!$error) {
                            // if some money has already payed then not allowing type change
                            if ($this->isPaymentConfirmed($check_data)) {
                                $this->vars["type_id"] = $check_data["type_id"];
                            }
                            $cur_step++;
                            $applData = array(
                                'application_type_id' => $cost_data['type'],
                                'prev_card_id' => $cost_data['last_card_id'],
                                'confirm_user' => 0,
                                'type_id' => $this->vars['type_id'],
                                'delivery_id' => $this->vars['delivery_id'],
                                'campaign_code' => $this->vars['campaign_code'],
                                'person_number' => $user_data['user_code'],
                                'person_birthday' => $user_data['birthday'],
                                'order_for_others' => $orderForOthers,
                                'parent_user_id' => $orderForOthers ? $this->userid : 0,
                                'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step)
                            );
                            if ($userDataEhis) {
                                $applData['person_name_first'] = $userDataEhis['name_first'];
                                $applData['person_name_last'] = $userDataEhis['name_last'];
                                $applData['person_class'] = $userDataEhis['class'];
                            }
                            $this->isicDbApplications->updateRecord($application, $applData);

                            // check if free collateral payment exists and assigning it to new created application
                            $this->isic_payment->setApplicationCollateralPayment($cost_data, $application);
                        }
                        break;
                    case 3: // person data
                        $required = array("person_name_first", "person_name_last", "person_birthday");
                        // check if collateral is required, then setting bank-account as required field
                        if ($this->isic_payment->getCardCollateralRequired($check_data["school_id"], $check_data["type_id"])) {
                            $required[] = "person_bankaccount";
                            $required[] = "person_bankaccount_name";
                        }
                        if ($this->isicDbCardDeliveries->isDeliverable($check_data['delivery_id'])) {
                            $required[] = "delivery_addr1";
                            $required[] = "delivery_addr2";
                            $required[] = "delivery_addr3";
                            $required[] = "delivery_addr4";
                            $this->vars['delivery_addr3'] = self::DEFAULT_COUNTRY;
                        }
                        if ($this->isicDbCardTypes->isPersonEmailRequired($check_data['type_id'])) {
                            $required[] = 'person_email';
                        }
                        $error = $this->checkRequired($required, $bad_fields);
                        if (!$error && $this->vars['person_email']) {
                            $error = $error_email = !validateEmail($this->vars["person_email"]);
                            if ($error) {
                                $bad_fields[] = "person_email";
                            }
                        }
                        if (!$error) {
                            $t_birthday = IsicDate::getDateFormattedFromEuroToDb($this->vars["person_birthday"]);
                            $t_bd_time = strtotime($t_birthday);
                            if ($t_bd_time == -1 || $t_bd_time == false) {
                                $error = true;
                            }
                            if (IsicPersonNumberValidator::isValid($check_data['person_number']) &&
                                $t_birthday != IsicDate::calcBirthdayFromNumber($check_data['person_number'])
                            ) {
                                $error = $error_birthday_invalid = true;
                            }
                        }
                        if (!$error) {
                            $cur_step++;
                            $applData = array(
                                'confirm_user' => 0,
                                'person_name_first' => $this->vars["person_name_first"],
                                'person_name_last' => $this->vars["person_name_last"],
                                'person_birthday' => $t_birthday,
                                'person_email' => $this->vars["person_email"],
                                'person_phone' => $this->vars["person_phone"],
                                'person_position' => $this->vars["person_position"],
                                'person_class' => $this->vars["person_class"],
                                'person_stru_unit' => $this->vars["person_stru_unit"],
                                'person_stru_unit2' => $this->vars["person_stru_unit2"],
                                'person_bankaccount' => $this->vars["person_bankaccount"],
                                'person_bankaccount_name' => $this->vars["person_bankaccount_name"],
                                'delivery_addr1' => $this->vars["delivery_addr1"],
                                'delivery_addr2' => $this->vars["delivery_addr2"],
                                'delivery_addr3' => $this->vars["delivery_addr3"],
                                'delivery_addr4' => $this->vars["delivery_addr4"],
                                'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step),
                            );
                            $this->isicDbApplications->updateRecord($application, $applData);
                            // only updating profile data if user is application owner (not ordering for others)
                            if (!$check_data['order_for_others']) {
                                $this->isicDbUsers->updateRecordFromApplication($user_data["user"], $this->isicDbApplications->getRecord($application));
                            }
                        }
                        break;
                    case 4: // picture
                        // if user wants to resize the same picture again, then assigning according variables
                        $imageUploader = new IsicImageUploader('application');
                        if ($this->vars["pic_resize_again"]) {
                            $pic_resize_required = true;
                            $pic_filename = $this->vars["pic_name"];
                            $tmp_pic = Filenames::constructPath($pic_filename, 'jpg', SITE_URL . '/' . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder_tmp);
                            $tmp_pic_path = Filenames::constructPath($pic_filename, 'jpg', SITE_PATH . '/' . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder_tmp);
                            $imageUploader->getImageSize($tmp_pic_path);
                            $this->pictureUploader['width'] = $imageUploader->getPicWidth();
                            $this->pictureUploader['height'] = $imageUploader->getPicHeight();
                        } else { // otherwise standard picture handling method
                            $this->pictureUploader = $pic_data = $imageUploader->handlePictureUpload($application);
                            $this->vars["pic"] = $pic_data["pic_vars"];
                            $pic_filename = $pic_data["pic_filename"];
                            $pic_resize_required = $pic_data["pic_resize_required"];
                            $tmp_pic = $pic_data["tmp_pic"];
                            $error = $pic_data["error"];
                            $error_pic = $pic_data["error_pic"];
                            $error_pic_save = $pic_data["error_pic_save"];
                            $error_pic_size = $pic_data["error_pic_size"];
                            $error_pic_resize = $pic_data["error_pic_resize"];
                            $error_pic_format = $pic_data["error_pic_format"];
                        }

                        if (!$pic_resize_required && !$error) {
                            $required = array("pic");
                            $error_req = $this->checkRequired($required, $bad_fields);

                            if ($error_req) {
                                if ($this->vars["use_user_pic"] && $user_data["pic"]) {
                                    $u_pic_filename = str_replace("_thumb.", ".", $user_data["pic"]);
                                    $u_pic_filename_thumb = SITE_PATH . str_replace(".", "_thumb.", $u_pic_filename);
                                    $u_pic_filename = SITE_PATH . $u_pic_filename;

                                    if (file_exists($u_pic_filename)) {
                                        $pic_filename = $this->isic_common->a_pic_prefix . str_pad($application, 10, '0', STR_PAD_LEFT);

                                        @copy($u_pic_filename, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder . '/' . $pic_filename . '.jpg');
                                        @copy($u_pic_filename_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder . '/' . $pic_filename . '_thumb.jpg');
                                        $this->vars["pic"] = Filenames::constructPath($pic_filename, 'jpg', "/" . $GLOBALS["directory"]["upload"] . $this->isic_common->a_pic_folder);
                                    }
                                    $error_req = $this->checkRequired($required, $bad_fields);
                                } elseif ($check_data["pic"]) {
                                    $error_req = false;
                                    $bad_fields = array();
                                    $this->vars["pic"] = $check_data["pic"];
                                    $pic_already_exists = true;
                                } else {
                                    $error = true;
                                }
                            }

                            if (!$error_req) {
                                $cur_step++;
                                $applData = array(
                                    'confirm_user' => 0,
                                    'pic' => $this->vars["pic"],
                                    'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step)
                                );
                                $this->isicDbApplications->updateRecord($application, $applData);

                                // if we are not using pic from user-table then asking if user wants to update pic
                                if (!$this->vars["use_user_pic"] && !$pic_already_exists && !$check_data['order_for_others']) {
                                    $error = $error_user_update = !($this->isic_common->updateUserPic($user_data["user"], $application));
                                }
                            }
                        }
                        break;
                    case 5: // conditions
                        $required = array("agree_user");
                        $error = $this->checkRequired($required, $bad_fields);
                        if (!$error) {
                            $cur_step++;

                            $applData = array(
                                'confirm_user' => 0,
                                'agree_user' => $this->vars["agree_user"] ? 1 : 0,
                                'person_newsletter' => $this->vars['person_newsletter'] ? 1 : 0,
                                'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step),
                            );
                            $this->isicDbApplications->updateRecord($application, $applData);
                            // updating newsletter settings for the card user
                            if ($this->vars['person_newsletter']) {
                                $this->updateSpecialOffers($check_data, $user_data);
                                // in case of parent ordering for child
                                if ($user_data['user'] != $this->userid) {
                                    $this->updateSpecialOffers($check_data, $this->isicDbUsers->getRecord($this->userid));
                                }
                            }
                        }
                        break;
                    case 6: // confirmation
                        $error = $error_school_not_active = !$this->isicDbSchools->isActive($check_data["school_id"]);
                        if (!$error) {
                            $required = array("confirm_user");
                            $error = $this->checkRequired($required, $bad_fields);
                            if (!$error) {
                                $cur_step++;

                                $t_cost_data = $this->isic_payment->getCardCostCollDeliveryData($check_data);
                                // if everything is payed also, then not going to payment step at all
                                if ($this->isic_payment->isApplicationPaymentComplete($check_data, $t_cost_data)) {
                                    $cur_step++;
                                    $t_state_id = $this->a_state_user_confirm;
                                    $t_request_date = date("Y-m-d H:i:s");
                                    $step++;
                                }

                                $applData = array(
                                    'application_type_id' => $t_cost_data["type"],
                                    'state_id' => $t_state_id ? $t_state_id : $check_data["state_id"],
                                    'confirm_user' => $this->vars["confirm_user"] ? 1 : 0,
                                    'user_step' => $this->getNextUserStep($check_data['user_step'], $cur_step),
                                    'user_request_date' => $t_request_date ? $t_request_date : $check_data["user_request_date"],
                                );
                                $this->isicDbApplications->updateRecord($application, $applData);
                                $applData = $this->isicDbApplications->getRecord($application);
                                $this->isicDbApplications->sendConfirmNotificationToAdmin($applData);
                            }
                        }
                        break;
                    case 7: // payment
                        break;
                    default :
                        // all steps are done, user can't change anything any more
                        break;
                }
            }
        }

        if ($action == "modify") {
            $row_data = $this->isicDbApplications->getRecord($application);
            if (!$this->isic_common->canModifyApplication($row_data)) {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=modify", array("appl_id", "action")));
            }
        }

        if (!$error && !$pic_resize_required) {
            if ($next_step) {
                if ($row_data["user_step"] - 1 >= $step) {
                    $step++;
                }
            } elseif ($prev_step) {
                if ($step > 1) {
                    $step--;
                }
            } elseif ($discontinue) {
                // redirecting user to list-view
                if ($delete) {
                    $this->deleteApplication($application, true);
                }
                redirect($general_url_list . "&info=discontinue");
            } elseif ($cancel) {
                // deleting current application and redirecting user to list-view
                //$this->deleteApplication($application);
            }
        }

        if ($action == "modify" && $row_data["type_id"]) {
            $t_cost_data = $this->isic_payment->getCardCostCollDeliveryData($row_data);
            $t_cost_info = $this->formatCostData($t_cost_data);
        }

        // ###################################

        $instanceParameters = '&type=addapplicationuser';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_application_user_step' . $step . '.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        if ($error == true) {
//            if ($error_card_exists) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists"));
//            } elseif ($error_card_exists_prolong) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists_prolong"));
//            } elseif ($error_card_exists_replace) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists_replace"));
//            } elseif ($error_prolong_not_allowed) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_prolong_not_allowed"));
//            } elseif ($error_isic_number) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_isic_number"));
//            } elseif ($error_status_required) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_status_required"));

            if ($error_pic_resize) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_resize"));
            } elseif ($error_pic_save) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_save"));
            } elseif ($error_pic_size) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_size"));
            } elseif ($error_pic_format) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_format"));
            } elseif ($error_user_update) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_user_update"));
            } elseif ($error_cost) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_deactivation_required"));
            } elseif ($error_email) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_email"));
            } elseif ($error_appl_type_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_appl_type_exists"));
            } elseif ($error_card_type_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_type_exists"));
            } elseif ($error_ehis_query) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_ehis_query"));
            } elseif ($error_card_type_allowed) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_type_allowed"));
            } elseif ($error_external_status_check_needed) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_external_status_check_needed"));
            } elseif ($error_ehl_status_check_needed) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_ehl_status_check_needed"));
            } elseif ($error_school_not_active) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_school_not_active"));
            } elseif ($error_valid_person_number) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_invalid_person_number"));
            } elseif ($error_parent_age) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_parent_age"));
            } elseif ($error_child_age) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_child_age"));
            } elseif ($error_birthday_invalid) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_birthday_invalid"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_required"));
            }
        } elseif ($info_message && $this->info_message[$info_message]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$info_message]));
        } else if ($this->vars['error'] && $this->error_message[$this->var['error']]) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display($this->error_message[$this->var['error']]));
        }

        switch ($step) {
            case 1: // school
                if ($this->isPaymentConfirmed($row_data)) {
                    $onlySchool = $row_data['school_id'];
                }
                if (empty($this->vars["school_id"])) {
                    $selectedSchool = $row_data['school_id'];
                } else {
                    $selectedSchool = $this->vars["school_id"];
                }
                $options = array();
                foreach ($this->isicDbSchools->getAllActiveRecords() as $school) {
                    $options[] = array(
                        'value' => $school['id'],
                        'text' => $school['name']
                    );
                }
                $tpl->addDataItem('ALL_SCHOOLS.OPTIONS', json_encode($options));
                $fields = array(
                    "school_id" => array("select", 0, 0, $selectedSchool, $this->getSchoolList(false, $onlySchool, true), "", "", true, true),
                );
                break;
            case 2: // type
                if ($this->isPaymentConfirmed($row_data)) {
                    $this->allowed_card_types_user = array($row_data['type_id']);
                } else {
                    $this->allowed_card_types_user = $this->getCardTypeListBySchool($row_data['school_id']);
                }
                if (!$row_data['type_id']) {
                    $row_data['type_id'] = $this->vars['type_id'] ? $this->vars['type_id'] : $this->allowed_card_types_user[0];
                } else {
                    if ($this->vars['type_id']) {
                        $row_data['type_id'] = $this->vars['type_id'];
                    }
                }

                $fields = array(
                    'type_id' => array("select", 0, 0, $this->vars["type_id"], $this->getCardTypeList('add', false), "onChange=\"changeCardTypeInfo(this.value);\"", "", true, true),
                    'delivery_id' => array("select", 0, 0, $this->vars["delivery_id"], $this->getDeliveryList($row_data['school_id'], true, $row_data['type_id']), "onChange=\"changeCardDeliveryInfo(this.value);\"", "", true, true),
                    'campaign_code' => array("textinput", 40, 0, $this->vars["campaign_code"], "", "", "", true, false),
                    'order_for_others' => array("checkbox", 0, 0, $this->vars["order_for_others"], "", "onClick=\"toggleDivVisibility('person_number_row', this.checked);\"", "", true, true, 'order_for_others_help'),
                    'person_number_child' => array('textinput', 40, 0, $this->vars['person_number_child'], "", "", "", true, true),
                );
                if (!$user_data['external_status_check_allowed']) {
//                    $row_data['external_status_check_allowed'] = 1;
                    $fields['external_status_check_allowed'] = array("checkbox", 0, 0, $this->vars["external_status_check_allowed"], "", "", "", true, true);
                }
                if (!$user_data['ehl_status_check_allowed']) {
//                    $row_data['ehl_status_check_allowed'] = 1;
                    $fields['ehl_status_check_allowed'] = array("checkbox", 0, 0, $this->vars["ehl_status_check_allowed"], "", "", "", true, true);
                }
                if ($row_data['person_number'] == $this->user_code) {
                    $row_data['person_number_child'] = '';
                } else {
                    $row_data['person_number_child'] = $row_data['person_number'];
                }
                break;
            case 3: // person data
                $row_data["person_birthday"] = IsicDate::getDateFormatted($row_data["person_birthday"]);
                $tBirthday = $this->vars["person_birthday"] ? $this->vars["person_birthday"] : $row_data["person_birthday"];
                $fields = array(
                    "person_name_first" => array("textinput", 40, 0, $this->vars["person_name_first"], "", "", "", true, true),
                    "person_name_last" => array("textinput", 40, 0, $this->vars["person_name_last"], "", "onblur=\"generateBankAccountName();\"", "", true, true),
                    "person_number" => array("textinput", 40, 0, $row_data["person_number"], "", "", "", false, true),
                    "person_birthday" => array("textinput", 40, 0, $tBirthday, "", "", "datePicker", true, true),
                    "person_email" => array("textinput", 40, 0, $this->vars["person_email"], "", "", "", true, true),
                    "person_phone" => array("textinput", 40, 0, $this->vars["person_phone"], "", "", "", true, true, "phone_help"),
                    "person_position" => array("textinput", 40, 0, $this->vars["person_position"], "", "", "", true, false),
//                    "person_class" => array("textinput", 40, 0, $this->vars["person_class"], "", "", "", true, false),
                    "person_stru_unit" => array("textinput", 40, 0, $this->vars["person_stru_unit"], "", "", "", true, false),
                    "person_stru_unit2" => array("textinput", 40, 0, $this->vars["person_stru_unit2"], "", "", "", true, false),
                    "person_bankaccount" => array("textinput", 40, 0, $this->vars["person_bankaccount"], "", "", "", true, true, "bankaccount_help"),
                    "person_bankaccount_name" => array("textinput", 40, 0, $this->vars["person_bankaccount_name"], "", "", "", true, true),
                );

                if ($this->isicDbCardDeliveries->isDeliverable($row_data['delivery_id'])) {
                    $fieldsDelivery = array(
                        "delivery_addr" => array("button", 0, 0, $this->txt->display("copy_delivery_addr"), "", "onClick=\"copyDeliveryAddr();\"", "", true, true),
                        "delivery_addr1" => array("textinput", 40, 0, $this->vars["delivery_addr1"], "", "", "", true, true, "delivery_addr1_help"),
                        "delivery_addr2" => array("textinput", 40, 0, $this->vars["delivery_addr2"], "", "", "", true, true),
                        "delivery_addr3" => array("textinput", 40, 0, $this->vars["delivery_addr3"], "", "", "", false, true),
                        "delivery_addr4" => array("textinput", 40, 0, $this->vars["delivery_addr4"], "", "", "", true, true),
                    );
                    $fields = array_merge($fields, $fieldsDelivery);
                    $row_data['delivery_addr3'] = self::DEFAULT_COUNTRY;
                }

                // if it's the first time user is in this step then assigning row_data values with user_data values
                if (is_array($row_data) && is_array($user_data) && ($row_data["user_step"] == $step) && !$error) {
                    foreach ($fields as $akey => $aval) {
                        $ukey = str_replace("person_", "", $akey);
                        if (array_key_exists($ukey, $user_data) && !$row_data[$akey] && $user_data[$ukey]) {
                            $row_data[$akey] = $user_data[$ukey];
                        }
                    }
                }
                break;
            case 4:
                $fields = array(
                    "pic" => array("file", 43, 0, $this->vars["pic"], "", "onchange='alert(1)'", "", true, true, ""),
                );
                break;
            case 5:
                $fields = array(
                    "agree_user" => array("checkbox", 0, 0, $this->vars["agree_user"], "", "", "", true, true),
                    "person_newsletter" => array("checkbox", 0, 0, $this->vars["person_newsletter"], "", "", "", true, true),
                );
                break;
            case 6:
                $fields = array();
                break;
            case 7:
                $fields = array(
                    "confirm_payment" => array("checkbox", 0, 0, $this->vars["confirm_payment"], "", "", "", true, true, "collateral_help"),
                );
                break;
            default :
                $fields = array();
                break;
        }
        $required_fields = array("school_id", "type_id", 'delivery_id', "person_name_first", "person_name_last",
            "person_birthday", "pic", "agree_user", "confirm_user");
        if ($t_cost_data["collateral"]["required"]) {
            $required_fields[] = "person_bankaccount";
            $required_fields[] = "person_bankaccount_name";
        }
        if (!$this->isicDbSchools->isJoined($check_data["school_id"])) {
            $tpl->addDataItem('SCHOOL_NOT_JOINED.DUMMY', 1);
        }
        if ($t_cost_data['delivery']['required']) {
            $required_fields[] = 'delivery_addr1';
            $required_fields[] = 'delivery_addr2';
            $required_fields[] = 'delivery_addr3';
            $required_fields[] = 'delivery_addr4';
        }
        if ($this->isic_payment->isPaymentRequired($t_cost_data)) {
            $required_fields[] = 'confirm_payment';
        }
        if ($this->isicDbCardTypes->isPersonEmailRequired($row_data['type_id'])) {
            $required_fields[] = 'person_email';
        }

        foreach ($fields as $key => $val) {
            $fdata = array();
            $fdata["type"] = $val[0];
            $fdata["size"] = $val[1];
            $fdata["cols"] = $val[1];
            $fdata["rows"] = $val[2];
            $fdata["list"] = $val[4];
            $fdata["java"] = $val[5];
            $fdata["class"] = $val[6];

            if (($action == "modify" || !$this->vars["write"]) &&
                $error != true &&
                $fdata['type'] != 'button'
            ) {
                $val[3] = $row_data[$key];
            }
            if ($action == "add" || $action == "modify" && $val[7]) {
                $f = new AdminFields($key, $fdata);
                if ($fdata['type'] == 'file') {
                    $f->setTitleAttr($this->txt->display('browse'));
                }
                if ($fdata['type'] == 'button') {
                    $f->setTitleAttr($val[3]);
                }
                $field_data = $f->display($val[3]);
                $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);
            } else {
                if (is_array($val[4])) {
                    $field_data = $val[4][$val[3]];
                } else {
                    if ($val[0] == "checkbox") {
                        $field_data = $this->txt->display("active" . $val[3]);
                    } else {
                        $field_data = $val[3];
                    }
                }
            }
            if (is_array($required_fields) && in_array($key, $required_fields)) {
                $required_field = "fRequired";
                if (is_array($bad_fields) && in_array($key, $bad_fields)) {
                    $required_field .= " fError";
                }
            } else {
                $required_field = "";
            }

            if ($key == 'confirm_payment') {
                $sub_tpl_name = 'CONFIRM_PAYMENT';
                $costRequired = $this->isic_payment->isPaymentRequired($t_cost_data);
                if ($action == "modify" && $costRequired) {
                    if ($val[9]) {
                        $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $this->txt->display($val[9]))));
                    }
                    if ($this->isic_payment->isApplicationPaymentComplete($row_data, $t_cost_data)) {
                        $tpl->addDataItem($sub_tpl_name . ".OK.DONE", $this->txt->display("paid"));
                    } else {
                        $tpl->addDataItem($sub_tpl_name . ".PAY.SUM", $t_cost_info['total_desc'] . ' = ' . $t_cost_info['total_sum']);
                        $tpl->addDataItem($sub_tpl_name . ".PAY.APPL_ID", $row_data["id"]);
                    }
                    $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                }
            } else {
                $sub_tpl_name = strtoupper($key);
                $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                if ($val[9]) {
                    $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $this->txt->display($val[9]))));
                }
            }
        }

        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('appl_id', $application);
        $hidden .= IsicForm::getHiddenField('step', $step);
        if ($pic_resize_required) {
            $hidden .= IsicForm::getHiddenField('pic_resize', 'true');
            $hidden .= IsicForm::getHiddenField('pic_name', $pic_filename);
        }

        switch ($step) {
            case 1:
                if (!$this->isPaymentConfirmed($row_data)) {
                    $tpl->addDataItem('SCHOOL_ID.ALL.TITLE', $this->txt->display('all_schools'));
                }
                break;
            case 2: // card type - product
                $this->showCardTypeStep($row_data, $tpl);
                break;
            case 3: // person data
                //$tpl->addDataItem("FIELD_person_birthday", $this->vars["person_birthday"] ? $this->vars["person_birthday"] : IsicDate::getDateFormatted($row_data["person_birthday"]));
                //$tpl->addDataItem("FIELD_person_birthday", $this->vars["person_birthday"] ? $this->vars["person_birthday"] : IsicDate::getDateFormatted($row_data["person_birthday"] . ' 12:00:00', 'Y-m-d H:i:s'));
                break;
            case 4: // pic data
                if ($pic_resize_required) {
                    if (IsicImageUploader::isMobileOrTablet()) {
                        $tpl->addDataItem("SHOW_PIC.DATA_pic", $pic_filename);
                    } else {
                        $sizeRatio = $this->pictureUploader['width'] / IsicImageUploader::IMAGE_SIZE_X;
                        $minWidth = IsicImageUploader::IMAGE_SIZE_X / $sizeRatio;
                        $minHeight = IsicImageUploader::IMAGE_SIZE_Y / $sizeRatio;
                        $tpl->addDataItem("EDIT_PIC_JS.MIN_WIDTH", round($minWidth));
                        $tpl->addDataItem("EDIT_PIC_JS.MIN_HEIGHT", round($minHeight));
                        $tpl->addDataItem("EDIT_PIC_JS.ASPECT_RATIO", IsicImageUploader::getAspectRatio());
                        $tpl->addDataItem("EDIT_PIC_JS.X1", round(($this->pictureUploader['width'] / $sizeRatio - $minWidth) / 2));
                        $tpl->addDataItem("EDIT_PIC_JS.Y1", round(($this->pictureUploader['height'] / $sizeRatio - $minHeight) / 2));
                        $tpl->addDataItem("EDIT_PIC.DATA_pic", $tmp_pic);
                        $tpl->addDataItem("EDIT_PIC.BUTTON", $this->txt->display("button_resize"));
                        $tpl->addDataItem("EDIT_PIC.MAX_WIDTH", $this->isic_common->image_size_x);
                    }
                    $t_pic_help = $this->txt->display("pic_help_resize");
                } else {
                    // if first time in this step, then showing pic from user data
                    if ($row_data["user_step"] == $step &&
                        !$error &&
                        !$row_data["pic"] &&
                        $this->isic_common->isValidPictureAge($user_data['pic'], $row_data['type_id'], $row_data['school_id'])
                    ) {
                        $row_data["pic"] = $user_data["pic"];
                        $hidden .= IsicForm::getHiddenField('use_user_pic', '1');
                    }

                    $t_pic_help = $this->txt->display("pic_help_general");
                    $tpl->addDataItem("SHOW_PIC.DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($row_data['pic'], 'big'));
                }
                $tpl->addDataItem("PIC.HELP", stripslashes($t_pic_help));
                break;
            case 5: // conditions
                $t_type_info = $this->isicDbCardTypes->getRecord($row_data["type_id"]);
                if ($t_type_info) {
                    $tpl->addDataItem("INFO_COND.URL", stripslashes($t_type_info["conditions_url"]));
                    $tpl->addDataItem("INFO_COND.DATA", stripslashes($t_type_info["conditions"]));
                }
                break;
            case 6: // confirm
                foreach ($row_data as $rkey => $rval) {
                    if ($rkey == "pic") {
                        $tpl->addDataItem("SHOW_PIC.DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($rval, 'big'));
                    } else if ($rkey == 'person_newsletter') {
                        $tpl->addDataItem("DATA_" . $rkey, $this->txt->display('active' . $rval));
                    } else {
                        $tpl->addDataItem("DATA_" . $rkey, stripslashes($rval));
                    }
                }
                if (!$onlyHiddenSchool) {
                    $tpl->addDataItem("SCHOOL.DATA_school_name", stripcslashes($row_data['school_name']));
                }
                $last_card = $this->isic_common->getUserLastCard($row_data["person_number"], $row_data["school_id"], $row_data["type_id"]);
                $t_type_expiration = $this->isic_common->getCardExpiration($row_data["type_id"], $last_card ? $last_card["expiration_date"] : "");
                $tpl->addDataItem("DATA_expiration_date", IsicDate::getDateFormatted($t_type_expiration));

                $deliverySum = $this->isicDbCardDeliveries->getDeliverySum($row_data['delivery_id']);

                // finding cost and collateral sums for every card type
                $tpl->addDataItem("DATA_collateral_sum", $t_cost_info["collateral"]);
                $tpl->addDataItem("DATA_cost_sum", $t_cost_info["cost"]);
                $tpl->addDataItem("DATA_delivery_sum", $t_cost_info["delivery"]);
                $tpl->addDataItem("DATA_application_type_name", $this->isicDbApplTypes->getNameById($t_cost_info["type"]));

                if ($t_cost_data['delivery']['required']) {
                    for ($i = 1; $i <= 4; $i++) {
                        $tpl->addDataItem("DELIVERY_ADDR{$i}.DATA", stripslashes($row_data['delivery_addr' . $i]));
                    }
                }
                break;
            case 8: // final screen - everything is done
                $tmpToken = $this->isicDbCardDeliveries->isDeliverable($check_data["delivery_id"]) ?
                    'application_done_nj' : 'application_done';
                $tpl->addDataItem("IMESSAGE.IMESSAGE",
                    str_replace('{DELIVERY_ADDRESS}',
                        $check_data['delivery_name'],
                        stripslashes($this->txt->display($tmpToken)))
                );
                break;
            default :
                break;
        }

        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", $general_url);
        $tpl->addDataItem("STEPS", $this->showApplicationSteps($application, $step, $row_data["user_step"], $general_url, $action, $firstStep));

        if ($step < 8) {
            if ($step > $firstStep) {
                $tpl->addDataItem("BUTTON_PREV.TITLE", $this->txt->display("prev_step"));
            }
            if ($step < 7) {
                if ($step == 6 && !$row_data["confirm_user"]) {
                    $tpl->addDataItem("BUTTON_CONFIRM.TITLE", $this->txt->display("confirm"));

                } else {
                    $tpl->addDataItem("BUTTON_NEXT.TITLE", $this->txt->display("next_step"));
                }
            }
            // record can be deleted if no payments has yet been done
            if (!$this->isPaymentConfirmed($row_data)) {
                $tpl->addDataItem("BUTTON_DISCONTINUE.TITLE", $this->txt->display("discontinue"));
                //$tpl->addDataItem("BUTTON_CANCEL.TITLE", $this->txt->display("cancel"));
            }
        } else {
            $tpl->addDataItem("BUTTON_LEAVE.TITLE", $this->txt->display("leave"));
            $tpl->addDataItem("BUTTON_LEAVE.URL", $general_url_list);
        }
        return $tpl->parse();
    }

    private function isPaymentConfirmed($check_data)
    {
        return
            $check_data['confirm_payment_cost'] ||
            $check_data['confirm_payment_collateral'] ||
            $check_data['confirm_payment_delivery'];
    }

    private function getNextUserStep($userStep, $currentStep)
    {
        return $currentStep;
        //return ($userStep > $currentStep) ? $userStep : $currentStep;
    }

    private function showCardTypeStep($row_data, $tpl)
    {
        $selectedType = $row_data["type_id"] ? $row_data["type_id"] : $this->vars["type_id"];
        if (!in_array($selectedType, $this->allowed_card_types_user)) {
            $selectedType = 0;
        }
        $userData = $this->isicDbUsers->getRecord($this->userid);
        $dbCardSendCosts = IsicDB::factory('CardSendCosts');
        $selectedDelivery = $row_data["delivery_id"] ? $row_data["delivery_id"] : $this->vars["delivery_id"];
        $currentDeliveryShown = false;

        foreach ($this->allowed_card_types_user as $t_type) {
            if (!$selectedType) {
                $selectedType = $t_type;
            }
            $cardCostCollData = $this->isic_payment->getCardCostCollData($row_data["person_number"], $row_data["school_id"], $t_type);
            $t_type_info = $this->getCardTypeInfo($row_data, $t_type, $cardCostCollData);
            if ($t_type_info) {
                $t_type_info['external_status_check_allowed'] = $userData['external_status_check_allowed'];
                $t_type_info['ehl_status_check_allowed'] = $userData['ehl_status_check_allowed'];
                if ($selectedType == $t_type) {
                    $this->showSelectedCardTypeInfo($t_type_info, $tpl);
                }

                $deliveries = $this->isicDbCardDeliveries->getRecordsBySchoolCardType($row_data['school_id'], $t_type);
                $deliveryList = array();
                foreach ($deliveries as $delivery) {
                    $deliveryList[] = array('id' => $delivery['id'], 'name' => $delivery['name']);
//                    $sendCostRecord = $dbCardSendCosts->getRecord($delivery['send_cost_id']);
                    $deliveryCostData = $this->isic_payment->getDeliveryData($cardCostCollData['compensation_total'], $delivery['id']);

                    if ($deliveryCostData['delivery']['required'] && $deliveryCostData['delivery']['sum']) {
                        $delivery['cost_sum'] = $deliveryCostData['delivery']['sum'] . ' ' . $cardCostCollData['currency'];
                    } else {
                        $delivery['cost_sum'] = '-';
                    }
                    if (!$selectedDelivery) {
                        $selectedDelivery = $delivery['id'];
                    }
                    if (!$currentDeliveryShown && $selectedDelivery == $delivery['id']) {
                        $this->showSelectedCardDeliveryInfo($delivery, $tpl);
                        $currentDeliveryShown = true;
                    }
                    $tpl->addDataItem("CARD_DELIVERY_DATA.TYPE_ID", $t_type);
                    $tpl->addDataItem("CARD_DELIVERY_DATA.ID", $delivery['id']);
                    $tpl->addDataItem("CARD_DELIVERY_DATA.DATA", JsonEncoder::encode($this->getCardDeliveryData($delivery)));
                }
                $t_type_info['delivery_list'] = $deliveryList;
                $tpl->addDataItem("CARD_TYPE_DATA.ID", $t_type);
                $tpl->addDataItem("CARD_TYPE_DATA.DATA", JsonEncoder::encode($this->getCardTypeData($t_type_info)));
            }
        }
    }

    private function showSelectedCardTypeInfo($typeInfo, $tpl)
    {
        $info_fields = array(
            "cost" => "cost",
            "coll" => "collateral",
            "desc" => "description",
            "bene" => "benefit_url",
            "expi" => "expiration",
            "type" => "appl_type_name",
        );
        $tpl->addDataItem("SHOW_PIC.DATA_pic", $typeInfo["pic"]);
        foreach ($info_fields as $ikey => $ival) {
            $tpl->addDataItem("INFO_" . strtoupper($ikey) . ".DATA", stripslashes($typeInfo[$ival]));
        }
        $tpl->addDataItem("EXTERNAL_STATUS_CHECK_ALLOWED.VISIBLE", (!$typeInfo['external_status_check_allowed'] && $typeInfo['external_status_check_needed']) ? '' : 'none');
        $tpl->addDataItem("EHL_STATUS_CHECK_ALLOWED.VISIBLE", (!$typeInfo['ehl_status_check_allowed'] && $typeInfo['ehl_status_check_needed']) ? '' : 'none');
        $tpl->addDataItem("ORDER_FOR_OTHERS.VISIBLE", $typeInfo['order_for_others_allowed'] ? '' : 'none');
        $tpl->addDataItem("PERSON_NUMBER_CHILD.VISIBLE", $typeInfo['order_for_others'] ? '' : 'none');
    }

    private function getCardTypeInfo($row_data, $t_type, $cardCostCollData)
    {
        $t_type_info = $this->isic_common->getCardTypeRecord($t_type, $row_data["school_id"]);
        if ($t_type_info) {
            $last_card = $this->isic_common->getUserLastCard($row_data["person_number"], $row_data["school_id"], $t_type);
            // finding expiration dates for every card type
            $t_type_expiration = $this->isic_common->getCardExpiration($t_type, $last_card ? $last_card["expiration_date"] : "", true);
            $t_type_info["expiration"] = IsicDate::getDateFormatted($t_type_expiration);
            $t_type_info["description"] .= ($t_type_info["description_school"] ? ("<br />" . $t_type_info["description_school"]) : "");

            // finding cost and collateral sums for every card type
            $t_cost_info = $this->formatCostData($cardCostCollData);
            $t_type_info["collateral"] = $t_cost_info["collateral"];
            $t_type_info["cost"] = $t_cost_info["cost"];
            $t_type_info["appl_type_name"] = $this->isicDbApplTypes->getNameById($t_cost_info["type"]);
            $t_type_info["external_status_check_needed"] = $this->isicDbCardTypes->isExternalCheckNeeded($t_type);
            $t_type_info["ehl_status_check_needed"] = $this->isicDbCardTypes->isEhlCheckNeeded($t_type);
            $t_type_info["order_for_others_allowed"] = $this->isicDbCardTypes->isOrderForOthersAllowed($t_type);
            $t_type_info['order_for_others'] = $this->vars['order_for_others'] ? $this->vars['order_for_others'] : $row_data['order_for_others'];

            if ($t_type_info["benefit_url"] && strpos($t_type_info["benefit_url"], "http") !== false) {
                $t_type_info["benefit_url"] = "<a href=\"" . $t_type_info["benefit_url"] . "\" target=\"_blank\">" . $t_type_info["benefit_url"] . "</a>";
            }
        }
        return $t_type_info;
    }

    private function getCardTypeData($t_type_info)
    {
        return array(
            "pic" => $t_type_info["pic"],
            "info_bene" => $t_type_info["benefit_url"] ? $t_type_info["benefit_url"] : "-",
            "info_desc" => $t_type_info["description"],
            "info_coll" => $t_type_info["collateral"],
            "info_cost" => $t_type_info["cost"],
            "info_expi" => $t_type_info["expiration"],
            "info_type" => $t_type_info["appl_type_name"],
            "info_ehis" => (!$t_type_info["external_status_check_allowed"] && $t_type_info["external_status_check_needed"]) ? '' : 'none',
            "info_ehl" => (!$t_type_info["ehl_status_check_allowed"] && $t_type_info["ehl_status_check_needed"]) ? '' : 'none',
            "info_cofo" => $t_type_info["order_for_others_allowed"] ? '' : 'none',
            "deli_list" => $t_type_info["delivery_list"],
        );
    }

    private function showSelectedCardDeliveryInfo($deliveryInfo, $tpl)
    {
        $info_fields = array(
            "deliv" => "cost_sum",
        );
        foreach ($info_fields as $ikey => $ival) {
            $tpl->addDataItem("INFO_" . strtoupper($ikey) . ".DATA", stripslashes($deliveryInfo[$ival]));
        }
    }

    private function getCardDeliveryData($deliveryInfo)
    {
        return array(
            'info_deliv' => $deliveryInfo['cost_sum'],
        );
    }

    /**
     * Formats given array with cost data into more suitable array for displaying
     *
     * @param array $cost_data array with all the cost related data
     * @return array with cost and collateral string for displaying
     */
    function formatCostData($cost_data)
    {
        $cost_info = array();
        $txt = new Text($this->language, 'module_isic_payment');

        $cost_info['type'] = $cost_data['type'];
        $cost_info['total_desc'] = '';
        $cost_info['total_sum'] = '';
        $paymentTypeList = array();
        $totalSum = 0;

        foreach ($this->isic_payment->payment_type_name as $paymentTypeId => $paymentTypeName) {
            if ($cost_data[$paymentTypeName]['hidden']) {
                continue;
            }
            if ($cost_data[$paymentTypeName]['error']) {
                $cost_info[$paymentTypeName] = '-- card has to be deactivated first --';
            } else {
                if ($cost_data[$paymentTypeName]['required'] && $cost_data[$paymentTypeName]['sum']) {
                    $cost_info[$paymentTypeName] = $cost_data[$paymentTypeName]['sum'] . ' ' . $cost_data['currency'];
                    $totalSum += $cost_data[$paymentTypeName]['sum'];
                    $paymentTypeList[] = $cost_data[$paymentTypeName]['sum'] . ' (' . $txt->display('payment_type' . $paymentTypeId) . ')';
                } else {
                    $cost_info[$paymentTypeName] = "-";
                }
            }
        }

        if ($totalSum) {
            $cost_info['total_sum'] = $totalSum . ' ' . $cost_data['currency'];
            $cost_info['total_desc'] = implode(' + ', $paymentTypeList);
        }

        return $cost_info;
    }

    public function checkRequired($required, &$errorFields)
    {
        $error = $this->isic_common->checkRequired($this->vars, $required, $errorFields);
        if ($error) {
            return true;
        }

        $applValidator = new IsicApplicationValidator();
        return $applValidator->hasNonValidFields($this->vars, $required, $errorFields);
    }

    /**
     * Displays add/modify view of a application for admin-user
     *
     * @param int $application application id
     * @param string $action action (add/modify)
     * @return string html addform for applications
     */
    function addApplicationAdmin($application, $action)
    {
        if ($this->checkAccess() == false) return "";
        if (!$application && $this->vars["appl_id"]) {
            $application = $this->vars["appl_id"];
        }
        if ($this->vars["action"]) {
            $action = $this->vars["action"];
        }
        if ($this->module_param["isic_application2"]) {
            $this->form_type = $this->module_param["isic_application2"];
        }
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];
        $use_user_pic = @$this->vars["use_user_pic"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }
        $general_url_list = $this->isic_common->getGeneralUrlByTemplate($this->isic_common->template_application_list); // list-template

        if ($application) {
            $check_data = $this->isicDbApplications->getRecord($application);
            if (!$check_data || !$this->isic_common->canModifyApplication($check_data)) {
                // if user has not rights to modify this application, then redirecting to listview with error
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=modify", array("appl_id", "action")));
            }
            // cost information for given card
            $t_cost_data = $this->isic_payment->getCardCostCollDeliveryData($check_data);
            // default pic name
            $pic_filename = $this->isic_common->a_pic_prefix . str_pad($check_data["id"], 10, '0', STR_PAD_LEFT);
            if (!$action) {
                $action = "modify";
            }
        } else {
            // if no application record given, then it has to be add-action
            $action = "add";
        }
        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            $error = false;

            // image upload handling
            $imageUploader = new IsicImageUploader('application');
            $this->pictureUploader = $pic_data = $imageUploader->handlePictureUpload($application);
            $this->vars["pic"] = $pic_data["pic_vars"];
            $pic_filename = $pic_data["pic_filename"];
            $pic_resize_required = $pic_data["pic_resize_required"];
            $tmp_pic = $pic_data["tmp_pic"];
            $error_pic = $pic_data["error_pic"];
            $error_pic_save = $pic_data["error_pic_save"];
            $error_pic_size = $pic_data["error_pic_size"];
            $error_pic_resize = $pic_data["error_pic_resize"];
            $error_pic_format = $pic_data["error_pic_format"];

            switch ($action) {
                case "add":
                    if ($this->vars["person_position"] == NULL) {
                        $this->vars["person_position"] = "";
                    }
                    if ($this->vars["person_class"] == NULL) {
                        $this->vars["person_class"] = "";
                    }
                    if ($this->vars["person_stru_unit"] == NULL) {
                        $this->vars["person_stru_unit"] = "";
                    }
                    if ($this->vars["person_stru_unit2"] == NULL) {
                        $this->vars["person_stru_unit2"] = "";
                    }
                    if ($this->vars["pic"] == NULL) {
                        $this->vars["pic"] = "";
                    }
                    if ($this->vars["pic"] == NULL) {
                        $this->vars["pic"] = "";
                    }
                    if ($this->vars["delivery_addr3"] == NULL) {
                        $this->vars["delivery_addr3"] = self::DEFAULT_COUNTRY;
                    }
                    if ($pic_resize_required) {
                        $use_user_pic = false;
                    }

                    $required = array("type_id", "school_id", "person_name_first", "person_name_last",
                        "person_birthday", "person_number", 'delivery_id');

                    $this->vars['delivery_id'] = $this->vars['delivery_id'] ? $this->vars['delivery_id'] : '';

                    $t_applData = array(
                        'person_number' => $this->vars["person_number"],
                        'school_id' => $this->vars["school_id"],
                        'type_id' => $this->vars["type_id"],
                        'delivery_id' => $this->vars["delivery_id"]
                    );
                    $t_cost_data = $this->isic_payment->getCardCostCollDeliveryData($t_applData);

                    // check if collateral is required, then setting bank-account as required field
                    if ($t_cost_data["collateral"]["required"]) {
                        $required[] = "person_bankaccount";
                        $required[] = "person_bankaccount_name";
                    }
                    if ($t_cost_data['delivery']['required']) {
                        $required[] = 'delivery_addr1';
                        $required[] = 'delivery_addr2';
                        $required[] = 'delivery_addr3';
                        $required[] = 'delivery_addr4';
                        $this->vars['delivery_addr3'] = self::DEFAULT_COUNTRY;
                    }
                    if ($this->isicDbCardTypes->isPersonEmailRequired($this->vars['type_id'])) {
                        $required[] = 'person_email';
                    }
                    $bad_fields = false;
                    $error = $this->checkRequired($required, $bad_fields);

                    $this->vars["person_birthday"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["person_birthday"]);

                    if (!$error && $this->vars['person_email']) {
                        $error = $error_email = !validateEmail($this->vars["person_email"]);
                        if ($error) {
                            $bad_fields[] = "person_email";
                        }
                    }

                    if (!$error) {
                        // Checking if card type is allowed for given school
                        // in case current user is super-admin, we allow all card types to all schools
                        if (!$this->isicDbUsers->isCurrentUserSuperAdmin() &&
                            !in_array($this->vars['type_id'],
                                $this->getCardTypeListBySchool($this->vars['school_id'])
                            )
                        ) {
                            $error = $error_card_type_not_allowed = true;
                            $bad_fields[] = "type_id";
                        }
                    }

                    if (!$error) {
                        // check if card type was chosen that requires the card to be de-acitvated first
                        if ($t_cost_data["cost"]["error"]) {
                            $error = $error_cost = true;
                            $bad_fields[] = "type_id";
                        }
                    }

                    if (!$error) {
                        $error = $error_appl_type_exists = $this->isic_common->getUserApplicationTypeExists($this->vars["person_number"], $this->vars["type_id"], 0);
                        if ($error) {
                            $bad_fields[] = "type_id";
                        }
                    }

                    if (!$error) {
                        $error = $error_card_type_exists = $this->isic_common->getUserCardTypeExistsOrderedDistributed($this->vars["person_number"], $this->vars["type_id"]);
                        if ($error) {
                            $bad_fields[] = "type_id";
                        }
                    }

                    if (!$error && !$error_pic) {
                        $applData = array(
                            'application_type_id' => $t_cost_data["type"],
                            'prev_card_id' => $t_cost_data["last_card_id"],
                            'type_id' => $this->vars["type_id"],
                            'school_id' => $this->vars["school_id"],
                            'person_name_first' => $this->vars["person_name_first"],
                            'person_name_last' => $this->vars["person_name_last"],
                            'person_birthday' => $this->vars["person_birthday"],
                            'person_email' => $this->vars["person_email"],
                            'person_number' => $this->vars["person_number"],
                            'person_phone' => $this->vars["person_phone"],
                            'person_position' => $this->vars["person_position"],
                            'person_class' => $this->vars["person_class"],
                            'person_stru_unit' => $this->vars["person_stru_unit"],
                            'person_stru_unit2' => $this->vars["person_stru_unit2"],
                            'person_bankaccount' => $this->vars["person_bankaccount"],
                            'person_bankaccount_name' => $this->vars["person_bankaccount_name"],
                            'pic' => $this->vars["pic"],
                            'confirm_payment_collateral' => 0,
                            'confirm_payment_cost' => 0,
                            'confirm_payment_delivery' => 0,
                            'delivery_id' => $this->vars["delivery_id"],
                            'delivery_addr1' => $this->vars["delivery_addr1"],
                            'delivery_addr2' => $this->vars["delivery_addr2"],
                            'delivery_addr3' => $this->vars["delivery_addr3"],
                            'delivery_addr4' => $this->vars["delivery_addr4"],
                            'campaign_code' => $this->vars["campaign_code"],
                            'person_newsletter' => $this->vars["person_newsletter"] ? 1 : 0,
                        );
                        $application = $this->isicDbApplications->insertRecord($applData);
                        $applRecord = $this->isicDbApplications->getRecord($application);
                        $userRecord = $this->getUserDataByApplicationAndAddNewIfNotFound($applRecord);
                        $info_msg = "add";

                        // updating card user profile
                        $userUpdates = $applRecord;
                        if ($this->vars['person_newsletter']) {
                            $this->updateSpecialOffers($applRecord, $userRecord);
                        }

                        $this->isicDbUsers->updateRecordFromApplication($userRecord['user'], $userUpdates);
                        if ($this->isicDbCardTypes->isExternalCheckNeeded($applRecord['type_id'])) {
                            $this->enableUserExternalDataCheck($userRecord);
                        }
                        if ($this->isicDbCardTypes->isEHLCheckNeeded($applRecord['type_id'])) {
                            $this->enableUserEhlDataCheck($userRecord);
                        }
                        $this->isAllowedCardType($applRecord['school_id'], $applRecord['type_id'], $userRecord['user']);
                        $this->updateStatusInfoFields($applRecord, $userRecord);

                        // if picture was added, then changing the name of the picture
                        if ($this->vars["pic"]) {
                            $old_name = $this->vars["pic"];
                            $old_name_thumb = str_replace('.jpg', '_thumb.jpg', $this->vars["pic"]);
                            $this->vars["pic"] = $new_name = str_replace($pic_filename, $this->isic_common->a_pic_prefix . str_pad($application, 10, '0', STR_PAD_LEFT), $old_name);
                            $new_name_thumb = str_replace('.jpg', '_thumb.jpg', $new_name);
                            if (Filesystem::rename(SITE_PATH . $old_name, SITE_PATH . $new_name) &&
                                Filesystem::rename(SITE_PATH . $old_name_thumb, SITE_PATH . $new_name_thumb)
                            ) {
                                $this->isicDbApplications->updateRecord($application, array('pic' => $new_name));
                                $this->isic_common->copyApplicationPictureToUser($this->isicDbApplications->getRecord($application));
                            }
                        } else if ($use_user_pic) {
                            $this->copyUserPictureToApplication($applRecord);
                        }

                        if ($pic_resize_required) {
                            $action = "modify";
                        }
                    }
                    break;
                case "modify":
                    $required = array("person_name_first", "person_name_last");
                    // check if collateral is required, then setting bank-account as required field
                    if ($t_cost_data["collateral"]["required"]) {
                        $required[] = "person_bankaccount";
                        $required[] = "person_bankaccount_name";
                    }
                    if ($t_cost_data['delivery']['required']) {
                        $required[] = 'delivery_addr1';
                        $required[] = 'delivery_addr2';
                        $required[] = 'delivery_addr3';
                        $required[] = 'delivery_addr4';
                        $this->vars['delivery_addr3'] = self::DEFAULT_COUNTRY;
                    }
                    if ($this->isicDbCardTypes->isPersonEmailRequired($check_data['type_id'])) {
                        $required[] = 'person_email';
                    }
                    $error = $this->checkRequired($required, $bad_fields);

                    if (!$error && $this->vars['person_email']) {
                        $error = $error_email = !validateEmail($this->vars["person_email"]);
                        if ($error) {
                            $bad_fields[] = "person_email";
                        }
                    }

                    if (!$error) {
                        $applData = array(
                            'person_name_first' => $this->vars["person_name_first"],
                            'person_name_last' => $this->vars["person_name_last"],
                            'person_email' => $this->vars["person_email"],
                            'person_phone' => $this->vars["person_phone"],
                            'person_position' => $this->vars["person_position"],
                            'person_class' => $this->vars["person_class"],
                            'person_stru_unit' => $this->vars["person_stru_unit"],
                            'person_stru_unit2' => $this->vars["person_stru_unit2"],
                            'person_bankaccount' => $this->vars["person_bankaccount"],
                            'person_bankaccount_name' => $this->vars["person_bankaccount_name"],
                            'person_newsletter' => $this->vars['person_newsletter'] ? 1 : 0,
                            'delivery_addr1' => $this->vars["delivery_addr1"],
                            'delivery_addr2' => $this->vars["delivery_addr2"],
                            'delivery_addr3' => $this->vars["delivery_addr3"],
                            'delivery_addr4' => $this->vars["delivery_addr4"],
                            'campaign_code' => $this->vars["campaign_code"],
                        );
                        $this->isicDbApplications->updateRecord($application, $applData);
                        $info_msg = "modify";
                        // updating newsletter settings for the card user
                        $applRecord = $this->isicDbApplications->getRecord($application);
                        $userRecord = $this->getUserDataByApplicationAndAddNewIfNotFound($applRecord);
                        if ($this->vars['person_newsletter']) {
                            $this->updateSpecialOffers($applRecord, $userRecord);
                        }

                        $this->updateStatusInfoFields($applRecord, $userRecord);
                    }

                    if (!$error_pic && $this->vars["pic"]) {
                        $applData = array(
                            'pic' => $this->vars["pic"],
                        );
                        $this->isicDbApplications->updateRecord($application, $applData);
                        $applRecord = $this->isicDbApplications->getRecord($application);
                        $this->isic_common->copyApplicationPictureToUser($applRecord);
                    }
                    break;
                case "confirm_admin":
                    if ($this->isic_payment->isApplicationPaymentComplete($check_data, $t_cost_data)) {
                        // performing EHL status retrieval if card type requires it
                        if (!$this->vars['confirm_admin_confirm'] && $this->isicDbCardTypes->isEHLCheckNeeded($check_data['type_id'])) {
                            $this->enableUserEhlDataCheck($this->isicDbUsers->getRecordByCode($check_data["person_number"]));
                            $this->getEHLClient()->getStatusListByUser($check_data['person_number']);
                        }

                        if (!$this->vars['confirm_admin_confirm'] && $this->isicDbCardTypes->isExternalCheckNeeded($check_data['type_id'])) {
                            // perform external check and see if needed groups are already present
                            $error = !$this->isApplicationUserInNeededGroup($check_data);
                            if ($this->ehisError->isError()) {
                                $error = $error_ehis_query = true;
                            }
                        }
                        if (!$error) {
                            $error = $error_school_not_active = !$this->isicDbSchools->isActive($check_data["school_id"]);
                        }

                        if (!$error) {
                            $this->autoPayments($t_cost_data, $check_data);

                            $applData = array(
                                'confirm_admin' => 1,
                                'state_id' => IsicDB_Applications::state_admin_confirm
                            );
                            $this->isicDbApplications->updateRecord($application, $applData);
                            $this->setUserAndGroupsByApplication($check_data);
                            $this->isicDbApplications->sendConfirmNotificationToUser($application);
                            $info_msg = "confirm_admin";
                        } else {
                            if ($error_ehis_query) {
                                $error_msg = 'ehis_query';
                            } elseif ($error_school_not_active) {
                                $error_msg = 'error_school_not_active';
                            } else {
                                $error_msg = 'manual_confirm_needed';
                            }
                            redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "appl_id={$application}&nocache=true&manual_confirm=1&error=" . $error_msg, array("appl_id", "action", "confirm_admin_confirm")));
                        }
                    } else {
                        $error = true;
                    }
                    break;
                case "reject":
                    if ($this->vars["confirm_reject"]) {
                        $required = array("reason_id");
                        $error = $this->checkRequired($required, $bad_fields);
                        if (!$error) {
                            // find new state for application by the reason ID
                            $t_state_id = $this->isic_common->getApplicationRejectState($this->vars["reason_id"]);
                            if (!$t_state_id) {
                                $error = true;
                                $bad_fields[] = "reason_id";
                            }
                        }
                        if (!$error) {
                            $reject_data = $this->isic_common->getApplicationRejectRecord($this->vars["reason_id"]);

                            if ($reject_data['return_collateral']) {
                                $this->isic_payment->setApplicationPaymentRejectedCollateral($application);
                                $appl_confirm_payment_collateral = 0;
                                $appl_collateral_sum = 0;
                                $appl_currency = '';
                            } else {
                                $appl_confirm_payment_collateral = $check_data['confirm_payment_collateral'];
                                $appl_collateral_sum = $check_data['collateral_sum'];
                                $appl_currency = $check_data['currency'];
                            }

                            if ($reject_data['return_cost']) {
                                $this->isic_payment->setApplicationPaymentRejectedCost($application);
                                $appl_confirm_payment_cost = 0;
                                $appl_cost_sum = 0;
                                $appl_currency = '';
                                $this->isic_payment->setApplicationPaymentRejectedCompensation($application);
                                $appl_compensation_sum = 0;
                                $appl_confirm_payment_delivery = 0;
                                $appl_delivery_sum = 0;
                                $this->isic_payment->setApplicationPaymentRejectedCompensationDelivery($application);
                                $appl_compensation_sum_delivery = 0;
                            } else {
                                $appl_currency = $check_data['currency'];
                                $appl_confirm_payment_cost = $check_data['confirm_payment_cost'];
                                $appl_cost_sum = $check_data['cost_sum'];
                                $appl_compensation_sum = $check_data['compensation_sum'];
                                $appl_confirm_payment_delivery = $check_data['confirm_payment_delivery'];
                                $appl_delivery_sum = $check_data['delivery_sum'];
                                $appl_compensation_sum_delivery = $check_data['compensation_sum_delivery'];
                            }

                            $applData = array(
                                'state_id' => $t_state_id,
                                'user_step' => 1,
                                'confirm_user' => 0,
                                'reject_reason_id' => $this->vars["reason_id"],
                                'reject_reason_text' => $this->vars["reason_text"],
                                'confirm_payment_collateral' => $appl_confirm_payment_collateral,
                                'collateral_sum' => $appl_collateral_sum,
                                'confirm_payment_cost' => $appl_confirm_payment_cost,
                                'cost_sum' => $appl_cost_sum,
                                'compensation_sum' => $appl_compensation_sum,
                                'confirm_payment_delivery' => $appl_confirm_payment_delivery,
                                'delivery_sum' => $appl_delivery_sum,
                                'compensation_sum_delivery' => $appl_compensation_sum_delivery,
                                'currency' => $appl_currency,
                            );
                            $this->isicDbApplications->updateRecord($application, $applData);
                            $info_msg = "reject";

                            // sending notifications to user
                            if ($t_state_id == $this->a_state_rejected) {
                                IsicMail::sendApplicationFinalRejectionNotification(
                                    $this->isicDbApplications->getRecord($application)
                                );
                            } elseif ($t_state_id == $this->a_state_not_done) {
                                IsicMail::sendApplicationCorrectionRequiredRejectionNotification(
                                    $this->isicDbApplications->getRecord($application)
                                );
                            }
                        }
                    }
                    break;
                case "cost": // falls through
                case "collateral":  // falls through
                case "delivery":
                    if ($this->vars['payment_method'] == $this->isicDbPayments->getMethodExternal()) {
                        $error = $error_payment_date = !IsicDate::isValidPaymentDate(
                            IsicDate::getDateFormattedFromEuroToDb($this->vars["actual_payment_date"])
                        );
                    }
                    if (!$error) {
                        $this->savePayment($action, $check_data, $t_cost_data);
                        $info_msg = $this->vars["info"] = "confirm_payment_" . $action;
                        $write = false;
                        $action = "modify";
                    } else {
                        $this->vars['payment_type'] = $action;
                        $action = 'payment';
                    }
                    break;
                case "deposit":
                    $payment = $this->isicDbPayments->getFreeCollateralRecordByUserSum(
                        $check_data['person_number'],
                        $t_cost_data["collateral"]["sum"],
                        $t_cost_data["currency"]
                    );
                    /**
                     * @todo Rewrite the following block to use IsicDB_Payments::createPaymentFromPayment()
                     */
                    if ($payment) {
                        // update the old payment
                        $this->isicDbPayments->updateRecord(
                            $payment['id'],
                            array(
                                'free' => 0,
                                'autoreturn' => 0,
                                'autoreturn_date' => IsicDate::EMPTY_DATE
                            )
                        );
                        // create a new one
                        $payment['application_id'] = $application;
                        $payment['type_id'] = $check_data['application_type_id'];
                        $payment['prev_id'] = $payment['id'];
                        $payment['card_id'] = 0;
                        $payment['free'] = 0;
                        $payment['autoreturn'] = 0;
                        $payment['autoreturn_date'] = IsicDate::EMPTY_DATE;
                        unset($payment['id']);
                        $this->isicDbPayments->insertRecord($payment);
                        // updating application
                        $applData = array(
                            'confirm_payment_collateral' => 1,
                            'collateral_sum' => $t_cost_data["collateral"]["sum"],
                            'currency' => $this->isic_payment->currency
                        );
                        $this->isicDbApplications->updateRecord($application, $applData);
                        $info_msg = $this->vars["info"] = "success_use_deposit";
                    }
                    $write = false;
                    $action = "modify";
                    break;
                default :
                    break;
            }

            // ######################
            if (!$error && !$pic_resize_required && !$error_pic) {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"],
                        "appl_id={$application}&nocache=true&info={$info_msg}",
                        array("appl_id", "action", 'error', 'manual_confirm'))
                );
            }
//            if ($error_payment_date) {
//                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"],
//                    "appl_id={$application}&nocache=true&error=error_payment_date&action=payment" .
//                    '&payment_method=' . $this->vars['payment_method'],
//                    array('appl_id', 'action', 'error', 'manual_confirm', 'payment_method'))
//                );
//            }
        }

        if ($action == "modify") {
            $row_data = $this->isicDbApplications->getRecord($application);
            if ($this->isic_common->canModifyApplication($row_data)) {
                $t_cost_data = $this->isic_payment->getCardCostCollDeliveryData($row_data);
                $t_cost_info = $this->formatCostData($t_cost_data);
                $this->vars["school_id"] = $row_data["school_id"];
                $this->vars["type_id"] = $row_data["type_id"];
                $this->vars["person_number"] = $row_data["person_number"];
            } else {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=modify", array("appl_id", "action")));
            }
        }

        // ###################################

        switch ($action) {
            case "add":
                $template = "module_isic_application_add.html";
                break;
            case "reject":
                $template = "module_isic_application_modify_confirm.html";
                break;
            case "payment":
                $template = "module_isic_application_payment_confirm.html";
                break;
            default :
                $template = "module_isic_application_modify.html";
                break;
        }

        $instanceParameters = '&type=addapplication';
        $tpl = $this->isicTemplate->initTemplateInstance($template, $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        // #################################
        if ($error == true || $error_pic) {
//            if ($error_card_exists) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists"));
//            } elseif ($error_card_exists_prolong) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists_prolong"));
//            } elseif ($error_card_exists_replace) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_exists_replace"));
//            } elseif ($error_prolong_not_allowed) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_prolong_not_allowed"));
//            } elseif ($error_isic_number) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_isic_number"));
//            } elseif ($error_status_required) {
//                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_status_required"));
            if ($error_pic_resize) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_resize"));
            } elseif ($error_pic_save) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_save"));
            } elseif ($error_pic_size) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_size"));
            } elseif ($error_pic_format) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_format"));
            } elseif ($error_cost) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_deactivation_required"));
            } elseif ($error_email) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_email"));
            } elseif ($error_appl_type_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_appl_type_exists"));
            } elseif ($error_card_type_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_type_exists"));
            } elseif ($error_card_type_not_allowed) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_card_type_not_allowed"));
            } elseif ($error_school_not_active) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_school_not_active"));
            } elseif ($error_payment_date) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_payment_date"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error"));
            }
        } elseif ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        if (!$this->vars["language_id"]) {
            $this->vars["language_id"] = $this->language_default;
        }

        switch ($action) {
            case "reject":
                $fields = array(
                    "reason_id" => array("select", 0, 0, $this->vars["reason_id"], $this->getApplicationRejectReasonList(), "", "", true, true, 'reason_help'),
                    "reason_text" => array("textfield", 40, 10, $this->vars["reason_text"], "", "", "", true, true),
                );
                break;
            case "payment":
                if (!$this->vars['actual_payment_date']) {

                }
                $fields = array(
                    "payment_method" => array("select", 0, 0, $this->vars["payment_method"], $this->getPaymentMethodList(), "onChange=\"showFields(this.value);\"", "", true, true, ''),
                    "actual_payment_date" => array("textinput", 40, 0, $this->vars["actual_payment_date"], "", "", "datePicker", true, true),
                    "bank_id" => array("select", 40, 0, $this->vars["bank_id"], $this->isicDbBanks->getBankList('', false), "", "", true, true),
                );
                break;
            default :
                $schoolList = $this->getSchoolList(false, false, true);
                if (!$this->vars["person_birthday"] && $row_data["person_birthday"]) {
                    $t_birthday = IsicDate::getDateFormatted($row_data["person_birthday"]);
                } else {
                    $t_birthday = IsicDate::getDateFormatted($this->vars["person_birthday"]);
                }

                if (isset($this->vars['school_id'])) {
                    $schoolId = $this->vars['school_id'];
                } else if ($row_data['school_id']) {
                    $schoolId = $row_data['school_id'];
                } else {
                    $schoolId = key($schoolList);
                }

                if (isset($this->vars['type_id'])) {
                    $cardTypeId = $this->vars['type_id'];
                } else if ($row_data['type_id']) {
                    $cardTypeId = $row_data['type_id'];
                } else {
                    $cardTypeId = 0;
                }

                $fields = array(
                    "application_type_id" => array("select", 0, 0, $this->vars["application_type_id"], $this->getApplicationTypeList(false), "", "", false, true),
                    "school_id" => array("select", 0, 0, $this->vars["school_id"], $schoolList, "onChange=\"refreshTypeList(); getUserStatusData();\"", "", false, true),
                    "type_id" => array("select", 0, 0, $this->vars["type_id"], $this->getCardTypeList('add', false), "onChange=\"refreshDeliveryList(); getUserStatusData();\"", "", false, true),
                    "delivery_id" => array("select", 0, 0, $this->vars["delivery_id"], $this->getDeliveryList($schoolId, true, $cardTypeId), "", "", false, true, 'delivery_help'),
                    "person_name_first" => array("textinput", 40, 0, $this->vars["person_name_first"], "", "", "", true, true),
                    "person_name_last" => array("textinput", 40, 0, $this->vars["person_name_last"], "", "onblur=\"generateBankAccountName();\"", "", true, true),
                    "person_number" => array("textinput", 40, 0, $this->vars["person_number"], "", "onblur=\"generateBirthday();\"", "", false, true),
                    "person_birthday" => array("textinput", 40, 0, $t_birthday, "", "", "datePicker", false, true),
                    "person_email" => array("textinput", 40, 0, $this->vars["person_email"], "", "", "", true, true),
                    "person_phone" => array("textinput", 40, 0, $this->vars["person_phone"], "", "", "", true, true, "phone_help"),
                    "person_position" => array("textinput", 40, 0, $this->vars["person_position"], "", "", "", true, false),
//                    "person_class" => array("textinput", 40, 0, $this->vars["person_class"], "", "", "", true, false),
                    "person_stru_unit" => array("textinput", 40, 0, $this->vars["person_stru_unit"], "", "", "", true, false),
                    "person_stru_unit2" => array("textinput", 40, 0, $this->vars["person_stru_unit2"], "", "", "", true, false),
                    "person_bankaccount" => array("textinput", 40, 0, $this->vars["person_bankaccount"], "", "", "", true, true, "bankaccount_help"),
                    "person_bankaccount_name" => array("textinput", 40, 0, $this->vars["person_bankaccount_name"], "", "", "", true, true),
                    "delivery_addr" => array("button", 0, 0, $this->txt->display("copy_delivery_addr"), "", "onClick=\"copyDeliveryAddr();\"", "", true, true),
                    "delivery_addr1" => array("textinput", 40, 0, $this->vars["delivery_addr1"], "", "", "", true, true, "delivery_addr1_help"),
                    "delivery_addr2" => array("textinput", 40, 0, $this->vars["delivery_addr2"], "", "", "", true, true),
                    "delivery_addr3" => array("textinput", 40, 0, self::DEFAULT_COUNTRY, "", "", "", false, true),
                    "delivery_addr4" => array("textinput", 40, 0, $this->vars["delivery_addr4"], "", "", "", true, true),
                    "campaign_code" => array("textinput", 40, 0, $this->vars["campaign_code"], "", "", "", true, true),
//                    "person_newsletter" => array("checkbox", 0, 0, $this->vars["person_newsletter"], "", "", "", true, true),
                );
                if ($action == 'modify' && $this->isicDbSchools->isHiddenSchool($schoolId)) {
                    unset($fields['school_id']);
                }

                $fields["pic"] = array("file", 43, 0, $this->vars["pic"], "", "", "", true, true, "pic_help");

                break;
        }

        $required_fields = array("school_id", "type_id", "person_number", "person_name_first", "person_name_last",
            "person_birthday", "person_email", "reason_id", "delivery_id", 'actual_payment_date', 'bank_id');
        if ($t_cost_data["collateral"]["required"]) {
            $required_fields[] = "person_bankaccount";
            $required_fields[] = "person_bankaccount_name";
        }
        if ($t_cost_data['delivery']['required']) {
            $required_fields[] = "delivery_addr1";
            $required_fields[] = "delivery_addr2";
            $required_fields[] = "delivery_addr3";
            $required_fields[] = "delivery_addr4";
        }
        if ($this->isicDbCardTypes->isPersonEmailRequired($action == 'add' ? $this->vars['type_id'] : $row_data['type_id'])) {
            $required_fields[] = 'person_email';
        }

        foreach ($fields as $key => $val) {
            $fdata["type"] = $val[0];
            $fdata["size"] = $val[1];
            $fdata["cols"] = $val[1];
            $fdata["rows"] = $val[2];
            $fdata["list"] = $val[4];
            $fdata["java"] = $val[5];
            $fdata["class"] = $val[6];

            if ($action == "modify" && !$error && $fdata['type'] != 'button') {
                $val[3] = $row_data[$key];
            }

            if (($action == "add" || ($action == "modify" || $action == "reject" || $action == 'payment') && $val[7]) && $key != 'delivery_addr3') {
                $f = new AdminFields($key, $fdata);
                if ($fdata['type'] == 'file') {
                    $f->setTitleAttr($this->txt->display('browse'));
                }
                if ($fdata['type'] == 'button') {
                    $f->setTitleAttr($val[3]);
                }
                $field_data = $f->display($val[3]);
                $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);
            } else {
                if (is_array($val[4])) {
                    $field_data = $val[4][$val[3]];
                } else {
                    if ($val[0] == "checkbox") {
                        $field_data = $this->txt->display("active" . $val[3]);
                    } else {
                        $field_data = $val[3];
                    }
                }
            }

            if (is_array($required_fields) && in_array($key, $required_fields)) {
                $required_field = "fRequired";
                if (is_array($bad_fields) && in_array($key, $bad_fields)) {
                    $required_field .= " fError";
                }
            } else {
                $required_field = "";
            }

            switch ($key) {
                case "person_bankaccount_name":
                    $sub_tpl_name = "PERSON_BANKACCNAME";
                    $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                    $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                    break;
                default:
                    $sub_tpl_name = strtoupper($key);
                    $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                    $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                    if ($val[9]) {
                        $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $this->txt->display($val[9]))));
                    }
                    break;
            }
            unset($fdata);
        }

        $hidden = '';
        if ($pic_resize_required) {
            $coordinates = IsicImageUploader::getMaxCoordinates($this->pictureUploader);
            $tpl->addDataItem("EDIT_PIC_JS.MIN_WIDTH", round($coordinates['minWidth']));
            $tpl->addDataItem("EDIT_PIC_JS.MIN_HEIGHT", round($coordinates['minHeight']));
            $tpl->addDataItem("EDIT_PIC_JS.ASPECT_RATIO", IsicImageUploader::getAspectRatio());
            $tpl->addDataItem("EDIT_PIC_JS.X1", $coordinates['x1']);
            $tpl->addDataItem("EDIT_PIC_JS.X2", $coordinates['x2']);
            $tpl->addDataItem("EDIT_PIC_JS.Y1", $coordinates['y1']);
            $tpl->addDataItem("EDIT_PIC_JS.Y2", $coordinates['y2']);

            $hidden .= IsicForm::getHiddenField('pic_resize', 'true');
            $hidden .= IsicForm::getHiddenField('pic_name', $this->pictureUploader["pic_filename"]);
            $tpl->addDataItem("EDIT_PIC.DATA_pic", $this->pictureUploader["tmp_pic"]);
            $tpl->addDataItem("EDIT_PIC.BUTTON", $this->txt->display("resize"));
            $tpl->addDataItem("EDIT_PIC.MAX_WIDTH", IsicImageUploader::IMAGE_SIZE_X);
        } else {
            if ($row_data["pic"] != "") {
                $t_pic = $row_data["pic"];
            } else if ($use_user_pic) {
                $userRecord = $this->isicDbUsers->getRecordByCode($this->vars["person_number"]);
                if ($userRecord && $userRecord["pic"]) {
                    $t_pic = $userRecord["pic"];
                }
            } else if ($this->vars['pic'] != '') {
                $t_pic = $this->vars['pic'];
            }
            $tpl->addDataItem("SHOW_PIC.DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($t_pic, 'big'));
        }

        if ($action == "modify") {
            $tpl->addDataItem("CONF_PAY_COLL.FIELD_conf_pay_coll", $t_cost_data["collateral"]["required"] ? $this->txt->display("active" . $row_data["confirm_payment_collateral"]) : "-");
            $tpl->addDataItem("CONF_PAY_COST.FIELD_conf_pay_cost", $t_cost_data["cost"]["required"] ? $this->txt->display("active" . $row_data["confirm_payment_cost"]) : "-");
            $tpl->addDataItem("CONF_PAY_DELIV.FIELD_conf_pay_delivery", $t_cost_data["delivery"]["required"] ? $this->txt->display("active" . $row_data["confirm_payment_delivery"]) : "-");

            $t_expiration_date = $row_data["expiration_date"];
            if ($t_expiration_date == IsicDate::EMPTY_DATE) {
                $last_card = $this->isic_common->getUserLastCard($row_data["person_number"], $row_data["school_id"], $row_data["type_id"]);
                $t_expiration_date = $this->isic_common->getCardExpiration($row_data["type_id"], $last_card ? $last_card["expiration_date"] : "", true);
            }
            $tpl->addDataItem("EXPIRATION_DATE.FIELD_expiration_date", date("d.m.Y", strtotime($t_expiration_date)));
        }

        if ($action == "add") {
            $tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("button_add_appl"));
            $tpl->addDataItem("INIT_FIELDS_ON_LOAD", $write ? 0 : 1);
        } else {
            if ($this->user_type == $this->user_type_admin) {
                if ($row_data["state_id"] != $this->a_state_processed) {
                    if ($action == "reject") {
                        $tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("reject_save"));
                    } else {
                        $tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("button_mod_appl"));
                    }
                    if ($row_data["state_id"] != $this->a_state_rejected) {
                        $tpl->addDataItem("REJECT.BUTTON", $this->txt->display("reject"));
                    }
                    if ($row_data["state_id"] != $this->a_state_admin_confirm) {
                        $tpl->addDataItem("CONFIRM_ADMIN.BUTTON", $this->txt->display("confirm"));
                    }
                    if (!$row_data["confirm_payment_collateral"] && $t_cost_data["collateral"]["required"]) {
                        $tpl->addDataItem("CONF_PAY_COLL.BUTTON", $this->txt->display("confirm_payment_collateral"));
                    }
                    if (!$row_data["confirm_payment_cost"] && $t_cost_data["cost"]["required"]) {
                        $tpl->addDataItem("CONF_PAY_COST.BUTTON", $this->txt->display("confirm_payment_cost"));
                    }
                    if ($action == 'payment') {
                        $tpl->addDataItem('BANK_PAYMENT.APPL_ID', $application);
                        $tpl->addDataItem('BANK_PAYMENT.PAYMENT_TYPE', $this->vars['payment_type']);
                    }
                }
            }
        }

        $hidden .= IsicForm::getHiddenField('action', $this->vars['payment_type'] ? $this->vars['payment_type'] : $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('appl_id', $application);
        if ($pic_resize_required) {
            $hidden .= IsicForm::getHiddenField('pic_resize', 'true');
            $hidden .= IsicForm::getHiddenField('pic_name', $pic_filename);
        } else if ($this->vars['pic'] && $action == 'add') {
            $hidden .= IsicForm::getHiddenField('pic_name', $pic_filename);
        }
        if ($action == "reject") {
            $hidden .= IsicForm::getHiddenField('confirm_reject', '1');
        }
        $hidden .= IsicForm::getHiddenField('use_user_pic', $use_user_pic);
        $tpl->addDataItem("HIDDEN", $hidden);
        //$tpl->addDataItem("SELF", $general_url);
        $tpl->addDataItem("SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("appl_id", "action", "person_number", "type_id", "school_id")));
        $tpl->addDataItem("BACK", $this->txt->display("discontinue"));
        //$tpl->addDataItem("URL_BACK", $general_url_list);
        $tpl->addDataItem("URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("appl_id", "action", "info")));
        $tpl->addDataItem("URL_BACK_CONFIRM", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "appl_id=" . $application, array("appl_id", "action", "info")));
        return $tpl->parse();
    }

    private function savePayment($paymentType, $check_data, $t_cost_data)
    {
        $applData = array(
            'confirm_payment_' . $paymentType => 1,
            $paymentType . '_sum' => $t_cost_data[$paymentType]["sum"],
            "currency" => $t_cost_data["currency"]
        );

        switch ($paymentType) {
            case 'cost':
                $this->isic_payment->setPaymentCost($check_data, $this->getPaymentInfo($paymentType, $t_cost_data));
                if ($t_cost_data['compensation']['sum'] > 0) {
                    $this->isic_payment->setPaymentCompensation($check_data, $this->getPaymentInfo('compensation', $t_cost_data));
                    $applData['compensation_sum'] = $t_cost_data['compensation']['sum'];
                }
                break;
            case 'collateral':
                $this->isic_payment->setPaymentCollateral($check_data, $this->getPaymentInfo($paymentType, $t_cost_data));
                break;
            case 'delivery':
                $this->isic_payment->setPaymentDelivery($check_data, $this->getPaymentInfo($paymentType, $t_cost_data));
                if ($t_cost_data['compensation_delivery']['sum'] > 0) {
                    $this->isic_payment->setPaymentCompensationDelivery($check_data, $this->getPaymentInfo('compensation_delivery', $t_cost_data));
                    $applData['compensation_sum_delivery'] = $t_cost_data['compensation_delivery']['sum'];
                }
                break;
        }

        $this->isicDbApplications->updateRecord($check_data['id'], $applData);
    }

    private function getPaymentInfo($costType, $t_cost_data)
    {
        $payment_info = array(
            "prev_id" => 0,
            "deposit_id" => 0,
            "compensation_id" => ($costType == 'compensation') ?
                $t_cost_data['compensation']['id'] :
                (($costType == 'compensation_delivery') ? $t_cost_data['compensation_delivery']['id'] : 0),
            "free" => 0,
            "payment_sum" => $t_cost_data[$costType]["sum"],
            "currency" => $t_cost_data["currency"],
            "actual_payment_date" => $this->getActualPaymentDate(),
            'payment_method' => $this->vars['payment_method'] ? $this->vars['payment_method'] : 0,
            'bank_id' => $this->getPaymentBank(),
        );
        return $payment_info;
    }

    private function getActualPaymentDate()
    {
        return $this->vars['payment_method'] == $this->isicDbPayments->getMethodExternal() ?
            IsicDate::getDateFormattedFromEuroToDb($this->vars["actual_payment_date"]) :
            IsicDate::EMPTY_DATE;
    }

    private function getPaymentBank()
    {
        return ($this->vars['payment_method'] == $this->isicDbPayments->getMethodExternal() && $this->vars['bank_id']) ?
            $this->vars['bank_id'] :
            0;
    }

    /**
     * Deletes application record from table
     *
     * @param int $applicatin application id
     * @param bool $redirect_list if true then redirects to general list view
     * @return redirect to a listview page
     */
    function deleteApplication($application, $redirect_list = false)
    {
        if ($this->checkAccess() == false) return "";
        if ($redirect_list) {
            $general_url_list = $this->isic_common->getGeneralUrlByTemplate($this->isic_common->template_application_list);
        } else {
            $general_url_list = processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "", array("appl_id", "action"));
        }
        $general_url_list = str_replace("&amp;", "&", $general_url_list) . "&nocache=true";

        $check_data = $this->isicDbApplications->getRecord($application);
        if ($check_data) {
            if ($this->isic_common->canDeleteApplication($check_data) && $check_data["exported"] == "0000-00-00 00:00:00") {
                $this->isicDbApplications->deleteRecord($application);
                redirect($general_url_list . "&info=delete");
            } else {
                redirect($general_url_list . "&error=delete");
            }
        }
        redirect($general_url_list);
    }

    /**
     * Redirects to listview page with given action
     *
     * @param string $action action (add, modify, delete)
     */
    function redirectWithAction($action = 'add')
    {
        $general_url_list = $this->isic_common->getGeneralUrlByTemplate($this->isic_common->template_application_list);
        redirect($general_url_list . "&nocache=true&action=" . $action);
    }

    /**
     * Check does the active user have access to the page/form
     *
     * @access private
     * @return boolean
     */
    function checkAccess()
    {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Returns module parameters array
     *
     * @return array module parameters
     */
    function getParameters()
    {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Creates array of module parameter values for content admin
     *
     * @return array module parameters
     */
    function moduleOptions()
    {
        $sq = new sql;
        //$txt = new Text($this->language, $this->translation_module_default);

        $list = array();
        $list["0"] = $this->txt->display("all");
        $r = &$this->db->query('SELECT * FROM `module_isic_application_state` ORDER BY `name`');
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }

        $list2 = array();
        $list2[""] = $this->txt->display("choose_form_type");
        $list2[1] = $this->txt->display("form_type1");
        $list2[2] = $this->txt->display("form_type2");
        $list2[3] = $this->txt->display("form_type3");

        // ####
        return array(
            $this->txt->display("list_type"), "select", $list,
            $this->txt->display("form_type"), "select", $list2
        );
        // name, type, list
    }

    private function getUserDataFromEhis($personNumber, $schoolId, $cardTypeId)
    {
        $personNumber = trim($personNumber);
        if (strlen($personNumber) != 11) {
            return null;
        }
        if (!$this->isicDbCardTypes->isExternalCheckNeeded($cardTypeId)) {
            return null;
        }
        if (!$this->isicDbSchools->isExternalCheckNeeded($schoolId)) {
            return null;
        }

        $profileUserData = $this->isicDbUsers->getRecordByCode($personNumber);
        $this->enableUserExternalDataCheck($profileUserData);
        // performing actual EHIS query
        $this->getEhisUser()->performQueryAndParseResult($personNumber);
        // if user exists, then assigning statuses from EHIS as well
        if ($profileUserData) {
            $this->getEhisUser()->getStatusListFromParsedResult();
        }
        return $this->getUserDataFromParsedResult($profileUserData, $schoolId, $cardTypeId);
    }

    private function getUserDataFromParsedResult($profileUserData, $schoolId, $cardTypeId)
    {
        $userData = $profileUserData ? $profileUserData : array();
        $matchingStatus = $this->getEhisUser()->getEhisStatus()->
        getStatusBySchoolAndCardType(
            $this->getEhisUser()->getParsedResults(),
            $schoolId,
            $cardTypeId
        );
        if ($matchingStatus) {
            $userData['name_first'] = $matchingStatus['person']['name_first'];
            $userData['name_last'] = $matchingStatus['person']['name_last'];
            $userData['class'] = $matchingStatus['status']['class'] ?
                $matchingStatus['status']['class'] : $matchingStatus['status']['course'];
        }
        return $userData;
    }

    private function enableUserExternalDataCheck($userData)
    {
        if ($userData && !$userData['external_status_check_allowed']) {
            $this->isicDbUsers->updateRecord($userData['user'], array('external_status_check_allowed' => 1));
        }
    }

    private function enableUserEhlDataCheck($userData)
    {
        if ($userData && !$userData['ehl_status_check_allowed']) {
            $this->isicDbUsers->updateRecord($userData['user'], array('ehl_status_check_allowed' => 1));
        }
    }

    public function getEhisUser()
    {
        if (!$this->ehisUser) {
            $this->ehisUser = new EhisUser();
        }
        return $this->ehisUser;
    }

    public function getEHLClient()
    {
        if (!$this->ehlClient) {
            $this->ehlClient = new IsicEHLClient();
        }
        return $this->ehlClient;
    }

    /**
     * Check if there are some payments that need to be automatically inserted
     * (this happens in case of compensation being large enough to make the actual payment zero)
     *
     * @param $cost_data
     * @param $appl_data
     */
    private function autoPayments($cost_data, $appl_data)
    {
        if ($cost_data['cost']['required'] &&
            $cost_data['cost']['sum'] == 0 &&
            $cost_data['compensation']['sum'] > 0 &&
            !$appl_data['confirm_payment_cost']
        ) {
            $this->savePayment('cost', $appl_data, $cost_data);
        }
        if ($cost_data['delivery']['required'] &&
            $cost_data['delivery']['sum'] == 0 &&
            $cost_data['compensation_delivery']['sum'] > 0 &&
            !$appl_data['confirm_payment_delivery']
        ) {
            $this->savePayment('delivery', $appl_data, $cost_data);
        }
    }

    /**
     * @param $applRecord
     * @param $userRecord
     */
    protected function updateSpecialOffers($applRecord, $userRecord)
    {
        $newsletterList = array_keys(
            $this->isicDbNewsletters->getNameListByAllowedNewsletters(array($applRecord['type_id']))
        );
        $this->isicDbUsers->enableSpecialOffers($userRecord['user']);

        $this->isicDbNewslettersOrders->updateUserOrders(
            $userRecord['user'],
            $applRecord["id"],
            $newsletterList,
            $this->userid,
            false
        );
    }
}
