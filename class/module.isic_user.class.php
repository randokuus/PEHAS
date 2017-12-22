<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImage.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicForm.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicError.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Ehis/EhisUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/ElapsedTime.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImageUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicListViewSortOrder.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicLogger.php");

class isic_user
{
    const CSV_MAX_ROWS = 301;
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
     * List view type
     *
     * @var string (all, ordered, void)
     * @access protected
     */

    var $list_type = "all";

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

    var $csv_import_min_fields = 3;

    /**
     * Fields that will be imported from CSV -- DEPRECATED
     *
     * @var array
     * @access protected
     */
    //var $csv_import_fields = array("user_code", "name_first", "name_last", "email", "phone", "addr1", "addr2", "addr3", "addr4", "position", "class", "stru_unit", "stru_unit2", "staff_number", "bankaccount", "bankaccount_name");

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_user";

    /**
     * Info message match array
     *
     * @var array
     * @access protected
     */
    var $info_message = array(
        "add" => "info_add",
        "modify" => "info_modify",
        "data_saved" => "info_data_saved",
        "delete" => "info_user_deleted",
        "add_status" => "info_add_status",
        "modify_status" => "info_modify_status",
        "ext_uncheck" => "info_external_status_uncheck",
        "ext_check" => "info_external_status_check",
        "ehl_uncheck" => "info_ehl_status_uncheck",
        "ehl_check" => "info_ehl_status_check",
        "data_sync_disallow" => "info_data_sync_disallow",
    );

    var $listSortFields = array(
        "pic" => "module_user_users.pic",
        "name_first" => "module_user_users.name_first",
        "name_last" => "module_user_users.name_last",
        "user_code" => "module_user_users.user_code",
        "email" => "module_user_users.email",
    );
    var $listSortFieldDefault = 'name_last';

    var $users;
    var $userStatuses;

    /**
     * @var IsicDB_UserStatuses
     */
    var $isicDbUserStatuses = null;
    /**
     * @var IsicDB_Users
     */
    var $isicDbUsers = null;
    var $isicDbUserGroups = false;
    var $isicDbSchools = false;
    var $isicDbUserStatusTypes = false;
    var $isicDbCards = false;
    var $isicDbCardTypes = false;

    var $userData = false;
    var $pictureUploader = false;

    var $ehisError = false;
    var $ehlError = false;
    var $user_active_school = 0;

    /**
     * Class constructor
     *
     * @global $GLOBALS ['site_settings']['template']
     * @global $GLOBALS ['language']
     * @global $GLOBALS ['database']
     */

    function isic_user()
    {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->txt = new Text($this->language, "module_isic_user");
        $this->txtf = new Text($this->language, "output");
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];
        $this->user_active_school = $GLOBALS["user_data"][9];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbUserStatuses = IsicDB::factory('UserStatuses');
        $this->isicDbUserStatusTypes = IsicDB::factory('UserStatusTypes');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbUserGroups = IsicDB::factory('UserGroups');
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbCardTypes = IsicDB::factory('CardTypes');
        $this->isicDbNewsletters = IsicDB::factory('Newsletters');
        $this->isicDbNewslettersOrders = IsicDB::factory('NewslettersOrders');

        // assigning common methods class
        $this->isic_common = IsicCommon::getInstance();
        $this->isicTemplate = new IsicTemplate('isic_user');


        $this->isicUser = new IsicUser($this->userid);
        $this->user_type_admin = $this->isic_common->user_type_admin;
        $this->user_type_user = $this->isic_common->user_type_user;

        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->allowed_groups = $this->isic_common->allowed_groups;
        $this->allowed_card_types_view = $this->isicUser->getAllowedCardTypesForView();
        $this->ehisError = new IsicError();
        $this->ehlError = new IsicError();
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
        $step = @$this->vars["step"];
        $user_id = @$this->vars["user_id"];

