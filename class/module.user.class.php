<?php

/**
 * User module
 *
 * <code>
 *  <TPL_OBJECT:user>
 *      <TPL_OBJECT_OUTPUT:show()>
 *  </TPL_OBJECT:user>
 * </code>
 *
 * @package modera_net
 * @version 1.4
 * @access public
 */

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");

class user
{
    const AUTH_TYPE_IDCARD = 2;
    const AUTH_TYPE_SWED = 3;
    const AUTH_TYPE_SEB = 4;
    const AUTH_TYPE_DANSKE = 5;
    const AUTH_TYPE_NORDEA = 6;
    const AUTH_TYPE_KREDIIDI = 7;
    const AUTH_TYPE_LHV = 8;
    const MAX_AUTH_TYPE = self::AUTH_TYPE_IDCARD;

    var $authenticationTypeName = array(
        self::AUTH_TYPE_IDCARD => 'ID-card',
        self::AUTH_TYPE_SWED => 'Swedbank',
        self::AUTH_TYPE_SEB => 'SEB',
        self::AUTH_TYPE_DANSKE => 'Danske',
        self::AUTH_TYPE_NORDEA => 'Nordea',
        self::AUTH_TYPE_KREDIIDI => 'Krediidi',
        self::AUTH_TYPE_LHV => 'LHV'
    );

    const ALLOWED_AGE_IN_YEARS = 14;



    /**
     * @var string session id
     */
    var $sid = false;
    /**
     * @var boolean loggedin status
     */
    var $status = false;
    /**
     * @var integer user ID
     */
    var $user = false;
    /**
     * @var string user password
     */
    var $pass = false;
    /**
     * @var string user's name
     */
    var $user_name = false;
    /**
     * @var array list of goups ids in which current user is listed
     */
    var $groups = array();
    /**
     * First group id from self::groups
     *
     * @var int
     */
    var $group;
    /**
     * @var string username
     */
    var $username = false;
    /**
     * @var integer session timeout in seconds
     */
    var $timeou = 3600; // timeout in seconds
    /**
     * @var string site root url
     */
    var $siteroot = false; // site name for the session cookie
    /**
     * @var integer database connection identifier
     */
    var $dbc = false;
    /**
     * @var string template file
     */
    var $language = false;
    /**
     * @var integer active template set
     */
    var $tmpl = false;
    /**
     * @var boolean page requires authorization
     */
    var $issecure = false;
    /**
     * @var string modera product type constant MODERA_PRODUCT
     */
    var $version = "";
    /**
     * @var array merged array with _GET and _POST data
     */
    var $vars = array();
    /**
     * @var array fields used in forms
     */
    var $fields = array();
    /**
     * @var array texts to be parsed from language files to forms
     */
    var $texts = array();
    /**
     * @var array form required fields
     */
    var $required = array();
    /**
     * @var array fields to write to the DB
     */
    var $write = array();
    /**
     * @var string module cache level
     */
    var $cachelevel = TPL_CACHE_NOTHING;
    /**
     * @var integer cache expiry time in minutes
     */
    var $cachetime = 1440; //cache time in minutes

    var $db = false;

    var $errorCode = false;

    var $children_list = array();

    /**
     * Class constructor
     */

    function user()
    {
        global $db, $language;

        $login = $_REQUEST["login"];
        $logout = $_REQUEST["logout"];

        $this->db = &$GLOBALS['database'];
        $this->siteroot = COOKIE_URL;
        $this->language = $language;
        $this->tmpl = $GLOBALS["site_settings"]["template"];
        $this->vars = $_POST;
        $this->version = MODERA_PRODUCT;
        $this->timeou = (defined("USER_AUTH_COOKIE_LENGTH") && is_numeric(constant("USER_AUTH_COOKIE_LENGTH"))) ? USER_AUTH_COOKIE_LENGTH : $this->timeou;

        if (is_object($db)) {
            $this->dbc = $db->con;
        } else {
            $db = new DB;
            $this->dbc = $db->connect();
        }

        // moved out here to check SID on all pages
        $this->checkSID();
        if (ereg("/admin/", $_SERVER["PHP_SELF"])) {
            return;
        }

        if (!$this->isLoginRequired()) {
            return;
        }

        $this->issecure = true;
        if (!$login && !$logout && $this->status == false) {
            if ($GLOBALS["loginform"] == true) {
                return $this->show();
            } else {
                $this->redirectToAuthPage();
            }
        }
    }

    /**
     * @return bool
     */
    protected function isLoginRequired()
    {
        return ($GLOBALS["userlogin"] == true ||
            $GLOBALS["pagedata"]["login"] == "1" ||
            $GLOBALS["loginform"] == true) &&
            !$GLOBALS['pagedata']['public']
        ;
    }

    /**
     * Check active sessions
     * @return boolean true - active, false - not
     */

    function checkSID()
    {
//        $SID = $_SESSION["SID"];
        $SID = $_COOKIE["USR_SESS_SID"];
        if ($SID) { // && $GLOBALS["user_logged"] == "") {
            $sq = new sql;
            $sq->query($this->dbc, "SELECT sid FROM module_user_session WHERE sid = '$SID' AND (UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(access)) < " . $this->timeou . " LIMIT 1");
            if ($sq->numrows > 0) {
                $this->sid = $SID;
                $this->status = true;
                $sq->query($this->dbc, "UPDATE module_user_session SET access = now() WHERE sid = '$SID'");
                $this->changeSessionUser();
                return true;
            } else {
                $this->sid = false;
                $this->status = false;
                return false;
            }
            $sq->free();
        }
        //else if ($SID && $GLOBALS["user_logged"]) {
        //  $this->sid = $SID;
        //  $this->status = true;
        //}
    }

    /**
     * Check if the page has defined user group and if yes, check is the current user among the chosen ones
     * @return boolean
     */

    function isAuthorisedGroup()
    {
        if ($this->status == true && $GLOBALS["pagedata"]["login"] == "1") {
            if ($GLOBALS["pagedata"]["logingroups"] == "") {
                return true;
            } else {
                $a = split(",", $GLOBALS["pagedata"]["logingroups"]);
                return (bool)array_intersect($a, $this->groups);
            }
        } else {
            return false;
        }
    }

    /**
     * Check if the page has defined user type and if yes, check is the current user among the chosen ones
     * @return boolean
     */

    function isAuthorisedUserType()
    {
        if ($this->status == true && $GLOBALS["pagedata"]["login"] == "1") {
            if ($GLOBALS["pagedata"]["loginusertypes"] == "") {
                return true;
            } else {
                $a = split(",", $GLOBALS["pagedata"]["loginusertypes"]);
                return (bool)in_array($this->user_type, $a);
            }
        } else {
            return false;
        }
    }

// ############################################

    /**
     * Main module display function.  Display login form (if not using standard) or logged in data
     * @return string html
     */

