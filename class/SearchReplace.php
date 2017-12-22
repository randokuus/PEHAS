<?php
/**
 * @version $Revision: 653 $
 */

/**
 * Search & Replace functionality for Modera.NET
 *
 * Search and replace is performed in binary mode
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class SearchReplace
{
    /**#@+
     * @access protected
     */

    /**
     * Database instance
     *
     * @var Database
     */
    var $_db;

    /**
     * Default limit value
     *
     * @var int
     */
    var $_def_limit;

    /**#@-*/

    /**
     * Class constructor
     *
     * @param Database $database
     * @return SearchReplace
     */
    function SearchReplace(&$database)
    {
        $this->_db =& $database;
        $this->_def_limit = 50;
    }

    /**#@+
     * @access protected
     */

    /**
     * Prepare search/replace string
     *
     * @param string $str
     * @param string $wrap_with
     * @return string
     */
    function _quote_like_str($str, $wrap_with = '')
    {
        $str = strtr($str, array('\\' => '\\\\', '%' => '\%', '_' => '\_'));
        $str = $this->_db->quote("$wrap_with$str$wrap_with");
        return strtr($str, array('\\\%' => '\\%', '\\\_' => '\\_'));
    }

    /**
     * Return array with specified object metadata or all objects metadata
     *
     * If argument is passed in is not null than method will return metadata array for
     * specified object, or NULL of there are no metadata. If argument is not passed, or
     * NULL than method will return associative array with all objects metadata
     *
     * @param string|NULL $object object name
     * @return array|NULL metadata array or NULL
     */
    function _obj_meta($object = null)
    {
        // multidimesional array mapping search objects to table names
        // and providing searchable field names
        $objects_map = array(
            'news' => array(                                // object name
                'module_news' => array(                     // table name
                    array('field', 'title'),                // title - array(type, title); type: text, or field
                    array('title', 'lead', 'content'),      // search/replace fields
                    array('id'),                            // primary key
                 ),
            ),
            'pages' => array(
                'content' => array(
                    array('field', 'title'),
                    array('title', 'text'),
                    array('content', 'language'),          // structure needed for builing link
                ),
            ),
            'products' => array(
                'module_products_texts' => array(
                    array('field', 'title'),
                    array('title', 'descr', 'info'),
                    array('id'),
                ),
            ),
            'translations' => array(
                'translations' => array(
                    array('field', 'token'),
                    array('translation'),
                    array('token', 'plural', 'language', 'domain'),
                ),
            ),
        );

        if (is_null($object)) return $objects_map;

        return @$objects_map[$object];
    }

    /**#@-*/

    /**
     * Search string in database tables
     *
     * Performes binary search in database tables
     *
     * @param string $search_str
     * @param int|NULL $limit how many records return, if 0 than all records will be returned
     * @param array|NULL $where
     * @return array
     */
    function search($search_str, $limit = null, $where = null)
    {
        // do not allow empty searches
        if ('' == $search_str) return array();

        if (is_null($limit)) $limit = $this->_def_limit;
        if (is_null($where)) $where = $this->available_objects();

        $results = array();
        $qsearch_str = $this->_quote_like_str($search_str, '%');
        $qsearch_str = $this->_db->escape_placeholders($qsearch_str);

        foreach ($where as $object) {
            $obj_meta = $this->_obj_meta($object);

            // loop through object metadata
            foreach ($obj_meta as $table_name => $table_meta) {
                list($title_meta, $search_fields, $key_fields) = $table_meta;
                list($title_type, $title_data) = $title_meta;

                // do not execute query if table not exists
                if (!$this->_db->table_exists($table_name)) continue;

                //
                // prepare sql query
                //
                $conditions = array();
                foreach ($search_fields as $field) {
                    $conditions[$field] = 'BINARY ' . $this->_db->quote_field_name($field)
                        . " LIKE $qsearch_str";
                }

                $sql = "SELECT ?@f FROM ?f WHERE " . implode(' OR ', $conditions)
                    . ($limit ? " LIMIT $limit" : '');

                // append title field to select fields if it's not there
                $sel_fields = $search_fields;
                if ('field' == $title_type && !in_array($title_data, $sel_fields)) {
                    $sel_fields[] = $title_data;
                }

                // append key fields to select fields
                $sel_fields = array_merge($key_fields, $sel_fields);

                //
                // execute query and populate results array
                //

                $res =& $this->_db->query($sql, $sel_fields, $table_name);
                while ($row = $res->fetch_assoc()) {
                    // for each row check in which fields it was found
                    // create match string and hilight searching string
                    $key_data = array();
                    $matched_data = '';
                    foreach ($row as $field => $value) {
                        // collect array with keys data
                        if (in_array($field, $key_fields)) {
                            $key_data[$field] = $value;
                        }

                        // skip non searched fields
                        if (!in_array($field, $search_fields)) continue;
                        if (false !== strpos($value, $search_str)) {
                            $matched_data .= '' == $matched_data ? $value : " $value";
                        }
                    }

                    if ('field' == $title_type) {
                        $title_value = $row[$title_data];
                    } else {
                        $title_value = $title_data;
                    }

                    $results[$object][] = array(
                        'table' => $table_name,
                        'title' => $title_value,
                        'data'  => $matched_data,
                        'key'   => $key_data,
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Replace string in specified row
     *
     * @param string $search_str search string
     * @param string $replace_str replace string
     * @param string $obj_name object name
     * @param string $table_name table name on which update query will be executed
     * @param array $conditions associative array with primary key data to identify table row
     * @return bool
     */
    function replace_one($search_str, $replace_str, $obj_name, $table_name, $conditions)
    {
        // do not allow empty
        if ('' == $search_str) return false;

        // escape search and replace strings
        $qsearch_str = $this->_db->quote($search_str);
        $qsearch_str = $this->_db->escape_placeholders($qsearch_str);
        $qreplace_str = $this->_db->quote($replace_str);
        $qreplace_str = $this->_db->escape_placeholders($qreplace_str);

        // check object
        if (is_null($obj_meta = $this->_obj_meta($obj_name))) {
            trigger_error(sprintf('Object %s is not known', $obj_name), E_USER_WARNING);
            return false;
        }

        // check object table
        if (!array_key_exists($table_name, $obj_meta)) {
            trigger_error(sprintf('Unknown table %s for object %s', $table_name, $obj_name), E_USER_WARNING);
            return false;
        }

        // get search fields for specified table
        list(, $upd_fields) = $obj_meta[$table_name];

        // process update fields
        $upd_arr = array();
        foreach ($upd_fields as $field_name) {
            $field_name = $this->_db->quote_field_name($field_name);
            $upd_arr[$field_name] = "REPLACE($field_name, $qsearch_str, $qreplace_str)";
        }

        $this->_db->query('UPDATE ?f SET !% WHERE ?%AND', $table_name, $upd_arr, $conditions);
        return (bool)$this->_db->affected_rows();
    }

    /**
     * Array with avaiable search objects
     *
     * Checks which objects really exists in modera, since some of them might be provided by
     * optional modules
     *
     * @return array
     */
    function available_objects()
    {
        $objects = array();
        foreach ($this->_obj_meta() as $obj_name => $obj_meta) {
            // we will check only first table for existance, since if it exists than all other
            // tables also should exists if not than db is corrupted or metadata is wrong
            $tables = array_keys($obj_meta);
            $table_name = current($tables);
            if ($this->_db->table_exists($table_name)) $objects[] = $obj_name;
        }

        return $objects;
    }
}