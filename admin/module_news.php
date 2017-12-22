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
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/JsonEncoder.php");
require_once(SITE_PATH . "/class/template_helpers.php");

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
$txtf = new Text($language2, "module_news");
$trf = new Text($language2, 'admin_content');
$path_parts = parse_url(SITE_URL);
$engine_url = $path_parts['path'];
if (substr($engine_url,0,1) != "/") $engine_url = "/" . $engine_url;
if (substr($engine_url,-1) != "/") $engine_url = $engine_url . "/";

// ##############################################################
// ##############################################################

$table = "module_news"; // SQL table name to be administered

$idfield = "id"; // name of the id field (unique field, usually simply 'id')

// general parameters (templates, messages etc.)
$general = array(
    "debug" => $GLOBALS["modera_debug"],
    "template_main" => "tmpl/admin_main_module.html",
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
    "max_entries" => 50,
    "never_expires" => $trf->display('never_expires'),
    "sort" => "entrydate DESC" // default sort to use
    //"enctype" => "enctype=\"multipart/form-data\""
);

/* the fields in the table */
$fields = array(
    "entrydate" => $txtf->display("entrydate"),
    "publishing_date" => $txtf->display("publishing_date"),
    "expiration_date" => $txtf->display("expiration_date"),
    "ngroup" => $txtf->display("ngroup"),
    "title"  => $txtf->display("title"),
    "author"  => $txtf->display("author"),
    "lead" => $txtf->display("lead"),
    "content" => $txtf->display("content"),
    "pic" => $txtf->display("pic")
);

$tabs = array(
    1 => array($txt->display("add"), $_SERVER["PHP_SELF"]."?show=add"),
    2 => array($txt->display("modify"), $_SERVER["PHP_SELF"])
);

$field_groups = array(
    1 => array($txt->display("fieldset1"), ""),
);

$fields_in_group = array();

/* the fields that we want to update (do not include primary key (id) here) */
$upd_fields = array(
    "language",
    "entrydate",
    "publishing_date",
    "expiration_date",
    "ngroup",
    "title",
    "author",
    "lead",
    "content",
    "pic"
);

/* the fields(associations) to display in the list */
$disp_fields = array(
    "listnumber" => $txt->display("nr"), // listnumber displays the number of current row starting from 1
//    $idfield => "ID", // if you want to display the ID as well,
    "entrydate" => $txtf->display("entrydate"),
    "publishing_date" => $txtf->display("publishing_date"),
    "expiration_date" => $txtf->display("expiration_date"),
    "ngroup" => $txtf->display("ngroup"),
    "title" => $txtf->display("title"),
    "lead" => $txtf->display("lead")
);

/* required fields */
$required = array(
    "entrydate",
    "title"
 );

 /* To construct the main list query SELECT what from where / also which fields to include in the Filter command*/

     $what = array(
        "$table.*",
        "if($table.publishing_date, $table.publishing_date, $table.entrydate) as publishing_date",
        "if($table.expiration_date, $table.expiration_date, '$general[never_expires]') as expiration_date",
        "module_news_groups.name as group_name"
    );
    $from = array(
        $table,
        "LEFT JOIN module_news_groups ON $table.ngroup = module_news_groups.id"
    );

    $where = "$table.language = '$language'";

    $filter_fields = array(
        "$table.entrydate",
        "publishing_date",
        "expiration_date",
        "$table.title",
        "$table.author",
        "$table.content",
        "module_news_groups.name"
    );



