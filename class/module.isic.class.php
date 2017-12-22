<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicEncoding.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicPayment.php");
//require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Database.php");

class isic {
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
     * Default language for new isic card
     *
     * @var int
     * @access protected
     */

    var $language_default = 3;

    /**
     * Default kind type for new isic card
     *
     * @var int
     * @access protected
     */

    var $kind_default = 1;

    /**
     * List view type
     *
     * @var string (all, ordered, void)
     * @access protected
     */

    var $list_type = "all";

    /**
     * Image size
     *
     * @var string
     * @access protected
     */

//    var $image_size = '261x261';
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
     * Payment required array for card_types
     *
     * @var array
     * @access protected
     */

    var $auto_export = false;

    /**
     * Collateral sum array for card_types
     *
     * @var array
     * @access protected
     */

    var $collateral_sum = false;

    /**
     * First payment sum array for card_types
     *
     * @var array
     * @access protected
     */

    var $first_sum = false;

    /**
     * Card cost sum array for card_statuses
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

    var $csv_import_fields = array("person_number", "person_name_first", "person_name_last", "person_email", "person_phone", "person_addr1", "person_addr2", "person_addr3", "person_addr4", "person_position", "person_class", "person_stru_unit");

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_card";


   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function isic () {
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
        $this->isic_encoding = new IsicEncoding();
        $this->isic_payment = new IsicPayment();

        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->allowed_card_types = $this->isic_common->createAllowedCardTypes();
        $this->auto_export = $this->isic_common->getCardTypeAutoExport();
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
        $step = @$this->vars["step"];
        $card_id = @$this->vars["card_id"];

        if (!$this->userid) {
            trigger_error("Module 'ISIC' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        if ($this->user_type == 2 && !$this->user_code) {
            trigger_error("Module 'ISIC' user must have ID-code to be assigned. Contact administrator.", E_USER_ERROR);
        }

        if ($action == "add") {
            $result = $this->addCard(false, $action);
        }
        else if ($card_id && ($action == "modify" || $action == "replace" || $action == "prolong")) {
            $result = $this->addCard($card_id, $action);
        }
        else if ($card_id && $action == "delete") {
            $result = $this->deleteCard($card_id);
        }
        else if ($action == "addmass") {
            $result = $this->addCardMass($action, $step);
        }
        else if ($card_id && !$action) {
            $result = $this->showCard($card_id);
        }
        else {
            $result = $this->showCardList();
        }
        return $result;
    }

    /**
     * Displays list of cards
     *
     * @return string html listview of cards
    */

    function showCardList() {
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $general_url_plain = $general_url;

        if (!$start) {
            $start = 0;
        }

        $txt = new Text($this->language, "module_isic_card");
        $txtf = new Text($this->language, "output");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_list.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=isic&type=cardlist&sort=" . $this->vars["sort"] . "&sort_order=" . $this->vars["sort_order"]);
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

        // #################################

        $ff_fields = array(
            "kind_id", "bank_id", "type_id", "school_id", "person_name_first", "person_name_last", "person_number", "isic_number", "card_number"
        );

        $ff_fields_partial = array(
            "person_name_first", "person_name_last", "person_number", "isic_number", "card_number"
        );

        $condition = array();
        for ($f = 0; $f < sizeof($ff_fields); $f++) {
            if ($this->vars["filter_".$ff_fields[$f]] != "" && $this->vars["filter_".$ff_fields[$f]] != "0") {
                if (in_array($ff_fields[$f], $ff_fields_partial)) {
                    $condition[] = $this->db->quote_field_name("module_isic_card." . $ff_fields[$f]) . " LIKE " . $this->db->quote("%" . $this->vars["filter_".$ff_fields[$f]] . "%");
                }
                else {
                    $condition[] = $this->db->quote_field_name("module_isic_card." . $ff_fields[$f]) . " = " . $this->db->quote($this->vars["filter_".$ff_fields[$f]]);
                }
                $url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($this->vars["filter_".$ff_fields[$f]]);
                $hidden .= "<input type=\"hidden\" name=\"filter_" . $ff_fields[$f] . "\" value=\"" . urlencode($this->vars["filter_".$ff_fields[$f]]) . "\">\n";
            }
        }

        // different view-filters (so called list_types)
        switch ($this->list_type) {
            case "all":
                // do nothing
            break;
            case "confirm_user":
                $condition[] = "`module_isic_card`.`active` = 0 AND `module_isic_card`.`exported` = '0000-00-00 00:00:00' AND `module_isic_card`.`status_id` = 0 AND `module_isic_card`.`confirm_user` = 1";
            break;
            case "confirm_user_not":
                $condition[] = "`module_isic_card`.`active` = 0 AND `module_isic_card`.`exported` = '0000-00-00 00:00:00' AND `module_isic_card`.`status_id` = 0 AND `module_isic_card`.`confirm_user` = 0";
            break;
            case "requested":
            case "first_time":
                $condition[] = "`module_isic_card`.`exported` = '0000-00-00 00:00:00' AND `module_isic_card`.`status_id` = 0";
            break;
            case "ordered":
                $condition[] = "`module_isic_card`.`active` = 0 AND `module_isic_card`.`expiration_date` > NOW() AND `module_isic_card`.`exported` > '0000-00-00 00:00:00' AND `module_isic_card`.`status_id` = 0";
            break;
            case "my_card":
                $condition[] = "`module_isic_card`.`exported` > '0000-00-00 00:00:00'";
            break;
            case "active":
                $condition[] = "`module_isic_card`.`active` = 1 AND `module_isic_card`.`expiration_date` > NOW() AND `module_isic_card`.`exported` > '0000-00-00 00:00:00'";
            break;
            case "void":
                $condition[] = "(`module_isic_card`.`active` = 0 OR `module_isic_card`.`expiration_date` < NOW()) AND `module_isic_card`.`exported` > '0000-00-00 00:00:00' AND `module_isic_card`.`status_id` > 0";
            break;
        }

        // restrictions based on user-type
        switch ($this->user_type) {
            case 1: // admin
                // do nothing, other filters will be in place
            break;
            case 2: // regular
                $condition[] = "`module_isic_card`.`person_number` = '" . mysql_escape_string($this->user_code) . "'";
            break;
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql = " AND " . $condition_sql;
        }

        // ####

        $oo_order = array(
            "pic" => "module_isic_card.pic",
            "active" => "module_isic_card.active",
            "card_type_name" => "card_type_name",
            "school_name" => "school_name",
            "person_birthday" => "module_isic_card.person_birthday",
            "person_name_first" => "module_isic_card.person_name_first",
            "person_name_last" => "module_isic_card.person_name_last",
            "person_number" => "module_isic_card.person_number",
            "isic_number" => "module_isic_card.isic_number",
            "confirm_payment_collateral" => "module_isic_card.confirm_payment_collateral",
            "confirm_payment_cost" => "module_isic_card.confirm_payment_cost",
            "person_stru_unit" => "module_isic_card.person_stru_unit",
            "expiration_date" => "module_isic_card.expiration_date"
        );

        if ($this->vars["sort"] != "" && $oo_order[$this->vars["sort"]]) {
            $hidden .= "<input type=\"hidden\" name=\"sort\" value=\"" . urlencode($this->vars["sort"]) . "\">\n";
            $hidden .= "<input type=\"hidden\" name=\"sort_order\" value=\"" . urlencode($this->vars["sort_order"]) . "\">\n";
            $order_by = $oo_order[$this->vars["sort"]];
            if ($this->vars["sort_order"] == "asc") {
                $sort_order = "asc";
                $sort_order1 = "desc";
            }
            else if ($this->vars["sort_order"] == "desc") {
                $sort_order = "desc";
                $sort_order1 = "asc";
            }
            else {
                $sort_order = "asc";
                $sort_order1 = "desc";
            }
        }

        if (!$order_by) {
            $this->vars["sort"] = "person_name_last";
            $order_by = "module_isic_card.person_name_last";
            $sort_order = "asc";
            $sort_order1 = "desc";
        }

        $hidden .= "<input type=\"hidden\" name=\"start\" value=\"" . $start . "\">\n";

        if ($this->user_type == 1) {
            $res =& $this->db->query("
                SELECT
                    `module_isic_card`.*,
                    IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                    IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                    IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                FROM
                    `module_isic_card`
                LEFT JOIN
                    `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                LEFT JOIN
                    `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
                LEFT JOIN
                    `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
                LEFT JOIN
                    `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                WHERE
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@)
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                $this->allowed_schools,
                $this->allowed_card_types,
                $condition_sql,
                $order_by,
                $sort_order,
                $start,
                $this->maxresults
            );
//            echo "<!-- " . $this->db->show_query() . " -->\n";
        } elseif ($this->user_type == 2) {
            $res =& $this->db->query("
                SELECT
                    `module_isic_card`.*,
                    IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                    IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                    IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                FROM
                    `module_isic_card`
                LEFT JOIN
                    `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                LEFT JOIN
                    `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
                LEFT JOIN
                    `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
                LEFT JOIN
                    `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                WHERE
                    1 = 1
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                $condition_sql,
                $order_by,
                $sort_order,
                $start,
                $this->maxresults
            );
        }

//        echo "<!-- SQL: " . $this->db->show_query() . " -->\n";

        $confirm_admin = $this->vars["confirm_admin"];
        $active = $this->vars["active"];

