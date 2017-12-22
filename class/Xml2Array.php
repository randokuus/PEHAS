<?php
/**
 * @version $Revision: 642 $
 */

require_once(SITE_PATH . '/class/SaxParser.php');

/**
 * Static class for converting XML data into php array
 *
 * @author Alexandr Chertkov <alexandr.chertkov@modera.net>
 * @author Lili Gots <lili.gots@modera.net>
 */
class Xml2Array extends SaxParser
{
    /**
     * Buffer for collecting array
     *
     * @var array
     * @access private
     */
    var $_array;

    function Xml2Array($encoding = null)
    {
        parent::SaxParser($encoding);
    }

    function _init_parser()
    {
        parent::_init_parser();
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        $this->_array = array();
    }

    function _element_handler($depth, $name, $data, $attrs)
    {
        // fetch element placeholder
        $element = array_pop($this->_array);

        // remove reserved names from attributes array
        foreach (array('_DATA', '_ELEMENTS', '_NAME') as $k) {
            if (array_key_exists($k, $attrs)) {
                unset($attrs[$k]);
            }
        }

        // create final element array
        $element = array_merge(array("_NAME" => $name, "_DATA" => trim($data)), $attrs
            , array("_ELEMENTS" => array()), $element);

        if (0 == $this->depth()) {
            $this->_array[] = $element;
        } else {
            $this->_array[$this->depth() - 1]["_ELEMENTS"][] = $element;
        }
    }

    function _start_element($parser, $name, $attrs)
    {
        parent::_start_element($parser, $name, $attrs);
        // create element placeholder
        $this->_array[] = array("_NAME" => $name);
    }

    /**
     * Parse xml into array
     *
     * @param string $xml
     * @return array|FALSE
     */
    function parse($xml, $is_final = true)
    {
        if (!parent::parse($xml, true)) {
            return false;
        }
        // clear internal array variable
        $array = $this->_array;
        $this->_array = null;
        return $array;
    }
}