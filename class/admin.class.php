<?php
/**
 * Modera.net administration main class. controls form and list display and adding/modifying
 *
 * @package modera_net
 * @access public
 */

class Admin {
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
     * @var boolean after successful insert/update/delete this value is set to true
     */
    var $db_write = false;
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
    var $entries = array(10, 20, 30, 50, 100);

    /**
     * Notice message
     *
     * @var string
     * @access private
     */
    var $_notice = '';

    /** @var Database */
    private $db;

    /**
     * Class constructor. Set values to variables, create a database connection (connection id $this->dbc)
     *
     * @access public
     */

    function Admin($table) {
        global $general;
        global $show;

        $path_parts = parse_url(SITE_URL);
        $engine_url = $path_parts['path'];
        if (substr($engine_url,0,1) != "/") $engine_url = "/" . $engine_url;
        if (substr($engine_url,-1) != "/") $engine_url = $engine_url . "/";
        $this->engine = $engine_url;

        $this->status = $show;
        $this->phpself = $_SERVER["PHP_SELF"];
        $this->values = $_POST;
        $this->general = $general;
        $this->debug = $GLOBALS["modera_debugsql"];
        $this->table = $table;
        $db = new DB;
        $this->dbc = $db->connect();
        $this->db = $GLOBALS['database'];
    }

    /**
     * Creates an array about fields from information gathered from a SQL table.
     * Table name is supplied when initializing this object.
     *
     * @return array returns an array with all fields and their specific features (name, type, value, size, cols, rows, list), internally $this->fields
    */

    function types() {
        if ($this->table) {
            $sq = new sql;
            $sq->query($this->dbc, "show fields from " . $this->table . "");
            while ($data = $sq->nextrow()) {
                unset($list);
                $field = $data["Field"];
                $type = $data["Type"];
                $default = $data["Default"];
                $size = $type;
                $type = eregi_replace("\\(.*\\).*", "", $type);
                $type = chop($type);
                $size = eregi_replace("^$type\(", "", $size);
                $size = eregi_replace("\).*$", "", trim($size));

                    $this->fields[$field]["name"] = $field;

                    if (ereg("text", $type)) { $this->fields[$field]["type"] = "textfield"; }
                    else if (ereg("enum", $type)) {
                        $this->fields[$field]["type"] = "select";
                        $arr = ereg_replace("'", "", $size);
                        $elements = explode(",", $arr);
                        for ($c = 0; $c < count($elements); $c++) {
                            $this->fields[$field]["list"][$elements[$c]] .= $elements[$c];
                        }
                    }
                    else { $this->fields[$field]["type"] = "textinput"; }

                    if ($type == "date") {
                         $this->fields[$field]["type"] = "textinput"; $size = 10; $max = 10;
                         $this->assignHelper($field, "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=1&field=".$field."', 270, 250);");
                    }
                    if ($type == "datetime") {
                        $this->fields[$field]["type"] = "textinput"; $size = 20; $max = 19;
                         $this->assignHelper($field, "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=11&field=".$field."', 270, 250);");
                    }

                    if ($type == "timestamp") {
                        $this->fields[$field]["type"] = "textinput"; $size = 15; $max = 14;
                         $this->assignHelper($field, "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=4&field=".$field."', 270, 250);");
                    }
                    if ($type == "time") {
                        $this->fields[$field]["type"] = "textinput"; $size = 9; $max = 8;
                         $this->assignHelper($field, "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=7&field=".$field."', 270, 250);");
                    }
                    if ($type == "year") {
                        $this->fields[$field]["type"] = "textinput"; $size = 5; $max = 4;
                         $this->assignHelper($field, "pic/calendar.gif", "javascript:newWindow('popup_calendar.php?type=6&field=".$field."', 270, 250);");
                    }

                    if ($type == "longblob") { $this->fields[$field]["type"] = "file"; $size = "";}

                    if ($this->values[$field] && $this->fields[$field]["value"] == "") { $this->fields[$field]["value"] = $this->values[$field];    }
                    else if ($this->fields[$field]["value"] == "") { $this->fields[$field]["value"] = $default; }

                    if ($size > 60) $size = 60;
                    if ($size) { $this->fields[$field]["size"] = $size; }
                    else { $this->fields[$field]["size"] = $this->def_size; }

                    if ($max) { $this->fields[$field]["max"] = $max; }
                    else { $this->fields[$field]["max"] = $this->def_max; }

                    $this->fields[$field]["cols"] = $this->def_cols;
                    $this->fields[$field]["rows"] = $this->def_rows;

            unset($max);
            unset($size);
            unset($type);

            }
            $sq->free();
        return $this->fields;
        }
    }

