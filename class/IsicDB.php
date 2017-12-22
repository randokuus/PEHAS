<?php

// version check for array_intersect_key(), PHP 5 objects and exceptions
if(version_compare(phpversion(), '5.1.0', '<')) {
    trigger_error('PHP 5.1.0 or newer is required', E_USER_ERROR);
}

require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FactoryPattern.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB_Debug.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicMail.php");

class IsicDB {

    const OFFSET = 0;
    const LIMIT = 65535;

    protected $table = false;
    protected $primary = 'id';
    protected $orderBy = 'name';
    protected $insertableFields = false;
    protected $updateableFields = false;
    protected $searchableFields = false;
    protected $requiredFields = array('add' => false, 'modify' => false);

    /**
     * @var Database
     */
    protected $db;
    protected $language;
    protected $userid;
    protected $usergroup;
    protected $usergroups;
    protected $user_type;
    protected $user_code;

    private static $factoryCache = array();

   /**
     * Class constructor
     *
     * @global $GLOBALS['user_data']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */
    public function __construct() {
        $this->db = $GLOBALS['database'];
        $this->language = $GLOBALS['language'];
        $this->userid = $GLOBALS["user_data"][0];
        $this->usergroup = $GLOBALS["user_data"][4];
        $this->usergroups = $GLOBALS["user_data"][5];
        $this->user_type = $GLOBALS["user_data"][6];
        $this->user_code = $GLOBALS["user_data"][7];

        if (!$this->table) {
            IsicDB_Debug::throwException('Table is not defined for ' . get_class($this), 101);
        }
        if (!is_array($this->insertableFields)) {
            IsicDB_Debug::throwException('An array of insertable fields is not defined for ' . get_class($this), 102);
        }
        if (!is_array($this->updateableFields)) {
            IsicDB_Debug::throwException('An array of updateable fields is not defined for ' . get_class($this), 103);
        }
        if (!is_array($this->searchableFields)) {
            IsicDB_Debug::throwException('An array of searchable fields is not defined for ' . get_class($this), 104);
        }
    }

    /**
     * Get available IsicDB classes
     * @access public
     * @return array
     */
    public static function available() {
        static $available = null;
        if (is_null($available)) {
            $available = FactoryPattern::available('IsicDB', dirname(__FILE__));
        }
        return $available;
    }

    public static function clearFactoryCache() {
        self::$factoryCache = array();
    }

    /**
     * Create an instance of a singleton IsicDB class
     * @access public
     * @param string
     * @return object
     * @throws IsicDB_Exception
     */
    public static function factory($name) {
        if (isset(self::$factoryCache[$name])) {
            if (!is_object(self::$factoryCache[$name])) {
                IsicDB_Debug::throwException("Class constructors recursion detected", 106);
            }
            return self::$factoryCache[$name];
        }
        self::$factoryCache = false;
        $obj = FactoryPattern::factory('IsicDB', $name, dirname(__FILE__));
        if (!is_object($obj)) {
            unset(self::$factoryCache[$name]);
        	IsicDB_Debug::throwException("Class not found: IsicDB_$name", 105);
        }
        return self::$factoryCache[$name] = $obj;
    }

    /**
     * Returns a table name of this class
     *
     * @return string table name
    */
    public function getTable() {
        return $this->table;
    }

    /**
     * Returns a table name of this class quoted for query
     *
     * @return string quoted table name
    */
    final public function getTableQuoted() {
        return $this->db->quote_field_name($this->getTable());
    }

    /**
     * Returns a primary key for a table of this class
     *
     * @return string primary key field name
    */
    public function getPrimaryKey() {
        return $this->primary;
    }

    // deprecated, use the above
    final public function getPK() {
        return $this->getPrimaryKey();
    }

    /**
     * Returns a quoted primary key for a table of this class
     *
     * @return string quoted primary key field name
    */
    final public function getPrimaryKeyQuoted() {
        return $this->db->quote_field_name($this->getPrimaryKey());
    }

    /**
     * Returns a base query for SQL which may be easily overwritten on need
     * Please not, the returned SQL should not contain any placeholders!
     * No mandatory method parameters may be used in derivative classes!
     *
     * @return string SQL query
    */
    public function getBaseQuery() {
        $t = $this->getTableQuoted();
        return "SELECT $t.* FROM $t ";
    }