    function show()
    {

        $sq = new sql;

        $structure = $_REQUEST["structure"];
        $content = $_REQUEST["content"];
        $logout = $_REQUEST["logout"];
        $login_check = $_REQUEST["login_check"];
        $change_profile = $_REQUEST["change_profile"];

        if ($this->issecure == true) {

            // Display Login form, redirect to login page
            if ($this->sid == false) {
                //if ($logout != "true") {
                //  Header("Location: user.php?login=true&href=". urlencode(processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("login","logout"))));
                //  exit;
                //}

                $txt = new Text($this->language, "module_user");

                // instantiate template class
                $tpl = new template;
                $tpl->setCacheLevel($this->cachelevel);
                $tpl->setCacheTtl($this->cachetime);
                $usecache = checkParameters();

                $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_login_pageform.html";
                $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&structure=" . $structure . "&content=" . $content . "&module=user&page=loginform");
                $tpl->setTemplateFile($template);

                // PAGE CACHED
                if ($tpl->isCached($template) == true && $usecache == true) {
                    $GLOBALS["caching"][] = "user";
                    if ($GLOBALS["modera_debug"] == true) {
                        return "<!-- module user cached -->\n" . $tpl->parse();
                    } else {
                        return $tpl->parse();
                    }
                }

                // #################################

                if ($login_check == "true") {
                    $tpl->addDataItem("MESSAGE.MESSAGE", "<font color=\"#ff0000\">" . $txt->display("error") . "</font>");
                }

                $tpl->addDataItem("SELF", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("username", "password", "login_check", "login", "logout")));

                $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 99 AND language = '" . addslashes($this->language) . "' LIMIT 1");
                if ($sq->numrows != 0) {
                    $data = $sq->nextrow();
                    $general_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"] . "&content=" . $data["content"];
                } else {
                    $general_url = "#";
                }
                $sq->free();

                $tpl->addDataItem("REGISTER", $txt->display("register"));
                $tpl->addDataItem("REGISTER_URL", $general_url);
                $tpl->addDataItem("USERNAME", $txt->display("username"));
                $tpl->addDataItem("PASSWORD", $txt->display("password"));
                $tpl->addDataItem("BUTTON", $txt->display("send"));

                return $tpl->parse();

            } // sid exists, user logged in
            else {

                $this->returnUser();

                $txt = new Text($this->language, "module_user");

                // instantiate template class
                $tpl = new template;
                $tpl->setCacheLevel($this->cachelevel);
                $tpl->setCacheTtl($this->cachetime);
                $usecache = checkParameters();

                $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_loggedin.html";
                $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=user&page=loggedin&user=" . $this->user);
                $tpl->setTemplateFile($template);

                // PAGE CACHED
                if ($tpl->isCached($template) == true && $usecache == true) {
                    $GLOBALS["caching"][] = "user";
                    if ($GLOBALS["modera_debug"] == true) {
                        return "<!-- module user cached -->\n" . $tpl->parse();
                    } else {
                        return $tpl->parse();
                    }
                }

                // #################################

                $name = $this->user_name;

                $message = ereg_replace("{NAME}", "$name", $txt->display("message_logged"));

                $tpl->addDataItem("LOGOUT", $txt->display("logout"));
                $tpl->addDataItem("LOGOUT_URL", processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "logout=true", array("username", "password", "login_check", "login", "logout")));

                $sq->query($this->dbc, "SELECT content, structure FROM content WHERE template = 98 AND language = '" . addslashes($this->language) . "' LIMIT 1");
                if ($sq->numrows != 0) {
                    $data = $sq->nextrow();
                    $profile_url = $_SERVER["PHP_SELF"] . "?structure=" . $data["structure"] . "&content=" . $data["content"];
                } else {
                    $profile_url = "#";
                }
                $sq->free();

                $tpl->addDataItem("EDIT_PROFILE", $txt->display("edit_profile"));
                $tpl->addDataItem("PROFILE_URL", $profile_url);

                if (strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
                    $tpl->addDataItem("UNIQID", substr(MODERA_KEY, 0, 4));
                } else {
                    $tpl->addDataItem("UNIQID", substr(MODERA_KEY, 0, 4) . "2");
                }

                $tpl->addDataItem("MESSAGE", $message);
                $tpl->addDataItem('USER_TYPE', $txt->display('user_type' . $this->user_type));

                $this->showUserTypeList($tpl, $txt);

                return $tpl->parse();
            }
        }
    }

    /**
     * User profile
     * @return string
     */

    function profile()
    {
        $sq = new sql;

        $this->returnUser();

        $txt = new Text($this->language, "module_user");

        $structure = $_REQUEST["structure"];
        $content = $_REQUEST["content"];
        $general_url = $_SERVER["PHP_SELF"] . "?content=" . $content . "&structure=" . $structure;
        $txt = new Text($this->language, "module_user");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_profile.html";
        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&structure=" . $structure . "&content=" . $content . "&module=user&page=profile");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "user";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module user cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }
        }


        // #################################

        if ($this->vars["submit_password"]) {
            if ($this->vars["password_old"] || $this->vars["password_new1"] || $this->vars["password_new2"]) {
                $pass_chck = $this->change_user_password($this->vars["password_old"], $this->vars["password_new1"], $this->vars["password_new2"]);
                switch ($pass_chck) {
                    case 1:
                        $pass_message = $txt->display("password_changed");
                        break;
                    case 2:
                        $pass_message = $txt->display("old_password_wrong");
                        break;
                    case 3:
                        $pass_message = $txt->display("password_error");
                        break;
                    case 4:
                        $pass_message = $txt->display("password_not_valid");
                        break;
                }
                $this->vars["password_old"] = "";
                $this->vars["password_new1"] = "";
                $this->vars["password_new2"] = "";
            }
            $this->changeProfile($txt->display("skin" . $this->vars["skin"]), $this->vars["right_column_hidden"] ? 1 : 0);
        }

        if ($pass_message) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $pass_message);
        }

        $tpl->addDataItem("HIDDEN", "<input type=\"hidden\" name=\"submit_password\" value=\"1\">");
        $tpl->addDataItem("BUTTON", $txt->display("change"));
        $tpl->addDataItem("SELF", $general_url);

        $fdata["type"] = "password";
        $fdata["size"] = "10";
        $fdata["class"] = "text";
        $f = new AdminFields("password_old", $fdata);
        $field_data = $f->display($this->vars["password_old"]);
        $tpl->addDataItem("FIELD_password_old", $field_data);

        $fdata["type"] = "password";
        $fdata["size"] = "10";
        $fdata["class"] = "text";
        $f = new AdminFields("password_new1", $fdata);
        $field_data = $f->display($this->vars["password_new1"]);
        $tpl->addDataItem("FIELD_password_new1", $field_data);

        $fdata["type"] = "password";
        $fdata["size"] = "10";
        $fdata["class"] = "text";
        $f = new AdminFields("password_new2", $fdata);
        $field_data = $f->display($this->vars["password_new2"]);
        $tpl->addDataItem("FIELD_password_new2", $field_data);

        $list = array();
        $selected_skin = 1;
        for ($u = 1; $u <= 10; $u++) {
            if ($this->skin == $txt->display("skin" . $u)) {
                $selected_skin = $u;
            }
            if ($txt->display("skin" . $u) != "*module_user|skin$u*") {
                $list[$u] = $txt->display("skin" . $u);
            }
        }

        $fdata["type"] = "select";
        $fdata["class"] = "text";
        $fdata["list"] = $list;
        $f = new AdminFields("skin", $fdata);
        $field_data = $f->display($selected_skin);
        $tpl->addDataItem("FIELD_skin", $field_data);

        $fdata["type"] = "checkbox";
        $fdata["class"] = "text";
        $f = new AdminFields("right_column_hidden", $fdata);
        $field_data = $f->display($this->rightcolhidden);
        $tpl->addDataItem("FIELD_right_column_hidden", $field_data);

        return $tpl->parse();
    }


    /**
     * Change password
     * @return 1 - password changed, 2 - old password wrong, 3 - new passwords don't match, 4 - new password not good enough
     */

    function change_user_password($old, $new1, $new2)
    {
        $sq = new sql;

        $old = trim($old);
        $new1 = trim($new1);
        $new2 = trim($new2);

        $hash_funct = $sq->pass_funct($this->dbc);

        $sql = "SELECT module_user_users.username, module_user_users.password, $hash_funct('$old') AS password_chck FROM module_user_users WHERE module_user_users.user = '" . $this->user . "'";

        $sq->query($this->dbc, $sql);
        if ($res = $sq->nextrow()) {
            $username = $res["username"];
            $password_real = $res["password"];
            $password_chck = $res["password_chck"];

            if ($password_real != $password_chck) {
                return 2; // old password is not right
            } else {
                if ($new1 != $new2) {
                    return 3; // new passwords do not match
                } else {
                    if (!$this->password_valid($new1)) {
                        return 4; // password is not valid
                    } else {
                        $sql = "UPDATE module_user_users SET module_user_users.password = $hash_funct('$new1') WHERE module_user_users.user = '" . $this->user . "'";
                        $sq->query($this->dbc, $sql);
                        // IM functionality
                        if (class_exists("imcontroller")) {
                            $sql = "UPDATE IM_users SET password = MD5(PASSWORD('" . addslashes($new1) . "')) WHERE username = '" . addslashes($username) . "'";
                            $sq->query($this->dbc, $sql);
                        }
                        return 1; // password changed
                    }
                }
            }
        }

        return 0; // something wrong, no user found
    }

    /**
     * Password validation
     * @return boolean
     */

    function password_valid($password)
    {
        if ($password == "") {
            return false;
        }
        return true;
    }

    /**
     * Change profile
     *
     */

    function changeProfile($skin, $rightcolhidden)
    {
        $sq = new sql;

        $sql = "UPDATE module_user_users SET module_user_users.skin = '" . addslashes($skin) . "', module_user_users.rightcolhidden = '" . addslashes($rightcolhidden) . "' WHERE module_user_users.user = '" . $this->user . "'";
        $sq->query($this->dbc, $sql);
    }


    /**
     * Standard modera.net login form
     * @return string
     */

    function login()
    {
        $structure = $_REQUEST["structure"];
        $content = $_REQUEST["content"];
        $href = $_REQUEST["href"];
        $login_check = $_REQUEST["login_check"];
        $auth_type = $_REQUEST["auth_type"];
        $auth_error = $_REQUEST["auth_error"];
        $error_code = $_REQUEST["error_code"];

        if ($this->sid) doJump("");

        $txt = new Text($this->language, "module_user");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_login.html";
        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=user&page=loginpage");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "user";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module user cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }
        }

        // #################################

        if ($login_check == "true") {
            $message = $txt->display("error");
        } elseif ($auth_error) {
            $message = $this->getAuthErrorMessage($txt, $auth_type, $error_code);
        }
        if ($message) {
            $tpl->addDataItem("MESSAGE.MESSAGE", "<font color=\"#ff0000\">" . $message . "</font>");
        }

        $url_params = array();
        if ($structure != "") $url_params[] = "structure=$structure";
        if ($content != "") $url_params[] = "content=$content";
        $url_params = join('&', $url_params);

        if ($url_params) $url_params = '?' . $url_params;

        $tpl->addDataItem("SELF", $_SERVER["PHP_SELF"] . $url_params);

        $tpl->addDataItem("LOGO", $GLOBALS["directory"]["img"] . "/topframe-logo-" . $this->version . ".gif");

        $tpl->addDataItem("HREF", urldecode($href));
        $tpl->addDataItem("AUTH_TYPE", $txt->display("auth_type"));
        $tpl->addDataItem("USERNAME", $txt->display("username"));
        $tpl->addDataItem("PASSWORD", $txt->display("password"));
        $tpl->addDataItem("BUTTON", $txt->display("send"));

        // authentication type list
        $list = array();
        for ($i = 1; $i <= self::MAX_AUTH_TYPE; $i++) {
            $list[$i] = $txt->display("auth_type" . $i);
        }

        $fdata["type"] = "select";
        $fdata["class"] = "text";
        $fdata["java"] = 'onchange="switch_login_type();"';
        $fdata["list"] = $list;

        $auth_type_selected = $auth_type ? $auth_type : 2; // default is id-card
        $f = new AdminFields("auth_type", $fdata);
        $field_data = $f->display($auth_type_selected);
        $field_data = str_replace("name=\"auth_type\"", "id=\"auth_type\" name=\"auth_type\"", $field_data);
        $tpl->addDataItem("FIELD_AUTH_TYPE", $field_data);

        return $tpl->parse();
    }

    private function getAuthErrorMessage($txt, $auth_type, $error_code)
    {
        $message = $this->getAuthErrorMessageByAuthType($txt, $auth_type);
        if ($error_code) {
            $tmpMessage = $txt->display("error_login" . $error_code);
            if (strpos($tmpMessage, '*') !== 0) {
                $message = $tmpMessage;
            }
        }
        return $message;
    }

    private function getAuthErrorMessageByAuthType($txt, $auth_type)
    {
        if ($auth_type == self::AUTH_TYPE_IDCARD) { // could not auth. with ID-card
            return $txt->display("error_id_card");
        }
        if ($auth_type == self::AUTH_TYPE_SWED) { // could not auth. with Hansanet
            return $txt->display("error_hansa");
        }
        if ($auth_type == self::AUTH_TYPE_SEB) { // could not auth. with SEB-bank
            return $txt->display("error_seb");
        }
        if ($auth_type == self::AUTH_TYPE_DANSKE) { // could not auth. with Danske-bank
            return $txt->display("error_danske");
        }
        if ($auth_type == self::AUTH_TYPE_NORDEA) { // could not auth. with Nordea-bank
            return $txt->display("error_nordea");
        }
        if ($auth_type == self::AUTH_TYPE_KREDIIDI) { // could not auth. with Krediidi-bank
            return $txt->display("error_krediidi");
        }
        if ($auth_type == self::AUTH_TYPE_LHV) { // could not auth. with LHV-bank
            return $txt->display("error_lhv");
        }
        return '';
    }

