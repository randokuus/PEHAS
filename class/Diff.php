<?php
/**
 * @version $Revision: 643 $
 */

require_once(SITE_PATH . '/class/Diff/DiffSettings.php');

/**
 * Class for performing diff between two strings
 *
 * @author Victor Buzyka <victor@itworks.biz.ua>
 */
class Diff
{
    /**
     * Object width diff current settings
     *
     * @access private
     * @var DiffSettings
     */
    var $_settings;

    /**
     * Different flag
     *
     * @var bool
     */
    var $differ = false;

    /**
     * Class constructor
     *
     * @return Diff
     */
    function Diff()
    {
        $this->_settings = new DiffSettings();
        //$this->settings->setParameter("view_type", "SiteBySite");
    }

    /**
     * Set diff parameters
     *
     * @param string name parameter name
     * @param string value parameter value
     * @return bool
     */
    function setParameter($name, $value)
    {
        return $this->_settings->setParameter($name, $value);
    }

    /**
     * Set view type parameter
     *
     * @param string value parameter value can by "Inline" or "SiteBySite"
     * @return bool
     */
    function setViewType($value)
    {
        switch (strtolower($value)) {
            case "sidebyside":
                $this->_settings->setParameter("view_type", "SideBySide");
                break;

            case "inline":
            default:
                $this->_settings->setParameter("view_type", "Inline");
        }
    }

