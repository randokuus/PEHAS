<?php
//error_reporting(E_ALL);
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/adminfields.class.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicImage.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicTemplate.php");

class isic_school {
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

    var $user_email = false;

    var $user_name = false;

    /**
     * @var boolean is page login protected, from $GLOBALS["pagedata"]["login"]
     */
    var $user_access = 0;

    /**
     * Level of caching of the pages
     *
     * @var const
     * @access protected
     */

    var $cachelevel = TPL_CACHE_ALL;

    /**
     * Cache time in minutes
     *
     * @var int
     * @access protected
     */

    var $cachetime = 60;

    /**
     * Default translation module to use
     *
     * @var string
     * @access protected
     */
    var $translation_module_default = "module_isic_user";

    var $users;

    var $isicDbUsers = false;

    var $user_active_school = 0;

    private $tplInstParam;

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

    function isic_school () {
        $this->vars = array_merge($_GET, $_POST);
        $this->tmpl = $GLOBALS['site_settings']['template'];
        $this->language = $GLOBALS['language'];
        $this->txt = new Text($this->language, "module_isic_user");
        $this->txts = new Text($this->language, "module_isic_settings");
        $this->db = &$GLOBALS['database'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->user_name = $GLOBALS["user_data"][1];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];
        $this->user_email = $GLOBALS["user_data"][8];
        $this->user_active_school = $GLOBALS["user_data"][9];

        if ($this->content_module == true) {
            $this->getParameters();
        }

        $this->isicDbUsers = IsicDB::factory('Users');
        $this->isicDbSchools = IsicDB::factory('Schools');

        // assigning common methods class
        $this->isic_common = IsicCommon::getInstance();
        $this->isicTemplate = new IsicTemplate('isic_school', $this->cachelevel, $this->cachetime);

        $this->user_type_admin = $this->isic_common->user_type_admin;
        $this->user_type_user = $this->isic_common->user_type_user;

        $this->allowed_schools = $this->isic_common->allowed_schools;
        $this->tplInstParam = sha1('UID:' . $this->userid . ';ALLOWED_SCHOOLS:' . implode(',', $this->allowed_schools));
    }

    /**
     * Displays the logo of the school where current user belongs to
     *
     * @return string html logo
    */
    function showLogo() {
        $instanceParameters = '&type=showschoollogo' . $this->tplInstParam;
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_show_school_logo.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $r = &$this->db->query("
            SELECT
                `module_isic_school`.`pic`
            FROM
                `module_isic_school`
            WHERE
                `module_isic_school`.`pic` <> '' AND
                `module_isic_school`.`id` IN (!@)
            ORDER BY
                `module_isic_school`.`name`
            LIMIT 1
            ",
            IsicDB::getIdsAsArray($this->allowed_schools)
        );

        if ($data = $r->fetch_assoc()) {
            $tpl->addDataItem("LOGO.PIC", IsicImage::getPictureUrlOrDummyUrlIfNotFound($data["pic"], 'thumb'));
        }

        return $tpl->parse();
    }

