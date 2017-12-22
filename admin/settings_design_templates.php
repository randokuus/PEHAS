<?php
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
require_once(SITE_PATH . "/class/admin2.class.php");            // administration main object
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


// init systemlog object
$log = &SystemLog::instance($database);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

$perm = new Rights($group, $user, "root", true);

// permissions
$perm->Access (0, 0, "m", "");

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_templates");

if (!$GLOBALS["templates_".$language]) {
    $GLOBALS["templates_".$language] = $GLOBALS["templates_EN"];
}
if (!$GLOBALS["temp_desc_".$language]) {
    $GLOBALS["temp_desc_".$language] = $GLOBALS["temp_desc_EN"];
}

$sel_language = "EN";

/**
 * Filtering mode. if $filter is not empty, store this string in SESSION
 */
if (!is_null($filter)) {
    $filter = str_replace(' ', '_', $filter);
    $filter = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $filter);
    $filter = preg_replace('/\$|\.|%|@|&|�|�|�|�|�|�|�|�|\"|\'/', '', $filter);
    $filter = strtolower($filter);
    $_SESSION['admin']['settings_templates']['filter'] = $filter;
}
$filter = $_SESSION['admin']['settings_templates']['filter'];

if (false !== strpos($file, '..')) exit();

// ##############################################################
// ##############################################################

$table = ""; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main_module.html",
    "template_form" => "tmpl/admin_form.html",
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
    "max_entries" => 100,
    "sort" => "" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "design" => $txtf->display("design"),
    "name" => $txtf->display("name"),
    "type" => $txtf->display("type"),
    "desc" => $txtf->display("desc"),
    "content" => $txtf->display("content")
);

