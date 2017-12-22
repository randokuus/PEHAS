<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<head>
	<title>Content Admin</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<link rel="stylesheet" href="main.css" type="text/css" media="all" />

		<SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
		<!--
		function del(urliMeez) {
			conf = window.confirm('<?php echo $data["CONFIRMATION"]; ?>');
			if (conf) top.main.right.document.location = urliMeez;
		}
		function submitTo() {
			document.forms["vorm"].elements['submit_to'].value = '1';
			document.forms["vorm"].submit();
		}
		function navigateTo(urliMeez) {
			top.main.right.document.location = urliMeez;
		}
		// Open new window for the selection
		function newWindow(myurl, sizex, sizey) {
			var newWindow;
			var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=no,location=no,directories=no,width='+sizex+',height='+sizey;
			newWindow = window.open(myurl, "window", props);
			newWindow.focus();
		}

		//-->
		</SCRIPT>
		<script language="JavaScript" type="text/javascript" src="img/aliases.js"></script>
</head>

<body id="body-frame">

<div class="infopanel">
	<h1><?php echo $data["TITLE"]; ?></h1>
	<br />
</div>

<form method="post" action="<?php echo $data["PHP_SELF"]; ?>" class="formpanel" name="vorm">

<?php if(isset($data["INFO"]) && is_array($data["INFO"])){ foreach($data["INFO"] as $_foreach["INFO"]){ ?>

	<fieldset>
	<legend><?php echo $_foreach["INFO"]["TITLE"]; ?></legend>
		<table class="inputfield">
		<tr>
			<td><img src="pic/bullet_<?php echo $_foreach["INFO"]["TYPE"]; ?>.gif" alt="" border="0"></td>
			<td><label for="action" class="left"><?php echo $_foreach["INFO"]["INFO"]; ?></label></td>
		</tr>
		</table>
	</fieldset>
<?php }} ?>


    <fieldset>
    	<label style="color:#666"><?php echo $data["REWRITEMAP_NOTICE"]; ?></label>
    	<br />
    	<label for="aliases_enabled"><?php echo $data["LABEL_ENABLEALIASES"]; ?>: </label><input type="checkbox" id="aliases_enabled" name="aliases_enabled" value="1" <?php echo $data["ALIASES_CHECKED"]; ?> />
    	<br /><br />
    </fieldset>

<?php if(isset($data["FIELDSET"]) && is_array($data["FIELDSET"])){ foreach($data["FIELDSET"] as $_foreach["FIELDSET"]){ ?>

	<fieldset>
	<legend><?php echo $_foreach["FIELDSET"]["TITLE"]; ?></legend>

	<?php echo $_foreach["FIELDSET"]["ALIASES_TBL"]; ?>

	</fieldset>
<?php }} ?>


<p></p>
<div class="buttonbar">
	<button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SENDBUTTONTXT"]; ?></button>
	<button onclick="generateAll();return false;"><?php echo $data["GENERATEBUTTONTXT"]; ?></button>
	<button onclick="clearAll();return false;"><?php echo $data["CLEARBUTTONTXT"]; ?></button>
</div>

</form>

</body>
</html>