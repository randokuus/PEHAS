<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImage.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicListViewSortOrder.php");

class isic_card {
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
     * Maximum number of results in listview
     *
     * @var int
     * @access protected
     */
    var $maxresults = 50;

    /**
     * List view type
     *
     * @var string (all, ordered, void)
     * @access protected
     */

    var $list_type = "";

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_card";

    /**
     * Default db-table to use
     *
     * @var string
     * @access protected
     */
    var $table_module_default = "`module_isic_card`";

    /**
     * Info message match array
     *
     * @var array
     * @access protected
     */
    var $info_message = array(
        "distribute" => "info_distribute",
        "activate" => "info_activate",
        "modify" => "info_modify",
        "deactivate" => "info_deactivate",
        "return" => "info_return",
        "replace" => "info_replace",
        "prolong" => "info_prolong",
        "data_saved" => "info_data_saved",
    );

    var $listSortFields = array(
        "pic" => "`module_isic_card`.pic",
        "person_name_first" => "`module_isic_card`.`person_name_first`",
        "person_name_last" => "`module_isic_card`.`person_name_last`",
        "person_number" => "`module_isic_card`.`person_number`",
        "expiration_date" => "`module_isic_card`.`expiration_date`",
        "isic_number" => "`module_isic_card`.`isic_number`",
        "card_type_name" => "`module_isic_card_type`.`name`",
//        "bank_name" => "`module_isic_bank`.`name`",
        "moddate" => "`module_isic_card`.`moddate`",
        "active" => "`module_isic_card`.`active`",
//        "delivery" => "`module_isic_card`.`delivery_id`",
    );

    var $listSortFieldDefault = 'person_name_last';

    var $isicDbCards = false;

    var $isicDbCardStatuses = false;

    /**
     * @var IsicDB_Schools
     */
    var $isicDbSchools = false;

    var $allowed_schools = false;

    var $allowed_schools_all = false;

    var $allowed_card_types_view = false;

    var $allowed_card_types_add = false;

    var $modifyActions = array("modify", "replace", "prolong", "distribute", "activate", "deactivate", "return");

    var $isicTemplate = false;

    /**
     * @var IsicDB_Banks
     */
    private $isicDbBanks;

    /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function isic_card () {
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

        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbCardStatuses = IsicDB::factory('CardStatuses');
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicDbBanks = IsicDB::factory('Banks');

        $this->c_state_ordered = $this->isic_common->c_state_ordered;
        $this->c_state_distributed = $this->isic_common->c_state_distributed;
        $this->c_state_activated = $this->isic_common->c_state_activated;
        $this->c_state_deactivated = $this->isic_common->c_state_deactivated;

        $this->isicUser = new IsicUser($this->userid);

        $this->allowed_schools = $this->isicUser->getAllowedSchools();
        $this->allowed_schools_all = $this->isic_common->allowed_schools_all;
        $this->allowed_card_types_view = $this->isicUser->getAllowedCardTypesForView();
        $this->allowed_card_types_add = $this->isicUser->getAllowedCardTypesForAdd();
        $this->isicTemplate = new IsicTemplate('isic_card');
        setlocale(LC_ALL, 'en_US.UTF-8');
    }

    /**
     * Main module display function
     *
     * @return string html ISIC content
    */

