<?php

/**
 * Database connectivety class for MySQL
 *
 * @package modera_net
 * @access public
 */
class DB
{

    /**
     * @var integer mysql database connection link identifier
     */
    var $con = false;
    /**
     * @var string db username
     */
    var $user = false;
    /**
     * @var string db password
     */
    var $pass = false;
    /**
     * @var string db hostname
     */
    var $host = false;
    /**
     * @var string db name
     */
    var $db = false;
    /**
     * @var boolean general debug from $GLOBALS["modera_debug"]
     */
    var $debug = false;
    /**
     * @var boolean sql debug from $GLOBALS["modera_debugsql"], will print out html comments with sql queries
     */
    var $debugsql = false;

    /** Class constructor
     */
    function DB()
    {
        if (!function_exists("mysqli_connect") || !extension_loaded('mysql')) {
            trigger_error("Mysql: Mysql support not found in the server. Cannot use object Mysql.", E_USER_ERROR);
        }
    }

    /** Connect to database. default method, connection data defined in the config.php
     * @return integer connection link identifier
     */

    function connect()
    {
        if ($this->check()) {
            return $this->con;
        } else {

            $this->user = DB_USER;
            $this->pass = DB_PASS;
            $this->host = DB_HOST;
            $this->db = DB_DB;
            $this->debug = $GLOBALS["modera_debug"];
            $this->debugsql = $GLOBALS["modera_debugsql"];

            $this->con = false;
            $this->con = mysqli_connect($this->host, $this->user, $this->pass);
//      echo "<h1>DB::connect()</h1>";
            mysqli_select_db($this->con, $this->db);
            if (!$this->con) {
                if ($this->debug == true) {
                    echo "\n\n<!-- ERROR connect: database connect failed\n" . mysqli_errno() . ": " . mysqli_error() . " -->\n\n";
                }
                trigger_error("Database connect failed !<br />\n" . mysqli_errno() . ": " . mysqli_error(), E_USER_ERROR);
                return false;
            } else {
                $version = $this->version($this->con);
                if (version_compare($version, '4.1', '>=')) {
                    mysqli_query($this->con, "SET NAMES 'utf8';");
                }

                if (version_compare($version, '5.0.2', '>=')) {
                    mysqli_query($this->con, "SET SESSION sql_mode=''");
                }
                return $this->con;
            }
        }
    }

    /** Connect to database with chosen connection parameters
     * @param string username
     * @param string password
     * @param string hostname
     * @param string database name
     * @return integer connection link identifier
     */

    function connect2($user, $pass, $host, $db)
    {
        if ($this->check()) {
            return $this->con;
        } else {

            if (!$user || !$pass || !$host || !$db) {
                trigger_error("Database connect2 parameters missing !", E_USER_ERROR);
            }

            $this->con = false;
            $this->con = mysqli_connect($host, $user, $pass);
//      echo "<h1>DB::connect2()</h1>";
            mysqli_select_db($this->con, $db);
            if (!$this->con) {
                if ($this->debug == true) {
                    echo "\n\n<!-- ERROR connect2: database connect failed \n" . mysqli_errno() . ": " . mysqli_error() . "-->\n\n";
                }
                trigger_error("Database connect failed !<br />\n" . mysqli_errno() . ": " . mysqli_error(), E_USER_ERROR);
                return false;
            } else {
                $version = $this->version($this->con);
                if (version_compare($version, '4.1', '>=')) {
                    mysqli_query($this->con, "SET NAMES 'utf8';");
                }

                if (version_compare($version, '5.0.2', '>=')) {
                    mysqli_query($this->con, "SET SESSION sql_mode=''");
                }
                return $this->con;
            }
        }
    }

    /**
     * Get Mysql version.
     * Connection must be established.
     *
     * @param resource - mysql resource. if not given, it will be set to current.
     * @return float|FALSE - returns version number on success, otherwise FALSE.
     */
    function version($con = null)
    {
        if (is_null($con)) $con = $this->con;

        $res = mysqli_query($con, "SELECT VERSION();");
        if (!$res) return false;
        // get result
        list($version) = mysqli_fetch_row($res);
        return $version;
    }

    /** Return connection link identifier
     * @return integer connection link identifier
     */

    function check()
    {
        return $this->con;
    }

    /** Disconnect from active connection
     */

    function disconnect()
    {
        if ($this->check()) {
            mysqli_close($this->con);
            $this->con = false;
        }
    }

}

/**
 * Query object
 *
 * @access public
 */
class sql extends DB
{

