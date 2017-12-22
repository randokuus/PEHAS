<?php
/**
* Variable converter.
*
* @version $Revision: 338 $
*/

/**
 * Converts variables to
 *
 * It allows you to validate with default value,
 *  convert variable to some data type.
 *
 * @author Priit Pold <priit.pold@modera.net>
 * @static
 */

DEFINE( 'VAR_2_INT'     , 'toInteger' );
DEFINE( 'VAR_2_FLOAT'   , 'toFloat' );
DEFINE( 'VAR_2_STR'     , 'toString' );
DEFINE( 'VAR_2_BOOL'    , 'toBoolean' );
DEFINE( 'VAR_2_ARRAY'   , 'toArray' );
DEFINE( 'VAR_2_ANY'     , 'toAny' );

class Filter{

    /**
     * Convert variable to string with trim
     *
     * @param mixed $str - string to convert or assoc array
     * @param string $key - key of $str array.
     *  if it is set, then $str must be an array.
     * @return formated string
     */
    function toString( $str , $key = null ){
        if( $key ){ if( isset($str[$key] ) ) return trim( $str[$key] );}
        else{ return trim( $str ); }
        return '';
    }

    /**
     * Convert variable to integer
     *
     * @see toInt()
     * @param string $str - string to convert
     * @param boolean $return_nil - returns NULL if given string is not an integer
     *   and $return_nil is set to FALSE, otherwise returns integer reprecentation
     *   of given string(if it is an empty string then will return 0)
     *
     * @return integer|NULL
     */
    function toInteger($str,$return_nil=true) {
        return Filter::toInt($str,$return_nil);

    }

    /**
     * Converts given variable to type INTEGER
     *
     * @param string $str - string to convert
     * @param boolean $return_nil - returns NULL if given string is not an integer
     *   and $return_nil is set to FALSE, otherwise returns integer reprecentation
     *   of given string(if it is an empty string then will return 0)
     *
     * @return integer|NULL
     */
    function toInt($str,$return_nil=true) {

        $str = preg_replace( '/[^0-9-]/','',$str);
        if ($str[0]!='-') {
            $str = str_replace('-','',$str);
        } else {
            $str = '-'.str_replace('-','',substr($str,1));
        }

        if (!preg_match('/[0-9]/',$str)) $str ='';

        if (strlen($str)) {
            $str = (int)$str;

        } else {
            $str = ($return_nil) ? 0 : null;
        }
        return $str;
    }

    /**
     * Convert variable to FLOAT
     *
     * @param string $str - string to convert
     * @return float
     */
    function toFloat( $str ){
        $str = str_replace( ',' , '.' , preg_replace( '/[^0-9,\.]/','',$str) );
        return ( strlen($str)? (float)$str : NULL );
    }

    /**
    * Converts given variable to BOOLEAN type
    *
    * @param string - variable to convert
    * @return BOOLEAN - converted variable
    */
    function toBoolean( $str ){
        switch ($str){
            case 'TRUE':
            case 'true':
            case '1':
            case 'on':
            case TRUE:
                return $str = TRUE;
            case 'FALSE':
            case 'false':
            case '0':
            case FALSE:
                return $str = FALSE;
            default:
                return $str = (bool)$str;
        }
    }

    /**
    * Convert variable to array.
    *
    * @param mixed $var - variable to convert
    * @return array - converted to array variable
    */
    function toArray( $var ){
        if( is_array( $var ) ){
            return $var;
        }else{
            return $var = array($var);
        }
    }

    /**
     * Set variable to type 'ANY'
     *
     * Set variable to type 'ANY' = TRUE.
     * @param mixed $str - variable to convert
     * @return TRUE
     */
    function toAny( $str ){
        return TRUE;
    }

    /**
     * Convert variable to given type
     * If it is not defined, set the default value.
     *
     * @param mixed|array $arr - variable to check
     * @param string $key in type:  ...
     * @param constant $var_type - variable type to convert
     * @param mixed - default value for key
     */
    function setDefault( &$arr , $key = null , $var_type = VAR_2_ANY , $def = null , $return_nill = false ){
        if (is_null($key) || !strlen($key)) {

            if ($var_type==VAR_2_INT) {
                $arr = Filter::$var_type($arr,$return_nill);

            } else {
                $arr = Filter::$var_type($arr);

            }
        } else {
            if (isset($arr[$key])) {
                if ($var_type==VAR_2_INT) {
                    $arr[$key] = Filter::$var_type($arr[$key],$return_nill);

                } else {
                    $arr[$key] = Filter::$var_type($arr[$key]);

                }
            } else {
                $arr[$key] = Filter::$var_type( $def );

            }
        }
    }
}