    function show () {
        if ($this->checkAccess() == false) return "";

        $action = @$this->vars["action"];
        $card_id = @$this->vars["card_id"];

        if (!$this->userid) {
            trigger_error("Module 'ISIC_card' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == $this->user_type_user && !$this->user_code) {
            trigger_error("Module 'ISIC_card' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }

        if ($card_id && $this->isModifyAction($action)) {
            $result = $this->modifyCard($card_id, $action);
        }
        else if ($card_id && !$action) {
            $result = $this->showCard($card_id);
        }
        else {
            $result = $this->showCardList();
        }
        return $result;
    }

    function isModifyAction($action) {
        return in_array($action, $this->modifyActions);
    }

    function showCardListFirst($count = 2) {
        $instanceParameters = '&type=cardlistfirst';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_card_list_first.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }
        $condition_sql = $this->getCardListQueryFilter();

        $res =& $this->db->query("
            SELECT
                {$this->table_module_default}.`id`
            FROM
                {$this->table_module_default}
            JOIN
                `module_isic_card_validities` ON `module_isic_card_validities`.`card_id` = `module_isic_card`.`id`
            JOIN
                `module_user_status_user` ON `module_isic_card_validities`.`user_status_id` = `module_user_status_user`.`id`
            WHERE
                {$this->table_module_default}.`exported` > ?
                !
            GROUP BY
                {$this->table_module_default}.`id`
            ORDER BY
                {$this->table_module_default}.`state_id` ASC,
                {$this->table_module_default}.`moddate` DESC
            LIMIT !, !",
            $this->isic_common->empty_date,
            $condition_sql,
            0,
            $count
        );
        IsicDB::assertCustomResult($res, $this->db);
//        echo "<!-- SQL: " . $this->db->show_query() . " -->\n";
        $ids = array();
        while($row = $res->fetch_assoc()) {
           $ids[] = $row['id'];
        }
        if (count($ids) > 0) {
          $res =& $this->db->query("
              SELECT
                  {$this->table_module_default}.*,
                  IF(`module_isic_card_state`.`id`, `module_isic_card_state`.`name`, '') AS state_name,
                  IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                  IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                  IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                  IF(`module_isic_application`.`id`, `module_isic_application`.`application_type_id`, 0) AS application_type_id
              FROM
                  {$this->table_module_default}
              LEFT JOIN
                  `module_isic_card_state` ON {$this->table_module_default}.`state_id` = `module_isic_card_state`.`id`
              LEFT JOIN
                  `module_isic_card_kind` ON {$this->table_module_default}.`kind_id` = `module_isic_card_kind`.`id`
              LEFT JOIN
                  `module_isic_bank` ON {$this->table_module_default}.`bank_id` = `module_isic_bank`.`id`
              LEFT JOIN
                  `module_isic_card_type` ON {$this->table_module_default}.`type_id` = `module_isic_card_type`.`id`
              LEFT JOIN
                  `module_isic_application` ON {$this->table_module_default}.`id` = `module_isic_application`.`card_id`
              WHERE
                  {$this->table_module_default}.`id` IN (!@)
              ",
              $ids
          );
          IsicDB::assert($res);
        }
        //echo "<!-- SQL: " . $this->db->show_query() . " -->\n";

        $cardData = array();
        while ($data = $res->fetch_assoc()) {
          $cardData[$data["id"]] = $data;
        }

        $generalUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_card'));

        foreach ($ids as $id) {
            $data = $cardData[$id];
            $tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb')));
            $tpl->addDataItem("DATA.ACTIVE", $this->txt->display("active" . $data["active"]));
            $tpl->addDataItem("DATA.STATE_NAME", $data["state_name"]);
            $tpl->addDataItem("DATA.SCHOOL_NAME", $data["school_name"]);
            $tpl->addDataItem("DATA.CARD_TYPE_NAME", $data["card_type_name"]);
            $tpl->addDataItem("DATA.PERSON_NAME_FIRST", $data["person_name_first"]);
            $tpl->addDataItem("DATA.PERSON_NAME_LAST", $data["person_name_last"]);
            $tpl->addDataItem("DATA.PERSON_BIRTHDAY", IsicDate::getDateFormatted($data["person_birthday"]));
            $tpl->addDataItem("DATA.PERSON_NUMBER", $data["person_number"]);
            $tpl->addDataItem("DATA.ISIC_NUMBER", $data["isic_number"]);
            $tpl->addDataItem("DATA.URL_DETAIL", $generalUrl . "&card_id=" . $data["id"]);
        }
        $tpl->addDataItem("URL", $generalUrl);

        return $tpl->parse();
    }

    /**
     * Displays list of cards
     *
     * @return string html listview of cards
    */

    function showCardList() {

        if ($this->checkAccess() == false) return "";
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $general_url_plain = $general_url;

        if (!$start) {
            $start = 0;
        }
        if ($this->module_param["isic_card"]) {
            $this->list_type = $this->module_param["isic_card"];
        }

        $txtf = new Text($this->language, "output");

        $instanceParameters = '&type=cardlist&sort=' . $this->vars['sort'] . '&sort_order=' . $this->vars['sort_order'];
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_card_list.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $confirm_states = array(
            $this->c_state_ordered,
            $this->c_state_distributed,
        );
        if ($this->list_type != $this->c_state_distributed) {
            unset($this->listSortFields['moddate']);
        }
        if ($this->list_type != $this->c_state_ordered) {
            unset($this->listSortFields['delivery']);
        }
        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);

        $hidden = IsicForm::getHiddenField('sort', $listSortOrder->getSort());
        $hidden .= IsicForm::getHiddenField('sort_order', $this->vars["sort_order"]);
        $hidden .= IsicForm::getHiddenField('start', $start);

        $condition_sql = $this->getCardListQueryFilter();
        $url_filter = $this->getCardListFiltersAsUrl();
        $hidden .= $this->getCardListFiltersAsHidden();


        $res =& $this->db->query("
            SELECT
                {$this->table_module_default}.id
            FROM
                {$this->table_module_default}
            JOIN
                `module_isic_card_validities` ON `module_isic_card_validities`.`card_id` = `module_isic_card`.`id`
            JOIN
                `module_user_status_user` ON `module_isic_card_validities`.`user_status_id` = `module_user_status_user`.`id`
            JOIN
                `module_isic_school` ON `module_isic_card_validities`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_bank` ON {$this->table_module_default}.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON {$this->table_module_default}.`type_id` = `module_isic_card_type`.`id`
            WHERE
                {$this->table_module_default}.`exported` > ?
                !
            GROUP BY
                {$this->table_module_default}.`id`
            ORDER BY
            ?f !
            LIMIT !, !",
            $this->isic_common->empty_date,
            $condition_sql,
            $listSortOrder->getOrderBy(),
            $listSortOrder->getSortOrder(),
            $start,
            $this->maxresults
        );
//        echo "<!-- SQL: " . $this->db->show_query() . " -->\n";
        IsicDB::assert($res);
        $ids = array();
        while($row = $res->fetch_assoc()) {
            $ids[] = $row['id'];
        }
        if (count($ids) > 0) {
            $res =& $this->db->query("
                SELECT
                    {$this->table_module_default}.*,
                    IF(`module_isic_card_state`.`id`, `module_isic_card_state`.`name`, '') AS state_name,
                    IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                    IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                    IF(`module_isic_card_delivery`.`id`, `module_isic_card_delivery`.`name`, '') AS card_delivery_name,
                    IF(`module_isic_application`.`id`, `module_isic_application`.`application_type_id`, 0) AS application_type_id
                FROM
                    {$this->table_module_default}
                LEFT JOIN
                    `module_isic_card_state` ON {$this->table_module_default}.`state_id` = `module_isic_card_state`.`id`
                LEFT JOIN
                    `module_isic_card_kind` ON {$this->table_module_default}.`kind_id` = `module_isic_card_kind`.`id`
                LEFT JOIN
                    `module_isic_bank` ON {$this->table_module_default}.`bank_id` = `module_isic_bank`.`id`
                LEFT JOIN
                    `module_isic_card_type` ON {$this->table_module_default}.`type_id` = `module_isic_card_type`.`id`
                LEFT JOIN
                    `module_isic_application` ON {$this->table_module_default}.`id` = `module_isic_application`.`card_id`
                LEFT JOIN
                    `module_isic_card_delivery` ON {$this->table_module_default}.`delivery_id` = `module_isic_card_delivery`.`id`
                    AND `module_isic_card_delivery`.`school_id` <> 0 AND `module_isic_card_delivery`.`active` = 1
                WHERE
                    {$this->table_module_default}.`id` IN (!@)
                ",
                $ids
            );
            IsicDB::assert($res);
        }
        //echo "<!-- SQL: " . $this->db->show_query() . " -->\n";
        $cardData = array();
        while ($data = $res->fetch_assoc()) {
            $cardData[$data["id"]] = $data;
        }
        $confirm_card = $this->vars["confirm_card"];

        foreach ($ids as $id) {
            $data = $cardData[$id];
            $show_confirm_box = false;
            $show_record = true;
            $can_distribute = $this->isic_common->canDistributeCard($data);
            $can_activate = $this->isic_common->canActivateCard($data);

            if ($this->user_type == $this->user_type_admin && in_array($this->list_type, $confirm_states)) {
                if (($can_distribute || $can_activate) && $this->vars["write_confirm"] && $confirm_card[$data["id"]]) {
                    switch ($this->vars["confirm_type"]) {
                        case 'distribute':
                            if ($can_distribute && $data["state_id"] != $this->c_state_distributed) {
                                $this->isicDbCards->distribute($data["id"]);
                                IsicMail::sendCardDistributionNotification($data);
                                $this->vars["info"] = "distribute";
                                $show_record = false;
                            }
                        break;
                        case 'activate':
                            if ($can_activate && $data["state_id"] != $this->c_state_activated) {
                                $this->isicDbCards->activate($data["id"]);
                                // if card was marked as active  then de-activating all other cards of the same type for this user
                                $this->isicDbCards->deActivateOtherCards($data);
                                $this->vars["info"] = "activate";
                                $show_record = false;
                            }
                        break;
                        default :
                        break;
                    }
                }
                $show_confirm_box = true;
            }

            if ($show_record) {
                $tpl->addDataItem("DATA.DUMMY", '');
                $rowUrl = $general_url . "&card_id=" . $data["id"] . $url_filter . "&sort=".$listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder();
                $dataValues = array(
                    'pic' => array('url' => IsicImage::getPopUpForUrl(IsicImage::getPictureUrl($data['pic'], 'big')), 'value' => IsicImage::getImgTagForUrl(IsicImage::getPictureUrl($data['pic'], 'thumb'))),
                    'active' => array('value' => $this->txt->display("active" . $data["active"])),
                    'school_name' => array('value' => $data["school_name"]),
                    'card_type_name' => array('value' => $data["card_type_name"]),
                    'person_name_first' => array('value' => $data["person_name_first"]),
                    'person_name_last' => array('value' => $data["person_name_last"]),
                    'person_birthday' => array('value' => IsicDate::getDateFormatted($data["person_birthday"])),
                    'person_number' => array('value' => $data["person_number"]),
                    'isic_number' => array('value' => $data["isic_number"]),
//                    'bank_name' => array('value' => $data["bank_name"]),
                    'card_number' => array('value' => $data["card_number"]),
                    'activation_date' => array('value' => date("m/Y", strtotime($data["activation_date"]))),
                    'expiration_date' => array('value' => date("m/Y", strtotime($data["expiration_date"]))),
                    'moddate' => array('value' => IsicDate::getDateFormatted($data["moddate"])),
//                    'delivery' => array('value' => $this->getDeliveryName($this->txt, $data["delivery_id"])),
                );
                foreach ($this->listSortFields as $fkey => $fval) {
                    $dUrl = array_key_exists('url', $dataValues[$fkey]) ? $dataValues[$fkey]['url'] : $rowUrl;
                    $tpl->addDataItem("DATA.DATA.URL", $dUrl);
                    $tpl->addDataItem("DATA.DATA.VALUE", $dataValues[$fkey]['value']);
                }
                if ($show_confirm_box) {
                    if (($can_distribute || $can_activate)) {
                        $f = new AdminFields("confirm_card[" . $data["id"] . "]", array("type" => "checkbox"));
                        $field_data = $f->display($confirm_card[$data["id"]]);
                        $tpl->addDataItem("DATA.CONFIRM.DATA", $field_data);
                    } else {
                        $tpl->addDataItem("DATA.CONFIRM.DATA", '');
                    }
                }
            }
        }

        $res->free();

        if ($this->user_type == $this->user_type_admin) {
            if (in_array($this->list_type, $confirm_states)) {
                $tpl->addDataItem("CONFIRM_TITLE.TITLE", $this->txt->display("check"));
                $tpl->addDataItem("CHECK_ALL.DUMMY", "");

                if ($this->list_type != $this->c_state_distributed) {
                    $tpl->addDataItem("CONFIRM_BUTTON.DISTRIBUTE.TITLE", $this->txt->display("distribute"));
                }
                if ($this->list_type != $this->c_state_activated) {
                    $tpl->addDataItem("CONFIRM_BUTTON.ACTIVATE.TITLE", $this->txt->display("activate"));
                }
            }
        }
        // page listing

        $res =& $this->db->query("
            SELECT
                COUNT(DISTINCT `module_isic_card`.`id`) AS cards_total
            FROM
                {$this->table_module_default}
            JOIN
                `module_isic_card_validities` ON `module_isic_card_validities`.`card_id` = {$this->table_module_default}.`id`
            JOIN
                `module_user_status_user` ON `module_isic_card_validities`.`user_status_id` = `module_user_status_user`.`id`
            WHERE
                 {$this->table_module_default}.`exported` > ?
                 !",
                 $this->isic_common->empty_date,
                 $condition_sql
        );
        IsicDB::assert($res);
        $data = $res->fetch_assoc();
        $total = $results = $data["cards_total"];
        $disp = ereg_replace("{NR}", "$total", $this->txt->display("results"));
        if ($results >= $this->maxresults) {
            $end = $start + $this->maxresults;
        } else {
            $end = $start + $results;
        }
        if ($end == 0) {
            $start0 = 0;
        }
        else {
            $start0 = $start + 1;
        }
        $disp = str_replace("{DISP}", $start0 . "-$end", $disp);
        $tpl->addDataItem("RESULTS", $disp);

        $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . $url_filter . "&sort=" . $listSortOrder->getSort() . "&sort_order=" . $listSortOrder->getSortOrder(), $this->maxresults, $this->txt->display("prev"), $this->txt->display("next")));

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
        }

        // filter fields are only shown to admin-user
        if ($this->user_type == $this->user_type_admin) {
            $this->showListFilter($this->txt, $tpl, $general_url);
        }

        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        $listSortOrder->showTitleFields($tpl, $this->txt, $general_url);
        $tpl->addDataItem("URL_GENERAL_PLAIN", $general_url_plain);
        $tpl->addDataItem("URL_GENERAL", $general_url . $url_filter);
        $tpl->addDataItem("URL_ADD", $general_url . $url_filter . "&action=add");
        $tpl->addDataItem("URL_IMPORT", $general_url . $url_filter . "&action=addmass");
        $tpl->addDataItem("SELF", $general_url);
        $tpl->addDataItem("HIDDEN", $hidden);

        $tpl->addDataItem("CONFIRMATION", $this->txt->display("confirmation"));
        return $tpl->parse();
    }

    private function getDeliveryName($txt, $id) {
        if (!$id) {
            return '-';
        }
        if ($id == 1) {
            return $txt->display('delivery_type1');
        }
        return $txt->display('delivery_type2');
    }

    private function getCardListFiltersAsArray() {
      $base_query_fields = array();
      $ff_fields = array(
        "kind_id", "bank_id", "type_id", "school_id", "person_name_first", "person_name_last", "person_number", "isic_number", "card_number", "structure_unit", "class"
      );

      for ($f = 0; $f < sizeof($ff_fields); $f++) {
        //$t_filter_value = $this->vars["filter_".$ff_fields[$f]] = urldecode($this->vars["filter_".$ff_fields[$f]]);
        $t_filter_value = $this->vars["filter_".$ff_fields[$f]];
        if ($t_filter_value != "" && $t_filter_value != "0") {
          $base_query_fields[$ff_fields[$f]] = $t_filter_value;
        }
      }
      return $base_query_fields;
    }


    private function getCardListFiltersAsSql() {
      $ff_fields_partial = array(
        "person_name_first", "person_name_last", "person_number", "isic_number", "card_number", "structure_unit", "class"
      );
      $ff_tables = array(
        "school_id" => "module_isic_card_validities",
        "structure_unit" => "module_user_status_user",
        "class" => "module_user_status_user"
      );
      $condition = array();
      $card_list_filter_fields = $this->getCardListFiltersAsArray();
      foreach ($card_list_filter_fields as $ff_field_name => $t_filter_value) {
        if (array_key_exists($ff_field_name, $ff_tables)) {
           $fieldName = $this->db->quote_field_name($ff_tables[$ff_field_name]) . "." . $this->db->quote_field_name($ff_field_name);
        } else {
           $fieldName = $this->db->quote_field_name($this->table_module_default)  . "." . $this->db->quote_field_name($ff_field_name);
        }
        if (in_array($ff_field_name, $ff_fields_partial)) {
          $condition[] = $fieldName . " LIKE " . $this->db->quote("%" . $t_filter_value . "%");
        }
        else {
          $condition[] = $fieldName . " = " . $this->db->quote($t_filter_value);
        }
      }
      return $condition;
    }


    private function getCardListFiltersAsUrl() {
        $card_list_filter_fields = $this->getCardListFiltersAsArray();
        $url_filter = '';
        foreach ($card_list_filter_fields as $ff_field_name => $t_filter_value) {
            $url_filter .= "&filter_" . $ff_field_name . "=" . urlencode($t_filter_value);
        }
        return $url_filter;
    }

    private function getCardListFiltersAsHidden() {
        $card_list_filter_fields = $this->getCardListFiltersAsArray();
        $hidden = '';
        foreach ($card_list_filter_fields as $ff_field_name => $t_filter_value) {
            $hidden .= IsicForm::getHiddenField('filter_' . $ff_field_name, $t_filter_value);
        }
        return $hidden;
    }

    private function getCardListQueryFilter() {
        $condition = $this->getCardListFiltersAsSql();
        // different view-filters (so called list_types)
        switch ($this->list_type) {
            case $this->c_state_ordered:
            case $this->c_state_distributed:
            case $this->c_state_activated:
                $condition[] = "{$this->table_module_default}.`state_id` = " . $this->list_type;
                $condition[] = "`module_isic_card_validities`.`user_status_active` = 1";
            break;
            case $this->c_state_deactivated:
                $subcondition = array();
                $subcondition[] = "{$this->table_module_default}.`state_id` = " . $this->c_state_deactivated;
                $subcondition[] = "`module_isic_card_validities`.`user_status_active` = 0";
                $condition[] = "(" . implode(" OR ", $subcondition) . ")";
            break;
        }
        // restrictions based on user-type

        switch ($this->user_type) {
            case $this->user_type_admin: // admin
                if (!IsicDB::factory('Users')->isCurrentUserSuperAdmin()) {
                  $allowedTypes = IsicDB::getIdsAsArray($this->allowed_card_types_view);
                  $condition[] = "{$this->table_module_default}.`type_id` IN (" . implode(',', $allowedTypes) . ")";
                }
            break;
            case $this->user_type_user: // regular
                $condition[] = "{$this->table_module_default}.`person_number` IN (" .
                    implode(',', $this->isic_common->getArrayQuoted($this->isic_common->getCurrentUserCodeList())) .
                    ")";
            break;
        }

        // if bind-query was requested then creating additional filter with only binded card id-s
        if ($this->vars["bind_id"] && $this->vars["filter_type_id"]) {
            $bind_data = $this->isic_common->getApplicationBindings($this->isic_common->getApplicationRecord($this->vars["bind_id"]));
            if (is_array($bind_data) && is_array($bind_data["card"]) && is_array($bind_data["card"][$this->vars["filter_type_id"]])) {
                $bcondition = array();
                foreach ($bind_data["card"][$this->vars["filter_type_id"]] as $bval) {
                    $bcondition[] = "{$this->table_module_default}.`id` = " . $this->db->quote($bval["id"]) . "";
                }
                $condition[] = "(" . implode(" OR ", $bcondition) . ")";
            }
        }
        $activeSchool = $this->isicUser->getActiveSchool();
        if (!IsicDB::factory('Users')->isCurrentUserSuperAdmin() || $activeSchool) {
           $condition[] = "(`module_isic_card_validities`.`school_id` IN (" .
               implode(",", $this->allowed_schools) . ")" .
               $this->getAllowedSchoolCountCondition() .
               ")";
        }

        if ($this->vars['filter_delivery_id']) {
            $delivery_id = intval($this->vars['filter_delivery_id']);
            if ($delivery_id == 1) {
                $condition[] = "{$this->table_module_default}.`delivery_id` = " . $this->db->quote($delivery_id);
            } else if ($delivery_id > 1) {
                $condition[] = "{$this->table_module_default}.`delivery_id` > " . $this->db->quote($delivery_id);
            }
        }

        if ($this->vars['filter_region_id']) {
            $regionSchools = $this->isicDbSchools->getIdListByRegion($this->vars['filter_region_id']);
            $condition[] = "(`module_isic_card_validities`.`school_id` IN (" . implode(",", $regionSchools) . "))";
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql = " AND " . $condition_sql;
        }
        return $condition_sql;
    }

    private function getAllowedSchoolCountCondition() {
        return $this->user_type == $this->user_type_user ?
            (" OR " . count($this->allowed_schools) . " = " . count($this->allowed_schools_all))
            : ''
        ;
    }

    private function showListFilter($txt, $tpl, $general_url) {
        $fields = array(
            "filter_active" => array("select",0,0,$this->vars["filter_active"],"","","i120"),
            "filter_kind_id" => array("select",0,0,$this->vars["filter_kind_id"],"","","i120"),
            "filter_bank_id" => array("select",0,0,$this->vars["filter_bank_id"],"","","i120"),
            "filter_type_id" => array("select",0,0,$this->vars["filter_type_id"],"","","i120"),
            "filter_region_id" => array("select", 0,0,$this->vars["filter_region_id"],"","","i120"),
            "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],"","","i120"),
            "filter_person_name_first" => array("textinput", 40,0,$this->vars["filter_person_name_first"],"","","i120"),
            "filter_person_name_last" => array("textinput", 40,0,$this->vars["filter_person_name_last"],"","","i120"),
            "filter_person_number" => array("textinput", 40,0,$this->vars["filter_person_number"],"","","i120"),
            "filter_delivery_addr1" => array("textinput", 40,0,$this->vars["filter_delivery_addr1"],"","","i120"),
            "filter_delivery_addr2" => array("textinput", 40,0,$this->vars["filter_delivery_addr2"],"","","i120"),
            "filter_delivery_addr3" => array("textinput", 40,0,$this->vars["filter_delivery_addr3"],"","","i120"),
            "filter_delivery_addr4" => array("textinput", 40,0,$this->vars["filter_delivery_addr4"],"","","i120"),
            "filter_isic_number" => array("textinput", 40,0,$this->vars["filter_isic_number"],"","","i120"),
            "filter_card_number" => array("textinput", 40,0,$this->vars["filter_card_number"],"","","i120"),
            "filter_structure_unit" => array("textinput", 40,0,$this->vars["filter_structure_unit"],"","","i120"),
            "filter_class" => array("textinput", 40,0,$this->vars["filter_class"],"","","i120"),
            "filter_delivery_id" => array("select", 0,0,$this->vars["filter_delivery_id"],"","","i120"),
        );

        // active selection
        $list = array();
        for ($i = 2; $i >= 0; $i--) {
            $list[$i] = $this->txt->display("active" . $i);
        }
        $fields["filter_active"][4] = $list;

        // card kinds
        $list = array();
        $r = &$this->db->query("
            SELECT
                `module_isic_card_kind`.*
            FROM
                `module_isic_card_kind`
            ORDER BY
                `module_isic_card_kind`.`id`
            ");

        $list[0] = $this->txt->display("all_kinds");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        $fields["filter_kind_id"][4] = $list;

        $fields["filter_bank_id"][4] = $this->isicDbBanks->getBankList($this->txt->display('all_bank'));

        // card types
        $list = array();
        $r = &$this->db->query("
            SELECT
                `module_isic_card_type`.`id`,
                `module_isic_card_type`.`name`
            FROM
                `module_isic_card_type`
            WHERE
                `module_isic_card_type`.`id` IN (!@)
            ORDER BY
                `module_isic_card_type`.`name`
            ",
            IsicDB::getIdsAsArray($this->allowed_card_types_view)
        );

        $list[0] = $this->txt->display("all_types");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        $fields["filter_type_id"][4] = $list;

        // regions
        $list = array();
        $r = &$this->db->query("
            SELECT
                `module_isic_region`.`id`,
                `module_isic_region`.`name`
            FROM
                `module_isic_region`,
                `module_isic_school`
            WHERE
                `module_isic_region`.`id` = `module_isic_school`.`region_id`AND
                `module_isic_school`.`id` IN (!@)
            GROUP BY
                `module_isic_region`.`id`
            ORDER BY
                `module_isic_region`.`name`
            ",
            IsicDB::getIdsAsArray($this->allowed_schools)
        );

        $list[0] = $this->txt->display("all_regions");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        $fields["filter_region_id"][4] = $list;

        // schools
        $list = array();
        $r = &$this->db->query("
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
            ",
            IsicDB::getIdsAsArray($this->allowed_schools)
        );

        $list[0] = $this->txt->display("all_schools");
        while ($data = $r->fetch_assoc()) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $list[$data["id"]] = $data["name"];
        }
        $fields["filter_school_id"][4] = $list;

        $list = array();
        $list[0] = $this->txt->display('delivery_type0');
        $list[1] = $this->txt->display('delivery_type1');
        $list[2] = $this->txt->display('delivery_type2');
        $fields['filter_delivery_id'][4] = $list;


        foreach ($fields as $key => $val) {
            $fdata = array();
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
        }
        $tpl->addDataItem("SEARCH.SELF", $general_url);
    }

    /**
     * Displays detail view of a card
     *
     * @param int $card card id
     * @return string html detailview of a card
    */

    function showCard($card) {
        if ($this->checkAccess() == false) return "";

        $instanceParameters = '&type=showcard';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_card_show.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($error == true) {
            if ($error_prolong) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_prolong"));
            } elseif ($error_distribute) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_distribute"));
            } elseif ($error_activate) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_activate"));
            } elseif ($error_deactivate) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_deactivate"));
            } elseif ($error_replace) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_replace"));
            } elseif ($error_return) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_return"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error"));
            }
        } elseif ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        $data = $this->isicDbCards->getRecord($card);
        if ($data) {
            if ($this->isic_common->canViewCard($data)) {
                if (!$this->isicDbSchools->isHiddenSchool($data['school_id'])) {
                    $tpl->addDataItem("SCHOOL.DATA_school_name", $data["school_name"]);
                }
                $deliveryAddress = $this->getDeliveryAddress($data);
                $tpl->addDataItem("DATA_active", $this->txt->display("active" . $data["active"]));
                $tpl->addDataItem("DATA_state_name", $data["state_name"]);
                $tpl->addDataItem("DATA_language_name", $data["language_name"]);
                $tpl->addDataItem("DATA_kind_name", $data["kind_name"]);
                $tpl->addDataItem("DATA_bank_name", $data["bank_name"]);
                $tpl->addDataItem("DATA_type_name", $data["type_name"]);
                $tpl->addDataItem("DATA_person_name_first", $data["person_name_first"]);
                $tpl->addDataItem("DATA_person_name_last", $data["person_name_last"]);
                $tpl->addDataItem("DATA_person_birthday", IsicDate::getDateFormatted($data["person_birthday"]));
                $tpl->addDataItem("DATA_person_number", $data["person_number"]);
                $tpl->addDataItem("DATA_delivery_addr1", $deliveryAddress["delivery_addr1"]);
                $tpl->addDataItem("DATA_delivery_addr2", $deliveryAddress["delivery_addr2"]);
                $tpl->addDataItem("DATA_delivery_addr3", $deliveryAddress["delivery_addr3"]);
                $tpl->addDataItem("DATA_delivery_addr4", $deliveryAddress["delivery_addr4"]);
                $tpl->addDataItem("DATA_person_email", $data["person_email"]);
                $tpl->addDataItem("DATA_person_phone", $data["person_phone"]);
                $tpl->addDataItem("DATA_activation_date", $data["activation_date"] == "0000-00-00" ? "-" : IsicDate::getDateFormatted($data["activation_date"]));
                $tpl->addDataItem("DATA_expiration_date", $data["expiration_date"] == "0000-00-00" ? "-" : IsicDate::getDateFormatted($data["expiration_date"]));
                $tpl->addDataItem("DATA_isic_number", $data["isic_number"]);
                $tpl->addDataItem("DATA_card_number", $data["card_number"]);
                $tpl->addDataItem("DATA_pic", IsicImage::getPictureUrlOrDummyUrlIfNotFound($data['pic'], 'big'));

                if ($data['state_id'] == $this->isicDbCards->getStateDeactivated()) {
                    $dbUser = $this->isicDbUsers = IsicDB::factory('Users');
                    $userData = $dbUser->getRecord($data['deactivation_user']);
                    $tpl->addDataItem("DEACTIVATION.DATA_reason", $data["status_name"]);
                    $tpl->addDataItem("DEACTIVATION.DATA_user", $userData['name_first'] . ' ' . $userData['name_last']);
                    $tpl->addDataItem("DEACTIVATION.DATA_date", IsicDate::getDateFormatted($data["deactivation_time"]));
                }

                if ($this->isic_common->canDistributeCard($data)) {
                    $tpl->addDataItem("DISTRIBUTE.TITLE", $this->txt->display("distribute"));
                    $tpl->addDataItem("DISTRIBUTE.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=distribute&write=true", array("card_id")));
                }

                if ($this->isic_common->canActivateCard($data)) {
                    $tpl->addDataItem("ACTIVATE.TITLE", $this->txt->display("activate"));
                    $tpl->addDataItem("ACTIVATE.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=activate&write=true", array("card_id")));
                }

                if ($this->isic_common->canDeactivateCard($data)) {
                    $tpl->addDataItem("DEACTIVATE.TITLE", $this->txt->display("deactivate"));
                    $tpl->addDataItem("DEACTIVATE.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=deactivate", array("card_id")));
                }

                if ($this->isic_common->canReturnCard($data)) {
                    $tpl->addDataItem("RETURN.TITLE", $this->txt->display("return"));
                    $tpl->addDataItem("RETURN.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=return&write=true", array("card_id")));
                }

                $appl_exists = $this->isic_common->getUserApplicationTypeExists($data["person_number"], $data["type_id"], 0);
                $card_exists = $this->isic_common->getUserCardTypeExistsOrderedDistributed($data["person_number"], $data["type_id"]);

                if (!$appl_exists && !$card_exists) {
                    if ($this->isic_common->canReplaceCard($data)) {
                        $tpl->addDataItem("REPLACE.TITLE", $this->txt->display("replace"));
                        $tpl->addDataItem("REPLACE.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=replace&write=true", array("card_id")));
                    }

                    if ($this->isic_common->canProlongCard($data)) {
                        $tpl->addDataItem("PROLONG.TITLE", $this->txt->display("prolong"));
                        $tpl->addDataItem("PROLONG.URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=prolong&write=true", array("card_id")));
                    }
                }

                // fill card validities
                $validities = IsicDB::factory('CardValidities');
                $validitiesList = $validities->listRecordsByCard($data['id']);
                foreach($validitiesList as $validityData) {
                    if ($validityData['school_id'] != $this->isicDbSchools->getHiddenSchoolId()) {
                        $tpl->addDataItem("VALIDITY.SCHOOL", $validityData['school_name']);
                        $tpl->addDataItem("VALIDITY.STRUCTURE_UNIT", $validityData['status_structure_unit']);
                        $tpl->addDataItem("VALIDITY.CLASS", $validityData['status_class']);
                        $tpl->addDataItem("VALIDITY.COURSE", $validityData['status_course']);
                        $tpl->addDataItem("VALIDITY.POSITION", $validityData['status_position']);
                        $tpl->addDataItem("VALIDITY.ACTIVE", $this->txt->display("active" . $validityData['user_status_active']));
                    }
                }

            } else {
                redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=view", array("card_id")));
            }
        }

        if (!$this->vars["bind"]) {
            $tpl->addDataItem("BACK.BACK", $this->txt->display("back"));
            if ($this->vars["content_prev"]) {
                $tpl->addDataItem("BACK.URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "content=" . $this->vars["content_prev"], array("card_id", "info", "action", "content")));
            } else {
                $tpl->addDataItem("BACK.URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("card_id", "info", "action")));
            }
        }
        return $tpl->parse();
    }

    private function getDeliveryAddress($cardData) {
        $address = array(
            'delivery_addr1' => $cardData['delivery_addr1'],
            'delivery_addr2' => $cardData['delivery_addr2'],
            'delivery_addr3' => $cardData['delivery_addr3'],
            'delivery_addr4' => $cardData['delivery_addr4'],
        );

        if ($cardData['delivery_id'] > 1) {
            $shipmentAddress = $this->getDeliveryShipmentAddress($cardData['delivery_id']);
            if ($shipmentAddress) {
                $address['delivery_addr1'] = $shipmentAddress['delivery_addr1'];
                $address['delivery_addr2'] = $shipmentAddress['delivery_addr2'];
                $address['delivery_addr3'] = $shipmentAddress['delivery_addr3'];
                $address['delivery_addr4'] = $shipmentAddress['delivery_addr4'];
            }
        }

        return $address;
    }

    private function getDeliveryShipmentAddress($deliveryId) {
        $sql = '
            SELECT
                module_isic_card_shipment.*
            FROM
                module_isic_card_shipment,
                module_isic_card_delivery
            WHERE
                module_isic_card_shipment.id = module_isic_card_delivery.shipment_id AND
                module_isic_card_delivery.id = !
        ';

        $result = &$this->db->query($sql, $deliveryId);
        if (!$result) {
            return false;
        }
        return $result->fetch_assoc();
    }

    /**
     * Displays confirmation view of a card before saving changes
     *
     * @param int $card card id
     * @param string $action action (add/modify)
     * @return string html addform for cards
    */

    function modifyCard($card, $action)
    {
        if ($this->checkAccess() == false) return "";
        if (!$card && $this->vars["card_id"]) {
            $card = $this->vars["card_id"];
        }
        if ($this->vars["action"]) {
            $action = $this->vars["action"];
        }
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $txtf = new Text($this->language, "output");

        if ($card) {
            $card_data = $this->isic_common->getCardRecord($card);
        }

        if ($card_data) {
            if ($write) {
                $appl_exists = $this->isic_common->getUserApplicationTypeExists($card_data["person_number"], $card_data["type_id"], 0);
                $card_exists = $this->isic_common->getUserCardTypeExistsOrderedDistributed($card_data["person_number"], $card_data["type_id"]);
                switch ($action) {
                    case "prolong":
                        if (!$appl_exists && !$card_exists && $this->isic_common->canProlongCard($card_data)) {
                            $t_prolong_id = $this->isicDbCardStatuses->getCardStatusProlongId($card_data["type_id"]);
                            $this->isicDbCards->prolong($card, $t_prolong_id);
                            if ($this->user_type == $this->user_type_admin) {
                                $admin_redirect_data = true;
                            } else if ($this->user_type == $this->user_type_user) {
                                    $appl_id = $this->isic_common->createApplicationFromCard($card, $this->userid);
                                    if ($appl_id) {
                                        $user_redirect_appl = $appl_id;
                                    } else {
                                        $write_ok = true;
                                    }
                            }
                        } else {
                            $error = $error_prolong = true;
                        }
                    break;
                    case "distribute":
                        if ($this->isic_common->canActivateCard($card_data)) {
                            $this->isicDbCards->distribute($card);
                            $write_ok = true;
                            IsicMail::sendCardDistributionNotification($card_data);
                        } else {
                            $error = $error_distribute = true;
                        }
                    break;
                    case "activate":
                        if ($this->isic_common->canActivateCard($card_data)) {
                            $this->isicDbCards->activate($card);
                            // also deactivating all other cards
                            $this->isicDbCards->deActivateOtherCards($card_data);
                            $write_ok = true;
                        } else {
                            $error = $error_activate = true;
                        }
                    break;
                    case "deactivate":
                        if ($this->vars["status_id"] && $this->isic_common->canDeactivateCard($card_data)) {
                            $this->isicDbCards->deactivate($card, $this->vars['status_id']);
                            $write_ok = true;
                        } else {
                            $error = $error_deactivate = true;
                        }
                    break;
                    case "replace":
                        if (!$appl_exists && !$card_exists && $this->isic_common->canReplaceCard($card_data)) {
                            $this->isicDbCards->replace($card, 0);
                            if ($this->user_type == $this->user_type_admin) {
                                $admin_redirect_data = true;
                            } else if ($this->user_type == $this->user_type_user) {
                                    $appl_id = $this->isic_common->createApplicationFromCard($card, $this->userid);
                                    if ($appl_id) {
                                        $user_redirect_appl = $appl_id;
                                    } else {
                                        $write_ok = true;
                                    }
                            }
                        } else {
                            $error = $error_replace = true;
                        }
                    break;
                    case "return":
                        if ($this->isic_common->canReturnCard($card_data)) {
                            $this->isicDbCards->returned($card);
                            $redirect_show = true;
                        } else {
                            $error = $error_return = true;
                        }
                    break;
                    default :
                        $tmpl_name = "error";
                    break;
                }
                if ($user_redirect_appl) {
                    $t_template = $this->isic_common->template_application_modify_user;
                    $general_url_appl = $this->isic_common->getGeneralUrlByTemplate($t_template);
                    redirect($general_url_appl . "&appl_id=" . $user_redirect_appl . "&action=modify");
                } elseif ($admin_redirect_data) {
                    $t_template = $this->isic_common->template_application_prolong_replace_admin;
                    $general_url_appl = $this->isic_common->getGeneralUrlByTemplate($t_template);
                    redirect($general_url_appl . "&action=add&school_id=" . $card_data['school_id'] .
                        "&type_id=" . $card_data['type_id'] . "&person_number=" . $card_data['person_number']);
                } elseif ($write_ok) {
                    redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "info=" . $action, array("card_id", "action", "write")));
                } elseif ($redirect_show) {
                    redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "info=" . $action, array("action", "write")));
                }
            }
        } else {
            redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=" . $action, array("card_id", "action")));
        }

        // ###################################

        $instanceParameters = '&type=modifycard';
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_card_modify_confirm.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($error == true) {
            if ($error_prolong) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_prolong"));
            } elseif ($error_distribute) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_distribute"));
            } elseif ($error_activate) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_activate"));
            } elseif ($error_deactivate) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_deactivate"));
            } elseif ($error_replace) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_replace"));
            } elseif ($error_return) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error_return"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $this->txt->display("modify_error"));
            }
        } elseif ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $this->txt->display($this->info_message[$this->vars["info"]]));
        }

        switch ($action) {
            case "deactivate":
                $fields = array(
                    "status_id" => array("select",0,0,$this->vars["status_id"],"","","isic300", "status_help"),
                );

                // card statuses
                $list = array();
                if ($card_data["type_id"]) {
                    $r = &$this->db->query("
                        SELECT
                            `module_isic_card_status`.*
                        FROM
                            `module_isic_card_status`
                        WHERE
                            `module_isic_card_status`.`card_type` = ! AND
                            `module_isic_card_status`.`action_type` = 0
                        ORDER BY
                            `module_isic_card_status`.`name`
                        ",
                        $card_data["type_id"]);

                    while ($data = $r->fetch_assoc()) {
                        $list[$data["id"]] = $data["name"];
                    }
                }
                $fields["status_id"][4] = $list;
            break;
            case "replace":
                $fields = array(
                    "status_id" => array("select",0,0,$this->vars["status_id"],"","","isic300", "status_help"),
                );

                // card statuses
                $list = array();
                if ($card_data["type_id"]) {
                    $r = &$this->db->query("
                        SELECT
                            `module_isic_card_status`.*
                        FROM
                            `module_isic_card_status`
                        WHERE
                            `module_isic_card_status`.`card_type` = ! AND
                            `module_isic_card_status`.`action_type` = 1
                        ORDER BY
                            `module_isic_card_status`.`name`
                        ",
                        $card_data["type_id"]);

                    while ($data = $r->fetch_assoc()) {
                        $list[$data["id"]] = $data["name"];
                    }
                }
                $fields["status_id"][4] = $list;
            break;
            default :
            break;
        }

        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];
                $fdata["class"] = $val[6];

                $f = new AdminFields("$key",$fdata);
                if ($fdata["type"] == "textfield") {
                    $f->classTextarea = "isic300";
                }
                $field_data = $f->display($val[3]);
                $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);

                $sub_tpl_name = strtoupper($key);
                $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
//                $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                if ($val[7]) {
                    $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $this->txt->display($val[7]))));
                }
            }
            $tpl->addDataItem("SUBMIT.BUTTON", $this->txt->display("ok"));
        }

        $hidden = IsicForm::getHiddenField('action', $action);
        $hidden .= IsicForm::getHiddenField('write', 'true');
        $hidden .= IsicForm::getHiddenField('card_id', $card);
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("card_id", "action", "write")));
        $tpl->addDataItem("URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("card_id", "action", "write")));
        $tpl->addDataItem("URL_BACK_CONFIRM", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "card_id=" . $card, array("card_id", "action", "write")));

        return $tpl->parse();
    }

    /**
     * Check does the active user have access to the page/form
     *
     * @access private
     * @return boolean
     */

    function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

    /**
     * Returns module parameters array
     *
     * @return array module parameters
    */

    function getParameters() {
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

    function moduleOptions() {
        $sq = new sql;

        $list = array();
        $list["0"] = $this->txt->display("all");
        $r = &$this->db->query("SELECT * FROM `module_isic_card_state` ORDER BY `name`");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }

        // ####
        return array(
            $this->txt->display("list_type"), "select", $list
        );
        // name, type, list
    }
}
