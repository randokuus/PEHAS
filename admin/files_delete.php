<?php
// Extract user request parameters
foreach ($_GET as $k => $v) $$k = $v;
foreach ($_POST as $k => $v) $$k = $v;

//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");

if ($GLOBALS["modera_debug"] == true) error_reporting(E_ALL ^ E_NOTICE);

$old_error_handler = set_error_handler("userErrorHandler");

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/admin.language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/admin.class.php");             // administration main object
require_once(SITE_PATH . "/class/adminfields.class.php"); // form fields definitions for admin
//require_once(SITE_PATH . "/class/template.class.php");  // site default template object
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
    echo '<body onLoad= "top.document.location=\'login.php\'">';
exit;
}

// init systemlog object
$log = &SystemLog::instance($database);

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

//$perm = new Rights($group, $user, "root", true);
if ($access) {
    $perm = new Rights($group, $user, "root", true);
    $perm->Access (0, 0, "m", "");
}
else {
    $perm = new Rights($group, $user, "module", true);
    $perm->Access (0, $id, "d", "fileaccess");
}

$sel_language = "EN";

// init Text object for this page
$txt = new Text($language2, "admin_general");
$txtf = new Text($language2, "admin_files");

// ##############################################################
// ##############################################################

if ($do == "delete" && $file != "") {

    if (false !== strpos($file, '..') || false !== strpos($folder, '.')) exit();

    if (false !== strpos($file, '?')) {
        $back = substr($file,(strrpos($file, '?')+1));
        $file = substr($file,0,strrpos($file, '?'));
    }

    $file = addslashes($file);

    if ($access == "img") {
        $rootfolder = $GLOBALS["directory"]["img"];
    }
    else if ($access == "tmpl") {
        $rootfolder = $GLOBALS["directory"]["tmpl"];
    }
    else {
        $rootfolder = $GLOBALS["directory"]["upload"];
    }

    if (false !== strpos($file, '/')) {
        $name = substr($file, (strrpos($file, '/')+1), (strrpos($file,'.')-strrpos($file, '/')-1));
        $type = substr($file, (strrpos($file,'.')+1), 4);
    }
    else {
        $name = substr($file, 0, strrpos($file,'.'));
        $type = substr($file, (strrpos($file,'.')+1));
    }

    $to_unlink = "/{$rootfolder}/{$folder}{$name}.$type";
    if (@file_exists(SITE_PATH . $to_unlink)) {
        if (!@unlink(SITE_PATH . $to_unlink)) {
            trigger_error("File delete failed. Check file/folder permissions", E_USER_ERROR);
            exit;
        } else {
            // save log about this action
            $log->log('file_manager', "File $to_unlink deleted by " . $GLOBALS['ses']->getUsername());
        }
    }

    $to_unlink = "/$rootfolder/{$folder}{$name}_thumb.$type";
    if (@file_exists(SITE_PATH . $to_unlink)) {
        if (!@unlink(SITE_PATH . $to_unlink)) {
            trigger_error("File delete failed. Check file/folder permissions", E_USER_ERROR);
            exit;
        } else {
            // save log about this action
            $log->log('file_manager', "Thumbnail File $to_unlink deleted by "
                . $GLOBALS['ses']->getUsername());
        }

    }

    if ($returnto != "") {
        //Header("Location: $returnto");
        redirect(substr($returnto, strpos($returnto, "admin")));
        exit;
    }
    else {
        echo '<body onLoad= "parent.frames.left.cleanInfo(); top.main.right.document.location=\'browser.php?folder='.urlencode($folder).'\'">';
        exit;
    }

    //echo '<body onLoad= "parent.frames.left.cleanInfo(); top.main.right.document.location=\'browser.php?' . urlencode($back) . '\'">';
    //exit;

}
else {
    exit;
}