    /**
     * @var mixed query result resource
     */
    var $sth;
    /**
     * @var integer sql query
     */
    var $sql;
    /**
     * @var integer number of rows returned from last select
     */
    var $numrows;
    /**
     * @var array single row data
     */
    var $row;
    /**
     * @var integer current row in case of nextrow()
     */
    var $rownr = false;
    /**
     * @var integer database connection identifier
     */
    var $con;
    /**
     * @var boolean general debug from $GLOBALS["modera_debug"]
     */
    var $debug = false;
    /**
     * @var boolean sql debug from $GLOBALS["modera_debugsql"], will print out html comments with sql queries
     */
    var $debugsql = false;

    /** Constructor, set debug levels
     */

    function sql()
    {
        $this->debug = $GLOBALS["modera_debug"];
        $this->debugsql = $GLOBALS["modera_debugsql"];
    }

    /** Perform query
     * @param string database connection identifier
     * @param string sql query
     * @return mixed query result resource, false on failure
     */

    function query($con, $sql)
    {
        $sql = trim($sql);
        $this->con = $con;
        if ($this->con) {
            $this->sth = mysqli_query($con, $sql);
            if ($this->sth && (0 == strncasecmp('SELECT', $sql, 6)
                    || 0 == strncasecmp('SHOW', $sql, 4))
            ) {
                $this->numrows = mysqli_num_rows($this->sth);
                $this->rownr = 0;
            }
            if ($this->sth) {
                if ($this->debugsql == true) {
                    echo "\n\n<!-- SQL query debug: $sql -->\n\n";
                }
                return $this->sth;
            } else {
                if ($this->debug == true) {
                    echo "\n\n<!-- ERROR bad query: $sql \n" . mysqli_errno($this->con) . ": " . mysqli_error($this->con) . " -->\n\n";
                }
                trigger_error("Bad query $sql<br />\n" . mysqli_errno($this->con) . ": " . mysqli_error($this->con), E_USER_WARNING);
                return false;
            }
        } else {
            if ($this->debug == true) {
                echo "\n\n<!-- ERROR query: no database connection present -->\n\n";
            }
            return false;
        }
    }


    /** Return number of rows from last select
     * @return mixed number of rows on success, false on failure
     */

    function rows()
    {
        if ($this->con && $this->sth) {
            return $this->numrows;
        } else {
            if ($this->debug == true) {
                echo "\n\n<!-- ERROR rows: no query -->\n\n";
            }
            return false;
        }
    }

    /** Loop through select resultsset
     * @return mixed return single array row of data in case of success, false on failure
     */

    function nextrow()
    {

        if ($this->sth) {
            $this->row = mysqli_fetch_array($this->sth);
            $this->rownr++;
            return $this->row;
        } else {
            if ($this->debug == true) {
                echo "\n\n<!-- ERROR nextrow: no query or bad query -->\n\n";
            }
            return false;
        }
    }

    /** Fetch specific column from a given row
     * @param integer row number to fetch, 0 is the first
     * @param string columnname to fetch
     * @return mixed column data
     */

    function column($rownr, $column)
    {
        if ($this->sth && $rownr < $this->numrows && $rownr >= 0) {
            $col = $this->mysqli_result($this->sth, $rownr, $column);
            return $col;
        }
    }

    function mysqli_result($res, $row, $field=0) {
        $res->data_seek($row);
        $datarow = $res->fetch_array();
        return $datarow[$field];
    }

    /** Jump to specific row in the select return set
     * @param integer row number to jump to
     * @return mixed false on failure
     */

    function jumprow($jump)
    {
        $this->rownr = $jump;
        if ($this->sth && ($this->rownr < $this->numrows)) {
            mysqli_data_seek($this->sth, $this->rownr);
        } else {
            if ($this->debug == true) {
                echo "\n\n<!-- ERROR jumprow: row jump failed -->\n\n";
            }
            return false;
        }
    }

    /** Return last auto increment from last insert query
     * @return integer
     */

    function insertID()
    {
        $id = mysqli_insert_id($this->con);
        return $id;
    }

    /** Clear query resultset
     */

    function free()
    {
        if ($this->con && $this->sth) {
            mysqli_free_result($this->sth);
            $this->sth = false;
        }
    }

    /**
     * Get mysql server version
     *
     * @var int $con MySQL connection identifier
     * @return string
     */
    function version($con = null)
    {
        $r = mysqli_query($con, 'SELECT VERSION()');
        list($version) = mysqli_fetch_row($r);
        return $version;
    }

    /**
     * Get password function name
     *
     * For MySQL server versions < 4.1 it function will return PASSWORD, for
     * version >= 4.1 OLD_PASSWORD will be returned
     *
     * @param itn $con MySQL connection identifier
     * @return string
     */
    function pass_funct($con)
    {
        // check mysql version, if it is >= 4.1.0 we will use old_password function
        if (version_compare($this->version($con), '4.1', '>=')) {
            return 'OLD_PASSWORD';
        } else {
            return 'PASSWORD';
        }
    }
}
