<?php

/**
 * Calendar module. show a calendar.
 *
 * sample use in template:
 * <code>
 *  <TPL_OBJECT:calendar>
 *    <TPL_OBJECT_OUTPUT:show()>
 * </TPL_OBJECT:calendar>
 * </code>
 *
 * @package modera_net
 * @version 1.5
 * @access public
 */

class calendar {

/**
 * @var integer active template set
 */
var $tmpl = false;
/**
 * @var integer database connection identifier
 */
var $dbc = false;
/**
 * @var string active language
 */
var $language = false;
/**
 * @var string template to use
 */
var $template = "module_calendar_main.html";
/**
 * @var boolean does this module provide additional parameters to admin page admin
 */
var $content_module = false;
/**
 * @var array additional parameters set at the page admin for this template
 */
var $module_param = array();
/**
 * @var integer active user id
 */
var $userid = false;
/**
 * @var string module cache level
 */
var $cachelevel = TPL_CACHE_NOTHING;
/**
 * @var integer cache expiry time in minutes
 */
var $cachetime = 1440; //cache time in minutes
/**
 * @var array event data, to display some days as having events
 */
var $events = array();
/**
 * @var string url to append to each calendar link
 */
var $url = false;
/**
 * @var string current active url of page
 */
var $url_page = false;
/**
 * @var string field name to put active date to
 */
var $field = "date";
/**
 * @var array merged array with _GET and _POST data
 */
var $vars = array();

    /**
     * Class constructor
    */

  function calendar () {
    global $db;
    global $language;
    $this->vars = array_merge($_GET, $_POST);
    $this->tmpl = $GLOBALS["site_settings"]["template"];
    $this->language = $language;
    if (!is_object($db)) { $db = new DB; $this->dbc = $db->connect(); }
    else { $this->dbc = $db->con; }

    $this->userid = $GLOBALS["user_data"][0];

    if ($this->content_module == true) {
        $this->getParameters();
    }
  }

    /**
     * Main display function. Display calendar
     * @return string html with calendar content
     */

    function show() {
        global $txtf;

        $structure = $this->vars['structure'];
        $content = $this->vars['content'];
        $day = $this->vars['day'];
        $month = $this->vars['month'];
        $year = $this->vars['year'];
        $hour = $this->vars['hour'];
        $minute = $this->vars['minute'];

        $sq = new sql;

        if ($this->vars["structure"] && !$this->vars["content"]) {
            $this->url_page = $_SERVER['PHP_SELF'] . "?structure=".$this->vars["structure"];
        }
        else if ($this->vars["structure"] && $this->vars["content"]) {
            $this->url_page = $_SERVER['PHP_SELF'] . "?structure=".$this->vars["structure"]."&content=".$this->vars["content"];
        }
        else {
            $this->url_page = "?";
        }

        if (!$this->url) {
            $this->url = $this->url_page;
        }

        if (!ereg("\?", $this->url)) $this->url .= "?";
        if (!ereg("\?", $this->url_page)) $this->url_page .= "?";


        $month_name = array ("", $txtf->display("month_1"), $txtf->display("month_2"), $txtf->display("month_3"), $txtf->display("month_4"), $txtf->display("month_5"), $txtf->display("month_6"), $txtf->display("month_7"), $txtf->display("month_8"), $txtf->display("month_9"), $txtf->display("month_10"), $txtf->display("month_11"), $txtf->display("month_12"));
        $day_name = array($txtf->display("day_1"), $txtf->display("day_2"), $txtf->display("day_3"), $txtf->display("day_4"), $txtf->display("day_5"), $txtf->display("day_6"), $txtf->display("day_0"));

        $datime = time();

        if(!isset($month)) { $month = date("n",$datime); }

        if(!isset($year)) { $year = date("Y",$datime); }

        if ($month == date("n",$datime) && $year == date("Y",$datime)) {
            $today = date("j", $datime);
        }

        if(!isset($hour)) $hour = date("H");
        if(!isset($minute)) $minute = date("i");

        // ########################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $dirname_this = dirname($_SERVER["PHP_SELF"]);

        if (substr($dirname_this, -5) == "admin" && basename($_SERVER["PHP_SELF"]) != "index.php") {
            $template = $this->template;
        }
        else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        }

        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=calendar&day=$day&month=$month&year=$year");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "calendar";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module calendar cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $lastmonth = $month - 1;
        $lastyear = $year;
        if ($lastmonth < 1) { $lastmonth = 12; $lastyear = $year - 1; }
        $tpl->addDataItem("URL_PREVIOUS", $this->url . "&year=$lastyear&month=$lastmonth");