    /**
     * Displays the logo of the school where current user belongs to
     *
     * @return string html logo
    */
    function showList() {
        $instanceParameters = '&type=showschoollist' . $this->tplInstParam;
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_show_school_list.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $r = &$this->db->query("
            SELECT
                `module_isic_school`.`id`,
                `module_isic_school`.`name`,
                `module_isic_school`.`ehl_code`
            FROM
                `module_isic_school`
            WHERE
                `module_isic_school`.`id` IN (!@) AND
                (`module_isic_school`.`hidden` = 0 OR 'superadmin' = ?)
            ORDER BY
                `module_isic_school`.`name`
            ",
            IsicDB::getIdsAsArray($this->isic_common->allowed_schools_all),
            $this->isicDbUsers->isCurrentUserSuperAdmin() ? 'superadmin' : ''
        );

        $activeSchoolName = $this->txt->display('all_schools');
        $userModuleUrl = $this->isic_common->getGeneralUrlByTemplate(IsicTemplate::getModuleTemplateId('content_isic_user')) . '&action=active_school&school_id=';

        $tpl->addDataItem("SCHOOL.URL", $userModuleUrl . 0);
        $tpl->addDataItem("SCHOOL.NAME", $this->txt->display('all_schools'));
        $tpl->addDataItem("SCHOOL.TYPE", '1');
        $tpl->addDataItem("SCHOOL.CLASS", $this->user_active_school == 0 ? 'active' : '');

        $schoolCount = $r->num_rows();
        while ($data = $r->fetch_assoc()) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $class = '';
            if ($this->user_active_school == $data['id'] || $schoolCount == 1) {
                $activeSchoolName = $data['name'];
                $class = 'active';
            }
            $tpl->addDataItem("SCHOOL.URL", $userModuleUrl . $data["id"]);
            $tpl->addDataItem("SCHOOL.NAME", $data["name"]);
            $tpl->addDataItem("SCHOOL.TYPE", '');
            $tpl->addDataItem("SCHOOL.CLASS", $class);
        }
        $tpl->addDataItem("CURRENT_SCHOOL", $activeSchoolName);

        return $tpl->parse();
    }

    /**
     * Displays the logo of the school where current user belongs to
     *
     * @return string html logo
    */
    function showSupportAndFeedbackInfo() {
        $instanceParameters = '&type=showschoolsupportandfeedbackinfo' . $this->tplInstParam;
        $tpl = $this->isicTemplate->initTemplateInstance('module_isic_show_school_support.html', $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        $r = &$this->db->query("
            SELECT
                `module_isic_school`.`joined`,
                `module_isic_school`.`email_support`,
                `module_isic_school`.`phone_support`
            FROM
                `module_isic_school`
            WHERE
                `module_isic_school`.`id` IN (!@)
            ORDER BY
                `module_isic_school`.`name`
            LIMIT 1
            ",
            IsicDB::getIdsAsArray($this->allowed_schools)
        );

        $email_support = $this->txts->display('email_support');
        $phone_support = $this->txts->display('phone_support');

        if ($this->user_type == $this->user_type_user && $data = $r->fetch_assoc()) {
            if ($data['joined'] && $data['email_support']) {
                $email_support = $data['email_support'];
            }
            if ($data['joined'] && $data['phone_support']) {
                $phone_support = $data['phone_support'];
            }
        }

        $tpl->addDataItem("EMAIL_SUPPORT", $email_support);
        $tpl->addDataItem("PHONE_SUPPORT", $phone_support);

        $email_support_subject = $this->txt->display('email_support_subject');
        $email_support_body = $this->txt->display('email_support_body');
        $tpl->addDataItem("EMAIL_SUPPORT_SUBJECT", $this->getTextForUrl($email_support_subject));
        $tpl->addDataItem("EMAIL_SUPPORT_BODY", $this->getTextForUrl($this->getStringWithUserData($email_support_body)));

        $email_feedback_subject = $this->txt->display('email_feedback_subject');
        $email_feedback_body = $this->txt->display('email_feedback_body');
        $tpl->addDataItem("EMAIL_FEEDBACK_SUBJECT", $this->getTextForUrl($email_feedback_subject));
        $tpl->addDataItem("EMAIL_FEEDBACK_BODY", $this->getTextForUrl($this->getStringWithUserData($email_feedback_body)));

        return $tpl->parse();
    }

    function getTextForUrl($str) {
        return str_replace(array(" ", "\r", "\n"), array("%20", "%0D", "%0A"), $str);
    }

    function getStringWithUserData($str) {
        return str_replace(array('{EMAIL}', '{USER_CODE}', '{NAME}'), array($this->user_email, $this->user_code, $this->user_name), $str);
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
        $list = array();

        // ####
        return array();
        // name, type, list
    }
}
