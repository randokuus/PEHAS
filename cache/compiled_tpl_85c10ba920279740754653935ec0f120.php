<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<head>
	<title>Files</title>
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
		// Open new window for the selection
		function newWindow(myurl, sizex, sizey) {
			var newWindow;
			var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=yes,location=no,directories=no,width='+sizex+',height='+sizey;
			newWindow = window.open(myurl, "window", props);
			newWindow.focus();
		}
		
		PositionX = 10;
		PositionY = 10;
		defaultWidth  = 600;
		defaultHeight = 400;
		
		//kinda important
		var AutoClose = true;
				
		function openPicture(imageURL){
			imageTitle = imageURL;
			var imgWin = window.open('','_blank','scrollbars=no,resizable=1,width='+defaultWidth+',height='+defaultHeight+',left='+PositionX+',top='+PositionY);
			if( !imgWin ) { return true; } //popup blockers should not cause errors
			imgWin.document.write('<html><head><title>'+imageTitle+'<\/title><script type="text\/javascript">\n'+
				'function resizeWinTo() {\n'+
				'if( !document.images.length ) { document.images[0] = document.layers[0].images[0]; }'+
				'var oH = document.images[0].height, oW = document.images[0].width;\n'+
				'if( !oH || window.doneAlready ) { return; }\n'+ //in case images are disabled
				'window.doneAlready = true;\n'+ //for Safari and Opera
				'var x = window; x.resizeTo( oW + 200, oH + 200 );\n'+
				'var myW = 0, myH = 0, d = x.document.documentElement, b = x.document.body;\n'+
				'if( x.innerWidth ) { myW = x.innerWidth; myH = x.innerHeight; }\n'+
				'else if( d && d.clientWidth ) { myW = d.clientWidth; myH = d.clientHeight; }\n'+
				'else if( b && b.clientWidth ) { myW = b.clientWidth; myH = b.clientHeight; }\n'+
				'if( window.opera && !document.childNodes ) { myW += 16; }\n'+
				'x.resizeTo( oW = oW + ( ( oW + 200 ) - myW ), oH = oH + ( (oH + 200 ) - myH ) );\n'+
				'var scW = screen.availWidth ? screen.availWidth : screen.width;\n'+
				'var scH = screen.availHeight ? screen.availHeight : screen.height;\n'+
				'if( !window.opera ) { x.moveTo(Math.round((scW-oW)/2),Math.round((scH-oH)/2)); }\n'+
				'}\n'+
				'<\/script>'+
				'<\/head><body onload="resizeWinTo();"'+(AutoClose?' onblur="self.close();"':'')+'>'+
				(document.layers?('<layer left="0" top="0">'):('<div style="position:absolute;left:0px;top:0px;">'))+
				'<img src='+imageURL+' alt="Loading image ..." title="" onload="resizeWinTo();">'+
				(document.layers?'<\/layer>':'<\/div>')+'<\/body><\/html>');
			imgWin.document.close();
			if( imgWin.focus ) { imgWin.focus(); }
			//return false;
		}

		function openFile(loct) {
			var win=window.open(loct,'','width=600,height=400,menu=yes,status=yes,scrollbars=no');		
		}			
		
		//-->
		</SCRIPT>
	
</head>

<body id="body-frame">

<div class="infopanel">
	<h1><?php echo $data["TITLE"]; ?></h1>
</div>


<div class="tabmenu-dark">
	<ul class="tabmenu-dark">
	<?php if(isset($data["TABS"]) && is_array($data["TABS"])){ foreach($data["TABS"] as $_foreach["TABS"]){ ?>

		<li id="tabset<?php echo $_foreach["TABS"]["ID"]; ?>" <?php echo $_foreach["TABS"]["CLASS"]; ?>><a href="<?php echo $_foreach["TABS"]["URL"]; ?>"><?php echo $_foreach["TABS"]["NAME"]; ?></a></li>
	<?php }} ?>
		
	</ul>
</div>

<SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function fieldsetInit(maxfs) {
	for (i = 0; i < maxfs; i++) {
		if ((i+1) != 1) {
			document.getElementById("fieldset"+(i+1)).style.display = "none";										
		}
	}
}

