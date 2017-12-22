<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once('admin_header.php');
require_once(SITE_PATH . '/class/admin2.class.php'); 		// administration main object
require_once(SITE_PATH . '/class/SaxParserCb.php');
require_once(SITE_PATH . '/class/Arrays.php');
require_once(SITE_PATH . '/class/DbHelpers.php');
require_once(SITE_PATH . '/class/templatef.class.php');
require_once(SITE_PATH . '/class/text.class.php');

$txt = new Text($language2, 'admin_general');
$txtf = new Text($language2, "module_importexport");

$perm = new Rights($group, $user, "root", true);

// permissions
$perm->Access (0, 0, "m", "");

// general parameters (templates, messages etc.)
$general['enctype'] = "enctype=\"multipart/form-data\"";

// fields to show in the form
$fields = array(
    'data_file' => $txtf->display('file'),
    'format' => $txtf->display('format'),
    'delimiter' => $txtf->display('delimiter'),
    'enclosure' => $txtf->display('enclosure'),
    'header_row' => $txtf->display('first_row_f_names'),
    'table' => $txtf->display('table'),
);

$field_groups = array(
	1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array(
    'table' => 1
);

/**
 * Tries to determine passed data type
 *
 * <pre>
 * Can determine following data formats:
 * XML - seaches for XML declatration {@link http://www.w3.org/TR/2004/REC-xml-20040204/#NT-XMLDecl}
 * </pre>
 *
 * @param string $data
 * @return string|FALSE XML, or FALSE if couldnt determine data type
 */
function determine_fmt($data)
{
    // seach for XML declaration string
    if (preg_match('/<?xml\s+version=.*\s+\?>/s', $data)) {
        return 'XML';
    }

    // unable to discover data format
    return false;
}

/**
 * Return list of fields in cvs data file
 *
 * @param string $file
 * @param string $delimiter
 * @param string $enclosure
 * @param bool $header_row wether fist row contains field names or not
 * @return array
 */
function csv_fields_list($file, $delimiter, $enclosure, $header_row)
{
    global $txtf;

    $fp = fopen($file, 'r');
    $row = my_fgetcsv($fp, 4096, $delimiter, $enclosure);
    fclose($fp);

    if (!$header_row) {
        $f_title = $txtf->display('field') . ' N';
        foreach ($row as $k => $dummy) {
            $row[$k] = $f_title . $k;
        }
    }

    return $row;
}

/**
 * Return xml data file fields
 *
 * @param string $file path to data file
 * @return array array with fields names
 */
function xml_fields_list($file)
{
    $fp = fopen($file, 'r');
    $xml = fread($fp, 4096);
    fclose($fp);

    $xml = substr($xml, 0, strpos($xml, '</item>'));
    $xml = substr($xml, strpos($xml, '<item>') + 7);

    $fields = array();
    $m = array();
    if (preg_match_all('/<([a-zA-Z_]+)>/', $xml, $m)) {
        list(, $fields) = $m;
    }

    return $fields;
}

/**
 * Inserts reconds into database
 *
 * @param sql $sql
 * @param int $dbc
 * @param string $table
 * @param array $values associative array with table fields and their values
 */
function import_record(&$sql, $dbc, $table, $values)
{
    $fields = array_keys($values);
    foreach ($fields as $k => $field) {
        $fields[$k] = "`" . addslashes($field) . "`";
    }
    $fields = implode(',', $fields);

    foreach ($values as $k => $value) {
        $values[$k] = "'" . addslashes($value) . "'";
    }
    $values = implode(',', $values);

    $sql->query($dbc, "INSERT INTO `$table` ($fields) VALUES($values)");
}

/**
 * Import data from
 *
 * @param sql $sql
 * @param int $dbc
 * @param string $file
 * @param string $table taget table
 * @param array $fields associative array of fields mapping keys are database fields
 *  values are xml fields
 */
function import_xml(&$sql, $dbc, $file, $table, $fields)
{
    $p =& new SaxParserCb();
    foreach ($fields as $k => $v) $fields[$k] = strtolower($v);
    $p->set_callback(1, 'ITEM', 'xml_callback', array(&$sql, $dbc, $table, $fields));
    $p->parse_file($file);
    $p->destruct();
}

/**
 * Xml callback function
 */
function xml_callback($depth, $name, $data, &$extra)
{
    list($sql, $dbc, $table, $fields) = $extra;

    if (is_array($data['VALUE'])) {
        $values = array();
        foreach ($data['VALUE'] as $el_name => $el_arr) {
            if (array_key_exists($el_name = strtolower($el_name), $fields)) {
                $values[$el_name] = $el_arr['VALUE'];
            }
        }

        import_record($sql, $dbc, $table, $values);
    }
}

/**
 * Import data from csv file
 *
 * Function is streams aware, it means that file size if virtually unlimited
 *
 * @param sql $sql
 * @param int $dbc
 * @param stirng $file path to csv data file
 * @param string $table target table
 * @param array $fields fields mapping array keys are field names values are cvs field positions
 * @param string $delimiter
 * @param string $enclosure
 * @param bool $header_row TRUE if first row contains field names
 */
function import_csv(&$sql, $dbc, $file, $table, $fields, $delimiter, $enclosure
    , $header_row)
{
    $fp = fopen($file, 'r');

    // skip first header record
    if ($header_row) my_fgetcsv($fp, 4096, $delimiter, $enclosure);

    while ($row = my_fgetcsv($fp, 4096, $delimiter, $enclosure)) {
        $values = array();
        foreach ($fields as $tbl_field_name => $csv_field_pos) {
            $values[$tbl_field_name] = $row[$csv_field_pos];
        }

        import_record($sql, $dbc, $table, $values);
    }

    fclose($fp);
}

/**
 * Wrapper for fgetcvs
 *
 * Enclosure parameter was added in php 4.3.0
 *
 * @param resource $fp pointer to opened file
 * @param int $maxlen
 * @param string $delimiter
 * @param string $enclosure
 * @return array|FALSE {@link http://ee.php.net/manual/en/function.fgetcsv.php}
 */
function my_fgetcsv($fp, $maxlen, $delimiter, $enclosure)
{
    // call fgetcsv with diffrent parameters count depending on php version
    // enclosure parameter was added in php 4.3.0
    if (str_pad(str_replace('.', '', phpversion()), 3, '0') < 430) {
        return fgetcsv($fp, $maxlen, $delimiter);
    }
    else {
        return fgetcsv($fp, $maxlen, $delimiter, $enclosure);
    }
}

/**
 * External function is called with every form/list call.
 * Here you can define or redefine values, lists, fields and their types.
 *
 * @param object reference to admin object
 * @param object sql object
 * @param object template instance
*/
function external(&$adm, &$sql, &$tpl) {
	global $txtf, $txt, $fields, $directory;

	// hide some fields by default
	foreach (array('delimiter', 'enclosure', 'header_row') as $v) {
	    $adm->assignProp($v, 'type', 'nothing');
	}

	// get all available tables
	$tables = DbHelpers::get_tables($sql, $adm->dbc);

	$adm->assignProp('data_file', 'type', 'file');
    $adm->assignProp('format', 'type', 'select');
    $adm->assignProp('format', 'java', 'onChange="submit();"');
    $adm->assignProp('format', 'list', array('AUTODETECT' => 'Autodetect'
        , 'XML' => 'XML', 'CSV' => 'CSV'));
    $adm->assignProp("table", "type", "select");
	$adm->assignProp("table", "list", Arrays::array_combine($tables, $tables));
	$adm->assignProp("table", "value", $adm->values['table']);

    if ('add' == $adm->values['do']) {
        // form submitted

        // check if file was uploaded
        if ($adm->values['uploaded_file_path'] && $adm->values['uploaded_file_name']) {
            // check if path is allowed
            if (dirname(realpath('../' . $directory['upload']) . '/some.file')
                != dirname(realpath($adm->values['uploaded_file_path'])))
            {
                die('Wrong path to uploaded file');
            }

            // file already uploaded
            $file_path = $adm->values['uploaded_file_path'];
            $file_name = basename($adm->values['uploaded_file_name']);
            $file_uploaded = true;
        }
        elseif (array_key_exists('data_file', $_FILES) && is_uploaded_file($_FILES['data_file']['tmp_name'])) {
            // move uploaded file to upload/ directory
            $file_path = '../' . $directory['upload'] . '/import_' . basename($_FILES['data_file']['tmp_name']);
            move_uploaded_file($_FILES['data_file']['tmp_name'], $file_path);
            $file_name = $_FILES['data_file']['name'];
            $file_uploaded = true;
        }
        else {
            $file_uploaded = false;
        }

        // import
        if ($adm->values['import_data'] && $file_uploaded) {
            // trying to import uploaded data

            // get table fields
            $tbl_fields = DbHelpers::get_tbl_fields($sql, $adm->dbc, $adm->values['table']);
            for ($i = 1, $tmp = array(); list($field, ) = each($tbl_fields); $i++) {
                $tmp[$i] = $field;
            }
            $tbl_fields = $tmp;

            // check if at least one field is seleced for importing
            $selected_fields = array();
            foreach ($adm->values as $field => $value) {
                $m = array();
                if (preg_match('/^field_(\d+)$/', $field, $m) && (int)$value !== -1) {
                    $selected_fields[$tbl_fields[$m[1]]] = (int)$value;
                }
            }

            if ($selected_fields) {
                // fields was selected
                // importing data
                switch ($adm->values['format']) {
                    case 'CSV':
                        import_csv($sql, $adm->dbc, $file_path, $adm->values['table']
                            , $selected_fields, $adm->values['delimiter'], $adm->values['enclosure']
                            , $adm->values['header_row']);
                    break;

                    case 'XML':
                        import_xml($sql, $adm->dbc, $file_path, $adm->values['table']
                            , $selected_fields);
                    break;
                }

                // remove temporary data file
                unlink($file_path);

                // setting db_write to true
                $adm->db_write = true;
                $file_uploaded = false;
            }
            else {
                // fields was not selected
                $adm->general['other_error'] = $txtf->display('fiesld_was_not_selected');
            }
        }

        // perform input data format autodetection
        if ($file_uploaded && 'AUTODETECT' == $adm->values['format']) {
            $fp = fopen($file_path, 'r');
            $data_piece = fread($fp, 4096);
            if (!($adm->values['format'] = determine_fmt($data_piece))) {
                // couldn't detect data format, set it to CSV by default
                $adm->values['format'] = 'CSV';
            }

            fclose($fp);
        }

        // set new hidden field, so next time when form will be submitted we will
        // import data
        if ($file_uploaded) {
            $adm->assignHidden('import_data', true);
            $adm->assignProp('format', 'java', 'onChange="this.form.import_data.value=0;submit();"');
            $adm->assignProp('table', 'java', 'onChange="this.form.import_data.value=0;submit();"');
        }

        // setup additional format field
        switch ($adm->values['format']) {
            case 'CSV':
                foreach (array('delimiter' => array('type' => 'textinput', 'def_value' => ';')
                    , 'enclosure' => array('type' => 'textinput', 'def_value' => '"')
                    , 'header_row' => array('type' => 'checkbox', 'def_value' => 0))
                    as $field => $params)
                {
                    extract($params);
                    $adm->assignProp($field, 'type', $type);

                    if (!$adm->values[$field] && array_key_exists($field, $adm->values)) {
                        $adm->values[$field] = $def_value;
                    }

                    $adm->assignProp($field, 'value', $adm->values[$field]);
                }
            break;
        }

        // creating mapping fields
        if ($adm->values['table'] && $file_uploaded) {
            // parse some data from uploaded file trying to determine information
            // about number of records and their names (XML only)
            $data_fields = array('-1' => '<none>');
            switch ($adm->values['format']) {
                case 'CSV':
                    $data_fields = array_merge($data_fields, csv_fields_list($file_path
                        , $adm->values['delimiter'], $adm->values['enclosure']
                        , $adm->values['header_row']));

                break;

                case 'XML':
                    $data_fields = array_merge($data_fields, xml_fields_list($file_path));
                break;
            }

            // show table fields
            // if data fields contain names than automapping is performed
            $tbl_fields = DbHelpers::get_tbl_fields($sql, $adm->dbc, $adm->values['table']);
            $i = 0;
            foreach ($tbl_fields as $field_name => $field_props) {
                $i++;
                // do not show primary key fields
                if ('PRI' == @$field_props['key']) continue;

                $sel_name = "field_$i";

                $fields[$sel_name] = "Field '$field_name'";
                $adm->assignProp($sel_name, 'type', 'select');
                $adm->assignProp($sel_name, 'list', $data_fields);

                if (('CSV' != $adm->values['format'] || $adm->values['header_row'])
                    && !in_array($field_num = array_search($field_name, $data_fields)
                    , array(null, false)))
                {
                    $adm->assignProp($sel_name, 'value', $field_num);
                    unset($data_fields[$field_num]);
                }
                else {
                    $adm->assignProp($sel_name, 'value', -1);
                }
            }
        }

        // setup data_file field
        if ($file_uploaded) {
	        $adm->assignProp('data_file', 'type', 'textinput');
	        $adm->assignProp('data_file', 'value', $file_name);

	        // save original file name and path to temporary file
	        // in uploaded_file hidden field
	        $adm->assignHidden('uploaded_file_path', $file_path);
	        $adm->assignHidden('uploaded_file_name', $file_name);

	        // change sumbit button text
	        $adm->general['button'] = $txtf->display('import_btn');
        }

        $adm->assignProp('format', 'value', $adm->values['format']);
	}
}

// ##############################################################
// ##############################################################
// DO NOT EDIT BELOW THESE LINES
// ##############################################################
// ##############################################################

$do = $_REQUEST["do"];

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);
$tpl->addDataItem("TITLE", $txtf->display("module_title"));

$adm = new Admin2($table);
$adm->assign("user", $user);
$adm->assign("language", $language);

external($adm, new sql(), $tpl);

$tpl->addDataItem("CONTENT", $adm->form($fields, '', '', '', 'add', 0, $field_groups, $fields_in_group));
echo $tpl->parse();
