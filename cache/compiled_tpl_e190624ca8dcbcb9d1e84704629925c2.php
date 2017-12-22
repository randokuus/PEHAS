<?php defined("MODERA_KEY")|| die(); ?><SCRIPT TYPE="text/javascript" LANGUAGE="JavaScript">

function fieldsetInit(maxfs) {
	for (i = 0; i < maxfs; i++) {
		if ((i+1) != 1) {
			document.getElementById("fieldset"+(i+1)).style.display = "none";										
		}
	}
}

function enableFieldset(current, fs, fs_extra, maxtab, maxfs) {
	for (i = 0; i < maxtab; i++) {
		document.getElementById("tabset"+(i+1)).className = "";										
	}
	document.getElementById("tabset"+current).className = "active";			
	
	for (i = 0; i < maxfs; i++) {
		if ((i+1) != fs && (i+1) != fs_extra) {
			document.getElementById("fieldset"+(i+1)).style.display = "none";										
		}
	}		
	if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
	else {document.getElementById(fs).style.display = "block";}
	
	if (fs_extra != '') {
		if (document.getElementById(fs_extra).style.display !="none"){document.getElementById(fs_extra).style.display = "none";}
		else {document.getElementById(fs_extra).style.display = "block"; }	
	}
	
	if (valueCheck(document.forms["vorm"].elements["editor_reload"]) == true) {
		if (current == 2 && document.forms["vorm"].elements["editor_reload"].value == 0) {
			document.forms["vorm"].elements["editor_reload"].value = 1;
			document.all.contentFreim.src = document.forms["vorm"].elements["editor_src"].value;
		}
	}
	
	return;
}

function fieldJump(current, maxtab, jumpto) {
	for (i = 0; i < maxtab; i++) {
		document.getElementById("tabset"+(i+1)).className = "";										
	}
	document.getElementById("tabset"+current).className = "active";			
	
	document.location = jumpto;
	
	return;
}

function enableSingleFieldset(fs) {
	if (fs != '') {
		if (document.getElementById(fs).style.display !="none"){document.getElementById(fs).style.display = "none";}
		else {document.getElementById(fs).style.display = "block"; }	
	}
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

<form method="post" action="<?php echo $data["PHP_SELF"]; ?>" <?php echo $data["ENCTYPE"]; ?> class="formpanel" name="vorm">

<?php if(isset($data["INFO"]) && is_array($data["INFO"])){ foreach($data["INFO"] as $_foreach["INFO"]){ ?>

	<fieldset title="<?php echo $_foreach["INFO"]["TITLE"]; ?>" <?php echo $_foreach["INFO"]["STYLE"]; ?>>
	<legend><?php echo $_foreach["INFO"]["TITLE"]; ?></legend>
		<table class="inputfield">
		<tr>
			<td><img src="pic/bullet_<?php echo $_foreach["INFO"]["TYPE"]; ?>.gif" alt="" border="0"></td>
			<td><label for="action" class="left"><?php echo $_foreach["INFO"]["INFO"]; ?></label></td>
		</tr>
		</table>
	</fieldset>
<?php }} ?>


<?php if(isset($data["FIELDSET"]) && is_array($data["FIELDSET"])){ foreach($data["FIELDSET"] as $_foreach["FIELDSET"]){ ?>

	<fieldset id="fieldset<?php echo $_foreach["FIELDSET"]["ID"]; ?>" title="<?php echo $_foreach["FIELDSET"]["TITLE"]; ?>" <?php echo $_foreach["FIELDSET"]["STYLE"]; ?>>
	<legend><?php echo $_foreach["FIELDSET"]["TITLE"]; ?></legend>
		<table class="inputfield">
		<?php if(isset($_foreach["FIELDSET"]["MAIN"]) && is_array($_foreach["FIELDSET"]["MAIN"])){ foreach($_foreach["FIELDSET"]["MAIN"] as $_foreach["FIELDSET.MAIN"]){ ?>
		
		<tr>
			<td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.MAIN"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.MAIN"]["DESC"]; ?>:</font>&nbsp;</label></td>
			<td align="left"><?php echo $_foreach["FIELDSET.MAIN"]["FIELD"]; ?>&nbsp;<label for="action" class="left"><?php echo $_foreach["FIELDSET.MAIN"]["EXTRA"]; ?></label></td>
			<td align="left">&nbsp;</td>			
		</tr>
		<?php }} ?>
		
		<?php if(isset($_foreach["FIELDSET"]["BUTTONS"]) && is_array($_foreach["FIELDSET"]["BUTTONS"])){ foreach($_foreach["FIELDSET"]["BUTTONS"] as $_foreach["FIELDSET.BUTTONS"]){ ?>
		
		<tr>
			<td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.BUTTONS"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.BUTTONS"]["DESC"]; ?>:</font>&nbsp;</label></td>		
			<td aling="left" colspan="2">
				<table border="0">
					<tr>
						<td align="left"><?php echo $_foreach["FIELDSET.BUTTONS"]["FIELD"]; ?></td>
						<td align="left"><?php echo $_foreach["FIELDSET.BUTTONS"]["EXTRA"]; ?></td>							
					</tr>
				</table>
			</td>
		</tr>
		<?php }} ?>
			
		<?php if(isset($_foreach["FIELDSET"]["EXTERN"]) && is_array($_foreach["FIELDSET"]["EXTERN"])){ foreach($_foreach["FIELDSET"]["EXTERN"] as $_foreach["FIELDSET.EXTERN"]){ ?>
		
		<tr>
			<td align="left"><label for="action" class="left"><font color="<?php echo $_foreach["FIELDSET.EXTERN"]["COLOR"]; ?>"><?php echo $_foreach["FIELDSET.EXTERN"]["DESC"]; ?>:</font>&nbsp;</label></td>
			<td align="left"><?php echo $_foreach["FIELDSET.EXTERN"]["FIELD"]; ?></td>
			<td align="left"><label for="action" class="left"><?php echo $_foreach["FIELDSET.EXTERN"]["EXTRA"]; ?></label></td>			
		</tr>
		<?php }} ?>
	
		<?php if(isset($_foreach["FIELDSET"]["NOTHING"]) && is_array($_foreach["FIELDSET"]["NOTHING"])){ foreach($_foreach["FIELDSET"]["NOTHING"] as $_foreach["FIELDSET.NOTHING"]){ ?>
	
		<table class="inputfield" width="100%"> 			
		<tr>
			<td align="left" colspan="3"><?php echo $_foreach["FIELDSET.NOTHING"]["FIELD"]; ?></td>
		</tr>
		</table>
		<?php }} ?>
				
		</table>
	</fieldset>
<?php }} ?>


		<?php if(isset($data["NOTHING"]) && is_array($data["NOTHING"])){ foreach($data["NOTHING"] as $_foreach["NOTHING"]){ ?>
	
		<table class="inputfield" width="100%"> 			
		<tr>
			<td align="left" colspan="3"><?php echo $_foreach["NOTHING"]["FIELD"]; ?></td>
		</tr>
		</table>
		<?php }} ?>
	
	
<?php if(isset($data["HIDDEN"]) && is_array($data["HIDDEN"])){ foreach($data["HIDDEN"] as $_foreach["HIDDEN"]){ ?>

	<input type="hidden" name="<?php echo $_foreach["HIDDEN"]["NAME"]; ?>" value="<?php echo $_foreach["HIDDEN"]["VALUE"]; ?>">
<?php }} ?>


<p></p>
<div class="buttonbar">
	<button type="submit"><img src="pic/button_accept.gif" alt="" border="0"><?php echo $data["SENDBUTTONTXT"]; ?></button>
</div>

</form>