function fieldJump(current, maxtab, jumpto) {
	for (i = 0; i < maxtab; i++) {
		document.getElementById("tabset"+(i+1)).className = "";										
	}
	document.getElementById("tabset"+current).className = "active";			
	
	document.location = jumpto;
	
	return;
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

</SCRIPT>

<form class="formpanel" action="<?php echo $this->getGlobals("PHP_SELF"); ?>" method="get" name="filter_form">
	<fieldset title="Node properties">
	<legend><?php echo $data["FILTER"]; ?></legend>
		<table class="inputfield">
<!-- 		<form action="<?php echo $this->getGlobals("PHP_SELF"); ?>" method="get" name="filter_form">		 -->
		<tr>
			<td><label for="filter" class="left"><?php echo $data["FILTER"]; ?></label></td>
			<td><input type="text" id="filter" name="filter" value="<?php echo $data["VAL_FILTER"]; ?>" size="16" /></td>	
			<td align="right"><label for="mode" class="left"><?php echo $data["MODE"]; ?></label></td>
			<td><select id="mode" name="mode">
				<?php if(isset($data["MODES"]) && is_array($data["MODES"])){ foreach($data["MODES"] as $_foreach["MODES"]){ ?>

					<option value="<?php echo $_foreach["MODES"]["VALUE"]; ?>" <?php echo $_foreach["MODES"]["SEL"]; ?>><?php echo $_foreach["MODES"]["NAME"]; ?></option>
				<?php }} ?>
					
				</select></td>
			<?php if(isset($data["EXTRAFILTER"]) && is_array($data["EXTRAFILTER"])){ foreach($data["EXTRAFILTER"] as $_foreach["EXTRAFILTER"]){ ?>

			<td><label for="<?php echo $_foreach["EXTRAFILTER"]["ID"]; ?>" class="left"><?php echo $_foreach["EXTRAFILTER"]["LABEL"]; ?></label></td>
			<td><?php echo $_foreach["EXTRAFILTER"]["FIELD"]; ?></td>			
			<?php }} ?>
							
			<td>&nbsp;</td>
			<td><button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SUBMIT"]; ?></button></td>
		</tr>
			<?php echo $data["HIDDEN"]; ?>
			<input type="hidden" name="sort" value="<?php echo $data["VAL_SORT"]; ?>">
			<input type="hidden" name="sort_type" value="<?php echo $data["VAL_SORT_TYPE"]; ?>">		
			<input type="hidden" name="folder" value="<?php echo $data["VAL_FOLDER"]; ?>">					
<!-- 		</form> -->
		</table>
	</fieldset>
</form>

<!-- begin list -->

<table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
<!-- <caption><?php echo $data["CAPTION"]; ?>&nbsp;</caption> -->
<tr>
	<?php if(isset($data["HEADER"]) && is_array($data["HEADER"])){ foreach($data["HEADER"] as $_foreach["HEADER"]){ ?>

	<th class="<?php echo $_foreach["HEADER"]["STYLE"]; ?>"><a href="<?php echo $_foreach["HEADER"]["URL"]; ?>"><?php echo $_foreach["HEADER"]["NAME"]; ?></a></th>
	<?php }} ?>
		
</tr>
<?php if(isset($data["FOLDERS"]) && is_array($data["FOLDERS"])){ foreach($data["FOLDERS"] as $_foreach["FOLDERS"]){ ?>

<tr>
	<td width="1%"><a href="<?php echo $_foreach["FOLDERS"]["URL"]; ?>" alt="<?php echo $_foreach["FOLDERS"]["TEXT"]; ?>"><?php echo $_foreach["FOLDERS"]["ICON"]; ?></a></td>
	<td colspan="5"><a href="<?php echo $_foreach["FOLDERS"]["URL"]; ?>" alt="<?php echo $_foreach["FOLDERS"]["TEXT"]; ?>"><?php echo $_foreach["FOLDERS"]["NAME"]; ?></a></td>	
</tr>
<?php }} ?>

<?php if(isset($data["ROWS"]) && is_array($data["ROWS"])){ foreach($data["ROWS"] as $_foreach["ROWS"]){ ?>

<tr>
	<td width="1%"><a href="<?php echo $_foreach["ROWS"]["URL1"]; ?>" alt="<?php echo $_foreach["ROWS"]["TEXT"]; ?>"><?php echo $_foreach["ROWS"]["ICON"]; ?></a></td>
	<td><a href="<?php echo $_foreach["ROWS"]["URL1"]; ?>" alt="<?php echo $_foreach["ROWS"]["TEXT"]; ?>"><?php echo $_foreach["ROWS"]["NAME"]; ?></a></td>
	<td><a href="<?php echo $_foreach["ROWS"]["URL1"]; ?>" alt="<?php echo $_foreach["ROWS"]["TEXT"]; ?>"><?php echo $_foreach["ROWS"]["SIZE"]; ?></a></td>
	<td><a href="<?php echo $_foreach["ROWS"]["URL1"]; ?>" alt="<?php echo $_foreach["ROWS"]["TEXT"]; ?>"><?php echo $_foreach["ROWS"]["DATE"]; ?></a></td>	
	<td align="right">&nbsp;<a href="<?php echo $_foreach["ROWS"]["MODURL"]; ?>"><img src="pic/edit.gif" width="9" height="11" border="0" alt="Edit?"></a></td>
	<td align="right">&nbsp;<a href="javascript:del('<?php echo $_foreach["ROWS"]["DELURL"]; ?>');"><img src="pic/delete.gif" width="9" height="11" border="0" alt="Delete?"></a></td>
</tr>
<?php }} ?>

</table>

<?php if(isset($data["PAGES"]) && is_array($data["PAGES"])){ foreach($data["PAGES"] as $_foreach["PAGES"]){ ?>

	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="datatable">
	<tr><td><?php echo $_foreach["PAGES"]["PAGES"]; ?>: <?php echo $_foreach["PAGES"]["LINKS"]; ?></td></tr>
	</table>
<?php }} ?>


<p></p>
<!-- end list -->

</body>
</html>