        if ($res !== false) {
            $card_count = $res->num_rows();
            if ($card_count == 1 && $this->user_type == 2) {
                $data = $res->fetch_assoc();
                return $this->addCard($data["id"], "modify");
            } elseif ($card_count) {
                while ($data = $res->fetch_assoc()) {
                    // setting the confirm_admin values for every record according to what user was specified
                    if ($data["pic"] != "") {
                        if (strpos($data["pic"], "_thumb") !== false) {
                            $big_picture = str_replace("_thumb", "", $data["pic"]);
                            $thumb_picture = $data["pic"];
                        } else {
                            $big_picture = $data["pic"];
                            $thumb_picture = str_replace(".", "_thumb.", $data["pic"]);
                        }

                        if (@file_exists(SITE_PATH . substr($thumb_picture, strpos($thumb_picture, "upload") - 1))) {
                            $tpl->addDataItem("DATA.IMAGE", "<img src=\"" . SITE_URL . $thumb_picture . "\" alt=\"\" border=\"\">");
                            $tpl->addDataItem("DATA.URL_IMAGE", "javascript:openPicture('" . SITE_URL . $big_picture."')");
                        } elseif (@file_exists(SITE_PATH . substr($big_picture, strpos($big_picture, "upload") - 1))) {
                            $tpl->addDataItem("DATA.IMAGE", "<img src=\"" . SITE_URL . $big_picture . "\" alt=\"\" border=\"\">");
                            $tpl->addDataItem("DATA.URL_IMAGE", "javascript:openPicture('" . SITE_URL . $big_picture."')");
                        } else {
                            $tpl->addDataItem("DATA.IMAGE", "<img src=\"" . SITE_URL . $data["pic"] . "\" alt=\"\" border=\"\">");
                            $tpl->addDataItem("DATA.URL_IMAGE", "#");
                        }
                    }
                    else {
                        $tpl->addDataItem("DATA.IMAGE", "<img src=\"img/nopic.gif\" alt=\"\" border=\"\">");
                        $tpl->addDataItem("DATA.URL_IMAGE", "#");
                    }

                    $tpl->addDataItem("DATA.DATA_ACTIVE", $txt->display("active" . $data["active"]));
                    $tpl->addDataItem("DATA.DATA_SCHOOL_NAME", $data["school_name"]);
                    $tpl->addDataItem("DATA.DATA_CARD_TYPE_NAME", $data["card_type_name"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_NAME_FIRST", $data["person_name_first"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_NAME_LAST", $data["person_name_last"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_BIRTHDAY", date("d/m/Y", strtotime($data["person_birthday"])));
                    $tpl->addDataItem("DATA.DATA_PERSON_NUMBER", $data["person_number"]);
                    $tpl->addDataItem("DATA.DATA_ISIC_NUMBER", $data["isic_number"]);
                    $tpl->addDataItem("DATA.DATA_CARD_NUMBER", $data["card_number"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_STRU_UNIT", $data["person_stru_unit"]);
                    $tpl->addDataItem("DATA.DATA_CONFIRM_PAYMENT_COLLATERAL", $this->isic_payment->getCardCollateralRequired($data["school_id"], $data["type_id"]) ? $txt->display("active" . $data["confirm_payment_collateral"]) : "-");
                    $is_card_first = $this->isic_common->isUserCardTypeFirst($data["person_number"], $data["id"], $data["type_id"]);
                    $tpl->addDataItem("DATA.DATA_CONFIRM_PAYMENT_COST", $this->isic_payment->getCardCostRequired($data["school_id"], $this->isic_common->getCardStatus($data["prev_card_id"]), $data["type_id"], $is_card_first) ? $txt->display("active" . $data["confirm_payment_cost"]) : "-");
                    $tpl->addDataItem("DATA.DATA_ACTIVATION_DATE", date("m/y", strtotime($data["activation_date"])));
                    $tpl->addDataItem("DATA.DATA_EXPIRATION_DATE", date("m/y", strtotime($data["expiration_date"])));
                    $tpl->addDataItem("DATA.URL_DETAIL", $general_url . "&card_id=" . $data["id"] . $url_filter . "&sort=".$this->vars["sort"]."&sort_order=" . $sort_order);
                    if ($this->isic_common->canModifyCard($data["school_id"], $data["person_number"]) /*&& !$data["status_id"]*/) {
                        $tpl->addDataItem("DATA.MOD.URL_MODIFY", $general_url . "&card_id=" . $data["id"] . $url_filter . "&sort=".$this->vars["sort"]."&sort_order=" . $sort_order . "&action=modify");
                    }
                    if ($this->isic_common->canDeleteCard($data["school_id"]) && $data["exported"] == "0000-00-00 00:00:00") {
                        $tpl->addDataItem("DATA.DEL.URL_DELETE", "javascript:del('".$general_url . "&card_id=" . $data["id"] . $url_filter . "&sort=".$this->vars["sort"]."&sort_order=" . $sort_order . "&action=delete" . "');");
                    }

                    if ($this->user_type == 1) {
                        if ($this->list_type == "requested" || $this->list_type == "confirm_user" || $this->list_type == "confirm_user_not") {
                            if ($data["exported"] == "0000-00-00 00:00:00") {
                                if ($this->vars["write_confirm"]) {
                                    $t_confirm = $confirm_admin[$data["id"]] ? 1 : 0;
                                    if ($t_confirm != $data["confirm_admin"]) {
                                        $row_old = $this->isic_common->getCardRecord($data["id"]);
                                        $res2 =& $this->db->query("UPDATE `module_isic_card` SET `moddate` = NOW(), `moduser` = !, `confirm_admin` = ! WHERE `id` = !", $this->userid, $t_confirm, $data["id"]);
                                        $data["confirm_admin"] = $t_confirm;
                                        // check if given card should be set as exported
                                        if ($this->auto_export[$data["type_id"]]) {
                                            $res2 =& $this->db->query("UPDATE `module_isic_card` SET `exported` = NOW() WHERE `id` = !", $data["id"]);
                                        }
                                        // saving changes made to the card to log-table
                                        $this->isic_common->saveCardChangeLog(2, $data["id"], $row_old, $this->isic_common->getCardRecord($data["id"]));
                                    }
                                }

                                $f = new AdminFields("confirm_admin[" . $data["id"] . "]", array("type" => "checkbox"));
                                $field_data = $f->display($data["confirm_admin"]);
                                $tpl->addDataItem("DATA.CONFIRM.DATA", $field_data);
                            } else {
                                $tpl->addDataItem("DATA.CONFIRM.DATA", $txt->display("active" . $data["confirm_admin"]));
                            }
                        } elseif ($this->list_type == "ordered") {
                            if ($data["exported"] != "0000-00-00 00:00:00") {
                                if ($this->vars["write_active"]) {
                                    $t_active = $active[$data["id"]] ? 1 : 0;
                                    if ($t_active != $data["active"]) {
                                        $row_old = $this->isic_common->getCardRecord($data["id"]);
                                        $res2 =& $this->db->query("UPDATE `module_isic_card` SET `moddate` = NOW(), `moduser` = !, `active` = !, `activation_date` = NOW() WHERE `id` = !", $this->userid, $t_active, $data["id"]);
                                        $data["active"] = $t_active;
                                        // saving changes made to the card to log-table
                                        $this->isic_common->saveCardChangeLog(2, $data["id"], $row_old, $this->isic_common->getCardRecord($data["id"]));

                                        // if card was marked as active and it's expiration is yet to come,
                                        // then de-activating all other cards of the same type for this user
                                        if ($t_active && (strtotime($data["expiration_date"]) > time())) {
                                            $r = &$this->db->query('
                                            SELECT
                                                `module_isic_card`.`id`
                                            FROM
                                                `module_isic_card`
                                            WHERE
                                                `module_isic_card`.`person_number` = ? AND
                                                `module_isic_card`.`id` <> ! AND
                                                `module_isic_card`.`type_id` = ! AND
                                                `module_isic_card`.`active` = !
                                            ',
                                               $data["person_number"],
                                               $data["id"],
                                               $data["type_id"],
                                               1);

                                            while ($t_data = $r->fetch_assoc()) {
                                                $row_old = $this->isic_common->getCardRecord($t_data["id"]);;
                                                $r2 = &$this->db->query('
                                                UPDATE
                                                    `module_isic_card`
                                                SET
                                                    `module_isic_card`.`moddate` = NOW(),
                                                    `module_isic_card`.`moduser` = ?,
                                                    `module_isic_card`.`active` = ?,
                                                    `module_isic_card`.`deactivation_date` = NOW()
                                                WHERE
                                                    `module_isic_card`.`id` = ! AND
                                                ', $this->userid,
                                                   0,
                                                   $t_data["id"]);

                                                // saving changes made to the card to log-table
                                                $this->isic_common->saveCardChangeLog(2, $t_data["id"], $row_old, $this->isic_common->getCardRecord($t_data["id"]));
                                            }
                                        }
                                    }
                                }

                                $f = new AdminFields("active[" . $data["id"] . "]", array("type" => "checkbox"));
                                $field_data = $f->display($data["active"]);
                                $tpl->addDataItem("DATA.CONFIRM.DATA", $field_data);
                            } else {
                                $tpl->addDataItem("DATA.CONFIRM.DATA", $txt->display("active" . $data["active"]));
                            }
                        }
                    }
                }
            } else {
                //echo("<!-- ut: " . $this->user_type . ", lt: " . $this->list_type . " -->\n");
                if (($this->user_type == 2) && ($this->list_type == "first_time")) {
                    $card_exist = true;
                    foreach ($this->allowed_card_types as $card_type) {
                        if (!$this->isic_common->getUserCardTypeExists($this->user_code, $card_type)) {
                            $card_exist = false;
                        }
                    }
                    if ($card_exist) {
                        return $this->isic_common->showErrorMessage("card_exists");
                    } else {
                        return $this->addCard(false, "add");
                    }
                } else {
                    $tpl->addDataItem("RESULTS", $txt->display("results_none"));
                }
            }
        } else {
            echo "Database error " . $this->db->error_code() . ": " . $this->db->error_string();
        }

        $res->free();

        if ($this->user_type == 1) {
            if ($this->list_type == "requested" || $this->list_type == "confirm_user" || $this->list_type == "confirm_user_not") {
                $hidden .= "<input type=\"hidden\" name=\"write_confirm\" value=\"1\">\n";
                $tpl->addDataItem("CONFIRM_TITLE.TITLE", $txt->display("confirm_title"));
                $tpl->addDataItem("CONFIRM_BUTTON.HIDDEN", $hidden);
                $tpl->addDataItem("CONFIRM_BUTTON.BUTTON", $txt->display("confirm_button"));
                $tpl->addDataItem("CHECK_ALL.DUMMY", "");
            } elseif ($this->list_type == "ordered") {
                $hidden .= "<input type=\"hidden\" name=\"write_active\" value=\"1\">\n";
                $tpl->addDataItem("CONFIRM_TITLE.TITLE", $txt->display("active_title"));
                $tpl->addDataItem("CONFIRM_BUTTON.HIDDEN", $hidden);
                $tpl->addDataItem("CONFIRM_BUTTON.BUTTON", $txt->display("active_button"));
                $tpl->addDataItem("CHECK_ALL.DUMMY", "");
            }
        }

        // page listing
        if ($this->user_type == 1) {
            $res =& $this->db->query("
                SELECT
                    COUNT(*) AS cards_total
                FROM
                    `module_isic_card`
                WHERE
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@)
                    !",
                $this->allowed_schools,
                $this->allowed_card_types,
                $condition_sql
            );
        } elseif ($this->user_type == 2) {
            $res =& $this->db->query("
                SELECT
                    COUNT(*) AS cards_total
                FROM
                    `module_isic_card`
                WHERE
                    1 = 1
                    !",
                $condition_sql
            );
        }

        $data = $res->fetch_assoc();
        $total = $results = $data["cards_total"];

        $disp = ereg_replace("{NR}", "$total", $txt->display("results"));
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

        $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . "&contact_id=" . $data["id"] . $url_filter . "&sort=".$this->vars["sort"]."&sort_order=" . $sort_order, $this->maxresults, $txt->display("prev"), $txt->display("next")));

        // ####

        switch ($this->vars["error"]) {
            case "modify":
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("error_modify"));
            break;
            case "delete":
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("error_delete"));
            break;
            case "view":
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("error_view"));
            break;
        }

        // filter fields are only shown to admin-user
        if ($this->user_type == 1) {
            $fields = array(
                "filter_active" => array("select",0,0,$this->vars["filter_active"],"","","i120"),
                "filter_language_id" => array("select",0,0,$this->vars["filter_language_id"],"","","i120"),
                "filter_kind_id" => array("select",0,0,$this->vars["filter_kind_id"],"","","i120"),
                "filter_bank_id" => array("select",0,0,$this->vars["filter_bank_id"],"","","i120"),
                "filter_type_id" => array("select",0,0,$this->vars["filter_type_id"],"","","i120"),
                "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],"","","i120"),
                "filter_person_name_first" => array("textinput", 40,0,$this->vars["filter_person_name_first"],"","","i120"),
                "filter_person_name_last" => array("textinput", 40,0,$this->vars["filter_person_name_last"],"","","i120"),
                "filter_person_number" => array("textinput", 40,0,$this->vars["filter_person_number"],"","","i120"),
                "filter_person_addr1" => array("textinput", 40,0,$this->vars["filter_person_addr1"],"","","i120"),
                "filter_person_addr2" => array("textinput", 40,0,$this->vars["filter_person_addr2"],"","","i120"),
                "filter_person_addr3" => array("textinput", 40,0,$this->vars["filter_person_addr3"],"","","i120"),
                "filter_person_addr4" => array("textinput", 40,0,$this->vars["filter_person_addr4"],"","","i120"),
                "filter_isic_number" => array("textinput", 40,0,$this->vars["filter_isic_number"],"","","i120"),
                "filter_card_number" => array("textinput", 40,0,$this->vars["filter_card_number"],"","","i120"),
                "expiration_date_m" => array("select",0,0,$this->vars["filter_expiration_date_m"],"","","i120"),
                "expiration_date_y" => array("select",0,0,$this->vars["filter_expiration_date_y"],"","","i120"),
            );

            // active selection
            $list = array();
            for ($i = 2; $i >= 0; $i--) {
                $list[$i] = $txt->display("active" . $i);
            }
            $fields["filter_active"][4] = $list;

            // card languages
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_language`.*
                FROM
                    `module_isic_card_language`
                ORDER BY
                    `module_isic_card_language`.`name`
                ');

            $list[0] = $txt->display("all_languages");
            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["filter_language_id"][4] = $list;

            // card kinds
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_kind`.*
                FROM
                    `module_isic_card_kind`
                ORDER BY
                    `module_isic_card_kind`.`id`
                ');

            $list[0] = $txt->display("all_kinds");
            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["filter_kind_id"][4] = $list;

            // banks
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_bank`.*
                FROM
                    `module_isic_bank`
                ORDER BY
                    `module_isic_bank`.`id`
                ');

            $list[0] = $txt->display("all_bank");
            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["filter_bank_id"][4] = $list;

            // card types
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_type`.*
                FROM
                    `module_isic_card_type`
                ORDER BY
                    `module_isic_card_type`.`name`
                ');

            $list[0] = $txt->display("all_types");
            while ($data = $r->fetch_assoc()) {
                if (in_array($data["id"], $this->allowed_card_types)) {
                    $list[$data["id"]] = $data["name"];
                }
            }
            $fields["filter_type_id"][4] = $list;

            // schools
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_school`.*
                FROM
                    `module_isic_school`
                ORDER BY
                    `module_isic_school`.`name`
                ');

            $list[0] = $txt->display("all_schools");
            while ($data = $r->fetch_assoc()) {
                if (in_array($data["id"], $this->allowed_schools)) {
                    $list[$data["id"]] = $data["name"];
                }
            }
            $fields["filter_school_id"][4] = $list;

            // expiration date months
            $list = array();
            for ($u = 1; $u < 13; $u++) {
                $list[substr("0".$u,-2)] = $txtf->display("month_".$u);
            }
            $fields["expiration_date_m"][4] = $list;

            // expiration date years
            $list = array();
            $beg_year = $row_data["expiration_date_y"] ? $row_data["expiration_date_y"] : date("Y");
            $end_year = date("Y") + 3;
            for ($u =  $beg_year; $u < $end_year; $u++) {
                $list[$u] = $u;
            }
            $fields["expiration_date_y"][4] = $list;

            while (list($key, $val) = each($fields)) {
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

        $tpl->addDataItem("URL_GENERAL_PLAIN", $general_url_plain);
        $tpl->addDataItem("URL_GENERAL", $general_url . $url_filter . "&sort_order=" . $sort_order1);
        $tpl->addDataItem("URL_ADD", $general_url . $url_filter . "&sort_order=" . $sort_order1 . "&action=add");
        $tpl->addDataItem("URL_IMPORT", $general_url . $url_filter . "&sort_order=" . $sort_order1 . "&action=addmass");
        $tpl->addDataItem("SELF", $general_url);

        $tpl->addDataItem("CONFIRMATION", $txt->display("confirmation"));

        // ####
        return $tpl->parse();
    }

    /**
     * Displays detail view of a card
     *
     * @param int $card card id
     * @return string html detailview of a card
    */

    function showCard($card) {
        $txt = new Text($this->language, "module_isic_card");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_show.html";

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic&type=showcard");
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

        $r = &$this->db->query("
            SELECT
                `module_isic_card`.*,
                IF(`module_isic_card_language`.`id`, `module_isic_card_language`.`name`, '') AS language_name,
                IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
            FROM
                `module_isic_card`
            LEFT JOIN
                `module_isic_card_language` ON `module_isic_card`.`language_id` = `module_isic_card_language`.`id`
            LEFT JOIN
                `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
            WHERE
                `module_isic_card`.`id` = !
            ", $card);

        if ($data = $r->fetch_assoc()) {
            if ($this->isic_common->canViewCard($data["school_id"], $data["person_number"])) {
                $tpl->addDataItem("DATA_active", $txt->display("active" . $data["active"]));
                $tpl->addDataItem("DATA_language_name", $data["language_name"]);
                $tpl->addDataItem("DATA_kind_name", $data["card_kind_name"]);
                $tpl->addDataItem("DATA_bank_name", $data["bank_name"]);
                $tpl->addDataItem("DATA_type_name", $data["card_type_name"]);
                $tpl->addDataItem("DATA_school_name", $data["school_name"]);
                $tpl->addDataItem("DATA_person_name_first", $data["person_name_first"]);
                $tpl->addDataItem("DATA_person_name_last", $data["person_name_last"]);
                $tpl->addDataItem("DATA_person_birthday", date("d.m.Y", strtotime($data["person_birthday"])));
                $tpl->addDataItem("DATA_person_number", $data["person_number"]);
                $tpl->addDataItem("DATA_person_addr1", $data["person_addr1"]);
                $tpl->addDataItem("DATA_person_addr2", $data["person_addr2"]);
                $tpl->addDataItem("DATA_person_addr3", $data["person_addr3"]);
                $tpl->addDataItem("DATA_person_addr4", $data["person_addr4"]);
                $tpl->addDataItem("DATA_person_email", $data["person_email"]);
                $tpl->addDataItem("DATA_person_phone", $data["person_phone"]);
                $tpl->addDataItem("DATA_person_position", $data["person_position"]);
                $tpl->addDataItem("DATA_person_class", $data["person_class"]);
                $tpl->addDataItem("DATA_person_stru_unit", $data["person_stru_unit"]);
                $tpl->addDataItem("DATA_activation_date", $data["activation_date"] == "0000-00-00" ? "-" : date("m/y", strtotime($data["activation_date"])));
                $tpl->addDataItem("DATA_expiration_date", $data["expiration_date"] == "0000-00-00" ? "-" : date("m/y", strtotime($data["expiration_date"])));
                $tpl->addDataItem("DATA_isic_number", $data["isic_number"]);
                $tpl->addDataItem("DATA_card_number", $data["card_number"]);
                if ($data["pic"] != "") {
                    $big_picture = ereg_replace("_thumb\.", ".", $data["pic"]);
                    if (@file_exists(SITE_PATH . substr($big_picture, strpos($big_picture, "upload")-1))) {
                        $tpl->addDataItem("DATA_pic", SITE_URL . $data["pic"]);
                    } else {
                        $tpl->addDataItem("DATA_pic", SITE_URL . $data["pic"]);
                    }
                } else {
                    $tpl->addDataItem("DATA_pic", "img/tyhi.gif");
                }
                // showing modify button in case of admin-users and card not being exported
                if ($data["exported"] == "0000-00-00 00:00:00" && ($this->user_type == 1 || $this->user_type == 2 && !$data["confirm_admin"])) {
                    $tpl->addDataItem("MODIFY.MODIFY", $txt->display("modify"));
                    $tpl->addDataItem("MODIFY.URL_MODIFY", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=modify", array("card_id")));
                }
                if ($this->user_type == 1 && $data["status_id"]) {
                    $tpl->addDataItem("REPLACE.REPLACE", $txt->display("replace"));
                    $tpl->addDataItem("REPLACE.URL_REPLACE", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "&card_id=" . $data["id"] . "&action=replace", array("card_id")));
                }
            } else {
                redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=view", array("card_id")));
            }
        }

        $tpl->addDataItem("BACK", $txt->display("back"));
        $tpl->addDataItem("URL_BACK", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("card_id")));
        return $tpl->parse();
    }

    /**
     * Displays the logo of the school where current user belongs to
     *
     * @return string html logo
    */

    function showSchoolLogo() {
        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_show_school_logo.html";

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic&type=showschoollogo");
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

        $sql_group = "";
        for ($i = 0; $i < sizeof($this->usergroups); $i++) {
            if ($sql_group) {
                $sql_group .= " OR ";
            }
            $sql_group .= "`module_user_groups`.`id` = '" . $this->usergroups[$i] . "'";
        }

        if ($sql_group) {
            $r = &$this->db->query("
                SELECT
                    `module_isic_school`.*
                FROM
                    `module_isic_school`,
                    `module_user_groups`
                WHERE
                    `module_isic_school`.`pic` <> '' AND
                    `module_isic_school`.`id` = `module_user_groups`.`isic_school` AND
                    (!)
                LIMIT 1
                ", $sql_group);

            if ($data = $r->fetch_assoc()) {
                if ($data["pic"]) {
                    $tpl->addDataItem("LOGO.PIC", $data["pic"]);
                }
            }
        }

        return $tpl->parse();
    }

    /**
     * Import file in CSV-format with contacts
     *
     * @param string $action action (addmass)
     * @param int $step step
     * @return string html addform for csv-import
    */

    function addCardMass ($action, $step = 0) {
        if ($this->vars["step"]) {
            $step = $this->vars["step"];
        }
        if ($this->user_type == 2) {
            return $this->isic_common->showErrorMessage("error_csv_import_not_allowed");
        }
//        setlocale(LC_ALL, 'en_US.UTF-8');
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];
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
        }

        $txt = new Text($this->language, "module_isic_card");
        $txtf = new Text($this->language, "output");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {

            if (!$step) {
                $error = false;

                $required = array("language_id", "kind_id", "type_id", "school_id");

                for ($c = 0; $c < sizeof($required); $c++) {
                    if ($this->vars[$required[$c]] == "") {
                        $bad[] = $required[$c];
                        $error = true;
                    }
                }

                if (!$error && $_FILES['datafile']['tmp_name'] && $_FILES['datafile']['size']) {
                    // get uploaded file info.
                    $file_info = Filenames::pathinfo($_FILES['datafile']['name']);
                    $file_info['extension'] = strtolower($file_info['extension']);

                    // if uploaded file type is valid process with saveing this csv.
                    if (in_array($file_info['extension'], array('csv')) || in_array($file_info['extension'], array('txt'))) {
                        $data_filename = md5(time());

                        // create destination path string.
                        $dest = Filenames::constructPath(
                            $data_filename, $file_info['extension']
                            , SITE_PATH . "/cache"
                        );

                        // process with file saving.
                        $file_uploader = new FileUploader();
                        $datafile_saved     = $file_uploader->processUploadedFile(
                            $_FILES['datafile']['tmp_name'], $dest, null, false);

                        if ($datafile_saved === FALSE) {
                            $error = true;
                            $error_datafile = $file_uploader->getLastError();
                            $this->vars["datafile"] = "";
                        } else {
                            $this->vars["datafile"] = SITE_PATH . Filenames::constructPath(
                                $data_filename, $file_info['extension']
                                , "/cache"
                            );
                        }
                    } else {
                        $error = $error_datafile = true;
                    }
                } else {
                    $error = $error_datafile = true;
                }
            } elseif ($step == 1 || $step == 2) {
                $this->vars["datafile"] = SITE_PATH . "/cache/" . $this->vars["data_filename"];
                if (is_file($this->vars["datafile"]) && file_exists($this->vars["datafile"]) && is_readable($this->vars["datafile"])) {
                    // do nothing
                } else {
                    $error = $error_datafile = true;
                }
            } elseif ($step == 3) {
                // do nothing
            }


            // ######################

            if ($error != true) {
                if ($action == "addmass") {
                    if (!$step && is_readable($this->vars["datafile"])) {
                        // first converting the whole file into UTF-8
                        $this->isic_encoding->convertFileEncoding($this->vars["datafile"]);
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
                                    //$t_data = $this->isic_encoding->convertStringEncoding($data[$c]);

                                    if ($this->vars["title_row"]) {
                                        $csv_fields[] = $t_data;
                                    } else {
                                        $csv_fields[] = $txt->display("column") . " " . ($c + 1);
                                    }
                                }
                            }
                            if ($num < $this->csv_import_min_fields) {
                                $error = $error_field_count = true;
                            }
                        } elseif ($step == 1) {
                            // importing data and showing it to user for confirmation
                            $csv_data = array();
                            $i_fields = $this->vars["datafield"];
                            $row = 0;
                            while (($data = fgetcsv($fp, 1000, $this->vars["separator"])) !== false) {
                                $row++;
                                if ($row == 1 && $this->vars["title_row"]) {
                                    continue;
                                }
                                $num = count($data);
                                for ($c = 0; $c < sizeof($this->csv_import_fields); $c++) {
                                    if ($i_fields[$c] != -1 && ($num >= $c)) {
                                        //$t_data = $this->isic_encoding->convertStringEncoding($data[$i_fields[$c]]);
                                        $t_data = $data[$i_fields[$c]];
                                        $csv_data[$row][$this->csv_import_fields[$c]] = $t_data;
                                    } else {
                                        $csv_data[$row][$this->csv_import_fields[$c]] = "";
                                    }
                                }

                                // first checking if this user already has the card of the same type that is active currently
                                $r = &$this->db->query('SELECT * FROM `module_isic_card`
                                                        WHERE
                                                            `person_number` = ? AND
                                                            `type_id` = ! AND
                                                            (`active` = 1 OR `exported` = ?) AND
                                                            `expiration_date` > NOW()
                                                        LIMIT 1',
                                                        $csv_data[$row]["person_number"], $this->vars["type_id"], '0000-00-00 00:00:00');
                                if ($check_data = $r->fetch_assoc()) {
                                    $error_import = $error_card_exists = true;
                                    $csv_data[$row]["error"]["card_exists"] = true;
                                }
                            }
                        } elseif ($step == 2) {
                            // Saving data to database
                            $csv_data = $this->vars["csv_data"];
                            $this->vars["csv_data"] = "";
                            for ($c = 0; $c < sizeof($csv_data); $c++) {
                                if ($csv_data[$c]["confirm"]) {
                                    foreach ($csv_data[$c] as $t_key => $t_val) {
                                        $this->vars[$t_key] = $t_val;
                                    }

                                    // first checking if this user already has the card of the same type that is active currently
                                    $r = &$this->db->query('SELECT * FROM `module_isic_card`
                                                            WHERE
                                                                `person_number` = ? AND
                                                                `type_id` = ! AND
                                                                (`active` = 1 OR `exported` = ?) AND
                                                                `expiration_date` > NOW()
                                                            LIMIT 1',
                                                            $this->vars["person_number"], $this->vars["type_id"], '0000-00-00 00:00:00');
                                    if ($check_data = $r->fetch_assoc()) {
                                        $error_save = $error_card_exists = true;
                                        $csv_data[$c]["error"]["card_exists"] = true;
                                        continue;
                                    }

                                    $isic_number = $this->isic_common->getISICNumber($this->vars["type_id"], $this->vars["school_id"]);
                                    $card_number = $this->isic_common->getCardNumber($isic_number);
                                    $this->vars["expiration_date"] = $this->isic_common->getCardExpiration($this->vars["type_id"]);
                                    if (!$this->vars["pic"]) {
                                        $this->vars["pic"] = "";
                                    }
                                    if (!$this->vars["bank_id"]) {
                                        $this->vars["bank_id"] = 0;
                                    }
                                    if (!$this->vars["person_stru_unit"]) {
                                        $this->vars["person_stru_unit"] = "";
                                    }

                                    $this->vars["active"] = 0; // all imported cards will be non-active at the beginning
                                    $this->vars["person_birthday"] = $this->isic_common->calcBirthdayFromNumber($this->vars["person_number"]);

                                    if ($isic_number && $card_number && $this->vars["expiration_date"] && $this->vars["person_birthday"]) {
                                        $r = &$this->db->query('INSERT INTO `module_isic_card` (
                                            `adddate`, `adduser`, `moddate`, `moduser`, `active`,
                                            `language_id`, `kind_id`, `bank_id`, `type_id`, `school_id`,
                                            `person_name_first`, `person_name_last`, `person_birthday`, `person_number`,
                                            `person_addr1`, `person_addr2`, `person_addr3`, `person_addr4`,
                                            `person_email`, `person_phone`, `person_position`,
                                            `person_class`, `person_stru_unit`,
                                            `isic_number`, `card_number`, `creation_date`, `expiration_date`, `pic`)
                                            VALUES (
                                            NOW(), ?, NOW(), ?, ?,
                                            ?, ?, ?, ?, ?,
                                            ?, ?, ?, ?,
                                            ?, ?, ?, ?,
                                            ?, ?, ?,
                                            ?, ?,
                                            ?, ?, NOW(), ?, ?)',
                                           $this->userid, $this->userid,
                                           $this->vars["active"] ? 1 : 0,
                                           $this->vars["language_id"],
                                           $this->vars["kind_id"],
                                           $this->vars["bank_id"],
                                           $this->vars["type_id"],
                                           $this->vars["school_id"],
                                           $this->vars["person_name_first"],
                                           $this->vars["person_name_last"],
                                           $this->vars["person_birthday"],
                                           $this->vars["person_number"],
                                           $this->vars["person_addr1"],
                                           $this->vars["person_addr2"],
                                           $this->vars["person_addr3"],
                                           $this->vars["person_addr4"],
                                           $this->vars["person_email"],
                                           $this->vars["person_phone"],
                                           $this->vars["person_position"],
                                           $this->vars["person_class"],
                                           $this->vars["person_stru_unit"],
                                           $isic_number,
                                           $card_number,
                                           $this->vars["expiration_date"],
                                           $this->vars["pic"]);

                                           //echo $this->db->show_query();

                                        $added_id = $this->db->insert_id();

                                        if ($t_school_account = $this->isic_common->schoolAutoUserAccounts($this->vars["school_id"])) {
                                            if ($t_school_account[0]) {
                                                $this->isic_common->createUserAccount($this->vars["school_id"], $this->vars["person_number"], $this->vars["person_name_first"], $this->vars["person_name_last"], $this->vars["person_email"], $this->vars["person_phone"], $t_school_account[1]);
                                            }
                                        }

                                        // saving info to log-table
                                        $this->isic_common->saveCardChangeLog(1, $added_id, array(), $this->isic_common->getCardRecord($added_id));

                                        $csv_data[$c]["card_created"] = true;
                                    } else {
                                        $error_save = $error_fields = true;
                                        $csv_data[$c]["error"]["isic_number"] = $isic_number ? false : true;
                                        $csv_data[$c]["error"]["card_number"] = $card_number ? false : true;
                                        $csv_data[$c]["error"]["expiration_date"] = $this->vars["expiration_date"] ? false : true;
                                        $csv_data[$c]["error"]["person_birthday"] = $this->vars["person_birthday"] ? false : true;
                                        // in case of errors, releasing the already reserved ISIC number
                                        if ($isic_number) {
                                            $this->isic_common->releaseISICNumber($this->vars["type_id"], $isic_number);
                                        }

                                    }
                                } else {
                                    $csv_data[$c]["error"]["not_confirmed"] = true;
                                }
                            }
                        }
                        fclose($fp);
                    }
                }

                if (!$error && !$error_save) {
                    $step++; // increasing step if there were no errors
//                    redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true", array("card_id", "action")));
                }
            }
        }

        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if (!$step) { // importing the csv
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_addmass.html";
            $fields = array(
                "title_row" => array("checkbox",0,0,$this->vars["title_row"],"","","", true),
                "language_id" => array("select",0,0,$this->vars["language_id"],"","","isic300", false),
                "kind_id" => array("select",0,0,$this->vars["kind_id"],"","","isic300", false),
                "bank_id" => array("select",0,0,$this->vars["bank_id"],"","","isic300", false),
                "type_id" => array("select",0,0,$this->vars["type_id"],"","","isic300", false),
                "school_id" => array("select", 0,0,$this->vars["school_id"],"","","isic300", false),
                "datafile" => array("file",40,0,$this->vars["datafile"],"","","isic300", true),
                "separator" => array("textinput",1,0,$this->vars["separator"],"","","", true)
            );

            // card languages
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_language`.*
                FROM
                    `module_isic_card_language`
                ORDER BY
                    `module_isic_card_language`.`name`
                ');

            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["language_id"][4] = $list;

            // card kinds
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_kind`.*
                FROM
                    `module_isic_card_kind`
                ORDER BY
                    `module_isic_card_kind`.`id`
                LIMIT 1
                ');

            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            $fields["kind_id"][4] = $list;

            // banks
            $list = array();
            $list[0] = '---';
            /*
            $r = &$this->db->query('
                SELECT
                    `module_isic_bank`.*
                FROM
                    `module_isic_bank`
                ORDER BY
                    `module_isic_bank`.`id`
                ');

            while ($data = $r->fetch_assoc()) {
                $list[$data["id"]] = $data["name"];
            }
            */
            $fields["bank_id"][4] = $list;

            // card types
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_type`.*
                FROM
                    `module_isic_card_type`
                ORDER BY
                    `module_isic_card_type`.`name`
                ');

            while ($data = $r->fetch_assoc()) {
                if (in_array($data["id"], $this->allowed_card_types)) {
                    $list[$data["id"]] = $data["name"];
                }
            }
            $fields["type_id"][4] = $list;

            // schools
            $list = array();
            $r = &$this->db->query('
                SELECT
                    `module_isic_school`.*
                FROM
                    `module_isic_school`
                ORDER BY
                    `module_isic_school`.`name`
                ');

            while ($data = $r->fetch_assoc()) {
                if (in_array($data["id"], $this->allowed_schools)) {
                    $list[$data["id"]] = $data["name"];
                }
            }
            $fields["school_id"][4] = $list;

        } elseif ($step == 1) {
            $fields = array();
            $data_fields = array();
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_addmass_1.html";

            for ($i = 0; $i < sizeof($this->csv_import_fields); $i++) {
                if ((sizeof($csv_fields) - 1) >= $i) {
                    $t_val = $i;
                }
                $data_fields[$i] = array("select", 40, 0, $t_val, $csv_fields, "", "", true);
            }
        } elseif ($step == 2) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_addmass_2.html";
        } elseif ($step == 3) {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_addmass_3.html";
        }

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic&type=addcards");
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

        // #################################

        if ($error == true) {
            if ($error_datafile) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_datafile"));
            } elseif ($error_fieldcount) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_csv_field_count"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error"));
            }
        }

        if ($error_save == true) {
            if ($error_fields) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_fields"));
            } elseif ($error_card_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_card_exists"));
            }
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
                    $f = new AdminFields("$key",$fdata);
                    if ($fdata["type"] == "textfield") {
                        $f->classTextarea = "isic300";
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
                $tpl->addDataItem("FIELDS.TITLE", $txt->display($this->csv_import_fields[$key]));
                $tpl->addDataItem("FIELDS.DATA", $field_data);
                unset($fdata);
            }
        }

        // show all the imported rows together with according statuses
        if ($step == 2) {
            $fdata = array();
            $fdata["type"] = "textinput";
            $fdata["size"] = 30;
            $fdata["cols"] = 30;
            $fdata["class"] = "i75";

            $fdata_conf = array();
            $fdata_conf["type"] = "checkbox";

            foreach ($this->csv_import_fields as $field_title) {
                $tpl->addDataItem("ROW_TITLE.TITLE", $txt->display($field_title));
            }

            $row = 0;
            foreach ($csv_data as $key => $val) {
                $tpl->addDataItem("ROW.ROW", $row + 1);
                foreach ($this->csv_import_fields as $field_name) {
                    $f = new AdminFields("csv_data[" . $row . "][" . $field_name . "]", $fdata);
                    $field_data = $f->display($val[$field_name]);
                    $tpl->addDataItem("ROW.COL.DATA", $field_data);
                }
                if (is_array($val["error"])) {
                    $err_txt = array();
                    foreach ($val["error"] as $err_key => $err_val) {
                        if ($err_val) {
                            $err_txt[] = $txt->display("modify_error_" . $err_key);
                        }
                    }
                    $tpl->addDataItem("ROW.COL.DATA", implode("<br />", $err_txt));
                } elseif ($val["card_created"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $txt->display("card_created"));
                } else {
                    $f = new AdminFields("csv_data[" . $row . "][confirm]", $fdata_conf);
                    $field_data = $f->display(1);
                    $tpl->addDataItem("ROW.COL.DATA", $field_data);
                }

                $row++;
            }
        }

        // show all the imported rows together with according statuses
        if ($step == 3) {
            foreach ($this->csv_import_fields as $field_title) {
                $tpl->addDataItem("ROW_TITLE.TITLE", $txt->display($field_title));
            }

            $row = 0;
            foreach ($csv_data as $key => $val) {
                $row++;
                $tpl->addDataItem("ROW.ROW", $row);
                foreach ($this->csv_import_fields as $field_name) {
                    $tpl->addDataItem("ROW.COL.DATA", $val[$field_name]);
                }

                if (is_array($val["error"])) {
                    $err_txt = array();
                    foreach ($val["error"] as $err_key => $err_val) {
                        if ($err_val) {
                            $err_txt[] = $txt->display("modify_error_" . $err_key);
                        }
                    }
                    $tpl->addDataItem("ROW.COL.DATA", implode("<br />", $err_txt));
                } elseif ($val["card_created"]) {
                    $tpl->addDataItem("ROW.COL.DATA", $txt->display("card_created"));
                } else {
                    $tpl->addDataItem("ROW.COL.DATA", "-");
                }
            }
        }

        if ($action == "addmass") {
            if (!$step) {
                $tpl->addDataItem("BUTTON", $txt->display("button_import"));
            } elseif ($step == 1 || $step == 2) {
                $tpl->addDataItem("BUTTON", $txt->display("button_save"));
            }
        }

        $hidden = "<input type=hidden name=\"action\" value=\"$action\">\n";
        $hidden .= "<input type=hidden name=\"write\" value=\"true\">\n";
        $hidden .= "<input type=hidden name=\"step\" value=\"" . $step . "\">\n";
        $hidden .= "<input type=hidden name=\"separator\" value=\"" . $this->vars["separator"] . "\">\n";
        if ($this->vars["data_filename"]) {
            $hidden .= "<input type=hidden name=\"data_filename\" value=\"" . $this->vars["data_filename"] . "\">\n";
        }
        if ($this->vars["language_id"]) {
            $hidden .= "<input type=hidden name=\"language_id\" value=\"" . $this->vars["language_id"] . "\">\n";
        }
        if ($this->vars["kind_id"]) {
            $hidden .= "<input type=hidden name=\"kind_id\" value=\"" . $this->vars["kind_id"] . "\">\n";
        }
        if ($this->vars["type_id"]) {
            $hidden .= "<input type=hidden name=\"type_id\" value=\"" . $this->vars["type_id"] . "\">\n";
        }
        if ($this->vars["school_id"]) {
            $hidden .= "<input type=hidden name=\"school_id\" value=\"" . $this->vars["school_id"] . "\">\n";
        }
        if ($this->vars["bank_id"]) {
            $hidden .= "<input type=hidden name=\"bank_id\" value=\"" . $this->vars["bank_id"] . "\">\n";
        }
        if ($this->vars["title_row"]) {
            $hidden .= "<input type=hidden name=\"title_row\" value=\"" . $this->vars["title_row"] . "\">\n";
        }

        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", $general_url);

        return $tpl->parse();
    }

    /**
     * Displays add/modify view of a card
     *
     * @param int $card card id
     * @param string $action action (add/modify)
     * @return string html addform for cards
    */

    function addCard($card, $action) {
        if (!$card && $this->vars["card_id"]) {
            $card = $this->vars["card_id"];
        }
        if ($this->vars["action"]) {
            $action = $this->vars["action"];
        }
        if ($this->module_param["isic"]) {
            $this->form_type = $this->module_param["isic"];
        }
        $content = @$this->vars["content"];
        $write = @$this->vars["write"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $txt = new Text($this->language, "module_isic_card");
        $txtf = new Text($this->language, "output");

        // ###################################
        // WRITE TO DB
        if ($write == "true") {

            $error = false;

            if ($action == "modify" && $card) {
                // check has the entry been renewed
                $r = &$this->db->query('
                    SELECT
                        `module_isic_card`.*
                    FROM
                        `module_isic_card`
                    WHERE
                        `module_isic_card`.`id` = ?
                    ', $card);

                if ($check_data = $r->fetch_assoc()) {
                    $pic_filename = 'ISIC' . str_pad($check_data["id"], 10, '0', STR_PAD_LEFT);
                    if (!$this->isic_common->canModifyCard($check_data["school_id"], $check_data["person_number"])) {
                        redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=modify", array("card_id", "action")));
                    }
                    // if card has already been exported, then regular user can not make any modifications
                    if ($this->user_type == 2 && $check_data["exported"] != "0000-00-00 00:00:00") {
//                        redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=modify", array("card_id", "action")));
                    }
                    // card is already closed and status has been set, so there will be no further modifications
                    if (!$check_data["active"] && $check_data["status_id"]) {
//                        redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "", array("card_id", "action")));
                    }
                    // if card is already expired, then no modifications are possible
                    if (strtotime($check_data["expiration_date"]) < time()) {
//                        redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "", array("card_id", "action")));
                    }
                }
            } else if ($action == "add" || $action == "replace" || $action == "prolong") {
                // do nothing really
            } else {
                redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "", array("card_id", "action")));
            }

            if ($action == "add") {
                if (!$this->vars["language_id"]) {
                    $this->vars["language_id"] = $this->language_default;
                }

                if (!$this->vars["kind_id"]) {
                    $this->vars["kind_id"] = $this->kind_default;
                }

                if ($this->vars["bank_id"] == NULL) {
                    $this->vars["bank_id"] = 0;
                }

                if ($this->vars["person_position"] == NULL) {
                    $this->vars["person_position"] = "";
                }

                if ($this->vars["person_class"] == NULL) {
                    $this->vars["person_class"] = "";
                }

                if ($this->vars["person_stru_unit"] == NULL) {
                    $this->vars["person_stru_unit"] = "";
                }

                $required = array("language_id", "kind_id", "type_id", "school_id", "person_name_first", "person_name_last", "person_birthday", "person_number", "person_email");
                // check if collateral is required, then setting bank-account as required field
                if ($this->isic_payment->getCardCollateralRequired($this->vars["school_id"], $this->vars["type_id"])) {
                    $required[] = "person_bankaccount";
                }

                for ($c = 0; $c < sizeof($required); $c++) {
                    if ($this->vars[$required[$c]] == "") {
                        $bad[] = $required[$c];
                        $error = true;
                    }
                }

                // first checking if this user already has the card of the same type that is active currently
                $r = &$this->db->query('SELECT * FROM `module_isic_card`
                                        WHERE
                                            `person_number` = ? AND
                                            `type_id` = ! AND
                                            (`active` = 1 OR `exported` = ?) AND
                                            `expiration_date` > NOW()
                                        LIMIT 1',
                                        $this->vars["person_number"], $this->vars["type_id"], '0000-00-00 00:00:00');
                if ($r->fetch_assoc()) {
                    $error = $error_card_exists = true;
                }
            } elseif ($action == "replace" || $action == "prolong") {
                // get all of the data from existing card that we are creating replacement card for
                $r = &$this->db->query('SELECT * FROM `module_isic_card`
                                        WHERE
                                            `id` = !
                                        LIMIT 1',
                                        $card);
                if ($orig_card = $r->fetch_assoc()) {
                    $this->vars["prev_card_id"] = $orig_card["id"];
                    $this->vars["language_id"] = $orig_card["language_id"];
                    $this->vars["type_id"] = $orig_card["type_id"];
                    $this->vars["kind_id"] = $orig_card["kind_id"];
                    $this->vars["bank_id"] = $orig_card["bank_id"];
                    $this->vars["school_id"] = $orig_card["school_id"];
                    $this->vars["person_number"] = $orig_card["person_number"];
                    $this->vars["person_birthday"] = date("d/m/Y", strtotime($orig_card["person_birthday"]));
                    if ($this->vars["person_position"] == NULL) {
                        $this->vars["person_position"] = $orig_card["person_position"];
                    }
                    if ($this->vars["person_class"] == NULL) {
                        $this->vars["person_class"] = $orig_card["person_class"];
                    }
                    if ($this->vars["person_stru_unit"] == NULL) {
                        $this->vars["person_stru_unit"] = $orig_card["person_stru_unit"];
                    }
                }

                $required = array("person_name_first", "person_name_last", "person_email");
                // check if collateral is required, then setting bank-account as required field
                if ($this->isic_payment->getCardCollateralRequired($orig_card["school_id"], $orig_card["type_id"])) {
                    $required[] = "person_bankaccount";
                }

                for ($c = 0; $c < sizeof($required); $c++) {
                    if ($this->vars[$required[$c]] == "") {
                        $bad[] = $required[$c];
                        $error = true;
                    }
                }

                // first checking if this user already has the card of the same type that is active currently
                if ($action == "replace") {
                    $r = &$this->db->query('SELECT COUNT(*) AS card_count FROM `module_isic_card`
                                            WHERE
                                                `person_number` = ? AND
                                                `type_id` = ! AND
                                                (`active` = 1 OR `exported` = ?) AND
                                                `expiration_date` > NOW()
                                            ',
                                            $this->vars["person_number"], $this->vars["type_id"], '0000-00-00 00:00:00');
                    if ($t_card = $r->fetch_assoc()) {
                        if ($t_card["card_count"] > 1) {
                            $error = $error_card_exists_replace = true;
                        }
                    }
                } elseif ($action == "prolong") {
                    // in case of prolonging, check if there are no more than only one active card
                    $r = &$this->db->query('SELECT COUNT(*) AS card_count FROM `module_isic_card`
                                            WHERE
                                                `person_number` = ? AND
                                                `type_id` = ! AND
                                                (`active` = 1 OR `exported` = ?) AND
                                                `expiration_date` > NOW()
                                            ',
                                            $this->vars["person_number"], $this->vars["type_id"], '0000-00-00 00:00:00');

                    if ($t_card = $r->fetch_assoc()) {
                        if ($t_card["card_count"] > 1) {
                            $error = $error_card_exists_prolong = true;
                        }
                    }
                }
            } else if ($action == "modify") {
                $required = array();
                if ($this->vars["prolong"]) {
                    // in case of prolonging, there is no need to check any required fields
                    // assigning prolong-status to status_id automatically
                    $t_status = $this->isic_common->getCardStatusProlongId($check_data["type_id"]);
                    if ($t_status) {
                        $this->vars["status_id"] = $t_status;
                    } else {
                        $error = $error_prolong_not_allowed = true;
                    }
                } else {

                    if ($check_data["exported"] == "0000-00-00 00:00:00") {
                        $required = array("person_name_first", "person_name_last", "person_email");
                        // check if collateral is required, then setting bank-account as required field
                        if ($this->isic_payment->getCardCollateralRequired($check_data["school_id"], $check_data["type_id"])) {
                            $required[] = "person_bankaccount";
                        }
                        for ($c = 0; $c < sizeof($required); $c++) {
                            if ($this->vars[$required[$c]] == "") {
                                $bad[] = $required[$c];
                                $error = true;
                            }
                        }
                    } elseif ($check_data["active"]) {
                        if ($this->form_type == 1 || $this->form_type == 2) {
                            $required = array("status_id");
                        }
                        // check if collateral is required, then setting bank-account as required field
                        if ($this->isic_payment->getCardCollateralRequired($check_data["school_id"], $check_data["type_id"])) {
                            $required[] = "person_bankaccount";
                        }
                        for ($c = 0; $c < sizeof($required); $c++) {
                            if ($this->vars[$required[$c]] == 0) {
                                $bad[] = $required[$c];
                                $error = $error_status_required = true;
                            }
                        }
                    }
                }
            }

            if (!$error && ($action == "add" || $action == "replace" || $action == "prolong")) {
                // temporarily assigning isic-number to pic_filename, later changing it to card record id
                $pic_filename = $isic_number = $this->isic_common->getISICNumber($this->vars["type_id"], $this->vars["school_id"]);
                if (!$isic_number) {
                    $error = $error_isic_number = true;
                }
            }

            // image upload handling
            if (!$error && $_FILES['pic']['tmp_name'] && $_FILES['pic']['size']) {
                // get uploaded file info.
                $file_info = Filenames::pathinfo($_FILES['pic']['name']);
                $file_info['extension'] = strtolower($file_info['extension']);

                if ($file_info['extension'] == 'jpeg') {
                    $file_info['extension'] = 'jpg';
                }

                // if uploaded file type is valid process with saveing this photo.
                if (in_array($file_info['extension'], array('jpg'))) {
                    // create destination path string.
                    $pic_filename = md5(rand(0, time()));
                    $dest = Filenames::constructPath ($pic_filename, $file_info['extension'], SITE_PATH . '/' . $GLOBALS["directory"]["upload"] . '/isic_tmp');

                    // process with pic saving.
                    $file_uploader = new FileUploader();
                    $pic_saved     = $file_uploader->processUploadedFile (
                        $_FILES['pic']['tmp_name'], $dest, 'replace', false);

                    if ($pic_saved === FALSE) {
                        $error = $error_pic_save = true;
                        $error_pic = $file_uploader->getLastError();
                        $this->vars["pic"] = "";
                    } else {
                        // trying to convert image into jpg no matter what was the extension of the file
                        $command = IMAGE_CONVERT . " $pic_saved $pic_saved";
                        exec($command, $_dummy, $return_val);
                        if ($return_val) {
                            $error = $error_pic_format = true;
                        } else {
                            $pic_size = getimagesize($pic_saved);
                            if (is_array($pic_size)) {
                                if (image_type_to_mime_type($pic_size[2]) != "image/jpeg") {
                                    $error = $error_pic_format = true;
                                } else {
                                    if ($pic_size[0] >= $this->image_size_x && $pic_size[1] >= $this->image_size_y) {
                                        $pic_resize_required = true;
                                        $tmp_pic = Filenames::constructPath ($pic_filename, $file_info['extension'], SITE_URL . '/' . $GLOBALS["directory"]["upload"] . "/isic_tmp");
                                    } else {
                                        $error = $error_pic_size = true;
                                    }
                                }
                            } else {
                                $error = $error_pic_size = true;
                            }
                        }
                    }
                } else {
                    $error = $error_pic_format = true;
                }
            }

            if (!$error && !$pic_resize_required && $this->vars["pic_resize"] &&  $this->vars["pic_name"]) {
                $t_pic_filename = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . '/isic_tmp/' . $this->vars["pic_name"] . ".jpg";
                $t_pic_filename_thumb = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . '/isic_tmp/' . $this->vars["pic_name"] . "_thumb.jpg";
                if (file_exists($t_pic_filename)) {
                    $pic_size = getimagesize($t_pic_filename);
                    if (is_array($pic_size)) {
                        $t_x = $pic_size[0];
                        $t_y = $pic_size[1];

                        $ratio = $t_x / $this->image_size_x;
                        $crop = array();
                        $crop["x1"] = round($this->vars["x1"] * $ratio);
                        $crop["x2"] = round($this->vars["x2"] * $ratio);
                        $crop["y1"] = round($this->vars["y1"] * $ratio);
                        $crop["y2"] = round($this->vars["y2"] * $ratio);

                        // crop
                        $command = IMAGE_CONVERT . " -crop '" . ($crop["x2"] - $crop["x1"]) . "x" . ($crop["y2"] - $crop["y1"]) . "+" . $crop["x1"] . "+" . $crop["y1"] . "' $t_pic_filename $t_pic_filename";
                        exec($command, $_dummy, $return_val);
                        if ($return_val) {
                            $error = $error_pic_resize = true;
                        }
                        // resize
                        $command = IMAGE_CONVERT . " -resize '" . $this->image_size . "' $t_pic_filename $t_pic_filename";
                        exec($command, $_dummy, $return_val);
                        if ($return_val) {
                            $error = $error_pic_resize = true;
                        }

                        // creating a thumbnail image
                        $command = IMAGE_CONVERT . " -resize '" . $this->image_size_thumb . "' $t_pic_filename $t_pic_filename_thumb";
                        exec($command, $_dummy, $return_val);
                        if ($return_val) {
                            $error = $error_pic_resize = true;
                        }

                        if (!$error) {
                            @copy($t_pic_filename, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . '/isic/' . $pic_filename . '.jpg');
                            @copy($t_pic_filename_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . '/isic/' . $pic_filename . '_thumb.jpg');
                            @unlink($t_pic_filename);
                            @unlink($t_pic_filename_thumb);
                            $this->vars["pic"] = Filenames::constructPath($pic_filename, 'jpg', "/" . $GLOBALS["directory"]["upload"] . '/isic');
                        }
                    }
                }
            }


            // ######################

            if ($error != true) {
                $this->vars["expiration_date"] = $this->isic_common->getCardExpiration($this->vars["type_id"]);
                $this->vars["person_birthday"] = substr($this->vars["person_birthday"], 6, 4) . "-" . substr($this->vars["person_birthday"], 3, 2) . "-" . substr($this->vars["person_birthday"], 0, 2);

                if ($action == "modify") {
                    if (!$this->vars["pic"]) {
                        $this->vars["pic"] = $check_data["pic"];
                    }

                    $row_old = $this->isic_common->getCardRecord($card);

                    // if not yet exported, then we can change almost anything
                    if ($check_data["exported"] == "0000-00-00 00:00:00") {
                        $r = &$this->db->query('
                        UPDATE
                            `module_isic_card`
                        SET
                            `module_isic_card`.`moddate` = NOW(),
                            `module_isic_card`.`moduser` = ?,
                            `module_isic_card`.`person_name_first` = ?,
                            `module_isic_card`.`person_name_last` = ?,
                            `module_isic_card`.`person_addr1` = ?,
                            `module_isic_card`.`person_addr2` = ?,
                            `module_isic_card`.`person_addr3` = ?,
                            `module_isic_card`.`person_addr4` = ?,
                            `module_isic_card`.`person_email` = ?,
                            `module_isic_card`.`person_phone` = ?,
                            `module_isic_card`.`person_position` = ?,
                            `module_isic_card`.`person_class` = ?,
                            `module_isic_card`.`person_stru_unit` = ?,
                            `module_isic_card`.`person_bankaccount` = ?,
                            `module_isic_card`.`person_bankaccount_name` = ?,
                            `module_isic_card`.`person_newsletter` = ?,
                            `module_isic_card`.`pic` = ?,
                            `module_isic_card`.`confirm_user` = !,
                            `module_isic_card`.`confirm_payment_collateral` = !,
                            `module_isic_card`.`confirm_payment_cost` = !,
                            `module_isic_card`.`confirm_admin` = !
                        WHERE
                            `module_isic_card`.`id` = !
                        ', $this->userid,
                           $this->vars["person_name_first"],
                           $this->vars["person_name_last"],
                           $this->vars["person_addr1"],
                           $this->vars["person_addr2"],
                           $this->vars["person_addr3"],
                           $this->vars["person_addr4"],
                           $this->vars["person_email"],
                           $this->vars["person_phone"],
                           $this->vars["person_position"],
                           $this->vars["person_class"],
                           $this->vars["person_stru_unit"],
                           $this->vars["person_bankaccount"],
                           $this->vars["person_bankaccount_name"],
                           $this->vars["person_newsletter"] ? 1 : 0,
                           $this->vars["pic"],
                           $this->vars["confirm_user"] ? 1: 0,
                           $this->user_type == 1 ? ($this->vars["confirm_payment_collateral"] ? 1 : 0) : $check_data["confirm_payment_collateral"],
                           $this->user_type == 1 ? ($this->vars["confirm_payment_cost"] ? 1 : 0) : $check_data["confirm_payment_cost"],
                           $this->user_type == 1 ? ($this->vars["confirm_admin"] ? 1 : 0) : $check_data["confirm_admin"],
                           $card);
                        // if card type should be auto-exported then setting exported field
                        if ($this->vars["confirm_admin"] && $this->auto_export[$check_data["type_id"]]) {
                            $r =& $this->db->query("UPDATE `module_isic_card` SET `exported` = NOW() WHERE `id` = !", $card);
                        }
/*
                    } elseif (!$check_data["status_id"] && !$check_data["active"]) { // if card is not active and status has not been set
                        $r = &$this->db->query('
                        UPDATE
                            `module_isic_card`
                        SET
                            `module_isic_card`.`moddate` = NOW(),
                            `module_isic_card`.`activation_date` = NOW(),
                            `module_isic_card`.`moduser` = ?,
                            `module_isic_card`.`active` = ?
                        WHERE
                            `module_isic_card`.`id` = !
                        ', $this->userid,
                           $this->vars["active"] ? 1 : 0,
                           $card);

                    } elseif ($check_data["active"] && !$check_data["status_id"]) { // if card is active and status has not been set
                        $r = &$this->db->query('
                        UPDATE
                            `module_isic_card`
                        SET
                            `module_isic_card`.`moddate` = NOW(),
                            `module_isic_card`.`moduser` = ?,
                            `module_isic_card`.`status_id` = ?,
                            `module_isic_card`.`active` = ?
                        WHERE
                            `module_isic_card`.`id` = !
                        ', $this->userid,
                           $this->vars["status_id"],
                           $this->vars["active"] ? 1 : 0,
                           $card);
*/
                    } else {
                        // if card has already been exported - meaning it's printed, then only active and status can be changed
                        if ($this->vars["active"]) {
                            $this->vars["activation_date"] = date("Y-m-d");
                            $this->vars["deactivation_date"] = $check_data["deactivation_date"];
                        } else {
                            $this->vars["activation_date"] = $check_data["activation_date"];
                            $this->vars["deactivation_date"] = date("Y-m-d");
                        }

                        // check if card is returned
                        if ($this->user_type == 1) {
                            if ($this->vars["returned"]) {
                                $this->vars["returned_date"] = date("Y-m-d");
                            } else {
                                $this->vars["returned_date"] = $check_data["returned_date"];
                            }
                        } else {
                            $this->vars["returned"] = $check_data["returned"];
                            $this->vars["returned_date"] = $check_data["returned_date"];
                        }

                        // check if card's collateral is returned
                        if ($this->user_type == 1) {
                            if ($this->vars["collateral_returned"]) {
                                $this->vars["collateral_returned_date"] = date("Y-m-d");
                            } else {
                                $this->vars["collateral_returned_date"] = $check_data["collateral_returned_date"];
                            }
                        } else {
                            $this->vars["collateral_returned"] = $check_data["collateral_returned"];
                            $this->vars["collateral_returned_date"] = $check_data["collateral_returned_date"];
                        }

                        if (!isset($this->vars["status_id"])) {
                            $this->vars["status_id"] = $row_old["status_id"];
                        }

                        $r = &$this->db->query('
                        UPDATE
                            `module_isic_card`
                        SET
                            `module_isic_card`.`moddate` = NOW(),
                            `module_isic_card`.`moduser` = ?,
                            `module_isic_card`.`activation_date` = ?,
                            `module_isic_card`.`deactivation_date` = ?,
                            `module_isic_card`.`status_id` = ?,
                            `module_isic_card`.`active` = ?,
                            `module_isic_card`.`returned` = ?,
                            `module_isic_card`.`returned_date` = ?,
                            `module_isic_card`.`collateral_returned` = ?,
                            `module_isic_card`.`collateral_returned_date` = ?
                        WHERE
                            `module_isic_card`.`id` = !
                        ', $this->userid,
                           $this->vars["activation_date"],
                           $this->vars["deactivation_date"],
                           $this->vars["status_id"],
                           $this->vars["active"] ? 1 : 0,
                           $this->vars["returned"] ? 1 : 0,
                           $this->vars["returned_date"],
                           $this->vars["collateral_returned"] ? 1 : 0,
                           $this->vars["collateral_returned_date"],
                           $card);

                        // if card was marked as active and it's expiration is yet to come,
                        // then de-activating all other cards of the same type for this user
                        if ($this->vars["active"] && (strtotime($check_data["expiration_date"]) > time())) {
                            $r = &$this->db->query('
                            UPDATE
                                `module_isic_card`
                            SET
                                `module_isic_card`.`moddate` = NOW(),
                                `module_isic_card`.`moduser` = ?,
                                `module_isic_card`.`active` = ?,
                                `module_isic_card`.`deactivation_date` = NOW()
                            WHERE
                                `module_isic_card`.`person_number` = ? AND
                                `module_isic_card`.`id` <> ! AND
                                `module_isic_card`.`type_id` = ! AND
                                `module_isic_card`.`active` = !
                            ', $this->userid,
                               0,
                               $check_data["person_number"],
                               $card,
                               $check_data["type_id"],
                               1);
                        }
                    }

                    $this->isic_common->saveCardChangeLog(2, $card, $row_old, $this->isic_common->getCardRecord($card));
                }
                else if ($action == "add" || $action == "replace" || $action == "prolong") {
                    $card_number = $this->isic_common->getCardNumber($isic_number);
                    if (!$this->vars["pic"]) {
                        $this->vars["pic"] = "";
                    }

                    $r = &$this->db->query('INSERT INTO `module_isic_card` (`prev_card_id`,
                        `adddate`, `adduser`, `moddate`, `moduser`, `active`,
                        `language_id`, `kind_id`, `bank_id`, `type_id`, `school_id`,
                        `person_name_first`, `person_name_last`, `person_birthday`, `person_number`, `person_addr1`,
                        `person_addr2`, `person_addr3`, `person_addr4`, `person_email`,
                        `person_phone`, `person_position`, `person_class`,
                        `person_stru_unit`, `person_bankaccount`, `person_bankaccount_name`, `person_newsletter`,
                        `isic_number`, `card_number`, `creation_date`, `expiration_date`, `pic`,
                        `confirm_user`, `confirm_payment_collateral`, `confirm_payment_cost`, `confirm_admin`)
                        VALUES (!,
                        NOW(), ?, NOW(), ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, NOW(), ?, ?,
                        !, !, !, !)',
                       $this->vars["prev_card_id"] ? $this->vars["prev_card_id"] : 0,
                       $this->userid, $this->userid,
                       $this->vars["active"] ? 1 : 0,
                       $this->vars["language_id"],
                       $this->vars["kind_id"],
                       $this->vars["bank_id"],
                       $this->vars["type_id"],
                       $this->vars["school_id"],
                       $this->vars["person_name_first"],
                       $this->vars["person_name_last"],
                       $this->vars["person_birthday"],
                       $this->vars["person_number"],
                       $this->vars["person_addr1"],
                       $this->vars["person_addr2"],
                       $this->vars["person_addr3"],
                       $this->vars["person_addr4"],
                       $this->vars["person_email"],
                       $this->vars["person_phone"],
                       $this->vars["person_position"],
                       $this->vars["person_class"],
                       $this->vars["person_stru_unit"],
                       $this->vars["person_bankaccount"],
                       $this->vars["person_bankaccount_name"],
                       $this->vars["person_newsletter"] ? 1 : 0,
                       $isic_number,
                       $card_number,
                       $this->vars["expiration_date"],
                       $this->vars["pic"],
                       $this->vars["confirm_user"] ? 1 : 0,
                       $this->user_type == 1 ? ($this->vars["confirm_payment_collateral"] ? 1 : 0) : 0,
                       $this->user_type == 1 ? ($this->vars["confirm_payment_cost"] ? 1 : 0) : 0,
                       $this->user_type == 1 ? ($this->vars["confirm_admin"] ? 1 : 0) : 0
                    );
                   //echo "<!-- " . $this->db->show_query() . " -->\n";

                    $added_id = $this->db->insert_id();
                    // 04/12/2008, Martin: disabling automatic user account creation for now ...
                    // 16/01/2009, Martin: activating automatic user account creation but checking if school has auto_user_accounts check box set
                    if ($t_school_account = $this->isic_common->schoolAutoUserAccounts($this->vars["school_id"])) {
                        if ($t_school_account[0]) {
                            $this->isic_common->createUserAccount($this->vars["school_id"], $this->vars["person_number"], $this->vars["person_name_first"], $this->vars["person_name_last"], $this->vars["person_email"], $this->vars["person_phone"], $t_school_account[1]);
                        }
                    }

                    // if picture was added, then changing the name of the picture
                    if ($this->vars["pic"]) {
                        $old_name = $this->vars["pic"];
                        $this->vars["pic"] = $new_name = str_replace($pic_filename, "ISIC" . str_pad($added_id, 10, '0', STR_PAD_LEFT), $old_name);
                        if (Filesystem::rename(SITE_PATH . $old_name, SITE_PATH . $new_name)) {
                            $r = &$this->db->query('UPDATE module_isic_card SET pic = ? WHERE id = ?', $new_name, $added_id);
                        }
                    } elseif ($action == "replace" || $action == "prolong") {
                        $old_name = $orig_card["pic"];
                        $this->vars["pic"] = $new_name = "/" . $GLOBALS["directory"]["upload"] . "/isic/ISIC" . str_pad($added_id, 10, '0', STR_PAD_LEFT) . ".jpg";
                        if (copy(SITE_PATH . $old_name, SITE_PATH . $new_name)) {
                            @copy(SITE_PATH . str_replace(".jpg", "_thumb.jpg", $old_name), SITE_PATH . str_replace(".jpg", "_thumb.jpg", $new_name));
                            $r = &$this->db->query('UPDATE module_isic_card SET pic = ? WHERE id = ?', $new_name, $added_id);
                        }
                    }

                    $this->isic_common->saveCardChangeLog(1, $added_id, array(), $this->isic_common->getCardRecord($added_id));

                    // if regular user, then after adding a new card, will head back to modification page
                    if ($this->user_type == 2) {
                        $modification_required = true;
                    }

                    if ($pic_resize_required || $modification_required) {
                        $action = "modify";
                        $card = $added_id;
                    }
                }

                if (!$error && !$pic_resize_required && !$modification_required) {
                    if ($this->vars["replacement"]) {
                        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&card_id=" . $card . "&action=replace", array("card_id", "action")));
                    } elseif ($this->vars["prolong"]) {
                        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&card_id=" . $card . "&action=prolong", array("card_id", "action")));
                    } else {
                        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true", array("card_id", "action")));
                    }
                }
            }
        }
        // ###################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        if ($action == "add") {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_add.html";
        } else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_modify.html";
        }

        $tpl->setInstance($_SERVER["PHP_SELF"]."?language=".$this->language."&module=isic&type=addcard");
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

        // #################################

        if ($error == true) {
            if ($error_card_exists) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_card_exists"));
            } elseif ($error_card_exists_prolong) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_card_exists_prolong"));
            } elseif ($error_card_exists_replace) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_card_exists_replace"));
            } elseif ($error_prolong_not_allowed) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_prolong_not_allowed"));
            } elseif ($error_isic_number) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_isic_number"));
            } elseif ($error_pic_resize) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_pic_resize"));
            } elseif ($error_pic_save) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_pic_save"));
            } elseif ($error_pic_size) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_pic_size"));
            } elseif ($error_pic_format) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_pic_format"));
            } elseif ($error_status_required) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_status_required"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error"));
            }
        }

        if ($action == "modify") {
            // getting the current record data
            $r = &$this->db->query('
                SELECT
                    `module_isic_card`.*
                FROM
                    `module_isic_card`
                WHERE
                    `module_isic_card`.`id` = ?
                ', $card);

            $row_data = $r->fetch_assoc();
            if ($this->isic_common->canModifyCard($row_data["school_id"], $row_data["person_number"])) {
                $row_data["expiration_date_m"] = date("n", strtotime($row_data["expiration_date"]));
                $row_data["expiration_date_y"] = date("Y", strtotime($row_data["expiration_date"]));

            } else {
                redirect(processUrl(SITE_URL,$_SERVER["QUERY_STRING"], "error=modify", array("card_id", "action")));
            }
        } elseif (($action == "replace" || $action == "prolong") && $write != "true") {
            $r = &$this->db->query('
                SELECT
                    `module_isic_card`.*
                FROM
                    `module_isic_card`
                WHERE
                    `module_isic_card`.`id` = ?
                ', $card);
            $row_data = $r->fetch_assoc();
            $row_data["exported"] = "0000-00-00 00:00:00";
            $row_data["status_id"] = 0;
            $row_data["active"] = 0;
            $row_data["confirm_payment_collateral"] = 0;
            $row_data["confirm_payment_cost"] = 0;
            $row_data["confirm_user"] = 0;
            $row_data["confirm_admin"] = 0;
        }

        if (!$this->vars["language_id"]) {
            $this->vars["language_id"] = $this->language_default;
        }

        $card_active = false;
        if ($row_data["active"]) {
            $card_active = true;
        }
        $card_exported = false;
        if ($row_data["exported"] && ($row_data["exported"] != "0000-00-00 00:00:00")) {
            $card_exported = true;
        }
        $card_void = false;
        if (/*$row_data["status_id"] !$row_data["active"] ||*/ (strtotime($row_data["expiration_date"]) < time())) {
            $card_void = true;
        }

        $card_prolonged = false;
        $card_replaced = false;
        if ($row_data["person_number"] && $row_data["type_id"]) {
            // check if card is already prolonged
            $r = &$this->db->query('
                SELECT
                    COUNT(*) AS card_count
                FROM
                    `module_isic_card`
                WHERE
                    `person_number` = ? AND
                    `type_id` = ! AND
                    (`active` = 1 OR `exported` = ?) AND
                    `expiration_date` > NOW()
                ',
                $row_data["person_number"], $row_data["type_id"], '0000-00-00 00:00:00');
            if ($t_card = $r->fetch_assoc()) {
                if ($t_card["card_count"] > 1) {
                    $card_prolonged = true;
                    $card_replaced = true;
                }
            }
        }

        if ($action == "add" && !$error && !$this->vars["person_newsletter"]) {
            $this->vars["person_newsletter"] = 1;
        }

        $fields = array(
            "active" => array("checkbox",0,0,$this->vars["active"],"", $card_active ? "onClick=\"toggleReplacementButton();\"" : "","", ($card_exported && !$card_void) ? true : false, true),
            "status_id" => array("select",0,0,$this->vars["status_id"],"","","isic300", ($card_exported && $card_active) ? true : false, true, "status_help"),
            "language_id" => array("select",0,0,$this->vars["language_id"],"","","isic300", false, false),
            "kind_id" => array("select",0,0,$this->vars["kind_id"],"","","isic300", false, false),
            "bank_id" => array("select",0,0,$this->vars["bank_id"],"","","isic300", false, false),
            "type_id" => array("select",0,0,$this->vars["type_id"],"","","isic300", false, true),
            "school_id" => array("select", 0,0,$this->vars["school_id"],"","","isic300", false, true),
            "person_name_first" => array("textinput", 40,0,$this->vars["person_name_first"],"","","isic300", $card_exported ? false : true, true),
            "person_name_last" => array("textinput", 40,0,$this->vars["person_name_last"],"","onblur=\"generateBankAccountName();\"","isic300", $card_exported ? false : true, true),
            "person_number" => array("textinput", 40,0,$this->vars["person_number"],"","onblur=\"generateBirthday();\"","isic300", false, true),
            "person_addr1" => array("textinput", 40,0,$this->vars["person_addr1"],"","","isic300", $card_exported ? false : true, true),
            "person_addr2" => array("textinput", 40,0,$this->vars["person_addr2"],"","","isic300", $card_exported ? false : true, true),
            "person_addr3" => array("textinput", 40,0,$this->vars["person_addr3"],"","","isic300", $card_exported ? false : true, true),
            "person_addr4" => array("textinput", 40,0,$this->vars["person_addr4"],"","","isic300", $card_exported ? false : true, true),
            "person_email" => array("textinput", 40,0,$this->vars["person_email"],"","","isic300", $card_exported ? false : true, true),
            "person_phone" => array("textinput", 40,0,$this->vars["person_phone"],"","","isic300", $card_exported ? false : true, true, "phone_help"),
            "person_position" => array("textinput", 40,0,$this->vars["person_position"],"","","isic300", $card_exported ? false : true, false),
            "person_class" => array("textinput", 40,0,$this->vars["person_class"],"","","isic300", $card_exported ? false : true, false),
            "person_stru_unit" => array("textinput", 40,0,$this->vars["person_stru_unit"],"","","isic300", $card_exported ? false : true, false),
            "person_bankaccount" => array("textinput", 40,0,$this->vars["person_bankaccount"],"","","isic300", $card_exported ? false : true, true, "bankaccount_help"),
            "person_bankaccount_name" => array("textinput", 40,0,$this->vars["person_bankaccount_name"],"","","isic300", $card_exported ? false : true, true),
            "person_newsletter" => array("checkbox", 0,0,$this->vars["person_newsletter"],"","","", $card_exported ? false : true, true, "newsletter_help"),
            "pic" => array("file",43,0,$this->vars["pic"],"","","isic300", $card_exported ? false : true, true, "pic_help"),
            "confirm_user" => array("checkbox",0,0,$this->vars["confirm_user"],"","","", ($card_exported) ? false : true, true),
            "confirm_payment_collateral" => array("checkbox",0,0,$this->vars["confirm_payment_collateral"],"","","", ($card_exported) ? false : true, true, "collateral_help"),
            "confirm_payment_cost" => array("checkbox",0,0,$this->vars["confirm_payment_cost"],"","","", ($card_exported) ? false : true, true),
            "confirm_admin" => array("checkbox",0,0,$this->vars["confirm_admin"],"","","", ($card_exported) ? false : true, false),
            "returned" => array("checkbox",0,0,$this->vars["returned"],"","","", ($card_exported && $this->user_type == 1) ? true : false, true),
            "collateral_returned" => array("checkbox",0,0,$this->vars["collateral_returned"],"","","", ($card_exported && $this->user_type == 1) ? true : false, true),
        );

        // card statuses
        $list = array();
        $list[0] = "---";
        if ($row_data["type_id"]) {
            $r = &$this->db->query('
                SELECT
                    `module_isic_card_status`.*
                FROM
                    `module_isic_card_status`
                WHERE
                    `module_isic_card_status`.`card_type` = !
                ORDER BY
                    `module_isic_card_status`.`name`
                ', $row_data["type_id"]);

            while ($data = $r->fetch_assoc()) {
                $show_status = false;
                switch ($this->form_type) {
                    case 1: // replace
                        if ($data["action_type"] == 1) {
                            $show_status = true;
                        }
                    break;
                    case 2: // prolong
                        if ($data["action_type"] == 2) {
                            $show_status = true;
                        }
                    break;
                    case 3: // de-activate
                        $show_status = false;
                    break;
                    default:
                        $show_status = true;
                    break;
                }
                if ($show_status) {
                    $list[$data["id"]] = $data["name"];
                }
            }
        }
        $fields["status_id"][4] = $list;

        // card languages
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_language`.*
            FROM
                `module_isic_card_language`
            ORDER BY
                `module_isic_card_language`.`name`
            ');

        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        $fields["language_id"][4] = $list;

        // card kinds
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_kind`.*
            FROM
                `module_isic_card_kind`
            ORDER BY
                `module_isic_card_kind`.`id`
            LIMIT 10
            ');

        while ($data = $r->fetch_assoc()) {
            if ($row_data["kind_id"] == $data["id"] || $action == "add" && $data["id"] == 1) {
                $list[$data["id"]] = $data["name"];
            }
        }
        $fields["kind_id"][4] = $list;

        // banks
        $list = array();
        $list[0] = '---';

        $r = &$this->db->query('
            SELECT
                `module_isic_bank`.*
            FROM
                `module_isic_bank`
            ORDER BY
                `module_isic_bank`.`id`
            ');

        while ($data = $r->fetch_assoc()) {
            if ($row_data["bank_id"] == $data["id"]) {
                $list[$data["id"]] = $data["name"];
            }
        }

        $fields["bank_id"][4] = $list;

        // card types
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_card_type`.*
            FROM
                `module_isic_card_type`
            ORDER BY
                `module_isic_card_type`.`name`
            ');

        while ($data = $r->fetch_assoc()) {
            if (in_array($data["id"], $this->allowed_card_types)) {
                // card adding then check if user already has card of this type
                if ($action == "add") {
                    $person_number = $this->vars["person_number"] ? $this->vars["person_number"] : ($this->user_type == 1 ? "" : $this->user_code);
                    if (!$this->isic_common->getUserCardTypeExists($person_number , $data["id"])) {
                        $list[$data["id"]] = $data["name"];
                    }
                } else {
                    $list[$data["id"]] = $data["name"];
                }
            }
        }
        $fields["type_id"][4] = $list;

        // schools
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_school`.*
            FROM
                `module_isic_school`
            ORDER BY
                `module_isic_school`.`name`
            ');

        while ($data = $r->fetch_assoc()) {
            if (in_array($data["id"], $this->allowed_schools)) {
                $list[$data["id"]] = $data["name"];
            }
        }
        $fields["school_id"][4] = $list;

        // expiration date months
        /*
        $list = array();
        for ($u = 1; $u < 13; $u++) {
            $list[$u] = $txtf->display("month_".$u);
        }
        $fields["expiration_date_m"][4] = $list;

        // expiration date years
        $list = array();
        $beg_year = $row_data["expiration_date_y"] ? $row_data["expiration_date_y"] : date("Y");
        $end_year = date("Y") + 3;
        for ($u =  $beg_year; $u < $end_year; $u++) {
            $list[$u] = $u;
        }
        $fields["expiration_date_y"][4] = $list;
        */

        $required_fields = array();
        $t_school_id = $this->vars["school_id"] ? $this->vars["school_id"] : $row_data["school_id"];
        $t_type_id = $this->vars["type_id"] ? $this->vars["type_id"] : $row_data["type_id"];
        if ($this->isic_payment->getCardCollateralRequired($t_school_id , $t_type_id)) {
            $required_fields[] = "person_bankaccount";
        }

        while (list($key, $val) = each($fields)) {
            $fdata["type"] = $val[0];
            $fdata["size"] = $val[1];
            $fdata["cols"] = $val[1];
            $fdata["rows"] = $val[2];
            $fdata["list"] = $val[4];
            $fdata["java"] = $val[5];
            $fdata["class"] = $val[6];

            if (($action == "modify" || (($action == "replace" || $action == "prolong") && !$this->vars["write"])) && $error != true) {
                $val[3] = $row_data[$key];
            }

            if ($action == "add" || ($action == "modify" || $action == "replace" || $action == "prolong") && $val[7]) {
                $f = new AdminFields("$key",$fdata);
                if ($fdata["type"] == "textfield") {
                    $f->classTextarea = "isic300";
                }
                $field_data = $f->display($val[3]);
                $field_data = str_replace("name=\"" . $key . "\"", "id=\"" . $key . "\" " . "name=\"" . $key . "\"", $field_data);
            } else {
                if (is_array($val[4])) {
                    $field_data = $val[4][$val[3]];
                } else {
                    if ($val[0] == "checkbox") {
                        $field_data = $txt->display("active" . $val[3]);
                    } else {
                        $field_data = $val[3];
                    }
                }
            }

            if (is_array($required_fields) && in_array($key, $required_fields)) {
                $required_field = "required";
            } else {
                $required_field = "";
            }

            switch ($key) {
                case "returned":
                    if ($card_exported) {
                        $tpl->addDataItem(strtoupper($key) . ".FIELD_$key", $field_data);
                    }
                break;
                case "collateral_returned":
                    if ($card_exported) {
                        $sub_tpl_name = "COL_RET";
                        $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                    }
                break;
                case "confirm_user":
                    $tpl->addDataItem(strtoupper($key) . ".FIELD_$key", $field_data);
                    if ($this->user_type == 2) {
                        $tpl->addDataItem(strtoupper($key) . ".COND.TEXT", $txt->display("confirm_user_conditions"));
                    }
                break;
                case "confirm_admin":
                    if ($this->user_type == 1) {
                        $tpl->addDataItem(strtoupper($key) . ".FIELD_$key", $field_data);
                    }
                break;
                case "confirm_payment_collateral":
                    if ($this->isic_payment->getCardCollateralRequired($row_data["school_id"], $row_data["type_id"]) && $this->isic_payment->getCardCollateralSum($row_data["school_id"], $row_data["type_id"])) {
                        $sub_tpl_name = "CNFRM_PAY_COLL";
                        if ($this->user_type == 1) {
                            $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                            if ($val[9]) {
                                $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $txt->display($val[9]))));
                            }
                        } elseif ($this->user_type == 2) {
                            if ($action == "modify") {
                                if ($val[9]) {
                                    $tpl->addDataItem($sub_tpl_name . "_REG.TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $txt->display($val[9]))));
                                }
                                if ($row_data["confirm_payment_collateral"]) {
                                    $tpl->addDataItem($sub_tpl_name . "_REG.OK.DONE", $txt->display("paid"));
                                } else {
                                    $tpl->addDataItem($sub_tpl_name . "_REG.PAY.SUM", $txt->display("collateral_sum") . ": " . $this->isic_payment->getCardCollateralSum($row_data["school_id"], $row_data["type_id"]));
                                    $tpl->addDataItem($sub_tpl_name . "_REG.PAY.CARD_ID", $card);
                                    // check if previous card has collateral payment
                                    if ($this->isic_payment->cardCollateralPaid($row_data["prev_card_id"])) {
                                        $tpl->addDataItem($sub_tpl_name . "_REG.PAY.DESCRIPTION", $txt->display("collateral_description"));
                                    }
                                }
                            }
                        }
                    }
                break;
                case "confirm_payment_cost":
                    $prev_card_status = $this->isic_common->getCardStatus($row_data["prev_card_id"]);
                    $is_card_first = $this->isic_common->isUserCardTypeFirst($row_data["person_number"], $row_data["id"], $row_data["type_id"]);
                    if ($this->isic_payment->getCardCostRequired($row_data["school_id"], $prev_card_status, $row_data["type_id"], $is_card_first) && $this->isic_payment->getCardCostSum($row_data["school_id"], $prev_card_status, $row_data["type_id"], $is_card_first)) {
                        $sub_tpl_name = "CNFRM_PAY_COST";
                        if ($this->user_type == 1) {
                            $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                        } elseif ($this->user_type == 2) {
                            if ($action == "modify") {
                                if ($row_data["confirm_payment_cost"]) {
                                    $tpl->addDataItem($sub_tpl_name . "_REG.OK.DONE", $txt->display("paid"));
                                } else {
                                    $tpl->addDataItem($sub_tpl_name . "_REG.PAY.SUM", $txt->display("cost_sum") . ": " . $this->isic_payment->getCardCostSum($row_data["school_id"], $prev_card_status, $row_data["type_id"], $is_card_first));
                                    $tpl->addDataItem($sub_tpl_name . "_REG.PAY.CARD_ID", $card);
                                }
                            }
                        }
                    }
                break;
                case "person_bankaccount_name":
                    $sub_tpl_name = "PERSON_BANKACCNAME";
                    $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                break;
                default:
                    if ($this->user_type == 1 || $this->user_type == 2 && $val[8]) {
                        $sub_tpl_name = strtoupper($key);
                        $tpl->addDataItem($sub_tpl_name . ".FIELD_$key", $field_data);
                        $tpl->addDataItem($sub_tpl_name . ".REQUIRED", $required_field);
                        if ($val[9]) {
                            $tpl->addDataItem($sub_tpl_name . ".TOOLTIP", str_replace("\n", "<br>", str_replace("\r", "", $txt->display($val[9]))));
                        }
                    }
                break;
            }
            unset($fdata);
        }

        if ($this->user_type == 1) {
            $tpl->addDataItem("ISIC_NUMBER.FIELD_isic_number", $action == "modify" ? $row_data["isic_number"] : $txt->display("generated_automatically"));
            $tpl->addDataItem("CARD_NUMBER.FIELD_card_number", $action == "modify" ? $row_data["card_number"] : $txt->display("generated_automatically"));
            $tpl->addDataItem("ACTIVATION_DATE.FIELD_activation_date", $action == "modify" ? ($row_data["activation_date"] == "0000-00-00" ? "-" : date("m/y", strtotime($row_data["activation_date"]))) : $txt->display("generated_automatically"));
        }

        if ($card_exported) {
            $tpl->addDataItem("RETURNED.FIELD_returned_date", ($row_data["returned_date"] == "0000-00-00") ? "" : ("(" . date("d/m/Y", strtotime($row_data["returned_date"])) . ")"));
            $tpl->addDataItem("COL_RET.FIELD_collateral_returned_date", ($row_data["collateral_returned_date"] == "0000-00-00") ? "" : ("(" . date("d/m/Y", strtotime($row_data["collateral_returned_date"])) . ")"));
        }
        $tpl->addDataItem("PERSON_BIRTHDAY.FIELD_person_birthday", ($action == "modify" || $action == "replace" || $action == "prolong") ? date("d/m/Y", strtotime($row_data["person_birthday"])) : $this->vars["person_birthday"]);
        $tpl->addDataItem("FIELD_person_birthday", ($action == "modify" || $action == "replace" || $action == "prolong") ? date("d/m/Y", strtotime($row_data["person_birthday"])) : $this->vars["person_birthday"]);
        $tpl->addDataItem("EXPIRATION_DATE.FIELD_expiration_date", $action == "modify" ? ($row_data["expiration_date"] == "0000-00-00" ? "-" : date("m/y", strtotime($row_data["expiration_date"]))) : $txt->display("generated_automatically"));
        $tpl->addDataItem("MIN_WIDTH", round($this->image_size_x / 2));
        $tpl->addDataItem("MIN_HEIGHT", round($this->image_size_y / 2));


        if ($pic_resize_required) {
            $tpl->addDataItem("EDIT_PIC.DATA_pic", $tmp_pic);
            $tpl->addDataItem("EDIT_PIC.BUTTON", $txt->display("resize"));
            $tpl->addDataItem("EDIT_PIC.MAX_WIDTH", $this->image_size_x);
        } else {
            if ($row_data["pic"] != "") {
                $big_picture = str_replace("_thumb.", ".", $row_data["pic"]);
                if (@file_exists(SITE_PATH . substr($big_picture, strpos($big_picture, "upload")-1))) {
                    $tpl->addDataItem("SHOW_PIC.DATA_pic", SITE_URL . $row_data["pic"]);
                } else {
                    $tpl->addDataItem("SHOW_PIC.DATA_pic", SITE_URL . $row_data["pic"]);
                }
            } else {
                $tpl->addDataItem("SHOW_PIC.DATA_pic", "img/tyhi.gif");
            }
        }

        if ($action == "add" || $action == "replace" || $action == "prolong") {
            $tpl->addDataItem("SUBMIT.BUTTON", $txt->display("button_add"));
        } else {
            if (($this->form_type == 3 || !$this->form_type) || ($action == "modify" && !$card_exported)) {
                $tpl->addDataItem("SUBMIT.BUTTON", $txt->display("button_mod"));
            }
            if (/*$this->user_type == 1 &&*/ ($this->form_type == 1 || !$this->form_type) && $card_active && $card_exported && !$card_void && !$card_replaced) {
                $tpl->addDataItem("REPLACE.BUTTON", $txt->display("replace"));
            }
            if (($this->form_type == 2 || !$this->form_type) && $card_exported && /*!$card_void &&*/ !$card_prolonged) {
                $tpl->addDataItem("PROLONG.BUTTON", $txt->display("prolong"));
            }
        }

        $hidden = "<input type=hidden name=\"action\" value=\"$action\">\n";
        $hidden .= "<input type=hidden name=\"write\" value=\"true\">\n";
        $hidden .= "<input type=hidden name=\"card_id\" value=\"" . $card . "\">\n";
        if ($pic_resize_required) {
            $hidden .= "<input type=hidden name=\"pic_resize\" value=\"true\">\n";
            $hidden .= "<input type=hidden name=\"pic_name\" value=\"" . $pic_filename . "\">\n";
        }
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", $general_url);

        return $tpl->parse();
    }


    /**
     * Deletes card record from table
     *
     * @param int $card card id
     * @return redirect to a listview page
    */

    function deleteCard($card) {
        //redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&error=delete", array("card_id", "action")));
        if ($card) {
            $r = &$this->db->query('SELECT * FROM `module_isic_card` WHERE `module_isic_card`.`id` = ?', $card);
            while ($check_data = $r->fetch_assoc()) {
                if ($this->isic_common->canDeleteCard($check_data["school_id"]) && $check_data["exported"] == "0000-00-00 00:00:00") {
                    $r2 = &$this->db->query('DELETE FROM `module_isic_card` WHERE `module_isic_card`.`id` = ?', $card);
                    $this->isic_common->releaseISICNumber($check_data["type_id"], $check_data["isic_number"]);
                } else {
                    redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true&error=delete", array("card_id", "action")));
                }
            }
        }
        redirect(processUrl(SITE_URL, $_SERVER["QUERY_STRING"], "nocache=true", array("card_id", "action")));
    }

    /**
     * Sets view type
     *
     * @param string $list_type list type (all, ordered, void)
    */

    function setListType($list_type) {
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
        $txt = new Text($this->language, "module_isic_card");

        $list = array();
        $list[""] = $txt->display("choose_form_type");
        $list[1] = $txt->display("form_type1");
        $list[2] = $txt->display("form_type2");
        $list[3] = $txt->display("form_type3");

        // ####
        return array($txt->display("form_type"), "select", $list);
        // name, type, list
    }
}
