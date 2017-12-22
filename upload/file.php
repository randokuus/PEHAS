<?php

// ***********************************************
// module 'filemanager' file opener
// last mod 11.01.05, siim
// ***********************************************
error_reporting(0);
// file parameter missing, exit
if (!$_GET['file'] || false !== strpos($_GET['file'], '..')) exit();
// 'file' parameter is relative to current folder
$file = dirname(__FILE__) . '/' . $_GET['file'];

//
// NB! If this file will be included in some other script in diffrent directory
// than the following includes with relative path ('../class/config.php') will
// not work, you should change them to: dirname(__FILE__) . '/../class/config.php'
//

include_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

// NO error handler here, has been noted to produce weird shit from time to time when using in a file download context
//if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);
//$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/Database.php");

$db = new db;
$db->connect();
$sq = new sql;
$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data_settings = $GLOBALS['site_settings'];

//admin Email
if (isset($GLOBALS["site_settings"]["admin_email"])) {
    $GLOBALS["site_admin"] = $GLOBALS["site_settings"]["admin_email"];
}
//admin name
if (isset($GLOBALS["site_settings"]["admin"])) {
    $GLOBALS["site_admin_name"] = $GLOBALS["site_settings"]["admin"];
}

// debug level
if (isset($GLOBALS["site_settings"]["debuglevel"])) {
    if ($GLOBALS["site_settings"]["debuglevel"] == 1) {
        $GLOBALS["modera_debug"] = true;
    }
    else if ($GLOBALS["site_settings"]["debuglevel"] == 2) {
        $GLOBALS["modera_debug"] = true;
        $GLOBALS["modera_debugsql"] = true;
    }
    else if ($GLOBALS["site_settings"]["debuglevel"] == 3) {
        $GLOBALS["modera_debug"] = false;
        $GLOBALS["modera_debugsql"] = false;
    }
}

$lan = new Language($database, '');
$language = $lan->lan();

if ($data_settings["userlogin"] == 1) {
    $GLOBALS["userlogin"] = true;
}
else {
    $GLOBALS["userlogin"] = false;
}
if ($data_settings["loginform"] == 1) {
    $GLOBALS["loginform"] = true;
}
else {
    $GLOBALS["loginform"] = false;
}

$access = false;

if ($_COOKIE["ADM_SESS_SID"]) {
    $ses = new Session;
    if ($ses->status == true) {
        $isadmin = true;
    }
    else {
        $isadmin = false;
    }
}

// ########################################
// decodes permission string into array
function decodePermissions($perm_str) {
    $tmp_perm = explode(";", $perm_str);
    $perm = array();

    for ($i = 0; $i < sizeof($tmp_perm); $i++) {
        $tmp_gp = explode(":", $tmp_perm[$i]);
        $tmp_p = explode(",", $tmp_gp[1]);
        $perm[$tmp_gp[0]]["r"] = $tmp_p[0];
        $perm[$tmp_gp[0]]["w"] = $tmp_p[1];
        $perm[$tmp_gp[0]]["d"] = $tmp_p[2];
    }

    return $perm;
}

// ########################################
// checks if permission is granted
function hasPermissions($u_groups, $perm, $type) {
    for ($i = 0; $i < sizeof($u_groups); $i++) {
        if ($perm[$u_groups[$i]][$type]) {
            return true;
        }
    }
    return false;
}

// Admin session exists, grant access to file
if ($isadmin == true) {
    $access = true;
}
else {
    if ($GLOBALS["userlogin"] == true) {
        $GLOBALS["userlogin"] = false;
        $GLOBALS["loginform"] = false;

        $login = 1;
        $usr = new user;
        //$user_logged = $usr->sid;
        $GLOBALS["user_logged"] = $usr->status;
//        $usr->returnUser();
//        $GLOBALS["user_data"] = array(
//            $usr->user,
//            $usr->user_name,
//            $usr->username,
//            $usr->company,
//            $usr->group,
//            $usr->groups
//        );
        unset($usr);
        // Protected area
        if ($user_logged == true) {
            $access = true;

            /* Checking if file has read-permissions for current user */
            /*
            $t_folder = "";
            $t_name = "";
            $t_type = "";

            $t_folder = substr($file, 0, strrpos($file, "/"));

            if (substr($t_folder, 0, 1) != "/") {
                $t_folder = "/" . $t_folder;
            }
            if (substr($t_folder, -1) != "/") {
                $t_folder .= "/";
            }
            $t_folder = str_replace("//", "/", $t_folder);

            $t_name = substr($file, strrpos($file, "/"), strrpos($file, ".") - strrpos($file, "/"));
            if (substr($t_name, 0, 1) == "/") {
                $t_name = substr($t_name, 1);
            }
            $t_name = str_replace("_thumb", "", $t_name);

            $t_type = substr($file, strrpos($file, "."));
            if (substr($t_type, 0, 1) == ".") {
                $t_type = substr($t_type, 1);
            }

            $fsql = "SELECT permissions, owner FROM files WHERE folder = '" . mysql_escape_string($t_folder) . "' AND name = '" . mysql_escape_string($t_name) . "' AND type = '" . mysql_escape_string($t_type) . "'";
            $sq->query($db, $fsql);
            if ($fdata = $sq->nextrow()) {
                $f_perm = decodePermissions($fdata["permissions"]);
                if (!hasPermissions($GLOBALS["user_data"][5], $f_perm, "r") && ($fdata["owner"] != "99" . $GLOBALS["user_data"][0])) {
                    $access = false;
                }
            }
            */
        }
        else {
            $access = false;
        }
    }
    else {
        $access = true;
    }
}


// #########################################################

// No access to file, exit
if ($access == false) {
    exit;
}

// #########################################################

// extract file name and it's extension
preg_match('|^.*/(.+?(?:\.([^\.]+))?)$|', $file, $m = null);
$filename = $m[1];
@$ext = $m[2];

switch(strtolower($ext))
{
   case "pdf": $ctype="application/pdf";                break;
   case "exe": $ctype="application/octet-stream";       break;
   case "zip": $ctype="application/zip";                break;
   case "doc": $ctype="application/msword";             break;
   case "xls": $ctype="application/vnd.ms-excel";       break;
   case "ppt": $ctype="application/vnd.ms-powerpoint";  break;
   case "gif": $ctype="image/gif";                      break;
   case "png": $ctype="image/png";                      break;
   case "jpg": $ctype="image/jpg";                      break;
   case "txt": $ctype="text/plain";                     break;
   case "xml": $ctype="text/xml";                       break;
   case "htm": $ctype="text/html";                      break;
   case "html": $ctype="text/html";                     break;
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
//  header("Content-Disposition: $disposition; filename=\"".trim(htmlentities($filename))."\"");
  header("Content-Description: ".trim(htmlentities($filename)));
  header("Content-Length: ".(string)(filesize($file)));

// Open the file
$fd=fopen($file,'r');
fpassthru($fd);
header("Connection: close");
exit();