    /** Assign values to fields' properties
     * @param string Fieldname
     * @param string Property name
     * @param mixed The value to assign
    */

    function assignProp($key, $property, $value) {
        if ($key != "" && $property != "" && $value != "") {
            $this->fields[$key][$property] = $value;
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

    /**
     * Adds values to the desired SQL table. the function first runs getValues()
     * function table structure is acquired directly from DB server
     *
     * @param string SQL table name. When no name is specified, default one
     *  (the one this object was initialized) will be used
     * @param array List of required fields
     * @return mixed 0 on success, array in case required fields failed
     */
    function add($table, $required=array(), $idfield) {

        $this->getValues();

        if ($this->checked != true) {
            $req = $this->checkRequired($required);
        }

        if ($table) { $tab = $table; }
        else { $tab = $this->table; }

        if (sizeof($req) > 0) {
            return $req;
        }
        else {
            $sq = new sql;
            $sq->query($this->dbc, "show fields from $tab");
            $i = 0;
            while ($data = $sq->nextrow()) {
                $this->fields[$data["Field"]]["value"] = addslashes($this->fields[$data["Field"]]["value"]);
                if ($data["Field"] == $idfield) {
                    $gg .= "null";
                }
                else {
                    if ($this->fields[$data["Field"]]["type"] == "password") {
                        // check mysql version, if it is >= 4.1.0 we will use old_password function
                        $hash_funct = $sq->pass_funct($this->dbc);
                        $gg .= $hash_funct . "('" . $this->fields[$data["Field"]]["value"] . "')";
                    }
                    else if ($this->fields[$data["Field"]]["type"] == "md5") {
                        $gg .= "MD5('" . $this->fields[$data["Field"]]["value"] . "')";
                    }
                    else {
                        $gg .= "'" . $this->fields[$data["Field"]]["value"] . "'";
                    }
                }
                $k = $i + 1;
                if ($k < $sq->rows()) {
                    $gg .= ", ";
                }
            $i++;
            }
        $sq->free();
        $sql = "insert into $tab values($gg)";
        if ($this->debug == true) {
            echo "<!-- $sql -->\n\n";
        }
        $res = $sq->query($this->dbc, $sql);
        $this->insert_id = $sq->insertID();
        if ($res) {
            $this->db_write = true;
        }
        //if ($res) {
        //  $this->insert_id = $sq->insertID();
        //}

        return 0;

        }
    }

    /** Modifies the data selected in a SQL table. the function first runs getValues() function
     * @param string SQL table name. When no name is specified, default one (the one this object was initialized) will be used
     * @param array List of fields to be updated
     * @param array List of required fields
     * @param string Name of the unique ID field
     * @param integer Row ID that will be updated
     * @return mixed 0 success, array on required fields failed
    */
    function modify($table, $what=array(), $required=array(), $idfield, $id) {
        $this->getValues();

        if ($this->checked != true) {
            $req = $this->checkRequired($required);
        }

        $tab = $table ? $table : $this->table;

        if (sizeof($req) > 0) {
            return $req;
        }

        $sq = new sql;
        $gg = '';
        for ($c = 0; $c < sizeof($what); $c++) {
            $this->fields[$what[$c]]["value"] = addslashes($this->fields[$what[$c]]["value"]);
            $field = $this->db->quote_field_name($what[$c]);
            if ($this->fields[$what[$c]]["type"] == "password") {
                // check mysql version, if it is >= 4.1.0 we will use old_password function

                $hash_funct = $sq->pass_funct($this->dbc);
                $gg .= $field . " = $hash_funct('" . $this->fields[$what[$c]]["value"] . "')";
            }
            else if ($this->fields[$what[$c]]["type"] == "md5") {
                $gg .= $field . " = MD5('" . $this->fields[$what[$c]]["value"] . "')";
            }
            else {
                $gg .= $field . "='" . $this->fields[$what[$c]]["value"] . "'";
            }
            $k = $c + 1;
            if ($k < sizeof($what)) {
                $gg .= ", ";
            }
        }

        $sql = "update $tab set $gg where $idfield = '".addslashes($id)."'";
        if ($this->debug == true) {
            echo "<!-- $sql -->\n\n";
        }
        if ($gg != "") {
            $res = $sq->query($this->dbc, $sql);
            if ($res) {
                $this->db_write = true;
            }
        }

        return 0;
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

    /** Deletes the desired row from defined SQL table.
     * @param string SQL table name. When no name is specified, default one (the one this object was initialized) will be used
     * @param string The ID field that will be used in the Delete query WHERE clause (most commonly simply 'id')
     * @param integer The ID to match the SQL delete query against.
     * @return integer 0 on success, 99 on error
    **/

    function delete ($table, $idfield, $id) {
        if ($table) { $tab = $table; }
        else { $tab = $this->table; }
        $sq = new sql;
        $result = $sq->query($this->dbc, "delete from $tab where $idfield = '".addslashes($id)."'");
        if ($result) {
            $this->db_write = true;
            return 0;
        }
        else { return 99; }
    }

    /** Retrieves all fields from a SQL table corresponding to a given ID.
     * @param string SQL table name. When no name is specified, default one (the one this object was initialized) will be used
     * @param string The ID field that will be used in the Delete query WHERE clause (most commonly simply 'id')
     * @param integer The ID to match the SQL delete query against.
    */

    function fillValues($table, $idfield, $id) {
        if ($table) { $tab = $table; }
        else { $tab = $this->table; }
        $sq = new sql;
        $sq->query($this->dbc, "select * from $tab where $idfield = '".addslashes($id)."'");
        $data = $sq->nextrow();
        if (is_array($data)) {
            while (list($key, $val) = each($data)) {
                $this->fields[$key]["value"] = $val;
            }
        }
        $sq->free();
    }

    /**
     * Fill the $this->fields array with values from post array
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

    /** Returns the ID from the last Insert query
     * @return integer
    */

    function returnInsert() {
        return $this->insert_id;
    }

    /**
     * Displays the rows (specific fields) in a list with user defined template
     * and functionality such as filtering and sorting.
     *
     * @param array Array of fields (object => name) which will be displayed
     * @param array List of columns names (or associations) to be put in the query
     * @param array List of locations (tables) to be put in the query
     * @param string A text string containing additional WHERE clause for the query (ex. 'field1 != 0 AND $field2 = 99')
     * @param integer Location to start displaying the data (when total rows is greater than max rows to display per page)
     * @param string Which object to sort
     * @param string Sorting order (ascending or descending, default is asc)
     * @param string Free text that will be applied to refine the results
     * @param array Free text that will be applied to refine the results
     * @param string Name of the field which is unique
     * @return string HTML Code with the current section
    */
    function show($display=array(), $what=array(), $from=array(), $where, $start, $sort, $sort_type, $filter, $filter_fields=array(), $idfield) {

        global $structure;

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
        if ($filter) {
            $nr = 1;
            $vilter = "WHERE (";
            for ($c = 0; $c < sizeof($filter_fields); $c++) {
                $vilter .= $filter_fields[$c] . " LIKE \"%".htmlspecialchars(stripslashes($filter))."%\"";
                if ($nr != sizeof($filter_fields)) {    $vilter .= " OR ";  }
            $nr++;
            }
            $vilter .= ") ";
            if (sizeof($e_vilter) > 0) {
                $vilter .= " AND (";
                $vilter .= join(" AND ", $e_vilter);
                $vilter .= ") ";
            }
        }
        else {
            if (sizeof($e_vilter) > 0) {
                $vilter = "WHERE (";
                $vilter .= join(" AND ", $e_vilter);
                $vilter .= ") ";
            }
        }

        if ($where) {
            if ($filter || sizeof($e_vilter) > 0) {
                $vilter .= " AND $where";
            }
            else {
                $vilter .= " WHERE $where";
            }
        }
        /* end generate filter part of query */

        /* generate what to select */
        $nr = 1;
        for ($c = 0; $c < sizeof($what); $c++) {
            $wha .= $what[$c];
            if ($nr != sizeof($what)) { $wha .= ", ";   }
        $nr++;
        }
        /* end generate what to select */

        /* generate from where to select */
        $nr = 1;
        for ($c = 0; $c < sizeof($from); $c++) {
            $fro .= $from[$c];
            if ($nr != sizeof($from)) { $fro .= " ";    }
        $nr++;
        }
        /* end generate from where to select */

        if ($sort && $sort != "listnumber") { $zort = " ORDER BY $sort $sort_type"; }
        else {
            if ($this->general["sort"] != "") $zort = " ORDER BY " . $this->general["sort"];
            else { $zort = ""; }
        }

        /* and finally let's display something */

        //parse extra filters
        //$tpl->addDataItem("EXTRAFILTER", join("", $e_vilter2));

        // header
        while (list($key, $val) = each($display)) {
            $url = $this->phpself . "?start=$start&structure=$structure&sort=$key&sort_type=$sort_type1&filter=$filter" . $hidadd;
            $tpl->addDataItem("HEADER.NAME", $val);
            $tpl->addDataItem("HEADER.URL", $url);
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

        $sql = "SELECT $wha FROM $fro $vilter $zort LIMIT $start, " . $this->general["max_entries"];
        if ($this->debug == true) {
            echo "<!-- $sql -->\n\n";
        }
        $sq = new sql;
        $sq->query($this->dbc, $sql);

        $listnumber = $start + 1;
        while ($data = $sq->nextrow()) {
            //if ($coll == 1) { $coll = ""; }
            //else { $coll = 1; }
            $data["listnumber"] = $listnumber;
            while (list($key, $val) = each($display)) {
                if ($sort == $key && $sort != "listnumber") {
                    $style = "class=\"active\"";
                }
                else {
                    $style = "";
                }
                $url = $this->phpself . "?show=modify&id=" . $data[$idfield] . "&start=$start&structure=$structure&sort=$sort&sort_type=$sort_type&filter=$filter&max_entries=" . $this->general["max_entries"]  . $hidadd;
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
        if ($structure) $struc = $structure;
        else { $struc = ""; }

        if ($structure) {
            $tpl->addDataItem("ROWS.HIDDEN1", $hidadd . "&structure=$structure");
        }
        else {
            $tpl->addDataItem("ROWS.HIDDEN1", $hidadd);
        }

        $tpl->addDataItem("ROWS.COLUMNS", $columns);
        $tpl->addDataItem("ROWS.ID", $data[$idfield]);
        $tpl->addDataItem("ROWS.STRUCTURE", $struc);

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
        $tpl->addDataItem("VAL_STRUCTURE", $structure);

        $tpl->addDataItem("HIDDEN", $hidhid);

        $sql = "SELECT count(" . $this->table . ".$idfield) as totalus FROM $fro $vilter";
        if ($this->debug == true) {
            echo "<!-- $sql -->\n\n";
        }
        $sq->query($this->dbc, $sql);
        $data = $sq->nextrow();
        $total = $data["totalus"];
        if ($total > $this->general["max_entries"]) {
            $page = $this->showPages($start, $total, $this->phpself . "?sort=$sort&structure=$structure&sort_type=$sort_type&filter=$filter&max_entries=" . $this->general["max_entries"]  . $hidadd);
            $sq->free();
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

    /**
     * Creates the actual form view (add or modify)
     *
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

        global $start, $sort_type, $max_entries, $what, $from, $where, $filter_fields, $idfield;

        $tpl = new template;
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);
        $tpl->setTemplateFile($this->general["template_form"]);

        if ($sort_tyyp == "") $sort_tyyp = addslashes($sort_type);

        if (is_array($this->hidden)) {
            foreach ($this->hidden as $key => $val) {
                $hidadd .= "&$key=$val";
            }
        }

        $back = "?show=modify&id=$id&sort=$sort&sort_type=$sort_tyyp&filter=$filter"
            . "&max_entries=$max_entries&start=$start$hidadd";

        $tpl->addDataItem("PHP_SELF", $_SERVER["PHP_SELF"]);
        $tpl->addDataItem("BACK", $back);
        $tpl->addDataItem("BACKTOLIST", $this->general["backtolist"]);
        $tpl->addDataItem("CURRENT", $this->general["current"]);
        $tpl->addDataItem("EXTRABUTTONS", $this->general["extrabuttons"]);
        $bt = ereg_replace("\&", "|1|", $this->general["button"]);
        $bt = ereg_replace("\#", "|2|", $bt);
        $tpl->addDataItem("SENDBUTTON", $this->general["button"]);
        $tpl->addDataItem("SENDBUTTONTXT", $this->general["button"]);

        if (is_array($this->general["extra_buttons"])) {
            foreach ($this->general["extra_buttons"] as $ebkey => $ebval) {
                $tpl->addDataItem("BUTTONS.ACTION", $ebval[1]);
                $tpl->addDataItem("BUTTONS.BUTTON", $ebval[0]);
                $tpl->addDataItem("BUTTONS.IMAGE", $ebval[2]);
                $tpl->addDataItem("BUTTONS.BTNEXTRA", $ebval[3]);
            }
            reset($this->general["extra_buttons"]);
        }

        for ($c = 0, $l = count($this->badfields); $c < $l; $c++) {
            $bad[$this->badfields[$c]] = 1;
        }
        reset($this->badfields);

        if (sizeof($fields_in_group) == 0 || sizeof($field_groups) == 0) {
            if ($field_groups[1][0]) {
                $tpl->addDataItem("FIELDSET.TITLE", $field_groups[1][0]);
            } else {
                $tpl->addDataItem("FIELDSET.TITLE", "-");
            }
            $tpl->addDataItem("FIELDSET.ID", 1);
        }

        $fieldset = "";

        foreach ($desc as $key => $val) {
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

            if (($do == "update" || $do == "add") && $this->fields[$key]["displayonly"] == true) {
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

            $tpl->addDataItem("$subblock.DESC", is_null($val) ? '' : $val);
            $tpl->addDataItem("$subblock.FIELD", $result);
            $tpl->addDataItem("$subblock.FIELD1", $key);

            if ($this->fields[$key]["display"] == "none") {
                $tpl->addDataItem("$subblock.FIELD1_STYLE", "display:none");
            }
            else {
                $tpl->addDataItem("$subblock.FIELD1_STYLE", "");
            }

            // CHECK FOR HELPERS
            if ($this->helper_buttons[$key][1] != "" && $this->fields[$key]["displayonly"] != true) {
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

        // notice box
        if ($this->_notice) {
            $tpl->addDataItem("NOTICE.CONTENT", $this->_notice);
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
        $tpl->addDataItem("HIDDEN.NAME", "id"); $tpl->addDataItem("HIDDEN.VALUE", $id);

        if (is_array($this->hidden)) {
            foreach ($this->hidden as $key => $val) {
                $tpl->addDataItem("HIDDEN.NAME", $key);
                $tpl->addDataItem("HIDDEN.VALUE", $val);
            }
        }

        return $tpl->parse();
    }

    /**
     * Set notice
     *
     * Notice will be passed later to template variable NOTICE.CONTENT
     *
     * @param string $notice
     */
    function setNotice($notice)
    {
        $this->_notice = (string)$notice;
    }
}
