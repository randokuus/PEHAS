<?php
/**
 * @version $Revision: 570 $
 * @package modera_net
 */

/**
 * Database result class for fetching query result
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class DatabaseResult
{
    /**
     * Database object
     *
     * @var Database
     */
    var $_database;

    /**
     * Database query resource
     *
     * @var resource
     */
    var $_result;

    /**
     * Class constructor
     *
     * @param resource $result
     * @param Database $database
     * @return DatabaseResult
     */
    function DatabaseResult($result, &$database)
    {
        $this->_result = $result;
        $this->_database =& $database;
    }

    /**
     * Get a result row as an enumerated array
     *
     * @return array|FALSE array of values, or FALSE if no more rows
     */
    function fetch_row()
    {
        return $this->_database->fetch_row($this->_result);
    }

    /**
     * Fetch a result row as an associative array
     *
     * @return array|FALSE associative array of values, or FALSE if no more rows
     */
    function fetch_assoc()
    {
        return $this->_database->fetch_assoc($this->_result);
    }

    /**
     * Fetch all data into array
     *
     * @return array
     */
    function fetch_all()
    {
        $data = array();
        while ($row = $this->_database->fetch_assoc($this->_result)) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Fetch first column into array
     *
     * @return array
     */
    function fetch_first_col()
    {
        $data = array();
        while ($row = $this->_database->fetch_row($this->_result)) {
            $data[] = $row[0];
        }
        return $data;
    }

    /**
     * Get number of rows in result
     *
     * @return int|FALSE number of rows in a result set on success, or FALSE on failure
     */
    function num_rows()
    {
        return $this->_database->num_rows($this->_result);
    }

    /**
     * Free result memory
     *
     * @return bool
     */
    function free()
    {
        return $this->_database->free($this->_result);
    }
}