        if (!$this->userid) {
            trigger_error("Module 'Isic User' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == $this->isic_common->user_type_user && !$this->user_code) {
            trigger_error("Module 'ISIC User' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }

        if ($action == "add") {
            $result = $this->addUser(false, $action);
        } else if ($user_id && $action == "modify") {
            $result = $this->addUser($user_id, $action);
        } else if ($user_id && $action == "delete") {
            $result = $this->deleteUser($user_id);
        } else if ($action == "addmass") {
            $result = $this->addUserMass($action, $step);
        } else if ($action == 'active_school') {
            $this->setActiveSchool();
        } else if ($user_id && ($action == 'ext_check' || $action == 'ext_uncheck')) {
            $result = $this->setExternalCheck($user_id, $action);
        } else if ($user_id && ($action == 'ehl_check' || $action == 'ehl_uncheck')) {
            $result = $this->setEhlCheck($user_id, $action);
        } else if ($user_id && ($action == 'data_sync_disallow')) {
            $result = $this->setDataSyncAllowed($user_id, $action);
        } else if ($user_id && !$action) {
            $result = $this->showUser($user_id);
        } else {
            $result = $this->showUserList();
        }
        return $result;
    }

    /**
     * Displays list of users
     *
     * @return string html listview of users
     */

    function showUserList()
    {
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];

        // list available user actions
        $processActions = array(// 'Deactivate' => $this->txt->display('deactivate_users')
        );

        // process an action first
        if (@$this->vars['processAction']) {
            $processMethod = 'processAction' . $this->vars['processAction'];
            if (method_exists($this, $processMethod)) {
                $processSucceeded = $this->$processMethod((array)@$this->vars['processItems']);
                $this->vars['error'] = $processSucceeded ? 'none' : 'modify';
            }
        }

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $general_url_plain = $general_url;

        if (!$start) {
            $start = 0;
        }

        if ($this->module_param["isic_user"]) {
            $this->list_type = $this->module_param["isic_user"];
        } else {
            $this->list_type = 'all';
        }

        $instanceParameters = '&type=userlist&sort=' . $this->vars['sort'] . '&sort_order=' . $this->vars['sort_order'];
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_user_list.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        // #################################
        $ff_fields = array(
            "name_first", "name_last", "user_code", "stru_unit", "class"
        );

        $ff_fields_partial = array(
            "name_first", "name_last", "user_code", "stru_unit", "class"
        );

        $condition = array();
        $hidden = '';
        $url_filter = '';
        for ($f = 0; $f < sizeof($ff_fields); $f++) {
            if ($this->vars["filter_" . $ff_fields[$f]] != "" && $this->vars["filter_" . $ff_fields[$f]] != "0") {
                if (in_array($ff_fields[$f], $ff_fields_partial)) {
                    $condition[] = $this->db->quote_field_name("module_user_users." . $ff_fields[$f]) . " LIKE " . $this->db->quote("%" . $this->vars["filter_" . $ff_fields[$f]] . "%");
                } else {
                    $condition[] = $this->db->quote_field_name("module_user_users." . $ff_fields[$f]) . " = " . $this->db->quote($this->vars["filter_" . $ff_fields[$f]]);
                }
                $url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($this->vars["filter_" . $ff_fields[$f]]);
                $hidden .= IsicForm::getHiddenField('filter_' . $ff_fields[$f], $this->vars["filter_" . $ff_fields[$f]]);
            }
        }

        // different view-filters (so called list_types)
        switch ($this->list_type) {
            case "all":
                // do nothing
                break;
            case "active":
                $condition[] = "`module_user_users`.`active` = 1";
                break;
        }

        // restrictions based on user-type
        switch ($this->user_type) {
            case $this->isic_common->user_type_admin: // admin
                // do nothing, other filters will be in place
                break;
            case $this->isic_common->user_type_user: // regular
                $condition[] = "`module_user_users`.`user_code` IN (" .
                    implode(',', $this->isic_common->getArrayQuoted($this->isic_common->getCurrentUserCodeList())) .
                    ")";
                break;
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql = " AND " . $condition_sql;
        }

        // group condition
        //$g_condition_sql = $this->getSqlGroupCondition();

        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);

        $hidden .= IsicForm::getHiddenField('sort', $listSortOrder->getSort());
        $hidden .= IsicForm::getHiddenField('sort_order', $this->vars["sort_order"]);
        $hidden .= IsicForm::getHiddenField('start', $start);

        if ($this->user_type == $this->isic_common->user_type_admin) {
            $userIds = $this->getUserIdsByGroups();

            $res =& $this->db->query("
                SELECT
                    `module_user_users`.*,
                    `module_user_users`.`user` AS `id`
                FROM
                    `module_user_users`
                WHERE
                    (`module_user_users`.`user` IN (!@) AND `module_user_users`.`user_type` = ! OR `module_user_users`.`user` = !)
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                IsicDB::getIdsAsArray($userIds),
                $this->isic_common->user_type_user,
                $this->userid,
                $condition_sql,
                $listSortOrder->getOrderBy(),
                $listSortOrder->getSortOrder(),
                $start,
                $this->maxresults
            );
            //echo "<!-- " . $this->db->show_query() . " -->\n";
        } elseif ($this->user_type == $this->isic_common->user_type_user) {
            $res =& $this->db->query("
                SELECT
                    `module_user_users`.*,
                    `module_user_users`.`user` AS `id`
                FROM
                    `module_user_users`
                WHERE
                    1 = 1
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                $condition_sql,
                $listSortOrder->getOrderBy(),
                $listSortOrder->getSortOrder(),
                $start,
                $this->maxresults
            );
        }

//        echo "<!-- SQL: " . $this->db->show_query() . " -->\n";

        if ($res !== false) {
            $user_count = $res->num_rows();
            if ($user_count == 1 && $this->user_type == $this->isic_common->user_type_user) {
                $data = $res->fetch_assoc();
                //return $this->addUser($data["user"], "modify");
                return $this->showUser($data["user"]);
            } elseif ($user_count) {
                while ($data = $res->fetch_assoc()) {
                    $tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb')));
                    $tpl->addDataItem("DATA.URL_IMAGE", IsicImage::getPopUpForUrl(IsicImage::getPictureUrl($data['pic'], 'big')));
                    $tpl->addDataItem("DATA.DATA_ID", $data['user']);
                    $tpl->addDataItem("DATA.DATA_ACTIVE", $this->txt->display("active" . $data["active"]));
                    $tpl->addDataItem("DATA.DATA_USER_TYPE_NAME", $data["user_type_name"]);
                    $tpl->addDataItem("DATA.DATA_NAME_FIRST", $data["name_first"]);
                    $tpl->addDataItem("DATA.DATA_NAME_LAST", $data["name_last"]);
                    $tpl->addDataItem("DATA.DATA_BIRTHDAY", IsicDate::getDateFormatted($data["birthday"]));
                    $tpl->addDataItem("DATA.DATA_USER_CODE", $data["user_code"]);
                    $tpl->addDataItem("DATA.DATA_EMAIL", $data["email"]);
                    $tpl->addDataItem("DATA.URL_DETAIL", $general_url . "&user_id=" . $data["user"] . $url_filter . "&sort=" . $listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder());
                    if ($this->isic_common->canModifyUser($data)) {
                        $tpl->addDataItem("DATA.MOD.URL_MODIFY", $general_url . "&user_id=" . $data["user"] . $url_filter . "&sort=" . $listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder() . "&action=modify");
                    }
                    if ($this->isic_common->canDeleteUser($data["user_code"])) {
                        $tpl->addDataItem("DATA.DEL.URL_DELETE", "javascript:del('" . $general_url . "&user_id=" . $data["user"] . $url_filter . "&sort=" . $listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder() . "&action=delete" . "');");
                    }
                }
            } else {
                $tpl->addDataItem("RESULTS", $this->txt->display("results_none"));
            }
        } else {
            echo "Database error " . $this->db->error_code() . ": " . $this->db->error_string();
        }

        $res->free();

        // page listing
        if ($this->user_type == $this->isic_common->user_type_admin) {
            $res =& $this->db->query("
                SELECT
                    COUNT(`module_user_users`.`user`) AS users_total
                FROM
                    `module_user_users`
                WHERE
                    (`module_user_users`.`user` IN (!@) AND `module_user_users`.`user_type` = ! OR `module_user_users`.`user` = !)
                    !
                ",
                IsicDB::getIdsAsArray($userIds),
                $this->isic_common->user_type_user,
                $this->userid,
                $condition_sql
            );
        } elseif ($this->user_type == $this->isic_common->user_type_user) {
            $res =& $this->db->query("
                SELECT
                    COUNT(*) AS users_total
                FROM
                    `module_user_users`
                WHERE
                    1 = 1
                    !",
                $condition_sql
            );
        }

        $data = $res->fetch_assoc();
        $total = $results = $data["users_total"];

        $disp = ereg_replace("{NR}", "$total", $this->txt->display("results"));
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

        $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . $url_filter . "&sort=" . $listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder, $this->maxresults, $this->txt->display("prev"), $this->txt->display("next")));

        // ####

        switch ($this->vars["error"]) {
            case "none":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_none"));
                break;
            case "modify":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_modify"));
                break;
            case "delete":
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_delete"));
                break;
            case "view":
                //$tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("error_view"));
                break;
        }

        // filter fields are only shown to admin-user
        if ($this->user_type == $this->isic_common->user_type_admin) {
            $fields = array(
                "filter_active" => array("select", 0, 0, $this->vars["filter_active"], "", "", ""),
                "filter_name_first" => array("textinput", 40, 0, $this->vars["filter_name_first"], "", "", ""),
                "filter_name_last" => array("textinput", 40, 0, $this->vars["filter_name_last"], "", "", ""),
                "filter_user_code" => array("textinput", 40, 0, $this->vars["filter_user_code"], "", "", ""),
                "filter_delivery_addr1" => array("textinput", 40, 0, $this->vars["filter_delivery_addr1"], "", "", ""),
                "filter_delivery_addr2" => array("textinput", 40, 0, $this->vars["filter_delivery_addr2"], "", "", ""),
                "filter_delivery_addr3" => array("textinput", 40, 0, $this->vars["filter_delivery_addr3"], "", "", ""),
                "filter_delivery_addr4" => array("textinput", 40, 0, $this->vars["filter_delivery_addr4"], "", "", ""),
                "filter_stru_unit" => array("textinput", 40, 0, $this->vars["filter_stru_unit"], "", "", ""),
                "filter_class" => array("textinput", 40, 0, $this->vars["filter_class"], "", "", ""),
            );

            // active selection
            $list = array();
            for ($i = 2; $i >= 0; $i--) {
                $list[$i] = $this->txt->display("active" . $i);
            }
            $fields["filter_active"][4] = $list;

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
            $tpl->addDataItem("SEARCH.SELF", $general_url);
        }

        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        $listSortOrder->showTitleFields($tpl, $this->txt, $general_url . $url_filter);
        $tpl->addDataItem("URL_GENERAL_PLAIN", $general_url_plain);
        $tpl->addDataItem("URL_GENERAL", $general_url . $url_filter);
        $tpl->addDataItem("URL_ADD", $general_url . $url_filter . "&action=add");
        $tpl->addDataItem("URL_IMPORT", $general_url . $url_filter . "&action=addmass");
        $tpl->addDataItem("SELF", $general_url);

        $tpl->addDataItem("CONFIRMATION", $this->txt->display("confirmation"));
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("ACTIONS", $processActions);

        // ####
        return $tpl->parse();
    }

    private function getSqlGroupCondition()
    {
        $g_condition_sql = '';
        $g_condition = array();
        foreach ($this->allowed_groups as $tg) {
            $g_condition[] = "CONCAT(',', `module_user_users`.`ggroup`, ',') LIKE '%," . mysql_escape_string($tg) . ",%'";
        }
        if (count($g_condition)) {
            $g_condition_sql = " AND (" . implode(" OR ", $g_condition) . ")";
        } else {
            $g_condition_sql = ' AND `module_user_users`.`user` = ' . $this->userid;
        }
        return $g_condition_sql;
    }


    private function getUserIdsByGroups()
    {
        $list = array();
        $res =& $this->db->query("
            SELECT
                DISTINCT(`module_user_status_user`.`user_id`) AS `user_id`
            FROM
                `module_user_status_user`
            WHERE
                `module_user_status_user`.`active` = 1 AND
                `module_user_status_user`.`group_id` IN (!@) AND
                `module_user_status_user`.`school_id` IN (!@)
            ",
            IsicDB::getIdsAsArray($this->usergroups),
            IsicDB::getIdsAsArray($this->isic_common->allowed_schools)
        );
        while ($data = $res->fetch_assoc()) {
            $list[] = $data['user_id'];
        }
        return $list;
    }

    public function getNewslettersFields(array $user)
    {
        $return = array();
        $newslettersList = $this->isicDbNewsletters->getAllActiveNewsletters();
        foreach ($newslettersList as $newsletterData) {
            $newsletterId = $newsletterData["id"];
            $fdata["type"] = "checkboxm2";
            $fdata["size"] = 0;
            $fdata["cols"] = 0;
            $fdata["rows"] = 0;
            $fdata["list"] = array($newsletterId => $newsletterData["name"]);
            $f = new AdminFields("person_newsletter_" . $newsletterId, $fdata);
            $newsletterValue = $this->isicDbNewslettersOrders->isNewsletterInUserOrder($newsletterId, $user) ? $newsletterId : "";
            $return[] = str_replace("[]", "", $f->display($newsletterValue));
        }
        return $return;
    }

    /**
     * Displays detail view of a user
     *
     * @param int $user user id
     * @return string html detailview of a user
     */
    function showUser($user)
    {
        $instanceParameters = '&type=showuser';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_user_show.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        $data = $this->isicDbUsers->getRecord($user);
        if ($data) {
            if ($this->isic_common->canViewUser($data)) {
                $canModifyUser = $this->isic_common->canModifyUser($data);
                foreach ($this->getNewslettersFields($data["user"]) as $newsletter) {
                    $tpl->addDataItem("DATA_NEWSLETTERS.FIELD_NEWSLETTER", $newsletter);
                }
                $tpl->addDataItem("DATA_active", $this->txt->display("active" . $data["active"]));
                $tpl->addDataItem("DATA_name_first", $data["name_first"]);
                $tpl->addDataItem("DATA_name_last", $data["name_last"]);
                $tpl->addDataItem("DATA_user_code", $data["user_code"]);
                $tpl->addDataItem("DATA_birthday", IsicDate::getDateFormatted($data["birthday"]));
                $tpl->addDataItem("DATA_bankaccount", $data["bankaccount"]);
                $tpl->addDataItem("DATA_bankaccount_name", $data["bankaccount_name"]);
                $tpl->addDataItem("DATA_delivery_addr1", $data["delivery_addr1"]);
                $tpl->addDataItem("DATA_delivery_addr2", $data["delivery_addr2"]);
                $tpl->addDataItem("DATA_delivery_addr3", $data["delivery_addr3"]);
                $tpl->addDataItem("DATA_delivery_addr4", $data["delivery_addr4"]);
                $tpl->addDataItem("DATA_email", $data["email"]);
                $tpl->addDataItem("DATA_phone", $data["phone"]);
                $tpl->addDataItem("DATA_external_status_check_allowed", $this->txt->display("active" . $data["external_status_check_allowed"]));
                $tpl->addDataItem("DATA_ehl_status_check_allowed", $this->txt->display("active" . $data["ehl_status_check_allowed"]));
                $tpl->addDataItem("DATA_data_sync_allowed", $this->txt->display("active" . $data["data_sync_allowed"]));
                $tpl->addDataItem("DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($data['pic'], 'big'));
                $tpl->addDataItem("DATA_special_offers", $this->getSpecialOffers($data["special_offers"]));

                if (($canModifyUser)) {
                    $tpl->addDataItem("MODIFY.MODIFY", $this->txt->display("modify"));
                    $tpl->addDataItem("MODIFY.URL_MODIFY", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=modify&redirect=user_detail", array("user_id", 'action', 'info')));
                }

                if ($this->isicDbUsers->isCurrentUserAdmin() && $this->isicDbUsers->isUserCurrentUser($data)) {
                    $tpl->addDataItem("APPL_CONFIRMATION_MAILS.TEXT", $this->txt->display("active" . $data["appl_confirmation_mails"]));
                }

                if ($this->canSetExternalCheck($data, 'ext_check')) {
                    $tpl->addDataItem("EXT_CHECK.TITLE", $this->txt->display("allow_external_status_check"));
                    $tpl->addDataItem("EXT_CHECK.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=ext_check", array("user_id", 'action', 'info')));
                }

                if ($this->canSetExternalCheck($data, 'ext_uncheck')) {
                    $tpl->addDataItem("EXT_UNCHECK.TITLE", $this->txt->display("disallow_external_status_check"));
                    $tpl->addDataItem("EXT_UNCHECK.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=ext_uncheck", array("user_id", 'action', 'info')));
                }

                if ($this->canSetEhlCheck($data, 'ehl_check')) {
                    $tpl->addDataItem("EHL_CHECK.TITLE", $this->txt->display("allow_ehl_status_check"));
                    $tpl->addDataItem("EHL_CHECK.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=ehl_check", array("user_id", 'action', 'info')));
                }

                if ($this->canSetEhlCheck($data, 'ehl_uncheck')) {
                    $tpl->addDataItem("EHL_UNCHECK.TITLE", $this->txt->display("disallow_ehl_status_check"));
                    $tpl->addDataItem("EHL_UNCHECK.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=ehl_uncheck", array("user_id", 'action', 'info')));
                }

                if ($this->canSetDataSyncAllowed($data, 'data_sync_disallow')) {
                    $tpl->addDataItem("DATA_SYNC_DISALLOW.TITLE", $this->txt->display("disallow_data_sync"));
                    $tpl->addDataItem("DATA_SYNC_DISALLOW.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&user_id=" . $data["user"] . "&action=data_sync_disallow", array("user_id", 'action', 'info')));
                }

                $this->showUserStatusList($data, $canModifyUser, $tpl);
                if ($this->user_type == $this->isic_common->user_type_admin) {
                    $tpl->addDataItem("ADD_STATUS.TITLE", $this->txt->display("add_status"));
                    $tpl->addDataItem("ADD_STATUS.URL", $this->isic_common->getGeneralUrlByTemplate($this->isicTemplate->getModuleTemplateId('content_isic_user_status')) . "&user_id=" . $data["user"] . "&action=add&redirect=user_detail");
                }
            } else {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "error=view", array("user_id", 'info')));
            }
        }
        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        if ($this->user_type == $this->isic_common->user_type_admin) {
            $tpl->addDataItem("BACK.TITLE", $this->txt->display("back"));
            $tpl->addDataItem("BACK.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("user_id", "info")));
        }
        return $tpl->parse();
    }

    public function getSpecialOffers($selected)
    {
        $params = array('checkboxm2', 0, 0, $selected, $this->getSpecialOfferTypes(), '', 'special_offer', true, 'special_offers_help');
        $form = new IsicForm(null, $this->txt, 'add', null, null);
        return $form->getFieldData('special_offers', $params);
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

    function canSetExternalCheck($userData, $action)
    {
        if (!in_array($userData['user_code'], $this->isic_common->getCurrentUserCodeList())) {
            return false;
        }
        switch ($action) {
            case 'ext_check':
                if (!$userData['external_status_check_allowed']) {
                    return true;
                }
                break;
            case 'ext_uncheck':
                if ($userData['external_status_check_allowed']) {
                    return !$this->hasActiveCardsDependingOnExternalStatusCheck($userData);
                }
                break;
            default:
                break;
        }
        return false;
    }

    function canSetEhlCheck($userData, $action)
    {
        if (!in_array($userData['user_code'], $this->isic_common->getCurrentUserCodeList())) {
            return false;
        }
        switch ($action) {
            case 'ehl_check':
                if (!$userData['ehl_status_check_allowed']) {
                    return true;
                }
                break;
            case 'ehl_uncheck':
                if ($userData['ehl_status_check_allowed']) {
                    return !$this->hasActiveCardsDependingOnEhlStatusCheck($userData);
                }
                break;
            default:
                break;
        }
        return false;
    }

    function canSetDataSyncAllowed($userData, $action)
    {
        if (!in_array($userData['user_code'], $this->isic_common->getCurrentUserCodeList())) {
            return false;
        }
        return $userData['data_sync_allowed'];
    }

    function hasActiveCardsDependingOnExternalStatusCheck($userData)
    {
        $userCards = $this->isicDbCards->getActivatedRecordsByPersonNumber($userData['user_code']);
        foreach ($userCards as $card) {
            $statusTypes = $this->isicDbUserStatusTypes->getRecordsByCardType($card['type_id']);
            foreach ($statusTypes as $statusType) {
                $userStatuses = $this->isicDbUserStatuses->getAllRecordsByStatusUserSchool($statusType['id'], $userData['user'], $card['school_id']);
                foreach ($userStatuses as $userStatus) {
                    $groupData = $this->isicDbUserGroups->getRecord($userStatus['group_id']);
                    if ($groupData['automatic']) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    function hasActiveCardsDependingOnEhlStatusCheck($userData)
    {
        return $this->hasActiveCardsDependingOnExternalStatusCheck($userData);
    }

    private function showUserStatusList($userData, $canModifyUser, $tpl)
    {
        $statusDetailUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user_status'));
        $statusList = $this->isicDbUserStatuses->listRecordsByUser($userData['user']);
        $hiddenSchoolId = $this->isicDbSchools->getHiddenSchoolId();

        foreach ($statusList as $statusData) {
            if ($this->isic_common->canViewUserStatus($statusData) && $statusData['school_id'] != $hiddenSchoolId) {
                $urlDetail = $statusDetailUrl . "&user_status_id=" . $statusData["id"] . '&redirect=user_detail';
                $urlModify = $urlDetail . "&action=modify&redirect=user_detail";
                $tpl->addDataItem("STATUS.URL_DETAIL", $urlDetail);
                $tpl->addDataItem("STATUS.DATA_active", $this->txt->display("active" . $statusData["active"]));
                $tpl->addDataItem("STATUS.DATA_group", $statusData["group"]);
                $tpl->addDataItem("STATUS.DATA_status", $statusData["title"]);
                $tpl->addDataItem("STATUS.DATA_school", $statusData["school"]);
                $tpl->addDataItem("STATUS.DATA_class", $statusData["class"]);
                $tpl->addDataItem("STATUS.DATA_structure_unit", $statusData["structure_unit"]);
                $tpl->addDataItem("STATUS.DATA_course", $statusData["course"]);
                $tpl->addDataItem("STATUS.DATA_position", $statusData["position"]);
                $tpl->addDataItem("STATUS.DATA_faculty", $statusData["faculty"]);
                if ($canModifyUser && $this->user_type == $this->isic_common->user_type_admin && $statusData['active']) {
                    $tpl->addDataItem("STATUS.MOD.URL_MODIFY", $urlModify);
                }
            }
        }
    }

    /**
     * Check and parse CSV birthdate from dd.mm.yy to yyyy-mm-dd
     *
     * @param string $birthdate Date of birth
     * @throws Exception
     * @return string normalized date of birth
     */
    function parseCsvBirthdate($birthdate)
    {
        if (!preg_match('/^(\\d{2})\\.(\\d{2})\\.(\\d{2})$/', trim($birthdate), $date)) {
            throw new Exception('Invalid birthdate format, must be: dd.mm.yy');
        }
        // if year of birth is over the current year, we consider 20th century, else 21th
        $baseyear = intval($date[3]) > intval(date('y')) ? 1900 : 2000;
        return sprintf(
            '%s-%s-%s',
            strval($baseyear + $date[3]),
            $date[2],
            $date[1]
        );
    }

    /**
     * Check and return CSV SSN
     *
     * @param string $birthdate Date of birth
     * @throws Exception
     * @return string normalized date of birth
     */
    function parseCsvSSN($ssn)
    {
        $ssn = trim($ssn);
        if (!preg_match('/^\\d{11}$/', $ssn)) {
            throw new Exception('Invalid SSN format');
        }
        return $ssn;
    }

    private function isValidCSV($step)
    {
        $error = new IsicError();
        if (!$step) {
            $error = $this->setDataFilePath();
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

        if (!$this->isAllowedNumberOfRows($dest)) {
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

    private function isAllowedNumberOfRows($path)
    {
        $fileLines = file($path);
        if ($fileLines &&
            count($fileLines) > self::CSV_MAX_ROWS
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

    /**
     * Import file in CSV-format with contacts
     *
     * @param string $action action (addmass)
     * @param int $step step
     * @return string html addform for csv-import
     */
    function addUserMass($action, $step = 0)
    {
        // configure
        $saveToUser = array('user_code', 'name_first', 'name_last', 'email', 'phone');
        $overwriteAlways = array('email', 'phone');
        $saveToStatus = array('structure_unit', 'faculty', 'position');
        $importFields = array_merge($saveToUser, $saveToStatus);

        // prepare
        if ($this->vars["step"]) {
            $step = $this->vars["step"];
        }
        if ($this->user_type == $this->isic_common->user_type_user) {
            return $this->isic_common->showErrorMessage("error_csv_import_not_allowed");
        }
        // setlocale(LC_ALL, 'en_US.UTF-8');
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];
        if (!$step) {
            $step = 0;
        }

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        if (!$this->vars["separator"]) {
            $this->vars["separator"] = $this->csv_import_separator;
        }

        $error = new IsicError();
        // ###################################
        // WRITE TO DB
        if ($write == "true") {
            $error = $this->isValidCSV($step);

            // ######################
            // process the file

            if (!$error->isError()) {
                if ($action == "addmass") {
                    if (!$step && is_readable($this->vars["datafile"])) {
                        // first converting the whole file into UTF-8
                        IsicEncoding::convertFileEncoding($this->vars["datafile"], true);
                    }
                    if ($fp = fopen($this->vars["datafile"], "rb")) {
                        $this->vars["title_row"] = $this->vars["title_row"] ? 1 : 0;
                        $tf = pathinfo($this->vars["datafile"]);
                        $this->vars["data_filename"] = $tf["basename"];

                        if (!$step) {
                            $csv_import_min_fields = $this->vars['import_type'] == 'active' ? 3 : 1;
                            // generating title row
                            $csv_fields = array();
                            $csv_fields[-1] = "---";
                            $num = 0;
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
                            if ($num < $csv_import_min_fields) {
                                $error->add('field_count', true);
                            }
                        }

                        if ($step == 1 || $step == 2) {

                            // importing data from csv or form (letting user fix errors)
                            if (isset($this->vars['csv_data']) && is_array($this->vars['csv_data'])) {
                                $csv_data = $this->vars["csv_data"];
                            } else {
                                $csv_data = array();
                                $i_fields = $this->vars["datafield"];
                                $rowNum = 0;
                                while (($data = fgetcsv($fp, 1000, $this->vars["separator"])) !== false) {
                                    $rowNum++;
                                    if ($rowNum == 1 && $this->vars["title_row"]) {
                                        continue;
                                    }
                                    $num = count($data);
                                    $row = array();
                                    for ($c = 0; $c < sizeof($importFields); $c++) {
                                        $t_data = "";
                                        if ($i_fields[$c] != -1 && $num >= $i_fields[$c]) {
                                            $t_data = $data[$i_fields[$c]];
                                        }
                                        $row[$importFields[$c]] = $t_data;
                                    }
                                    $row['confirm'] = 1;
                                    $csv_data[] = $row;
                                }
                            }

                            // check for errors and show csv data to user for confirmation
                            foreach ($csv_data as &$row) {
                                if (!$row['confirm']) {
                                    continue;
                                }
                                try {
                                    $this->parseCsvSSN($row["user_code"]);
                                } catch (Exception $e) {
                                    $row["error"]["user_code"] = true;
                                }
                                if ($this->vars['import_type'] == 'active') {
                                    if (!$row['name_first'] || !$row['name_last']) {
                                        $row["error"]["name_empty"] = true;
                                    }
                                }
                                if (isset($row["error"])) {
                                    $error_fields = true;
                                    if ($step == 2) {
                                        $error = true;
                                    }
                                }
                            }
                            unset($row);  // do not remove!! if $row stays a reference, some later checks may fail

                        }

                        if ($step == 2 && !$error_fields) {
                            // Saving data to database
                            $csv_data = $this->vars["csv_data"];
                            $this->vars["csv_data"] = "";
                            for ($c = 0; $c < sizeof($csv_data); $c++) {
                                if ($csv_data[$c]["confirm"]) {
                                    try {  // for each confirmed user row do the following

                                        foreach ($csv_data[$c] as $t_key => $t_val) {
                                            $this->vars[$t_key] = $t_val;
                                        }

                                        // parse important fields (may throw an exception)
                                        $this->vars["user_code"] = $this->parseCsvSSN($this->vars["user_code"]);
                                        $birthday = IsicDate::calcBirthdayFromNumber($this->vars["user_code"]);

                                        $user = $this->isicDbUsers->getRecordByCode($this->vars['user_code']);
                                        if ($user === false) {
                                            $userData = array_merge(
                                                array_intersect_key($this->vars, array_flip($saveToUser)),
                                                array(
                                                    'birthday' => $birthday,
                                                    'active' => '1'
                                                )
                                            );
                                            $userId = $this->isicDbUsers->insertRecord($userData);
                                        } else {
                                            $userId = $user['user'];
                                            // updating empty user record fields if possible
                                            $updateUserData = false;
                                            foreach ($saveToUser as $userField) {
                                                if ((!trim($user[$userField]) || in_array($userField, $overwriteAlways))
                                                    && $this->vars[$userField]
                                                ) {
                                                    $updateUserData[$userField] = $this->vars[$userField];
                                                }
                                            }
                                            if ($updateUserData) {
                                                $this->isicDbUsers->updateRecord($userId, $updateUserData);
                                            }
                                        }

                                        $status = $this->isicDbUserStatuses->getRecordByGroupUser($this->vars['group'], $userId);
                                        $statusData = array_merge(
                                            array_intersect_key($this->vars, array_flip($saveToStatus)),
                                            array(
                                                'user_id' => $userId,
                                                'group_id' => $this->vars['group'],
                                                'active' => $this->vars['import_type'] == 'active' ? '1' : '0'
                                            )
                                        );
                                        if ($status === false) {
                                            if ($this->vars['import_type'] != 'active') {
                                                throw new Exception('status_does_not_exist');
                                            }
                                            $this->isicDbUserStatuses->insertRecord($statusData);
                                        } else {
                                            $this->isicDbUserStatuses->updateRecord($status['id'], $statusData);
                                        }

                                        if ($this->vars['import_type'] == 'active') {
                                            $csv_data[$c]["user_created"] = true;
                                        } else {
                                            $csv_data[$c]["user_status_deactivated"] = true;
                                        }

                                    } catch (IsicDB_Exception $e) {
                                        $error_save = $error_fields = true;
                                        $csv_data[$c]["error"]["database"] = true;
                                    } catch (Exception $e) {
                                        $error_save = $error_fields = true;
                                        $csv_data[$c]["error"][$e->getMessage()] = true;
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
            $template = "module_isic_user_addmass.html";
            $fields = array(
                // type, size, cols, rows, list, java, class, ?
                "title_row" => array("checkbox", 0, 0, $this->vars["title_row"], "", "", "", true),
                "import_type" => array("select", 1, 0, $this->vars["import_type"], "", "", "", true),
                "datafile" => array("file", 40, 0, $this->vars["datafile"], "", "", "", true),
                "separator" => array("textinput", 1, 0, $this->vars["separator"], "", "", "", true),
                "group" => array("select", 1, 0, $this->vars["group"], "", "", "", true),
            );

            // import types
            $list = array(
                'active' => $this->txt->display('import_type_active'),
                'inactive' => $this->txt->display('import_type_inactive')
            );
            $fields['import_type'][4] = $list;

            // list groups
            $list = array();
            foreach ($this->isicDbUserGroups->listAllowedRecords() as $data) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["group"][4] = $list;

        } elseif ($step == 1) {
            $fields = array();
            $data_fields = array();
            $template = "module_isic_user_addmass_1.html";

            for ($i = 0; $i < sizeof($importFields); $i++) {
                if ((sizeof($csv_fields) - 1) >= $i) {
                    $t_val = $i;
                }
                $data_fields[$i] = array("select", 40, 0, $t_val, $csv_fields, "", "", true);
            }
        } elseif ($step == 2) {
            $template = "module_isic_user_addmass_2.html";
        } elseif ($step == 3) {
            $template = "module_isic_user_addmass_3.html";
        }

        $instanceParameters = '&type=addusers';
        $tpl = $this->isicTemplate->initTemplateInstance($template, $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        // #################################
        if ($error->isError()) {
            $this->showErrorMessage($error, $tpl);
        }

        if (is_array($fields) && sizeof($fields)) {
            while (list($key, $val) = each($fields)) {
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
            while (list($key, $val) = each($data_fields)) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];
                $fdata["class"] = $val[6];

                $f = new AdminFields("datafield[" . $key . "]", $fdata);
                $field_data = $f->display($val[3]);
                $tpl->addDataItem("FIELDS.TITLE", $this->txt->display($importFields[$key]));
                $tpl->addDataItem("FIELDS.DATA", $field_data);
                unset($fdata);
            }
        }

        // show all the imported rows together with according statuses
        if ($step == 2) {
            $fdata = array();
            $fdata["type"] = "textinput";
//            $fdata["size"] = 30;
//            $fdata["cols"] = 30;

            $fdata_conf = array();
            $fdata_conf["type"] = "checkbox";

            foreach ($importFields as $field_title) {
                $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display($field_title));
            }

            $row = 0;
            foreach ($csv_data as $key => $val) {
                $tpl->addDataItem("ROW.ROW", $row + 1);
                foreach ($importFields as $field_name) {
                    $f = new AdminFields("csv_data[" . $row . "][" . $field_name . "]", $fdata);
                    $field_data = $f->display($val[$field_name]);
                    $tpl->addDataItem("ROW.COL.DATA", $field_data);
                }
                if ($val["user_created"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("user_created"));
                } else if ($val["user_status_deactivated"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("user_status_deactivated"));
                } else {
                    $f = new AdminFields("csv_data[" . $row . "][confirm]", $fdata_conf);
                    $field_data = array(
                        $f->display(intval($val['confirm']))
                    );
                    if (is_array($val["error"])) {
                        foreach ($val["error"] as $err_key => $err_val) {
                            if ($err_val) {
                                $field_data[] = '<font color="red">' . stripslashes($this->txt->display("modify_error_" . $err_key)) . '</font>';
                            }
                        }
                    }
                    $tpl->addDataItem("ROW.COL.DATA", implode("<br />", $field_data));
                }

                $row++;
            }
        }

        // show all the imported rows together with according statuses
        if ($step == 3) {
            foreach ($importFields as $field_title) {
                $tpl->addDataItem("ROW_TITLE.TITLE", $this->txt->display($field_title));
            }

            $row = 0;
            foreach ($csv_data as $key => $val) {
                $row++;
                $tpl->addDataItem("ROW.ROW", $row);
                foreach ($importFields as $field_name) {
                    $tpl->addDataItem("ROW.COL.DATA", $val[$field_name]);
                }

                if (is_array($val["error"])) {
                    $err_txt = array();
                    foreach ($val["error"] as $err_key => $err_val) {
                        if ($err_val) {
                            $err_txt[] = $this->txt->display("modify_error_" . $err_key);
                        }
                    }
                    $tpl->addDataItem("ROW.COL.DATA", implode("<br />", $err_txt));
                } else if ($val["user_created"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("user_created"));
                } else if ($val["user_status_deactivated"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $this->txt->display("user_status_deactivated"));
                } else {
                    $tpl->addDataItem("ROW.COL.DATA", "-");
                }
            }
        }

        if ($action == "addmass") {
            if (!$step) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_import"));
            } else if ($step == 1) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_next"));
            } else if ($step == 2) {
                $tpl->addDataItem("BUTTON", $this->txt->display("button_save"));
            }
        }

        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('step', $step);
        $hidden .= IsicForm::getHiddenField('separator', $this->vars["separator"]);
        $hidden .= IsicForm::getHiddenField('start', $start);
        if ($this->vars["data_filename"]) {
            $hidden .= IsicForm::getHiddenField('data_filename', $this->vars["data_filename"]);
        }
        if ($this->vars["import_type"]) {
            $hidden .= IsicForm::getHiddenField('import_type', $this->vars["import_type"]);
        }
        if ($this->vars["group"]) {
            $hidden .= IsicForm::getHiddenField('group', $this->vars["group"]);
        }
        if ($this->vars["title_row"]) {
            $hidden .= IsicForm::getHiddenField('title_row', $this->vars["title_row"]);
        }

        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", $general_url);

        return $tpl->parse();
    }

    function getGeneralUrl($content = 0)
    {
        $general_url = '';
        if (!$content) {
            $content = (int)@$this->vars['content'];
        }
        if ($content && is_int($content)) {
            $general_url = $_SERVER['PHP_SELF'] . '?content=' . $content;
        }
        return $general_url;
    }

    function assignUserVariables($user)
    {
        if (!$user) {
            return 'add';
        }

        $this->userData = $this->isicDbUsers->getRecord($user);
        if (!$this->userData) {
            $this->redirectIntoListView('error=user');
        }

        if (!$this->canModifyUser()) {
            $this->redirectIntoListView('error=modify');
        }

        return 'modify';
    }

    function redirectIntoListView($param = '')
    {
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&" . $param, array("user_id", 'user_status_id', "action", 'info')));
    }

    function canModifyUser()
    {
        // admin-users can always modify user data no matter what the groups are telling
        // because otherwise the new status adding functionality wouldn't work
        return
            $this->isic_common->canModifyUser($this->userData) ||
            $this->user_type == $this->isic_common->user_type_admin;
    }

    function getUserId($user)
    {
        if (!$user && $this->vars["user_id"]) {
            $user = $this->vars["user_id"];
        }
        return $user;
    }

    function saveData($user, $action)
    {
        $this->assignSpecialOffers();
        if ($action == 'modify') {
            $newslettersList = $this->getNewsletterValue();
            $this->isicDbNewslettersOrders->updateUserOrders($user, false, $newslettersList, $this->userid, true);
            $formFields = $this->getFormFields($this->isicDbUsers->getRecord($user));
            if (array_key_exists('appl_confirmation_mails', $formFields) &&
                !isset($this->vars['appl_confirmation_mails'])
            ) {
                $this->vars['appl_confirmation_mails'] = 0;
            }
            $this->isicDbUsers->updateRecord($user, $this->vars);
        } else if ($action == 'add') {
            $user = $this->isicDbUsers->insertRecord($this->vars);
        }
        return $user;
    }

    protected function assignSpecialOffers()
    {
        $specialOffers = implode(',', $this->vars['special_offers']);
        $this->vars['special_offers'] = $specialOffers ? $specialOffers : '';
    }

    private function getNewsletterValue()
    {
        $newsletterList = array();
        foreach ($this->vars as $name => $value) {
            if (preg_match("/person_newsletter/", $name)) {
                $newsletterList[] = $value;
            }
        }
        return $newsletterList;
    }

    function setExternalCheck($user, $action)
    {
        $user = $this->getUserId($user);
        $userData = $this->isicDbUsers->getRecord($user);
        if (!$userData) {
            $this->redirectIntoListView('error=user');
        }

        if (!$this->canSetExternalCheck($userData, $action)) {
            $this->redirectIntoDetailView('user_id=' . $user . '&error=' . $action);
        }

        switch ($action) {
            case 'ext_check':
                $this->isicDbUsers->updateRecord($user, array('external_status_check_allowed' => 1));
                $ehisUser = new EhisUser();
                $idList = $ehisUser->getStatusListByUser($userData['user_code']);
                $this->ehisError = $ehisUser->getError();
                break;
            case 'ext_uncheck':
                $this->isicDbUsers->updateRecord($user, array('external_status_check_allowed' => 0));
                $this->isicDbUserStatuses->deactivateAllAutomaticRecordsByUser($user);
                break;
            default:
                break;
        }
        $this->redirectIntoDetailView('user_id=' . $user . '&info=' . $action);
    }

    function setEhlCheck($user, $action)
    {
        $user = $this->getUserId($user);
        $userData = $this->isicDbUsers->getRecord($user);
        if (!$userData) {
            $this->redirectIntoListView('error=user');
        }

        if (!$this->canSetEhlCheck($userData, $action)) {
            $this->redirectIntoDetailView('user_id=' . $user . '&error=' . $action);
        }

        switch ($action) {
            case 'ehl_check':
                $this->isicDbUsers->updateRecord($user, array('ehl_status_check_allowed' => 1));
                $ehlClient = new IsicEHLClient();
                $idList = $ehlClient->getStatusListByUser($userData['user_code']);
                $this->ehlError = $ehlClient->getError();
                break;
            case 'ehl_uncheck':
                $this->isicDbUsers->updateRecord($user, array('ehl_status_check_allowed' => 0));
                $this->isicDbUserStatuses->deactivateAllAutomaticRecordsByUser($user, $this->isicDbUserStatuses->getOriginEhl());
                break;
            default:
                break;
        }
        $this->redirectIntoDetailView('user_id=' . $user . '&info=' . $action);
    }

    function setDataSyncAllowed($user, $action)
    {
        $user = $this->getUserId($user);
        $userData = $this->isicDbUsers->getRecord($user);
        if (!$userData) {
            $this->redirectIntoListView('error=user');
        }

        if (!$this->canSetDataSyncAllowed($userData, $action)) {
            $this->redirectIntoDetailView('user_id=' . $user . '&error=' . $action);
        }

        switch ($action) {
            case 'data_sync_disallow':
                $this->isicDbUsers->updateRecord($user, array('data_sync_allowed' => 0));
                $isicDbCardDataSync = IsicDB::factory('CardDataSync');
                $isicDbCardDataSync->scheduleUser($userData);
                break;
            default:
                break;
        }
        $this->redirectIntoDetailView('user_id=' . $user . '&info=' . $action);
    }


    function redirectIntoDetailView($param = '')
    {
        redirect(processUrl(SITE_URL, $_SERVER['QUERY_STRING'], 'nocache=true&' . $param, array('user_id', 'action', 'info', 'error')));
    }


    /**
     * Displays add/modify view of a user
     *
     * @param int $user user id
     * @param string $action action (add/modify)
     * @return string html addform for users
     */
    function addUser($user, $action, $user_status = false)
    {
        $user = $this->getUserId($user);
        $info_message = @$this->vars["info"];
        $action = $this->assignUserVariables($user);
        $error = new IsicError();
        if ($this->isWriteNeeded()) {
            $error = $this->isValidData($user, $action);
            if (!$error->isError()) {
                $user = $this->saveData($user, $action);
                if ($user_status) {
                    $user_status->vars['user_id'] = $user;
                    $user_status->id = $user_status->getUserStatusId($user_status->id);

                    $us_action = $user_status->id ? 'modify' : 'add';
                    $error = $user_status->checkRequired($us_action);
                    if (!$error->isError()) {
                        $user_status->saveData($us_action);
                        $user_status->assignStatusAndUserVariables();
                        $info_message = $us_action . '_status';
                    }
                } else {
                    $info_message = $action;
                }

                if (!$error->isError()) {
                    if (!$this->pictureUploader["pic_resize_required"]) {
                        if ($this->vars['redirect'] == 'user_detail' && $user) {
                            $url = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user')) . '&user_id=' . $user . '&info=' . $info_message;
                        } else if ($user_status) {
                            $url = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user_status')) . '&user_status_id=' . $user_status->id . '&info=' . $info_message;
                        } else {
                            $url = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user')) . '&user_id=' . $user . '&info=' . $info_message;
                        }
                        redirect($url);
                    } else {
                        $action = "modify";
                    }
                }
            }
        }

        // ###################################
        $instanceParameters = '&type=adduser';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_user_add_mod.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        // #################################

        if ($error->isError()) {
            $this->showErrorMessage($error, $tpl);
        } elseif ($info_message && $this->info_message[$info_message]) {
            $this->showInfoMessage($info_message, $tpl);
        }

        $hidden = '';
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('user_id', $user);

        $userData = $this->getUserData($user, $error);
        $requiredFields = $this->isicDbUsers->getRequiredFields($action);
        $formFields = $this->getFormFields($userData);
        foreach ($this->getNewslettersFields($userData["user"]) as $newsletter) {
            $tpl->addDataItem("DATA_NEWSLETTERS.FIELD_NEWSLETTER", $newsletter);
        }
        $form = new IsicForm($tpl, $this->txt, $action, $error, $requiredFields);
        $form->showFields($formFields);
        if ($user_status) {
            $actionStatus = $user_status->id ? 'modify' : 'add';
            $hidden .= IsicForm::getHiddenField('user_status_id', $user_status->id);
            if ($actionStatus == 'modify') {
                $hidden .= IsicForm::getHiddenField('group_id', $user_status->statusData['group_id']);
            }
            $formFieldsStatus = $user_status->getFormFields($user_status->getStatusData($error));
            //$formFields = array_merge($formFields, $formFieldsStatus);
            $requiredFieldsStatus = $user_status->isicDbUserStatuses->getRequiredFields($actionStatus);
            //$requiredFields = array_merge($requiredFields, $requiredFieldsStatus);
            $formStatus = new IsicForm($tpl, $this->txt, $actionStatus, $error, $requiredFieldsStatus);
            $formStatus->showFields($formFieldsStatus);
            $hidden .= IsicForm::getHiddenField('action', $actionStatus);
        } else {
            $hidden .= IsicForm::getHiddenField('action', $action);
        }

        if ($user_status) {
            $form->showActionButton('_status');
        } else {
            $form->showActionButton();
        }
        /*
        if ($action == 'add') {
            $tpl->addDataItem("SHOW_BIRTHDAY", '1');
            $tpl->addDataItem("BIRTHDAY_VALUE", IsicDate::getDateFormatted($userData["birthday"]));
            $tpl->addDataItem("DATE_FORMAT", IsicDate::DEFAULT_DATE_FORMAT);
            $tpl->addDataItem("BIRTHDAY.FIELD_birthday", '');
        }
        */

        if ($this->pictureUploader["pic_resize_required"]) {
            $sizeRatio = $this->pictureUploader['width'] / IsicImageUploader::IMAGE_SIZE_X;
            $minWidth = IsicImageUploader::IMAGE_SIZE_X / $sizeRatio;
            $minHeight = IsicImageUploader::IMAGE_SIZE_Y / $sizeRatio;
            $tpl->addDataItem("EDIT_PIC_JS.MIN_WIDTH", round($minWidth));
            $tpl->addDataItem("EDIT_PIC_JS.MIN_HEIGHT", round($minHeight));
            $tpl->addDataItem("EDIT_PIC_JS.ASPECT_RATIO", IsicImageUploader::getAspectRatio());
            $tpl->addDataItem("EDIT_PIC_JS.X1", round(($this->pictureUploader['width'] / $sizeRatio - $minWidth) / 2));
            $tpl->addDataItem("EDIT_PIC_JS.Y1", round(($this->pictureUploader['height'] / $sizeRatio - $minHeight) / 2));

            $hidden .= IsicForm::getHiddenField('pic_resize', 'true');
            $hidden .= IsicForm::getHiddenField('pic_name', $this->pictureUploader["pic_filename"]);
            $tpl->addDataItem("EDIT_PIC.DATA_pic", $this->pictureUploader["tmp_pic"]);
            $tpl->addDataItem("EDIT_PIC.BUTTON", $this->txt->display("resize"));
            $tpl->addDataItem("EDIT_PIC.MAX_WIDTH", IsicImageUploader::IMAGE_SIZE_X);
        } else {
            $tpl->addDataItem("SHOW_PIC.DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($userData["pic"], 'big'));
        }
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("user_id", "action", "write")));

        $tpl->addDataItem("BACK.TITLE", $this->txt->display("cancel"));
        $tpl->addDataItem("BACK.URL", $this->getBackUrl($this->vars['redirect'], $user, $action));

        return $tpl->parse();
    }

    function getBackUrl($redirect, $user, $action)
    {
        if ($redirect == 'user_detail' && $user) {
            $backUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user')) . '&user_id=' . $user;
        } else if ($action == 'add') {
            $backUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user_status'));
        } else {
            $backUrl = processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("user_id", "action"));
        }
        return $backUrl;
    }

    function isWriteNeeded()
    {
        return 'true' == @$this->vars["write"];
    }

    function isValidData($user, $action)
    {
        $error = new IsicError();
        $error->checkRequired($this->vars, $this->isicDbUsers->getRequiredFields($action));

        if ($action == "add") {
            $this->vars["birthday"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["birthday"]);
            // first checking if this user with the same user_code already exists
            if (!$error->isError() &&
                $this->isicDbUsers->getRecordByCode($this->vars["user_code"])
            ) {
                $error->add('user_exists');
            }
        }

        // check if e-mail is valid
        if (!$error->isError() &&
            (!validateEmail($this->vars["email"]) && trim($this->vars['email']))
        ) {
            $error->add('email');
            $error->addBadField('email');
        }

        // image upload handling
        if (!$error->isError()) {
            $imageUploader = new IsicImageUploader('user');
            $this->pictureUploader = $imageUploader->handlePictureUpload($user);
            $this->vars["pic"] = $this->pictureUploader["pic_vars"];
            $error->setError($this->pictureUploader["error"]);
            $error->add('pic', $this->pictureUploader["error_pic"]);
            $error->add('pic_save', $this->pictureUploader["error_pic_save"]);
            $error->add('pic_size', $this->pictureUploader["error_pic_size"]);
            $error->add('pic_resize', $this->pictureUploader["error_pic_resize"]);
            $error->add('pic_format', $this->pictureUploader["error_pic_format"]);
        }
        return $error;
    }

    function getUserData($user, $error)
    {
        if ($user) {
            $userData = $this->isicDbUsers->getRecord($user);
            $this->vars["user_code"] = $userData["user_code"];
            $this->vars["pic"] = $userData["pic"];
        }
        if (!$error->isError()) {
            return $userData;
        } else {
            return $this->vars;
        }
    }

    function getFormFields($data)
    {
        $fields = array(
            "name_first" => array("textinput", 40, 0, $data["name_first"], "", "", "", true),
            "name_last" => array("textinput", 40, 0, $data["name_last"], "", "onblur=\"generateBankAccountName();\"", "", true),
            "user_code" => array("textinput", 40, 0, $data["user_code"], "", "onblur=\"generateBirthday();\"", "", false),
            "birthday" => array("textinput", 40, 0, IsicDate::getDateFormatted($data["birthday"]), "", "", "datePicker", false),
            "delivery_addr1" => array("textinput", 40, 0, $data["delivery_addr1"], "", "", "", true),
            "delivery_addr2" => array("textinput", 40, 0, $data["delivery_addr2"], "", "", "", true),
            "delivery_addr3" => array("textinput", 40, 0, $data["delivery_addr3"], "", "", "", true),
            "delivery_addr4" => array("textinput", 40, 0, $data["delivery_addr4"], "", "", "", true),
            "email" => array("textinput", 40, 0, $data["email"], "", "", "", true),
            "phone" => array("textinput", 40, 0, $data["phone"], "", "", "", true, "phone_help"),
//            "bankaccount" => array("textinput", 40, 0, $data["bankaccount"], "", "", "", true, "bankaccount_help"),
//            "bankaccount_name" => array("textinput", 40, 0, $data["bankaccount_name"], "", "", "", true),
            'special_offers' => array('checkboxm2', 0, 0, $data['special_offers'], $this->getSpecialOfferTypes(), '', '', true, 'special_offers_help'),
            //"newsletter" => array("checkboxm2", 0,0,$data["newsletter"],$this->getAllowedCardTypes(),"","", true, "newsletter_help"),
            "pic" => array("file", 43, 0, $data["pic"], "", "", "", true, "pic_help"),
        );
        if ($this->isicDbUsers->isCurrentUserAdmin() && $this->isicDbUsers->isUserCurrentUser($data)) {
            $fields["appl_confirmation_mails"] = array("checkbox", 0, 0, $data["appl_confirmation_mails"], "", "", "", true);
        }
        return $fields;
    }

    protected function getSpecialOfferTypes()
    {
        return array(
            1 => $this->txt->display('special_offer1'),
            2 => $this->txt->display('special_offer2'),
        );
    }

    function getAllowedCardTypes()
    {
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_type`.*
            FROM
                `module_isic_card_type`
            WHERE
                `module_isic_card_type`.`id` IN (!@)
            ORDER BY
                `module_isic_card_type`.`name`
            ',
            $this->allowed_card_types_view);
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        return $list;
    }


    function getUserGroups($userData)
    {
        $list = array();
        $groups = $this->isicDbUserGroups->listAllowedRecords();
        foreach ($groups as $data) {
            $list[$data['id']] = $data['name'];
        }
        return $list;
    }

    function getGroupTypes($userData)
    {
        $t_group_type = array($this->isic_common->user_type_user);
        if (is_array($userData) && $userData["user_type"] == $this->isic_common->user_type_admin) {
            $t_group_type[] = $this->isic_common->user_type_admin;
        }
        return $t_group_type;
    }

    function showErrorMessage($error, $tpl)
    {
        if (!$error->isError()) {
            return;
        }

        if ($error->get('user_exists')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_user_exists"));
        } elseif ($error->get('pic_resize')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_resize"));
        } elseif ($error->get('pic_save')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_save"));
        } elseif ($error->get('pic_size')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_size"));
        } elseif ($error->get('pic_format')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_pic_format"));
        } elseif ($error->get('email')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_email"));
        } elseif ($error->get('datafile')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_datafile"));
        } elseif ($error->get('field_count')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_csv_field_count"));
        } elseif ($error->get('too_many_rows')) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_too_many_rows"));
        } else {
            $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error"));
        }
    }

    function showInfoMessage($info_message, $tpl)
    {
        if ($info_message && $this->info_message[$info_message]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$info_message]));
        }
    }

    function getGroupValue($group)
    {
        // if regular user then group will be taken from it's current user-record
        if ($this->user_type == $this->isic_common->user_type_user) {
            return $this->userData["ggroup"];
        }

        if (is_array($group)) {
            return implode(",", $group);
        }
        return '';
    }

    function setActiveSchool()
    {
        if ($this->vars['school_id'] == 0 || $this->vars['school_id'] && in_array($this->vars['school_id'], $this->isic_common->allowed_schools_all)) {
            $this->isicDbUsers->updateRecord($this->userid, array('active_school_id' => $this->vars['school_id']));
        }
        redirect(SITE_URL);
    }

    /**
     * Deletes user record from table
     *
     * @param int $user user id
     * @return redirect to a listview page
     */

    function deleteUser($user)
    {
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&error=delete", array("user_id", "action")));
    }

    /**
     * Sets view type
     *
     * @param string $list_type list type (all, ordered, void)
     */

    function setListType($list_type)
    {
        if ($list_type == "all" || $list_type == "requested" || $list_type == "confirm_user" || $list_type == "confirm_user_not" || $list_type == "active" || $list_type == "ordered" || $list_type == "void" || $list_type == "first_time" || $list_type == "my_card") {
            $this->list_type = $list_type;
        }
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
        $txt = new Text($this->language, "module_isic_user");

        $list = array();
        $list['all'] = $txt->display("all");
        $list['active'] = $txt->display("active");

        // ####
        return array($txt->display("list_type"), "select", $list);
        // name, type, list
    }
}
