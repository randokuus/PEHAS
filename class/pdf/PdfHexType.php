<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF hex string type object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfHexType extends PdfStringType {

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode() {
        $value = strtoupper(bin2hex($this->_value));
        $lines = '';
        do {
            $lines .= substr($value, 0, 80) . "\n";
            $value = substr($value, 80);
        } while (strlen($value) > 80);
        $lines .= $value;
        return '<' . $lines . '>';
    }
}