$tabs = array(
    1 => array($txtf->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txtf->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
    //$idfield => "ID", // if you want to display the ID as well,
    "design" => $txtf->display("design"),
    "name" => $txtf->display("name"),
    "desc" => $txtf->display("desc")
);

/* required fields */
$required = array(
//  "design",
    "name",
    "desc"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

$what = array();
$from = array();

//$where = "language = '$language'";
$filter_fields = array();

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $txtf, $txt, $group, $language, $sel_language, $id, $file, $structure, $sel_template;
    //$sq = new sql;

    $adm->displayOnly("design");
    $adm->assign("design", $GLOBALS["templates_".$sel_language][$sel_template][0]);

    $adm->assignProp("name", "type", "textinput");
    $adm->assignProp("name", "size", "40");
    $adm->assignProp("desc", "type", "textinput");
    $adm->assignProp("desc", "size", "40");
    $adm->assignProp("content", "type", "textfield");
    $adm->assignProp("content", "cols", "90");
    $adm->assignProp("content", "rows", "30");
    $adm->assignProp("content", "wrap", "off");

    //$adm->assignProp("name", "extra", $txtf->display("name_extra"));

    $adm->assignProp("type", "type", "select");
    $adm->assignProp("type", "list", array("1" => $txtf->display("type1"), "2" => $txtf->display("type2"), "3" => $txtf->display("type3"), "4" => $txtf->display("type4")));
    //$adm->assignProp("type", "list", array("1" => $txtf->display("type1"), "2" => $txtf->display("type2"), "3" => $txtf->display("type3"), "4" => $txtf->display("type4"), "5" => $txtf->display("type5"), "6" => $txtf->display("type6")));

//  if ($show == "modify" && ($id || $file))
    if (in_array($show, array('modify', 'add')) && ($id || $file))
    {
        $adm->displayOnly("name");
        $adm->displayOnly("type");
        $adm->assign("name", $GLOBALS["templates_".$sel_language][$sel_template][2][$id]);
        $adm->displayOnly("desc");
        $adm->assign("desc", $GLOBALS["temp_desc_".$sel_language][$sel_template][$id]);
        $adm->assign("content", "<__DUMMY__>");

        if (!$id) {
            if ($show == 'modify') {
                $adm->assign("name", $file);
            } else {
                $adm->assign("name", substr($file, 0, strrpos($file, '.html')));
            }
        }

        if (preg_match("/^content/i", $file)) {
            $adm->assign("type", $txtf->display("type1"));
        }
        else if (preg_match("/^print_content_/i", $file)) {
            $adm->assign("type", $txtf->display("type2"));
        }
        else if (preg_match("/^module_/i", $file)) {
            $adm->assign("type", $txtf->display("type3"));
        }
        else {
            $adm->assign("type", $txtf->display("type4"));
        }
        if (preg_match("/\.xml/i", $file)) {
            $adm->assign("type", $txtf->display("type5"));
        }
        else if (preg_match("/\.xsl/i", $file)) {
            $adm->assign("type", $txtf->display("type6"));
        }

        $adm->assignHidden("file", $file);
    }
}

// add lines to config file
function addToConfig ($lines, $lang, $temp, $id) {
    $handle = @fopen(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/config2.php", "r");
    $contents = @fread($handle, 204800);
    fclose($handle);
    if ($contents != "") {
        $handle = @fopen(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/config2.php", "w");

$lines = "/*BEGIN_TEMP_".$lang."_".$temp."_".$id."*/
" . $lines . "/*END_TEMP_".$lang."_".$temp."_".$id."*/
";

        $final = preg_replace("/\/\*INSERT_HERE\*\//", $lines . "\n/*INSERT_HERE*/", $contents);

        $status = @fwrite($handle, $final);
        @fclose($handle);
        if ($status == false) return false;
        else { return true; }
    }
    else {
        return false;
    }
}

// remove lines from config
function removeFromConfig ($lang, $temp, $id) {
    $handle = @fopen(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/config2.php", "r");
    $contents = @fread($handle, 204800);
    fclose($handle);
    if ($contents != "") {
        $handle = @fopen(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/config2.php", "w");

        $search = array ("'\/\*BEGIN_TEMP_".$lang."_".$temp."_".$id."\*\/.*\/\*END_TEMP_".$lang."_".$temp."_".$id."\*\/..'si"
        );

        $replace = array ("");

        $contents = preg_replace($search, $replace, $contents);
        $status = @fwrite($handle, $contents);

        @fclose($handle);
        if ($status == false) return false;
        else { return true; }
    }
    else {
        return false;
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

    $adm = new Admin2($table);

    $sq = new sql;

    $sq->query($adm->dbc, "SELECT `template` FROM `settings`");
    $data = $sq->nextrow();
    $sel_template = $data["template"];
    $sq->free();

    /* DB writing part */
    if ($do == "add") {


        $tempfile = str_replace(' ', '_', $name);
        $tempfile = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $tempfile);
        $tempfile = preg_replace('/\$|\.|%|@|&|�|�|�|�|�|�|�|�|\"|\'/', '', $tempfile);
        $tempfile = strtolower($tempfile);

        // if new file name is empty, then try again.
        if (!strlen($tempfile)) {
            $adm->values["name"] = "";
        }

        if ($type == 1) {
            $tempfile = "content_" . $tempfile;
            $tempext = ".html";
        }
        else if ($type == 2) {
            $tempfile = "print_content_" . $tempfile;
            $tempext = ".html";
        }
        else if ($type == 3) {
            $tempfile = "module_" . $tempfile;
            $tempext = ".html";
        }
        else if ($type == 4) {
            $tempext = ".html";
        }
        else if ($type == 5) {
            $tempext = ".xml";
        }
        else if ($type == 6) {
            $tempext = ".xsl";
        }

        if (file_exists(SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $tempfile . $tempext))
        {
            $adm->values["name"] = "";
        }

        if (sizeof($adm->checkRequired($required)) == 0)
        {

            $filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $tempfile . $tempext;
            $handle = @fopen ($filename, "w");
            if($status = @fwrite($handle, $content)){
                // save log about this action
                $log->log('settings_design_templates', 'File ' . substr($filename, strlen(SITE_PATH))
                    . ' created by ' . $GLOBALS['ses']->getUsername());

            }
            @fclose($handle);

            if ($status != false && $type == 1)
            {
                $number = 500;
                while (isset($GLOBALS["templates_".$sel_language][$sel_template][2][$number]))
                {
                    $number++;
                }
                $to_add = '$GLOBALS["templates_'.$sel_language.'"]'
                    . '['.$sel_template.'][2]['.$number.'] = "' . addslashes($tempfile) . '";';

                $to_add .= '$GLOBALS["temp_desc_'.$sel_language.'"]'
                    . '['.$sel_template.']['.$number.'] = "' . addslashes($desc) . '";';

                addToConfig ($to_add, $sel_language, $sel_template, $number);

                $GLOBALS["templates_".$sel_language][$sel_template][2][$number] = addslashes($tempfile);
                $GLOBALS["temp_desc_".$sel_language][$sel_template][$number] = addslashes($desc);

            }
        }
        else
        {
            $res = 99;
        }

        if ($res == 0)
        {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");
        }
        else
        {
            $adm->values["content"] = preg_replace("/<TPL_OBJECT/m", "<TPLOBJECT", $adm->values["content"]);
            $adm->values["content"] = preg_replace("/<\/TPL_OBJECT/m", "</TPLOBJECT", $adm->values["content"]);
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            //$adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "update" && ($id || $file))
    {

        $content = preg_replace("/\\\"/m", "\"", $content);
        $content = preg_replace("/<textarea\/>/m", "</textarea>", $content);

        //rename(SITE_PATH . "/" . $GLOBALS["directory"]["tmpl"] . "/" . $file, SITE_PATH . "/" . $GLOBALS["directory"]["tmpl"] . "/" . "backup".date("YmdHi")."_".$file);

        //$filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $GLOBALS["templates_".$sel_language][$sel_template][2][$id] . ".html";
        $filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $file;
        $handle = @fopen ($filename, "w+");
        if ($status = @fwrite($handle, $content)) {
            // save log about this action
            $log->log('settings_design_templates', 'File ' . substr($filename, strlen(SITE_PATH))
                . ' updated by ' . $GLOBALS['ses']->getUsername());
        }
        @fclose ($handle);
        $res = 0;

        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");
        }
        else
        {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "delete" && ($id || $file))
    {
        $filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $file;
        if (file_exists($filename) && @unlink($filename))
        {
            // save log about this action
            $log->log('settings_design_templates', 'File ' . substr($filename, strlen(SITE_PATH))
                . ' deleted by ' . $GLOBALS['ses']->getUsername());
        }

        if ($id)
        {
            $GLOBALS["temp_desc_".$sel_language][$sel_template][$id] = "";
            removeFromConfig($sel_language, $sel_template, $id);
        }

        $res = 0;

        if ($res == 0)
        {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");
        }
        else
        {
            $result = $general["error"];
        }
    }
    /* end DB writing part */

    if ($show == "add")
    {
        //if ($copyto != "")    $adm->fillValues($table, $idfield, $copyto);
        //$adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);

    }
    else if ($show == "modify" && ($id || $file))
    {
        //$adm->fillValues($table, $idfield, $id);
        //$adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);

    }
    else if (!$res || $res == 0)
    {
        external();

        // page templates
        $pos = 0;
        $listdata = array();
        while (list($key, $val) = each($GLOBALS["templates_".$sel_language][$sel_template][2]))
        {
            if ($GLOBALS["temp_desc_".$sel_language][$sel_template][$key] != ""
                && file_exists(SITE_PATH . "/"
                    . $GLOBALS["templates_" . $sel_language][$sel_template][1] . "/"
                    . $GLOBALS["templates_" . $sel_language][$sel_template][2][$key] . ".html"))
            {
                if ($GLOBALS["temp_desc_".$language][$sel_template][$key] != "")
                {
                    $desc = $GLOBALS["temp_desc_".$language][$sel_template][$key];
                }
                else
                {
                    $desc = $GLOBALS["temp_desc_".$sel_language][$sel_template][$key];
                }

                $listdata[$pos] = array("design" => $GLOBALS["templates_".$sel_language][$sel_template][0]
                    , "name" => $val
                    // small hack, copyto works only with ID... but filename needed.
                    , "id" => $key.'&file=' . $val . '.html'
                    , "file" => $val.".html", "desc" => $desc
                );

                $pos++;
            }
        }

        // all other templates
        $ar = array();
        if ($dir = @opendir(SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/"))
        {
            // get each file in given directory.
            while (($fil = readdir($dir)) !== false)
            {
                //if (!is_dir(SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $fil) && $fil != "." && $fil != ".." && (eregi("\.htm", $fil) || eregi("\.xsl", $fil) || eregi("\.xml", $fil)) && !eregi("^content", $fil) && !eregi("^backup", $fil)) {

                if (!is_dir(SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $fil)
                        && $fil != "." && $fil != ".."
                        && false !== strpos(strtolower($fil), '.htm')
                        && !preg_match("/^content/i", $fil)
                        && !preg_match("/^backup/i", $fil))
                {
                    $name = substr($fil, 0, strrpos($fil,'.'));
                    $type = substr($fil, (strrpos($fil,'.')+1));
                    $ar[$fil] = $fil;
                }
            }
            closedir($dir);
        }

        ksort($ar);
        reset($ar);

        // add additional files into  $listdata array.
        while (list($key, $val) = each($ar))
        {
            $listdata[$pos] = array(
                  "design" => $GLOBALS["templates_" . $sel_language][$sel_template][0]
                , "name" => $val
                // small hack. copyto works only with (int)$id
                , "id" => "&file=" . $key
                , "file" => $key, "desc" => "&nbsp;"
            );
            $pos++;
        }

        /**
         * if filter is set, then sort files by file name using $filter.
         * @author Priit Pold
         * @since 2007.02.23
         */
        if ($filter != '') {
            $_listdata = array();

            foreach ($listdata as $key=> $val)
            {
                // if file name contains $filter string, then hightlihgt it
                // and add this file into new list of files.
                if (strpos($val['name'], $filter) !== FALSE)
                {
                    $val['name'] = str_replace($filter
                        , '<span style="color:#FF1B1B;">' . $filter . '</span>', $val['name']
                    );
                    $_listdata[] = $val;
                }
            }
            $listdata = $_listdata;
            unset($_listdata);
        }

        $result .= $adm->show($disp_fields, $listdata, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }

if ($show == "add" || ($do == "add" && is_array($res)))
{
    $tpl->addDataItem("TITLE", $txtf->display("add"));
    $active_tab = 1;

}
else
{
    $tpl->addDataItem("TITLE", $txtf->display("modify"));
    $active_tab = 2;

}

$nr = 1;
while(list($key, $val) = each($tabs))
{
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

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit("
    . sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

// if file is set and $show is one of 'modify' or 'add'
// then change/copy content of given file.
if (in_array($show, array('modify', 'add')) && $file)
{

    //$filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $GLOBALS["templates_".$sel_language][$sel_template][2][$id] . ".html";

    $filename = SITE_PATH . "/" . $GLOBALS["templates_".$sel_language][$sel_template][1] . "/" . $file;
    $handle   = @fopen ($filename, "r");
    $content  = @fread ($handle, 100000);
    fclose ($handle);

    $content  = preg_replace("/\"/m", "\"", $content);
    $content  = preg_replace("/<\/textarea>/m", "<textarea/>", $content);

    $result   = $tpl->parse();
    $result   = preg_replace("/<__DUMMY__>/i", $content, $result);

}
else
{
    $result = $tpl->parse();
    $result = preg_replace("/<TPLOBJECT/m", "<TPL_OBJECT", $result);
    $result = preg_replace("/<\/TPLOBJECT/m", "</TPL_OBJECT", $result);
}

echo $result;