<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF image object
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfImage extends PdfStreamType {
    /**
     * @var string Name of image XObject
     */
    var $xName;

    /**
     * Constructor
     *
     * Set initial width & height, type, list of mandatory values
     *
     * @access public
     * @param int Width
     * @param int Height
     */
    function PdfImage($img_width, $img_height) {
        parent :: PdfStreamType();
        $type = & new PdfNameType("XObject");
        $subtype = & new PdfNameType("Image");
        $img_width = & new PdfNumericType($img_width);
        $img_height = & new PdfNumericType($img_height);
        $color_space = & new PdfNameType("DeviceRGB");
        $bits_per_components = & new PdfNumericType("8");
        $this->setType($type);
        $this->setMandatories(array (
            'Type',
            'Subtype',
            'Width',
            'Height',
            'ColorSpace',
            'BitsPerComponent'
        ));
        $this->setWidth($img_width);
        $this->setHeight($img_height);
        $this->setSubtype($subtype);
        $this->setColorSpace($color_space);
        $this->setBitsPerComponent($bits_per_components);
    }

    /**
     * Set image subtype
     *
     * @access public
     * @param object Subtype
     */
    function setSubtype(& $value) {
        $this->addItem("Subtype", $value, "PdfNameType");
    }

    /**
        * Set width
        *
        * @access public
        * @param object Width
     */
    function setWidth(& $value) {
        $this->addItem("Width", $value, "PdfNumericType");
        return true;
    }

    /**
     * Set height
     *
     * @access public
     * @param object Height
     */
    function setHeight($value) {
        $this->addItem("Height", $value, "PdfNumericType");
        return true;
    }

    /**
        * Set colorspace
        *
        * @access public
        * @param object colorspace
     */
    function setColorSpace(& $value) {
        $this->addItem("ColorSpace", $value, array (
            "PdfNameType",
            "PdfArrayType"
        ));
    }

    /**
     * Set count of bits per component
     *
     * @access public
     * @param object count of bits per component
     */
    function setBitsPerComponent(& $value) {
        $this->addItem("BitsPerComponent", $value, "PdfNumericType");
        return true;
    }

    /**
     * Set image content
     *
     * @access public
     * @param string image content
     */
    function setImageContent($image_content) {
        $this->setFilteredStream($image_content, "DCTDecode");
    }

}
