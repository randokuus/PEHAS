<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Created PDF Contents object
 *
 * The pages of a document are accessed through a structure known as the page tree,
 * which defines their ordering within the document. The tree structure allows PDF
 * viewer applications to quickly open a document containing thousands of pages
 * using only limited memory.
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfContents extends PdfStreamType {

    /**
     * Add save state command
     *
     * @access public
     */
    function saveState() {
        $this->_stream .= "q\n";
    }

    /**
     * Add restore state command
     *
     * @access public
     */
    function restoreState() {
        $this->_stream .= "Q\n";
    }

    /**
     * Add CTM command
     *
     * @access public
     * @param int CTM a-value
     * @param int CTM b-value
     * @param int CTM c-value
     * @param int CTM d-value
     * @param int CTM e-value
     * @param int CTM f-value
     */
    function setCTM($a, $b, $c, $d, $e, $f) {
        $this->_stream .= intval($a) . ' ' . intval($b) . ' ' . intval($c) . ' ' .
        intval($d) . ' ' . intval($e) . ' ' . intval($f) . " cm\n";
    }

    /**
     * Add do XObject command
     *
     * @access public
     * @param object XObject
     */
    function doXObject(& $obj) {
        if (!isset ($obj->xName)) {
            trigger_error("Object in not a XObject", E_USER_ERROR);
        }
        $this->_stream .= "/" . $obj->xName . " Do\n";
    }

    /**
     * Add begin text command
     *
     * @access public
     */
    function beginText() {
        $this->_stream .= "BT\n";
    }

    /**
     * Add end text command
     *
     * @access public
     */
    function endText() {
        $this->_stream .= "ET\n";
    }

    /**
     * Add set font command
     *
     * @access public
     * @param object Font object
     * @param int Size
     */
    function setFont(& $font, $size) {
        if (!isset ($font->xName)) {
            trigger_error("Invalid font object", E_USER_ERROR);
        }
        $this->_stream .= "/" . $font->xName . " $size Tf\n";
    }

    /**
     * Add set text position command
     *
     * @access public
     * @param int left offset
     * @param int bottom osset
     */
    function setTextPos($x, $y) {
        $this->_stream .= intval($x) . " " . intval($y) . " Td\n";
    }

    /**
     * Add text out command
     *
     * @access public
     * @param string String to output
     */
    function outText($string) {
        $string = & new PdfStringType($string);
        $this->_stream .= $string->getValueCode() . " Tj\n";
    }
}
