<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . '/class/admin2.class.php'); 		// administration main object
require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/DbHelpers.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/text.class.php');

$txt = new Text($language2, 'admin_general');
$txtf = new Text($language2, "module_importexport");

$perm = new Rights($group, $user, "root", true);

// permissions
$perm->Access (0, 0, "m", "");

/* the fields in the table */
$fields = array(
	"tables" => $txtf->display("tables"),
	"format" => $txtf->display("format"),

	"delimiter" => $txtf->display("delimiter"),
	"enclosure" => $txtf->display("enclosure"),
	"add_f_names" => $txtf->display("add_f_names"),

	"fields" => $txtf->display("fields"),
	"filter_lang" => $txtf->display("filter_lang"),
	"to_file" => $txtf->display("to_file"),
	"output" => $txtf->display("output")
);

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array(
	"tables" => 1,
	"fields" => 1
);

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
);

/* the fields(associations) to display in the list */
$disp_fields = array(
);

/* required fields */
$required = array(
	"title"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

 	$what = array(
		"$table.*"
	);
	$from = array(
		$table
	);

	$where = "$table.language = '$language'";

	$filter_fields = array(
		"$table.title"
	);

 /* end display list part */

// If for example our table has references to another table (foreign key)

////////////////////////////////////////////////////////////////////////////////
// Library functions, should be grouped and placed in some global class/classes
////////////////////////////////////////////////////////////////////////////////

/**
 * Wrap text within CDATA
 *
 * Additionally all ">" in text are converted to "&gt";
 *
 * @param string $xml
 * @return string
 */
function xml_escape($xml)
{
    return '<![CDATA[' . strtr($xml, '>', '&gt;') . ']]>';
}

/**
 * Convert array to comma separated values string
 *
 * @param array $array
 * @param string $delimiter separate field values
 * @param string $enclosure wrap field values
 * @return string
 */
function array_to_csv($array, $delimiter, $enclosure)
{
    $values = array();
    foreach ($array as $v) {
        $values[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $v)
            . $enclosure;
    }

    return implode($delimiter, $values);
}

/**
 * Convert array of table fields returned by {@see DbHelpers::get_tbl_fields} to
 * form array ( field_name => field_type}.
 *
 * Field types are converted and contains only data type without it size and other
 * additional information.
 *
 * @param array $fields assoc array of fields information returned by {@see DbHelpers::get_tbl_fields}
 * @return array
 */
function prepare_assoc_fields($fields)
{
    $prep_fields = array();
    foreach ($fields as $field => $field_info) {
        $m = array();
        preg_match('/([a-z]+)\(?/', $field_info['type'], $m);
        $prep_fields[$field] = $m[1];
    }

    return $prep_fields;
}

/**
 * Export database table
 *
 * @param sql $sql
 * @param int $dbc
 * @param string $table
 * @param array $fields array of fields to export. keys are field names, values are
 *  data types
 * @param string $cond where conditions
 * @param string $format
 * @param bool $send_file if TRUE than file will be forced to be downloaded by user
 * @param array $props associative array of properties
 * @return string
 */
function export(&$sql, $dbc, $table, $fields, $cond, $format, $send_file = false, $props = array())
{
    $output = '';

    if ($send_file) {
        // send http headers to force opening download file dialog by browser
        $fname = "$table.$format";
        header("Pragma: no-cache");
        header("Cache-control: no-cache");
        header("Content-disposition: attachment; filename=$fname");
    }

    // header
    switch ($format) {

        case 'xml':
            $output = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n\n";
            $output .= "<$table>\n";
        break;

        case 'csv':
            // process values in props array
            foreach (array('delimiter', 'enclosure') as $v) {
                $props[$v] = $props[$v];
            }

            if (1 == @$props['header_row']) {
                // put fist row with column names
                $output .= array_to_csv(array_keys($fields), $props['delimiter'], $props['enclosure']) . "\n";
            }
        break;
    }

    $sql->query($dbc, "SELECT " . DbHelpers::array_to_fields(array_keys($fields)) . " FROM `$table`"
         . ($cond ? "WHERE $cond" : ''));

    // body
    while ($row = $sql->nextrow()) {

        // remove dumplicated fileds from $row
        $tmp = array();
        foreach ($fields as $field_name => $dummy) {
            $tmp[$field_name] = $row[$field_name];
        }
        $row = $tmp;

        switch ($format) {

            case 'xml':
                $output .= "	<item>\n";
                foreach ($fields as $field_name => $field_type) {
                    // wrap some fields with CDATA
                    if (in_array($field_type, array('char', 'varchar', 'tinytext'
                        , 'mediumtext', 'text', 'longtext', 'tinyblob', 'mediumblob'
                        , 'blob', 'longblob', 'enum', 'set')))
                    {
                        $row[$field_name] = xml_escape($row[$field_name]);
                    }

                    $output .= "		<$field_name>$row[$field_name]</$field_name>\n";
                }

                $output .= "	</item>\n";

            break;

            case 'csv':
                $output .= array_to_csv($row, $props['delimiter'], $props['enclosure']) . "\n";
            break;

            default:
                trigger_error(sprintf('Unknown export format: "%s"', $format)
                    , E_USER_ERROR);
        }

        if ($send_file) {
            echo $output;
            $output = '';
        }

        if (strlen($output) > 4194304 /* 4MB */) {
            $output .= "\n\n WARNING! Too big table, please select 'Into file' option\n";
            break;
        }
    }

    // footer
    switch ($format) {

        case 'xml':
            $output .= "</$table>\n";
        break;

    }

    if ($send_file) {
        echo $output;
        exit();
    }

    return $output;
}

////////////////////////////////////////////////////////////////////////////////

function external() {
	global $adm, $show, $txtf, $txt, $group, $language, $id, $structure;
	$sq = new sql;

	// get all available tables
	$tables = DbHelpers::get_tables($sq, $adm->dbc);

    // hide some fields by default
    foreach (array('fields', 'to_file', 'output', 'delimiter', 'filter_lang'
        , 'enclosure', 'add_f_names') as $field)
    {
        $adm->assignProp($field, 'type', 'nothing');
    }

    // always available fields
    $adm->assignProp("tables", "type", "select");
	$adm->assignProp("tables", "list", Arrays::array_combine($tables, $tables));
    $adm->assignProp("tables", "java", "onChange=\"for(i=this.form.elements.length;i>0;i--)"
        . " {if ('fields[]'==this.form.elements[i-1].name) {this.form.elements[i-1].selectedIndex=-1; break;}}"
        . " submit();\"");

    $adm->assignProp("format", "type", "select");
    $adm->assignProp("format", "list", array("none" => "", "xml" => $txtf->display("format_xml")
        , "csv" => $txtf->display("format_csv")));
    $adm->assignProp("format", "java", "onChange=\"submit();\"");

    // fill in tables field
    $adm->assignProp("tables", "value", $adm->values['tables']);

	if ($adm->values['tables'] && 'none' != $adm->values['format']) {
	    // form submitted and data format selected
	    $table = $adm->values['tables']; // shortcut

	    // change submit button text
	    $adm->general['button'] = $txtf->display('export_btn');

	    //
	    // init form elements
	    //

        // common fields
	    $adm->assignProp("to_file", "type", "checkbox");
	    $adm->assignProp("to_file", "value", $adm->values['to_file']);
	    $adm->assignProp("output", "type", "nothing");
		$adm->assignProp("format", "value", $adm->values['format'] );

		// table fields, store them in $fields_info variable
		$fields = prepare_assoc_fields(DbHelpers::get_tbl_fields($sq, $adm->dbc, $table));

		// strip datatype information from fields
		$fields_list = array_keys($fields);

		$adm->assignProp("fields", "type", "select2");
		$adm->assignProp("fields", "size", 10);
		$adm->assignProp("fields", "list", Arrays::array_combine($fields_list, $fields_list));
		$adm->assignProp("fields", "value", $adm->values['fields']);

		// fields dependent on selected output format
		switch ($adm->values['format']) {

		    case 'csv':

		        //
		        // Setup form field related to CSV export
		        //

    			$options = array('delimiter' => array('textinput', ';')
    			     , 'enclosure' => array('textinput', '"')
    			     , 'add_f_names' => array('checkbox',false)
    			);

    			foreach ($options as $opt_name => $option) {
    			    list($opt_type, $def_value) = $option;

    			    // set default value for field if it's empty
    			    if (!array_key_exists($opt_name, $adm->values) || null == $adm->values[$opt_name]) {
    			        $adm->values[$opt_name] = $def_value;
    			    }

    			    // add field
    			    $adm->assignProp($opt_name, 'type', $opt_type);
    			    $adm->assignProp($opt_name, 'value', $adm->values[$opt_name]);
    			}

    			// initialize $props array passed to export function
    			$props = array();
    			foreach (array('delimiter' => 'delimiter', 'enclosure' => 'enclosure'
    			     , 'add_f_names' => 'header_row') as $form_field => $property)
    			{
    			    $props[$property] = $adm->values[$form_field];
    			}

		      break;
		}

		// language field
		if ($languages = DbHelpers::get_tbl_langs($sq, $adm->dbc, $adm->values['tables'])) {
            $adm->assignProp("filter_lang", "type", "select");
            $adm->assignProp("filter_lang", "list", Arrays::array_combine($languages, $languages));
		}

	    if ($adm->values['fields']) {
	        // some fields was selected, performing export
	        if ($adm->values['filter_lang']) {
	            $where = sprintf("language = '%s'", addslashes($adm->values['filter_lang']));
	        }
	        else {
	            $where = '';
	        }

	        $fields = Arrays::array_intersect_key_val($fields, $adm->values['fields']);

	        /////
            $output = export($sq, $adm->dbc, $table, $fields, $where, $adm->values['format']
                , $adm->values['to_file'], $props);
            /////

    		$adm->assignProp("output", "type", "textfield");
    		$adm->assignProp("output", "cols", "80");
    		$adm->assignProp("output", "rows", "20");
    		$adm->assignProp("output", "value", $output);
	    }
	}
}

// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 100) { $general["max_entries"] = $max_entries; }

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

$adm = new Admin2('none');

$sq = new sql;

$adm->assign("language", $language);

// permissions
$perm->Access (0, $id, "m", "moderaxmldata");

external();
$result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);

$tpl->addDataItem("TITLE", $txtf->display("module_title"));
$active_tab = 2;

$nr = 1;
while(list($key, $val) = each($tabs)) {
	$tpl->addDataItem("TABS.ID", $nr);
	$tpl->addDataItem("TABS.URL", "javascript:fieldJump($nr, ".sizeof($tabs).", '".$val[1]."');");
	$tpl->addDataItem("TABS.NAME", $val[0]);
		if ($active_tab == $nr) {
			$tpl->addDataItem("TABS.CLASS", "class=\"active\"");
		}
		else {
			$tpl->addDataItem("TABS.CLASS", "class=\"\"");
		}
	$nr++;
}

//$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();
