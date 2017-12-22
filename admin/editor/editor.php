<?php
require_once("../../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/Versioning.php");
require_once(SITE_PATH . "/class/module.widgets.class.php");

// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

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
    echo '<body onLoad= "top.document.location=\'../login.php\'">';
exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

//if ($language2) { include(SITE_PATH . "/class/admin_" . $language2 . ".php"); }

// init Text object for this page
//$txt = new Text($language2, "admin_general");
//$txtf = new Text($language2, "admin_content");

$db = new DB;
$db->connect();
$sq = new sql;

switch ($node_type) {
    case "trash":
        $table = "content_trash";
        break;

    case "template":
        $table = "content_templates";
        break;

    case "content":
    default:
        $node_type = 'content';
        $table = "content";
}

// ##############################################################

/* GET parameters for Editor

1. css file
2. module
3. id
4. type  (info, template, newsletter, news, messages)
5. table
6. field
7. full_source

*/

// backward compat.
if (!isset($_GET["module"]) && in_array($_GET['type'], array('newsletter', 'news', 'messages'))) {
    $_GET["module"] = $_GET["type"];
}

// check access

if (isset($_GET["module"])) {
    // perm
    $perm = new Rights($group, $user, "module", true);
    if (isset($_GET["id"])) {
        $perm->Access (0, 0, "m", $_GET["module"]);
    }
    else {
        $perm->Access (0, 0, "a", $_GET["module"]);
    }
}
else {
    // root only
    if ($_GET["type"] == "info" || $_GET["type"] == "template") {
        // perm
        $perm = new Rights($group, $user, "root", true);
        $perm->Access (0, 0, "m", "");

    }
    else {
        // perm
        $perm = new Rights($group, $user, $node_type, true);
        $perm->Access ('', $_GET["id"], "m", "");
    }
}

// determine css parameter for external css files
// MOVED TO editorcss.php, since Moz editor will only accept external css
// from editor path or below (Not above nor full path)
// reason: unknown

$full_source = false;

$purl = parse_url(SITE_URL);
$path = $purl["path"];

if (isset($_GET["css"])) {
    if (ereg("\.\.", $_GET["css"])) exit;
    if (@file_exists(SITE_PATH . "/" . addslashes($_GET["css"]))) {
        $stylesheet = $path . "/" . $_GET["css"];
    }
}
else {
    if (@file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/style_admin.css")) {
        $stylesheet = $path . "/" . $GLOBALS["directory"]["img"] . "/style_admin.css";
    }
    else {
        $stylesheet = $path . "/" . $GLOBALS["directory"]["img"] . "/style.css";
    }
}

if ($_GET["module"] == "newsletter") {
    if (@file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["img"] . "/style_newsletter.css")) {
        $stylesheet = $path . "/" . $GLOBALS["directory"]["img"] . "/style_newsletter.css";
    }
    $full_source = true;
}


// ##############################################################

// Main content editor
if (isset($_GET["id"]) && $_GET["type"] != "template") {
    if ('content' == $node_type && MODERA_PENDING_CHANGES == $database->fetch_first_value(
        'SELECT `pending` FROM ?f WHERE `content` = ?', $table, $_GET['id']))
    {
        $versioning = new Versioning($database);
        $data = $versioning->getCurrentData($node_type, $_GET['id']);
        $text = $data['text'];

    } else {
        $sq->query($db->con, "SELECT text FROM `$table` WHERE content = '".addslashes($_GET["id"])."'");
        $text = $sq->column(0, "text");
    }
}

// Template
if (isset($_GET["id"]) && $_GET["type"] == "template" && file_exists(SITE_PATH . "/" . $GLOBALS["directory"]["tmpl"] . "/" . $GLOBALS["templates_".$language][$_GET["id"]][1])) {
    $text = fread(fopen(SITE_PATH . "/" . $GLOBALS["directory"]["tmpl"] . "/" . $GLOBALS["templates_".$language][$id][1], "r"), 10000000);
}

// News module
if (isset($_GET["id"]) && $_GET["module"] == "news") {
    $sq->query($db->con, "SELECT content FROM module_news WHERE id = '".addslashes($_GET["id"])."'");
    $text = $sq->column(0, "content");
}

// Settings, first page intro text
if (isset($_GET["id"]) && $_GET["type"] == "info") {
    $sq->query($db->con, "SELECT intro FROM intro WHERE language = '".addslashes($language)."'");
    $text = $sq->column(0, "intro");
}

// Newsletter module
if (isset($_GET["id"]) && $_GET["module"] == "newsletter") {
    $sq->query($db->con, "SELECT text FROM module_newsletter_texts WHERE id = '".addslashes($_GET["id"])."'");
    $text = $sq->column(0, "text");
}

// Messages module
if (isset($_GET["id"]) && $_GET["module"] == "messages") {
    $sq->query($db->con, "SELECT content FROM module_messages WHERE id = '".addslashes($_GET["id"])."'");
    $text = $sq->column(0, "content");
}

