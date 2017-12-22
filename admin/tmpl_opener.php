<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

error_reporting(0);
if (!$file) exit;
//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
//require_once(SITE_PATH . "/class/text.class.php");
//require_once(SITE_PATH . "/class/admin.class.php"); 			// administration main object
//require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
//require_once(SITE_PATH . "/class/templatef.class.php");  // site default template object

// ##############################################################

// init session object
$ses = new Session();
$logged = $ses->returnID();
$user = $ses->returnUser();
$group = $ses->group;

if (!$logged) {
	exit;
}

if (false !== strpos($file, '..')) exit();

// ##############################################################

// #########################################################

$filename = SITE_PATH . "/" . $GLOBALS["directory"]["tmpl"] . "/" . $file;
if (!file_exists($filename)) exit;
$ext = substr($file, strrpos($filename, ".")+1);
if ($ext == "php") exit;
switch( $ext ){
   case "pdf": $ctype="application/pdf";              break;
   case "exe": $ctype="application/octet-stream";      break;
   case "zip": $ctype="application/zip";              break;
   case "doc": $ctype="application/msword";            break;
   case "xls": $ctype="application/vnd.ms-excel";      break;
   case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
   case "gif": $ctype="image/gif";                    break;
   case "png": $ctype="image/png";                    break;
   case "jpg": $ctype="image/jpg";                    break;
   case "txt": $ctype="text/plain";                    break;
   case "xml": $ctype="text/xml";                    break;
   case "htm": $ctype="text/html";                    break;
   case "html": $ctype="text/html";                    break;
   case "css": $ctype="text/css";                    break;
   default:    $ctype="application/force-download";  // application/force-download  application/octet-stream  application/download
}

// http://support.microsoft.com/default.aspx?scid=kb;en-us;812935

  $disposition = "attachment"; // "inline" to view file in browser or "attachment" to download to hard disk

  if (isset($_SERVER["HTTPS"])) {
     /**
       * We need to set the following headers to make downloads work using IE in HTTPS mode.
       */

     header("Pragma: ");
     header("Cache-Control: ");
     header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
     header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
     header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
     header("Cache-Control: post-check=0, pre-check=0", false);
  }
  else if ($disposition == "attachment") {
         header("Cache-control: private");
  }
  else {
     header("Cache-Control: no-cache, must-revalidate");
     header("Pragma: no-cache");
  }

  header("Content-Type: $ctype");
  header("Content-Disposition: $disposition; filename=\"".trim(htmlentities(basename($filename)))."\"");
  header("Content-Description: ".trim(htmlentities(basename($filename))));
  header("Content-Length: ".(string)(filesize($filename)));


// Open the file
$fd=@fopen($filename,'r');
@fpassthru($fd);
//header("Connection: close");
exit();