    /**
     * Set arround lines parameter
     *
     * @param int value count arround lines
     * @return bool
     */
    function setArroundLines($value)
    {
        if (is_numeric($value) && intval($value) >= 0) {
            $this->_settings->setParameter("arround_line", $value);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set ignore parameter
     *
     * @param string value ignore type (posible values: "blank_lines", "case_changes", "space_changes")
     * @return bool
     */
    function setIgnore($value)
    {
        $posible_ignore = array("blank_lines", "case_changes", "space_changes");
        if (in_array($value, $posible_ignore)) {
            $arr = $this->_settings->getParameter("ignore");
            if (!in_array($value, $arr)) {
                $arr[] = $value;
                $this->_settings->setParameter("ignore", $arr);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set style list parameter
     *
     * @param string style_name style system name
     * @param string style_class class name to style
     * @return bool
     */
    function setStyle($style_name, $class_name)
    {
        return $this->_settings->setStyle($style_name, $class_name);
    }

    /**
     * Old string head name
     *
     * @param string name head name
     */
    function setOldStringHead($name)
    {
        $this->_settings->old_str_head = $name;
    }

    /**
     * New string head name
     *
     * @param string name head name
     */
    function setNewStringHead($name)
    {
        $this->_settings->new_str_head = $name;
    }

    /**
     * Get diffs table
     *
     * @param string old old string
     * @param string new new string
     * @return string HTML diffs table
     */
    function getDiffs($old, $new)
    {
        $t1 = explode("\n", $old);
        $x = array_pop($t1);
        if ($x > '') {
            $t1[] = "$x\n";
        }
        $t2 = explode("\n",$new);
        $x = array_pop($t2);
        if ($x > '') {
            $t2[] = "$x\n";
        }

        $ignore_list = $this->_settings->getParameter("ignore");
        $cmp_function = "strcmp";
        $space_ch = false;
        foreach ($ignore_list as $v) {
            switch($v) {
                case "blank_lines":
                    $t1_old = $t1;
                    $t1 = array();
                    foreach ($t1_old as $t) {
                        if (trim($t) != "") {
                            $t1[] = $t;
                        }
                    }
                    $t2_old = $t2;
                    $t2 = array();
                    foreach ($t2_old as $t) {
                        if (trim($t) != "") {
                            $t2[] = $t;
                        }
                    }
                    break;

                case "case_changes":
                    $cmp_function = "strcasecmp";
                    break;

                case "space_changes":
                    $space_ch = true;
                    break;
            }
        }

        foreach ($t1 as $i => $x) {
            if ($x > '') {
                $r1[$x][] = $i;
            }
        }

        foreach ($t2 as $i => $x) {
            if ($x > '') {
                $r2[$x][] = $i;
            }
        }

        $a1 = 0;
        $a2 = 0;
        $actions = array();

        while ($a1 < count($t1) && $a2 < count($t2)) {
            if ($space_ch) {
                $tmp_s1 = preg_replace("/( +)/", " ", $t1[$a1]);
                $tmp_s2 = preg_replace("/( +)/", " ", $t2[$a2]);
            } else {
                $tmp_s1 = $t1[$a1];
                $tmp_s2 = $t2[$a2];
            }

            if ($cmp_function($tmp_s1, $tmp_s2) == 0) {
                $actions[]=4;
                $a1++;
                $a2++;
                continue;
            }

            $best1 = count($t1);
            $best2 = count($t2);
            $s1 = $a1;
            $s2 = $a2;

            while (($s1 + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                $d = -1;
                foreach ((array)@$r1[$t2[$s2]] as $n) {
                    if ($n >= $s1) {
                        $d = $n;
                        break;
                    }
                }

                if ($d >= $s1 && ($d + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                    $best1 = $d;
                    $best2 = $s2;
                }

                $d = -1;

                foreach((array)@$r2[$t1[$s1]] as $n) {
                    if ($n >= $s2) {
                        $d = $n;
                        break;
                    }
                }

                if ($d >= $s2 && ($s1 + $d - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                    $best1 = $s1;
                    $best2 = $d;
                }

                $s1++;
                $s2++;
            }

            while ($a1 < $best1) {
                $actions[] = 1;
                $a1++;
            }  # deleted elements

            while ($a2 < $best2) {
                $actions[] = 2;
                $a2++;
            }  # added elements
        }

        while ($a1 < count($t1)) {
            $actions[] = 1;
            $a1++;
        } # deleted elements

        while ($a2 < count($t2)) {
            $actions[] = 2;
            $a2++;
        }  # added elements

        $actions[] = 8;

        $op = 0;
        $x0 = $x1 = 0;
        $y0 = $y1 = 0;
        $data_array = array();
        foreach ($actions as $act) {
            if ($act == 1) {
                $op |= $act;
                $x1++;
                continue;
            }

            if ($act == 2) {
                $op |= $act;
                $y1++;
                continue;
            }

            if ($op > 0) {
                if ($x1 == ($x0 + 1)) {
                    $xstr = array("start" => $x1, "end" => $x1);
                } else {
                    $xstr = array("start" => ($x0+1), "end" => $x1);
                }

                if ($y1 == ($y0 + 1)) {
                    $ystr = array("start" => $y1, "end" => $y1);
                } else {
                    $ystr = array("start" => ($y0 + 1), "end" => $y1);
                }

                if ($op == 1) {
                    $data_array[] = array("type" => "Delete", "old" => $xstr
                        , "new" => $y1);
                } else if ($op == 3) {
                    $data_array[] = array("type" => "Modifi", "old" => $xstr
                        , "new" => $ystr);
                }

                if ($op == 2) {
                    $data_array[] = array("type" => "Add", "old" => $x1
                        , "new" => $ystr);
                }

                $this->differ = true;
            }

            $x1++;
            $x0 = $x1;
            $y1++;
            $y0 = $y1;
            $op = 0;
        }
        return $this->_drowTable($data_array, $t1, $t2);
    }

    /**
     * Get HTML-table content
     *
     * @access private
     * @param array diff_array list of differents
     * @param array old parsed old string
     * @param array new parsed new string
     * @return string HTML diff table
     */
    function _drowTable($diff_array, $old, $new)
    {
        $this->start_point = 0;
        $res = "";
        $type = $this->_settings->getParameter('view_type');
        $file_name = SITE_PATH . "/class/Diff/DiffDraw" .$type. "Interface.php";

        if (is_readable($file_name)) {
            require_once($file_name);
            $class_name = "DiffDraw" .$type. "Interface";
            $interface = new $class_name($old, $new, $this->_settings);
            foreach ($diff_array as $d) {
                if (!is_array($d['new'])) {
                    $d['new'] = array("start" => $d['new'], "end" => $d['new']);
                }
                if (!is_array($d['old'])) {
                    $d['old'] = array("start" => $d['old'], "end" => $d['old']);
                }
                $method_name = $d['type'];
                if (($interface->start_point > 0)
                    && ($d['new']['start'] - $interface->start_point) > 1)
                {
                    $res .= $interface->Skip();
                }
                if (method_exists($interface, $method_name)) {
                    $res .= $interface->$method_name($d['old'], $d['new']);
                }
            }
            return $interface->getTableWrapping($res);
        } else {
            return false;
        }
    }
}