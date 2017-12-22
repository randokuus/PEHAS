<?php
/**
 * Common header for modules admin files
 *
 * @verison $Revision: 541 $
 */

require_once('../class/config.php');
require_once(SITE_PATH . '/class/common.php');

if ($GLOBALS['modera_debug'] == true) error_reporting(E_ALL ^ E_NOTICE);
$old_error_handler = set_error_handler('userErrorHandler');

require_once(SITE_PATH . '/class/'.DB_TYPE.'.class.php');
require_once(SITE_PATH . '/class/admin.session.class.php');
require_once(SITE_PATH . '/class/admin.language.class.php');
require_once(SITE_PATH . '/class/adminfields.class.php');  // form fields definitions for admin
require_once(SITE_PATH . '/class/ModeraTranslator.php');
require_once(SITE_PATH . '/class/Database.php');

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

// init language object
$lan = new AdminLanguage($database, $language);
$language2 = $lan->interfaceLanguage($language2);
$language = $lan->lan();
load_site_name($language);

// init user permissions
$perm = new Rights($group, $user, 'module', true);

// init Text object(s) for this page, multiple can be used
$translator =& ModeraTranslator::instance($language2, 'admin_general');

// general parameters (templates, messages etc.)
$general = array(
	'debug' => $GLOBALS['modera_debug'],
	'template_main' => 'tmpl/admin_main_module.html',
	'template_form' => 'tmpl/admin_form.html',
	'template_list' => 'tmpl/admin_list.html',
	'add_text' => $translator->tr('add_text'),
	'modify_text' => $translator->tr('modify_text'),
	'delete_text' => $translator->tr('delete_text'),
	'required_error' => $translator->tr('required_error'),
	'delete_confirmation' => $translator->tr('delete_confirmation'),
	'backtolist' => $translator->tr('backtolist'),
	'current' => $translator->tr('current'),
	'error' => $translator->tr('error'),
	'filter' => $translator->tr('filter'),
	'display' => $translator->tr('display'),
	'display1' => $translator->tr('display1'),
	'prev' => $translator->tr('prev'),
	'next' => $translator->tr('next'),
	'pages' => $translator->tr('pages'),
	'button' => $translator->tr('button'),
	'max_entries' => 50,
);

unset($translator);

// array of tabs
// values should be array with 2 elements: 1 - link text, 2 - link url
$tabs = array();