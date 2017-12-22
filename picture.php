<?php
/**
 * Modera.net picture opener. This is actually not used any more. Use img/scripts.js -> openPicture() instead
 * @access public
 */
 
// ############################################################## 
include("class/config.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"> 

<html>
<head>

<SCRIPT LANGUAGE="JavaScript">

var NS = (navigator.appName=="Netscape")?true:false; 

function fitWindowSize() { 
iWidth = (NS)?window.innerWidth:document.body.clientWidth; 
iHeight = (NS)?window.innerHeight:document.body.clientHeight; 
iWidth = document.images[0].width - iWidth; 
iHeight = document.images[0].height - iHeight; 
window.resizeBy(iWidth, iHeight); 
//self.focus(); 
}

function doTitle()	{
	document.title = "<?= COOKIE_URL?>";
}

//  End -->
</script>
</HEAD>

	<title>Loading...</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">	
</head> 

<BODY onLoad="fitWindowSize();doTitle();self.focus()" leftmargin=0 topmargin=0>

<a href="javascript:window.close();"><img src="<?= $image?>" alt="" border="0"></a>

</body>
</html>

