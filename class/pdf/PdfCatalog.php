<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Create PDF catalog
 *
 * The root of a PDF document's object hierarchy. The catalog
 * contains references to other objects defining the document’s contents, outline,
 * article threads, named destinations, and other attributes. In
 * addition, it contains information about how the document should be displayed
 * on the screen, such as whether its outline and thumbnail page images should be
 * displayed automatically and whether some location other than the first page
 * should be shown when the document is opened.
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfCatalog extends PdfDictionaryType {

    /**
     * Constructor
     *
     * Set initial value, type, list of mandatory values
     *
     * @access public
     * @param array Initial value
     */
    function PdfCatalog() {
        parent :: PdfDictionaryType();
        $type = "Catalog";
        $this->setType($type);
        $this->setMandatories(array (
            "Pages"
        ));
    }

    /**
        * Set pages
        *
        * @access public
        * @param object Pages object
     */
    function setPages(& $value) {
        $this->addItem("Pages", $value, "PdfDictionaryType");
    }

    /**
     * Set outlines object
     *
     * @access public
     * @param object Outlines object
     */
    function setOutlines(& $value) {
        $this->addItem("Outlines", $value, "PdfDictionaryType");
    }

    /**
     * Set URI object
     *
     * @access public
     * @param object URI object
     */
    function setUri(& $value) {
        $this->addItem("URI", $value, "PdfDictionaryType");
    }

    /**
     * Set AcroForm object
     *
     * @access public
     * @param object AcroForm object
     */
    function setAcroForm(& $value) {
        $this->addItem("AcroForm", $value, "PdfDictionaryType");
    }
}
