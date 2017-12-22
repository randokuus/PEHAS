<?php
/**
 * @version $Revision: 639 $
 * @package modera_net
 */

/**
 * Abstract sequental xml parser (simple api for xml)
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @abstract
 */
class SaxParser
{
    /**
     * XML parser handle
     *
     * @var resource
     * @access protected
     */
    var $_parser;

    /**
     * Xml stack
     *
     * @var array stack of xml elements (array(xml_element, array(attribute => value, ..)))
     * @access private
     */
    var $_stack;

    /**
     * Collector of characted data for current element
     *
     * @var string
     * @access private
     */
    var $_data_collector;

    /**
     * XML parser encoding
     *
     * @var string|NULL
     */
    var $_encoding;

    /**
     * Constructor
     *
     * @param string $encoding parser encoding
     * @return SaxParser
     */
    function SaxParser($encoding = null)
    {
        $this->_encoding = $encoding;
    }

    /**
     * Xml parser initialization
     *
     * @access protected
     */
    function _init_parser()
    {
        $this->_stack = array();
        $this->_data_collector = '';
        $this->_parser = is_null($this->_encoding) ? xml_parser_create()
            : xml_parser_create($this->_encoding);
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($this->_parser, XML_OPTION_SKIP_WHITE, true);
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, '_start_element', '_end_element');
        xml_set_character_data_handler($this->_parser, '_character_data');
    }

    /**
     * Destructor
     *
     */
    function __destruct()
    {
        if (is_resource($this->_parser)) {
            xml_parser_free($this->_parser);
        }
    }

    /**
     * Manual destructor
     *
     * Will call __destruct method from current class if php version is older than
     * 5.0.0 newer php versions will execute destructor automatically
     */
    function destruct()
    {
        if (str_pad(str_replace('.', '', phpversion()), 3, '0') < 500) {
            $this->__destruct();
        }
    }

    /**
     * Start element handler
     *
     * @param resource $parser
     * @param string $name element name
     * @param array $attrs array of element attributes
     * @access protected
     */
    function _start_element($parser, $name, $attrs)
    {
        $this->_stack[] = array($name, $attrs);
        $this->_data_collector = '';
    }

    /**
     * End element handler
     *
     * @param resource $parser
     * @param string $name
     * @access protected
     */
    function _end_element($parser, $name)
    {
        list($name, $attrs) = array_pop($this->_stack);
        $this->_element_handler($this->depth(), $name, $this->_data_collector, $attrs);
        // clear data_collector in case if it will contain very big data
        $this->_data_collector = '';
    }

    /**
     * Character data handler
     *
     * @param resource $parser
     * @param string $data
     * @access protected
     */
    function _character_data($parser, $data)
    {
        $this->_data_collector .= $data;
    }

    /**
     * Element handler
     *
     * Should be implemented in subclasses
     *
     * @param int $depth element depth
     * @param string $name element name
     * @param string $data character data
     * @param array $attrs array of element attributes
     * @access protected
     * @abstract
     */
    function _element_handler($depth, $name, $data, $attrs) {}

    /**
     * Get current depth
     *
     * @return int
     * @access public
     */
    function depth()
    {
        return count($this->_stack);
    }

    /**
     * Start parsing xml document
     *
     * @param string $data
     * @param bool $is_final
     * @return bool TRUE on success or FALSE on failure
     * @access public
     */
    function parse($data, $is_final = false)
    {
        if (!$this->_parser) {
            $this->_init_parser();
        }

        $result = xml_parse($this->_parser, $data, $is_final);

        if ($is_final) {
            // reinit parser if previous xml_parse() was called with is_final
            // parameter set to true
            xml_parser_free($this->_parser);
            $this->_parser = null;
        }

        return $result;
    }

    /**
     * Parse full xml file
     *
     * @param string $file path to file
     * @param int $piece_size date from file will be readed by small pieces of this size
     * @access public
     */
    function parse_file($file, $piece_size = 4096)
    {
        $fp = fopen($file, 'r');
        while (true) {
            $data = fread($fp, $piece_size);
            if (feof($fp)) {
                $this->parse($data, true);
                break;
            }
            else {
                $this->parse($data);
            }
        }
    }

    /**
     * Get parser resource
     *
     * @return resource
     * @access public
     */
    function parser()
    {
        return $this->_parser;
    }
}
