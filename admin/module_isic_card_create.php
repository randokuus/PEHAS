<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;
$id = 1;
$show = "modify";

//error_reporting(E_ALL);
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/IsicCommon.php");

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
$bind_user = $ses->getBindUser();
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
$txtf = new Text($language2, "module_isic_card_create");

// ##############################################################
// ##############################################################

$table = "module_isic_application"; // SQL table name to be administered

$idfield = "1"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
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
    "button" => $txtf->display("create"),
    "max_entries" => 30,
    "sort" => "", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "card_type"  => $txtf->display("card_type"),
    "school_id"  => $txtf->display("school"),
//  "title_row" => $txtf->display("title_row")
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
//  "file_delimiter"
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

function picUrl($pic) {
    $pic_url = "";
    if ($pic) {
        if (strpos($pic, "_thumb") !== false) {
            $full_pic = str_replace("_thumb", "", $pic);
            $thumb_pic = $pic;
        } else {
            $thumb_pic = str_replace(".", "_thumb.", $pic);
            $full_pic = $pic;
        }

        if (file_exists(SITE_PATH . $thumb_pic)) {
            $pic_url = SITE_URL . $thumb_pic;
        } else {
            $pic_url = SITE_URL . $full_pic;
        }
    } else {
        $pic_url = SITE_URL . "/img/nopic.gif";
    }

    return $pic_url;
}

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $show_editor, $txtf, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp("card_type", "type", "select");
    $adm->assignExternal("card_type", "module_isic_card_type", "module_isic_card_type.id",
        "CONCAT(module_isic_card_type.name, ' (', COUNT(*), ')') AS t_name",
        " , module_isic_application WHERE
            module_isic_application.type_id = module_isic_card_type.id AND
            module_isic_application.state_id = 5
            GROUP BY module_isic_card_type.id ORDER BY module_isic_card_type.name",
        true
    );

    $adm->assignProp("school_id", "type", "select");
    $adm->assignExternal("school_id", "module_isic_school", "module_isic_school.id",
        "CONCAT(module_isic_school.name, ' (', COUNT(*), ')') AS t_name",
        " , module_isic_application WHERE
            module_isic_application.school_id = module_isic_school.id AND
            module_isic_application.state_id = 5
            GROUP BY module_isic_school.id ORDER BY module_isic_school.name",
        true
    );
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
$sq2 = new sql;

/* DB writing part */

if ($do == "update" && $id) {
    $card_ids = array();
    if ($export) {
        foreach ($card as $ckey => $cval) {
            if ($cval == "on") {
                $card_ids[] = $ckey;
            }
        }
        if (sizeof($card_ids) == 0) {
            $card_ids[] = -1;
        }
    }

    $export_log = "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n";
    $export_log .= "<input type=\"hidden\" name=\"do\" value=\"update\">\n";
    $export_log .= "<input type=\"hidden\" name=\"export\" value=\"1\">\n";
    $export_log .= "<tr>\n";
    $export_log .= "<th>Row</th>\n";
    if (!$export) {
        $export_log .= "<th>Pic</th>\n";
    }
    $export_log .= "<th>Name</th>\n";
    $export_log .= "<th>Person code</th>\n";
    $export_log .= "<th>School</th>\n";
    $export_log .= "<th>Card type</th>\n";
    $export_log .= "<th>Structure Unit</th>\n";
    $export_log .= "<th>Create card?</th>\n";
    $export_log .= "</tr>\n";

    $isic = IsicCommon::getInstance();
    $card_list = $isic->exportApplicationArray($card_type, $school_id, $card_ids);
    if (sizeof($card_list) > 0) {
        for ($i = 0; $i < sizeof($card_list); $i++) {
            $export_log .= "<tr>\n";
            $export_log .= "<td>" . ($i + 1) . "</td>\n";
            if ($export) {
                $export_log .= "<td>" . $card_list[$i]["person_name_first"] . ' ' . $card_list[$i]["person_name_last"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["school_name"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["type_name"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_stru_unit"] . "</td>\n";

                $appl2card = $isic->createCardFromApplication($card_list[$i]["id"], $bind_user);
                switch ($appl2card) {
                    case 0:
                        $export_log .= "<td><b>Card was not created. Couldn't find application</b></td>\n";
                    break;
                    case -1:
                        $export_log .= "<td><b>Card was not created. Problems with serial number generation</b></td>\n";
                    break;
                    case -2:
                        $export_log .= "<td><b>Card was not created. Person name(s) too long for export</b></td>\n";
                        break;
                    default :
                        $export_log .= "<td>Created</td>\n";
                    break;
                }
            } else {
                $export_log .= "<td><img src=\"" . picUrl($card_list[$i]["pic"]) . "\"></td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_name_first"] . ' ' . $card_list[$i]["person_name_last"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["school_name"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["type_name"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_stru_unit"] . "</td>\n";
                $export_log .= "<td><input type=\"checkbox\" name=\"card[" . $card_list[$i]["id"] . "]\" checked=\"checked\"></td>\n";
            }
            $export_log .= "</tr>\n";
        }

        if (!$export) {
            $export_log .= "<tr>\n";
            $export_log .= "<td colspan=\"9\"><div class=\"buttonbar\"><button type=\"button\" onClick=\"this.form.submit();\"><img src=\"pic/button_accept.gif\" alt=\"OK!\" border=\"0\">Create cards from selected applications</button></div></td>\n";
            $export_log .= "</tr>\n";
        }
    } else {
        $export_log .= "<tr>\n";
        $export_log .= "<td>&nbsp;</td>\n";
        $export_log .= "<td colspan=\"9\">There were no applications to create cards from at this time ...</td>\n";
        $export_log .= "</tr>\n";
    }
    $export_log .= "</form>\n";
}
/* end DB writing part */

if ($show == "modify" && $id && $do != "update") {

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

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";
$result .= "<table class=\"datatable\" width=\"100%\">\n";
$result .= $export_log;
$result .= "</table>\n";

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;
