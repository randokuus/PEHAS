<?php

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/mail/htmlMimeMail.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/SMS/MobileNumberValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/SMS/SMSSendQueue.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicLogger.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicUser.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicForm.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/SMS/MobileNumberValidator.php");


class messages {
    const TABLE_NAME = 'module_messages';

    const SEND_TYPE_DB = 1;
    const SEND_TYPE_EMAIL = 2;
    const SEND_TYPE_SMS = 3;

    const STEP_PARAMETERS = 1;
    const STEP_CONFIRM = 2;

    const RECIPIENT_TYPE_GROUP = 1;
    const RECIPIENT_TYPE_PERSON_NUMBER = 2;

    private $sendTypeList = array(
        self::SEND_TYPE_DB,
        self::SEND_TYPE_EMAIL,
        self::SEND_TYPE_SMS
    );

    private $requiredFields = array(
        self::SEND_TYPE_DB => array(
            'title',
            'text'
        ),
        self::SEND_TYPE_EMAIL => array(
            'title',
            'text'
        ),
        self::SEND_TYPE_SMS => array(
            'text',
            'school_id'
        )
    );

    private $requiredRecipients = array(
        self::RECIPIENT_TYPE_GROUP => array(
        ),
        self::RECIPIENT_TYPE_PERSON_NUMBER => array(
            'person_numbers'
        )
    );
    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;

    var $articleid;
    var $tmpl = false;
    var $debug = false;
    var $mode = false; //"short";
    var $language = false;
    var $siteroot = false;
    var $vars = array();

    /**
     * @var Database
     */
    private $dbc;
    var $content_module = false;
    var $module_param = array();
    var $userid = false;
    var $usergroup = false;
    var $cachelevel = TPL_CACHE_NOTHING;
    var $cachetime = 43200; //cache time in minutes
    var $tplfile = "messages";
    var $info_email = "support@modera.net";
    var $maxresults = 10;
    var $user_email = false;
    private $user_name = null;
    /**
     * @var user type (1 - can view all cards from the school his/her usergroup belongs to, 2 - only his/her own cards)
     */
    var $user_type = false;

    /**
     * Groups that are allowed to be shown in group list
     *
     * @var array
     * @access protected
     */
    var $allowed_groups = array();

    /**
     * @var IsicDB_Users
     */
    private $isicDbUser;

    /**
     * @var IsicCommon
     */
    private $isicCommon;

    private $activeSchool;

    /**
     * @var IsicDB_SchoolSMSCredit
     */
    private $isicSchoolSMSCredit;

    /**
     * @var IsicDB_GlobalSettings
     */
    private $isicGlobalSettings;

    /** @var IsicLogger */
    private $logger;

    /**
     * @var IsicUser
     */
    private $isicUser;

    /**
     * @var IsicDB_Schools
     */
    private $isicDbSchools;

    private $smsPrice;

    private $smsFrom;

    private $userList = array();

    /**
     * Info message match array
     *
     * @var array
     * @access protected
     */
    var $info_message = array(
        "add" => "info_message_add",
        "modify" => "info_message_modify",
        "delete" => "info_message_delete",
        'sent' => 'info_message_sent'
    );

    /**
     * @var Text
     */
    private $txt;

    /** Constructor
     */

    public function __construct() {
        global $db;
        global $language;
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->language = $language;
        $this->db = $GLOBALS['database'];
        $this->debug = $GLOBALS["modera_debug"];
        if (!is_object($db)) {
            $db = new DB;
            $this->dbc = $db->connect();
        } else {
            $this->dbc = $db->con;
        }

        $this->userid = $GLOBALS["user_data"][0];
        $this->user_name = $GLOBALS["user_data"][1];
//  $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->groupid = $GLOBALS["usr"]->groups[0];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_email = $GLOBALS["user_data"][8];

        if ($GLOBALS["site_settings"]["admin_email"]) $this->info_email = $GLOBALS["site_settings"]["admin_email"];

        if ($this->content_module == true) {
            $this->getParameters();
        }
        $this->allowed_groups = $this->createAllowedGroups();
        $this->isicCommon = IsicCommon::getInstance();
        $this->isicSchoolSMSCredit = IsicDB::factory('SchoolSMSCredit');
        $this->isicDbUser = IsicDB::factory('Users');
        $this->isicGlobalSettings = IsicDB::factory('GlobalSettings');
        $this->activeSchool = $this->getActiveSchool();
        $this->isicDbSchools = IsicDB::factory('Schools');
        $this->isicUser = new IsicUser($this->userid);
        $this->logger = new IsicLogger();
        $this->smsPrice = floatval($this->isicGlobalSettings->getRecord('sms_price'));
        $this->smsFrom = $this->isicGlobalSettings->getRecord('sms_from');
        $this->txt = new Text($this->language, "module_messages");
    }

    // ########################################

    // Main function to call

    function show($mode)
    {
        if ($this->checkAccess() == false) return "";

        if (!$this->userid) {
            trigger_error("Module 'messages' requires an authorized user. Configure the site to be password protected.", E_USER_ERROR);
        }

        // Hack solution to always open the add view if action is not specified
        if (!isset($this->vars['action'])) {
            $this->vars['action'] = 'add';
        }

        if (($mode && !$this->vars["action"]) || ($mode == "info" && $this->vars["action"]) || ($mode == "short" && $this->vars["action"])) {
            $result = $this->display($mode, $this->vars["articleid"]);
        } else {
            if ($this->vars["action"] == "add") {
                $result = $this->addMessage(false, $this->vars["action"]);
            } else if ($this->vars["articleid"] && $this->vars["action"] == "modify") {
                $result = $this->addMessage($this->vars["articleid"], $this->vars["action"]);
            } else if ($this->vars["articleid"] && $this->vars["action"] == "delete") {
                $result = $this->deleteMessage($this->vars["articleid"]);
            }
        }
        return $result;
    }

    private function getActiveSchool() {
        if ($this->isicDbUser->isCurrentUserSuperAdmin()) {
            $schoolId = 0;
        } else {
            $schoolId = $this->isicCommon->allowed_schools[0];
        }
        return $schoolId;
    }

