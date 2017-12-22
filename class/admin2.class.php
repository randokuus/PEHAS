<?php
/**
 * Modera.net administration2 main class. Similar to admin class but with no ties to DB. controls form and list display and adding/modifying
 *
 * @package modera_net
 * @access public
 */


class Admin2 {

/**
 * @var array values from POST operation
 */
var $values = array();
/**
 * @var array associative array containg field data. fields[fieldname][property]. property can be (type, size, cols, max, java, rows, displayonly, buttonfield, list, value)
 */
var $fields = array();
/**
 * @var array fields that were required and left unfilled
 */
var $badfields = array();
/**
 * @var string the main sql table to work on
 */
var $table = false;
/**
 * @var integer database connection resource
 */
var $dbc = false;
/**
 * @var array general administration values, templates, settings etc.
 */
var $general = array();
/**
 * @var boolean if checked is set to true, add() and modify() will not check for required fields
 */
var $checked = false;
/**
 * @var integer last inserted id from add() function
 */
var $insert_id = false;
/**
 * @var boolean set general debug to true/false. by default get's its value from $GLOBALS['modera_debugsql']. will display html commented queries
 */
var $debug = false;
/**
 * @var string location of current running script
 */
var $phpself = false;
/**
 * @var string value from REQUEST['show'], add/modify
 */
var $status = false;
/**
 * @var array hidden fields/elements to be included in the forms, links
 */
var $hidden = array();
/**
 * @var array additional filters used in the list view
 */
var $extra_filter = array();
/**
 * @var array helper buttons, that can be assigned to a field/fields
 */
var $helper_buttons = array();
/**
 * @var string location of main modera.net program, is set automatically
 */
var $engine = "";
/**
 * @var array field info, to which a media selector type buttons have been assigned to
 */
var $mediaselectors = array();

/**
 * @var integer default value for form field property 'cols'
 */
var $def_cols = 40;
/**
 * @var integer default value for form field property 'rows'
 */
var $def_rows = 4;
/**
 * @var integer default value for form field property 'max'
 */
var $def_max = 255;
/**
 * @var integer default value for form field property 'size'
 */
var $def_size = 30;
/**
 * @var array list entries per page allowed array(10, 20, 30, 50, 100)
 */
var $entries = array(10, 20, 30, 50, 100, 200, 500);

    /**
     * Class constructor. Set values to variables, create a database connection (connection id $this->dbc)
     *
     * @access public
     */

    function Admin2($table) {
        global $general;
        global $show;
        $this->status = $show;
        $this->phpself = $_SERVER['PHP_SELF'];
        $this->values = $_POST;
        $this->general = $general;
        $this->debug = $general["debug"];
        $this->table = $table;
        $db = new DB;
        $this->dbc = $db->connect();
    }

    /** Types must be manually configured for each field/form element, since no strict DB table is used.
     * @return array returns $this->fields
    */

    function types() {
        return $this->fields;
    }

    /** Assign values to fields' properties
     * @param string Fieldname
     * @param string Property name
     * @param mixed The value to assign
    */

    function assignProp($key, $property, $value) {
        if ($key != "" && $property != "" && $value != "") {
            $this->fields[$key][$property]  = $value;
        }
    }

    /** Assign helper buttons to fields (eg. calendar popup)
     * @param string Fieldname
     * @param string Image location
     * @param string The url/javascript function to point the helper to
    */

    function assignHelper($key, $image, $url) {
        if ($key != "" && $image != "" && $url != "") {
            $this->helper_buttons[$key] = array($image, $url);
        }
    }

    /** If in modify view we only want to display the value (and no form element), sets $this->fields[$field]["displayonly"] = true
     * @param string Fieldname
    */

    function displayOnly($field) {
        $this->fields[$field]["displayonly"] = true;
    }

    /** The field contains buttons
     * @param string Fieldname
    */

    function displayButtons($field) {
        $this->fields[$field]["buttonfield"] = true;
    }

