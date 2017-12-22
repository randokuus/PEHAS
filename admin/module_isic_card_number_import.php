<?php
$id = 1;
$show = "modify";
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
$txtf = new Text($language2, "module_isic_card_number_import");

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
    "card_type"  => $txtf->display("card_type"),
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


function saveIsicNumber ($card_type, $isic_number) {
    global $adm;
    $sq = new sql;
    $t_isic_number = str_replace(" ", "", $isic_number);

    $sql = "SELECT * FROM `module_isic_card_number` WHERE `card_number` = '" . mysql_escape_string($t_isic_number) . "'";
    $sq->query($adm->dbc, $sql);
    if ($sq->nextrow()) {
        return "<b>Not Imported.</b> Number already exists ...";
    } else {
        $sql = "INSERT INTO `module_isic_card_number` (`card_type`, `entrydate`, `card_number`, `reserved`) VALUES (
            '" . mysql_escape_string($card_type) . "',
            NOW(),
            '" . mysql_escape_string($t_isic_number) . "',
            '0'
        )";
        $sq->query($adm->dbc, $sql);
        if ($sq->insertId()) {
            return "Imported";
        }
    }
    return "<b>Not imported.</b> Unknown error.";
}

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $show_editor, $txtf, $txtg, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp("source_file", "type", "file");
	$adm->assignProp("source_file", "extra", $txtf->display("source_file_extra"));

	$adm->assignProp("card_type", "type", "select");
	$adm->assignExternal("card_type", "module_isic_card_type", "id", "name", " WHERE number_type = 1 ORDER BY name", false);
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

    // Site settings
    $sq->query($adm->dbc, "SELECT * FROM settings");
    $data_settings = $sq->nextrow();
    $sq->free();

    $adm->assign("lastmod", date("Y-m-d H:i:s"));
    $adm->assign("user", $user);

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
                    $t_line = fgetcsv($fp, 1000, ",");
                    if ($t_line[0]) {
                        $line_count++;
                        $res = saveIsicNumber($card_type, $t_line[0]);
                        $import_log .= "<tr><td>" . $line_count . ".</td><td>" . $t_line[0] . "</td><td>" . $res . "</td></tr>\n";
                    }
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