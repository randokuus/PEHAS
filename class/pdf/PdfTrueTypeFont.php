<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF TrueType Font object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfTrueTypeFont extends PdfFont {

    /**
     * Constructor
     *
     * Set initial value, type, list of mandatory values
     *
     * @access public
     */
    function PdfTrueTypeFont() {
        parent::PdfFont();
        $this->setSubtype("TrueType");
    }
}
