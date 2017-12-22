<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF string type object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfStringType extends PdfType {

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param string Initial value
     */
    function PdfStringType($value = '') {
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
        $value = $this->_value;
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace("\n", "\\n", $value);
        $value = str_replace("\r", "\\r", $value);
        $value = str_replace("\t", "\\t", $value);
        $value = str_replace("\x08", "\\b", $value);
        $value = str_replace("\x0C", "\\f", $value);
        $value = str_replace("(", "\\(", $value);
        $value = str_replace(")", "\\)", $value);
        $lines = '';
        while (strlen($value) > 80) {
            $lines .= substr($value, 0, 79) . "\\\n";
            $value = substr($value, 79);
        };
        $lines .= $value;
        return '(' . $lines . ')';
    }
}
