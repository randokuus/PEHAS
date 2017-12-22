<?php
/**
 * @version $Revision: 643 $
 */

/**
 * Diff show interfase
 *
 * @abstract
 * @author Victor Buzyka <victor@itworks.biz.ua>
 */
class DiffDrawInterface
{
    /**
     * Splited old string
     *
     * @var array
     */
    var $old_str;

    /**
     * Splited new string
     *
     * @var array
     */
    var $new_str;

    /**
     * Settings object reference
     *
     * @var DiffSettings
     */
    var $settings;

    var $start_point = 0;

    /**
     * Constructor
     *
     * @param string $old first string
     * @param string $new second string
     * @param object $settings settings object
     * @return DiffDrawInterface
     */
    function DiffDrawInterface($old, $new, &$settings)
    {
        $this->settings = &$settings;
        $this->old_str = $old;
        $this->new_str = $new;
    }

    /**
     * Draw added strings
     *
     * @abstract
     * @param int old_pos old string position
     * @param array new_pos inserted string index
     * @return string HTML-content string
     */
    function Add($old_pos, $new_pos) {
    }

    /**
     * Draw modifid strings
     *
     * @abstract
     * @param int old_pos old string position
     * @param array new_pos inserted string index
     * @return string HTML-content string
     */
    function Modifi($old_pos, $new_pos) {
    }

    /**
     * Draw deleted strings
     *
     * @abstract
     * @param array old_pos inserted string index
     * @param int new_pos new string position
     * @return string HTML-content string
     */
    function Delete($old_pos, $new_pos) {
    }

    /**
     * Draw skiped string between modify pieces
     *
     * @abstract
     * @return string HTML-content string
     */
    function Skip() {
    }

    /**
     * Draw deleted strings
     *
     * @abstract
     * @param int old_pos old string position
     * @param int new_pos new string position
     * @param string direction can be "upper" - to create top unmodify string or "down"
     * @return string HTML-content string
     */
    function Unmodify($old_pos, $new_pos, $direction="upper") {
    }

    /**
     * Draw table width content
     *
     * @param string content table content
     * @return string HTML - table
     */
    function getTableWrapping($content) {
    }

    /**
     * Compare strings
     *
     * @param string $old first string
     * @param string $new second string
     * @return array
     */
    function strCompare($old, $new)
    {
        $o = preg_split('/(\s+)/', $old);
        $n = preg_split('/(\s+)/', $new);
        $cmp = array();
        $last_cmp = 0;
        for ($i = 0; $i < count($o); $i++) {
            for ($j = $last_cmp; $j < count($n); $j++) {
                if ($o[$i] == $n[$j]) {
                    $last_cmp = $j+1;
                    $cmp['old'][] = $i;
                    $cmp['new'][] = $j;
                    break;
                }
            }
        }
        $old_str = "";
        $flag = false;
        foreach ($o as $k => $value) {
            if (!in_array($k, $cmp['old'])) {
                if (!$flag) {
                    $old_str .= "<del>";
                    $flag = true;
                }
                $old_str .= $value . " ";
            } else {
                if ($flag) {
                    $old_str .= "</del>";
                    $flag = false;
                }
                $old_str .= $value . " ";
            }
        }
        $new_str = " ";
        $flag = false;
        foreach ($n as $k => $value) {
            if (!in_array($k, $cmp['new'])){
                if (!$flag) {
                    $new_str .= "<ins>";
                    $flag = true;
                }
                $new_str .= $value . " ";
            } else {
                if ($flag) {
                    $new_str .= "</ins>";
                    $flag = false;
                }
                $new_str .= $value . " ";
            }
        }
        return array("old" => $old_str, "new"=> $new_str);
    }

    /**
     * Formating text before output
     *
     * @access public
     * @param string text text to format
     * @return string formated text
     */
    function textFormating($text)
    {
        $text = str_replace(" ", " &nbsp;", htmlspecialchars($text));
        return $text;
    }
}