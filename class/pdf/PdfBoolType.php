<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF bool type object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfBoolType extends PdfType {

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param bool Initial value
     */
    function PdfBoolType($value = false) {
        parent::PdfType($value);
    }

    /**
     * Set value
     *
     * @access public
     * @param bool Value to set
     */
    function setValue($value) {
        $this->_value = $value ? true : false;
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode() {
        return $this->_value ? 'true' : 'false';
    }
}
