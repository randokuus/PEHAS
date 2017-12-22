<?php
/**
 * @version $Revision: 570 $
 * @package modera_net
 */

/**
 * Static class with database helper methods.
 *
 * Actually all these methods should be implemented in Database class, but at the
 * moment of writing this code was closed.
 * <b>NB!</b> This is temporary solution. In the future better Database class acting
 * as a wrapper for standard sql class will be implemented including all functionality
 * added by methods of this class.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @static
 */
class DbHelpers
{
    /**
     * Get tables structure information
     *
     * @param sql $sql
     * @param int $dbc
     * @param string $table
     * @return array keys are table names, values are associative arrays of field properties
     */
    function get_tbl_fields(&$sql, $dbc, $table)
    {
        $fields = array();
        $table = addslashes($table);

        $sql->query($dbc, "DESCRIBE `$table`");
        while ($row = $sql->nextrow()) {
            $params = array();
            foreach ($row as $k => $v) {
                if (!is_numeric($k) && 'Field' != $k && '' !== $v) {
                    $params[strtolower($k)] = $v;
                }
            }

            $fields[$row[0]] = $params;
        }

        return $fields;
    }

    /**
     * Return array with all available tables
     *
     * @param sql $sql
     * @param int $dbc
     * @return array
     */
    function get_tables(&$sql, $dbc)
    {
        $tables = array();

        $sql->query($dbc, "SHOW TABLES");
        while(list($table) = $sql->nextrow()) {
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * Get available language from specified table
     *
     * @param sql $sql
     * @param int $dbc
     * @param table $table
     * @return array
     */
    function get_tbl_langs(&$sql, $dbc, $table)
    {
        $langs = array();
        // escape table name
        $table = addslashes($table);

        // check if table has language field
        $sql->query($dbc, "SHOW columns FROM `$table` LIKE 'language'");
        if ($sql->rows()) {
            // select distinct languages from table
            $sql->query($dbc, "SELECT DISTINCT language FROM `$table`");
            while (list($language) = $sql->nextrow()) {
                $langs[] = $language;
            }
        }

        return $langs;
    }

    /**
     * Convert array of fields to string prepared to use in SQL query
     *
     * Fields are comma separated and escaped
     *
     * @param array $array
     * @return string
     */
    function array_to_fields($array)
    {
        foreach ($array as $k => $field) {
            $array[$k] = "`" . addslashes($field) . "`";
        }

        return implode(', ', $array);
    }
}
