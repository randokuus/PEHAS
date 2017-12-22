<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Pdf rectangle type
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfRectangleType extends PdfArrayType {

    /**
     * Constructor
     *
     * @access public
     * @param int Lower left x
     * @param int Lower left y
     * @param int Upper right x
     * @param int Upper right y
     */
    function PdfRectangleType($llx = 0, $lly = 0, $urx = 0, $ury = 0) {
        $value = $this->_getRectangleArray($llx, $lly, $urx, $ury);
        parent::PdfArrayType($value);
    }

    /**
     * Get array of rectangle coordinates
     *
     * @access private
     * @param int Lower left x
     * @param int Lower left y
     * @param int Upper right x
     * @param int Upper right y
     * @return array
     */
    function &_getRectangleArray($llx, $lly, $urx, $ury) {
        $value = array(
            new PdfNumericType(intval($llx)),
            new PdfNumericType(intval($lly)),
            new PdfNumericType(intval($urx)),
            new PdfNumericType(intval($ury))
        );
        return $value;
    }

    /**
     * Set value
     *
     * If parameter is not array and count of elements != 4, method raise an error
     *
     * @access public
     * @param array Value to set
     */
    function setValue(&$value) {
        if (!is_array($value) || count($value) != 4) {
            trigger_error("Array of four PdfNumericType values only accepted\n" . var_export($value, true), E_USER_ERROR);
        }
        $this->_value = & $value;
    }

    /**
     * Set rectangle value
     *
     * @access public
     * @param int Lower left x
     * @param int Lower left y
     * @param int Upper right x
     * @param int Upper right y
     */
    function setRectangle($llx, $lly, $urx, $ury) {
        $value = $this->_getRectangleArray($llx, $lly, $urx, $ury);
        $this->setValue($value);
    }
}
