<?php
set_time_limit(0);
$id = 1;
$show = "modify";
$errorMessages = array(
    'elements' => 'Not enough elements in line',
    'name_first' => 'First name invalid',
    'name_last' => 'Last name invalid',
    'birthday' => 'Birthday invalid',
    'person_number' => 'Person number invalid',
    'school_name' => 'School name invalid',
    'school_ehis_code' => 'School EHIS code invalid',
    'expiration' => 'Expiration date invalid',
    'isic_number' => 'ISIC number invalid',
    'isic_number_exists' => 'Card with this ISIC number already exists',
    'email' => 'E-mail invalid',
    'card_type' => 'Card type invalid',
);
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicDB.php");
require_once(SITE_PATH . "/class/Isic/IsicCardValidator.php");
require_once(SITE_PATH . "/class/IsicCardImporter.php");
require_once(SITE_PATH . "/class/IsicEncoding.php");

// ##############################################################

// init session object
$ses = new Session();

// create database instance
// using database connection id from Session instance
$sql = new sql();
$sql->con = $ses->dbc;
$database = new Database($sql);
load_site_settings($database);
unset($sql);

$logged = $ses->returnID();
$user = $ses->returnUser();
$group = $ses->group;

if (!$logged) {
    echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
    echo '<body onLoad= "top.document.location=\'login.php\'">';
    exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "module", true);

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "module_isic_card_import");

// ##############################################################
// ##############################################################

$table = ""; // SQL table name to be administered

$idfield = "1"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["debug"],
    "template_main" => "tmpl/admin_main.html",
    "template_form" => "tmpl/admin_form1.html",
    "template_list" => "tmpl/admin_list.html",
    "add_text" => $txt->display("add_text"),
    "modify_text" => $txt->display("modify_text"),
    "delete_text" => $txt->display("delete_text"),
    "required_error" => $txt->display("required_error"),
    "delete_confirmation" => $txt->display("delete_confirmation"),
    "backtolist" => $txt->display("backtolist"),
    "current" => $txt->display("current"),
    "error" => $txt->display("error"),
    "filter" => $txt->display("filter"),
    "display" => $txt->display("display"),
    "display1" => $txt->display("display1"),
    "prev" => $txt->display("prev"),
    "next" => $txt->display("next"),
    "pages" => $txt->display("pages"),
    "button" => $txt->display("button"),
    "max_entries" => 30,
    "sort" => "", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "source_file"  => $txtf->display("source_file"),
);

$tabs = array(
    1 => $txt->display("modify")
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), "")
);

$fields_in_group = array(
);


/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    $idfield => "ID" // if you want to display the ID as well
);

/* required fields */
$required = array(
//  "source_file",
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

    $what = array(
        "$table.*"
    );
    $from = array(
        $table
    );

    //$where = "$table.structure = '$structure' AND language = '$language'";

    $filter_fields = array(
    );

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $show_editor, $txtf, $txtg, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp("source_file", "type", "file");
    $adm->assignProp("source_file", "extra", $txtf->display("source_file_format"));
}

function getErrorMessageById($id) {
    global $errorMessages;
    if (array_key_exists($id, $errorMessages)) {
        return $errorMessages[$id];
    }
    return 'Unknown message: ' . $id;
}

function getErrorMessagesByIdList($errorList) {
    $messages = array();
    foreach ($errorList as $errorData) {
        $messages[] = getErrorMessageById($errorData[0]) . ' (' . $errorData[1] . ')';
    }
    return $messages;
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

    $adm = new Admin($table);

    $sq = new sql;

    $cardValidator = new IsicCardValidator();
    $isicDbSchools = IsicDB::factory('Schools');
    $cardValidator->setIsicDbSchools($isicDbSchools);
    $isicDbCardTypes = IsicDB::factory('CardTypes');
    $cardValidator->setIsicDbCardTypes($isicDbCardTypes);
    $isicDbCards = IsicDB::factory('Cards');
    $cardValidator->setIsicDbCards($isicDbCards);
    $cardImporter = new IsicCardImporter();

    /* DB writing part */
    if ($do == "update" && $id) {
        // permissions
        $perm->Access (0, 0, "a", "isic");

        if ($_FILES["source_file"]["tmp_name"] && $_FILES["source_file"]["size"] > 0) {

            $import_log = "";

            $filename = $_FILES["source_file"]["tmp_name"];
            if ($fp = fopen($filename, "rb")) {
                $line_count = 0;
                while (!feof($fp)) {
                    $t_line = IsicEncoding::convertArrayEncoding(fgetcsv($fp, 1000, ","));
                    echo "<!-- " . $t_line . " -->\n";
                    $line_count++;
                    $errorList = false;
                    if ($cardValidator->isValidLine($t_line)) {
                        $cardId = $cardImporter->saveCardData($t_line);
                        if (!$cardId) {
                            $errorList[] = 'Could not create card record for some reason ...';
                        }
                    } else {
                        $errorList = $cardValidator->getErrors();
                    }
                    if ($errorList) {
                        $importMessage = '<b>' . implode('<br />', getErrorMessagesByIdList($errorList)) . '</b>';
                    } else {
                        $importMessage = "Imported: " . $cardId;
                    }
                    $import_log .= "<tr><td>" . $line_count . ".</td><td>" . implode(', ', $t_line) . "</td><td>" . $importMessage . "</td></tr>\n";
                }

                fclose($fp);
                $do = "";
                $res = 1;
                $adm->db_write = true;
            } else {
                $import_log .= "<tr><td>Could not open file: " . $_FILES["source_file"]["tmp_name"] . "</td></tr>\n";
            }

            //echo $sql . "<br>";
           //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "<br />\n";
        } else {
            $adm->badfields[] = "source_file";
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            //$adm->types();
            external();
            $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    /* end DB writing part */

    if ($show == "modify" && $id && $do != "update") {

        // permissions
        $perm->Access (0, 0, "m", "isic");

        //$adm->fillValues($table, $idfield, $id);
        //$adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_tyyp, $filter, "update", $id, $field_groups, $fields_in_group);
    }


$tpl->addDataItem("TITLE", $txtf->display("module_title"));
$active_tab = 1;

$nr = 1;
while(list($key, $val) = each($tabs)) {
    $tpl->addDataItem("TABS.ID", $nr);
    $tpl->addDataItem("TABS.URL", "javascript:enableFieldset($nr, 'fieldset$key', '', ".sizeof($tabs).", ".sizeof($field_groups).");");
    $tpl->addDataItem("TABS.NAME", $val);
    if ($key == 1) {
        $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
    }
    $nr++;
}

if ($import_log) {
    $result .= "<table class=\"datatable\">" . $import_log . "</table>\n";
}
$result .= "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;