<?php
/**
 * @version $Revision: 638 $
 * @package modera_net
 */

require_once(SITE_PATH . '/class/SaxParser.php');

/**
 * Sequental callback xml parser
 *
 * @author Alexandr Chertkov <s6urik@modera.net>
 * @author Lili Gots <lili.gots@modera.net>
 */
class SaxParserCb extends SaxParser
{
    /**
     * Buffer for collecting array
     *
     * @var array
     * @access private
     */
    var $_array;

    /**
     * Callback parameters
     *
     * @var array [level => [element => [[callback_function, array_of_extra_params]]]]
     * @access private
     */
    var $_callback;

    /**
     * Minimum depth on which elements are processed/catched
     *
     * If FALSE than no callback was set
     *
     * @var int|FALSE
     * @access private
     */
    var $_min_depth;

    /**
     * Names of elements that are cactched on minimum level of depth
     *
     * @var array
     * @access private
     */
    var $_min_depth_elements;

    /**
     * Constructor
     *
     * @param string $encoding parser encoding
     * @return SaxParserCb
     */
    function SaxParserCb($encoding = null)
    {
        parent::SaxParser($encoding);
        $this->_callback = array();
        $this->_min_depth = false;
        $this->_min_depth_elements = array();
    }

    function _init_parser()
    {
        parent::_init_parser();
        $this->_array = array();
    }

    function _start_element($parser, $name, $attrs)
    {
        parent::_start_element($parser, $name, $attrs);
        $this->_array[] = array('VALUE' => '');
    }

    /**
     * Parsed element handler
     *
     * @param int $depth
     * @param string $name
     * @param string $data
     * @param array $attrs
     * @access protected
     */
    function _element_handler($depth, $name, $data, $attrs)
    {
        // minimum depth filtering out elements for which we don't have callback functions
        if (false === $this->_min_depth || $depth < $this->_min_depth ||
            ($depth == $this->_min_depth && !in_array($name, $this->_min_depth_elements)))
        {
            $this->_array = array();
            return;
        }

        // pop this element off the node stack
        $element = array_pop($this->_array);

        $data = trim($data);
        if ('' != $data) {
            $element['VALUE'] = $data;
        }
        $element['ATTRIBUTES'] =  $attrs;

        //calling callback functions
        if (isset($this->_callback[$depth][$name])) {
            foreach ($this->_callback[$depth][$name] as $callback) {
                list($cb_fname, $cb_extra) = $callback;
                call_user_func($cb_fname, $depth, $name, $element, $cb_extra);
            }
        }

        // add popped element to the end of stack
        if ($this->_array) {
            end($this->_array);
            $key = key($this->_array);
            if (is_array($this->_array[$key]["VALUE"])) {
                $this->_array[$key]["VALUE"][$name] = $element;
            } else {
                $this->_array[$key]["VALUE"] = array($name => $element);
            }
        }
    }

    /**
     * Return minimum catched depth
     *
     * @return int|FALSE false if callback function was not set
     * @access private
     */
    function _min_depth()
    {
        if ($this->_callback) {
            reset($this->_callback);
            return key($this->_callback);
        } else {
            return false;
        }
    }

    /**
     * Array of catched elements at minimal depth
     *
     * @return array
     * @access private
     */
    function _min_depth_elements()
    {
        $elements = array();
        $min_depth = $this->_min_depth();
        if (count($this->_callback[$min_depth]) > 0) {
            $elements = array_keys($this->_callback[$min_depth]);
        }

        return $elements;
    }

    /**
     * Set callback function
     *
     * Callback function must have 3 parameters:
     * $depth - element depth (level)
     * $element - element name
     * $data - element data
     *
     * @param int $level
     * @param string $element
     * @param string|array $callback callback function name or array
     * @param array $extra array of extra data passed to callback function
     *  (in case if callback is method)
     */
    function set_callback($level, $element, $callback, $extra = array())
    {
        if (!isset($this->_callback[$level][$element])) {
            $this->_callback[$level][$element] = array();
        }

        $this->_callback[$level][$element][] = array($callback, $extra);
        ksort($this->_callback);

        $this->_min_depth = $this->_min_depth();
        $this->_min_depth_elements = $this->_min_depth_elements();
    }

    /**
     * Remove callback function
     *
     * @param int $level
     * @param string $element
     */
    function remove_callback($level, $element)
    {
        if (isset($this->_callback[$level][$element])) {
            unset($this->_callback[$level][$element]);
        }
        if (!$this->_callback[$level]) {
            unset($this->_callback[$level]);
        }

        $this->_min_depth = $this->_min_depth();
        $this->_min_depth_elements = $this->_min_depth_elements();
    }

    /**
     * Remove all registered callback functions
     *
     */
    function clear_all_calbacks()
    {
        $this->_callback = array();
        $this->_min_depth = false;
        $this->_min_depth_elements = array();
    }
}
