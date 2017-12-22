<?php
/**
 * @version $Revision: 570 $
 * @package modera_net
 */

require_once(SITE_PATH . '/class/Arrays.php');

/**
 * Class for formatting sql queries (sprintf like behaviour)
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Placeholder
{
    /**
     * Database object
     *
     * @var Database
     * @access protected
     */
    var $_database;

    /**
     * Buffer for storing array of parameters
     *
     * @var array
     * @access private
     */
    var $_params;

    /**
     * Array of available placeholders
     *
     * @var array
     * @access protected
     */
    var $_placeholders;

    /**
     * Class constructor
     *
     * @param Database $database
     * @return Placeholder
     */
    function Placeholder(&$database)
    {
        $this->_database =& $database;
        $this->_placeholders = array('!', '!@', '!%', '!%AND', '!%OR', '?', '?f'
            , '?@', '?@f', '?%', '?%AND', '?%OR');
    }

    /**
     * Compiles placeholder into internal structure (array)
     *
     * @param string $placeholder
     * @return array
     * @access private
     */
    function _compile(&$placeholder)
    {
        $compiled = array();
        $l = strlen($placeholder);
        $p_new = '';
        $offset = 0;

        // loop through all characters
        for ($i = 0; $i < $l; $i++) {
            // skip ! and ? characters followed by slash
            if (in_array(substr($placeholder, $i, 2), array('\\!', '\\?'))) {
                $p_new .= substr($placeholder, ++$i, 1);
                $offset++;
                continue;
            }

            // found a placeholder
            if (in_array(substr($placeholder, $i, 1), array('!', '?'))) {
                for ($n = 5; $n > 0; $n--) {
                    $s = substr($placeholder, $i, $n);
                    if (strlen($s) < $n) $n = strlen($s);
                    if (in_array($s, $this->_placeholders)) {
                        $p_new .= $s;
                        // save compiled placeholder in array
                        $compiled[$i-$offset] = array ($n, $s);
                        // increase $i
                        $i += $n-1;
                        continue(2);
                    }
                }
            }
            else {
                $p_new .= substr($placeholder, $i, 1);
            }
        }

        $placeholder = $p_new;
        return $compiled;
    }

    /**
     * Covert array to comma separated list
     *
     * @param array $array
     * @param string $a_type list type: 'fields', 'values', 'assoc'
     * @param bool $escape if TRUE than values will be escaped (fields are always escaped)
     * @param string $glue glue string between pairs
     * @return string
     * @access private
     */
    function _array_to_list($array, $a_type, $escape, $glue)
    {
        $values = array();

        foreach ($array as $field => $value) {
            if ('fields' == $a_type) $value = $this->_database->quote_field_name($value);
            if ($escape && in_array($a_type, array('values', 'assoc'))) {
                $value = $this->_database->quote($value);
            }

            if ('assoc' == $a_type) {
                $values[$this->_database->quote_field_name($field)] = $value;
            }
            else {
                $values[] = $value;
            }
        }

        return 'assoc' == $a_type ? Arrays::implode_assoc('=', $glue, $values)
            : implode($glue, $values);
    }

    /**
     * Escape placeholder sequences in string
     *
     * @param string $str
     */
    function escape_placeholders($str)
    {
        return strtr($str, array('!' => '\\!', '?' => '\\?'));
    }

    /**
     * Format sql query
     *
     * <pre>
     * List of available placeholders:
     * !        no escaping, input - scalar
     * !@       comma separated list, without escaping, input - array (10, 20)
     * !%       comma separated list of key = value pairs, values are not escaped,
     *          input - associative array (`key1`=10, `key2`=20)
     * ?        simple value escaping using {@link Database::quote()}
     * ?f       field name escaping, input - scalar {@link Database::quote_field_name()}
     * ?@       comma separated escaped list, input - array
     * ?@f      comma separated field list, with escaping, input - array (fname, lname)
     * ?%       comma separated list of key = value pairs, values are escaped
     * ?%AND    'AND' separated list of key = value pairs, values are escaped
     * ?%OR     'OR' separated list of key = value pairs, values are escaped
     * </pre>
     *
     * @param string $string
     * @param array $params
     * @return string
     * @access public
     * @todo it might be useful to add support for precompiled placeholders instead
     *  of executing Placeholder::_compile() method every time
     */
    function prepare($string, $params)
    {
        $prepared = '';
        $cursor = 0;

        foreach ($this->_compile($string) as $start => $pl_array) {
            list($length, $pl) = $pl_array;

            // get next parameter
            if (!(list(,$param) = each($params))) {
                trigger_error('Not enough parameters given', E_USER_ERROR);
            }

            switch ($pl[0]) {
                case '!':
                    $escape = false;
                    break;
                case '?':
                    $escape = true;
                    break;
            }

            switch (substr($pl, 1, 1)) {
                case '@':
                    $a_type = 'values';
                    break;
                case '%':
                    $a_type = 'assoc';
                    $glue = ' ' . substr($pl, 2) . ' ';
                    break;
                default:
                    $a_type = 'values';
            }

            switch($pl) {
                case '!':
                    // not escaped
                    $value = $param;
                    break;

                case '?':
                    // escaped scalar value
                    $value = $this->_database->quote($param);
                    break;

                case '?f':
                    // escape field name
                    $value = $this->_database->quote_field_name($param);
                    break;

                case '?@f':
                    // list of escaped field names
                    $a_type = 'fields';

                default:
                    $value = $this->_array_to_list($param, $a_type, $escape
                        , trim(@$glue) ? $glue : ', ');
                    break;
            }

            $prepared .= substr($string, $cursor, $start - $cursor) . $value;
            $cursor = $start + $length;
        }

        return $prepared . substr($string, $cursor);
    }
}
