<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF page resources object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfResources extends PdfDictionaryType {

    /**
     * Set procset
     *
     * @access public
     * @param object Procset
     */
    function setProcSet(&$value) {
        $this->addItem("ProcSet", $value, "PdfArrayType");
    }

    /**
     * Get procset
     *
     * @access public
     * @return object
     */
    function &getProcSet() {
        return $this->getItem("ProcSet");
    }

    /**
     * Set font item
     *
     * @access public
     * @param object Fonts dictionary
     */
    function setFont(& $value) {
        $this->addItem("Font", $value, "PdfDictionaryType");
    }

    /**
     * Add XObject
     *
     * @access public
     * @param object XObject
     */
    function addXObjectItem(&$obj){
        $x_object = & $this->getItem("XObject");
        if ($x_object === false){
            $x_object =& new PdfDictionaryType();
            $this->addItem("XObject", $x_object);
        }
        $obj->xName = 'xobj' . ($x_object->getLength() + 1);
        $x_object->addItem($obj->xName, $obj);
    }

    /**
     * Add font
     *
     * @access public
     * @param object Font
     */
    function addFont(&$obj) {
        $fonts = & $this->getItem("Font");
        if ($fonts === false) {
            $fonts =& new PdfDictionaryType();
            $this->addItem("Font", $fonts);
        }
        $obj->xName = 'F' . ($fonts->getLength() + 1);
        $fonts->addItem($obj->xName, $obj);
    }
}
