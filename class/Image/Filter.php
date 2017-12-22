<?php
/**
 * Parent class for all filters.
 * @version $Revision: 331 $
 */
require_once(SITE_PATH . '/class/FactoryPattern.php');

/**
 * Contains some function for retriving all available filters,
 *  to load some filter file, and others helpful functions
 * @author Priit Pold <priit.pold@modera.net>
 */
class Image_Filter{

    /**
     * Main constructor.
     * @return Image_Filter
     */
    function Image_Filter(){}

    /**
     * ImageFilter - returns selected filter subclass.
     *
     * @param string $driver - filter name.
     * @return ImageFilter.
     */
    function &driver($filter, $options = array())
    {
        $obj =& FactoryPattern::factory('Image_Filter', $filter, dirname(__FILE__), array($options));
        return $obj;
    }

    /**
     * Array of available filters
     *
     * @staticvar array $available cache
     * @return array
     * @static
     */
    function available()
    {
        static $available = null;
        if (is_null($available)) $available = FactoryPattern::available('Image_Filter'
            , dirname(__FILE__));
        return $available;
    }

    /**
     * Get filter full file path by it's name.
     * Returns filter full path.
     *
     * @param string $filter_name - filter name.
     * @return string - filter full path
     * @access static
     */
    function getFilterFileName($filter_name)
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Filter'
            . DIRECTORY_SEPARATOR . $filter_name . '.php';
    }


    /**
     * Checks, if filter with given name exists or not.
     *
     * @param string $filter_name - filter name to check
     * @return bool - returns TRUE if filter exists with given name and is readable,
     *  otherwise FALSE.
     */
    function checkExistance($filter_name)
    {
        $filter_file_name = Image_Filter::getFilterFileName($filter_name);
        if (!file_exists($filter_file_name)){
            return false;
        }
        if (!is_readable($filter_file_name)){
            return false;
        }
        return true;
    }

    /**
     * Applies selected filter.
     *
     * Try to load filter, and apply it on image resource.
     * @param resource $resource
     * @return bool - on success returns TRUE, otherwise FALSE.
     */
    function applyFilter( &$resource, $filter_name='', $options=array())
    {
        if (Image_Filter::loadFilter($filter_name)){
            $class = 'Image_Filter_'.$filter_name;
            call_user_func(array($class,'applyFilter'), $resource, $options);
            return true;
        }
        return false;
    }

    /**
     * Applies selected filter.
     *
     * Try to load filter, and apply it on Image_Resource.
     * @param Image_Resource $resource
     * @return bool - on success returns TRUE, otherwise FALSE.
     */
    function applyFilterOnResource( &$resource, $filter_name='', $options=array())
    {
        if (Image_Filter::loadFilter($filter_name)){
            $class = 'Image_Filter_'.$filter_name;
            call_user_func(array($class,'applyFilterOnResource'), $resource, $options);
        }
    }

    /**
     * Load selected filter.
     *
     * @param string $filter_name
     * @return bool - return TRUE if filter exists and was successfully loaded
     */
    function loadFilter($filter_name)
    {
        $filter_file_name = Image_Filter::getFilterFileName($filter_name);
        if (!file_exists($filter_file_name)){
            return false;
        }
        if (!is_readable($filter_file_name)){
            return false;
        }
        if (!require_once($filter_file_name)){
            return false;
        }
        return true;
    }

    /**
     * Get option by key from options array.
     *
     * @param array $options - array of options.
     * @param string $key - option key
     * @return mixed|NULL - if options exists, returns it, otherwise return NULL
     */
    function getOption(&$options, $key = '', $default=null)
    {
        if (isset($options[$key])) return $options[$key];
        return $default;
    }
}