    /**
     * Returns a record
     *
     * @param int|string $id record id
     * @return array record data
    */
    public function getRecord($id) {
        $r = $this->db->query(
            $this->getBaseQuery() . " WHERE ?f.?f = ? LIMIT 1",
            $this->getTable(),
            $this->getPrimaryKey(),
            $id
        );
        if (!is_object($r)) {
            IsicDB_Debug::throwException('Get query failed: ' . $this->db->error_string(), 201);
        }
        return $r->fetch_assoc();
    }

    /**
     * Lists available records
     *
     * @param int offset
     * @param int limit
     * @return array array of records
    */
    public function listRecords($offset = self::OFFSET, $limit = self::LIMIT) {
        $r = $this->db->query(
            $this->getBaseQuery() . " LIMIT !, !",
            intval($offset),
            intval($limit)
        );
        if (!is_object($r)) {
            IsicDB_Debug::throwException('List query failed: ' . $this->db->error_string(), 202);
        }
        return $r->fetch_all();
    }

    private function generateSearchFilters(array $fields) {
        $fields = array_intersect_key(
            $fields, array_flip((array)$this->searchableFields)
        );
        $filters = array();
        foreach ($fields as $fieldName => $fieldValue) {
            $filters[] = $this->getTableQuoted() . '.'
                . $this->db->quote_field_name($fieldName)
                . ' = ' . $this->db->quote($fieldValue);
        }
        return count($filters) == 0 ? '1' : implode(" AND ", $filters);
    }

    /**
     * Searches for records with matching fields
     *
     * @param array array of AND'ed conditions
     * @param int offset
     * @param int limit
     * @return array array of records
    */
    public function findRecords(array $fields, $offset = self::OFFSET, $limit = self::LIMIT) {
        $r = $this->db->query(
            $this->getBaseQuery() . " WHERE ! LIMIT !, !",
            $this->generateSearchFilters($fields),
            intval($offset),
            intval($limit)
        );
        if (!is_object($r)) {
            IsicDB_Debug::throwException('Search query failed: ' . $this->db->error_string(), 203);
        }
        return $r->fetch_all();
    }

    /**
     * Searches for a record with matching fields
     *
     * @param array array of AND'ed conditions
     * @param string|null $orderBy a field to order the result by before selecting the record
     * @return array record data
    */
    public function findRecord(array $fields, $orderByField = null, $orderByTable = null, $orderDirection = 'ASC') {
        $orderTableSql = $orderByTable ? $this->db->quote_field_name($orderByTable) . '.' : '';
        $orderSql = $orderByField ? 'ORDER BY ' . $orderTableSql . $this->db->quote_field_name($orderByField) . ' ' . $orderDirection : '';
        $r = $this->db->query(
            $this->getBaseQuery() . " WHERE ! ! LIMIT 1",
            $this->generateSearchFilters($fields),
            $orderSql
        );
        if (!is_object($r)) {
            IsicDB_Debug::throwException('Search query failed: ' . $this->db->error_string(), 203);
        }
        return $r->fetch_assoc();
    }

    /**
     * Insert a record
     *
     * @param array $data record data
     * @return int|boolean record's autoincrement field's value on success
    */
    public function insertRecord(array $data) {
        $data = array_intersect_key($data, array_flip((array)$this->insertableFields));
        $r = $this->db->query(
            "INSERT INTO ?f (?@f) VALUES (?@)",
            $this->getTable(),
            array_keys($data),
            array_values($data)
        );
        if ($r === false) {
            IsicDB_Debug::throwException('Insert query failed: ' . $this->db->error_string(), 301);
        }
        return $this->db->insert_id();
    }

    /**
     * Update a record
     *
     * @param int|string $id record id
     * @param array $data record data
    */
    public function updateRecord($id, array $data) {
        $data = array_intersect_key($data, array_flip((array)$this->updateableFields));
        if (count($data) == 0) {
            IsicDB_Debug::throwException('Data contains no fields allowed for update', 402);
        }
        $r = $this->db->query(
            "UPDATE ?f SET ?% WHERE ?f = ?",
            $this->getTable(),
            $data,
            $this->getPrimaryKey(),
            $id
        );
        if ($r === false) {
            IsicDB_Debug::throwException('Update query failed: ' . $this->db->error_string(), 401);
        }
    }

