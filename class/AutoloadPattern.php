<?php
/**
 * @version $Revision: 253 $
 */

/**
 * Autoload method
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @static
 */
 class AutoloadPattern
 {
    /**
     * Autoload method.
     *
     * If file with target class was not included than method will guess
     * right name for file name and search for this file name in $paths
     * directories.
     *
     * @param string $class name of class to create
     * @param array|string $paths search directories
     * @param mixed $param,... parameters passed to constructor
     * @return object|FALSE created object, or FALSE if object was not created
     */
    function &autoload($class, $paths, &$args)
    {
        if (!is_array($paths)) $paths = array($paths);

        $arg_names = array();
        for ($i = 0; $i < count($args); $i++) {
            $var = "arg$i";
            $$var =& $args[$i];
            array_push($arg_names, "\$$var");
        }

        $args = implode(',', $arg_names);
        $eval = "return new $class($args);";
        $file_name = "$class.php";

        if (class_exists($class)) {
            $obj = eval($eval);
            return $obj;

        } else {
            // search for file_name
            foreach ($paths as $path) {
                if (is_readable($path . '/' . $file_name)) {
                    include_once($path . '/' . $file_name);
                    if (class_exists($class)) {
                        $obj = eval($eval);
                        return $obj;
                    }
                }
            }
        }

        $false = false;
        return $false;
    }
}
