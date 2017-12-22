<?php
/**
 * @version $Revision: 333 $
 * @package Pdf
 */

/**
 * PDF dictionary type objects
 *
 * @author Gleb Sinkovskiy <gleb.sinkovsky@modera.net>
 * @author Victor Buzyka <viktor.buzyka@modera.net>
 * @package Pdf
 */
class PdfDictionaryType extends PdfType {

    /**
     * @access private
     * @var array List of mandatory items
     */
    var $_mandatories = array ();

    /**
     * Constructor
     *
     * Set initial value
     *
     * @access public
     * @param array Initial value
     */
    function PdfDictionaryType($value = array ()) {
        parent :: PdfType($value);
    }

    /**
     * Set value
     *
     * @access public
     * @param array Value to set
     */
    function setValue(& $value) {
        if (!is_array($value)) {
            trigger_error("Array values only accepted\n" . var_export($value, true), E_USER_ERROR);
        }
        $this->_value = & $value;
    }

    /**
     * Add fields to mandatories list
     *
     * @access public
     * @param array Fields list
     */
    function setMandatories($names) {
        if (!is_array($names)) {
            trigger_error("Array values only accepted\n" . var_export($names, true), E_USER_ERROR);
        }
        $this->_mandatories = array_merge($this->_mandatories, $names);
    }

    /**
        * Add item to dictionary
        *
        * If type(s) is specified, method checks type of item to add. If type of value
        * is different, method raise an error
        *
        * @access public
        * @param string Item name
        * @param mixed Value to add
        * @param mixed Value type (array of strings or string)
     */
    function addItem($name, & $value, $type = false) {
        if (empty ($name)) {
            return false;
        }
        if ($type !== false) {
            $this->_checkItemType($value, $name, $type);
        }
        $this->_value[$name] = & $value;
        return true;
    }

    /**
        * Get item from dictionary
        *
        * @access public
        * @param string Item name
        * @return mixed Item value
     */
    function & getItem($name) {
        if (!isset ($this->_value[$name])) {
            $tmp = false;
            return $tmp;
        }
        return $this->_value[$name];
    }

    /**
        * Set Type item
        *
        * @access public
        * @param mixed Type name (string or PdfNameType object)
     */
    function setType($value) {
        if (empty ($value)) {
            return false;
        }
        if (!is_object($value)) {
            $value = & new PdfNameType($value);
        }
        return $this->addItem("Type", $value, "PdfNameType");
    }

    /**
     * Get PDF-code for object
     *
     * @access public
     * @return string
     */
    function getValueCode($level = 0) {
        $value = "";
        foreach ($this->_mandatories as $name) {
            if (!isset ($this->_value[$name])) {
                trigger_error("Required value of $name in " . get_class($this) . " not found", E_USER_ERROR);
            }
        }
        $indent = str_pad('', ($level -1) * $this->_indentValue);
        $item_indent = str_pad('', $level * $this->_indentValue);
        foreach ($this->_value as $k => $v) {
            if (!is_object($this->_value[$k]) || !method_exists($this->_value[$k], 'getValueCode')) {
                print "$k\n";
                trigger_error("Invalid array item\n" . var_export($this->_value[$k], true), E_USER_ERROR);
            } else {
                if ($this->_value[$k]->getId() > 0) {
                    $value .= "$item_indent/$k " . $this->_value[$k]->getId() . " 0 R\n";
                } else {
                    $value .= "$item_indent/$k " . $this->_value[$k]->getValueCode($level +1) . "\n";
                }
            }
        }
        return "<<\n{$value}{$indent}>>";
    }

    /**
     * Check value type
     *
     * Method used in AddItem to check type of added item.
     * If value has invalid type - method raise an error
     *
     * @access private
     * @param object Object to check
     * @param string Item name
     * @param mixed Accessible type(s) (string or array)
     */
    function _checkItemType(& $value, $name, $type) {
        if (!is_array($type)) {
            $type = array (
                $type
            );
        }
        if (version_compare(phpversion(), '4.2.0') < 0) {
            return;
        }
        foreach ($type as $t) {
            if (is_a($value, $t)) {
                return;
            }
        }
        trigger_error("Invalid $name type, accept only " . implode(", ", $type) . "\n" /* . var_export($value, true)*/
        , E_USER_ERROR);
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
