<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Created PDF pages
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
class PdfPages extends PdfDictionaryType {

    /**
     * Constructor
     *
     * Set type and list of mandatory values
     *
     * @access public
     */
    function PdfPages() {
        $type = "Pages";
        parent :: PdfDictionaryType();
        $this->setType($type);
        $this->setMandatories(array (
            'Kids'
        ));
    }

    /**
     * Set parent of pages
     *
     * @access public
     * @param object parent of pages
     */
    function setParent(& $value) {
        $this->addItem("Parent", $value, "PdfDictionaryType");
    }

    /**
     * Set kids (list of pages)
     *
     * @access public
     * @param object list of pages
     */
    function setKids(& $value) {
        $this->addItem("Kids", $value, "PdfArrayType");
        $this->addItem("Count", new PdfNumericType($value->getLength()));
    }

    /**
        * Add page
        *
        * @access public
        * @param object Page object
     */
    function addPage(& $value) {
        if (empty ($value)) {
            return false;
        }
        $kids = $this->getItem("Kids");
        if ($kids === false) {
            $kids = & new PdfArrayType();
            $this->setKids($kids);
        }
        $kids->addItem($value);
        $this->addItem("Count", new PdfNumericType($kids->getLength()));
        $value->setParent($this);
    }
}
