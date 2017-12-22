<?php
/**
 * @version $Revision: 643 $
 */

require_once(SITE_PATH . "/class/Diff/DiffDrawInterface.php");

/**
 * Diff show in format "Inline" interfase
 *
 * @author Victor Buzyka <victor@itworks.biz.ua>
 */
class DiffDrawInlineInterface extends DiffDrawInterface
{
    /**
     * Draw added strings
     *
     * @param array old_pos old string positions
     * @param array new_pos inserted string index
     * @return string HTML-content string
     */
    function Add($old_pos, $new_pos)
    {
        $res = "";
        $s_pos = $new_pos['start'];
        $end_pos = $new_pos['end'];
        $old_pos = $old_pos['start'];

        $res .= $this->Unmodify($old_pos, $s_pos);
        $class = $this->settings->getStyle('add');
        for ($i = $s_pos; $i <= $end_pos; $i++) {
            $res .= "<tr> "
                . "<td class=\"" .$this->settings->getStyle('position'). "\"> &nbsp; </td>"
                . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . $i . " </td>"
                . "<td class=\"" .$class. "\"> " . $this->textFormating($this->new_str[$i-1]) . " </td>"
                . "</tr>";
        }
        $res .= $this->Unmodify($old_pos, $end_pos, "down");
        return $res;
    }

    /**
     * Draw modifid strings
     *
     * @param int old_pos old string position
     * @param array new_pos inserted string index
     * @return string HTML-content string
     */
    function Modifi($old_pos, $new_pos)
    {
        $res = "";
        $s_pos = $new_pos['start'];
        $end_pos = $new_pos['end'];
        $o_s_pos = $old_pos['start'];
        $o_end_pos = $old_pos['end'];

        $res .= $this->Unmodify($o_s_pos, $s_pos);
        while ($s_pos <= $end_pos || $o_s_pos <= $o_end_pos) {
            if ($o_s_pos <= $o_end_pos) {
                $old_str = $this->textFormating($this->old_str[$o_s_pos-1]);
                $o_pos = $o_s_pos;
            } else {
                $old_str =  "&nbsp;";
                $o_pos = "&nbsp;";
            }
            if ($s_pos <= $end_pos) {
                $new_str = $this->textFormating($this->new_str[$s_pos-1]);
                $n_pos = $s_pos;
            } else {
                $new_str = "&nbsp;";
                $n_pos = "&nbsp;";
            }
            if ($old_str != "&nbsp;" && $new_str != "&nbsp;") {
                $strings = $this->strCompare($old_str, $new_str);
                $old_str = $strings['old'];
                $new_str = $strings['new'];
            }
            if ($o_pos != "&nbsp;") {
                $class = $this->settings->getStyle('remove');
                $res .= "<tr> "
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . $o_s_pos . " </td>"
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> &nbsp; </td>"
                    . "<td class=\"" .$class. "\"> " . $old_str  . " </td>"
                    . "</tr>";
                $o_s_pos++;
            }
            if ($n_pos != "&nbsp;") {
                $class = $this->settings->getStyle('add');
                $res .= "<tr> "
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> &nbsp; </td>"
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . $n_pos . " </td>"
                    . "<td class=\"" .$class. "\"> " . $new_str . " </td>"
                    . "</tr>";
                $s_pos++;
            }
        }
        $this->start_point = $end_pos;
        $res .= $this->Unmodify($o_end_pos, $end_pos, "down");
        return $res;
    }

    /**
     * Draw deleted strings
     *
     * @param array old_pos inserted string index
     * @param int new_pos new string position
     * @return string HTML-content string
     */
    function Delete($old_pos, $new_pos)
    {
        $res = "";
        $s_pos = $old_pos['start'];
        $end_pos = $old_pos['end'];
        $new_pos = $new_pos['start'];

        $res .= $this->Unmodify($s_pos, $new_pos);
        $class = $this->settings->getStyle('remove');
        while ($s_pos <= $end_pos) {
            $res .= "<tr> "
                . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . $s_pos . " </td>"
                . "<td class=\"" .$this->settings->getStyle('position'). "\"> &nbsp; </td>"
                . "<td class=\"" .$class. "\"> " . $this->textFormating($this->old_str[$s_pos-1]) . " </td>"
                . "</tr>";
            $s_pos++;
        }
        $res .= $this->Unmodify($end_pos, $new_pos, "down");
        return $res;
    }

    /**
     * Draw skiped string between modify pieces
     *
     * @return string HTML-content string
     */
    function Skip()
    {
        $class = $this->settings->getStyle('space');
        $res = "<tr> "
            . "<td class=\"" .$this->settings->getStyle('position'). "\"> ... </td>"
            . "<td class=\"" .$this->settings->getStyle('position'). "\"> ... </td>"
            . "<td class=\"" .$class. "\"> &nbsp; </td>"
            . "</tr>";
        return $res;
    }

    /**
     * Draw deleted strings
     *
     * @param int old_pos old string position
     * @param int new_pos new string position
     * @param string direction can be "upper" - to create top unmodify string or "down"
     * @return string HTML-content string
     */
    function Unmodify($old_pos, $new_pos, $direction="upper")
    {
        $res = "";
        $new_start = $new_pos;
        $old_start = $old_pos;
        if ($direction == "upper") {
            $s_pos = $new_start-$this->settings->getParameter('arround_line');
            if ($this->start_point >= $s_pos) {
                $s_pos = $this->start_point;
            }
            $r = $new_start-$s_pos;
            $class = $this->settings->getStyle('unmod');
            for ($i = $r; $i > 0; $i--){
                $res .= "<tr> "
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . ($old_start-$i) . " </td>"
                    . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . ($new_start-$i) . " </td>"
                    . "<td class=\"" .$class. "\"> " . $this->textFormating($this->new_str[$new_start-$i-1]) . " </td>"
                    . "</tr>";
            }
        } else {
            $class = $this->settings->getStyle('unmod');
            for ($i = 0; $i <= $this->settings->getParameter('arround_line'); $i++) {
                $this->start_point++;
                if (isset($this->new_str[$new_start + $i - 1])
                    && isset($this->old_str[$old_start + $i - 1])
                    && ($this->new_str[$new_start + $i - 1] == $this->old_str[$old_start + $i - 1]))
                {
                    $res .= "<tr> "
                        . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . ($old_start+$i) . " </td>"
                        . "<td class=\"" .$this->settings->getStyle('position'). "\"> " . ($new_start+$i) . " </td>"
                        . "<td class=\"" .$class. "\"> " . $this->textFormating($this->new_str[$new_start+$i-1]) . " </td>"
                        . "</tr>";
                }
            }
        }

        return $res;
    }

    /**
     * Draw table width content
     *
     * @param string content table content
     * @return string HTML - table
     */
    function getTableWrapping($content)
    {
        $res = "<table cellpadding=\"0\" cellspacing=\"0\" class=\""
            . $this->settings->getStyle('table') . "\">";
        $class = $this->settings->getStyle('head');
        $res .= "<tr> "
            . "<td class=\"" .$class. "\"> " . $this->settings->old_str_head . " </td>"
            . "<td class=\"" .$class. "\"> " . $this->settings->new_str_head . " </td>"
            . "<td class=\"" .$class. "\"> &nbsp; </td>"
            . "</tr>";
        $res .= $content;
        $res .= "</table>";
        return $res;
    }
}