        $nextmonth = $month + 1;
        $nextyear = $year;
        if ($nextmonth > 12) { $nextmonth = 1; $nextyear = $year + 1; }
        $tpl->addDataItem("URL_NEXT", $this->url . "&year=$nextyear&month=$nextmonth");
        $tpl->addDataItem("CURRENT_MONTH", $month_name[$month] . " " . $year);

        for ($c = 0; $c < 7; $c++) {
            $wdays .= "<div class=\"dayname\">".substr($day_name[$c],0,1)."</div>\n";
        }

        $tpl->addDataItem("WEEKDAYS", $wdays);

        // ####
        // Initial empty days
        $c = 1;
        $firstmonday = date("w", mktime(0,0,0,$month, 1, $year));
        if ($firstmonday == 0) $firstmonday = 7;
        $dayz = "";
        while ($firstmonday > $c) {
            $dayz .= "<div class=\"blank\">&nbsp;</div>\n";
            $c++;
        }
        // ####

        for ($c = 1; $c <= 31; $c++) {

            // TODAY
            if($c == $today) { $daystyle = "today"; }
            else if($c != $today) {  $daystyle = ""; }

            // SELECTED DAY
            if ($c == $day) { $daystyle = "selected";  }

            if (checkdate($month, $c, $year)) {
                if (is_array($this->events[$year][$month][$c])) {
                    $dayz .= "<a href=\"".$this->url."&day=".$c."&month=".$month."&year=".$year."\" class=\"$daystyle\"><b>$c</b></a>\n";
                }
                else {
                    $dayz .= "<a href=\"".$this->url."&day=".$c."&month=".$month."&year=".$year."\" class=\"$daystyle\">$c</a>\n";
                }
            }
            //else {
            //  $dayz .= "<div class=\"blank\">&nbsp;</div>\n";
            //}

        }

        $tpl->addDataItem("DAYS", $dayz);

        $f_hour = "";
        for ($c = 0; $c < 24; $c++) {
            if ($c < 10) $z = "0" . $c;
            else { $z = $c; }
            if ($hour == $z) $sel = "selected";
            else { $sel = ""; }
            $f_hour .= "<option value=\"$z\" $sel>$z</option>\n";
        }
        $f_minute = "";
        for ($c = 0; $c < 60; $c = $c + 5) {
            if ($c < 10) $z = "0" . $c;
            else { $z = $c; }
            if ($minute == $z) $sel = "selected";
            else { $sel = ""; }
            $f_minute .= "<option value=\"$z\" $sel>$z</option>\n";
        }

        // Hours
        $tpl->addDataItem("HOURS", $f_hour);
        // Minutes
        $tpl->addDataItem("MINUTES", $f_minute);

        if (!$day) $day = date("j");

        $tpl->addDataItem("DAY", $day);
        $tpl->addDataItem("MONTH", $month);
        $tpl->addDataItem("YEAR", $year);
        $tpl->addDataItem("HOUR", $hour);
        $tpl->addDataItem("MINUTE", $minute);
        $tpl->addDataItem("FIELD", $this->field);
        $tpl->addDataItem("TYPE", $_GET["type"]);

