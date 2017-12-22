<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/module.xslprocess.class.php");
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
    echo '<body onLoad= "self.close();">';
exit;
}

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// init Text object for this page
$txt = new Text($language2, "editor");
$txtf = new Text($language2, "message");

$db = new DB;
$db->connect();
$sq = new sql;
$sq1 = new sql;

$path_parts = parse_url(SITE_URL);
$engine_url = $path_parts['path'];
if (substr($engine_url,0,1) != "/") $engine_url = "/" . $engine_url;
if (substr($engine_url,-1) != "/") $engine_url = $engine_url . "/";

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="main.css" type="text/css" media="all" />
<TITLE><?= $txt->display("select_link")?></TITLE>

<script>

function openMedia()
    {
<?php
//Check user's Browser
if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
    echo "insertLink(modalDialogShowWin('browser_selectfile.php',640,465));";
else
    echo "modalDialogShow('browser_selectfile.php',640,465);";
?>
    }

function modalDialogShow(url,width,height)
    {
    var left = screen.availWidth/2 - width/2;
    var top = screen.availHeight/2 - height/2;
    activeModalWin = window.open(url, "", "width="+width+"px,height="+height+",left="+left+",top="+top);
    window.onfocus = function(){if (activeModalWin.closed == false){activeModalWin.focus();};};
    }

function modalDialogShowWin(url,width,height)
    {
    return window.showModalDialog(url,window,
        "dialogWidth:"+width+"px;dialogHeight:"+height+"px;edge:Raised;center:Yes;help:No;Resizable:Yes;Maximize:Yes");
    }

    function setAssetValue(url) {
        insertLink(url);
    }

    function submitToPage() {
      window.opener.document.vorm.redirectto.value = document.getElementById("inpURL").value;
      window.close();
    }

    function insertLink(url) {
        if (valueCheck(url)) {
            if (url.substr(0,1) != "/") {
                url = "<?=$engine_url?>" + url;
            }
        document.getElementById("inpURL").value = url;
        }
    }

function valueCheck(objToTest) {
    if (null == objToTest) {
        return false;
    }
    if ("undefined" == typeof(objToTest) ) {
        return false;
    }
    return true;

}

</script>
</head>
<body class="generalstyle" style="background-color:#EEE; margin: 0px">
    <FORM id="linkselector" NAME="linkselector" method="post" action="" class="formpanel">
    <table border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td><label><?= $txt->display("url")?>:&nbsp;&nbsp;</label></td>
        <td width="92%"><INPUT type="text" ID="inpURL" NAME="inpURL" value="<?=$_GET["link_url"]?>" style="width:100%"></td>
            <td>&nbsp;</td>
            <td><a href="javascript:openMedia();"><img src="pic/open_media.gif" alt="" border="0"></a></td>
            <td>&nbsp;&nbsp;</td>
            <td><BUTTON ID=Ok TYPE="button" onClick="submitToPage();"><?= $txt->display("choose");?></BUTTON></td>
            <td>&nbsp;</td>
            <td><BUTTON type="button" ONCLICK="window.close();" class="but"><?= $txt->display("cancel");?></BUTTON></td>
    </tr>
    </table>
</form>
    <table width=100%>
    <tr>
        <td>
        <div id="tree">
        <?php
            $xslp = new xslprocess;
            $xslp->all_visible = true;
            $xslp->cachelevel = TPL_CACHE_NOTHING;
            echo $xslp->menu("sitemap.xsl", "not published");
        ?>
        </div>
        </td>
    </tr>
    </table>


</body>
</html>