// ############################################

    /**
     * User registration form
     * @return string html
     */

    function register()
    {
        global $structure, $content, $send, $username, $code;

        $general_url_params = array();
        if ($structure) $general_url_params[] = 'structure=' . $structure;
        if ($content) $general_url_params[] = 'content=' . $content;

        $general_url = $_SERVER["PHP_SELF"] . '?' . join('&', $general_url_params);
        $general_url1 = '/?' . join('&', $general_url_params);

        // if logged in, redirect to modify page
        if ($this->sid != false) {
            doJump("");
        }

        if ($username != "" && $code != "") {
            return $this->activate($username, $code);
        } else {

            $sq = new sql;

            $txt = new Text($this->language, "module_user");

            // instantiate template class
            $tpl = new template;
            $tpl->setCacheLevel($this->cachelevel);
            $tpl->setCacheTtl($this->cachetime);
            $usecache = checkParameters();

            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_register.html";
            $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=user&page=register");
            $tpl->setTemplateFile($template);

            // PAGE CACHED
            if ($tpl->isCached($template) == true && $usecache == true) {
                $GLOBALS["caching"][] = "user";
                if ($GLOBALS["modera_debug"] == true) {
                    return "<!-- module user cached -->\n" . $tpl->parse();
                } else {
                    return $tpl->parse();
                }
            }

            // #################################

            $this->vars["added"] = date("Y-m-d H:i:s");

            if ($send == "true") {
                $error = 0;

                // CHECK ERRORS

                $old_username = $this->vars["username"];
                $this->vars["username"] = preg_replace('/ /', '_', $this->vars["username"]);
                $this->vars["username"] = ereg_replace("[^[:space:]a-zA-Z0-9*_-]", "", $this->vars["username"]);

                if ($old_username != $this->vars["username"]) {
                    $register_size = true;
                    $error++;
                }

                for ($c = 0; $c < sizeof($this->required); $c++) {
                    if ($this->vars[$this->required[$c]] == "") {
                        $error++;
                    }
                }

                if ($this->vars["username"] != "") {
                    $sq->query($this->dbc, "SELECT username FROM module_user_users WHERE username = '" . addslashes($this->vars["username"]) . "'");
                    if ($sq->numrows > 0) {
                        $register_exists = true;
                        $error++;
                    }
                    $sq->free();
                }

                if (strlen($this->vars["username"]) < 3 || strlen($this->vars["username"]) > 10) {
                    $register_size = true;
                    $error++;
                }
                if (strlen($this->vars["password"]) < 4) {
                    $register_size = true;
                    $error++;
                }
                if ($this->vars["password"] != $this->vars["password2"]) {
                    $register_password = true;
                    $error++;
                }

                if ($this->vars["email"] != "") {
                    if (validateEmail($this->vars["email"]) == false) {
                        $register_email = true;
                        $error++;
                    }
                }

                // END CHECK ERRORS

                if ($error == 0) {
                    for ($c = 0; $c < sizeof($this->write); $c++) {
                        if (ereg("password", $this->write[$c])) {
                            // check mysql version, if it is >= 4.1.0 we will use old_password function
                            $hash_funct = $sq->pass_funct($this->dbc);
                            $val[] = "$hash_funct('" . addslashes($this->vars[$this->write[$c]]) . "')";
                        } else {
                            $temp_val = "";
                            if (is_array($this->vars[$this->write[$c]])) {
                                for ($t = 0; $t < sizeof($this->vars[$this->write[$c]]); $t++) {
                                    $temp_val .= $this->vars[$this->write[$c]][$t];
                                    if (($t + 1) < sizeof($this->vars[$this->write[$c]])) $temp_val .= ",";
                                }
                                $val[] = "'" . addslashes($temp_val) . "'";
                            } else {
                                $val[] = "'" . addslashes($this->vars[$this->write[$c]]) . "'";
                            }
                        }
                    }

                    $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("register_ok"));

                    $res = $sq->query($this->dbc, "INSERT INTO module_user_users (" . join(",", $this->write) . ") VALUES(" . join(",", $val) . ")");

                    if ($res) {
                        $code = substr(md5($this->vars["username"] . $this->vars["email"] . "MDZ"), 0, 12);

                        $contentx = $txt->display("register_verify");
                        $url = SITE_URL . $general_url1 . "&username=" . $this->vars["username"] . "&code=" . $code;
                        $url1 = "<a href=\"$url\">$url</a>";
                        $contentx = ereg_replace("{URL}", "$url1", $contentx);

                        include(SITE_PATH . "/class/mail/htmlMimeMail.php");

                        $mail = new htmlMimeMail();
                        $mail->setHtml($contentx, returnPlainText($contentx));
                        $mail->setFrom($GLOBALS["site_settings"]["name"] . " <" . $GLOBALS["site_settings"]["admin_email"] . ">");
                        $mail->setSubject($txt->display("register_verify_subject"));
                        $result = $mail->send(array($this->vars["email"]));
                    } else {
                        $error++;
                    }

                }
            }
            // display form
            if (!$send || $error > 0) {

                $tpl->addDataItem("FORM.SELF", $general_url);

                if ($error > 0) {
                    $errors = '';
                    if ($register_exists == true) $errors .= "<br>" . $txt->display("register_exists");
                    if ($register_size == true) $errors .= "<br>" . $txt->display("register_size");
                    if ($register_password == true) $errors .= "<br>" . $txt->display("register_password");
                    if ($register_email == true) $errors .= "<br>" . $txt->display("register_email");
                    $tpl->addDataItem("MESSAGE.MESSAGE", "<font color=\"#ff0000\">" . $txt->display("register_error") . $errors . "</font>");
                } else {
                    $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("register_info"));
                }

                // parse text elements
                for ($c = 0; $c < sizeof($this->texts); $c++) {
                    $tpl->addDataItem("FORM.TEXT_" . $this->texts[$c], $txt->display($this->texts[$c]));
                }

                // parse fields
                while (list($key, $val) = each($this->fields)) {
                    $f = new AdminFields("$key", $val);
                    $field_data = $f->display($this->vars[$key]);
                    $tpl->addDataItem("FORM.FIELD_$key", $field_data);
                    unset($fdata);
                }

            }

            return $tpl->parse();

            // ##
        }

    }

