<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Basic type for PDF objects
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfType {

    /**
     * @access private
     * @var int Object ID (used in PDF-file)
     */
    var $_id = 0;

    /**
     * @access private
     * @var int Offset of object in PDF-file
     */
    var $_position = 0;

    /**
     * @access private
     * @var mixed Object value
     */
    var $_value;

    /**
     * @access private
     * @var int Indend value to use in PDF formatter
     */
    var $_indentValue = 2;

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param array Initial value
     */
    function PdfType(&$value) {
        $this->setValue($value);
    }

    /**
     * Set ID of object
     *
     * @access public
     * @param int Object ID
     */
    function setId($value) {
        $this->_id = intval($value);
    }

    /**
     * Get ID of object
     *
     * @access public
     * @return int Object ID
     */
    function getId() {
        return $this->_id;
    }

    /**
     * Set position (offset) of object
     *
     * @access public
     * @param int Object offset
     */
    function setPosition($value) {
        $this->_position = intval($value);
    }

    /**
     * Get position (offset) of object
     *
     * @access public
     * @return int Object offset
     */
    function getPosition() {
        return sprintf("%010d", $this->_position);
    }

    /**
     * Set value
     *
     * @access public
     * @param mixed Value to set
     * @abstract
     */
    function setValue(&$value) {

    }

    /**
     * Get value
     *
     * @access public
     * @return mixed Value of object
     */
    function &getValue() {
        return $this->_value;
    }

    /**
     * Get PDF-code for object
     *
     * @abstract
     * @access public
     * @param int Level in PDF object hierarchy
     * @return string
     */
    function getValueCode($level = 0) {
        return null;
    }
}
