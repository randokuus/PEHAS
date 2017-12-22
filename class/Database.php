<?php
/**
 * @version $Revision: 1036 $
 * @package modera_net
 */

require_once(SITE_PATH . "/class/Placeholder.php");
require_once(SITE_PATH . "/class/DatabaseResult.php");

/**
 * Database class
 *
 * Used as a wrapper for modera's standard database class DB. Adds placeholders
 * support for sql queries and some usefull methods simplifying work with database.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Database
{
    /**
     * Sql class instance
     *
     * @var sql
     * @access protected
     */
    var $_db;

    /**
     * Placeholder object
     *
     * @var Placeholder
     * @access protected
     */
    var $_placeholder;

    /**
     * SQL of the most recently executed query
     *
     * @var string
     */
    var $_last_sql;

    /**
     * @param sql $db object should contain valid mysql link resource in $sql->con
     *  variable, so if needed put it manually: "$s =& new sql(); $s->con = $adm->dbc;"
     */
    function Database(&$sql)
    {
        $this->_db =& $sql;
    }

    /**
     * Get placeholder object
     *
     * Creates placeholder object on first request.
     *
     * @return Placeholder
     */
    function &placeholder()
    {
        if (is_null(@$this->_placeholder)) {
            $this->_placeholder = new Placeholder($this);
        }

        return $this->_placeholder;
    }

    /**
     * Escapes string before using it into sql query
     *
     * @param string $value string to escape
     * @return string escaped string
     * @access protected
     */
    function _escape_string($value)
    {
        // for escaping we prefer mysqli_real_escape_string() function because it
        // can take database link identifier and perform escaping taking into
        // account the current character set of the connection if no such function
        // exists (old php) we use mysqli_escape_string() for escaping
        if (function_exists('mysqli_real_escape_string')) {
            return mysqli_real_escape_string($this->link(), $value);
        }
        else {
            return mysqli_escape_string($this->link(), $value);
        }
    }

    /**
     * Executes database query
     *
     * @param string $sql
     * @param array $args array of parameters used in query
     * @return DatabaseResult|FALSE
     * @access protected
     */
    function _real_query($sql, $args)
    {
        // use placehoder object only if we received at least one item in args array
        if (count($args) > 0) {
            $p = $this->placeholder();
            $sql = $p->prepare($sql, $args);
        }

        $this->_last_sql = $sql;

        // execute query
        $result = $this->_db->query($this->link(), $sql);

        if ($result === true || $result === false) {
            return $result;
        }
        $res = new DatabaseResult($result, $this);
        return $res;
    }

    /**
     * Get mysql connection link identifier
     *
     * @return integer mysql connection link identifier
     */
    function link()
    {
        return $this->_db->check();
    }

    /**
     * Quote field name
     *
     * Method understand complex field names containing database and table names,
     * for example: `my_database.my_table.my_field_name`
     *
     * @param string $field_name
     * @return string
     */
    function quote_field_name($field_name)
    {
        $fields = array();
        foreach (explode('.', $field_name) as $cur_field_name) {
            $fields[] = '`' . $this->_escape_string(strtr($cur_field_name
                , array('`'=>''))) . '`';
        }

        return implode('.', $fields);
    }

    /**
     * Quote value before using it in sql query
     *
     * In the backend method uses native mysql library escaping functions, so it
     * should be safe to use this method for escaping any data including binary.
     *
     * @param mixed $value
     * @return string
     */
    function quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        elseif (is_bool($value)) {
            return $value ? '1' : '0';
        }
        else {
            return "'" . $this->_escape_string($value) . "'";
        }
    }

    /**
     * Escape placeholders to make them invisible by placeholder processor
     *
     * Method is useful when one part of the query is created by user while another part
     * created with help of placeholder processor
     *
     * @param string $value
     * @return string
     */
    function escape_placeholders($value)
    {
        $p =& $this->placeholder();
        return $p->escape_placeholders($value);
    }

    /**
     * Get last database error description
     *
     * @return string
     * @link http://www.php.net/mysqli_error
     */
    function error_string()
    {
        return mysqli_error($this->link());
    }

    /**
     * Get last database error code
     *
     * @return int error number, or 0 if no error occured
     * @link http://www.php.net/mysqli_errno
     */
    function error_code()
    {
        return mysqli_errno($this->link());
    }

    /**
     * Get number of affected rows in previous databse INSERT|UPDATE|DELETE query
     *
     * @return int
     * @link http://www.php.net/mysqli_affected_rows
     */
    function affected_rows()
    {
        return mysqli_affected_rows($this->link());
    }

    /**
     * Get the ID generated from the previous INSERT operation
     *
     * @return int 0 if no records was created, or created AUTO_INCREMENT column id
     * @link http://www.php.net/mysqli_insert_id
     */
    function insert_id()
    {
        return mysqli_insert_id($this->link());
    }

    /**
     * Get a result row as an enumerated array
     *
     * @param resource $result
     * @return array|FALSE array of values, or FALSE if no more rows
     * @link http://www.php.net/mysqli_fetch_row
     */
    function fetch_row($result)
    {
        return mysqli_fetch_row($result);
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param resource $result
     * @return array|FALSE associative array of values, or FALSE if no more rows
     * @link http://www.php.net/mysqli_fetch_assoc
     */
    function fetch_assoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    /**
     * Get number of rows in result
     *
     * @param resource $result
     * @return int|FALSE number of rows in a result set on success, or FALSE on failure
     * @link http://www.php.net/mysqli_num_rows
     */
    function num_rows($result)
    {
        return mysqli_num_rows($result);
    }

    /**
     * Free result memory
     *
     * @param resource $result
     * @return bool
     * @link http://www.php.net/mysqli_free_result
     */
    function free($result)
    {
        return mysqli_free_result($result);
    }

    /**
     * Get current timestamp in database related format
     *
     * @return string
     */
    function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Executes database query
     *
     * If methods receives more than one parameter it's supposed that data
     * to replace placeholders is passed and query will be prepared with
     * {@link Placeholder::prepare()}
     *
     * @param string $sql
     * @param mixed $arg,.. variable number of parameters used in query
     * @return DatabaseResult|FALSE
     */
    function query($sql)
    {
        $args = func_get_args();
        array_shift($args);

        $res = $this->_real_query($sql, $args);
        return $res;
    }

    /**
     * Show the latest sql query executed or prepared if any parameters are passed
     *
     * @see query()
     * @param string $sql
     * @param mixed $arg,.. variable number of parameters used in query
     * @return string|NULL
     */
    function show_query($sql = null)
    {
        if (null === $sql) {
            $sql = $this->_last_sql;

        } else {
            $args = func_get_args();
            array_shift($args);

            if (count($args) > 0) {
                $p =& $this->placeholder();
                $sql = $p->prepare($sql, $args);
            }
        }

        return $sql;
    }

    /**
     * Executes query and returns first row as an associative array
     *
     * @param string $sql sql query with placeholders
     * @param mixed $arg,.. variable number of parameters used in query
     * @return array|FALSE row array, or FALSE of error happend
     * @see Placeholder::prepare()
     */
    function fetch_first_row($sql)
    {
        $args = func_get_args();
        array_shift($args);

        if (false !== ($result =& $this->_real_query($sql, $args))) {
            $row = $result->fetch_assoc();
        }
        else {
            return false;
        }

        return $row;
    }

    /**
     * Executes query and returnes first value from first row
     *
     * @param string $sql
     * @param mixed $arg,.. variable number of parameters used in query
     * @return string|FALSE
     * @see Placeholder::prepare()
     */
    function fetch_first_value($sql)
    {
        $args = func_get_args();
        array_shift($args);

        if (false !== ($result =& $this->_real_query($sql, $args))
            && $row = $result->fetch_row())
        {
            $value = current($row);
        }
        else {
            return false;
        }

        return $value;
    }

    /**
     * Query database and return all resulting data as multidimensional associative array
     *
     * @param string $sql
     * @param mixed $arg,.. variable number of parameters used in query
     * @return array|FALSE
     */
    function fetch_all($sql)
    {
        $args = func_get_args();
        array_shift($args);

        if (false === ($result =& $this->_real_query($sql, $args))) {
            return false;
        }

        return $result->fetch_all();
    }

    /**
     * Query database and return first column in result set as array
     *
     * @param string $sql
     * @param mixed $arg,.. variable number of parameters used in query
     * @return array|FALSE
     */
    function fetch_first_col($sql)
    {
        $args = func_get_args();
        array_shift($args);

        if (false === ($result =& $this->_real_query($sql, $args))) {
            return false;
        }

        return $result->fetch_first_col();
    }

    /**
     * Check if table exists
     *
     * @param string $table
     * @return bool
     * @staticvar $cache cache previous calls
     */
    function table_exists($table, $usecache = true)
    {
        static $cache = array();

        if (!$usecache || !array_key_exists($table, $cache)) {
            if (false !== ($result =& $this->_real_query('SHOW TABLES LIKE ?', array($table)))) {
                $cache[$table] = (bool)$result->num_rows();
            } else {
                return false;
            }
        }

        return $cache[$table];
    }

    /**
     * Get mysql server version
     *
     * @return string
     */
	function version()
	{
	    return $this->_db->version($this->link());
    }
}