        // ####
        return $tpl->parse();

    }


    /**
     * Main display function for events calendar. Display calendar
     * @return string html with calendar content
     */

    function show_events_cal() {
        global $txtf;

        $structure = $this->vars['structure'];
        $content = $this->vars['content'];
        $day = $this->vars['day'];
        $month = $this->vars['month'];
        $year = $this->vars['year'];
        $hour = $this->vars['hour'];
        $minute = $this->vars['minute'];

        $sq = new sql;

        if ($this->vars["structure"] && !$this->vars["content"]) {
            $this->url_page = $_SERVER['PHP_SELF'] . "?structure=".$this->vars["structure"];
        }
        else if ($this->vars["structure"] && $this->vars["content"]) {
            $this->url_page = $_SERVER['PHP_SELF'] . "?structure=".$this->vars["structure"]."&content=".$this->vars["content"];
        }
        else {
            $this->url_page = "?";
        }

        if (!$this->url) {
            $this->url = $this->url_page;
        }

        if (!ereg("\?", $this->url)) $this->url .= "?";
        if (!ereg("\?", $this->url_page)) $this->url_page .= "?";


        $month_name = array ("", $txtf->display("month_1"), $txtf->display("month_2"), $txtf->display("month_3"), $txtf->display("month_4"), $txtf->display("month_5"), $txtf->display("month_6"), $txtf->display("month_7"), $txtf->display("month_8"), $txtf->display("month_9"), $txtf->display("month_10"), $txtf->display("month_11"), $txtf->display("month_12"));
        $day_name = array($txtf->display("day_1"), $txtf->display("day_2"), $txtf->display("day_3"), $txtf->display("day_4"), $txtf->display("day_5"), $txtf->display("day_6"), $txtf->display("day_0"));

        $datime = time();

        if(!isset($month) || !is_numeric($month)) {
            $month = date("n", $datime);
        }

        if(!isset($year) || !is_numeric($year)) {
            $year = date("Y", $datime);
        }

        if ($month == date("n",$datime) && $year == date("Y",$datime)) {
            $today = date("j", $datime);
        }

        if(!isset($hour)) $hour = date("H");
        if(!isset($minute)) $minute = date("i");

        // ########################################

        // instantiate template class
        $tpl = new template;
        $tpl->setCacheLevel($this->cachelevel);
        $tpl->setCacheTtl($this->cachetime);
        $usecache = checkParameters();

        $dirname_this = dirname($_SERVER["PHP_SELF"]);

        if (substr($dirname_this, -5) == "admin" && basename($_SERVER["PHP_SELF"]) != "index.php") {
            $template = $this->template;
        }
        else {
            $template = $GLOBALS["templates_".$this->language][$this->tmpl][1] . "/" . $this->template;
        }

        $tpl->setInstance($_SERVER['PHP_SELF']."?language=".$this->language."&module=calendar&day=$day&month=$month&year=$year");
        $tpl->setTemplateFile($template);

        // PAGE CACHED
        if ($tpl->isCached($template) == true && $usecache == true) {
            $GLOBALS["caching"][] = "calendar";
            if ($GLOBALS["modera_debug"] == true) {
                return "<!-- module calendar cached -->\n" . $tpl->parse();
            }
            else {
                return $tpl->parse();
            }
        }

        $lastmonth = $month - 1;
        $lastyear = $year;
        if ($lastmonth < 1) { $lastmonth = 12; $lastyear = $year - 1; }
        $tpl->addDataItem("URL_PREVIOUS", $this->url . "&year=$lastyear&month=$lastmonth");

        $nextmonth = $month + 1;
        $nextyear = $year;
        if ($nextmonth > 12) { $nextmonth = 1; $nextyear = $year + 1; }
        $tpl->addDataItem("URL_NEXT", $this->url . "&year=$nextyear&month=$nextmonth");
        $tpl->addDataItem("CURRENT_MONTH", $month_name[$month] . " " . $year);

        $wdays = "<th class=\"static\">" . $txtf->display("week_short") . "</th>\n";
        for ($c = 0; $c < 7; $c++) {
            $wdays .= "<th>".substr($day_name[$c],0,1)."</th>\n";
        }

        $tpl->addDataItem("WEEKDAYS", $wdays);

        // ####
        // Initial empty days
        $c = 1;
        $firstmonday = date("w", mktime(0,0,0,$month, 1, $year));
        if ($firstmonday == 0) $firstmonday = 7;
        $dayz = "<tr>\n";
        // WEEK NUMBER
        if ($c != $firstmonday) {
            $wn = date("W", mktime(0, 0, 0, $month, $c, $year)) + 0;
            $dayz .= "<td class=\"static\">" . $wn . "</td>\n";

            while ($firstmonday > $c) {
                $dayz .= "<td>&nbsp;</td>\n";
                $c++;
            }
        }
        // ####

        for ($c = 1; $c <= 31; $c++) {
            $wd = date("w", mktime(0, 0, 0, $month, $c, $year));
            if (!$wd) {
                $wd = 7;
            }

            // TODAY
            if($c == $today) {
                $daystyle = "today";
            }
            elseif($c != $today) {
                $daystyle = "";
            }
            if ($wd == 6 || $wd == 7) {
                $daystyle = ($daystyle ? $daystyle . " " : "") . "sunday";
            }


            // SELECTED DAY
//          if ($c == $day) { $daystyle = "selected";  }

            if ($wd == 1) { // monday
                $dayz .= "<tr>\n";
                // WEEK NUMBER
                $wn = date("W", mktime(0, 0, 0, $month, $c, $year)) + 0;
                $dayz .= "<td class=\"static\">" . $wn . "</td>\n";
            }

            if (checkdate($month, $c, $year)) {
                if (is_array($this->events[$year][$month][$c])) {
                    $dayz .= "<td class=\"$daystyle\"><a href=\"".$this->url."&day=".$c."&month=".$month."&year=".$year."\"><b>$c</b></a></td>\n";
                }
                else {
                    $dayz .= "<td class=\"$daystyle\"><a href=\"".$this->url."&day=".$c."&month=".$month."&year=".$year."\">$c</a></td>\n";
                }
            }

            if ($wd == 7) { // sunday
                $dayz .= "</tr>\n";
            }
        }

        if ($wd < 7) {
            for ($i = $wd; $i <= 7; $i++) {
                $dayz .= "<td>&nbsp;</td>\n";
            }
            $dayz .= "</tr>\n";
        }

        $tpl->addDataItem("DAYS", $dayz);

        $f_hour = "";
        for ($c = 0; $c < 24; $c++) {
            if ($c < 10) $z = "0" . $c;
            else { $z = $c; }
            if ($hour == $z) $sel = "selected";
            else { $sel = ""; }
            $f_hour .= "<option value=\"$z\" $sel>$z</option>\n";
        }
        $f_minute = "";
        for ($c = 0; $c < 60; $c = $c + 5) {
            if ($c < 10) $z = "0" . $c;
            else { $z = $c; }
            if ($minute == $z) $sel = "selected";
            else { $sel = ""; }
            $f_minute .= "<option value=\"$z\" $sel>$z</option>\n";
        }

        // Hours
        $tpl->addDataItem("HOURS", $f_hour);
        // Minutes
        $tpl->addDataItem("MINUTES", $f_minute);

        if (!$day) $day = date("j");

        $tpl->addDataItem("DAY", $day);
        $tpl->addDataItem("MONTH", $month);
        $tpl->addDataItem("YEAR", $year);
        $tpl->addDataItem("HOUR", $hour);
        $tpl->addDataItem("MINUTE", $minute);
        $tpl->addDataItem("FIELD", $this->field);
        $tpl->addDataItem("TYPE", $_GET["type"]);

        // ####
        return $tpl->parse();

    }

    /**
     * Assign parameters to calendar
     * @param string url to append to the links
     * @param array array with event data format $this->events[year][month][day][]
     * @param string fiel name
     */

    function parameters($url, $events, $field = "date") {
        if ($url) $this->url = $url;
        if ($events && is_array($events)) $this->events = $events;
        $this->field = $field;
    }

    /**
     * Set the template to show
     * @param string template filename
     */

    function setTemplate($template) {
        if ($template && !ereg("\.\.", $template)) $this->template = $template;
    }

    /**
     * Get parameters from page data
     * @access private
     */

    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Provide addtional parameters to page admin
     * @access private
     */

    function moduleOptions() {
        $sq = new sql;
        //$txt = new Text($this->language, "module_calendar");
        $list[""] = "";
        return array($txt->display("module_title"), "select", $list);
        // name, type, list
    }

}