    /**
     * Delete a record
     *
     * @param int|string $id record id
    */
    public function deleteRecord($id) {
        $r = $this->db->query(
            "DELETE FROM ?f WHERE ?f = ?",
            $this->getTable(),
            $this->getPrimaryKey(),
            $id
        );
        if ($r === false) {
            IsicDB_Debug::throwException('Delete query failed: ' . $this->db->error_string(), 501);
        }
        if ($this->db->affected_rows() == 0) {
            IsicDB_Debug::throwException('Record not found', 502);
        }
    }

    /**
     * @return the $requiredFields
     */
    public function getRequiredFields($action) {
        return $this->requiredFields[$action];
    }

    public function getIdsAsArray($ids) {
        if (is_array($ids)) {
            if ($ids[0] == null) {
                return array(-1);
            }
            return $ids;
        }
        if (strpos($ids, ',') !== false) {
            return explode(',', $ids);
        }
        return ($ids ? array($ids) : array(-1));
    }

    public function listRecordsFields($fields, $orderby = 'name') {
        $r = $this->db->query("
            SELECT
                ?@f
            FROM
                ?f AS `s`
            ORDER BY
                `s`.?f",
            $fields,
            $this->table,
            $orderby
        );
        if (!is_object($r)) {
            IsicDB_Debug::throwException('Fields list query failed: ' . $this->db->error_string(), 205);
        }
        return $r->fetch_all();
    }

    function getRecordsByIds($ids, $orderBy = null) {
        $res = &$this->db->query(
            $this->getBaseQuery() . " WHERE ?f.?f IN (!@) ORDER BY !",
            $this->getTable(),
            $this->getPrimaryKey(),
            $this->getIdsAsArray($ids),
            $orderBy
                ? $this->db->quote_field_name($orderBy)
                : $this->getTableQuoted() . "." . $this->db->quote_field_name($this->orderBy)
        );
        if (!is_object($res)) {
            IsicDB_Debug::throwException('Multiple get query failed: ' . $this->db->error_string(), 204);
        }
        return $res->fetch_all();
    }

    /**
     * Asserts that a custom query has succeeded
     * @param object $databaseResult a result retrieved from Database::query()
     * @throws IsicDB_Exception exception is thrown if query was a failure
    */
    final public function assertResult($databaseResult) {
        self::assertCustomResult($databaseResult, $this->db);
    }

    /**
     * Asserts that a custom query on a custom database has succeeded
     * @param object $databaseResult a result retrieved from Database::query()
     * @param object $database a Database object
     * @throws IsicDB_Exception exception is thrown if query was a failure
    */
    final public static function assertCustomResult($databaseResult, $database) {
        if(!($database instanceof Database)) {
            IsicDB_Debug::throwException('Invalid database object', 603);
        }
        if(!($databaseResult instanceof DatabaseResult)) {
            IsicDB_Debug::throwException(
                'Custom query failed: ' . $database->error_string() . ' in "' . $database->show_query() . '"', 601, 2
            );
        }
    }

    /**
     * Asserts that conditions are true and throws an exception otherwise
     * @param boolean $truth condition value to check
     * @param string $description assertion additional description
     * @throws IsicDB_Exception exception is thrown if query was a failure
    */
    final public static function assert($truth, $description = '') {
        if (!(bool)$truth) {
            IsicDB_Debug::throwException('Assertion failed' . ($description ? " ($description)" : ""), 602);
        }
    }

    /**
     * Saves a number of parameters for later dump and
     * send for a convinient unobtrusive debugging
     * (uses DEVELOPERS_EMAILS constant you could define in config.php)
     *
     * @param mixed any data
     * @param mixed ...
    */
    final public static function dump() {
        IsicDB_Debug::dump(func_get_args(), 2);
    }

	/**
     * @return the $userid
     */
    public function getUserid() {
        return $this->userid;
    }

	/**
     * @param $userid the $userid to set
     */
    public function setUserid($userid) {
        $this->userid = $userid;
    }

    public function getDb() {
        return $this->db;
    }
}

