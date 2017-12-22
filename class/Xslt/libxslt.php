<?php
/**
 * @version $Revision: 341 $
 */

/**
 * XSL Functions wrapper
 *
 * Wrapper for XSLTProcessor class available under php5 for performing XSLT
 * transformations.
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 */
class Xslt_libxslt extends Xslt
{
    /**
     * Source XML DOM
     *
     * @var DOMDocument
     * @access private
     */
    var $_xml;

    /**
     * XSLT processor instance
     *
     * @var XSLTProcessor
     * @access private
     */
    var $_xslt_proc;

    /**
     * Class constructor
     *
     * @return Xslt_libxslt
     */
    function Xslt_libxslt()
    {
        parent::Xslt();
        $this->_xslt_proc = new XSLTProcessor();
    }

    /**
     * Set XML data
     *
     * @param string $xml
     */
    function set_xml($xml)
    {
        $this->_xml = DOMDocument::loadXML($xml);
    }

    /**
     * Set XSL data
     *
     * @param string $xsl
     */
    function set_xsl($xsl)
    {
        $doc = DOMDocument::loadXml($xsl);
        $this->_xslt_proc->importStylesheet($doc);
    }

    /**
     * Set path to XML file
     *
     * @param string $file path to XML file
     */
    function set_xml_file($file)
    {
        $this->_xml = DOMDocument::load($file);
    }

    /**
     * Set path to XSL file
     *
     * @param string $file path to XSL file
     */
    function set_xsl_file($file)
    {
        $doc = new DOMDocument();
        $doc->substituteEntities = true; // workaround for php bug#30218
        $doc->load($file, LIBXML_NOCDATA);
        $this->_xslt_proc->importStylesheet($doc);
    }

    /**
     * Set parameter passed to XSLT processor
     *
     * @param string $name parameter name
     * @param string $value parameter value
     */
    function set_parameter($name, $value)
    {
        parent::set_parameter($name, $value);
        $this->_xslt_proc->setParameter('', $name, $value);
    }

    /**
     * Perform XSLT transformation
     *
     * @return mixed
     */
    function process()
    {
        return $this->_xslt_proc->transformToXml($this->_xml);
    }
}