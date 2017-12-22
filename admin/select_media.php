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

$insert_into = false;
if ($_REQUEST['insert_into']) {
    $insert_into = $_REQUEST['insert_into'];
}

?>
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="main.css" type="text/css" media="all" />
<TITLE><?= $txt->display("image_insert")?></TITLE>
<SCRIPT LANGUAGE=JavaScript>
<!--
var imgCaptions = new Array();
var imgHeights = new Array();
var imgWidths = new Array();
imgCaptions[0] = "";
imgHeights[0] = "0";
imgWidths[0] = "0";

function setAssetValue(url) {
	insertLink(url);
}

function insertLink(url) {
	if (url) {
		if (url.substr(0,1) != "/") {
			url = "<?php echo $engine_url?>" + url;
		}
	}
	document.getElementById("inpURL").value = url;
}

function submitToPage() {
  var arr = new Array();
  <?php

  if ($pic) {
      echo "var insert_into = 'pic{$pic}';\n";
      echo "var picnr = $pic;\n";
  }
  else {
      echo "var insert_into = 'pic';\n";
      echo "var picnr = '';\n";
  }
  if ($insert_into){
      echo "var insert_into = '$insert_into';\n";
  }
  ?>

	if (document.forms["mediaselector"].inpURL.value) {

	var filetype = document.forms["mediaselector"].inpURL.value.substring(document.forms["mediaselector"].inpURL.value.length-3, document.forms["mediaselector"].inpURL.value.length);
	if (filetype.toLowerCase() == "swf") {


		var inpSwfWidth = 120;
		var inpSwfHeight = 120;
		var inpSwfURL = document.forms["mediaselector"].inpURL.value;

		var sHTML = "<object "+
			"classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" " +
			"width=\""+inpSwfWidth+"\" "+
			"height=\""+inpSwfHeight+"\" " +
			"codebase=\"http://active.macromedia.com/flash6/cabs/swflash.cab#version=6.0.0.0\">"+
			"	<param name=movie value=\""+inpSwfURL+"\">" +
			"	<param name=play value=\"true\">" +
			"	<param name=loop value=\"true\">" +
			"	<param name=WMode value=\"Opaque\">" +
			"	<param name=quality value=\"high\">" +
			"	<param name=bgcolor value=\"\">" +
			"	<param name=align value=\"\">" +
			"	<embed src=\""+inpSwfURL+"\" " +
			"		width=\""+inpSwfWidth+"\" " +
			"		height=\""+inpSwfHeight+"\" " +
			"		play=\"true\" " +
			"		loop=\"true\" " +
			"		wmode=\"Opaque\" " +
			"		quality=\"high\" " +
			"		bgcolor=\"\" " +
			"		align=\"\" " +
			"		pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\">" +
			"	</embed>"+
			"</object>";

			self.opener.document.getElementById("newspic"+picnr).innerHTML = sHTML;
			self.opener.document.forms["vorm"].elements[insert_into].value = document.forms["mediaselector"].inpURL.value;

		}
		else if (filetype.toLowerCase() == "jpe" || filetype.toLowerCase() == "jpg" || filetype.toLowerCase() == "gif" || filetype.toLowerCase() == "png" || filetype.toLowerCase() == "tif"|| filetype.toLowerCase() == "bmp") {
			self.opener.document.getElementById("newspic"+picnr).innerHTML = "<img src='" + document.forms["mediaselector"].inpURL.value + "' alt='' border=0>";
			self.opener.document.forms["vorm"].elements[insert_into].value = document.forms["mediaselector"].inpURL.value;
		}

	  window.close();
  }
 }
// -->
</SCRIPT>

</HEAD>

<BODY class="generalstyle" style="background-color:#EEE; margin: 0px">
<iframe name="IMGPICK" src="browser_selectfile.php" style="border: grey 1px; width: 100%; height:293px; z-index:0"></iframe><br />
<FORM id="mediaselector" NAME="mediaselector" method="post" action="" class="formpanel">
<TABLE cellspacing=0 cellpadding="0" border="0">
<TR>
<TD VALIGN="top" align="left" colspan="2" nowrap>
<label><?php echo $txt->display("image_url")?>:<br></label>
<INPUT TYPE=TEXT SIZE=40 NAME="inpURL" ID="inpURL" style="width : 300px;" value="">

&nbsp;
<BUTTON type="button" ID="Ok" onClick="submitToPage();"><?php echo $txt->display("image_ok")?></BUTTON>
&nbsp;
<BUTTON type="button" ONCLICK="window.close();"><?php echo $txt->display("image_cancel")?></BUTTON>

</TD>
</TR>
</TABLE>
<INPUT TYPE=HIDDEN SIZE=5 value="0" NAME=ImgHeight>
<INPUT TYPE=HIDDEN SIZE=5 value="0" NAME=ImgWidth>
</FORM>
</BODY>
</HTML>