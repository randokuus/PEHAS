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
$txtf = new Text($language2, "module_isic_chip_numbers");

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
    "button" => $txtf->display("export"),
    "max_entries" => 30,
    "sort" => "", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "school_id"  => $txtf->display("school"),
    "active"  => $txtf->display("active"),
    "kind_id"  => $txtf->display("kind"),
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

    $adm->assignProp("active", "type", "select");
    $adm->assignProp("active", "list", array("0" => $txtf->display("all"), "1" => $txt->display("yes"), "2" => $txt->display("no")));

    $adm->assignProp("kind_id", "type", "select");
    $adm->assignProp("kind_id", "list", array("0" => $txtf->display("all"), "1" => $txtf->display("regular"), "2" => $txtf->display("compound")));

	$adm->assignProp("school_id", "type", "select");
	$adm->assignExternal("school_id", "module_isic_school", "id", "name", " ORDER BY name", true);
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
        $sql = "SELECT * FROM `module_isic_card` WHERE `school_id` = " . $school_id;
        switch ($active) {
            case 1:
                $sql .= " AND `active` = 1";
            break;
            case 2:
                $sql .= " AND `active` = 0";
            break;
            default :
            break;
        }
        switch ($kind_id) {
            case 1:
                $sql .= " AND `kind_id` = 1";
            break;
            case 2:
                $sql .= " AND `kind_id` = 2";
            break;
            default :
            break;
        }
        $sql .= " ORDER BY `person_name_last`, `person_name_first`";
        $sq->query($adm->dbc, $sql);
        if ($sq->rows()) {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=isic_cards.csv");
            header("Pragma: ");
            header("Cache-control: ");

            while ($cards = $sq->nextrow()) {
                if (!$cars["person_class"] && $cards["type_id"] == 3) {
                    $t_class = "OP"; // teachers card
                } else {
                    $t_class = mb_convert_encoding($cards["person_class"], "Windows-1252", "UTF-8");
                }
                echo mb_convert_encoding($cards["person_name_first"], "Windows-1252", "UTF-8") . ";" . mb_convert_encoding($cards["person_name_last"], "Windows-1252", "UTF-8") . ";" . mb_convert_encoding($cards["person_number"], "Windows-1252", "UTF-8") . ";" . $cards["isic_number"] . ";" . $cards["chip_number"] . ";" . $t_class . "\n";
            }

            exit();
        }

        $do = "";
        $res = 1;
        $adm->db_write = true;
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

$result .= "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;