// other possible modules
if (isset($_GET["id"]) && isset($_GET["table"]) && isset($_GET["field"])) {
    $sq->query($db->con, "SELECT `".addslashes($_GET["field"])."` FROM `module_".addslashes($_GET["table"])."` WHERE id = '".addslashes($_GET["id"])."' LIMIT 1");
    $text = $sq->column(0, addslashes($_GET["field"]));
}

// FULL SOURCE VIEW
if($_GET["full_source"] == "true" || $_GET["full_source"] == 1) {
    $full_source = true;
}

//$stylesheet = "http://www.modera.ee/siimtest/img/style_admin.css";

// ##############################################################
?>

<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    body{font:10px verdana,arial,sans-serif;}
    a{color:#cc0000;font-size:xx-small;}
</style>

<script language=JavaScript src='scripts/language/modera/modera.php?type=editor_lang'></script>

<?php
//Check user's Browser
if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE")) {
    echo "<script language=JavaScript src='scripts/editor.js'></script>";
    $height = "100%";
}
else {
    echo "<script language=JavaScript src='scripts/moz/editor.js'></script>";
    $height = "97%";
}
?>

</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<pre id="idTemporary" name="idTemporary" style="display:none">
<?php
if(isset($text))
    {
    //$sContent=stripslashes($text);//remove slashes (/)
    //echo htmlentities($sContent, ENT_QUOTES, UTF-8);
    //echo htmlentities($sContent);
    echo  htmlentities($text, ENT_COMPAT, "UTF-8");
    }

//          oEdit1.publishingPath=" echo SITE_URL . "/"   ";
?>
</pre>

<form id="Form1">
    <script>
        var oEdit1 = new InnovaEditor("oEdit1");
        <?php //oEdit1.arrStyle = [["BODY",false,"","font:10px verdana,arial,sans-serif;"]];?>

        oEdit1.useBR=false;
        oEdit1.useDIV=false;

        <?php
        if ($full_source == true) {
            $source_button = "XHTMLFullSource";
        }
        else {
            $source_button = "XHTMLSource";
        }
        ?>

        oEdit1.width="100%";
        oEdit1.height="<?= $height?>";
        oEdit1.features=["Preview","Print","Search","SpellCheck", "ClearAll",
            "|", "Undo","Redo","|", "Cut","Copy","Paste","PasteWord","PasteText","|",
            "ForeColor","BackColor","|", "LTR","RTL", "|", "Clean","<?= $source_button?>",
            "BRK","Bookmark","Hyperlink", "|","Image","Flash","Media","|","CustomTag", "|",
            "Table","Guidelines","Absolute","|","Characters","Line",
            "Form", "BRK",
            "StyleAndFormatting","TextFormatting","ListFormatting","BoxFormatting",
            "ParagraphFormatting","CssText","Styles","|",
            "Paragraph","|",
            "Bold","Italic",
            "Underline","Strikethrough","|","Superscript","Subscript","|",
            "JustifyLeft","JustifyCenter","JustifyRight","JustifyFull", "|", "Numbering","Bullets","|","Indent","Outdent"];

        <?php
            if ($_SERVER['HTTPS'] && 0 == strncmp(SITE_URL, 'http:', 5)) {
                $site_url = 'https' . substr(SITE_URL, 4);
            } else {
                $site_url = SITE_URL;
            }
        ?>
        oEdit1.cmdAssetManager="modalDialogShow('<?php echo $site_url?>/admin/browser_selectfile.php',640,465)";

        oEdit1.btnStyles=true;
        oEdit1.css="<?php echo $stylesheet; ?>";//Specify external css file here

        <?php
        //oEdit1.cmdInternalLink = "modalDialogShow('pages.php',400,380)"
        //oEdit1.cmdCustomObject = "modelessDialogShow('objects.htm',365,270)"

        $widgets = new widgets;
        $tags_from_widgets = $widgets->getWidgetsForEditor();
        $tags = '';
        if (isset($_GET["module"])) {
            $mdl = new $_GET["module"];
            if (@is_object($mdl) && @method_exists($mdl, 'editorCustomTags')) {
                $tags = $mdl->editorCustomTags();
            }
        }
        if ($tags != '' && $tags_from_widgets != '') {
            $tags .= ",";
        }
        $tags .= $tags_from_widgets;
        echo "oEdit1.arrCustomTag=[$tags];\n";
        ?>

        if (parent && typeof parent.submittedContent != "undefined") {
            oEdit1.RENDER(parent.submittedContent);
        } else {
            oEdit1.RENDER(document.getElementById("idTemporary").innerHTML);
        }

    </script>
    <input type="hidden" name="inpContent" id="inpContent">
    <input type="hidden" name="FullSource" id="FullSource" value="<?= $full_source?>">
</form>

</body>
</html>