    /** Assign values directly from query to the desired list
     * @param string Fieldname
     * @param string Select from which table
     * @param integer The idfield to join with the current table
     * @param string Name to display in the list
     * @param string Additional query parameters (WHERE you = me ORDER BY me DESC)
     * @param boolean true/false Should the first element of the list be an empty element
    */

    function assignExternal($key, $table, $id, $name, $extra, $empty) {
        if ($key != "" && $table != "" && $id != "" && $name != "") {
            $sq = new sql;
            // 12.04.04 added && $this->fields[$key]["value"]
            if ($this->fields[$key]["displayonly"] == true && $this->status != "add" && $this->fields[$key]["value"]) {
                $extra = ereg_replace("WHERE", "AND", $extra);
                $sql = "SELECT $id, $name FROM $table WHERE $id = '" . $this->fields[$key]["value"] . "' $extra";
            }
            else {
                $sql = "SELECT $id, $name FROM $table $extra";
            }
            if ($this->debug == true) {
                echo "<!-- $sql -->\n\n";
            }
            $res = $sq->query($this->dbc, $sql);
            if ($empty == true) $ar[0] = " --- no value --- ";
            while ($data = $sq->nextrow()) {
                $ar[$data[0]] = $data[1];
            }
            if ($sq->numrows == 0 && $empty != true) $ar[0] = " ";
            $this->fields[$key]["list"] = $ar;
            $sq->free();
            unset($ar);
        }
    }

    /** Assign a value to a field
     * @param string fieldname
     * @param mixed the value to assign
    */

    function assign($key, $val) {
        $this->fields[$key]["value"] = $val;
    }

    /** Assign values to hidden fields, to carry along values in forms/urls
     * @param string fieldname
     * @param string the value to assign
    */

    function assignHidden($key, $value) {
        if ($key != "" && $value != "") {
            $this->hidden[$key] = $value;
        }
    }

    /** Assign values to additional filter fields
     * @param string fieldname
     * @param string value of the field
     * @param string the filter logic - module_demo1.field = 2
     * @param array field data, as returned by adminfields class
    */

    function assignFilter($key, $value, $filter, $fielddata) {
        $this->extra_filter[$key] = array($value, $filter, $fielddata);
    }

    /**
     * Assign media selector (image/flash) with add/delete buttons to a field
     * @param string field name
     * @param string add button text
     * @param string delete button text
    */