function getFuncPreviewPages($jsonar){
    global $txtf;
    $str = "
<script>
    function refreshPreviewPages(){

            var previewPages    = document.forms['vorm'].elements['news_content_id'];
            var selectedGroup   = document.forms['vorm'].elements['ngroup'];
            var previewButton    = document.forms['vorm'].elements['extra_buttons_preview'];
            var selectedGroupId = selectedGroup[selectedGroup.selectedIndex].value;
            var articleholder   = " . JsonEncoder::encode($jsonar) . "

            var lengthpreview   = previewPages.options.length;
            for(var i=0; i<lengthpreview; i++){
                previewPages.options[i] = null;
            }
            previewButton.disabled = true;

            if((articleholder[selectedGroupId] == undefined || articleholder[selectedGroupId].length == 0)
                && (articleholder[0] == undefined || articleholder[0].length == 0))
            {
                previewPages.options[0] = new Option('" . $txtf->display('no_preview') . "',0);
                previewPages.disabled = true;
                return;
            }

            try{
                var i=0;
                previewPages.options[i] = new Option('" . $txtf->display('select_preview_content') . "','-');
                for(key in articleholder[selectedGroupId]){
                    i++;
                    previewPages.options[i] = new Option(articleholder[selectedGroupId][key],key);
                }

                if (selectedGroupId != 0) {
                    for(key in articleholder[0]){
                        i++;
                        previewPages.options[i] = new Option(articleholder[0][key],key);
                    }
                }
                previewPages.disabled = false;
            } catch(error){}
   }
   refreshPreviewPages();
</script>\n";

    return $str;
}

 /* end display list part */

// If for example our table has references to another table (foreign key)

