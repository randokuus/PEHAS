<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImage.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicForm.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicError.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicExport.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/ElapsedTime.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicListViewSortOrder.php");

class isic_user_status
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
     * @var user type (1 - can view all users from the school his/her usergroup belongs to, 2 - only his/her own users)
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
     * Users that are allowed to access the same users as current user
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
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_user"; //"module_user_status";

    /**
     * Default db-table to use
     *
     * @var string
     * @access protected
     */
    var $table_module_default = "`module_isic_user_status_user`";

    /**
     * Info message match array
     *
     * @var array
     * @access protected
     */
    var $info_message = array(
        "add" => "info_add_status",
        "modify" => "info_modify_status",
        "add_status" => "info_add_status",
        "modify_status" => "info_modify_status",
        "data_saved" => "info_data_saved",
        "delete" => "info_user_deleted",
    );

    var $filterFields = array(
        'name_first' => array('field' => "name_first", 'partial' => true, 'table' => 'module_user_users'),
        'name_last' => array('field' => "name_last", 'partial' => true, 'table' => 'module_user_users'),
        'user_code' => array('field' => "user_code", 'partial' => true, 'table' => 'module_user_users'),
        'stru_unit' => array('field' => "structure_unit", 'partial' => true, 'table' => 'module_user_status_user'),
        'faculty' => array('field' => "faculty", 'partial' => true, 'table' => 'module_user_status_user'),
//        'class' => array('field' => "class", 'partial' => true, 'table' => 'module_user_status_user'),
//        'course' => array('field' => "course", 'partial' => true, 'table' => 'module_user_status_user'),
        'status' => array('field' => "status_id", 'partial' => false, 'table' => 'module_user_status_user'),
        'school' => array('field' => "school_id", 'partial' => false, 'table' => 'module_user_status_user'),
        'region' => array('field' => "region_id", 'partial' => false, 'table' => 'module_isic_school'),
        'active' => array('field' => "active", 'boolean' => true, 'partial' => false, 'table' => 'module_user_status_user'),
    );

    var $listSortFields = array(
        "pic" => "module_user_users.pic",
        "name_first" => "module_user_users.name_first",
        "name_last" => "module_user_users.name_last",
        "user_code" => "module_user_users.user_code",
        "school" => "module_isic_school.name",
        "status" => "module_user_status.name",
        "structure_unit" => "module_user_status_user.structure_unit",
        "faculty" => "module_user_status_user.faculty",
//        "class" => "module_user_status_user.class",
//        "course" => "module_user_status_user.course",
        "active" => "module_user_status_user.active",
    );

    var $listSortFieldDefault = 'name_last';

    /**
     * @var IsicDB_UserStatuses
     */
    var $isicDbUserStatuses = null;

    /**
     * @var IsicDB_Users
     */
    var $isicDbUsers = null;

    /**
     * @var IsicDB_UserGroups
     */
    var $isicDbUserGroups = null;

    /**
     * @var IsicDB_Schools
     */
    var $isicDbSchools = null;

    /**
     * @var IsicDB_UserStatusTypes
     */
    var $isicDbUserStatusTypes = null;

    /**
     * @var IsicDB_CardTypes
     */
    var $isicDbCardTypes = null;

    var $errorFields = false;
    var $id = false;

    /**
     * Class constructor
     *
     * @global $GLOBALS ['site_settings']['template']
     * @global $GLOBALS ['language']
     * @global $GLOBALS ['database']
     */
    function isic_user_status()
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
        $this->isicTemplate = new IsicTemplate('isic_user_status');
        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');

