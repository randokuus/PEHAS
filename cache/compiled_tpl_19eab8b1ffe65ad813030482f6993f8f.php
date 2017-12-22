<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Top Index</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="topindex_<?php echo $data["TYPE"]; ?>.css" type="text/css" media="all" />
	
	<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">
		function jumpToMenu(menuid, url) {
			document.getElementById("menu"+1).className = "";		
			document.getElementById("menu"+2).className = "";		
			document.getElementById("menu"+3).className = "";		
			document.getElementById("menu"+4).className = "";		
			document.getElementById("menu"+5).className = "";														
			document.getElementById("menu"+menuid).className = "active";
			parent.frames['main'].location = url;
			return;
		}
		function openFile(loct) {
			var win=window.open(loct,'','width=600,height=400,menubar=yes,resizable=yes,status=yes,scrollbars=yes');		
		}			
	</SCRIPT>	
	
</head>
<body id="menu-frame">
	<div id="logo"><img src="pic/topframe-logo-<?php echo $data["TYPE"]; ?>.gif" alt="<?php echo $data["TYPE"]; ?>"/></div>
	<div id="menublock">
 		<form method="get" action="index.php" target="_top">	
		<div id="langmenu">
			<select name="language" title="<?php echo $data["LANGUAGESELECT"]; ?>" onChange="this.form.submit()">
				<?php if(isset($data["LANGUAGE"]) && is_array($data["LANGUAGE"])){ foreach($data["LANGUAGE"] as $_foreach["LANGUAGE"]){ ?>

				<option value="<?php echo $_foreach["LANGUAGE"]["CODE"]; ?>" <?php echo $_foreach["LANGUAGE"]["SEL"]; ?>><?php echo $_foreach["LANGUAGE"]["LANGUAGE"]; ?></option>
				<?php }} ?>

			</select>
		</div>
		</form>
		<div id="mainmenu">
				<?php if(isset($data["MENU"]) && is_array($data["MENU"])){ foreach($data["MENU"] as $_foreach["MENU"]){ ?>

					<a id="menu<?php echo $_foreach["MENU"]["ID"]; ?>" href="#" onClick="<?php echo $_foreach["MENU"]["URL"]; ?>" target="" class="<?php echo $_foreach["MENU"]["STYLE"]; ?>"><?php echo $_foreach["MENU"]["NAME"]; ?></a>
				<?php }} ?>
		
		</div>
		<div id="infopanel">
			<?php echo $data["USER"]; ?> &nbsp;
			<a href="javascript:openFile('http://www.modera.net/help/?language=<?php echo $data["INTERFACE"]; ?>&version=<?php echo $data["VERSION"]; ?>')" class="logout"><img src="pic/ico-help.gif" width="8" height="10" hspace="4" border="0" /><?php echo $data["HELP"]; ?></a>
			<a href="logout.php" class="logout"><img src="pic/ico-logout.gif" width="8" height="9" hspace="4" border="0" /><?php echo $data["LOGOUT"]; ?></a>
		</div>
	</div>
</body>
</html>
