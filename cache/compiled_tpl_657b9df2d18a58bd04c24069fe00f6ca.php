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
			var props = 'scrollBars=yes,resizable=yes,toolbar=no,menubar=yes,location=no,directories=no,width='+sizex+',height='+sizey;
			newWindow = window.open(myurl, "window", props);
			newWindow.focus();
		}

		function clearPic(nr) {
			if (nr)  {
				document.forms["vorm"].elements['pic'+nr].value = '';
				document.getElementById("newspic"+nr).innerHTML = '&nbsp;'
			}
			else {
				document.forms["vorm"].elements['pic'].value = '';
				document.getElementById("newspic").innerHTML = '&nbsp;'
			}
		}

        function clearPicUniversal(nr, clear) {
            var clear_el = '';
            if (nr)  {
                clear_el = 'pic' + nr;
                document.getElementById("newspic"+nr).innerHTML = '&nbsp;'
            }
            else {
                clear_el = 'pic';
                document.getElementById("newspic").innerHTML = '&nbsp;'
            }
            if (clear) {
                clear_el = clear;
            }
            if (!document.forms["vorm"].elements[clear_el] || document.forms["vorm"].elements[clear_el] == undefined) {
                alert("Form element not found: " + clear_el);
            } else {
                document.forms["vorm"].elements[clear_el].value = '';
            }
        }

		function showPic(nr, url) {
				if (valueCheck(url)) {

				var filetype = url.substring(url.length-3, url.length);
				if (filetype.toLowerCase() == "swf") {


					var inpSwfWidth = 120;
					var inpSwfHeight = 120;
					var inpSwfURL = url;

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

					}
					else if (filetype.toLowerCase() == "jpe" || filetype.toLowerCase() == "jpg" || filetype.toLowerCase() == "gif" || filetype.toLowerCase() == "png" || filetype.toLowerCase() == "tif") {
						var sHTML = "<img src=\"" + url + "\" border=0 alt=\"\">";
					}

					if (valueCheck(nr))  {
						document.getElementById("newspic"+nr).innerHTML = sHTML;
					}
					else {
						document.getElementById("newspic").innerHTML = sHTML;
					}

			  }
		}

		function doPrompt(text, field) {
			var val = prompt(text);
			if (val) {
				var frm = document.forms["vorm"];
				var destinationList = frm.elements[field+"[]"];
				count = destinationList.options.length;
				destinationList.options[count] = new Option(val, val);
			}
		}

		function deleteSelectedItemsFromList(sourceList) {
			obj = document.forms["vorm"].elements[sourceList+"[]"];
			while (obj.selectedIndex != '-1') {
				if (obj.options[obj.selectedIndex].value != '-1') {
					obj.options[obj.selectedIndex] = null;
				} else break;
			}
		}

		editIndex = -1;

		//edit the selection item
		function editItemInList(text, field) {
			var val = prompt(text);
			if (val) {
				obj = document.forms["vorm"].elements[field+"[]"];
				if( obj.selectedIndex != -1 ) {
					editIndex = obj.selectedIndex;
					obj.options[editIndex].text = val;
					obj.options[editIndex].value = val;
				}
			}
		}

		//-->
		</SCRIPT>

</head>

<body id="body-frame" onload="<?php echo $data["BODY_ONLOAD"]; ?>">

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

<?php echo $data["CONTENT"]; ?>

</body>
</html>
