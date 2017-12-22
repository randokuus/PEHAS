<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF name type object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfNameType extends PdfType {

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param string Initial value
     */
    function PdfNameType($value = '') {
        parent::PdfType($value);
    }

    /**
     * Set value
     *
     * @access public
     * @param string Value to set
     */
    function setValue($value) {
        $this->_value = '' . $value;
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode() {
        $value = $this->_value; // (, ), <, >, [, ], {, }, /, and %
        foreach (array("\t", " ", "(", ")", "<", ">", "[", "]", "{", "}", "/", "%", "#") as $ch) {
            $value = str_replace($ch, "#" . strtoupper(bin2hex($ch)), $value);
        }
        return '/' . $value;
    }
}