    /** Main object display function
     */
    function display($mode, $articleid)
    {
        if ($articleid != "") {
            $this->articleid = addslashes($articleid);
            //$this->cachelevel = TPL_CACHE_NOTHING;
        }
        if ($mode != "") {
            $this->mode = $mode;
        }

        $sq = new sql;

        $txt = new Text($this->language, "module_messages");
        $txto = new Text($this->language, "output");

        // instantiate template class
        $tpl = new template;
        $tpl->tplfile = $this->tplfile . "_" . $this->mode . "_usr" . $this->userid;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setForceCompile(true);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        // creating correct url for messages
        $sql = "SELECT content, structure FROM content WHERE template = 130 AND language = '" . addslashes($this->language) . "' LIMIT 1";
        $sq->query($this->dbc, $sql);
        if ($data = $sq->nextrow()) {
            $contact_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"];
            if ($data["content"]) {
                $contact_url .= "&content=" . $data["content"];
            }
            $tpl->addDataItem("MESSAGES_URL", $contact_url);
            if ($this->isicDbUser->isCurrentUserAdmin()) {
                $tpl->addDataItem("ADD.MESSAGES_URL", $contact_url);
            }
        }

        if ($this->mode == "info") {
            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_messages_info.html";
        } else if ($this->mode == "short") {
            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_messages_short.html";
        } else if ($this->mode == "full" && !$this->articleid) {
            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_messages_list.html";
        } else if ($this->mode == "full" && $this->articleid) {
            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_messages_detail.html";
        }

        $tpl->setInstance($_SERVER['PHP_SELF'] . "?language=" . $this->language . "&module=messages&articleid=" . $this->articleid);
        $tpl->setTemplateFile($template);

        // register that a message has been read
        $this->setMessageAsRead($sq);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "messages";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module messages cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }
        }

        // #################################

        // user Check
        $usr_check = " AND ((module_messages.client LIKE '" . $this->userid .
            ",%' OR module_messages.client LIKE '%," . $this->userid .
            ",%' OR module_messages.client LIKE '%," . $this->userid .
            "' OR module_messages.client = '' OR module_messages.client = '" . $this->userid .
            "' OR module_messages.client = 0) OR module_messages.user = " . $this->userid . ")"
        ;

        $general_url = $this->getGeneralUrl($sq);

        // #################################

        if ($this->mode == "info") {
            $sql = "
              SELECT
                module_messages_status.*,
                module_messages_deleted.msgdel
              FROM
                module_messages
                LEFT JOIN module_messages_status ON
                  module_messages.id = module_messages_status.msg_id
                LEFT JOIN module_messages_deleted ON
                  module_messages.id = module_messages_deleted.msg_id
              WHERE
                1 = 1
                $usr_check"
            ;

            //AND (module_messages_status.msgread = 0 OR module_messages_status.msgread = NULL)
            //echo "<!-- $sql -->";

            $sq->query($this->dbc, $sql);
            $count = $sq->numrows;
            while ($data = $sq->nextrow()) {
                if ($data["msgread"] == 1) $count--;
            }
            $sq->free();

            if ($count > 0) {
                $tpl->addDataItem("INFO", ereg_replace("{NR}", "$count", $txt->display("msgs_info")));
                $tpl->addDataItem("URL", $general_url);
            } else {
                $tpl->addDataItem("INFO", $txt->display("msgs_nomsgs"));
                $tpl->addDataItem("URL", $general_url);
            }
        } // #################################

        else if ($this->mode == "short") {
            // first count how many unred messages are there
            $total_mess = 0;
            $sql = "
                SELECT
                    module_messages_status.msgread,
                    module_messages_deleted.msgdel
                FROM
                    module_messages
                    LEFT JOIN module_messages_status ON
                        module_messages.id = module_messages_status.msg_id AND
                        module_messages_status.user = '" . $this->userid . "'
                    LEFT JOIN module_messages_deleted ON
                        module_messages.id = module_messages_deleted.msg_id AND
                        module_messages_deleted.user = '" . $this->userid . "'
                WHERE
                    module_messages_deleted.msgdel IS NULL
                    $usr_check"
            ;
            $sq->query($this->dbc, $sql);

            while ($data = $sq->nextrow()) {
                if ($data["msgread"] == 0) {
                    $total_mess++;
                }
            }

            $tpl->addDataItem("MESSAGES_TOTAL", $total_mess);

            $sql = "
                SELECT
                    module_messages.id,
                    date_format(module_messages.entrydate, '%d.%m.%Y') as date,
                    module_messages.title,
                    module_messages.lead,
                    module_messages_status.msgread,
                    module_user_users.username,
                    CONCAT(module_user_users.name_first, ' ', module_user_users.name_last) AS fullname,
                    module_messages_deleted.msgdel
                FROM
                    module_messages
                    LEFT JOIN module_messages_status ON
                        module_messages.id = module_messages_status.msg_id AND
                        module_messages_status.user = '" . $this->userid . "'
                    LEFT JOIN module_messages_deleted ON
                        module_messages.id = module_messages_deleted.msg_id AND
                        module_messages_deleted.user = '" . $this->userid . "'
                    LEFT JOIN module_user_users ON
                        module_messages.user = module_user_users.user
                WHERE
                    module_messages_deleted.msgdel IS NULL
                    $usr_check
                ORDER BY
                    module_messages_status.msgread ASC,
                    module_messages.entrydate DESC
                LIMIT 5";

            $sq->query($this->dbc, $sql);
            $nr = 0;
            while ($data = $sq->nextrow()) {
                if ($data["msgread"] == 0) {
                    $tpl->addDataItem("ARTICLE.DATE", $data["date"]);
                    $tpl->addDataItem("ARTICLE.TITLE", $data["title"]);
                    $tpl->addDataItem("ARTICLE.NR", $nr);
                    $tpl->addDataItem("ARTICLE.USERNAME", $data["fullname"] ? $data["fullname"] : $data["username"]);
                    $tex = ereg_replace("\n.?\n", "|xy|", $data["lead"]);
                    $regs = split("\|xy\|", $tex, 2);
                    $regs[0] = ereg_replace("\n", "", trim($regs[0]));
                    $tpl->addDataItem("ARTICLE.CONTENT", ereg_replace("\'", "-", $regs[0]));
                    $tpl->addDataItem("ARTICLE.URL", $general_url . "&articleid=" . $data["id"]);
                    $nr++;
                }
            }
            $sq->free();

        } // #################################

        else if ($this->mode == "full" && !$this->articleid) {
            $start = $this->vars["start"];
            if (!$start) {
                $start = 0;
            }

            $bd = $this->vars["filter_begdate_d"] + 0;
            $bm = $this->vars["filter_begdate_m"] + 0;
            $by = $this->vars["filter_begdate_y"] + 0;
            $ed = $this->vars["filter_enddate_d"] + 0;
            $em = $this->vars["filter_enddate_m"] + 0;
            $ey = $this->vars["filter_enddate_y"] + 0;
            $keyword = addslashes(trim($this->vars["filter_keyword"]));
            $filter = '';
            $filter_url = "";

            if ($bd && $bm && $by) {
                $begdate = date("Y-m-d", mktime(0, 0, 0, $bm, $bd, $by));
                $filter .= " AND module_messages.entrydate >= '$begdate'";
                $filter_url .= "&filter_begdate_d=" . $bd . "&filter_begdate_m=" . $bm . "&filter_begdate_y=" . $by;
            }
            if ($ed && $em && $ey) {
                $enddate = date("Y-m-d", mktime(0, 0, 0, $em, $ed, $ey));
                $filter .= " AND module_messages.entrydate <= '$enddate'";
                $filter_url .= "&filter_enddate_d=" . $ed . "&filter_enddate_m=" . $em . "&filter_enddate_y=" . $ey;
            }
            if ($keyword) {
                $filter .= " AND (module_messages.title LIKE '%" . $keyword . "%'";
                $filter .= " OR module_messages.lead LIKE '%" . $keyword . "%'";
                $filter .= " OR module_messages.content LIKE '%" . $keyword . "%')";
                $filter_url .= "&filter_keyword=" . $keyword;
            }

            $sql = "SELECT
                    module_messages.id,
                    date_format(module_messages.entrydate, '%d.%m.%Y') as date,
                    module_messages.title,
                    module_messages.lead,
                    module_messages.user,
                    module_user_users.username,
                    CONCAT(module_user_users.name_first, ' ', module_user_users.name_last) AS fullname
                FROM
                    module_messages
                    LEFT JOIN module_user_users ON
                        module_messages.user = module_user_users.user
                    LEFT JOIN module_messages_deleted ON
                        module_messages.id = module_messages_deleted.msg_id AND
                        module_messages_deleted.user = '" . $this->userid . "'
                WHERE
                    module_messages_deleted.msgdel IS NULL
                    $filter
                    $usr_check
                ORDER BY
                    entrydate DESC
                LIMIT $start," . $this->maxresults
            ;

            $sq->query($this->dbc, $sql);

            while ($data = $sq->nextrow()) {
                $tpl->addDataItem("ARTICLE.DATE", $data["date"]);
                $tpl->addDataItem("ARTICLE.TITLE", $data["title"]);
                $tpl->addDataItem("ARTICLE.CONTENT", $data["lead"]);
                $tpl->addDataItem("ARTICLE.USERNAME", $data["fullname"] ? $data["fullname"] : $data["username"]);
                $tpl->addDataItem("ARTICLE.URL", $general_url . "&articleid=" . $data["id"]);
                $tpl->addDataItem("ARTICLE.URL_DELETE", "javascript:del('" . $general_url . "&articleid=" . $data["id"] . "&action=delete" . "');");

                if ($data["user"] == $this->userid /*&& $this->user_type == 1*/) {
                    $tpl->addDataItem("ARTICLE.MOD.URL_MODIFY", $general_url . "&articleid=" . $data["id"] . "&action=modify");
                }
            }
            $sq->free();

            // page listing
            $sql = "
                SELECT
                    count(module_messages.id) as totalus
                FROM
                    module_messages
                    LEFT JOIN module_user_users ON
                        module_messages.user = module_user_users.user
                    LEFT JOIN module_messages_deleted ON
                        module_messages.id = module_messages_deleted.msg_id AND
                        module_messages_deleted.user = '" . $this->userid . "'
                WHERE
                    module_messages_deleted.msgdel IS NULL
                    $filter
                    $usr_check"
            ;
            $sq->query($this->dbc, $sql);
            $data = $sq->nextrow();
            $total = $data["totalus"];
            $sq->free();
            $disp = str_replace("{NR}", $total, $txt->display("results"));
            if ($total >= $this->maxresults) {
                $end = $start + $this->maxresults;
            } else {
                $end = $start + $total;
            }
            if ($end == 0) {
                $start0 = 0;
            }
            else {
                $start0 = $start + 1;
            }
            $disp = str_replace("{DISP}", $start0 . "-$end", $disp);

            $tpl->addDataItem("PAGES", resultPages($start, $total, $general_url . $filter_url, $this->maxresults,
                $txt->display("prev"), $txt->display("next")));
            $tpl->addDataItem("RESULTS", $disp);

            //////////////////
            //    Filter    //
            //////////////////

            $sql = "
                SELECT
                    MIN(YEAR(module_messages.entrydate)) AS min_year,
                    MAX(YEAR(module_messages.entrydate)) AS max_year
                FROM
                    module_messages";
            $sq->query($this->dbc, $sql);
            if ($data = $sq->nextrow()) {
                $min_year = $data["min_year"];
                $max_year = $data["max_year"];
            }

            if ($this->isicDbUser->isCurrentUserAdmin()) {
                $tpl->addDataItem("ADD.URL_ADD", ($general_url . "&action=add"));
            }

            // Search-form

            $fields = array(
                "begdate_d" => array("select", 0, 0, $this->vars["filter_begdate_d"]),
                "begdate_m" => array("select", 0, 0, $this->vars["filter_begdate_m"]),
                "begdate_y" => array("select", 0, 0, $this->vars["filter_begdate_y"]),
                "enddate_d" => array("select", 0, 0, $this->vars["filter_enddate_d"]),
                "enddate_m" => array("select", 0, 0, $this->vars["filter_enddate_m"]),
                "enddate_y" => array("select", 0, 0, $this->vars["filter_enddate_y"]),
                "keyword" => array("textinput", 40, 0, $this->vars["filter_keyword"])
            );

            $list = array();
            $list["00"] = "-";
            for ($u = 1; $u < 32; $u++) {
                $list[substr("0" . $u, -2)] = $u;
            }
            $fields["begdate_d"][4] = $list;

            $list = array();
            $list["00"] = "-";
            for ($u = 1; $u < 13; $u++) {
                $list[substr("0" . $u, -2)] = $txto->display("month_" . $u);
            }
            $fields["begdate_m"][4] = $list;

            $list = array();
            $list["0000"] = "-";
            for ($u = $min_year; $u <= $max_year; $u++) {
                $list[$u] = $u;
            }
            $fields["begdate_y"][4] = $list;

            $list = array();
            $list["00"] = "-";
            for ($u = 1; $u < 32; $u++) {
                $list[substr("0" . $u, -2)] = $u;
            }
            $fields["enddate_d"][4] = $list;

            $list = array();
            $list["00"] = "-";
            for ($u = 1; $u < 13; $u++) {
                $list[substr("0" . $u, -2)] = $txto->display("month_" . $u);
            }
            $fields["enddate_m"][4] = $list;

            $list = array();
            $list["0000"] = "-";
            for ($u = $min_year; $u <= $max_year; $u++) {
                $list[$u] = $u;
            }
            $fields["enddate_y"][4] = $list;

            while (list($key, $val) = each($fields)) {
                $fdata["type"] = $val[0];
                $fdata["size"] = $val[1];
                $fdata["cols"] = $val[1];
                $fdata["rows"] = $val[2];
                $fdata["list"] = $val[4];
                $fdata["java"] = $val[5];

                $f = new AdminFields("filter_$key", $fdata);
                $field_data = $f->display($val[3]);
                $tpl->addDataItem("FIELD_filter_$key", $field_data);
                unset($fdata);
            }
        } // #################################

        else if ($this->mode == "full" && $this->articleid) {

            $sq->query($this->dbc, "
                SELECT
                    module_messages.id,
                    date_format(module_messages.entrydate, '%d.%m.%Y') as date,
                    module_messages.pic,
                    module_messages.title,
                    module_messages.content,
                    module_messages.lead,
                    module_user_users.username,
                    CONCAT(module_user_users.name_first, ' ', module_user_users.name_last) AS fullname
                FROM
                    module_messages
                    LEFT JOIN module_user_users ON
                        module_messages.user = module_user_users.user
                    LEFT JOIN module_messages_deleted ON
                        module_messages.id = module_messages_deleted.msg_id AND
                        module_messages_deleted.user = '" . $this->userid . "'
                WHERE
                    module_messages_deleted.msgdel IS NULL AND
                    module_messages.id = '" . $this->articleid . "'
                    $usr_check"
            );
            $data = $sq->nextrow();
            $tpl->addDataItem("ARTICLE.DATE", $data["date"]);
            $tpl->addDataItem("ARTICLE.TITLE", $data["title"]);
            $tpl->addDataItem("ARTICLE.USERNAME", $data["fullname"] ? $data["fullname"] : $data["username"]);
            $tpl->addDataItem("ARTICLE.LEAD", $data["lead"]);
            $data["content"] = str_replace("$", "&#" . ord("$") . ";", stripslashes($data["content"]));
            if (eregi("<.*>", $data["content"])) {
                $tpl->addDataItem("ARTICLE.CONTENT", $data["content"]);
            } else {
                $tpl->addDataItem("ARTICLE.CONTENT", ereg_replace("\n", "<br>", $data["content"]));
            }
            $tpl->addDataItem("ARTICLE.BACKURL", $general_url);
            $tpl->addDataItem("ARTICLE.BACK", $txt->display("back"));
            $sq->free();

        }

        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $txt->display($this->info_message[$this->vars["info"]]));
        }

        return $tpl->parse();
    }


    private function getMessageById($id) {
        $sql = 'SELECT * FROM `module_messages` WHERE `id` = ?';
        $res = $this->db->query($sql, $id);
        if (!$res) {
            return false;
        }
        $data = $res->fetch_assoc();
        $data["text"] = str_replace(array("</p>", "<br>"), array("\n\n", "\n"), $data["content"]);
        $data["text"] = strip_tags($data["text"]);
        $data["send_type"] = self::SEND_TYPE_DB;
        return $data;
    }

    private function isMessageSendingAllowed() {
        if (!$this->isicDbUser->isCurrentUserAdmin()) {
            return false;
        }

        if (!(is_array($this->allowed_groups) && $this->allowed_groups[0] != null)) {
            return false;
        }
        return true;
    }

    /**
     * @param $messageId
     * @param $action
     * @return bool|mixed
     */
    public function addMessage($messageId, $action)
    {
        if (!$this->isMessageSendingAllowed()) {
            return $this->showErrorMessage("error_can_not_send_message");
        }

        if (!$this->userHasSMSCredit()) {
//            return $this->showErrorMessage("error_sms_credit");
        }

        if (!in_array($action, array('modify', 'add'))) {
            return $this->showErrorMessage("error_unknown_action");
        }

        if ($messageId && !$this->isAllowedMessage($messageId)) {
            return $this->showErrorMessage("error_message_not_allowed");
        }

        $this->initSendType();
        $this->initRecipientType();

        $step = $this->getCurrentStep();
        if ($this->isWrite()) {
            switch ($step) {
                case self::STEP_PARAMETERS:
                    $saveResult = $this->confirmMessage();
                    if (!$saveResult['error']) {
                        $step++;
                    }
                    break;
                case self::STEP_CONFIRM:
                    $saveResult = $this->saveAndSendMessage($messageId, $action);
                    if (!$saveResult['error']) {
                        clearCacheFiles("tpl_messages", "");
                        redirect(processUrl(SITE_URL, $_SERVER['QUERY_STRING'],
                            "nocache=true&info=" . $saveResult['info'], array("articleid", "action")));
                    }
                    break;
                default:
                    break;
            }
        }

        return $this->createForm($messageId, $action, $saveResult, $step);
    }

    private function isWrite() {
        if (isset($this->vars['write'])) {
            return true;
        }
        return false;
    }

    private function isAllowedMessage($id) {
        $messageData = $this->getMessageById($id);
        return ($messageData && $messageData["user"] == $this->userid);
    }

    private function getUserListFromPersonNumbers($param) {
        $userIds = array();
        $userList = array();
        foreach ($param['person_numbers'] as $personNumber) {
            $userData = $this->isicDbUser->getRecordByCode($personNumber);
            if (!$userData) {
                $userList[] = array('id' => 0, 'name' => $personNumber);
                continue;
            }
            if (!$this->isicDbUser->isValidContact($param['send_type'], $userData)) {
                continue;
            }
            if (in_array($userData['user'], $userIds)) {
                continue;
            }
            $userIds[] = $userData['user'];
            $userList[] = $this->isicDbUser->getShortUserData($userData);
        }
        return array($userIds, $userList);
    }

    private function getUserListFromGroupAndClient($params) {
        if (!is_array($params['groups']) && !is_array($params['clients'])) {
            return array();
        }
        list($userIds, $userList) = $this->isicDbUser->getRecordsByGroupsWithFilter($params['groups'], $params['send_type'], $params['faculty']);
        $newUserIds = array();
        $newUserList = array();
        $clients = $params['clients'];
        for ($i = 0; $i < count($userIds); $i++) {
            if (!in_array($userIds[$i], $clients)) {
                continue;
            }
            $newUserIds[] = $userIds[$i];
            $newUserList[] = $userList[$i];
        }
        return array($newUserIds, $newUserList);
    }

    private function sendEmail($userData) {
        $text = str_replace("\n", "<br/>\n", trim($this->vars["text"]));
        $sender = $this->user_name . " <" . $this->user_email . ">";

        foreach ($userData as $userRecord) {
            $this->messageEmail(array($userRecord['email'] . " <" . $userRecord['email'] . ">"), $sender, $this->vars["title"], $text);
        }
    }

    private function getUserEmailData($userList) {
        $userData = array();
        foreach ($userList as $user) {
            $userData[$user['id']]['email'] = $user['email'];
            $userData[$user['id']]['name'] = $user['name'];
        }
        return $userData;
    }

    private function sendSMS($userList, $schoolId) {
        $smsSendQueue = new SMSSendQueue($this->db);
        foreach ($userList as $user) {
            $message = array(
                'from' => $this->smsFrom,
                'to' => MobileNumberValidator::convertNumber($user['phone']),
                'text' => $this->vars['text'],
                'user_id' => $this->userid,
                'school_id' => $schoolId
            );
            $smsSendQueue->addToQueue($message);
        }
        return true;
    }

    private function reserveSMSCredit($smsCount, $schoolId) {
        $smsSum = $this->smsPrice * $smsCount;

        if ($smsSum > $this->getSMSCredit($schoolId)) {
            return false;
        }
        $this->isicSchoolSMSCredit->reserveCredit($schoolId, $smsSum);
        return true;
    }

    private function userHasSMSCredit() {
        $credit = 0;
        foreach ($this->isicUser->getAllowedSchools() as $schoolId) {
            $credit += $this->getSMSCredit($schoolId);
        }
        return $credit > 0;
    }

    /**
     * @return array
     */
    private function getSMSCredit($schoolId) {
        return $this->isicSchoolSMSCredit->getCredit($schoolId);
    }

    private function getUserMobileData($userList) {
        $userData = array();
        foreach ($userList as $user) {
            $userData[$user['id']]['phone'] = $user['phone'];
            $userData[$user['id']]['name'] = $user['name'];
        }

        return $userData;
    }

    // ########################################
    /** Delete message
     */

    function deleteMessage($messageId) {
        if ($this->isAllowedMessage($messageId)) {
            $this->db->query("DELETE FROM module_messages WHERE id = ?", $messageId);
            $this->db->query("DELETE FROM module_messages_status WHERE msg_id = ?", $messageId);
            $this->db->query("DELETE FROM module_messages_deleted WHERE msg_id = ?", $messageId);
            clearCacheFiles("tpl_messages", "");
        } else {
            $this->db->query("INSERT INTO module_messages_deleted (msg_id, user, msgdel) VALUES (?, ?, 1)", $messageId, $this->userid);
        }
        redirect(processUrl(SITE_URL, $_SERVER['QUERY_STRING'], "info=delete", array("articleid", "action")));
    }

