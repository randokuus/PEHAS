<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF Font object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfFont extends PdfDictionaryType {
    /**
     * @var string Name of font
     */
    var $xName;

    /**
     * Constructor
     *
     * Set initial value, type, list of mandatory values
     *
     * @access public
     */
    function PdfFont() {
        parent::PdfDictionaryType();
        $this->setType("Font");
    }

    /**
     * Set font subtype
     *
     * @access public
     * @param string Subtype name
     */
    function setSubtype($value) {
        return $this->addItem("Subtype", new PdfNameType($value));
    }

    /**
     * Set base font
     *
     * @access public
     * @param string Base font name
     */
    function setBaseFontName($value) {
        return $this->addItem("BaseFont", new PdfNameType($value));
    }
}