// ############################################

    /**
     * Activate registered account
     * @param string username
     * @param string activation code
     * @return string html result
     */

    function activate($username, $code)
    {

        global $structure, $content, $send;

        $sq = new sql;

        $txt = new Text($this->language, "module_user");

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1] . "/" . "module_user_register.html";
        $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language . "&module=user&page=register&activate=true");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "user";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module user cached -->\n" . $tpl->parse();
            } else {
                return $tpl->parse();
            }
        }

        // #################################

        $sq->query($this->dbc, "SELECT username, email FROM module_user_users WHERE username = '" . addslashes($username) . "' AND active = 0");
        if ($sq->numrows > 0) {
            $data = $sq->nextrow();
            $code1 = substr(md5($data["username"] . $data["email"] . "MDZ"), 0, 12);
            if ($code == $code1) {
                $sq->query($this->dbc, "UPDATE module_user_users SET active = 1 WHERE username = '" . addslashes($username) . "' AND active = 0");
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("register_verify_ok"));
            } else {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("register_verify_error"));
            }
        } else {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("register_verify_error"));
        }

        return $tpl->parse();

    }

// ############################################

    /**
     * If user account has defined Ip addresses, check if the current user is using any of them
     * @access private
     * @return boolean true on access, redirect to error page on failure
     */

    function checkIP($ips)
    {

        if ($ips) {
            $a = split(",", $ips);
            if (in_array($_SERVER["REMOTE_ADDR"], $a)) {
                return true;
            } else {
                $this->displayError("403");
            }
        } else {
            return true;
        }

    }

    /**
     * Create unique session ID
     * @access private
     * @return string generated session ID
     */

    function initSid()
    {
        $sid = md5(uniqid(rand()));
        $this->sid = $sid;
        return $this->sid;
    }

    /**
     * Get "engine url" which actually is cookie path
     *
     * Logic stays from previous version of class
     *
     * @return string
     * @access protected
     */
    function _engine_url()
    {
        if (false !== $pos = strpos(SITE_URL, '/', 8)) {
            return substr(SITE_URL, $pos);
        } else {
            return '/';
        }
    }

    function getUserRecordByLoginData($loginData)
    {
        $res =& $this->db->query("
            SELECT
                `u`.*
            FROM
                `module_user_users` AS `u`
            WHERE
                ?%AND
            ORDER BY
                `user_type` ASC
            LIMIT 1
        ",
            $loginData);
        if ($res) {
            return $res->fetch_assoc();
        }
        return false;
    }

    function insertSession()
    {
        $this->db->query("
            INSERT INTO
                `module_user_session`
            VALUES (
                null,
                ?,
                ?,
                !,
                ?
            )
        ",
            $this->sid,
            $this->db->now(),
            $this->user,
            $this->db->now()
        );
        setcookie("USR_SESS_SID", $this->sid, 0, $this->_engine_url(), COOKIE_URL);
    }

    function changeSessionUser()
    {
        $userId = $_REQUEST["change_user"] + 0;
        if ($userId && is_int($userId) && $this->isValidUserId($userId)) {
            $this->db->query("
                UPDATE
                    `module_user_session`
                SET
                    `user` = !
                WHERE
                    `sid` = ?
            ",
                $userId,
                $this->sid
            );
        }
    }

    function isValidUserId($userId)
    {
        $this->returnUser();
        $r = $this->db->query("
            SELECT
                `module_user_users`.`user`
            FROM
                `module_user_users`
            WHERE
                `user_code` = ? AND
                `active` = 1 AND
                `user` = !
        ",
            $this->user_code,
            $userId
        );
        return $r->num_rows() > 0;
    }

    function log($message)
    {
        $log = &SystemLog::instance($this->db);
        $log->log('user_login', $message);
    }

    function setSessionDefault($sessionData)
    {
        if ($this->sid) {
            return $this->sid;
        }

        $externalInfo = $sessionData['external_info'];
        $type = $sessionData['type'];
        $typeName = $this->authenticationTypeName[$type];
        $user_code = $sessionData['user_code'];
        if ($this->isUserCodeCorrect($type, $user_code, $externalInfo)) {
            $user_name = $this->getNameFromExternalInfo($type, $externalInfo);
            $data = $this->getUserRecordByUserCode($user_code, $user_name);
            if ($data) {
                if ($this->isAllowedAge($data['birthday'])) {
                    if ($this->isAllowedAuthType($type, $data['auth_type'])) {
                        $this->sid = $this->initSid();
                        $this->status = true;
                        $this->user = $data['user'];
                        $this->insertSession();
                        $this->returnUser(); // fill class variables
                        $this->log('User "' . $data["username"] . '" logged into the system with ' . $typeName . '.');
                        return $this->sid;
                    } else {
                        $this->errorCode = 40;
                        $error = "auth_type";
                    }
                } else {
                    $this->errorCode = 30;
                    $error = 'not allowed age';
                }
            } else {
                $this->errorCode = 20;
                $error = "user not found";
            }
        } else {
            $this->errorCode = 10;
            $error = "codes not matching";
        }

        // Write to systemlog info about login attempt
        $this->log('Unsuccessful attempt to login with ' . $typeName . ' of user "' . $user_code . ", " . $externalInfo . '". Error: ' . $error);
        return false;
    }

    function isAllowedAge($date)
    {
        return IsicDate::diffInYears(time(), strtotime($date)) >= self::ALLOWED_AGE_IN_YEARS;
    }

    function getUserRecordByUserCode($user_code, $user_name)
    {
        $data = $this->getUserRecordByLoginData(
            array(
                'user_code' => $user_code,
                'active' => 1,
                'user_type' => 1
            )
        );
        return $data;
    }

    function addNewUserRecord($user_code, $user_name)
    {
        $userDb = IsicDB::factory('Users');
        $userDb->insertRecord(
            array(
                'user_code' => $user_code,
                'username' => $user_code,
                'birthday' => IsicDate::calcBirthdayFromNumber($user_code),
                'name_first' => $user_name['name_first'],
                'name_last' => $user_name['name_last'],
            )
        );
    }

    function isUserCodeCorrect($type, $userCode, $externalInfo)
    {
        return ($userCode && $userCode == $this->getIdCode($type, $userCode, $externalInfo));
    }

    function isAllowedAuthType($authType, $allowedTypes)
    {
        $auth_types = explode(",", $allowedTypes);
        return in_array($authType, $auth_types);
    }

    function getIdCode($type, $userCode, $externalInfo)
    {
        switch ($type) {
            case self::AUTH_TYPE_IDCARD:
                $tmp_client = explode(",", $externalInfo);
                $id_code = @$tmp_client[2]; // estonian id-code
                return $id_code;
                break;
            case self::AUTH_TYPE_SWED: // falls through
            case self::AUTH_TYPE_SEB: // falls through
            case self::AUTH_TYPE_DANSKE: // falls through
            case self::AUTH_TYPE_KREDIIDI: // falls through
            case self::AUTH_TYPE_LHV: // falls through
                $id_code = $this->getAttributeValue($externalInfo, 'ISIK'); // estonian id-code
                return $id_code;
                break;
            default:
                return $userCode;
                break;
        }
    }

    function getAttributeValue($srcList, $attrName)
    {
        $srcArray = explode(';', $srcList);
        foreach ($srcArray as $src) {
            if (strpos($src, $attrName . ':') !== false) {
                return trim(str_replace($attrName . ':', '', $src));
            }
        }
        return false;
    }

    function getNameFromExternalInfo($type, $externalInfo)
    {
        $name = array('name_first' => '', 'name_last' => '');
        switch ($type) {
            case self::AUTH_TYPE_IDCARD:
                $tmp_client = explode(",", $externalInfo);
                $name['name_first'] = @$tmp_client[1];
                $name['name_last'] = @$tmp_client[0];
                break;
            case self::AUTH_TYPE_SWED: // falls through
            case self::AUTH_TYPE_SEB: // falls through
            case self::AUTH_TYPE_DANSKE: // falls through
            case self::AUTH_TYPE_KREDIIDI: // falls through
            case self::AUTH_TYPE_LHV: // falls through
                $tmpName = $this->getAttributeValue($externalInfo, 'NIMI');
                $name = $this->getFirstAndLastNameFromFullName($tmpName);
                break;
            case self::AUTH_TYPE_NORDEA:
                $name = $this->getFirstAndLastNameFromFullName($externalInfo);
                break;
            default:
                break;
        }
        return $name;
    }

    function getFirstAndLastNameFromFullName($inFullName)
    {
        $name = array();
        $fullName = str_replace(',', ' ', $inFullName);
        $name['name_first'] = substr($fullName, 0, strrpos($fullName, " "));
        $name['name_last'] = substr($fullName, strrpos($fullName, " ") + 1);
        return $name;
    }

    /**
     * Login with ID-card
     *
     * @param user_code User code
     * @param string $ssl_client_s_dn_cn
     * @return mixed false on failure, session ID on success
     */
    function setIdCardSession($user_code, $ssl_client_s_dn_cn)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_IDCARD,
            'external_info' => $ssl_client_s_dn_cn
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with Hansanet
     *
     * @param user_code
     * @param string VK_INFO (ISIK:12345678901;NIMI:FIRSTNAME LASTNAME
     * @return mixed false on failure, session ID on success
     */
    function setHansaSession($user_code, $VK_INFO)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_SWED,
            'external_info' => $VK_INFO
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with SEB
     *
     * @param user_code
     * @param string VK_INFO (NIMI:FIRSTNAME LASTNAME;ISIK:12345678901;
     * @return mixed false on failure, session ID on success
     */
    function setSebSession($user_code, $VK_INFO)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_SEB,
            'external_info' => $VK_INFO
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with Danske
     *
     * @param user_code
     * @param string VK_INFO (ISIK:12345678901;NIMI:FIRSTNAME LASTNAME
     * @return mixed false on failure, session ID on success
     */
    function setDanskeSession($user_code, $VK_INFO)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_DANSKE,
            'external_info' => $VK_INFO
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with Nordea
     *
     * @param user_code
     * @param user_name
     * @return mixed false on failure, session ID on success
     */
    function setNordeaSession($user_code, $user_name)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_NORDEA,
            'external_info' => $user_name
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with Krediidipank
     *
     * @param user_code
     * @param string VK_INFO (ISIK:12345678901;NIMI:FIRSTNAME LASTNAME
     * @return mixed false on failure, session ID on success
     */
    function setKrediidiSession($user_code, $VK_INFO)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_KREDIIDI,
            'external_info' => $VK_INFO
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Login with LHV
     *
     * @param user_code
     * @param string VK_INFO (ISIK:12345678901;NIMI:FIRSTNAME LASTNAME
     * @return mixed false on failure, session ID on success
     */
    function setLhvSession($user_code, $VK_INFO)
    {
        $sessionData = array(
            'user_code' => $user_code,
            'type' => self::AUTH_TYPE_LHV,
            'external_info' => $VK_INFO
        );
        return $this->setSessionDefault($sessionData);
    }

    /**
     * Create new sessions
     * @param username Username
     * @param password User password
     * @return mixed false on failure, session ID on success
     */

    function setSession($username, $password)
    {
        if ($this->sid) {
            return $this->sid;
        }
        $username = addslashes($username);
        $password = addslashes($password);
        $sq = new sql;
        // check mysql version, if it is >= 4.1.0 we will use old_password function
        $hash_funct = $sq->pass_funct($this->dbc);
        $sq->query($this->dbc, "
            SELECT `user`, `ips` 
            FROM `module_user_users`
            WHERE 
                `username` = '{$username}' AND 
                `password` = {$hash_funct}('{$password}') AND 
                `active` = 1 AND
                `user_type` = 1
            LIMIT 1"
        );

        if ($sq->numrows == 0) {
            // Write to systemlog info about login attempt
            $this->log('Unsuccessful attempt to login of user "' . $username . '".');
            return false;
        }
        $this->checkIP($sq->column(0, "ips"));
        $this->sid = $this->initSid();
        $this->status = true;
        $this->user = $sq->column(0, "user");
        $this->insertSession();

        // Write to systemlog info about login user
        $this->log('User "' . $username . '" logged into the system.');
        return $this->sid;
    }

    function setSessionByUserId($userId)
    {

    }

    /**
     * Retrieve logged in user data based on session
     * @return integer user id
     */

    function returnUser()
    {
        if ($this->sid) {
            $sq = new sql;

            $sql = "SELECT
                        s.user, CONCAT(u.name_first, ' ', u.name_last) AS name,
                        u.username,
                        u.ggroup,
                        u.password,
                        u.skin,
                        u.rightcolhidden,
                        u.auth_type,
                        u.user_type,
                        u.user_code,
                        u.email,
                        u.active_school_id,
                        u.children_list
                    FROM
                        module_user_session as s
                        LEFT JOIN module_user_users AS u
                        ON s.user = u.user
                    WHERE
                        s.sid = '" . $this->sid . "' LIMIT 1";

            $sq->query($this->dbc, $sql);
            $this->user = $sq->column(0, "user");
            $this->pass = $sq->column(0, "password");
            $this->user_name = $sq->column(0, "name");
            $this->user_type = $sq->column(0, "user_type");
            $this->user_code = $sq->column(0, "user_code");
            $this->groups = $this->getUserGroups(explode(',', $sq->column(0, 'ggroup')));
            $this->auth_types = explode(',', $sq->column(0, 'auth_type'));
            $this->group = $this->groups[0];
            $this->username = $sq->column(0, "username");
            $this->user_email = $sq->column(0, "email");
            $this->active_school_id = $sq->column(0, "active_school_id");
            $this->skin = $sq->column(0, "skin");
            $this->rightcolhidden = $sq->column(0, "rightcolhidden");
            $tmpChildrenList = $sq->column(0, "children_list");
            $this->children_list = $tmpChildrenList ? explode(',', $tmpChildrenList) : array();
            $sq->free();
        }

        return $this->user;
    }

    function getUserGroups($groups)
    {
        $userGroupsDb = IsicDB::factory('UserGroups');
        IsicDB::clearFactoryCache();  // used to avoid cache with wrong user data
        if (is_array($groups) && in_array($userGroupsDb->getSuperGroupId(), $groups)) {
            return $userGroupsDb->getAllRecordIds();
        } else {
            return $groups;
        }
    }

    /**
     * Return active user session id
     * @return string
     */

    function returnID()
    {
        return $this->sid;
    }


    /**
     * Logs out the currently logged in user.
     * @access private
     */

    function logOut()
    {
        if ($this->sid) {
            $sq = new sql;
            $sq->query($this->dbc, "UPDATE `module_user_session` SET `access` = '"
                . date("Y-m-d H:i:s", (time() - ($this->timeou + 60)))
                . "' WHERE `sid` = '" . $this->sid . "'");
            unset($_SESSION["SID"]);
            $this->sid = false;
        }
        if ($GLOBALS["loginform"] == true) {
            redirect("/");
            exit;
        } else {
            redirect("user.php?logout=true");
            exit;
        }
    }

    // ###############################

    /**
     * Redirect to modera.net standard error page, with given code
     * @access private
     * @param integer error code
     */

    function displayError($code)
    {
        redirect("error.php?error=$code");
        exit;
    }

    /**
     * Set the fields in the registration form
     * @param string comma separated list of fieldname
     */

    function setFields($fields)
    {
        if ($fields) {
            $a = array();
            $a = explode(",", $fields);
            for ($c = 0; $c < sizeof($a); $c++) {
                $this->fields[$a[$c]] = array();
                $this->fields[$a[$c]]["type"] = "textinput";
                $this->fields[$a[$c]]["size"] = "40";
            }
        }
    }

    /**
     * Set text elements to parse form the language file
     * @param string comma separated list of names
     */

    function setText($fields)
    {
        if ($fields) {
            $this->texts = explode(",", $fields);
        }
    }

    /**
     * set field type. (refer to adminfields class for list of field types)
     * @param string field name
     * @param string field type
     */

    function setFieldType($field, $type)
    {
        if ($field && $type) {
            $this->fields[$field]["type"] = $type;
        }
    }

    /**
     * Set field properties (refer to adminfields class for list of type properties)
     * @param string field name
     * @param string field property
     * @param string value to set
     */

    function setFieldProp($field, $prop, $value)
    {
        if ($field && $prop && $value) {
            if ($prop == "list") {
                $txt = new Text($this->language, "module_user");
                $a = array();
                $final = array();
                $a = explode(",", $value);
                for ($c = 0; $c < sizeof($a); $c++) {
                    if ($txt->display($field . $a[$c]) != "") {
                        $final[$a[$c]] = $txt->display($field . $a[$c]);
                    } else {
                        $final[$a[$c]] = $a[$c];
                    }
                }
                $this->fields[$field]["list"] = $final;
            } else {
                $this->fields[$field][$prop] = $value;
            }
        }
    }

    /**
     * Join field values
     * @param string field to join values to
     * @param string the glue to join values with
     * @param string comma separated list of fields to join
     */

    function setJoin($field, $delimiter, $fields)
    {
        $ar = explode(",", $fields);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $ar1[] = $this->vars[$ar[$c]];
        }
        $this->vars[$field] = join($delimiter, $ar1);
    }

    /**
     * Set required fields
     * @param array required fields
     */

    function setRequired($required)
    {
        $this->required = explode(",", $required);
    }

    /**
     * Set fields to write to DB
     * @param string comma separated list of fields to join
     */

    function setWrite($write)
    {
        $this->write = explode(",", $write);
    }

    /**
     * Get available groups
     *
     * Returns associative array where keys are groups ids and values
     * are group names
     *
     * @return array
     */
    function getGroups()
    {
        $groups = array();
        $res =& $GLOBALS['database']->query('SELECT `id`, `name` FROM `module_user_groups`');
        while ($row = $res->fetch_assoc()) {
            $groups[$row['id']] = $row['name'];
        }

        return $groups;
    }

    /**
     * Get available user types
     *
     * Returns associative array where keys are user type ids and values
     * are user type names
     *
     * @return array
     */
    function getUserTypes()
    {
        $utypes = array();
        $res =& $GLOBALS['database']->query('SELECT `id`, `name` FROM `module_user_types`');
        while ($row = $res->fetch_assoc()) {
            $utypes[$row['id']] = $row['name'];
        }

        return $utypes;
    }

    /**
     * Issues a new ticket for renewing forgotten password
     *
     * @param string $username
     * @return array|FALSE associative array with two items: user email and
     *  ticket key and, if supplied username was not found in system than
     *  returns FALSE
     * @access private
     */
    function _createRenewTicket($username)
    {
        global $database;

        $row = $database->fetch_first_row('SELECT `user`, `email` FROM '
            . '`module_user_users` WHERE `username` = ? AND `active` = 1', $username);
        if (false !== $row) {
            $user_id = $row['user'];
            $email = $row['email'];

        } else {
            return false;
        }

        // remove old renew records
        $database->query('DELETE FROM `module_user_passrenew` WHERE `user` = ?'
            , $user_id);

        // generate new unique ticket
        do {
            $key = md5($user_id . rand());
        } while (false !== $database->fetch_first_value('SELECT `user` FROM '
            . '`module_user_passrenew` WHERE `key` = ?', $key));

        // create new password renew record (ticket)
        $database->query('INSERT INTO `module_user_passrenew` (`user`, `key`, `date`) '
            . ' VALUES(?, ?, NOW())', $user_id, $key);

        return array($email, $key);
    }

    /**
     * Change forgotten password
     *
     * @param string $key ticket key issued by _createNewTicket() method
     * @param string $password
     * @return bool
     * @access private
     */
    function _renewPassword($key, $password)
    {
        global $database;

        if (32 != strlen($key)) return false;
        $this->_clearOldRenews();

        $user_id = $database->fetch_first_value('SELECT `user` FROM `module_user_passrenew`'
            . ' WHERE `key` = ?', $key);
        if (false == $user_id) return false;

        // get password hash function name
        $sq = new sql;
        $hash_funct = $sq->pass_funct($this->dbc);

        // escape password
        $password = $database->quote($password);

        // update password
        $database->query('UPDATE `module_user_users` SET `password` = ! WHERE `user` = ?'
            , "$hash_funct($password)", $user_id);

        // remove ticket
        $database->query('DELETE FROM `module_user_passrenew` WHERE `key` = ?'
            , $key);

        return true;
    }

    /**
     * Clear outdated tickets
     *
     * @return int number of removed tickets
     */
    function _clearOldRenews()
    {
        global $database;

        $database->query('DELETE FROM `module_user_passrenew` WHERE NOW() > '
            . ' DATE_ADD(`date`, INTERVAL 14 DAY)');
        return $database->affected_rows();
    }

    /**
     * Passowrd renew action
     *
     * Method is called from template
     *
     * @return string
     */
    function passwordRenew()
    {
        global $database;

        // if logged in, redirect to modify page
        if ($this->sid != false) doJump("");

        $translator = ModeraTranslator::instance($this->language, 'module_user');
        $tpl = new template;
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);

        if (isset($_GET['key'])) {
            $template = $GLOBALS['templates_' . $this->language][$this->tmpl][1]
                . '/module_user_updatepassword.html';
            $tpl->setTemplateFile($template);

            if (isset($this->vars['password'])) {
                if (strlen($this->vars['password']) > 3
                    && $this->vars['password'] == $this->vars['password_verify']
                ) {
                    // change password
                    if ($this->_renewPassword($_GET['key'], $this->vars['password'])) {
                        return '<div align="center"><h1> ' . $translator->tr('msg_password_changed')
                            . '</h1></div>';
                    } else {
                        return '<div align="center"><h1>' . $translator->tr('msg_key_not_valid')
                            . '</h1></div>';
                    }

                } else {
                    // show errors
                    $tpl->addDataItem('PASSWORD_ERROR', '<div style="color: red;">'
                        . $translator->tr('msg_passwords_not_matched') . '</div>');
                }
            }

            $tpl->addDataItem('ENTER_NEW_PASSWORD', $translator->tr('enter_new_password'));
            $tpl->addDataItem('PASSWORD', $translator->tr('password'));
            $tpl->addDataItem('PASSWORD_VERIFY', $translator->tr('password2'));
            $tpl->addDataItem('CHANGE_PASSWORD', $translator->tr('change_password'));

            // show form for changing password

        } else {
            $template = $GLOBALS["templates_" . $this->language][$this->tmpl][1]
                . "/module_user_forgotpassword.html";
            $tpl->setInstance($_SERVER["PHP_SELF"] . "?language=" . $this->language
                . "&module=user&page=forgotpassword");
            $tpl->setTemplateFile($template);

            $tpl->addDataItem('FORGOT_PASSOWRD', $translator->tr('forgot_password'));
            $tpl->addDataItem('GET_PASSOWRD', $translator->tr('get_password'));
            $tpl->addDataItem('GET_USERNAME', $translator->tr('get_username'));
            $tpl->addDataItem('FORGOT_PASSWORD_NOTE', $translator->tr('forgot_password_note'));
            $tpl->addDataItem('FORGOT_USERNAME_NOTE', $translator->tr('forgot_username_note'));
            $tpl->addDataItem('ENTER_YOUR_USERNAME', $translator->tr('enter_username'));
            $tpl->addDataItem('FORGOT_USERNAME', $translator->tr('forgot_username'));
            $tpl->addDataItem('ENTER_YOUR_EMAIL', $translator->tr('enter_email'));

            if (isset($this->vars['type'])) {
                switch ($this->vars['type']) {
                    case 'username':
                        $username = trim($this->vars['username']);

                        if (false !== ($row = $this->_createRenewTicket($username))) {
                            list($email, $key) = $row;

                            $row = $database->fetch_first_row('SELECT `content`, `structure`'
                                . ' FROM `content` WHERE `template` = 101 AND `language` = ?'
                                . ' LIMIT 1', $this->language);
                            if ($row) {
                                $url = SITE_URL . "/?structure=$row[structure]&content=$row[content]&key=$key";
                            } else {
                                $url = '#';
                            }

                            $message = $translator->tr('mail_body_password_link'
                                , array(htmlspecialchars($url), $url));
                            $subject = $translator->tr('mail_subj_password_link'
                                , array(htmlspecialchars(SITE_URL)));

                            if ($this->_sendPassRenewEmail($email, $subject, $message)) {
                                return '<div align="center">'
                                    . $translator->tr('msg_email_password_link_sent')
                                    . '</div>';
                            } else {
                                return '<div align="center">' . $translator->tr('msg_mail_error')
                                    . '</div>';
                            }
                        }

                        $tpl->addDataItem('USERNAME_ERROR', '<div style="color: red;">'
                            . $translator->tr('msg_no_such_user') . '</div>');
                        $tpl->addDataItem('username', htmlspecialchars($username));
                        break;

                    case 'email':
                        $email = trim($this->vars['email']);
                        $username = $GLOBALS['database']->fetch_first_value(
                            'SELECT `username` FROM `module_user_users` WHERE `email` = ? AND `active` = 1'
                            , $email);

                        if (false !== $username) {
                            $site_url = htmlspecialchars(SITE_URL);
                            $site_link = "<a href=\"$site_url\">$site_url</a>";

                            $message = $translator->tr('mail_body_username'
                                , array($site_url, htmlspecialchars($username)));
                            $subject = $translator->tr('mail_subj_username', $site_url);

                            if ($this->_sendPassRenewEmail($email, $subject, $message)) {
                                return '<div align="center">' . $translator->tr('msg_email_username_sent')
                                    . '</div>';
                            } else {
                                return '<div align="center">' . $translator->tr('msg_mail_error')
                                    . '</div>';
                            }
                        }

                        $tpl->addDataItem('EMAIL_ERROR', '<div style="color: red;">'
                            . $translator->tr('msg_no_such_email') . '</div>');
                        $tpl->addDataItem('email', htmlspecialchars($email));
                        break;
                }
            }
        }

        return $tpl->parse();
    }

    /**
     * Sends email
     *
     * @param string $to destination email address
     * @param string $subject email subject
     * @param string $text message body
     * @return bool TRUE if email was sended successfully, FALSE otherwise
     */
    function _sendPassRenewEmail($to, $subject, $text)
    {
        include_once(SITE_PATH . "/class/mail/htmlMimeMail.php");
        $mail = new htmlMimeMail();
        $mail->setHtmlCharset('UTF-8');
        $mail->setHtml($text, returnPlainText($text));
        $mail->setText(returnPlainText($text));

        if ($GLOBALS["site_admin_name"] && validateEmail($GLOBALS["site_admin"])) {
            $mail->setFrom($GLOBALS["site_admin_name"] . " <" . $GLOBALS["site_admin"] . ">");
            $mail->setReturnPath($GLOBALS['site_admin']);
        } else {
            $mail->setFrom("Modera <info@modera.net>");
            $mail->setReturnPath('info@modera.net');
        }

        $mail->setSubject($subject);
        return $mail->send(array($to));
    }

    function showUserTypeList($tpl, $txt)
    {
        $r = &$this->db->query("
            SELECT
                `module_user_users`.`user`,
                `module_user_users`.`user_type`,
                `module_user_users`.`name_first`,
                `module_user_users`.`name_last`
            FROM
                `module_user_users`
            WHERE
                `module_user_users`.`user_code` = ? AND
                `module_user_users`.`active` = 1
            ORDER BY
                `module_user_users`.`name_last`,
                `module_user_users`.`name_first`
            ",
            $this->user_code
        );
        $userModuleUrl = SITE_URL . '/?change_user=';
        while ($data = $r->fetch_assoc()) {
            $tpl->addDataItem('USER_LIST.URL', $userModuleUrl . $data["user"]);
            $tpl->addDataItem('USER_LIST.NAME', $data["name_first"] . ' ' . $data["name_last"]);
            $tpl->addDataItem('USER_LIST.TYPE_ID', $data["user_type"] == 1 ? '1' : '');
            $tpl->addDataItem('USER_LIST.TYPE_NAME', $txt->display('user_type' . $data["user_type"]));
            $tpl->addDataItem('USER_LIST.CLASS', $data["user"] == $this->user ? 'active' : '');
        }
    }

    protected function redirectToAuthPage()
    {
        $auth_type = $_REQUEST["auth_type"];
        $auth_error = $_REQUEST["auth_error"];
        $error_code = $_REQUEST["error_code"];

        $url = "/user.php?login=true";
        if ($auth_type) {
            $url .= "&auth_type=" . urlencode($auth_type);
        }
        if ($auth_error) {
            $url .= "&auth_error=1";
        }
        if ($error_code) {
            $url .= "&error_code=" . intval($error_code);
        }
        $url . "&href=" . urlencode(processUrl($_SERVER["PHP_SELF"], $_SERVER["QUERY_STRING"], "", array("login", "logout", "auth_type", "auth_error")));
        redirect(SITE_URL . $url);
        exit;
    }
}
