<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF numeric type object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfNumericType extends PdfType {

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param float Initial value
     */
    function PdfNumericType($value = 0) {
        parent::PdfType($value);
    }

    /**
     * Set value
     *
     * @access public
     * @param float Value to set
     */
    function setValue($value) {
        if (version_compare(phpversion(), '4.2.0') < 0) {
            $this->_value = $value;
        } else {
            $this->_value = floatval($value);
        }
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode() {
        return $this->_value;
    }
}
