<?php defined("MODERA_KEY")|| die(); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $this->getTranslate("admin_general|calendar"); ?></title>

<style>
BODY, INPUT, BUTTON, SELECT, TEXTAREA, TD { font: 11px Tahoma, Arial, Helvetica, sans-serif; }

IMG { border: 0px; }
FORM { padding: 0px; margin: 0px; }

A { text-decoration: none; }
A:hover { text-decoration: underline; }

DIV.clear { clear: both; }
.fix1 { font-size: 1px; line-height: 1px; }
.fix2 { clear: both; font-size: 1px; line-height: 1px; }

BODY.cal { background: #DDD; }

DIV.cal { padding: 10px 4px 0px 4px; }
DIV.cal DIV.cal-body { background: #DDD; border: 1px solid #CCC; margin: 0px 6px 10px 6px; padding: 10px; }
DIV.cal DIV.cal-body A.left { float: left; }
DIV.cal DIV.cal-body A.right { float: right; }
DIV.cal DIV.cal-body DIV.month { text-align: center; color: #534968; margin-bottom: 5px; }
DIV.cal DIV.cal-body TABLE { border-collapse: collapse; }
DIV.cal DIV.cal-body TABLE TD { vertical-align: top; text-align: left; padding: 3px 2px; color: #737373; }
DIV.cal DIV.cal-body TABLE TD A { color: #737373; }

DIV.cal DIV.cal-time { background: #DDD; border: 1px solid #CCC; margin: 0px 6px 10px 6px; padding: 5px; font-family: Tahoma, Arial, Helvetica, sans-serif; }
DIV.cal DIV.cal-time SELECT { vertical-align: middle; font-family: Tahoma, Arial, Helvetica, sans-serif; }
DIV.cal DIV.cal-time BUTTON { vertical-align: middle; margin-top: -2px; margin-left: 10px; }

DIV.cal DIV.days { width: 157px; text-align: center; margin: auto; } 
DIV.cal DIV.days DIV.blank { float: left; display: block; width: 20px; padding: 3px 0px 3px 0px; border: 1px solid #DDD; }
DIV.cal DIV.days DIV.dayname { float: left; display: block; width: 20px; padding: 3px 0px 3px 0px; border: 1px solid #CCC; background: #CCC; font-weight: bold; color: #333333; }
DIV.cal DIV.days A { float: left; display: block; width: 20px; padding: 3px 0px 3px 0px; border: 1px solid #FFF; border-right: 1px solid #DDD; border-bottom: 1px solid #DDD; background: #FFF; color: #666; text-align: center; }
DIV.cal DIV.days A B { color: #333333; }
DIV.cal DIV.days A.today { border: 1px solid #C60000; color: #C60000; }
DIV.cal DIV.days A.today:hover { border: 1px solid #C60000; color: #C60000; background: #ffe6e6; }
DIV.cal DIV.days A.selected { border: 1px solid #DDD; color: #000; background: #DDD; }
DIV.cal DIV.days A.selected:hover { border: 1px solid #DDD; color: #333; background: #DDD; }
DIV.cal DIV.days A:hover { border: 1px solid #DDD; border-bottom: 1px solid #EEE; border-right: 1px solid #EEE; background: #EEE; text-decoration: none; }
.cal-btn { overflow: visible; padding: 0px 8px; color: #534968; background: #dddddd; border: 1px solid; border-color: #ededed #6d6d6d #6d6d6d #ededed; cursor: pointer; }
DIV.cal .cal-fix2 { clear: both; font-size: 1px; line-height: 1px; }

</style>

	
<SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
<!--
	function ChooseDate() {
		var finalres = "";
		var year = parseInt(document.forms['datevorm'].elements['year'].value);
		var month = parseInt(document.forms['datevorm'].elements['month'].value);
		var day = parseInt(document.forms['datevorm'].elements['day'].value);				
		var field = document.forms['datevorm'].elements['field'].value;		
		var type = parseInt(document.forms['datevorm'].elements['type'].value);		
		
		var hour = document.forms['datevorm'].elements['hour'].options [document.forms['datevorm'].elements['hour'].selectedIndex].value;
		var minute = document.forms['datevorm'].elements['minute'].options [document.forms['datevorm'].elements['minute'].selectedIndex].value;
		
		if (month < 10) month = "0" + month;
		if (day < 10) day = "0" + day;		
		
		year += '';
		month += '';		
		day += '';		
		
	  // type 1 - date only, standard 1 field (STANDARD CASE ALSO)
	  // field = value
	  
	  // type 11 - date+time, standard 1 field (datetime yyyy-mm-dd hh:ii:ss)
	  // field = value	  
	  
	  // type 2 - full date, separate fields 
	  // field_d   field_m   field_y   field_hh   field_mm    
	  
	  // type 3 - date, separate fields 
	  // field_d   field_m   field_y
	  
	  // type 4 - timestamp, 1 field (lenght YYYYMMDDHHMMSS)
	  // field = value
	  
	  // type 5 - timestamp, 1 field (lenght (lenght YYYYMMDD)
	  // field = value	 
	  
	  // type 6 - year (YYYY)
	  // field = value	 
	  
	  // type 7 - time (hh:ii:ss)
	  // field = value	 
	    
		
		if (type == 2) {
			finalres = year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00";
		}
		else if (type == 11) {
			finalres = year + "-" + month + "-" + day + " " + hour + ":" + minute + ":00";
		}		
		else if (type == 4) {
			finalres = year + month + day + hour + minute + "00";
		}		
		else if (type == 5) {
			finalres = year + month + day;
		}	
		else if (type == 6) {
			finalres = year;
		}
		else if (type == 7) {
			finalres = hour + ":" + minute + ":00";
		}		
		else {
			finalres = year + "-" + month + "-" + day;
		}
		
		//alert (final);
	  //window.opener.document.vorm.elements[field].value = final;
	  
		if (window.opener && !window.opener.closed) {
		    if (type == 2) {
					window.opener.document.forms['vorm'].elements[field + '_d'].value = day;
		            window.opener.document.forms['vorm'].elements[field + '_m'].value = month;
		            window.opener.document.forms['vorm'].elements[field + '_y'].value = year;
		            window.opener.document.forms['vorm'].elements[field + '_hh'].value = hour;
		            window.opener.document.forms['vorm'].elements[field + '_mm'].value = minute;
		    }
		    else if (type == 3) {
					window.opener.document.forms['vorm'].elements[field + '_d'].value = day;
		            window.opener.document.forms['vorm'].elements[field + '_m'].value = month;
		            window.opener.document.forms['vorm'].elements[field + '_y'].value = year;
		    } 			 
			else {
					window.opener.document.forms['vorm'].elements[field].value = finalres;
		    }
			window.close();	   
		}
  
	}
// -->
</SCRIPT>

</head>

<body class="cal">

<?php echo $data["CALENDAR_BODY"]; ?>

</body>
</html>