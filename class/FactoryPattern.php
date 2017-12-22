<?php
/**
 * @version $Revision: 306 $
 */

require_once(SITE_PATH . '/class/AutoloadPattern.php');

/**
 * @author Alexandr Chertkov <s6urik@s6urik.ee>
 * @static
 */
class FactoryPattern
{
    /**
     * Implementation of reusable factory method.
     *
     * @param string $superclass superclass name
     * @param string $subclass subclass name
     * @param string|NULL $dir directory where superclass located, or NULL if superclass
     *  is located in current directory
     * @param array $args array of parameters passed to subclass constructor
     * @return object
     */
    function &factory($superclass, $subclass, $dir = null, $args = array())
    {
        if (is_null($dir)) $dir = './';

        $class_name = $superclass . '_' . $subclass;
        $file_name = $dir . '/' . $superclass . '/' . $subclass . '.php';

        if (preg_match('/^[_a-z]+[_a-z0-9]*$/i', $class_name)) {
            if (class_exists($class_name)) {
                $obj =& AutoloadPattern::autoload($class_name, '', $args);
                return $obj;

            } elseif (is_readable($file_name)) {
                include_once($file_name);
                if (false === $obj =& AutoloadPattern::autoload($class_name, '', $args)) {
                    trigger_error(sprintf('Cannot create class "%s"', $class_name), E_USER_ERROR);
                } else {
                    return $obj;
                }

            } else {
                trigger_error(sprintf('Cannot open file "%s"', $file_name), E_USER_ERROR);
            }
        } else {
            trigger_error(sprintf('Bad factory class name "%s"', $class_name), E_USER_ERROR);
        }
    }

    /**
     * Get array of avalable drivers
     *
     * Method searches for php which names do not begins with underscore in
     * $superclass directory located in $dir
     *
     * @param string $superclass
     * @param string $dir
     * @return array
     */
    function available($superclass, $dir = null)
    {
        if (is_null($dir)) $dir = './';
        $dir .= '/' . $superclass;
        $available = array();

        if (is_dir($dir) && is_readable($dir) && $dp = opendir($dir)) {
            while ($file = readdir($dp)) {
                if ('_' != substr($file, 0, 1) && '.php' == substr($file, -4)) {
                    $available[] = substr($file, 0, - 4);
                }
            }
            closedir($dp);
        }

        return $available;
    }
}
