<?php
/**
 * @version $Revision: 341 $
 */

require_once(SITE_PATH . '/class/FactoryPattern.php');

/**
 * Abstract XSLT processor
 *
 * This class is intended for providing one API for XSLT transformations on both
 * PHP4 and PHP5. Under PHP4 it will use sablotron backend, under PHP5 libxslt
 * backend will be used.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @abstract
 */
class Xslt
{
    /**
     * XSLT processor parameters array
     *
     * @var array
     * @access protected
     */
    var $_parameters;

    /**
     * Create instance of xslt subclass
     *
     * Under PHP4 it will return instance of Xslt_sablotron class, under PHP5
     * Xslt_libxslt instance.
     *
     * @return Xslt
     * @static
     */
    function &instance()
    {
        if (version_compare(PHP_VERSION, '5', '>=')) {
            $obj =& FactoryPattern::factory('Xslt', 'libxslt', SITE_PATH . '/class');
        } else {
            $obj =& FactoryPattern::factory('Xslt', 'sablotron', SITE_PATH . '/class');
        }

        return $obj;
    }

    /**
     * Class constructor
     *
     * @return Xslt
     */
    function Xslt()
    {
        $this->_parameters = array();
    }

    /**
     * Set XML data
     *
     * @param string $xml
     * @abstract
     */
    function set_xml($xml)
    {
        trigger_error('You have to implement abstract method _raw_translate()'
            , E_USER_ERROR);
    }

    /**
     * Set XSL data
     *
     * @param string $xsl
     * @abstract
     */
    function set_xsl($xsl)
    {
        trigger_error('You have to implement abstract method _raw_translate()'
            , E_USER_ERROR);
    }

    /**
     * Set path to XML file
     *
     * @param string $file path to XML file
     * @abstract
     */
    function set_xml_file($file)
    {
        trigger_error('You have to implement abstract method _raw_translate()'
            , E_USER_ERROR);
    }

    /**
     * Set path to XSL file
     *
     * @param string $file path to XSL file
     * @abstract
     */
    function set_xsl_file($file)
    {
        trigger_error('You have to implement abstract method _raw_translate()'
            , E_USER_ERROR);
    }

    /**
     * Set parameter passed to XSLT processor
     *
     * @param string $name parameter name
     * @param string $value parameter value
     */
    function set_parameter($name, $value)
    {
       $this->_parameters[$name] = $value;
    }

    /**
     * Get parameters passed to XSLT processor
     *
     * @return array
     */
    function get_parameters()
    {
        return $this->_parameters;
    }

    /**
     * Perform XSLT transformation
     *
     * @return string
     * @abstract
     */
    function process()
    {
        trigger_error('You have to implement abstract method _raw_translate()'
            , E_USER_ERROR);
    }

    /**
     * Get last error description
     *
     * NB! Not all backends support errors description
     *
     * @return string
     */
    function get_error()
    {
        return '';
    }
}