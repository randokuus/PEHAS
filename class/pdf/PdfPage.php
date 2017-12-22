<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF page object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfPage extends PdfDictionaryType {

    /**
     * Constructor
     *
     * Set resources and media box
     *
     * @access public
     * @param object resources of the page
     * @param object media box of the page
     */
    function PdfPage(& $resources, & $media_box) {
        $type = "Page";
        parent :: PdfDictionaryType();
        $this->setType($type);
        $this->setResources($resources);
        $this->setMediaBox($media_box);
        $this->setMandatories(array (
            "Parent",
            "Resources",
            "MediaBox"
        ));
    }

    /**
       * Set parent of page
       *
       * @access public
       * @param object parent of page (Pages object)
     */
    function setParent(& $value) {
        $this->addItem("Parent", $value, "PdfDictionaryType");
    }

    /**
     * Set resources of page
     *
     * @access public
     * @param object resources of page
     */
    function setResources(& $value) {
        $this->addItem("Resources", $value, "PdfDictionaryType");
    }

    /**
     * Get resources of page
     *
     * @access public
     * @return object
     */
    function & getResources() {
        return $this->getItem("Resources");
    }

    /**
     * Set media box
     *
     * @access public
     * @param object media box
     */
    function setMediaBox(& $value) {
        $this->addItem("MediaBox", $value, "PdfRectangleType");
    }

    /**
     * Set page contents
     *
     * @access public
     * @param object contents
     */
    function setContents(& $value) {
        $this->addItem("Contents", $value, array (
            "PdfArrayType",
            "PdfStreamType"
        ));
    }

    /**
     * Get page contents
     *
     * @access public
     * @return object
     */
    function & getContents() {
        return $this->getItem("Contents");
    }
}
