<?php

/**
 * Administration session management
 *
 * @package modera_net
 * @access public
 */

class Session {

/**
 * @var string session id
 */
var $sid = false;
/**
 * @var boolean true/false - user logged in
 */
var $status = false;
/**
 * @var integer user ID
 */
var $user = false;
/**
 * Logged in username
 *
 * @var string
 * @access private
 */
var $_username = null;
/**
 * @var integer group ID
 */
var $group = false;
/**
 * @var array integer session timeout in seconds
 */
var $timeou = 3600; // timeout in seconds
/**
 * @var integer database connection resource
 */
var $dbc = false;
/**
 * @var integer binded user ID
 */
var $bind_user = false;

    /** Constructor
     */

    function Session() {

        $GLOBLAS["perm_group"] = unserialize(MODERA_PERM_GROUP);
        $GLOBLAS["perm_other"] = unserialize(MODERA_PERM_OTHER);
        $GLOBLAS["perm_module_group"] = unserialize(MODERA_PERM_MODULE_GROUP);
        $GLOBLAS["perm_file_group"] = unserialize(MODERA_PERM_FILE_GROUP);

        //DOUBLE CHECK site path
        if (SITE_PATH == "" || SITE_PATH == "{LOCATION_SYS}") {
            trigger_error("WARNING! Site path under config is undefined", E_USER_ERROR);
            exit;
        }
        else {
            if (!checkSitePath()) {
                trigger_error("WARNING! Attempt to access paths other than the current document root. Check Site path under config.", E_USER_ERROR);
                exit;
            }
        }

        $db = new DB;
        $this->dbc = $db->connect();

        $ADM_SESS_SID = $_COOKIE["ADM_SESS_SID"];
        if ($ADM_SESS_SID != "") {
            $sq = new sql;
            $sq->query($this->dbc, "SELECT `sid` FROM `adm_session` WHERE `sid` = '".addslashes($ADM_SESS_SID)
                . "' AND (UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(`access`)) < ".$this->timeou." LIMIT 1");
            if ($sq->numrows > 0) {
                $this->sid = $ADM_SESS_SID;
                $this->status = true;
                $sq->query($this->dbc, "UPDATE `adm_session` SET `access` = now() WHERE `sid` = '$ADM_SESS_SID'");
            }
            else {
                $this->sid = false;
                $this->status = false;
            }
        }
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

    /** Creates a unique Session ID
     * @access private
     * @return integer session ID
     */

    function initSid() {
        $sid = md5 (uniqid (rand()));
        $this->sid = $sid;
        return $this->sid;
    }

    /** Authorise user, create new session
     * @param string username
     * @param string user password
     * @return mixed false on failure, string session ID on success
    */

    function setSession($username, $password) {
        $username = addslashes($username);
        $password = addslashes($password);
        $sq = new sql;

        // check mysql version, if it is >= 4.1.0 we will use old_password function
        $hash_funct = $sq->pass_funct($this->dbc);
        $sq->query($this->dbc, "SELECT `user`, `ggroup`, `ips` FROM `adm_user` WHERE `username` "
          ." = '$username' AND `password` = $hash_funct('$password') AND `active` = '1'");

        $sq->con = $this->dbc;
        $database = new Database($sq);
        $log = &SystemLog::instance($database);

        if ($sq->numrows > 0) {
            $this->ipcheck = $this->checkIP($sq->column(0, "ips"));
            $this->sid = $this->initSid();
            $this->user = $sq->column(0, "user");
            $this->group = $sq->column(0, "ggroup");
            $sq->query($this->dbc, "INSERT INTO `adm_session` values(null, '" . $this->sid . "', now(), '"
                . $this->user . "','" . $_SERVER["REMOTE_ADDR"] . "', now())");
            setcookie("ADM_SESS_SID", $this->sid, 0, $this->_engine_url(), '.' . COOKIE_URL, ((COOKIE_SECURE === true || COOKIE_SECURE === "true" || COOKIE_SECURE === 1 || COOKIE_SECURE === "1") ? true : false));
            // Write to systemlog info about login user
            $log->log('admin_login', 'Admin user: "' . $username . '" logged into the system.');
            return $this->sid;
        }
        else {
            // Write to systemlog info about login attempt
            $log->log('admin_login', 'Unsuccessful attempt to login of Admin user: "' . $username . '".');
            return false;
        }
    }

    /** Check does the user Ip match defined IP-s for the user
     * @param string Ip's, comma separated
     * @access private
     * @return boolean
    */

    function checkIP ($ips) {

        if ($ips) {
            $a = split(",", $ips);
            if (in_array($_SERVER["REMOTE_ADDR"], $a)) {
                return true;
            }
            else {
                $this->displayError("403", "main");
            }
        }
        else {
            return true;
        }

    }

    /** Returns the user ID from the currently active session. set other user parameters to class variables
     * @return integer user ID
     */

    function returnUser() {
        if ($this->sid) {
            $sq = new sql;

            $sql = "SELECT
                            `s`.`user`, `u`.`ggroup`, `u`.`user_id`
                         FROM
                            `adm_session` as `s`
                            LEFT JOIN `adm_user` AS `u`
                            ON `s`.`user` = `u`.`user`
                         WHERE
                            `s`.`sid` = '" . $this->sid . "' LIMIT 1";

            $sq->query($this->dbc, $sql);
            $this->user = $sq->column(0, "user");
            $this->group = $sq->column(0, "ggroup");
            $this->bind_user = $sq->column(0, "user_id");
            $sq->free();
        }
        return $this->user;
    }


    /**
     * Get binded regular user
     *
     * @return int
     */
    function getBindUser() {
        return $this->bind_user;
    }

    /**
     * Get name of logged in admin user
     *
     * @return string|FALSE
     */
    function getUsername() {

        if (null === $this->_username) {

            if (!$this->user && $this->sid && !$this->returnUser()) {
                // unable to get logged in user
                return false;
            }

            $sq = new sql();
            $sql = 'SELECT `username` FROM `adm_user` WHERE `user` = '
                . (int) $this->user;

            $sq->query($this->dbc, $sql);

            if ($sq->numrows > 0) {
                $this->_username = $sq->column(0, 'username');
            } else {
                $this->_username = false;
            }
            $sq->free();
        }

        return $this->_username;
    }

    /** Returns session ID from active session.
     *  @return string
     */

    function returnID() {
        return $this->sid;
    }


    /** Logs out the currently logged in user.
     */

    function logOut() {
        if ($this->sid) {
            setcookie('ADM_SESS_SID', '', 0, $this->_engine_url(), '.' . COOKIE_URL, COOKIE_SECURE);
            $this->sid = false;
        }
    }

    /** redirect to the error page, exit.
     */

    function displayError($code, $frame) {
        redirect("error.php?error=$code");
        exit;
    }

}

// ##################
// ##################

/**
 * Administration user permissions. Will act based on chosen rights in the administration interface, will use default
 * permissions standards.
 *
 * @access public
 */

class Rights {

/**
 * @var string session id
 */
var $sid = false;
/**
 * @var integer user id
 */
var $user = false;
/**
 * @var integer group id
 */
var $group = false;
/**
 * @var integer database connection resource
 */
var $dbc = false;
/**
 * @var string check based on level (structure,content,module,root)
 */
var $level = false;
/**
 * @var string active content language
 */
var $language = false;
/**
 * @var integer root group ID
 */
var $root = 1;
/**
 * @var boolean true - show errors, eg. redirect to error page on failure
 */
var $show_error = false;

