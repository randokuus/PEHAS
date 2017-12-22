<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;
$id = 1;
$show = "modify";

//error_reporting(E_ALL);
require_once("../class/common.php");
require_once("../class/config.php");
require_once(SITE_PATH . "/class/" . DB_TYPE . ".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/module.isic_card_export.class.php");
require_once(SITE_PATH . "/class/Isic/IsicDate.php");
require_once(SITE_PATH . "/tools/archive.php");
require(SITE_PATH . "/class/scp.class.php");

// ##############################################################

$path = SITE_PATH . "/cache/isic/";

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
$txtf = new Text($language2, "module_isic_card_export");

// ##############################################################
// ##############################################################

$table = "module_isic_card"; // SQL table name to be administered

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
    "button" => $txtf->display("export"),
    "max_entries" => 30,
    "sort" => "", // default sort to use
    "enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "card_type" => $txtf->display("card_type"),
    "school_id" => $txtf->display("school"),
//  "title_row" => $txtf->display("title_row")
);

$tabs = array(
    1 => $txt->display("modify")
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), "")
);

$fields_in_group = array();


/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array();

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

$filter_fields = array();

/* end display list part */

function picUrl($pic)
{
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

function isLastExportTooClose($minTimeDiff = 60)
{
    $exporter = new isic_card_export();
    $lastExportTime = strtotime($exporter->getLastExportTime());
    $curTime = time();
    return $curTime - $lastExportTime <= $minTimeDiff;
}

// If for example our table has references to another table (foreign key)
function external()
{
    global $adm, $show, $show_editor, $txtf, $txt, $group, $language, $id, $structure;
    $sq = new sql;

    $adm->assignProp("card_type", "type", "select");
    $adm->assignExternal(
        "card_type",
        "module_isic_card_type",
        "module_isic_card_type.id",
        "CONCAT(module_isic_card_type.name, ' (', COUNT(*), ')') AS t_name",
        " , module_isic_card 
        WHERE 
            module_isic_card.type_id = module_isic_card_type.id AND 
            module_isic_card.exported = '0000-00-00' AND 
            module_isic_card.confirm_admin = 1 
        GROUP BY 
            module_isic_card_type.id 
        ORDER BY 
            module_isic_card_type.name",
        true
    );

    $adm->assignProp("school_id", "type", "select");
    $adm->assignExternal(
        "school_id",
        "module_isic_school",
        "module_isic_school.id",
        "CONCAT(module_isic_school.name, ' (', COUNT(*), ')') AS t_name",
        " , module_isic_card 
        WHERE 
            module_isic_card.school_id = module_isic_school.id AND 
            module_isic_card.exported = '0000-00-00' AND 
            module_isic_card.confirm_admin = 1 
        GROUP BY 
            module_isic_school.id 
        ORDER BY 
            module_isic_school.name",
        true
    );

    if (isLastExportTooClose()) {
        $adm->displayOnly("card_type");
        $adm->displayOnly("school_id");
    }
}


// ##############################################################
// ##############################################################
/* DO NOT EDIT BELOW THESE LINES */
// ##############################################################
// ##############################################################

if ($max_entries && $max_entries <= 100) {
    $general["max_entries"] = $max_entries;
}

$tpl = new template;
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile($general["template_main"]);

$tpl->addDataItem("CONFIRMATION", $general["delete_confirmation"]);

$adm = new Admin($table);

$sq = new sql;
$sq2 = new sql;

/* DB writing part */
$lastExportTooClose = isLastExportTooClose();
if ($do == "update" && $id && !$lastExportTooClose) {
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
    $export_log .= "<th>ISIC Number</th>\n";
    $export_log .= "<th>Structure Unit</th>\n";
    $export_log .= "<th>Export?</th>\n";
    $export_log .= "</tr>\n";

    $isic = new isic_card_export();
    $card_list = $isic->exportCardsArray($card_type, $school_id, $card_ids);
    if (sizeof($card_list) > 0) {
        for ($i = 0; $i < sizeof($card_list); $i++) {
            $export_log .= "<tr>\n";
            $export_log .= "<td>" . ($i + 1) . "</td>\n";
            if ($export) {
                $export_log .= "<td>" . $card_list[$i]["person_name_first"] . " " . $card_list[$i]["person_name_last"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["isic_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_stru_unit"] . "</td>\n";
                $export_log .= "<td>Exported</td>\n";
            } else {
                $export_log .= "<td><img src=\"" . picUrl($card_list[$i]["pic"]) . "\"></td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_name_first"] . " " . $card_list[$i]["person_name_last"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["isic_number"] . "</td>\n";
                $export_log .= "<td>" . $card_list[$i]["person_stru_unit"] . "</td>\n";
                $errorFields = array();
                if ($isic->isRecordValid($card_list[$i], $errorFields)) {
                    $export_log .= "<td><input type=\"checkbox\" name=\"card[" . $card_list[$i]["id"] . "]\" checked=\"checked\"></td>\n";
                } else {
                    $export_log .= "<td><b>Fields (" . implode(', ', $errorFields) . ") are not valid!</b></td>\n";
                }
            }
            $export_log .= "</tr>\n";
        }

        if ($export) {
            $pic_names = array();
            $logo_names = array();
            $order_data = $isic->createOrder();
            $card_content = $isic->exportCards($pic_names, $card_ids, $logo_names, $order_data["order_id"]);

            $fname = $order_data["filename"];
            $out_fname = $fname . '.txt';
            $isic->saveFile($out_fname, ISIC_PATH, $card_content);

            for ($i = 0; $i < sizeof($pic_names); $i++) {
                $pic_names[$i] = SITE_PATH . $pic_names[$i];
            }

            for ($i = 0; $i < sizeof($logo_names); $i++) {
                $logo_names[$i] = SITE_PATH . $logo_names[$i];
            }

            // creating tar-archive
            $tar_fname = ISIC_PATH . $fname . ".tar";
            $tar = new tar_file($tar_fname);
            $tar->set_options(array('basedir' => ISIC_PATH, 'overwrite' => 1, 'storepaths' => 0));
            $tar->add_files(array(ISIC_PATH . $out_fname));
            $tar->add_files($pic_names);
            $tar->add_files($logo_names);
            $tar->create_archive();

            if (strpos(SITE_URL, "dev.") == false &&
                strpos(SITE_URL, "test.") == false) {
                $scp = new scp(HOST_FILE, ID_FILE, TARGET_HOSTNAME, TARGET_USERNAME);

                if (!$scp->upload($tar_fname, TARGET_PATH)) {
                    $export_log .= "<tr>\n";
                    $export_log .= "<td>&nbsp;</td>\n";
                    $export_log .= "<td colspan=\"5\">Errors occured while uploading file: " . $scp->getErrors() . "</td>\n";
                    $export_log .= "</tr>\n";
                }
            }

//            FileSystem::rmr(ISIC_PATH . $out_fname);
//            FileSystem::rmr(ISIC_PATH . $tar_fname);
//            FileSystem::rmr(ISIC_PATH . $zip_fname);
        }

        if (!$export) {
            $export_log .= "<tr>\n";
            $export_log .= "<td colspan=\"7\"><div class=\"buttonbar\"><button type=\"button\" onClick=\"this.form.submit();\"><img src=\"pic/button_accept.gif\" alt=\"OK!\" border=\"0\">Export selected cards</button></div></td>\n";
            $export_log .= "</tr>\n";
        }
    } else {
        $export_log .= "<tr>\n";
        $export_log .= "<td>&nbsp;</td>\n";
        $export_log .= "<td colspan=\"7\">There were no cards to export at this time ...</td>\n";
        $export_log .= "</tr>\n";
    }
    $export_log .= "</form>\n";
} else if ($lastExportTooClose) {
    $export_log .= "<tr>\n";
    $export_log .= "<td>&nbsp;</td>\n";
    $export_log .= "<td colspan=\"7\">" . $txtf->display("last_export_less_than_minute_ago") . "</td>\n";
    $export_log .= "</tr>\n";
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
while (list($key, $val) = each($tabs)) {
    $tpl->addDataItem("TABS.ID", $nr);
    $tpl->addDataItem("TABS.URL", "javascript:enableFieldset($nr, 'fieldset$key', '', " . sizeof($tabs) . ", " . sizeof($field_groups) . ");");
    $tpl->addDataItem("TABS.NAME", $val);
    if ($key == 1) {
        $tpl->addDataItem("TABS.CLASS", "class=\"active\"");
    }
    $nr++;
}

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(" . sizeof($field_groups) . ");</SCRIPT>\n";
$result .= "<table class=\"datatable\" width=\"100%\">\n";
$result .= $export_log;
$result .= "</table>\n";

$tpl->addDataItem("CONTENT", $result);

$result = $tpl->parse();
echo $result;