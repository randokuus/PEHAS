<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF stream type objects
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfStreamType extends PdfDictionaryType {
    /**
     * @access private
     * @var string Stream content
     */
    var $_stream;

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param array Initial dictionary value
     * @param string Initial stream data
     */
    function PdfStreamType($value = array (), $stream_data = '') {
        $this->setValue($value, $stream_data);
        $this->setMandatories(array (
            'Length'
        ));
    }

    /**
     * Set value
     *
     * @access public
     * @param array Initial dictionary value
     * @param string Initial stream data
     */
    function setValue(& $value, $stream_data) {
        if (!is_array($value)) {
            trigger_error("Array values only accepted\n" . var_export($value, true), E_USER_ERROR);
        }
        $this->_value = & $value;
        $this->setStream($stream_data);
    }

    /**
     * Set stream data
     *
     * @access public
     * @param string Initial stream data
     * @param string Filter name
     */
    function setStream($stream_data, $filter_name = '') {
        $filtered_data = $this->_getFilteredData($stream_data, $filter_name);
        $this->setFilteredStream($filtered_data, $filter_name);
    }

    /**
     * Set filtered stream data
     *
     * @access public
     * @param string Initial stream data
     * @param string Filter name
     */
    function setFilteredStream($stream_data, $filter_name) {
        $this->_stream = $stream_data;
        $this->setFilterByName($filter_name);
    }

    /**
     * Get stream data
     *
     * @access public
     * @return string Stream data encoded by current filter
     */
    function & getStream() {
        return $this->_stream;
    }

    /**
     * Set filter
     *
     * @access public
     * @param object Filter name
     */
    function setFilter(& $value) {
        $this->addItem("Filter", $value, array (
            "PdfNameType",
            "PdfArrayType"
        ));
    }

    /**
     * Set filter by string name
     *
     * @access public
     * @param string Filter name
     */
    function setFilterByName($name) {
        if ($name) {
            $filter = & new PdfNameType($name);
            $this->setFilter($filter);
        }
    }

    /**
     * Get encoded data
     *
     * @access private
     * @param string Data to encode
     * @param string Filter name
     * @return string
     */
    function _getFilteredData($data, $filter_name) {
        switch ($filter_name) {
            case 'ASCIIHexDecode' :
                $data = $this->_getFilteredByASCIIHexDecode($data);
                break;
            case 'FlateDecode' :
                if (!function_exists('gzcompress')) {
                    trigger_error('Zlib required to decompress FlateDecoded streams', E_USER_ERROR);
                }
                $data = gzcompress($data);
                break;
        }
        return $data;
    }

    /**
     * Get data, encoded by ASCIIHex filter
     *
     * @access private
     * @param string Data to encode
     * @return string
     */
    function _getFilteredByASCIIHexDecode($value) {
        $lines = '';
        while (strlen($value) > 40) {
            $lines .= strtoupper(bin2hex(substr($value, 0, 40))) . "\n";
            $value = substr($value, 40);
        };
        $lines .= strtoupper(bin2hex($value));
        return $lines;
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode($level = 0) {
        $this->addItem('Length', new PdfNumericType(strlen($this->_stream)));
        $value = parent :: getValueCode($level) . "\nstream\n" . $this->_stream . "\nendstream\n";
        return $value;
    }
}
