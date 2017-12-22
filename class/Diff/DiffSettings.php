<?php
/**
 * @version $Revision: 643 $
 */

 /**
  * Diff Settings class
  *
  * @author Victor Buzyka <victor@itworks.biz.ua>
  */
class DiffSettings
{
    /**
     * Diff settings
     *
     * @access private
     * @var array
     */
    var $_settings;

    /**
     * List of style for result table cells
     *
     * @access private
     * @var array
     */
    var $_style_settings;

    /**
     * Old string head
     *
     * @access public
     * @var string
     */
    var $old_str_head;

    /**
     * New string head
     *
     * @access public
     * @var string
     */
    var $new_str_head;

    /**
     * Class constructor
     *
     * @return DiffSettings
     */
    function DiffSettings()
    {
        $this->old_str_head = "";
        $this->new_str_head = "";

        $this->_settings = array(
            "view_type" => "Inline",
            "arround_line" => "2",
            "ignore" => array(),
        );

        $this->_style_settings = array(
            "unmod" => "unmod",
            "add" => "add",
            "remove" => "remove",
            "modifi" => "modifi",
            "head" => "head",
            "space" => "space",
            "position" => "position",
            "table" => "table",
        );
    }

    /**
     * Set diff  settings parameter
     *
     * @param string name parameter_name
     * @param string value parameter value
     * @return bool
     */
    function setParameter($name, $value)
    {
        if (isset($this->_settings[$name])) {
            $this->_settings[$name] = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set draw settings param
     *
     * @param string name parameter_name
     * @param string value parameter value
     * @return bool
     */
    function setStyle($name, $value)
    {
        if (isset($this->_style_settings[$name])) {
            $this->_style_settings[$name] = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get style-class name by style neme
     *
     * @param string name style name
     * @return string class name
     */
     function getStyle($name)
     {
        if (isset($this->_style_settings[$name])) {
            return $this->_style_settings[$name];
        } else {
            return false;
        }
     }

    /**
     * Get param name
     *
     * @param string name parameter name
     * @return string parameter content
     */
     function getParameter($name)
     {
        if (isset($this->_settings[$name])) {
            return $this->_settings[$name];
        } else {
            return false;
        }
     }
}