// ########################################

    function create_recipients_list($i_group, $i_user)
    {
        $sq = new sql;

        $txt = new Text($this->language, "module_messages");

        $recipients = $txt->display("message_was_sent") . " ";

        $u_list = array();
        $g_list = array();
        $g_user = array();
        $g_flag = false;

        if ($i_user == "0") {
            $recipients .= $txt->display("all_users");
        } else {
            $groups = split(",", $i_group);
            $users = split(",", $i_user);

            $sql = "SELECT * FROM module_user_groups ORDER BY name";
            $sq->query($this->dbc, $sql);
            while ($data = $sq->nextrow()) {
                $g_list[$data["id"]] = str_replace("Arco Real, ", "", $data["name"]);
            }

            $sql = "SELECT user, CONCAT(name_first, ' ', name_last) AS name, SUBSTRING_INDEX(ggroup, ',', 1) AS ggroup FROM module_user_users WHERE active = 1 ORDER BY name";
            $sq->query($this->dbc, $sql);
            while ($data = $sq->nextrow()) {
                $u_list[$data["user"]] = $data["name"];
                $g_user[$data["ggroup"]]++;
            }

            if ($i_group != "0") {
                // check if all of the members of the selected groups were also selected, to make sure if user did not select individual users instead
                // this is important for us to know because we have to display group-list or user-list respectively
                $user_count = 0;
                for ($i = 0; $i < sizeof($groups); $i++) {
                    $user_count += $g_user[$groups[$i]];
                }
                if ($user_count == sizeof($users)) {
                    $g_flag = true;
                }
            }

            if ($g_flag) {
                for ($i = 0; $i < sizeof($groups); $i++) {
                    $recipients .= $i ? "; " : "";
                    $recipients .= $g_list[$groups[$i]];
                }
            } else {
                for ($i = 0; $i < sizeof($users); $i++) {
                    $recipients .= $i ? "; " : "";
                    $recipients .= $u_list[$users[$i]];
                }
            }
        }
        return $recipients;
    }