//        $this->allowed_card_types = $this->isic_common->createAllowedCardTypes();
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    /**
     * Main module display function
     *
     * @return string html User content
     */
    function show()
    {
        if ($this->checkAccess() == false) return "";

        $action = @$this->vars["action"];
        $user_status_id = @$this->vars["user_status_id"];

        if (!$this->userid) {
            trigger_error("Module 'Isic User Status' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == $this->isic_common->user_type_user && !$this->user_code) {
            trigger_error("Module 'ISIC User Status' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }
        if ($action == "add") {
            $result = $this->addOrModify(false);
        } else if ($user_status_id && $action == "modify") {
            $result = $this->addOrModify($user_status_id);
        } else if ($user_status_id && $action == "deactivate" && $this->user_type == $this->isicDbUsers->getUserTypeAdmin()) {
            $ok = $this->processActionDeactivate(array($user_status_id));
            $this->redirectIntoDetailView(($ok ? 'info' : 'error') . '=modify');
        } else if ($user_status_id && $action == "delete") {
            $result = $this->delete($user_status_id);
        } else if ($user_status_id && !$action) {
            $result = $this->showDetail($user_status_id);
        } else {
            $result = $this->showList();
        }
        return $result;
    }

    /**
     * Displays list of users statuses
     *
     * @return string html listview of users
     */
    function showList()
    {
        $instanceParameters = '&type=userstatuslist&sort=' . $this->vars['sort'] . '&sort_order=' . $this->vars['sort_order'];
        if ($this->vars['export']) {
            $templateName = 'module_isic_user_status_list_csv.html';
        } else {
            $templateName = 'module_isic_user_status_list.html';
        }
        $this->tpl = $this->isicTemplate->initTemplateInstance($templateName, $instanceParameters);
        if (!$this->processAction() && $this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $this->sqlConditionArray = array();
        $this->urlFilter = '';
        $this->hiddenFields = '';

        $this->start = $this->getStartValue();
        $this->hiddenFields .= IsicForm::getHiddenField('start', $this->start);
        $this->generalUrl = $this->getGeneralUrl();

        $this->assignActionsList();
        $this->assignActiveValue();
        $this->assignFilterValues();
        $this->assignSqlConditionUserType();
        $this->assignSqlCondition();
        $this->assignSqlGroupCondition();
        $this->assignSqlOrderBy();

        if ($this->vars['export']) {
            return $this->showListBodyCsv();
        }
        return $this->showListBody();
    }

    private function showListBody()
    {
        $this->showListTitle();
        $this->showListRows();
        $this->showListPages();
        $this->showListFilterToAdmin();
        $this->showListError();
        $this->showListInfo();

        $this->tpl->addDataItem("URL_GENERAL_PLAIN", $this->generalUrl);
        $this->tpl->addDataItem("URL_GENERAL", $this->generalUrl . $this->urlFilter . "&sort_order=" . $this->sqlSortOrderReverse);
        $this->tpl->addDataItem("URL_ADD", $this->generalUrl . $this->urlFilter . "&sort_order=" . $this->sqlSortOrderReverse . "&action=add");
        $this->tpl->addDataItem("SELF", $this->generalUrl);
        $this->tpl->addDataItem("CONFIRMATION", $this->txt->display("confirmation"));
        return $this->tpl->parse();
    }

    private function showListBodyCsv()
    {
        // removing pic-column for csv
        unset($this->listSortFields['pic']);
        $this->showListTitle();
        // all results for csv
        $this->maxresults = 999999;
        $this->showListRows();
        IsicExport::showCsv($this->tpl->parse(), 'user_status.csv');
    }

    function assignActionsList()
    {
        $actions = array(
            'Deactivate' => $this->txt->display('action_deactivate')
        );
        foreach ($actions as $type => $title) {
            $this->tpl->addDataItem("ACTIONS.TYPE", $type);
            $this->tpl->addDataItem("ACTIONS.TITLE", $title);
        }
    }

    function processActionDeactivate(array $statusIDs)
    {
        $ok = true;
        $st = IsicDB::factory('UserStatuses');
        foreach ($statusIDs as $id) {
            try {
                $st->deactivate($id);
            } catch (Exception $e) {
                $ok = false;
            }
        }
        return $ok;
    }

    function processAction()
    {
        if (!$this->vars['processAction']) {
            return false;
        }
        $processMethod = 'processAction' . $this->vars['processAction'];
        if (method_exists($this, $processMethod)) {
            $processSucceeded = $this->$processMethod((array)@$this->vars['processItems']);
            if ($processSucceeded) {
                $this->vars['info'] = 'modify';
            } else {
                $this->vars['error'] = 'modify';
            }
        }
        return true;
    }

    function showListInfo()
    {
        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $this->tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }
    }

    function showListError()
    {
        switch ($this->vars["error"]) {
            case "modify":
                $this->tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_modify"));
                break;
            case "delete":
                $this->tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_delete"));
                break;
            case "view":
                $this->tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_view"));
                break;
        }
    }

    function showListFilterToAdmin()
    {
        if ($this->user_type != $this->isic_common->user_type_admin) {
            return;
        }

        $fields = $this->getFilterFields();
        foreach ($fields as $key => $val) {
            $this->tpl->addDataItem('SEARCH.FIELD_' . $key, $this->getFieldData($key, $val));
        }
        $this->tpl->addDataItem("SEARCH.SELF", $this->generalUrl);
    }

    function getFieldData($key, $val)
    {
        $fdata["type"] = $val[0];
        $fdata["size"] = $val[1];
        $fdata["cols"] = $val[1];
        $fdata["rows"] = $val[2];
        $fdata["list"] = $val[4];
        $fdata["java"] = $val[5];
        $fdata["class"] = $val[6];

        $f = new AdminFields($key, $fdata);
        $field_data = $f->display($val[3]);
        return $field_data;
    }

    function getFilterFields()
    {
        return array(
            "filter_active" => array("select", 0, 0, $this->vars['filter_active'], $this->getActiveList(), "", "i120"),
            "filter_name_first" => array("textinput", 40, 0, $this->vars["filter_name_first"], "", "", "i120"),
            "filter_name_last" => array("textinput", 40, 0, $this->vars["filter_name_last"], "", "", "i120"),
            "filter_user_code" => array("textinput", 40, 0, $this->vars["filter_user_code"], "", "", "i120"),
            "filter_delivery_addr1" => array("textinput", 40, 0, $this->vars["filter_delivery_addr1"], "", "", "i120"),
            "filter_delivery_addr2" => array("textinput", 40, 0, $this->vars["filter_delivery_addr2"], "", "", "i120"),
            "filter_delivery_addr3" => array("textinput", 40, 0, $this->vars["filter_delivery_addr3"], "", "", "i120"),
            "filter_delivery_addr4" => array("textinput", 40, 0, $this->vars["filter_delivery_addr4"], "", "", "i120"),
            "filter_stru_unit" => array("textinput", 40, 0, $this->vars["filter_stru_unit"], "", "", "i120"),
            "filter_faculty" => array("textinput", 40, 0, $this->vars["filter_faculty"], "", "", "i120"),
//            "filter_class" => array("textinput", 40, 0, $this->vars["filter_class"], "", "", "i120"),
//            "filter_course" => array("textinput", 40, 0, $this->vars["filter_course"], "", "", "i120"),
            "filter_status" => array("select", 40, 0, $this->vars["filter_status"], $this->getStatusList(true), "", "i120"),
            "filter_school" => array("select", 40, 0, $this->vars["filter_school"], $this->getSchoolList(true), "", "i120"),
            "filter_region" => array("select", 40, 0, $this->vars["filter_region"], $this->getRegionList(), "", "i120"),
        );
    }

    function assignActiveValue($field = 'filter_active')
    {
        if (!isset($this->vars[$field])) {
            $this->vars[$field] = 1;
        }
        return $this->vars[$field];
    }

    function getActiveList()
    {
        $list = array();
        $list[2] = $this->txt->display("active2"); // all
        $list[1] = $this->txt->display("active1"); // active
        $list[0] = $this->txt->display("active0"); // non-active
        return $list;
    }

    function showListTitle()
    {
        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);
        $listSortOrder->showTitleFields($this->tpl, $this->txt, $this->generalUrl . $this->urlFilter);
    }

    function showListRows()
    {
        $dbQueryResult = $this->getListQueryResult();
        //echo "<!-- SQL: " . $this->db->show_query() . " -->\n";

        if ($dbQueryResult == false) {
            echo "Database error " . $this->db->error_code() . ": " . $this->db->error_string();
            return;
        }

        if (!$dbQueryResult->num_rows()) {
            $this->tpl->addDataItem("RESULTS", $this->txt->display("results_none"));
            return;
        }

        while ($data = $dbQueryResult->fetch_assoc()) {
            $this->showListRow($data);
        }
    }

    function showListRow($data)
    {
        $urlDetail = $this->generalUrl . "&user_status_id=" . $data["user_status_id"] . $this->urlFilter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $this->sqlSortOrder;
        $urlModify = $urlDetail . "&action=modify";
        $urlDelete = $urlDetail . "&action=delete";
        $this->tpl->addDataItem("DATA.DATA_ID", $data['user_status_id']);
        $this->tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb')));
        $this->tpl->addDataItem("DATA.URL_IMAGE", IsicImage::getPopUpForUrl(IsicImage::getPictureUrl($data['pic'], 'big')));
        $this->tpl->addDataItem("DATA.DATA_ACTIVE", $this->txt->display("active" . $data["status_active"]));
        $this->tpl->addDataItem("DATA.DATA_USER_TYPE_NAME", $data["user_type_name"]);
        $this->tpl->addDataItem("DATA.DATA_NAME_FIRST", $data["name_first"]);
        $this->tpl->addDataItem("DATA.DATA_NAME_LAST", $data["name_last"]);
        $this->tpl->addDataItem("DATA.DATA_BIRTHDAY", IsicDate::getDateFormatted($data["birthday"]));
        $this->tpl->addDataItem("DATA.DATA_USER_CODE", $data["user_code"]);
        $this->tpl->addDataItem("DATA.DATA_STRU_UNIT", $data["status_structure_unit"]);
        $this->tpl->addDataItem("DATA.DATA_FACULTY", $data["status_faculty"]);
        $this->tpl->addDataItem("DATA.DATA_CLASS", $data["status_class"]);
        $this->tpl->addDataItem("DATA.DATA_COURSE", $data["status_course"]);
        $this->tpl->addDataItem("DATA.DATA_STATUS", $data["status_name"]);
        $this->tpl->addDataItem("DATA.DATA_SCHOOL", $data["school_name"]);
        $this->tpl->addDataItem("DATA.URL_DETAIL", $urlDetail);
        if ($this->isic_common->canModifyUser($data) &&
            $this->user_type == $this->isic_common->user_type_admin &&
            $data['status_active']
        ) {
            $this->tpl->addDataItem("DATA.MOD.URL_MODIFY", $urlModify);
        }
        if ($this->isic_common->canDeleteUser($data["user_code"])) {
            $this->tpl->addDataItem("DATA.DEL.URL_DELETE", "javascript:del('" . $urlDelete . "');");
        }
    }

    function showListPages()
    {
        $results = $this->getListRowsCount();

        $disp = ereg_replace("{NR}", "$results", $this->txt->display("results"));
        if ($results >= $this->maxresults) {
            $end = $this->start + $this->maxresults;
        } else {
            $end = $this->start + $results;
        }
        if ($end == 0) {
            $start0 = 0;
        } else {
            $start0 = $this->start + 1;
        }
        $disp = str_replace("{DISP}", $start0 . "-$end", $disp);
        $this->tpl->addDataItem("RESULTS", $disp);

        $url = $this->generalUrl . $this->urlFilter . "&sort=" . $this->vars["sort"] . "&sort_order=" . $this->sqlSortOrder;
        $this->tpl->addDataItem("PAGES", resultPages($this->start, $results, $url, $this->maxresults, $this->txt->display("prev"), $this->txt->display("next")));
    }

    function getListRowsCount()
    {
        if ($this->user_type == $this->isic_common->user_type_admin) {
            $res = $this->getListRowsCountResultForAdmin();
        } elseif ($this->user_type == $this->isic_common->user_type_user) {
            $res = $this->getListRowsCountResultForUser();
        }
        $data = $res->fetch_assoc();
        return $data['users_total'];
    }

    function getListRowsCountResultForAdmin()
    {
        return $this->db->query("
            SELECT STRAIGHT_JOIN
                COUNT(*) AS users_total
            FROM
                `module_user_users`,
                `module_user_status_user`,
                `module_isic_school`,
                `module_user_status`
            WHERE
                (`module_user_users`.`user_type` = 2 OR `module_user_users`.`user` = !) AND
                `module_user_users`.`user` = `module_user_status_user`.`user_id` AND
                `module_isic_school`.`id` = `module_user_status_user`.`school_id` AND
                `module_user_status`.`id` = `module_user_status_user`.`status_id` AND
                `module_user_status_user`.`school_id` IN (!@)
                !
                !",
            $this->userid,
            IsicDB::getIdsAsArray($this->isic_common->allowed_schools),
            $this->sqlCondition,
            $this->sqlGroupCondition
        );
    }

    function getListRowsCountResultForUser()
    {
        return $this->db->query("
            SELECT
                COUNT(*) AS users_total
            FROM
                `module_user_users`,
                `module_user_status_user`,
                `module_isic_school`,
                `module_user_status`
            WHERE
                `module_user_users`.`user` = `module_user_status_user`.`user_id` AND
                `module_isic_school`.`id` = `module_user_status_user`.`school_id` AND
                `module_user_status`.`id` = `module_user_status_user`.`status_id`
                !",
            $this->sqlCondition
        );
    }

    function getListQueryResult()
    {
        if ($this->user_type == $this->isic_common->user_type_admin) {
            return $this->getListQueryResultForAdmin();
        } elseif ($this->user_type == $this->isic_common->user_type_user) {
            return $this->getListQueryResultForUser();
        }
    }

    function getListQueryResultForAdmin()
    {
        return $this->db->query("
            SELECT STRAIGHT_JOIN
                `module_user_users`.*,
                `module_user_users`.`user` AS `id`,
                `module_user_status`.`name` AS `status_name`,
                IF(`module_isic_school`.`id` = !, '', `module_isic_school`.`name`) AS `school_name`,
                `module_user_status_user`.`id` AS `user_status_id`,
                `module_user_status_user`.`faculty` AS `status_faculty`,
                `module_user_status_user`.`class` AS `status_class`,
                `module_user_status_user`.`course` AS `status_course`,
                `module_user_status_user`.`position` AS `status_position`,
                `module_user_status_user`.`structure_unit` AS `status_structure_unit`,
                `module_user_status_user`.`active` AS `status_active`
            FROM
                `module_user_users`,
                `module_user_status_user`,
                `module_isic_school`,
                `module_user_status`
            WHERE
                (`module_user_users`.`user_type` = 2 OR `module_user_users`.`user` = !) AND
                `module_user_users`.`user` = `module_user_status_user`.`user_id` AND
                `module_isic_school`.`id` = `module_user_status_user`.`school_id` AND
                `module_user_status`.`id` = `module_user_status_user`.`status_id` AND
                `module_user_status_user`.`school_id` IN (!@)
                !
                !
            ORDER BY
                ?f !
            LIMIT !, !",
            $this->isicDbSchools->getHiddenSchoolId(),
            $this->userid,
            IsicDB::getIdsAsArray($this->isic_common->allowed_schools),
            $this->sqlCondition,
            $this->sqlGroupCondition,
            $this->sqlOrderBy,
            $this->sqlSortOrder,
            $this->start,
            $this->maxresults
        );
    }

    function getListQueryResultForUser()
    {
        return $this->db->query("
            SELECT
                `module_user_users`.*,
                `module_user_users`.`user` AS `id`,
                `module_user_status_user`.`id` AS `user_status_id`,
                `module_user_status`.`name` AS `status_name`,
                IF(`module_isic_school`.`id` = !, '', `module_isic_school`.`name`) AS `school_name`,
                `module_user_status_user`.`faculty` AS `status_faculty`,
                `module_user_status_user`.`class` AS `status_class`,
                `module_user_status_user`.`course` AS `status_course`,
                `module_user_status_user`.`position` AS `status_position`,
                `module_user_status_user`.`structure_unit` AS `status_structure_unit`,
                `module_user_status_user`.`active` AS `status_active`
            FROM
                `module_user_users`,
                `module_user_status_user`,
                `module_isic_school`,
                `module_user_status`
            WHERE
                `module_user_users`.`user` = `module_user_status_user`.`user_id` AND
                `module_isic_school`.`id` = `module_user_status_user`.`school_id` AND
                `module_user_status`.`id` = `module_user_status_user`.`status_id`
                !
            ORDER BY
                ?f !
            LIMIT !, !",
            $this->isicDbSchools->getHiddenSchoolId(),
            $this->sqlCondition,
            $this->sqlOrderBy,
            $this->sqlSortOrder,
            $this->start,
            $this->maxresults
        );
    }

    function assignSqlOrderBy()
    {
        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);
        $this->hiddenFields .= IsicForm::getHiddenField('sort', $listSortOrder->getSort());
        $this->hiddenFields .= IsicForm::getHiddenField('sort_order', $this->vars["sort_order"]);
        $this->sqlOrderBy = $listSortOrder->getOrderBy();
        $this->vars["sort"] = $listSortOrder->getSort();
        $this->sqlSortOrder = $listSortOrder->getSortOrder();
        $this->sqlSortOrderReverse = $listSortOrder->getSortOrderReverse();
    }

    function assignSqlCondition()
    {
        $this->sqlCondition = implode(" AND ", $this->sqlConditionArray);
        if ($this->sqlCondition) {
            $this->sqlCondition = " AND " . $this->sqlCondition;
        }
    }

    function assignSqlGroupCondition()
    {
        $this->sqlGroupCondition = '';
        if (in_array(1, $this->isic_common->allowed_groups)) {
            return;
        }
        $g_condition = array();
        foreach ($this->isic_common->allowed_groups as $tg) {
            $g_condition[] = "`module_user_status_user`.`group_id` = " . $this->db->quote($tg);
        }
        if (count($g_condition)) {
            $this->sqlGroupCondition = " AND (" . implode(" OR ", $g_condition) . ")";
        } else {
            $this->sqlGroupCondition = ' AND `module_user_users`.`user` = ' . $this->userid;
        }
    }

    function assignFilterValues()
    {
        foreach ($this->filterFields as $filter_field => $field_info) {
            $this->assignFilterValue($filter_field, $this->vars['filter_' . $filter_field], $field_info);
            $this->assignUrlFilterAndHiddenFields($filter_field, $this->vars['filter_' . $filter_field]);
        }
    }

    function assignFilterValue($filter_field, $filter_value, $field_info)
    {
        if ($this->isEmpty($filter_value, $field_info['boolean'])) {
            return;
        }
        $t_condition = $this->db->quote_field_name($field_info['table'] . '.' . $field_info['field']);
        if ($field_info['partial']) {
            $t_condition .= " LIKE " . $this->db->quote("%" . $filter_value . "%");
        } else {
            $t_condition .= " = " . $this->db->quote($filter_value);
        }
        $this->sqlConditionArray[] = $t_condition;

    }

    private function assignUrlFilterAndHiddenFields($filter_field, $filter_value)
    {
        $this->urlFilter .= "&filter_" . $filter_field . "=" . urlencode($filter_value);
        $this->hiddenFields .= IsicForm::getHiddenField('filter_' . $filter_field, $filter_value);
    }

    function isEmpty($field, $boolean = false)
    {
        if ($boolean) {
            return $field == '2';
        } else {
            return $field == "" || $field == "0";
        }
    }

    function assignSqlConditionUserType()
    {
        // restrictions based on user-type
        switch ($this->user_type) {
            case $this->isic_common->user_type_admin:
                // do nothing, other filters will be in place
                break;
            case $this->isic_common->user_type_user:
                $this->sqlConditionArray[] = "`module_user_users`.`user_code` = " . $this->db->quote($this->user_code);
                break;
        }
    }

    function getGeneralUrl()
    {
        $general_url = '';
        $content = (int)@$this->vars['content'];
        if ($content && is_int($content)) {
            $general_url = $_SERVER['PHP_SELF'] . '?content=' . $content;
        }
        return $general_url;
    }

    function getStartValue()
    {
        $start = @$this->vars["start"] + 0;
        if (!$start || !is_int($start)) {
            $start = 0;
        }
        return $start;
    }


    /**
     * Displays detail view of a user
     *
     * @param int $user user id
     * @return string html detailview of a user
     */
    function showDetail($user_status)
    {
        $instanceParameters = '&type=showuserstatus';
        $this->tpl = $this->isicTemplate->initTemplateInstance('module_isic_user_status_show.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $statusData = $this->isicDbUserStatuses->getRecordById($user_status);
        if ($statusData) {
            $userData = $this->isicDbUsers->getRecord($statusData['user_id']);
            $groupData = $this->isicDbUserGroups->getRecord($statusData['group_id']);
            $schoolData = $this->isicDbSchools->getRecord($statusData['school_id']);
            $statusTypeData = $this->isicDbUserStatusTypes->getRecord($statusData['status_id']);
            $addUserData = $this->isicDbUsers->getRecord($statusData['adduser']);
            $modUserData = $this->isicDbUsers->getRecord($statusData['moduser']);
        }

        if ($statusData && $userData) {
            if ($this->isic_common->canViewUserStatus($statusData)) {
                $isicUser = new isic_user();
                foreach ($isicUser->getNewslettersFields($userData["user"]) as $newsletter) {
                    $this->tpl->addDataItem("DATA_NEWSLETTERS.FIELD_NEWSLETTER", $newsletter);
                }
                $this->tpl->addDataItem("DATA_active", $this->txt->display("active" . $statusData["active"]));
                $this->tpl->addDataItem("DATA_name_first", $userData["name_first"]);
                $this->tpl->addDataItem("DATA_name_last", $userData["name_last"]);
                $this->tpl->addDataItem("DATA_user_code", $userData["user_code"]);
                $this->tpl->addDataItem("DATA_birthday", IsicDate::getDateFormatted($userData["birthday"]));
                $this->tpl->addDataItem("DATA_bankaccount", $userData["bankaccount"]);
                $this->tpl->addDataItem("DATA_bankaccount_name", $userData["bankaccount_name"]);
                $this->tpl->addDataItem("DATA_delivery_addr1", $userData["delivery_addr1"]);
                $this->tpl->addDataItem("DATA_delivery_addr2", $userData["delivery_addr2"]);
                $this->tpl->addDataItem("DATA_delivery_addr3", $userData["delivery_addr3"]);
                $this->tpl->addDataItem("DATA_delivery_addr4", $userData["delivery_addr4"]);
                $this->tpl->addDataItem("DATA_email", $userData["email"]);
                $this->tpl->addDataItem("DATA_phone", $userData["phone"]);
                $this->tpl->addDataItem("DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($userData['pic'], 'big'));
                $this->tpl->addDataItem("DATA_group_name", $groupData["name"]);
                $this->tpl->addDataItem("DATA_school_name", $schoolData["name"]);
                $this->tpl->addDataItem("DATA_status_name", $statusTypeData["name"]);
                $this->tpl->addDataItem("DATA_addtime", IsicDate::getDateTimeFormatted($statusData["addtime"]));
                $this->tpl->addDataItem("DATA_adduser", $addUserData["name_first"] . ' ' . $addUserData["name_last"]);
                $this->tpl->addDataItem("DATA_addtype", $this->txt->display('origin_type' . $statusData["addtype"]));
                $this->tpl->addDataItem("DATA_class", $statusData["class"]);
                $this->tpl->addDataItem("DATA_course", $statusData["course"]);
                $this->tpl->addDataItem("DATA_position", $statusData["position"]);
                $this->tpl->addDataItem("DATA_structure_unit", $statusData["structure_unit"]);
                $this->tpl->addDataItem("DATA_faculty", $statusData["faculty"]);
                $this->tpl->addDataItem("DATA_special_offers", $isicUser->getSpecialOffers($userData["special_offers"]));
                if (!$statusData['active']) {
                    $this->tpl->addDataItem("DEACTIVATED.DATA_modtime", IsicDate::getDateTimeFormatted($statusData["modtime"]));
                    $this->tpl->addDataItem("DEACTIVATED.DATA_moduser", $modUserData["name_first"] . ' ' . $modUserData["name_last"]);
                    $this->tpl->addDataItem("DEACTIVATED.DATA_modtype", $this->txt->display('origin_type' . $statusData["modtype"]));
                }

                // showing modify button in case of admin-users and user not being exported
                if ($this->user_type == $this->isic_common->user_type_admin && $statusData['active']) {
                    $this->tpl->addDataItem("MODIFY.MODIFY", $this->txt->display("modify"));
                    $this->tpl->addDataItem("MODIFY.URL_MODIFY", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_status_id=" . $statusData["id"] . "&action=modify", array("user_status_id", 'info')));
                    $this->tpl->addDataItem("DEACTIVATE.URL", htmlspecialchars_decode(processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_status_id=" . $statusData["id"] . "&action=deactivate", array("user_status_id", 'info'))));
                }
            } else {
                $this->redirectIntoListView("error=view");
            }
        }


        if ($this->vars['redirect'] == 'user_detail' && $statusData['user_id']) {
            $this->tpl->addDataItem("BACK", $this->txt->display("back_only"));
            $backUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user')) . '&user_id=' . $statusData['user_id'];
        } else {
            $this->tpl->addDataItem("BACK", $this->txt->display("back"));
            $backUrl = processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("user_status_id", 'info'));
        }

        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $this->tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        $this->tpl->addDataItem("URL_BACK", $backUrl);
        return $this->tpl->parse();
    }

    function getNewsletterNamesByIds($ids)
    {
        $names = array();
        $cardTypeList = $this->isicDbCardTypes->getRecordsByIds($ids);
        foreach ($cardTypeList as $cardType) {
            $names[] = $cardType['name'];
        }
        return implode('<br />', $names);
    }

    function assignStatusAndUserVariables()
    {
        if (!$this->id) {
            return 'add';
        }
        $this->statusData = $this->isicDbUserStatuses->getRecordById($this->id);
        if (!$this->statusData) {
            $this->redirectIntoListView('error=status');
        }

        $this->userData = $this->isicDbUsers->getRecord($this->statusData['user_id']);
        if (!$this->userData) {
            $this->redirectIntoListView('error=user');
        }

        if (!$this->canModifyStatus()) {
            $this->redirectIntoListView('error=modify');
        }
        return 'modify';
    }

    function canModifyStatus()
    {
        return $this->isic_common->canModifyUser($this->userData);
    }

    function redirectIntoListView($param = '')
    {
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&" . $param, array("user_status_id", "action")));
    }

    function redirectIntoDetailView($param = '')
    {
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], $param, array("action")));
    }

    function getUserId()
    {
        if ($this->userData) {
            return $this->userData['user'];
        }
        return $this->vars['user_id'];
    }

    /**
     * Displays add/modify view of a user
     *
     * @param int $user user id
     * @return string html addform for users
     */
    function addOrModify($id)
    {
        $this->id = $this->getUserStatusId($id);
        $action = $this->assignStatusAndUserVariables();
        $user = new isic_user();
        return $user->addUser($this->getUserId(), $action, $this);
    }

    function getUserStatusId($user_status)
    {
        if (!$user_status && $this->vars["user_status_id"]) {
            return $this->vars["user_status_id"];
        }
        if ($this->vars['user_id'] && $this->vars['group_id']) {
            $us = $this->isicDbUserStatuses->getRecordByGroupUser($this->vars['group_id'], $this->vars['user_id']);
            if ($us) {
                return $us['id'];
            }
        }
        return $user_status;
    }

    function isWriteNeeded()
    {
        return 'true' == @$this->vars["write"];
    }

    function showErrorMessage($error)
    {
        if ($error->isError()) {
            $this->tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_user_status_exists"));
        }
    }

    function showInfoMessage($info_message)
    {
        if ($info_message && $this->info_message[$info_message]) {
            $this->tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$info_message]));
        }
    }

    function getStatusData($error)
    {
        if ($this->id && !$error->isError()) {
            return $this->isicDbUserStatuses->getRecordById($this->id);
        } else {
            return $this->vars;
        }
    }

    function getFormFields($data)
    {
        return array(
            "group_id" => array("select", 0, 0, $data["group_id"], $this->getUserGroups(), "onChange=\"getUserStatusData();\"", "", $this->user_type == $this->user_type_admin ? true : false),
            "faculty" => array("textinput", 40, 0, $data["faculty"], "", "", "", true),
//            "class" => array("textinput", 40, 0, $data["class"], "", "", "", true),
//            "course" => array("textinput", 40, 0, $data["course"], "", "", "", true),
            "position" => array("textinput", 40, 0, $data["position"], "", "", "", true),
            "structure_unit" => array("textinput", 40, 0, $data["structure_unit"], "", "", "", true),
        );
    }

    function getUserGroups()
    {
        $list = array();
        $list[0] = $this->txt->display('choose_group');
        $groups = $this->isicDbUserGroups->listAllowedRecords();
        foreach ($groups as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    private function getFormHiddenFields($action)
    {
        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('user_status_id', $this->id);
        return $hidden;
    }

    function getUserList()
    {
        $users = $this->isicDbUsers->listAllowedRecords();
        foreach ($users as $data) {
            $list[$data['user']] = $data['name_last'] . ' ' . $data['name_first'] . ' (' . $data['username'] . ')';
        }
        return $list;
    }

    function getRegionList($all = true)
    {
        if ($all) {
            $list[0] = $this->txt->display('choose_region');
        }
        $dbRegions = IsicDB::factory('Regions');
        $regions = $dbRegions->getRecordsBySchoolIds($this->isic_common->allowed_schools);
        foreach ($regions as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    function getSchoolList($all = true)
    {
        if ($all) {
            $list[0] = $this->txt->display('choose_school');
        }
        $schools = $this->isicDbSchools->listAllowedRecords();
        foreach ($schools as $data) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    function getStatusList($all = true)
    {
        if ($all) {
            $list[0] = $this->txt->display('choose_status');
        }
        $statuses = $this->isicDbUserStatusTypes->listAllowedRecords();
        foreach ($statuses as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    function saveAndRedirectIntoListIfNoError($action)
    {
        $error = $this->checkRequired($action);
        if (!$error->isError()) {
            $this->saveData($action);
            $this->redirectIntoListView('info=' . $action);
        }
        return $error;
    }

    function checkRequired($action)
    {
        $error = new IsicError();
        $error->setEmptyValues(array('', '0'));
        $error->checkRequired($this->vars, $this->isicDbUserStatuses->getRequiredFields($action));

        // first checking if this user with the same user_code already exists
        if (!$error->isError()) {
            $status = $this->isicDbUserStatuses->getRecordByGroupUser(
                $this->vars['group_id'],
                $this->vars['user_id']
            );
            $error->setError($this->isDuplicateStatus($action, $status));
        }
        return $error;
    }

    function isDuplicateStatus($action, $status)
    {
        if ($action == 'modify') {
            return is_array($status) && $status['id'] != $this->id;
        } else if ($action == 'add') {
            return is_array($status);
        }
    }

    function saveData($action)
    {
        if ($action == 'modify') {
            $this->isicDbUserStatuses->updateRecord($this->id, $this->vars);
        } else if ($action == "add") {
            $this->vars['active'] = 1;
            $this->id = $this->isicDbUserStatuses->insertRecord($this->vars);
        }
        return $this->id;
    }

    /**
     * Deletes user status record from table
     *
     * @param int $user_status user status id
     * @return redirect to a listview page
     */
    function delete($user_status)
    {
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&error=delete", array("user_status_id", "action")));
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
        $txt = new Text($this->language, "module_isic_user_status");

        $list = array();
        return array();
        // name, type, list
    }
}