    function assignMediaSelector($field, $add_txt, $del_txt) {
        $this->mediaSelectors[] = $field;
        $this->displayButtons($field);
        $this->assignProp($field,"type","onlyhidden");
        if (sizeof($this->mediaSelectors) > 1) {
            $add1 = sizeof($this->mediaSelectors);
            $add2 = "pic=".sizeof($this->mediaSelectors);
        }
        if ($this->fields[$field]["value"] != "") {
            $this->assignProp($field, "extra", "
            <table border=0 cellpadding=0 cellspacing=0>
            <tr valign=top><td><div align=\"left\" id=\"newspic".$add1."\"><img src=\"" . $this->fields[$field]["value"] . "\" border=0></div></td>
            <td>&nbsp;&nbsp;</td>
            <td><button type=button onClick=\"newWindow('select_media.php?".$add2."',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$add_txt."</button>
            <button type=button onClick=\"javascript:clearPic(".$add1.");\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".$del_txt."</button>
            </td></tr></table>");
        }
        else {
            $this->assignProp($field, "extra", "
            <table border=0 cellpadding=0 cellspacing=0>
            <tr valign=top><td><div align=\"left\" id=\"newspic".$add1."\">&nbsp;</div></td>
            <td>&nbsp;&nbsp;</td>
            <td><button type=button onClick=\"newWindow('select_media.php?".$add2."',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".$add_txt."</button>
            <button type=button onClick=\"javascript:clearPic(".$add1.");\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".$del_txt."</button>
            </td></tr></table>");
        }
    }

    /** Checks for required fields
     * @return array with fields that were bad
    */

    function checkRequired($required) {
        for ($c = 0; $c < sizeof($required); $c++) {
            if ($this->values[$required[$c]] == "" && !is_array($this->values[$required[$c]])) {
                $this->badfields[] = $required[$c];
            }
        }
        $this->checked = true;
        return $this->badfields;
    }

    /** Fill the $this->fields array with values from post array
    */

    function getValues() {
        if (is_array($this->values)) {
            while (list($key, $val) = each($this->values)) {
                if (is_array($val) && !is_array($this->fields[$key]["value"])) {
                    for ($c = 0; $c < sizeof($val); $c++) {
                        $this->fields[$key]["value"] .= $val[$c];
                        if (($c+1) < sizeof($val)) $this->fields[$key]["value"] .= ",";
                    }
                }
                else if (is_array($this->fields[$key]["value"])) {
                        $this->fields[$key]["value"] = join(";;", $this->fields[$key]["value"]);
                }
                else {
                    if ($this->fields[$key]["is_editor"] == true) {
                        $val = stripslashes($val);//remove slashes (/)
                        $val = str_replace("src=\"../../","src=\"".$this->engine, $val);
                        $val = str_replace("href=\"../../","href=\"".$this->engine, $val);
                        $val = str_replace("src=\"".SITE_URL."/","src=\"".$this->engine, $val);
                        $val = str_replace("href=\"".SITE_URL."/","href=\"".$this->engine, $val);
                    }
                    $this->fields[$key]["value"] = "$val";
                }
            }
            reset($this->values);
        }
    }

    /** Displays the rows (specific fields) in a list with user defined template and functionality such as filtering and sorting.
     * @param array Array of fields (object => name) which will be displayed
     * @param array Listdata the actual data to show
     * @param integer Location to start displaying the data (when total rows is greater than max rows to display per page)
     * @param string Which object to sort
     * @param string Sorting order (ascending or descending, default is asc)
     * @param string Free text that will be applied to refine the results
     * @param array Free text that will be applied to refine the results
     * @param string Name of the field which is unique
     * @return string HTML Code with the current section
    */

    function show($display=array(), $listdata=array(), $start, $sort, $sort_type, $filter, $filter_fields=array(), $idfield) {

        if (!$start) { $start = 0; }

        if (!$sort_type || $sort_type == "") { $sort_type = "asc"; }

        if ($sort_type == "asc") { $sort_type1 = "desc";    }
        else if ($sort_type == "desc") { $sort_type1 = "asc"; }

        $tpl = new template;
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl->setTemplateFile($this->general["template_list"]);

        // do special hidden fields if found
        if (is_array($this->hidden)) {
            reset($this->hidden);
            while (list($key, $val) = each($this->hidden)) {
                $hidadd .= "&$key=$val";
                if (!$this->extra_filter[$key][0]) {
                    $hidhid .= "<input type=hidden name=\"$key\" value=\"$val\">\n";
                }
            }
        }
        $e_vilter = array();
        $e_vilter2 = array();
        if (is_array($this->extra_filter)) {
            while (list($key, $val) = each($this->extra_filter)) {
                if ($val[1]) {
                    $hidadd .= "&$key=$val[0]";
                    $e_vilter[] = $val[1];
                }
                $e_vilter2[] = $val[2];
                $tpl->addDataItem("EXTRAFILTER.FIELD", $val[2]);
                $tpl->addDataItem("EXTRAFILTER.ID", "ef$key");
                $tpl->addDataItem("EXTRAFILTER.LABEL", "");
            }
        }
        reset($this->extra_filter);

        /* generate filter part of query */
        // NOT IMPLEMENTED
        /* end generate filter part of query */

        /* and finally let's display something */

        //parse extra filters
        //$tpl->addDataItem("EXTRAFILTER", join("", $e_vilter2));

        // header
        while (list($key, $val) = each($display)) {
            //$url = $this->phpself . "?start=$start&structure=$structure&sort=$key&sort_type=$sort_type1&filter=$filter" . $hidadd;
            $tpl->addDataItem("HEADER.NAME", $val);
            $tpl->addDataItem("HEADER.URL", "#");
            if ($sort == $key && $sort != "listnumber") {
                if ($sort_type == "asc") {
                    $tpl->addDataItem("HEADER.STYLE", "active up");
                }
                else if ($sort_type == "desc") {
                    $tpl->addDataItem("HEADER.STYLE", "active dn");
                }
            }
        }
        reset($display);

        // Parse data list


        $listnumber = $start + 1;
        $cnt_data = sizeof($listdata);
        if ($cnt_data > $start){
            $start_pos = $start;

        }else{
            $start_pos = 0;
        }
        for ($u = 0; $u < $this->general["max_entries"]; $u++) {
            if (!isset($listdata[$u+$start_pos])){
                break;
            }
            $data = $listdata[$u+$start_pos];

            $data["listnumber"] = $listnumber;
            while (list($key, $val) = each($display)) {
                if ($sort == $key && $sort != "listnumber") {
                    $style = "class=\"active\"";
                }
                else {
                    $style = "";
                }
                $url = $this->phpself . "?show=modify&id=" . $data[$idfield] . "&file=".urlencode($data["file"])."&start=$start&sort=$sort&sort_type=$sort_type&filter=$filter&max_entries=" . $this->general["max_entries"]  . $hidadd;
                if ($this->fields[$key]["list"][$data[$key]] != "") {
                    $columns .= "<td $style><a href=\"$url\">" . $this->fields[$key]["list"][$data[$key]] . "</a></td>\n";
                }
                else {
                    if ($data[$key] == "") $data[$key] = "&nbsp;";
                    $columns .= "<td $style><a href=\"$url\">$data[$key]</a></td>\n";
                }
            }
            reset($display);
        $listnumber++;

        $tpl->addDataItem("ROWS.HIDDEN1", "&file=".urlencode($data["file"]) . $hidadd);

        $tpl->addDataItem("ROWS.COLUMNS", $columns);
        $tpl->addDataItem("ROWS.ID", $data[$idfield]);
        //$tpl->addDataItem("ROWS.FILE", $data["file"]);

        unset($columns);
        }

        // general text
        $tpl->addDataItem("FILTER", $this->general["filter"]);
        $tpl->addDataItem("DISPLAY", $this->general["display"]);
        $tpl->addDataItem("SUBMIT", $this->general["filter"]);

        // max entries
        for ($c = 0; $c < sizeof($this->entries); $c++) {
            $tpl->addDataItem("ENTRIES.VALUE", $this->entries[$c]);
            $tpl->addDataItem("ENTRIES.NAME", $this->entries[$c]);
            if ($this->general["max_entries"] == $this->entries[$c]) {
                $tpl->addDataItem("ENTRIES.SEL", "selected");
            }
            else {
                $tpl->addDataItem("ENTRIES.SEL", "");
            }
        }

        $tpl->addDataItem("VAL_SORT", $sort);
        $tpl->addDataItem("VAL_SORT_TYPE", $sort_type);
        $tpl->addDataItem("VAL_FILTER", $filter);

        $tpl->addDataItem("HIDDEN", $hidhid);

        $total = sizeof($listdata);
        if ($total > $this->general["max_entries"]) {
            $page = $this->showPages($start, $total, $this->phpself
                . "?sort=$sort&sort_type=$sort_type&filter=$filter&max_entries="
                . $this->general["max_entries"]  . $hidadd);
            $tpl->addDataItem("PAGES.PAGES", $this->general["pages"]);
            $tpl->addDataItem("PAGES.LINKS", $page);
        }

        $result = $tpl->parse();
        return $result;
    }

    /** Generate page separation max entries from $this->general['max_entries']
     * @param integer Row number from which to start counting
     * @param integer Total number of results(rows) found
     * @param string URL to append to each link
     * @return string HTML code with page separation
    */

    function showPages($start, $total, $url) {

            $diff = $total;

            while ($diff > 0) {
                $diff -= $this->general["max_entries"];
                $lk++;
            }

            if ($lk != 1) {

                $prev = $start - $this->general["max_entries"];
                $next = $start + $this->general["max_entries"];

                if ($prev > 0) {
                    $result .= "&nbsp;<a href=\"$url&start=$prev\">&lt;" . $this->general["prev"] . "</a>&nbsp;\n";
                }
                if ($prev == 0) {
                    $result .= "&nbsp;<a href=\"$url\">&lt;" . $this->general["prev"] . "</a>&nbsp;\n";
                }


                $page = 1;

                $pos = ceil($start/$this->general["max_entries"]);
                if ($pos == 0) $pos = 1;

                if ($lk > 16) {

                    $st = $pos-8; $en = $pos+8;
                    if ($st < 0) { $st = 0; $en = 16; }
                    if ($en > $lk) { $st = $lk-16; $en = $lk; }

                    if ($st > 0) {
                        $result .= "...&nbsp;";
                    }

                    $page = $st+1;
                    for ($i = $st; $i < $en; $i++) {
                            $add = $i * $this->general["max_entries"];
                        if ($add < $total) {
                            if ($add == $start) { $result .= "<b>$page</b> \n"; }
                            else if ($add == 0) { $result .= "<a href=\"$url\">$page</a> \n"; }
                            else { $result .= "<a href=\"$url&start=$add\">$page</a> \n";   }
                        }
                    $page++;
                    }

                    if ($en < $lk) {
                        $result .= "&nbsp;...";
                    }

                }
                else {
                    for ($i = 0; $i < $lk ; $i++) {
                        $add = $i * $this->general["max_entries"];
                        if ($add == $start) { $result .= "<b>$page</b> \n"; }
                        else if ($add == 0) { $result .= "<a href=\"$url\">$page</a> \n"; }
                        else { $result .= "<a href=\"$url&start=$add\">$page</a> \n";   }
                    $page++;
                    }
                }

                if ($next < $total) { $result .= "&nbsp;<a href=\"$url&start=$next\">" . $this->general["next"] . "&gt;</a>\n"; }

            }

    return  $result;
    }

    /** Creates the actual form view (add or modify)
     * @param array List with field names and descriptions
     * @param string Retain sort data so that after form submission sort remains the same (sort field)
     * @param string Retain sort data so that after form submission sort remains the same (sort type ASC/DESC)
     * @param string Retain filter data so that after form submission filter remains the same
     * @param string Action to continue with (add = add to table / update = update table
     * @param integer If where are updating then we need the row unique identifier
     * @param array Field groups
     * @param array Which fields associated with groups
     * @return string html with form data and fields
    */

    function form($desc=array(), $sort, $sort_tyyp, $filter, $do, $id, $field_groups = array(), $fields_in_group = array()) {

        //$this->types();

        global $start, $sort_type, $max_entries, $what, $from, $where, $filter_fields, $idfield, $structure;

        $tpl = new template;
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl->setTemplateFile($this->general["template_form"]);

        if ($sort_tyyp == "") $sort_tyyp = addslashes($sort_type);

        $back = "?show=modify&structure=$structure&sort=$sort&sort_type=$sort_tyyp&filter=$filter&max_entries=$max_entries&start=$start";

        if (is_array($this->hidden)) {
            reset($this->hidden);
            while (list($key, $val) = each($this->hidden)) {
                $hidadd .= "&$key=$val";
            }
        }

        $back = $back . $hidadd;

        $tpl->addDataItem("PHP_SELF", $_SERVER["PHP_SELF"]);
        $tpl->addDataItem("BACK", $back);
        $tpl->addDataItem("BACKTOLIST", $this->general["backtolist"]);
        $tpl->addDataItem("CURRENT", $this->general["current"]);
        $tpl->addDataItem("EXTRABUTTONS", $this->general["extrabuttons"]);
        $bt = ereg_replace("\&", "|1|", $this->general["button"]);
        $bt = ereg_replace("\#", "|2|", $bt);
        $tpl->addDataItem("SENDBUTTON", $this->general["button"]);
        $tpl->addDataItem("SENDBUTTONTXT", $this->general["button"]);

        for ($c = 0; $c < sizeof($this->badfields); $c++) {
            $bad[$this->badfields[$c]] = 1;
        }
        reset($this->badfields);

        if (sizeof($fields_in_group) == 0 || sizeof($field_groups) == 0) {
            if ($field_groups[1][0]) {
                $tpl->addDataItem("FIELDSET.TITLE", $field_groups[1][0]);
            }
            else {
                $tpl->addDataItem("FIELDSET.TITLE", "-");
            }
            $tpl->addDataItem("FIELDSET.ID", 1);
        }

        $fieldset = "";

        while (list($key, $val) = each($desc)) {

            // editor special cases
            if ($this->fields[$key]["is_editor"] == true) {
                $this->fields[$key]["type"] = "nothing";
                $this->fields[$key]["displayonly"] = true;
            }

            if ($fieldset != $fields_in_group[$key] && $fields_in_group[$key] != 0) {
                $fieldset = $fields_in_group[$key];
                $tpl->addDataItem("FIELDSET.TITLE", $field_groups[$fieldset][0]);
                $tpl->addDataItem("FIELDSET.STYLE", $field_groups[$fieldset][1]);
                $tpl->addDataItem("FIELDSET.ID", $fieldset);
            }

            if ($do == "update" && $this->fields[$key]["displayonly"] == true) {
                if ($this->fields[$key]["list"][$this->fields[$key]["value"]] != "") {
                    $result =  "<label for=\"action\">" . $this->fields[$key]["list"][$this->fields[$key]["value"]] . "</label>";
                }
                else {
                    $result =  "<label for=\"action\">" . $this->fields[$key]["value"] . "</label>";
                }
                if (eregi("^<iframe", $this->fields[$key]["value"])) {
                    $result .= "<input type=\"hidden\" name=\"$key\" value=\"\">\n";
                }
                else {
                    $result .= "<input type=\"hidden\" name=\"$key\" value=\"" . $this->fields[$key]["value"] . "\">\n";
                }
            }
            else {
                if ($this->fields[$key]["type"] == "nothing") {
                    $result =  "<label for=\"action\">" . $this->fields[$key]["value"] . "</label>";
                    $result .= "<input type=\"hidden\" name=\"$key\" value=\"\">\n";
                }
                else if ($this->fields[$key]["type"] == "extern") {
                    $result =  "<label for=\"action\">" . $this->fields[$key]["value"] . "</label>";
                    $result .= "<input type=\"hidden\" name=\"$key\" value=\"\">\n";
                }
                else if ($this->fields[$key]["type"] == "onlyhidden") {
                    $result = "<input type=\"hidden\" name=\"$key\" value=\"" . $this->fields[$key]["value"] . "\">\n";
                }
                else {
                    $f = new AdminFields($key, $this->fields[$key]);
                    $f->java = $this->fields[$key]["java"];
                    $result = $f->display($this->fields[$key]["value"]);
                }
            }

            // ##################################
            // PARSE

            if ($this->fields[$key]["type"] == "nothing") {
                $subblock = "FIELDSET.NOTHING";
            }
            else if ($this->fields[$key]["type"] == "extern") {
                $subblock = "FIELDSET.EXTERN";
            }
            else {
                $subblock = "FIELDSET.MAIN";
            }

            // buttonfield
            if ($this->fields[$key]["buttonfield"]) {
                $subblock = "FIELDSET.BUTTONS";
            }

            $tpl->addDataItem("$subblock.DESC", $val);
            $tpl->addDataItem("$subblock.FIELD", $result);
            $tpl->addDataItem("$subblock.FIELD1", $key);

            if ($this->fields[$key]["display"] == "none") {
                $tpl->addDataItem("$subblock.FIELD1_STYLE", "display:none");
            }
            else {
                $tpl->addDataItem("$subblock.FIELD1_STYLE", "");
            }

            $tpl->addDataItem("$subblock.EXTRA", $this->fields[$key]["extra"]);

            // CHECK FOR HELPERS
            if ($this->helper_buttons[$key][1] != "") {
                $tpl->addDataItem("$subblock.EXTRA",
                "<a href=\"".$this->helper_buttons[$key][1]."\"><img src=\"".$this->helper_buttons[$key][0]."\" align=\"absmiddle\" alt=\"\" border=0></a>" . $this->fields[$key]["extra"]);
            }
            else {
                $tpl->addDataItem("$subblock.EXTRA", $this->fields[$key]["extra"]);
            }


            // Error with the field
            if ($bad[$key] == 1) {
                $tpl->addDataItem("$subblock.COLOR", "red");
            }
            else {  $tpl->addDataItem("$subblock.COLOR", "");  }

            // ##################################

            unset($result);
        }

    // error
    if (sizeof($this->badfields) > 0) {
        $tpl->addDataItem("INFO.TITLE", $this->general["error"]);
        $tpl->addDataItem("INFO.INFO", $this->general["required_error"]);
        $tpl->addDataItem("INFO.TYPE", "error");
    }
    else {
        if ($this->db_write == true) {
            $tpl->addDataItem("INFO.TITLE", $this->general["modify_text"]);
            $tpl->addDataItem("INFO.INFO", $this->general["modify_text"]);
            $tpl->addDataItem("INFO.TYPE", "confirm");
        }
        else if ($this->general["other_error"] != "") {
            $tpl->addDataItem("INFO.TITLE", $this->general["error"]);
            $tpl->addDataItem("INFO.INFO", $this->general["other_error"]);
            $tpl->addDataItem("INFO.TYPE", "error");
        }
    }

    $tpl->addDataItem("ENCTYPE", $this->general["enctype"]);

    $tpl->addDataItem("HIDDEN.NAME", "do");
    $tpl->addDataItem("HIDDEN.VALUE", $do);

    if ($do == "update") {
        // PREV & NEXT DISABLED AT THIS POINT
        //$this->PrevAndNext($what, $from, $where, $start, $filter, $filter_fields, $sort, $sort_type, $idfield, $id);
        //  if ($this->prev != "") {
        //      $tpl->assign("PREV", "<a href=\"" . $this->phpself . "?" . ereg_replace("&id=([^&])*", "", $_SERVER["QUERY_STRING"]) . "&id=" . $this->prev . "\">&lt;" . $this->general["prev"] . "</a>");
        //  }
        //  if ($this->next != "") {
        //      $tpl->assign("NEXT", "<a href=\"" . $this->phpself . "?" . ereg_replace("&id=([^&])*", "", $_SERVER["QUERY_STRING"])  . "&id=" . $this->next . "\">" . $this->general["next"] . "&gt;</a>");
        //  }
        //  $tpl->assign("POS", $this->pos);
        //  $tpl->assign("MAX", $this->max);

        $tpl->addDataItem("HIDDEN.NAME", "id");
        $tpl->addDataItem("HIDDEN.VALUE", $id);
    }


    $tpl->addDataItem("HIDDEN.NAME", "sort"); $tpl->addDataItem("HIDDEN.VALUE", $sort);

    $tpl->addDataItem("HIDDEN.NAME", "sort_type"); $tpl->addDataItem("HIDDEN.VALUE", $sort_type);

    $tpl->addDataItem("HIDDEN.NAME", "filter"); $tpl->addDataItem("HIDDEN.VALUE", $filter);

    $tpl->addDataItem("HIDDEN.NAME", "structure"); $tpl->addDataItem("HIDDEN.VALUE", $structure);

    if (is_array($this->hidden)) {
        reset($this->hidden);
        while (list($key, $val) = each($this->hidden)) {
            $tpl->addDataItem("HIDDEN.NAME", $key); $tpl->addDataItem("HIDDEN.VALUE", $val);
        }
    }

    $result = $tpl->parse();
    return $result;
    }

}