// ########################################

    function messageEmail($send_to, $sender, $subject, $text)
    {
        $txt = new Text($this->language, "module_messages");
        if (!$sender) $sender = $this->info_email;
        if (!$subject) $subject = "";
        if (!$text) return false;

        $subject = $txt->display("message_from_intranet") . ": " . $subject;

        if (strpos(SITE_URL, 'dev.modera.net') !== false) {
            $subject .= " (dev redirect per " . current($send_to) . ")";
            if (defined('TESTERS_EMAILS')) {
                $send_to = explode(",", TESTERS_EMAILS);
            } else {
                return false;
            }
        }
        if (!is_array($send_to)) {
            return false;
        }

        $mail = new htmlMimeMail();
        $mail->setHtml($text, returnPlainText($text));
        $mail->setFrom($sender);
        $mail->setSubject($subject);
        return $mail->send($send_to);
    }

    // #####################
    // global site search interface
    function global_site_search($search, $beg_date = "", $end_date = "")
    {
        $sq = new sql;
        $txt = new Text($this->language, "module_messages");

        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 130 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        if ($data = $sq->nextrow()) {
            $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"] . "&content=" . $data["content"];
        } else {
            return false; // no content-record found, will not continue
        }

        // creating array for search result
        $result = array(
            "title" => $txt->display("module_title"), // module title
            "fields" => array("entrydate", "title", "lead", "author"), // array of fields with according titles
            "values" => array() // array of values will be stored here
        );

        if ($this->userid) {
            /*
            if ($beg_date) {
                $bd = date("d", strtotime($beg_date));
                $bm = date("m", strtotime($beg_date));
                $by = date("Y");
                $beg_date = date("Y-m-d", mktime(0, 0, 0, $bm, $bd, $by));

                $date_filter .= " AND module_messages.entrydate >= '" . $beg_date . "'";
            }
            if ($end_date) {
                $ed = date("d", strtotime($end_date));
                $em = date("m", strtotime($end_date));
                $ey = date("Y");
                $end_date = date("Y-m-d 23:59:59", mktime(0, 0, 0, $em, $ed, $ey));

                $date_filter .= " AND module_messages.entrydate <= '" . $end_date . "'";
            }
            */

            $usr_check = " AND ((module_messages.client LIKE '" . $this->userid . ",%' OR module_messages.client LIKE '%," . $this->userid . ",%' OR module_messages.client LIKE '%," . $this->userid . "' OR module_messages.client = '' OR module_messages.client = '" . $this->userid . "' OR module_messages.client = 0) OR module_messages.user = " . $this->userid . ")";

            $filter = " AND (LOWER(module_messages.title) LIKE LOWER('%" . $search . "%')";
            $filter .= " OR LOWER(module_messages.lead) LIKE LOWER('%" . $search . "%')";
            $filter .= " OR LOWER(module_messages.content) LIKE LOWER('%" . $search . "%'))";

            $sql = "SELECT module_messages.id, module_messages.entrydate, module_messages.title, module_messages.lead, module_messages.user, module_user_users.username, CONCAT(module_user_users.name_first, ' ', module_user_users.name_last) AS author FROM module_messages LEFT JOIN module_user_users ON module_messages.user = module_user_users.user LEFT JOIN module_messages_deleted ON module_messages.id = module_messages_deleted.msg_id AND module_messages_deleted.user = '" . $this->userid . "' WHERE module_messages_deleted.msgdel IS NULL $filter $usr_check ORDER BY entrydate DESC";

            $sq->query($this->dbc, $sql);

            $row = 0;
            while ($data = $sq->nextrow()) {
                $data["entrydate"] = date("d.m.Y", strtotime($data["entrydate"]));
                $result["values"][$row]["url"] = $general_url . "&articleid=" . $data["id"];
                foreach ($result["fields"] as $key) {
                    $result["values"][$row][$key] = $data[$key];
                }
                $row++;
            }
        }

        $sq->free();

        return $result;
    }

    /**
     * Creates an array of all the groups that current user should be able to see
     * group list is created by the school
     *
     * @return array list of school id's
     */

    function createAllowedGroups()
    {
        return $this->usergroups;
        $group_list = array();
        $school_list = array();

        // 16/12/2008, Martin: no more E�L group should be shown
//        $group_list[] = 1; // E�L is seen for everyone

        if (is_array($this->usergroups) && $this->usergroups[0] != null) {
            // regular users can olny send messages to administrators
            if ($this->user_type == 2) {
                $sql_user = " AND `module_user_groups`.`group_type` = 1";
            } else {
                $sql_user = "";
            }

            for ($i = 0; $i < sizeof($this->usergroups); $i++) {
                $r = & $this->db->query('SELECT `module_user_groups`.* FROM `module_user_groups` WHERE `id` = !', $this->usergroups[$i]);
                if ($data = $r->fetch_assoc()) {
                    $school_list[] = $data["isic_school"];
                }
            }

            for ($i = 0; $i < sizeof($school_list); $i++) {
                $r = & $this->db->query('SELECT `module_user_groups`.* FROM `module_user_groups` WHERE `id` > 1 AND `isic_school` = !' . $sql_user, $school_list[$i]);
                while ($data = $r->fetch_assoc()) {
                    $group_list[] = $data["id"];
                }
            }
        }

        return $group_list;
    }

    /**
     * Displays error message for user
     *
     * @param string $message message to show
     * @return boolean
     */

    function showErrorMessage($message)
    {
        $txt = new Text($this->language, "module_messages");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_messages_error.html";

        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=messages&type=error");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "isic";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module messages cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }
        }

        $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display($message));

        return $tpl->parse();
    }

