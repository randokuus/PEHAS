<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */


// include PDF Types
require_once (SITE_PATH . '/class/pdf/PdfType.php');
require_once (SITE_PATH . '/class/pdf/PdfBoolType.php');
require_once (SITE_PATH . '/class/pdf/PdfStringType.php');
require_once (SITE_PATH . '/class/pdf/PdfHexType.php');
require_once (SITE_PATH . '/class/pdf/PdfNameType.php');
require_once (SITE_PATH . '/class/pdf/PdfNumericType.php');
require_once (SITE_PATH . '/class/pdf/PdfArrayType.php');
require_once (SITE_PATH . '/class/pdf/PdfRectangleType.php');
require_once (SITE_PATH . '/class/pdf/PdfDictionaryType.php');
require_once (SITE_PATH . '/class/pdf/PdfStreamType.php');
// include PDF objects
require_once (SITE_PATH . '/class/pdf/PdfCatalog.php');
require_once (SITE_PATH . '/class/pdf/PdfImage.php');
require_once (SITE_PATH . '/class/pdf/PdfPages.php');
require_once (SITE_PATH . '/class/pdf/PdfPage.php');
require_once (SITE_PATH . '/class/pdf/PdfResources.php');
require_once (SITE_PATH . '/class/pdf/PdfContents.php');
require_once (SITE_PATH . '/class/pdf/PdfFont.php');
require_once (SITE_PATH . '/class/pdf/PdfTrueTypeFont.php');

