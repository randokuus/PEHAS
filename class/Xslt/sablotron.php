<?php
/**
 * @version $Revision: 352 $
 */

/**
 * XSLT Functions wrapper
 *
 * Wrapper for XSLT Functions based on sablotron backend.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Xslt_sablotron extends Xslt
{
    /**
     * XSLT processor arguments array
     *
     * @var array
     * @access private
     */
    var $_arguments;

    /**
     * XSLT processor resource
     *
     * @var resource
     * @access private
     */
    var $_proc;

    /**
     * XML container
     *
     * Could be wether XML source or path to XML file
     *
     * @var string
     * @access private
     */
    var $_xml;

    /**
     * XSL container
     *
     * Could be wether XSL source or path to XSL file
     *
     * @var string
     * @access private
     */
    var $_xsl;

    function Xslt_sablotron()
    {
        parent::Xslt();
        $this->_arguments = array();
        $this->_parameters = array();
        $this->_proc = xslt_create();
    }

    /**
     * Modify file path if needed
     *
     * Under windows xslt extension expects that pathes to files are perended
     * with 'file://'. This method takes path as input parameter and prepends it
     * with 'file://' if it was not done already.
     *
     * @param string $file
     * @return string
     */
    function _process_file($file)
    {
        if ('\\' == DIRECTORY_SEPARATOR && 0 !== strncmp($file, 'file://', 7)) {
            $file = 'file://' . $file;
        }

        return $file;
    }

    /**
     * Set XML data
     *
     * @param string $xml
     */
    function set_xml($xml)
    {
        $this->_arguments['/_xml'] = $xml;
        $this->_xml = 'arg:/_xml';
    }

    /**
     * Set XSL data
     *
     * @param string $xsl
     */
    function set_xsl($xsl)
    {
        $this->_arguments['/_xsl'] = $xsl;
        $this->_xsl = 'arg:/_xsl';
    }

    /**
     * Set path to XML file
     *
     * @param string $file path to XML file
     */
    function set_xml_file($file)
    {
        $file = $this->_process_file($file);
        if (array_key_exists('_xml', $this->_arguments)) {
            unset($this->_arguments['_xml']);
        }

        $this->_xml = $file;
    }

    /**
     * Set path to XSL file
     *
     * @param string $file path to XSL file
     */
    function set_xsl_file($file)
    {
        $file = $this->_process_file($file);
        if (array_key_exists('_xsl', $this->_arguments)) {
            unset($this->_arguments['_xsl']);
        }

        $this->_xsl = $file;
    }

    /**
     * Perform XSLT transformation
     *
     * @return mixed
     */
    function process()
    {
        return xslt_process($this->_proc, $this->_xml, $this->_xsl, NULL
            , $this->_arguments, $this->_parameters);
    }

    /**
     * Get last error description
     *
     * @link http://www.php.net/manual/en/function.xslt-error.php
     * @return string
     */
    function get_error()
    {
        return xslt_error($this->_proc);
    }
}