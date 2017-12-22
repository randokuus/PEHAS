<?php
//error_reporting(E_ALL);
require_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/admin.session.class.php");

// ######### BEGIN INIT PART  #########

// init session object
$ses = new Session();
$logged = $ses->returnID();
$user = $ses->returnUser();

if ($logged) {
	$ses->logOut();
	echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
	echo '<body onLoad= "top.document.location=\'' . base_site_path() . 'admin/\'">';

}
else {
	echo '<META HTTP-EQUIV="refresh" CONTENT="0">';
	echo '<body onLoad= "top.document.location=\'login.php\'">';
}
