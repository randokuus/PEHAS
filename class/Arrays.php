<?php
/**
 * @version $Revision: 327 $
 * @package modera_net
 */

/**
 * Static class with diffrent arrays related methods
 *
 * @author Alexandr Chertkov
 * @static
 */
class Arrays
{
    /**
     * Creates an array by using one array for keys and another for its values
     *
     * @param array $keys
     * @param array $values
     * @return array|FALSE
     */
    function array_combine($keys, $values)
    {
        $l = count($keys);
        if (0 == $l || $l != count($values)) {
            return false;
        }

        $combined = array();
        for ($i = 0; $i < $l; $i++) {
            list(, $k) = each($keys);
            list(, $v) = each($values);
            $combined[$k] = $v;
        }

        return $combined;
    }

    /**
     * Computes the intersection of arrays using keys from first array and values
     * from second for comparition.
     *
     * Returned array contain key=>value pairs from first array which keys exists in
     * second array values.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    function array_intersect_key_val($array1, $array2)
    {
        $int_array = array();
        foreach ($array1 as $k => $v) {
            if (in_array($k, $array2)) {
                $int_array[$k] = $v;
                // removing matched key from second array it can be matched only
                // once, on next loop array2 will be shorter
                unset($array2[$k]);
            }
        }

        return $int_array;
    }

    /**
     * Implode key-pair array.
     *
     * @param string $inner_glue    glue between key and value
     * @param string $outer_glue    glue between key/value pairs
     * @param array $array          array to implode
     * @param bool $skip_empty      if set to TRUE, key/value pairs where value is empty
     *                              will be ignored
     * @return string
     */
    function implode_assoc($inner_glue, $outer_glue, $array, $skip_empty = false)
    {
        $output = array();
        foreach ($array as $k => $v) {
            if (!$skip_empty || $v) {
                $output[] = $k . $inner_glue . $v;
            }
        }
        return(implode($outer_glue, $output));
    }


    /**
     * Set all values of given array to NULL
     *
     * Recursively set all values of given array to NULL
     * and returns result.
     *
     * @param array $arr - array, which values you want to set to NULL.
     * @return array - array with values = NULL
     */
    function nullArray( $arr ){
		foreach( $arr as $key => $val ){
			if( is_array( $arr[$key] ) ){
				$arr[$key] = Arrays::nullArray( $arr[$key] );
			}else{
				$arr[$key] = NULL;
			}
		}
		return $arr;
	}
}
