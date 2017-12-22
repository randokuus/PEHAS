<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * Pdf array type
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfArrayType extends PdfType {

    /**
     * Constructor
     *
     * @access public
     * @param array Initial value
     */
    function PdfArrayType($value = array()) {
        parent::PdfType($value);
    }

    /**
     * Set value
     *
     * If parameter is not array, method raise an error
     *
     * @access public
     * @param array Value to set
     */
    function setValue(&$value) {
        if (!is_array($value)) {
            trigger_error("Array values only accepted\n" . var_export($value, true), E_USER_ERROR);
        }
        $this->_value = & $value;
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @param int Level in PDF object hierarchy
     * @return string
     */
    function getValueCode($level = 0) {
        $value = "";
        $indent = str_pad('', ($level - 1) * $this->_indentValue);
        $item_indent = str_pad('', $level * $this->_indentValue);
        foreach ($this->_value as $k => $v) {
            if (!is_object($this->_value[$k]) || !method_exists($this->_value[$k], 'getValueCode')) {
                trigger_error("Invalid array item\n" . var_export($this->_value[$k], true), E_USER_ERROR);
            } else {
                $value .= "\n$item_indent";
                if ($this->_value[$k]->getId() > 0) {
                    $value .= $this->_value[$k]->getId() . " 0 R";
                } else {
                    $value .= $this->_value[$k]->getValueCode($level + 1);
                }
            }
        }
        return "[{$value}\n{$indent}]";
    }

    /**
     * Add item to array value
     *
     * @access public
     * @param mixed Value to add in array
     */
    function addItem($value) {
        $this->_value[] = & $value;
    }

    /**
     * Get count of items
     *
     * @access public
     * @return int
     */
    function getLength() {
        return count($this->_value);
    }
}
