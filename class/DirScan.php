<?php
/**
 * Directory scanner.
 * @version $Revision: 306 $
 */

require_once( SITE_PATH.'/class/FactoryPattern.php' );

/**
 * Scans directories with selected options and
 * returns and array in selected view.
 *
 * To chooce return structure type, just create sub class with
 * main function for parsing directories.
 *
 * @author Priit Pold <priit.pold@modera.net>
 */
class DirScan{

    var $root_path;

    /**
     * Array of options for scanner
     *
     * @var unknown_type
     */
    var $scan_options;

    /**
     * is root path valid
     * @var boolean
     */
    var $valid_path;

    /**
     * Constructor
     *
     * @param string $path - root path for scanning
     * @return DirScan
     */
    function DirScan( $path = '' )
    {
        $this->valid_path = false;
        $this->setRootPath($path);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $method
     * @param unknown_type $path
     * @return unknown
     * @static
     */
    function &method( $method, $path )
    {
        $obj =& FactoryPattern::factory('DirScan', $method, dirname(__FILE__)
            , array($path));
        return $obj;
    }

    /**
     * Set root path
     *
     * Path, from where it begins to scan
     *
     * @param string $path
     */
    function setRootPath( $path )
    {
        if( !file_exists($path) OR !is_dir($path))
        {
            $this->valid_path = false;
            return false;
        }
        $this->valid_path = true;
        $this->root_path = $path;
    }

    /**
     * Get root path
     * @return string
     */
    function getRootPath(){ return $this->root_path; }
    /**
     * Set scan options
     *
     * @param array $options - array of options
     */
    function setOptions( $options ){
        if( !is_array( $options ) ) return;
        foreach ($options as $opt_key => $opt_val)
            $this->scan_options[$opt_key]=$opt_val;
    }

    /**
     * Set scan options to default options.
     */
    function setDefaultOptions()
    {
        $this->setOptions($this->getAvailableOptions());
    }

    /**
     * Check, if selected option exists in options array or not
     *
     * @param string $key - option key
     * @return boolean
     */
    function checkOption( $key ){
        return isset( $this->scan_options[ $key ] )?true:false;
    }

    /**
     * Return scan option value
     *
     * @param string $key - option key
     * @return mixed|null - returns value of selected option if it exists
     *  ,otherwise null
     */
    function getOption( $key )
    {
        return isset( $this->scan_options[$key] )?$this->scan_options[ $key ]:null;
    }

    /**
     * Get array of available/default options for dir scanner
     * @return array - default/available options for directory scan
     */
    function getAvailableOptions()
    {
        return array(
            'count_files'=>false        // will count files in directories
            ,'with_files'=>false        // will also return files in dir structure
            ,'allowed_ext'              // array of accepted extensions
            ,'forbidden_ext'            // array of restricted ext
            ,'max_depth'=>0             // set max depth for scanner
            ,'with_subfolders'=>true    // set false if you want to
            ,'forbidden_dir'            // array of forbidden dirs(skip)
        );
    }

    /**
     * Call this function for getting directory structure.
     *
     * Get root path structure with selected options.
     * @return array - root path structure
     */
    function getDirStructure(){
        if( !$this->valid_path ) return array();
        $structure = array();
        $structure = $this->_scanning( $this->getRootPath(),'' , 0 );
        ksort($structure);
        return $structure;
    }

    /**
     * Scan root path.
     *
     * Main function for recursive scan of selected directory.
     * Returns an array of files and folders according to user options.
     *
     * @param string $root_path - root path.
     * @param string $path - current path for scan.
     * @param int $depth - current path depth
     * @return array - directory structure with options
     */
    function _scanning( $root_path , $path , $depth = 1 )
    {
        trigger_error('function doesnt implemented');
    }
}