// ########################################

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

    // #####################
    // functions for content management

    function getParameters()
    {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    function moduleOptions()
    {
        $sq = new sql;
        $list = array();
        $list[0] = "---";
        $txt = new Text($this->language, "module_messages");
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

    /**
     * @param $sq
     */
    private function setMessageAsRead($sq)
    {
        if ($this->mode == "full" && $this->articleid) {
            $sq->query($this->dbc, "SELECT id, msgread FROM module_messages_status WHERE msg_id = '" . $this->articleid . "' AND user = " . $this->userid . "");
            if ($sq->numrows > 0) {
                $msg_status_id = $sq->column(0, "id");
                $msg_read = $sq->column(0, "msgread");
                if ($msg_read == 0) {
                    $res = $sq->query($this->dbc, "UPDATE module_messages_status SET msgread = 1 WHERE id = '" . $msg_status_id . "'");
                    clearCacheFiles("tpl_messages_info_usr" . $this->userid, "tpl_messages_short_usr" . $this->userid);
                }
            } else {
                $res = $sq->query($this->dbc, "INSERT INTO module_messages_status (msg_id, user, msgread) VALUES ('" . $this->articleid . "', " . $this->userid . ", 1)");
                clearCacheFiles("tpl_messages_info_usr" . $this->userid, "tpl_messages_short_usr" . $this->userid);
            }
        }
    }

    /**
     * @param $sq
     * @return array
     */
    private function getGeneralUrl($sq)
    {
        $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 130 AND language = '" . addslashes($this->language) . "' LIMIT 1");
        if ($sq->numrows != 0) {
            $data = $sq->nextrow();
            $general_url = $_SERVER['PHP_SELF'] . "?content=" . $data["content"];
        } else {
            $general_url = "#PLEASE_CREATE_MESSAGE_PAGE";
        }
        $sq->free();
        return $general_url;
    }

    /**
     * @return array
     */
    private function getGroupList()
    {
        $glist = array();
//        if ($this->isicDbUser->isCurrentUserSuperAdmin()) {
//            $glist["0"] = $txt->display("all_groups");
//        }

        $sql = '
            SELECT
                g.id,
                g.name
            FROM
                module_user_groups AS g
            WHERE
                g.has_users = 1 AND
                g.id IN (?@)
            ORDER BY
                g.name
        ';
        $res = $this->db->query($sql, $this->allowed_groups);

        while ($data = $res->fetch_assoc()) {
            $glist[$data['id']] = $data['name'];
        }
        return $glist;
    }

    private function getClientList($clients) {
        if (!is_array($clients) || count($clients) < 1) {
            return array();
        }
        $sql = '
            SELECT
                u.user,
                u.name_first,
                u.name_last
            FROM
                module_user_users AS u
            WHERE
                u.active = 1 AND
                u.user IN (?@)
            ORDER BY
                u.name_last,
                u.name_first
        ';
        $res = $this->db->query($sql, $clients);
        if (!$res) {
            return array();
        }
        $clientList = array();
        while ($data = $res->fetch_assoc()) {
            $clientList[$data['user']] = $data['name_first'] . ' ' . $data['name_last'];
        }
        return $clientList;
    }

    /**
     * @param $required
     * @param $bad
     * @return bool
     */
    private function isRequiredSet($sendType, $recipientType)
    {
        $requiredSet = true;
        $bad = array();
        foreach ($this->requiredFields[$sendType] as $required) {
            if ($this->vars[$required] == '') {
                $bad[] = $required;
                $requiredSet = false;
            }
        }
        foreach ($this->requiredRecipients[$recipientType] as $required) {
            if ($this->vars[$required] == '') {
                $bad[] = $required;
                $requiredSet = false;
            }
        }

        return $requiredSet;
    }

    /**
     * @param $action
     * @return template
     */
    private function initAddTemplate($action, $templateName = 'module_messages_add.html')
    {
        $tpl = new template;
        $tpl->tplfile = $this->tplfile;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . $templateName;
        $tpl->setInstance($_SERVER['PHP_SELF'] . "?language=" . $this->language . "&module=messages&action=" . $action . "&usergroup=" . $this->usergroup);
        $tpl->setTemplateFile($template);
        return $tpl;
    }

    /**
     * @param $txt
     * @param $messageData
     * @return array
     */
    private function initConfirmFields($txt)
    {
        $fields = array(
            "way_of_sending" => $txt->display("send_type" . $this->vars["send_type"]),
            "school_sms_credit" => $this->vars['send_type'] == self::SEND_TYPE_SMS ? $this->getSchoolName($this->vars["school_id"]) : '',
            "title" => $this->vars["title"],
            "content" => $this->vars["text"],
            "faculty" => $this->vars["faculty"],
        );
        return $fields;
    }

    private function getSchoolName($schoolId) {
        if ($schoolId) {
            $schoolData = $this->isicDbSchools->getRecord($schoolId);
        } else {
            $schoolData = array('id' => $schoolId, 'name' => '');
        }
        return $this->getSchoolNameWithCredit($schoolData);
    }

    public function getSchoolNameWithCredit($data) {
        if (!array_key_exists('credit', $data)) {
            $data['credit'] = $this->isicSchoolSMSCredit->getCredit($data['id']);
        }
        return $data['name'] . ' (' . round($data['credit'] / $this->smsPrice, 0) . ' ' . $this->txt->display('sms_unit') . ')';
    }

    /**
     * @param $txt
     * @param $messageData
     * @return array
     */
    private function initAddModFields($txt, $messageData)
    {
        $tClients = $this->vars['client'] ? $this->vars['client'] : explode(',', $messageData['client']);
        $fields = array(
            "send_type" => array("radio", 0, 0, $this->vars["send_type"], $this->getSendTypes($txt), 'onClick="refreshFields();"', "form-element-width"),
            "school_id" => array("select", 0, 0, $this->vars["school_id"], $this->getSchoolList(), "", "form-element-width"),
            "title" => array("textinput", 60, 0, $this->vars["title"], "", "", "form-element-width"),
            "text" => array("textfield", 60, 5, $this->vars["text"], "", "", "form-element-width"),
            "recipient_type" => array("radio", 0, 0, $this->vars["recipient_type"], $this->getRecipientTypes($txt), 'onClick="refreshRecipientFields();"', ""),
            "faculty" => array("textinput", 60, 0, $this->vars["faculty"], "", 'onblur="refreshClientList();"', "form-element-width"),
            "group" => array("select2", 10, 0, $this->vars["group"], $this->getGroupList(), "onChange=\"refreshClientList();\"", "form-element-width"),
            "client" => array("select2", 10, 0, $this->vars["client"], $this->getClientList($tClients), "", "form-element-width"),
            "person_numbers" => array("textfield", 20, 20, $this->vars["person_numbers"], '', "", "form-element-width"),
        );
        return $fields;
    }

    private function getSchoolList() {
        $list = array();

        if ($this->isicDbUser->isCurrentUserSuperAdmin()) {
            $r = &$this->db->query('
            SELECT
                0 AS `id`,
                ? AS `name`,
                `c`.`credit`
            FROM
                `module_isic_school_sms_credit` AS `c`
            WHERE
                `c`.`school_id` = 0
            ', '');
        } else {
            $r = &$this->db->query('
            SELECT
                `s`.`id`,
                `s`.`name`,
                `s`.`ehl_code`,
                `c`.`credit`
            FROM
                `module_isic_school` AS `s`,
                `module_isic_school_sms_credit` AS `c`
            WHERE
                `s`.`id` = `c`.`school_id` AND
                `s`.`id` IN (!@)
            ORDER BY
                `s`.`name`
            ', IsicDB::getIdsAsArray($this->isicUser->getAllowedSchools()));
        }
$this->logger->addDebug($this->db->show_query(), 'SQL');
        while ($data = $r->fetch_assoc()) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $list[$data['id']] = $this->getSchoolNameWithCredit($data);
        }
        return $list;
    }

    /**
     * @param $txt
     * @return array
     */
    private function getSendTypes($txt)
    {
        $list = array();
//        $list["1"] = $txt->display("send_type1");
//        if ($this->user_email) {
//            $list["2"] = $txt->display("send_type2");
//        }
//        if ($this->userHasSMSCredit()) {
            $list["3"] = $txt->display("send_type3");
//        }
        return $list;
    }

    private function getRecipientTypes($txt) {
        return array(
            self::RECIPIENT_TYPE_GROUP => $txt->display('recipient_type1'),
            //self::RECIPIENT_TYPE_PERSON_NUMBER => $txt->display('recipient_type2')
        );
    }

    private function createForm($messageId, $action, $error, $step = self::STEP_PARAMETERS) {
        if ($step == self::STEP_PARAMETERS) {
            return $this->createAddModForm($messageId, $action, $error);
        } else if ($step == self::STEP_CONFIRM) {
            return $this->createConfirmForm($messageId, $action, $error);
        }
        return '';
    }

    private function createConfirmForm($messageId, $action, $error) {
        $txt = new Text($this->language, "module_messages");
        $tpl = $this->initAddTemplate($action, 'module_messages_confirm.html');
        if ($error['error']) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_" . $error['type']));
        }

        $fields = $this->initConfirmFields($txt);
        foreach ($fields as $key => $val) {
            if (!trim($val)) {
                continue;
            }
            $tpl->addDataItem('FIELD.TITLE', $txt->display($key));
            $tpl->addDataItem('FIELD.DATA', $val);
        }

        $clients = @$this->vars['clients'];
        $confirm = @$this->vars['confirm'];
        $userIds = array();
        foreach ($this->userList as $userData) {
            $tpl->addDataItem('RECIPIENT.NAME', $userData['name']);
            if ($userData['id']) {
                $f = new AdminFields("clients[" . $userData["id"] . "]", array("type" => "checkbox"));
                $fieldData = $f->display($confirm ? $clients[$userData["id"]] : 1);
                $tpl->addDataItem('RECIPIENT.CHECKED', $fieldData);
                $userIds[] = $userData['id'];
            }
        }

        $tpl->addDataItem("BUTTON", $txt->display("button_confirm"));
        $tpl->addDataItem("HIDDEN", $this->getHiddenFieldsConfirm($messageId, $action, self::STEP_CONFIRM, $userIds));
        $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content={$this->vars["content"]}");
        return $tpl->parse();
    }

    /**
     * @param $messageId
     * @param $action
     * @param $error
     * @param $messageData
     * @return mixed
     */
    private function createAddModForm($messageId, $action, $error)
    {
        $txt = new Text($this->language, "module_messages");
        $tpl = $this->initAddTemplate($action, 'module_messages_add.html');
        if ($error['error']) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("modify_error_" . $error['type']));
        }
        if ($this->vars["info"] && $this->info_message[$this->vars["info"]]) {
            $tpl->addDataItem("IMESSAGE.IMESSAGE", $txt->display($this->info_message[$this->vars["info"]]));
        }

        $messageData = $this->getMessageById($messageId);
        $fields = $this->initAddModFields($txt, $messageData);
        foreach ($fields as $key => $val) {
            $fieldData = array();
            $fieldData["type"] = $val[0];
            $fieldData["size"] = $val[1];
            $fieldData["cols"] = $val[1];
            $fieldData["rows"] = $val[2];
            $fieldData["list"] = $val[4];
            $fieldData["java"] = $val[5];
            $fieldData["class"] = $val[6];

            if ($action == "modify" && !$error['error']) {
                $val[3] = $messageData[$key];
            }
            $f = new AdminFields($key, $fieldData);
            $field_data = $f->display($val[3]);
            if ($key == 'group' || $key == 'client') {
                $field_data = str_replace("<select", "<select id=\"{$key}\"", $field_data);
            }
            $tpl->addDataItem('FIELD_' . $key, $field_data);
        }

        $tpl->addDataItem("BUTTON", $txt->display("button_" . ($action == 'add' ? 'add' : 'mod')));
        $tpl->addDataItem("HIDDEN", $this->getHiddenFields($messageId, $action, self::STEP_PARAMETERS));
        $content = @$this->vars["content"];
        $tpl->addDataItem("SELF", $_SERVER['PHP_SELF'] . "?content={$content}");
        return $tpl->parse();
    }

    private function getHiddenFieldsConfirm($articleId, $action, $step, $userIds) {
        return
            IsicForm::getHiddenField('send_type', $this->vars['send_type']) .
            IsicForm::getHiddenField('school_id', $this->vars['school_id']) .
            IsicForm::getHiddenField('title', $this->vars['title']) .
            IsicForm::getHiddenField('text', $this->vars['text']) .
            IsicForm::getHiddenField('faculty', $this->vars['faculty']) .
            IsicForm::getHiddenField('user_id_list', $this->convertArrayToList($userIds)) .
            IsicForm::getHiddenField('client', $this->convertArrayToList($this->vars['client'])) .
            IsicForm::getHiddenField('group', $this->convertArrayToList($this->vars['group'])) .
            IsicForm::getHiddenField('step', $step) .
            IsicForm::getHiddenField('action', $action) .
            IsicForm::getHiddenField('write', 'true') .
            IsicForm::getHiddenField('articleid', $articleId) .
            IsicForm::getHiddenField('recipient_type', $this->vars['recipient_type']) .
            IsicForm::getHiddenField('person_numbers', $this->convertArrayToList($this->vars['person_numbers'])) .
            IsicForm::getHiddenField('confirm', 1)
        ;
    }

    private function convertArrayToList($data) {
        if (is_array($data)) {
            return implode(',', $data);
        }
        return $data;
    }

    private function convertListToArray($data) {
        if (is_array($data)) {
            return $data;
        }

        $separator = ' ';
        $separators = array(',', ';', "\n");
        foreach ($separators as $curSep) {
            if (strpos($data, $curSep) !== false) {
                $separator = $curSep;
                break;
            }
        }
        $arr = array();
        $elements = explode($separator, $data);
        for ($i = 0; $i < count($elements); $i++) {
            $val = trim($elements[$i]);
            if ($val === '') {
                continue;
            }
            $arr[] = $val;
        }
        return $arr;
    }


    /**
     * @param $articleId
     * @param $action
     * @return string
     */
    private function getHiddenFields($articleId, $action, $step) {
        return
            IsicForm::getHiddenField('action', $action) .
            IsicForm::getHiddenField('write', 'true') .
            IsicForm::getHiddenField('articleid', $articleId) .
            IsicForm::getHiddenField('step', $step)
        ;
    }

    private function initSendType() {
        if (!$this->vars["send_type"]) {
            $this->vars["send_type"] = self::SEND_TYPE_SMS;
        }
        return (int)$this->vars["send_type"];
    }

    private function initRecipientType() {
        if (!$this->vars["recipient_type"]) {
            $this->vars["recipient_type"] = self::RECIPIENT_TYPE_GROUP;
        }
        return (int)$this->vars["recipient_type"];
    }

    private function getCurrentStep() {
        if (!$this->vars["step"]) {
            $this->vars["step"] = self::STEP_PARAMETERS;
        }
        return (int)$this->vars["step"];
    }

    private function isValidSendType($sendType) {
        return in_array($sendType, $this->sendTypeList);
    }

    private function confirmMessage() {
        if (!$this->isValidSendType($this->vars['send_type']) ||
            !$this->isRequiredSet($this->vars['send_type'], $this->vars['recipient_type'])) {
            return $this->buildReturnMessage('required_fields');
        }

        if ($this->vars['recipient_type'] == self::RECIPIENT_TYPE_GROUP) {
            $params = array(
                'groups' => $this->vars['group'],
                'clients' => $this->vars['client'],
                'send_type' => $this->vars['send_type'],
                'faculty' => $this->vars['faculty']
            );
            list($userIds, $userList) = $this->getUserListFromGroupAndClient($params);
        } else {
            $params = array(
                'person_numbers' => $this->convertListToArray($this->vars['person_numbers']),
                'send_type' => $this->vars['send_type'],
            );
            list($userIds, $userList) = $this->getUserListFromPersonNumbers($params);
        }
        if (!$userIds || count($userIds) < 1) {
            return $this->buildReturnMessage('no_users');
        }

        switch ($this->vars['send_type']) {
            case self::SEND_TYPE_EMAIL:
                // if no e-mail set for current user, then no sending
                if (!$this->user_email) {
                    return $this->buildReturnMessage('email_sender');
                }
                break;
            case self::SEND_TYPE_SMS:
                if (!$this->isMessageSendingAllowed()) {
                    return $this->buildReturnMessage('mobile_users');
                }
                $schoolId = $this->assignSMSSchool();
                if ($schoolId === false) {
                    return $this->buildReturnMessage('sms_school');
                }
                break;
        }
        $this->userList = $userList;
        return $this->buildReturnMessage();
    }

    /**
     * @param $messageId
     * @param $action
     * @param $users
     */
    private function saveAndSendMessage($messageId, $action)
    {
        if (!$this->isValidSendType($this->vars['send_type']) ||
            !$this->isRequiredSet($this->vars['send_type'], $this->vars['recipient_type'])) {
            return $this->buildReturnMessage('required_fields');
        }

        $this->userList = $this->getUserListByIds($this->convertListToArray($this->vars['user_id_list']));

        list($userIds, $userList) = $this->assignUserIdsAndList();
        if (!$userIds || count($userIds) < 1) {
            return $this->buildReturnMessage('no_users');
        }

        $info = null;
        switch ($this->vars['send_type']) {
            case self::SEND_TYPE_DB:
                if ($action == 'modify') {
                    $this->update($messageId, $userIds, $this->vars['group']);
                    $info = 'modify';
                } else if ($action == 'add') {
                    $this->insert($userIds, $this->vars['group']);
                    $info = 'add';
                }
                break;
            case self::SEND_TYPE_EMAIL:
                // if no e-mail set for current user, then no sending
                if (!$this->user_email) {
                    return $this->buildReturnMessage('email_sender');
                }
                $userData = $this->getUserEmailData($userList);
                $this->sendEmail($userData);
                $info = 'sent';
                break;
            case self::SEND_TYPE_SMS:
                if (!$this->isMessageSendingAllowed()) {
                    return $this->buildReturnMessage('mobile_users');
                }
                $schoolId = $this->assignSMSSchool();
                if ($schoolId === false) {
                    return $this->buildReturnMessage('sms_school');
                }
                $userList = $this->getUserMobileData($userList);
                $smsCount = count($userList);
                if (!$this->reserveSMSCredit($smsCount, $schoolId)) {
                    return $this->buildReturnMessage('sms_credit');
                }
                $this->sendSMS($userList, $schoolId);
                $info = 'sent';
                break;
        }

        return $this->buildReturnMessage(null, $info);
    }

    private function getUserListByIds($ids) {
        $users = array();
        $userList = $this->isicDbUser->getRecordsByIds($ids);
        foreach ($userList as $user) {
            $users[] = $this->isicDbUser->getShortUserData($user);
        }
        return $users;
    }

    private function assignUserIdsAndList() {
        if (is_array($this->vars['clients'])) {
            $clients = array_keys($this->vars['clients']);
            return array($clients, $this->getUserListByIds($clients));
        }
        return array(array(), array());
    }

    private function assignSMSSchool() {
        if ($this->isicDbUser->isCurrentUserSuperAdmin()) {
            return 0;
        }
        if (!isset($this->vars['school_id'])) {
            return false;
        }
        if (!in_array($this->vars['school_id'], $this->isicUser->getAllowedSchools())) {
            return false;
        }
        return $this->vars['school_id'];
    }

    private function buildReturnMessage($type = null, $info = null) {
        return array('error' => $type !== null, 'type' => $type, 'info' => $info);
    }

    /**
     * @param $messageId
     * @param $users
     */
    private function update($messageId, $users, $groups)
    {
        $sql = "UPDATE ?f SET
                    `title` = ?,
                    `content` = ?,
                    `client` = ?,
                    `group` = ?
                WHERE
                    `id` = ?";
        $this->db->query($sql,
            self::TABLE_NAME,
            $this->vars["title"],
            $this->vars["text"],
            implode(',', $users),
            $this->convertArrayToList($groups),
            $messageId
        );

        $sql = "UPDATE module_messages_status SET msgread = 0 WHERE msg_id = ?";
        $this->db->query($sql, $messageId);
    }

    /**
     * @param $users
     */
    private function insert($users, $groups)
    {
        $sql = "INSERT INTO ?f (
                    `language`,
                    `entrydate`,
                    `title`,
                    `content`,
                    `client`,
                    `group`,
                    `user`
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )";
        $this->db->query($sql,
            self::TABLE_NAME,
            $this->language,
            $this->db->now(),
            $this->vars["title"],
            $this->vars["text"],
            implode(',', $users),
            $this->convertArrayToList($groups),
            $this->userid
        );
    }
}