    /** Constructor for the class
     * @param integer group id
     * @param integer user id
     * @param string level to check (structure,content,module,root)
     * @param boolean show errors to the user or not
     */

    function Rights($group, $user, $level, $show) {
        global $adm, $language;

        if ($adm->dbc) $this->dbc = $adm->dbc;
        else {
            $db = new DB;
            $this->dbc = $db->connect();
        }

        $this->group = $group;
        $this->user = $user;
        $this->level = $level;
        $this->language = $language;
        if ($show) {
            $this->show_error = $show;
        }
    }

    /** Check access to resource
     * @param string structure ID
     * @param integer content id
     * @param string action a - add, m - modify,d - delete
     * @param string module name
     * @return boolean
     */
    function Access ($structure, $id, $action, $module) {
        // Root group users have FULL access, We only check other users
        if ($this->group != $this->root) {
            switch( $this->level ) {
            case "structure":
                $asc = $this->Structure($structure, $id, $action);
                break;
            case "content":
            case "trash":
            case "template":
                $asc = $this->PageNode($id, $this->level, $action);
                break;
            case "module":
                $asc = $this->Module($module, $action, $id);
                break;
            case "root":
                $asc = $this->Root();
                break;
            }

            return $asc;
        }
        else {
            return true;
        }
    }

    /** Check does the user have root access
     * @return boolean
     */
    function Root () {
        if ($this->group != $this->root) {
            $access = false;
        }
        else {
            $access = true;
        }

        if ($access == false && $this->show_error == true) $this->displayError(403, "right");

        return $access;
    }

    /**
     * Check access to structure element
     *
     * NB! Not used anymore, left for backward compatibility
     */
    function Structure()
    {
        return true;
    }