/**
 * PDF class
 *
 * A PHP class to provide the basic functionality to create a pdf document without
 * any requirement for additional modules.
 *
 * Specified features:
 *  - create PDF-document
 *  - manage pages
 *  - manage fonts
 *  - add image to document
 *  - add text blocks with several formatting options such as font family, size, style, alignment
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class Pdf {
    /**
     * @access private
     * @var array List of PDF-file objects
     */
    var $_objects;

    /**
     * @access private
     * @var object Link to PDF-catalog object
     */
    var $_catalog;

    /**
     * @access private
     * @var object Link to PDF-pages object
     */
    var $_pages;

    /**
     * @access private
     * @var array List of errors
     */
    var $_errorList = array(
        "01" => "Image file does not exists",
        "02" => "Image file does not readed",
        "03" => "Image file is not image"
        );

    /**
     * @access private
     * @var string Last error code
     */
    var $_lastError = false;

    /**
     * Constructor
     *
     * Create basic PDF-file objects: catalog and pages
     *
     * @access public
     */
    function Pdf() {
        $this->createDocument();
    }

    /**
     * Add object to list of PDF-objects
     *
     * @access private
     * @param object
     */
    function _addObject(& $obj) {
        if (empty ($obj)) {
            return false;
        }
        $obj->setId(count($this->_objects) + 1);
        $this->_objects[] = & $obj;
    }

    /**
     * Get PDF-file header
     *
     * @access private
     * @return string
     */
    function _getHeader() {
        return "%PDF-1.3\n%" . chr(128) . chr(128) . chr(128) . chr(128);
    }

    /**
     * Get PDF-file body
     *
     * @access private
     * @param int PDF-header length
     * @return string
     */
    function _getBody($header_length) {
        $content = '';
        for ($i = 0; $i < count($this->_objects); $i++) {
            $this->_objects[$i]->position = $header_length + strlen($content);
            $content .= $this->_objects[$i]->getId() . " 0 obj\n" . $this->_objects[$i]->getValueCode(1) . "\nendobj\n\n";
        }
        return $content;
    }

    /**
     * Get PDF cross-reference table
     *
     * @access private
     * @return string
     */
    function _getCRTable() {
        $content = "xref\n0 " . (count($this->_objects) + 1) . "\n0000000000 65535 f\n";
        for ($i = 0; $i < count($this->_objects); $i++) {
            $content .= sprintf("%010d", $this->_objects[$i]->position) . " 00000 n\n";
        }
        return $content;
    }

    /**
     * Get PDF-file trailer
     *
     * @access private
     * @param int offset of cross-reference table
     */
    function _getTrailer($xref_position) {
        $content = "trailer\n<<\n" .
            "/Size " . count($this->_objects) . "\n" .
            "/Root " . $this->_catalog->getId() . " 0 R\n>>\nstartxref\n$xref_position\n%%EOF";
        return $content;
    }

    /**
     * Get PDF-file content
     *
     * @access public
     * @return string
     */
    function output() {
        $header = $this->_getHeader() . "\n";
        $body = $this->_getBody(strlen($header));
        $crt = $this->_getCRTable() . "\n";
        return $header . $body . $crt . $this->_getTrailer(strlen($header) + strlen($body));
    }

    /**
     * Create PDF-document
     *
     * Creates basic PDF-file objects: catalog and pages.
     * If they are exists - it will be removed and replaced by new objects.
     *
     * @access public
     */
    function createDocument() {
        $this->_objects = array();
        $catalog = & new PdfCatalog();
        if ($catalog === false) {
            return false;
        }
        $pages = & new PdfPages();
        if ($pages === false) {
            return false;
        }
        $catalog->setPages($pages);
        $this->_addObject($catalog);
        $this->_catalog = & $catalog;
        $this->_addObject($pages);
        $this->_pages =& $pages;
        return true;
    }

    /**
     * Add new page to object list
     *
     * As parameters coordinates of rectangular area on page are used
     * where the object of page will be placed.
     * If page have successfully created, method returns the reference to page object.
     * If page haven't created, method returns false
     *
     * @param int llx Lower-left x
     * @param int lly Lower-left y
     * @param int urx Upper-right x
     * @param int ury Upper-right y
     * @return object Page object
     */
    function addPage($llx, $lly, $urx, $ury) {
        if (!is_numeric($llx) || !is_numeric($lly) || !is_numeric($urx) || !is_numeric($ury)){
            $tmp = false;
            return $tmp;
        }
        $resources = & new PdfResources();
        $procset =& new PdfArrayType(array(new PdfNameType('Pdf')));
        $resources->setProcSet($procset);
        $this->_addObject($resources);
        $page =& new PdfPage($resources, new PdfRectangleType($llx, $lly, $urx, $ury));
        $contents =& new PdfContents();
        $this->_addObject($contents);
        $page->setContents($contents);
        $this->_addObject($page);
        $this->_pages->addPage($page);
        return $page;
    }

    /**
     * Add text string to page content
     *
     * @access public
     * @param string String to output
     * @param object Font object which used for string output
     * @param object Page object which used for string output
     * @param int Left offset of string, picas
     * @param int Bottom offset of string, picas
     * @param int Font size, picas
     */
    function addText($string, &$font, &$page, $x, $y, $size) {
        // Add font to page resources
        $resources =& $page->getResources();
        if ($resources === false){
            trigger_error("Resources not found in page.", E_USER_ERROR);
        }
        $resources->addFont($font);

        // add Text to page
        $contents =& $page->getContents();
        if ($contents === false){
            trigger_error("Contents not found in page.", E_USER_ERROR);
        }
        $contents->beginText();
        $contents->setFont($font, $size);
        $contents->setTextPos($x, $y);
        $contents->outText($string);
        $contents->endText();
    }

    /**
     * Add image to page
     *
     * @access public
     * @param string Filename of image file
     * @param object Page object which used for image output
     * @param int Left offset of string, picas
     * @param int Bottom offset of string, picas
     * @return object Image object
     */
    function &addImage($image_file, &$page, $x, $y) {
        if (!file_exists($image_file)){
            $this->_setError("01");
            $result= false;
            return $result;
        }

        $image_info = getimagesize($image_file);
        if (is_null($image_info)){
            $this->_setError("03");
            $result=false;
            return $result;
        }
        // set image size and create image object
        if (!is_numeric($image_info[0])){
            $this->_setError("03");
            $result=false;
            return $result;
        }else{
            $img_width =  $image_info[0];
        }
        if (!is_numeric($image_info[1])){
            $this->_setError("03");
            $result=false;
            return $result;
        }else{
            $img_height =  $image_info[1];
        }
        $image = & new PdfImage ($img_width, $img_height);
        $this->_addObject($image);

        // set color space
        if ($image_info['channels'] == 4){
            $color_space =& new PdfNameType("DeviceCMYK");
            $image->setColorSpace($color_space);
        }else{
            $color_space =& new PdfNameType("DeviceRGB");
            $image->setColorSpace($color_space);
        }

        // set bits per component
        if (isset($image_info['bits'])){
           $bits_per_components =& new PdfNumericType($image_info['bits']);
           $image->setBitsPerComponent($bits_per_components);
        }

        if ($fh = fopen($image_file, "rb")){
            $image_content = fread($fh, filesize($image_file));
            fclose($fh);
            if (!empty($image_content)){
                $image->setImageContent($image_content);
            }
        }else{
            $this->_setError("02");
            $result=false;
            return $result;
        }

        // get page resources
        $resources =& $page->getResources();
        if ($resources === false){
            trigger_error("Resources not found in page.", E_USER_ERROR);
        }
        $resources->addXObjectItem($image);

        $procset =& $resources->getProcSet();
        if ($procset === false){
            trigger_error("ProcSet not found in page resources.", E_USER_ERROR);
        }
        $procset->addItem(new PdfNameType('ImageB'));

        // add image to page
        $contents =& $page->getContents();
        if ($contents === false){
            trigger_error("Contents not found in page.", E_USER_ERROR);
        }
        $contents->saveState();
        $contents->setCTM($img_width, 0, 0, $img_height, $x, $y);
        $contents->doXObject($image);
        $contents->restoreState();
/*        var_dump($page->getContents());
        var_dump($contents);
        exit();*/

        return $image;
    }

    /**
     * Add TrueType font to document
     *
     * @access public
     * @param string Font name
     * @return object Font object
     */
    function &addTrueTypeFont($font_name) {
        $font = & new PdfTrueTypeFont();
        if ($font === false) {
            return false;
        }
        $font->setBaseFontName($font_name);
        $this->_addObject($font);
        return $font;
    }

    /**
     * Set error
     *
     * @access private
     */
    function _setError($id){
        if (array_key_exists($id, $this->_errorList)){
            $this->_lastError = $id;
        }else{
            return false;
        }
    }

    /**
     * Get ID of last error
     *
     * @access public
     * @return string Error code
     */
    function getLastErrorID(){
        return $this->_lastError;
    }

    /**
     * Get description of last error
     *
     * @access public
     * @return string Error description
     */
    function getLastErrorDesc(){
        return $this->_errorList[$this->_lastError];
    }
}