function external() {
    global $adm, $show, $database, $trf, $txtf, $txt, $group, $language, $id, $structure;
    //$sq = new sql;

    if ($show == "add") {
        $adm->assign("entrydate", date("Y-m-d H:i:s"));
    }

    // additional fields for expiration date.
    $never_expires_checked = '';
    $never_expires_extra = '';
    $current_date = $adm->fields["expiration_date"]["value"];
    $txt_never_expires = $trf->display('never_expires');

    // on save, update and add, check never_expires flag.
    // if it is on, then set expiration_date to ''
    if (('add' == $do || 'update' == $do) && $adm->values['never_expires']) {
        $adm->fields['expiration_date']['value'] = '';
        $adm->values['expiration_date'] = '';
    }

    if ((int)$adm->fields["publishing_date"]["value"] <= 0) {
            $adm->fields["publishing_date"]["value"] = date('Y-m-d H:i:s');
    }

    // For form, if expiration_date is null or 0000-00-00....
    // then set never_expires flag to ON and set expiration_date = ''
    if ((int)$adm->fields["expiration_date"]["value"] <= 0) {
       $adm->fields["expiration_date"]["value"] = '';
       $never_expires_checked = 'checked="checked"';
       $current_date = date('Y-m-d H:i:s');
    }

    $never_expires_extra = " <input type='checkbox' id='never_expires' name='never_expires' value='1' style='vertical-align:middle;'"
            . "onChange=\"javascript:expField('never_expires');\" "
            . "onClick=\"javascript:expField('never_expires');\"$never_expires_checked /> $txt_never_expires";

    $never_expires_extra .= <<<EOB
            <script type='text/javascript' language='javascript'>
            var expiration_date_val = '{$current_date}';
            var last_state = 2;
            expField = function(chk_box){
                var el = document.getElementById(chk_box);
                if (!el || el == '' || el == 'undefined') return;
                if (el.checked) {
                    if (last_state != 1 && document.forms[0].elements['expiration_date'].value.length > 0)
                    {
                        expiration_date_val = document.forms[0].elements['expiration_date'].value;
                        document.forms[0].elements['expiration_date'].style.color = 'gray';
                    }
                    document.forms[0].elements['expiration_date'].value = '{$txt_never_expires}';
                    document.forms[0].elements['expiration_date'].disabled = true;
                    last_state = 1;
                } else {
                    if (last_state != 0 && expiration_date_val.length > 0) {
                        document.forms[0].elements['expiration_date'].value = expiration_date_val;
                        document.forms[0].elements['expiration_date'].style.color = 'black';
                    }
                    document.forms[0].elements['expiration_date'].disabled = false;
                    last_state = 0;
                }
            }
            expField('never_expires');
            </script>
EOB;

    $adm->assignProp("expiration_date", "extra", $never_expires_extra);

    $adm->assignProp("content", "type", "nothing");
    $adm->assignProp("lead", "rows", "3");
    $adm->assignProp("lead", "cols", "60");
    $adm->displayOnly("content");
    $adm->assign("content", "<iframe id=\"contentFreim\" name=\"contentFreim\" src=\"editor/editor.php?id=$id&type=news&rnd=".randomNumber()."\" WIDTH=100% HEIGHT=350 marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" frameborder=\"0\">
    </iframe>");

    $adm->assignProp("ngroup", "type", "select");
    $adm->assignExternal("ngroup", "module_news_groups", "id", "name", "ORDER BY name ASC", true);


    $tpls_with_news_module = templateHelpers::findTemplatesWithModule($language, 'news');
    $tpls_with_news_module = array_unique($tpls_with_news_module);

    $_select .= '<select name="news_content_id" onChange="var exbd = ' .
       'document.forms[\'vorm\'].elements[\'extra_buttons_preview\']; ' .
       'if(this.value!=\'-\'){exbd.disabled=false;}else{exbd.disabled=true;}">' .
       '<option value="-">- '.$txtf->display("select_preview_content").' -</option></select>';

    $jsonar = array();

    if ($tpls_with_news_module && count($tpls_with_news_module)) {
        $result = &$database->query('
            SELECT
                `title`, `content`, `module`
            FROM
                `content`
            WHERE
                `language` = ? AND `template` IN (?@)
            ORDER BY
                `content` ASC
            ', $language, $tpls_with_news_module);

        while ($_data = $result->fetch_assoc()) {
            $ar = split(";", $_data['module']);
            for ($c = 0; $c < sizeof($ar); $c++) {
                $a = split("=", $ar[$c]);
                if($a[0] == 'news' && !empty($a[1])
                   && isset($adm->fields['ngroup']['list'][$a[1]]))
                {
                    $jsonar[$a[1]][$_data["content"]] = htmlspecialchars($_data["title"]);
                    break;
                } elseif ($a[0] == 'news' && empty($a[1])) {
                    $jsonar[0][$_data["content"]] = htmlspecialchars($_data["title"]);
                    break;
                }
            }
        }
    }
    $_select .= getFuncPreviewPages($jsonar);

    // getting all users to testing preview
    $result = &$database->query("SELECT `user`, `username` FROM `module_user_users`"
                                . " WHERE `active` = 1 ORDER BY `username`");

    if ($result && $result->num_rows()) {
        $_select .= "<select name=\"preview_user_id\">";
        $_select .= '<option value="-">- '.$trf->display("select_preview_user").' -</option>';

        while($row = $result->fetch_assoc()) {
            $_select .= "<option value=\"$row[user]\">" . htmlspecialchars($row["username"]) . "</option>";
        }
        $_select .= '</select>';
    }

    $adm->general["extra_buttons"] = array(
        "1" => array(
            $trf->display('preview'),
            "javascript: previewContent();\" disabled=\"true\" id=\"extra_buttons_preview",
            "pic/button_preview.gif",
            $_select,
        )
    );
    $adm->assignProp("ngroup", "java", "onChange=\"refreshPreviewPages()\"");

        $adm->displayButtons("pic");
        //$adm->displayOnly("pic");
        $adm->assignProp("pic","type","onlyhidden");
        $prod_image = $adm->fields["pic"]["value"];
        //$adm->assign("pic", "");
        if ($prod_image != "") {
            $adm->assignProp("pic", "extra", "
            <table border=0 cellpadding=0 cellspacing=0>
            <tr valign=top><td><div align=\"left\" id=\"newspic\"><img src=\"" . $prod_image . "\" border=0></div></td>
            <td>&nbsp;&nbsp;</td>
            <td><button type=button onClick=\"newWindow('editor/Inc/insimage1.php',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_choose"))."</button>
            <button type=button onClick=\"javascript:clearPic();\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_del"))."</button>
            </td></tr></table>");
        }
        else {
            $adm->assignProp("pic", "extra", "
            <table border=0 cellpadding=0 cellspacing=0>
            <tr valign=top><td><div align=\"left\" id=\"newspic\">&nbsp;</div></td>
            <td>&nbsp;&nbsp;</td>
            <td><button type=button onClick=\"newWindow('editor/Inc/insimage1.php',660,350);\"><img src=\"pic/button_accept.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_choose"))."</button>
            <button type=button onClick=\"javascript:clearPic();\"><img src=\"pic/button_decline.gif\" alt=\"\" border=\"0\">".str_replace("+", " ", $txtf->display("pic_del"))."</button>
            </td></tr></table>");
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

        /*$_POST["content"] = preg_replace("/<\/?(HTML|HEAD|TITLE|BODY)>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<\/?(html|head|title|body)>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<META[^>]*>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<meta[^>]*>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<link rel[^>]*>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<BODY[^>]*>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<body[^>]*>\n?/", "", $_POST["content"]);
        $_POST["content"] = preg_replace("/<!DOCTYPE[^>]*>\n?/", "", $_POST["content"]);
        //$_POST["content"] = preg_replace("/'/", "&lsquo;", $_POST["content"]);
        $_POST["content"] = preg_replace("/\\\\'/m","'", $_POST["content"]);
        $_POST["content"] = trim($_POST["content"]);        */
        $_POST["content"]=stripslashes($_POST["content"]);//remove slashes (/)
        $_POST["content"] = str_replace("src=\"../../","src=\"".$engine_url, $_POST["content"]);
        $_POST["content"] = str_replace("href=\"../../","href=\"".$engine_url, $_POST["content"]);
        $_POST["content"] = str_replace("src=\"".SITE_URL."/","src=\"".$engine_url, $_POST["content"]);
        $_POST["content"] = str_replace("href=\"".SITE_URL."/","href=\"".$engine_url, $_POST["content"]);


    $adm = new Admin($table);

    $sq = new sql;

    //$adm->assign("lastmod", date("Y-m-d H:i:s"));
    //$adm->assign("user", $user);
    $adm->assign("language", $language);

    /* DB writing part */
    if ($do == "add") {

        // permissions
        $perm->Access (0, 0, "a", "news");

        $res = $adm->add($table, $required, $idfield);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["add_text"] . "')\"");

            // clear cache
            clearCacheFiles("tpl_news", "");

         }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "update" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "news");

        $res = $adm->modify($table, $upd_fields, $required, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["modify_text"] . "')\"");

            // clear cache
            clearCacheFiles("tpl_news", "");

        }
        else {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["required_error"] . "')\"");
            $adm->getValues();
            $adm->types();
            external();
            $result .= $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        }
    }
    else if ($do == "delete" && $id) {

        // permissions
        $perm->Access (0, $id, "d", "news");

        $res = $adm->delete($table, $idfield, $id);
        if ($res == 0) {
            $tpl->addDataItem("NOTICE", "onLoad=\"notice('" . $general["delete_text"] . "')\"");

            // clear cache
            clearCacheFiles("tpl_news", "");

        }
        else { $result = $general["error"]; }
    }
    /* end DB writing part */

    if ($show == "add") {

        // permissions
        $perm->Access (0, 0, "a", "news");

        if ($copyto != "")  $adm->fillValues($table, $idfield, $copyto);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "add", $id, $field_groups, $fields_in_group);
    }
    else if ($show == "modify" && $id) {

        // permissions
        $perm->Access (0, $id, "m", "news");

        $adm->fillValues($table, $idfield, $id);
        $adm->types();
        external();
        $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
    }
    else if (!$res || $res == 0) {
        // permissions
        $perm->Access (0, 0, "m", "news");

        if($submit_to){
            $adm->fillValues($table, $idfield, $id);
            $adm->types();
            external();
            $result = $adm->form($fields, $sort, $sort_type, $filter, "update", $id, $field_groups, $fields_in_group);
        }else{
        external();
        $result .= $adm->show($disp_fields, $what, $from, $where, $start, $sort, $sort_type, $filter, $filter_fields, $idfield);
    }
    }

if ($show == "add" || ($do == "add" && is_array($res))) {
    $tpl->addDataItem("TITLE", $txtf->display("module_title"));
    $active_tab = 1;
}
else {
    $tpl->addDataItem("TITLE", $txtf->display("module_title"));
    $active_tab = 2;
}

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

$result = $result . "<SCRIPT TYPE=\"text/javascript\" LANGUAGE=\"JavaScript\">fieldsetInit(".sizeof($field_groups).");</SCRIPT>\n";

$tpl->addDataItem("CONTENT", $result);

echo $tpl->parse();