    /**
     * Check access to page node
     *
     * @param int $node_id
     * @param string $node_type trash/template/content
     * @param string $action one of the "a" - add, "m" - modify and "d" - delete
     * @return bool
     */
    function PageNode($node_id, $node_type, $action)
    {
        if (!$node_id) {
            // exception case, node_id is 0 or NULL when root node created
            // or new template is created
            return true;
        }

        // table name by node type
        $map = array(
            "trash" => "content_trash",
            "template" => "content_templates",
        );

        $table = $map[$node_type] ? $map[$node_type] : "content";

        $db =& $this->_database();
        $row = $db->fetch_first_row("
            SELECT
                `c`.`perm_group`
                , `c`.`perm_other`
                , `c`.`owner`
                , `u`.`ggroup`
            FROM
                ?f as `c`
                , `adm_user` as `u`
            WHERE
                `c`.`owner` = `u`.`user`
                AND `c`.`content` = ?
            ", $table, $node_id);

        if (!$row) {
            $access = false;
        } else {

            if ($row["owner"] == $this->user) {
                // owner get full access
                $access = true;

            } else {

                $subject = ($row["ggroup"] == $this->group) ? "perm_group" : "perm_other";
                $permission = split(",", $row[$subject]);
                if (3 != count($permission)) {
                    $permission = array(
                        $GLOBALS[$subject]["a"],
                        $GLOBALS[$subject]["m"],
                        $GLOBALS[$subject]["d"],
                    );
                }

                // map $action to appropriate permission array index
                $index = array_search($action, array("a", "m", "d"));
                $access = (bool)$permission[$index];
            }
        }

        if (!$access && $this->show_error) {
            $this->displayError(403, "right");
        } else {
            return $access;
        }
    }

    /**
     * Check access to content page
     *
     * @param mixed $_dummy structure parameter in previous implementation,
     *  left here for backward compatibility
     * @param int $content content id
     * @param string $action one of the "a" - add, "m" - modify and "d" - delete
     * @return bool
     */
    function Content($_dummy, $content, $action)
    {
        return $this->PageNode($content, "content", $action);
    }

    /**
     * Check access to a modue
     *
     * @param string $module module name
     * @param string $action one of the "a" - add, "m" - modify and "d" - delete
     * @param int $id content page id
     * @return bool
     */
    function Module($module, $action, $id)
    {

        if ("folderaccess" == $module) {
            $accesstable = "folders";
            $module = "fileaccess";
        } elseif ("fileaccess" == $module) {
            $accesstable = "files";
        }

        $db = &$this->_database();
        $perm_module = $db->fetch_first_value("SELECT `perm_module` FROM `adm_group`"
            . " WHERE `ggroup` = ?", $this->group);

        if ($perm_module) {
            foreach (split(";", $perm_module) as $moduleperm) {
                list($m, $p) = split("=", $moduleperm);
                if ($module == $m) {
                    $permission = split(",", $p);
                    if (3 != count($permission)) {
                        $permission = array();
                    }
                    break;
                }
            }
        } else {
            if ("fileaccess" == $module) {
                $permission = array(
                    $GLOBALS["perm_file_group"]["a"],
                    $GLOBALS["perm_file_group"]["m"],
                    $GLOBALS["perm_file_group"]["d"],
                );
            } else {
                $permission = array(
                    $GLOBALS["perm_module_group"]["a"],
                    $GLOBALS["perm_module_group"]["m"],
                    $GLOBALS["perm_module_group"]["d"],
                );
            }
        }

        // Check "fileaccess" owner
        if ($id && "fileaccess" == $module) {
            $owner = $db->fetch_first_value("SELECT `owner` FROM `$accesstable` WHERE `id` = ?"
                , $id);

            if (false !== $owner && $owner == $this->user) {
                $permission[1] = 1;
                $permission[2] = 1;
            }
        }

        // map $action to appropriate permission array index
        $index = array_search($action, array("a", "m", "d"));
        $access = (bool)$permission[$index];

        if (!$access && $this->show_error) {
            $this->displayError(403, "right");
        } else {
            return $access;
        }
    }

    /**
     * Check if user has publish permission
     *
     * @return bool
     */
    function canPublish()
    {
        // in standard non-professional version of software every user has
        // permission to publish content
        if (!pro_version()) {
            return true;
        }

        // root users always have publish permission
        if ($this->group == $this->root) {
            return true;
        }

        if (!$this->user) {
            // user has not been retrived yet, assume that he doesnt have permission
            return false;
        }

        $db = &$this->_database();

        // check if user has personal publish permission
        $can_publish = $db->fetch_first_value('SELECT `can_publish` FROM `adm_user`'
            . ' WHERE `user` = ?', $this->user);
        if ($can_publish) {
            return true;
        }

        // check if user group has publish permission
        $perm_module = $db->fetch_first_value('SELECT `perm_module`'
            . ' FROM `adm_group` WHERE `ggroup` = ?', $this->group);

        if ($perm_module) {
            foreach(explode(';', $perm_module) as $perm) {
                list($module, $perm_value) =  explode('=', $perm);
                if ('perm_contentpublish' == $module) {
                    return (bool) $perm_value;
                }
            }
        }

        return false;
    }

    /**
     * Get database class instance
     *
     * @return Database
     */
    function &_database()
    {
        if (!isset($GLOBALS["database"])) {
            $sql = new sql();
            $sql->con = $this->dbc;
            $database = new Database($sql);
        } else {
            $database =& $GLOBALS["database"];
        }

        return $database;
    }

    /**
     * Redirect to error page
     *
     * @param int $code http error code
     * @access private
     */
    function displayError($code) {
        redirect("error.php?error=$code");
        exit;
    }
}