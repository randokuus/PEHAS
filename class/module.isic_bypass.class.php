<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicListViewSortOrder.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");

class isic_bypass {
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

    var $listSortFields = array(
        "lock_name" => "module_isic_bypass_lock.name",
        "event_time" => "module_isic_bypass_event.event_time",
        "direction" => "module_isic_bypass_event.direction",
        "access" => "module_isic_bypass_event.access",
        "person_name_first" => "module_isic_card.person_name_first",
        "person_name_last" => "module_isic_card.person_name_last",
        "isic_number" => "module_isic_card.isic_number",
    );
    var $listSortFieldDefault = 'event_time';

    /**
     * @var IsicDB_Schools
     */
    private $isicDbSchools;

   /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */

    function isic_bypass () {
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
        $this->isicDbSchools = IsicDB::factory('Schools');

        $this->allowed_schools = $this->isic_common->allowed_schools;
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

        $result = $this->showEventList();
        return $result;
    }

    /**
     * Displays list of cards
     *
     * @return string html listview of cards
    */

    function showEventList() {
        $content = @$this->vars["content"];
        $start = @$this->vars["start"];

        if ($content) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=$content";
        }

        $general_url_plain = $general_url;

        if (!$start) {
            $start = 0;
        }

        $txt = new Text($this->language, "module_isic_bypass");
        $txtf = new Text($this->language, "output");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . "module_isic_bypass_list.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=isic_bypass&type=list&sort=" . $this->vars["sort"] . "&sort_order=" . $this->vars["sort_order"]);
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module isic_bypass cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        // #################################

        $cur_time = time();

        if (!$this->vars["filter_start_time"]) {
            $this->vars["filter_start_time"] = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), 1, date("Y", $cur_time)), 'Y-m-d H:i:s');
        } else {
            $this->vars["filter_start_time"] = IsicDate::getTimeFormattedFromEuroToDb($this->vars["filter_start_time"]);
        }
        $beg_date = IsicDate::getDateTimeFormatted($this->vars["filter_start_time"]);

        if (!$this->vars["filter_end_time"]) {
            $this->vars["filter_end_time"] = IsicDate::getCurrentTimeFormatted('Y-m-d H:i:s');
        } else {
            $this->vars["filter_end_time"] = IsicDate::getTimeFormattedFromEuroToDb($this->vars["filter_end_time"]);
        }
        $end_date = IsicDate::getDateTimeFormatted($this->vars["filter_end_time"]);

        $ff_fields = array(
            "school_id", "lock_id", "person_name_first", "person_name_last", "person_number", "isic_number", "card_number", "access", "direction", "start_time", "end_time"
        );

        $ff_fields_table = array(
            "module_isic_bypass_lock", "module_isic_bypass_event", "module_isic_card", "module_isic_card", "module_isic_card", "module_isic_card", "module_isic_bypass_event", "module_isic_bypass_event"
        );

        $ff_fields_partial = array(
            "person_name_first", "person_name_last", "person_number", "isic_number", "card_number"
        );

        $ff_fields_range_match = array("start_time" => "event_time", "end_time" => "event_time");
        $ff_fields_range_from = array("start_time");
        $ff_fields_range_to = array("end_time");

        $condition = array();
        $url_filter = '';
        $hidden = '';
        for ($f = 0; $f < sizeof($ff_fields); $f++) {
            if ($this->vars["filter_".$ff_fields[$f]] != "" && $this->vars["filter_".$ff_fields[$f]] != "0") {
                if (in_array($ff_fields[$f], $ff_fields_partial)) {
                    $condition[] = $this->db->quote_field_name($ff_fields_table[$f] . "." . $ff_fields[$f]) . " LIKE " . $this->db->quote("%" . $this->vars["filter_".$ff_fields[$f]] . "%");
                } elseif (in_array($ff_fields[$f], $ff_fields_range_from)) {
                    $condition[] = $this->db->quote_field_name($ff_fields_table[$f] . "." . $ff_fields_range_match[$ff_fields[$f]]) . " >= " . $this->db->quote($this->vars["filter_".$ff_fields[$f]]);
                } elseif (in_array($ff_fields[$f], $ff_fields_range_to)) {
                    $condition[] = $this->db->quote_field_name($ff_fields_table[$f] . "." . $ff_fields_range_match[$ff_fields[$f]]) . " <= " . $this->db->quote($this->vars["filter_".$ff_fields[$f]]);
                } else {
                    if ($ff_fields[$f] == "access") {
                        $condition[] = $this->db->quote_field_name($ff_fields_table[$f] . "." . $ff_fields[$f]) . " = " . $this->db->quote($this->vars["filter_".$ff_fields[$f]] - 1);
                    } else {
                        $condition[] = $this->db->quote_field_name($ff_fields_table[$f] . "." . $ff_fields[$f]) . " = " . $this->db->quote($this->vars["filter_".$ff_fields[$f]]);
                    }
                }

                if ($ff_fields[$f] == "start_time") {
                    $t_val = $beg_date;
                } else if ($ff_fields[$f] == "end_time") {
                    $t_val = $end_date;
                } else {
                    $t_val = $this->vars["filter_".$ff_fields[$f]];
                }
                $url_filter .= "&filter_" . $ff_fields[$f] . "=" . urlencode($t_val);
                $hidden .= "<input type=\"hidden\" name=\"filter_" . $ff_fields[$f] . "\" value=\"" . urlencode($t_val) . "\">\n";
            }
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

        $listSortOrder = new IsicListViewSortOrder($this->listSortFields, $this->listSortFieldDefault, $this->vars);
        $hidden .= IsicForm::getHiddenField('sort', $listSortOrder->getSort());
        $hidden .= IsicForm::getHiddenField('sort_order', $this->vars["sort_order"]);
        $hidden .= IsicForm::getHiddenField('start', $start);

        if ($this->user_type == 1) {
            $res =& $this->db->query("
                SELECT
                    `module_isic_bypass_event`.*,
                    `module_isic_card`.`person_name_first`,
                    `module_isic_card`.`person_name_last`,
                    `module_isic_card`.`person_number`,
                    `module_isic_card`.`isic_number`,
                    `module_isic_bypass_lock`.`name` AS lock_name
                FROM
                    `module_isic_bypass_event`,
                    `module_isic_bypass_lock`,
                    `module_isic_card`
                WHERE
                    `module_isic_bypass_event`.`lock_id` = `module_isic_bypass_lock`.`id` AND
                    `module_isic_bypass_event`.`card_id` = `module_isic_card`.`id` AND
                    `module_isic_card`.`school_id` IN (!@)
                    !
                ORDER BY
                    ?f !
                LIMIT !, !",
                $this->allowed_schools,
                $condition_sql,
                $listSortOrder->getOrderBy(),
                $listSortOrder->getSortOrder(),
                $start,
                $this->maxresults
            );
        } elseif ($this->user_type == 2) {
            $res =& $this->db->query("
                SELECT
                    `module_isic_bypass_event`.*,
                    `module_isic_card`.`person_name_first`,
                    `module_isic_card`.`person_name_last`,
                    `module_isic_card`.`person_number`,
                    `module_isic_card`.`isic_number`,
                    `module_isic_bypass_lock`.`name` AS lock_name
                FROM
                    `module_isic_bypass_event`,
                    `module_isic_bypass_lock`,
                    `module_isic_card`
                WHERE
                    `module_isic_bypass_event`.`lock_id` = `module_isic_bypass_lock`.`id` AND
                    `module_isic_bypass_event`.`card_id` = `module_isic_card`.`id`
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
        $card_url = $this->isic_common->getGeneralUrlByTemplate(870);

        if ($res !== false) {
            $row = 1;
            if ($res->num_rows()) {
                while ($data = $res->fetch_assoc()) {
                    $tpl->addDataItem("DATA.DATA_NUMBER", $row++);
                    $tpl->addDataItem("DATA.STYLE", $data["access"] ? "tRowOk" : "tRowNotOk");
                    $tpl->addDataItem("DATA.DATA_ACCESS", $txt->display("access" . $data["access"]));
                    $tpl->addDataItem("DATA.DATA_DIRECTION", $txt->display("direction" . $data["direction"]));
                    $tpl->addDataItem("DATA.DATA_LOCK_NAME", $data["lock_name"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_NAME_FIRST", $data["person_name_first"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_NAME_LAST", $data["person_name_last"]);
                    $tpl->addDataItem("DATA.DATA_PERSON_NUMBER", $data["person_number"]);
                    $tpl->addDataItem("DATA.DATA_ISIC_NUMBER", $data["isic_number"]);
                    $tpl->addDataItem("DATA.DATA_EVENT_TIME", IsicDate::getDateTimeFormatted($data["event_time"]));
                    $tpl->addDataItem("DATA.URL_DETAIL", $card_url . "&card_id=" . $data["card_id"]);
                }
                $res->free();
            } else {
                $tpl->addDataItem("RESULTS", $txt->display("results_none"));
            }
        } else {
            echo "Database error " . $this->db->error_code() . ": " . $this->db->error_string();
        }


        // page listing
        if ($this->user_type == 1) {
            $res =& $this->db->query("
                SELECT
                    COUNT(*) AS events_total
                FROM
                    `module_isic_bypass_event`,
                    `module_isic_bypass_lock`,
                    `module_isic_card`
                WHERE
                    `module_isic_bypass_event`.`lock_id` = `module_isic_bypass_lock`.`id` AND
                    `module_isic_bypass_event`.`card_id` = `module_isic_card`.`id` AND
                    `module_isic_card`.`school_id` IN (!@)
                    !",
                $this->allowed_schools,
                $condition_sql
            );
        } elseif ($this->user_type == 2) {
            $res =& $this->db->query("
                SELECT
                    COUNT(*) AS events_total
                FROM
                    `module_isic_bypass_event`,
                    `module_isic_bypass_lock`,
                    `module_isic_card`
                WHERE
                    `module_isic_bypass_event`.`lock_id` = `module_isic_bypass_lock`.`id` AND
                    `module_isic_bypass_event`.`card_id` = `module_isic_card`.`id`
                    !",
                $condition_sql
            );
        }

        $data = $res->fetch_assoc();
        $total = $results = $data["events_total"];

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

        $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . "&contact_id=" . $data["id"] .
            $url_filter . "&sort=" . $this->vars["sort"]."&sort_order=" . $this->vars["sort_order"],
            $this->maxresults, $txt->display("prev"), $txt->display("next")));

        // ####

        switch ($this->vars["error"]) {
            case "view":
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("error_view"));
            break;
        }

        // filter fields
        $fields = array(
            "filter_start_time" => array("textinput", 40,0,$beg_date,"","","datePicker"),
            "filter_end_time" => array("textinput", 40,0,$end_date,"","","datePicker"),
            "filter_lock_id" => array("select",0,0,$this->vars["filter_lock_id"],"","",""),
            "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],"","",""),
            "filter_access" => array("select",0,0,$this->vars["filter_access"],"","",""),
            "filter_direction" => array("select", 0,0,$this->vars["filter_direction"],"","",""),
            "filter_person_name_first" => array("textinput", 20,0,$this->vars["filter_person_name_first"],"","",""),
            "filter_person_name_last" => array("textinput", 20,0,$this->vars["filter_person_name_last"],"","",""),
            "filter_person_number" => array("textinput", 20,0,$this->vars["filter_person_number"],"","",""),
            "filter_isic_number" => array("textinput", 20,0,$this->vars["filter_isic_number"],"","",""),
            "filter_card_number" => array("textinput", 20,0,$this->vars["filter_card_number"],"","",""),
        );

        // active selection
        $list = array();
        $list[0] = $txt->display("all");
        for ($i = 0; $i < 2; $i++) {
            $list[$i + 1] = $txt->display("access" . $i);
        }
        $fields["filter_access"][4] = $list;

        // access selection
        $list = array();
        $list[0] = $txt->display("all");
        for ($i = 1; $i < 3; $i++) {
            $list[$i] = $txt->display("direction" . $i);
        }
        $fields["filter_direction"][4] = $list;

        // locks
        $list = array();
        $r = &$this->db->query('
            SELECT
                `module_isic_bypass_lock`.*
            FROM
                `module_isic_bypass_lock`
            ORDER BY
                `module_isic_bypass_lock`.`name`
            ');

        $list[0] = $txt->display("all_locks");
        while ($data = $r->fetch_assoc()) {
            $list[$data["id"]] = $data["name"];
        }
        $fields["filter_lock_id"][4] = $list;

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
            if (in_array($data["id"], $this->allowed_schools) && !$this->isicDbSchools->isEhlRegion($data)) {
                $list[$data["id"]] = $data["name"];
            }
        }
        $fields["filter_school_id"][4] = $list;

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
            $tpl->addDataItem("FIELD_$key", $field_data);
            unset($fdata);
        }

        $listSortOrder->showTitleFields($tpl, $txt, $general_url . $url_filter);
        $tpl->addDataItem("URL_GENERAL_PLAIN", $general_url_plain);
        $tpl->addDataItem("URL_GENERAL", $general_url . $url_filter . "&sort_order=" . $this->vars["sort_order"]);
        $tpl->addDataItem("SELF", $general_url);

        // ####
        return $tpl->parse();
    }


    /**
     * Displays detail view of a card
     *
     * @param int $card card id
     * @return string html detailview of a card
    */

    function showEvent($card) {
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
                $tpl->addDataItem("DATA_delivery_addr1", $data["delivery_addr1"]);
                $tpl->addDataItem("DATA_delivery_addr2", $data["delivery_addr2"]);
                $tpl->addDataItem("DATA_delivery_addr3", $data["delivery_addr3"]);
                $tpl->addDataItem("DATA_delivery_addr4", $data["delivery_addr4"]);
                $tpl->addDataItem("DATA_person_email", $data["person_email"]);
                $tpl->addDataItem("DATA_person_phone", $data["person_phone"]);
                $tpl->addDataItem("DATA_person_position", $data["person_position"]);
                $tpl->addDataItem("DATA_person_class", $data["person_class"]);
                $tpl->addDataItem("DATA_activation_date_m", date("m", strtotime($data["activation_date"])));
                $tpl->addDataItem("DATA_activation_date_y", date("y", strtotime($data["activation_date"])));
                $tpl->addDataItem("DATA_expiration_date_m", date("m", strtotime($data["expiration_date"])));
                $tpl->addDataItem("DATA_expiration_date_y", date("y", strtotime($data["expiration_date"])));
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
        return array();
        // name, type, list
    }
}
