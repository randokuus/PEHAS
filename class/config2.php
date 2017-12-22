<?php
/**
 * Modera.net configuration file, publicly accessable
 * Possible parameters inside this file are (by means of examples):
 * <code>
 * $GLOBALS["templates_EN"][1][2][40] = "content_forum" - filename (wihtout .html extension) to a template, language code EN and template unqiue ID 50
 * $GLOBALS["temp_desc_EN"][1][40] = "Forum" - description for the previous template
 * $GLOBALS["modules"][] = "forum" - Include module Forum
 * $GLOBALS["modules_sub"]["forum"] = array("forum", "forum_text") - Admin sub-admins for module Forum
 * </code>
 * @package modera_net
 * @access public
 * @version 2.0
 */

// BEGIN INSTALLED MODULES AND FUNCTIONALITY

$GLOBALS["modules_sub"]["user"][] = 'ehis_log';
$GLOBALS["modules_sub"]["user"][] = 'ehl_log';

$GLOBALS["templates_EN"][1][2][9] = "content_plain";
$GLOBALS["temp_desc_EE"][1][9] = "Sisu - tavaline";
$GLOBALS["temp_desc_EN"][1][9] = "Content - plain";

$GLOBALS["templates_EN"][1][2][98] = "content_profile";
$GLOBALS["temp_desc_EE"][1][98] = "Kasutajaprofiil";
$GLOBALS["temp_desc_EN"][1][98] = "User profile";

$GLOBALS["templates_EN"][1][2][130] = "content_messages";
$GLOBALS["temp_desc_EE"][1][130] = "Teated";
$GLOBALS["temp_desc_EN"][1][130] = "Messages";
$GLOBALS["modules"][] = "messages";

$GLOBALS["templates_EN"][1][2][50] = "content_poll";
$GLOBALS["temp_desc_EE"][1][50] = "Küsitlus";
$GLOBALS["temp_desc_EN"][1][50] = "Poll";
$GLOBALS["modules"][] = "poll";
$GLOBALS["modules_sub"]["poll"] = array("poll", "poll_question");

$GLOBALS["modules"][] = "calendar";

$GLOBALS["templates_EN"][1][2][120] = "content_files";
$GLOBALS["temp_desc_EE"][1][120] = "Failid";
$GLOBALS["temp_desc_EN"][1][120] = "Files";
$GLOBALS["modules"][] = "filemanager";

$GLOBALS["templates_EN"][1][2][200] = "content_projects";
$GLOBALS["temp_desc_EE"][1][200] = "Projektid";
$GLOBALS["temp_desc_EN"][1][200] = "Projects";
$GLOBALS["templates_EN"][1][2][201] = "content_tasks";
$GLOBALS["temp_desc_EE"][1][201] = "Projektid/taskid";
$GLOBALS["temp_desc_EN"][1][201] = "Projects/tasks";

$GLOBALS["modules"][] = "projects";
$GLOBALS["modules_sub"]["projects"] = array("projects", "projects_tasks");

$GLOBALS["modules"][] = "imcontroller";
$GLOBALS["modules_sub"]["imcontroller"] = array("imcontroller_config","imcontroller_users", "imcontroller_groups", "imcontroller_chat");

$GLOBALS["modules"][] = "iforum";
$GLOBALS["modules_sub"]["iforum"] = array("iforum_sections", "iforum_forums", "iforum_threads", "iforum_posts");
$GLOBALS["templates_EN"][1][2][701] = "content_iforum";
$GLOBALS["temp_desc_EN"][1][701] = "iforum";

$GLOBALS["modules"][] = "links";
$GLOBALS["modules_sub"]["links"] = array("links", "links_groups");

$GLOBALS["modules"][] = "clock";


$GLOBALS["modules"][] = "isic";
$GLOBALS["modules_sub"]["isic"] = array(
    "isic_school",
    "isic_application",
    "isic_card",
    "isic_application_state",
    "isic_card_state",
    "isic_card_range",
    "isic_card_number",
    "isic_card_number_import",
    "isic_card_kind",
    "isic_card_type",
    "isic_card_language",
    "isic_card_create",
    "isic_card_export",
    "isic_application_log",
    "isic_card_log",
    "isic_bank",
    "isic_card_status",
    "isic_chip_numbers",
    "isic_bypass_lock",
    "isic_bypass_event",
    "isic_card_users",
    "isic_card_status_school",
    "isic_card_type_school",
    "isic_bank_school",
    "isic_bank_type",
    "isic_bank_status",
    "isic_card_type_school_cost",
    "isic_application_reject_reason",
    "isic_application_type",
    "isic_application_cost",
    "isic_application_cost_school",
    "isic_service_provider",
    "isic_service_location",
    "isic_service_device",
    "isic_service_event",
    "isic_payment",
    "isic_payment_deposit",
    "isic_card_validities",
    "isic_global_settings",
    "isic_currency",
    "isic_card_transfer",
    "isic_card_import",
    "isic_card_producers",
    "isic_newsletters",
    "isic_newsletters_orders",
    "isic_card_send_type",
    "isic_card_send_cost",
    "isic_card_shipment",
    "isic_card_delivery",
    "isic_school_compensation",
    "isic_school_compensation_user",
    "isic_payment_log",
    "isic_card_data_sync",
    "isic_card_data_sync_ccdb",
    "isic_region",
    'messages_send_queue',
    'messages_send_log',
    'isic_school_sms_credit',
    'isic_school_assembler',
);
$GLOBALS["templates_EN"][1][2][801] = "content_isic";
$GLOBALS["temp_desc_EN"][1][801] = "ISIC - All cards";

$GLOBALS["modules"][] = "isic_pic";
$GLOBALS["templates_EN"][1][2][802] = "content_isic_pic";
$GLOBALS["temp_desc_EN"][1][802] = "ISIC - Picture import";

$GLOBALS["templates_EN"][1][2][803] = "content_isic_add";
$GLOBALS["temp_desc_EN"][1][803] = "ISIC - Add card";

$GLOBALS["templates_EN"][1][2][804] = "content_isic_ordered";
$GLOBALS["temp_desc_EN"][1][804] = "ISIC - Ordered cards";

$GLOBALS["templates_EN"][1][2][805] = "content_isic_void";
$GLOBALS["temp_desc_EN"][1][805] = "ISIC - Void cards";

$GLOBALS["templates_EN"][1][2][806] = "content_isic_addmass";
$GLOBALS["temp_desc_EN"][1][806] = "ISIC - CSV Import";

$GLOBALS["templates_EN"][1][2][807] = "content_isic_confirm_user";
$GLOBALS["temp_desc_EN"][1][807] = "ISIC - User-confirmed cards";

$GLOBALS["templates_EN"][1][2][808] = "content_isic_requested";
$GLOBALS["temp_desc_EN"][1][808] = "ISIC - Requested";

$GLOBALS["templates_EN"][1][2][809] = "content_isic_active";
$GLOBALS["temp_desc_EN"][1][809] = "ISIC - Active";

$GLOBALS["templates_EN"][1][2][810] = "content_isic_confirm_user_not";
$GLOBALS["temp_desc_EN"][1][810] = "ISIC - User-non-confirmed cards";

$GLOBALS["templates_EN"][1][2][811] = "content_isic_first_time";
$GLOBALS["temp_desc_EN"][1][811] = "ISIC - First-time";

$GLOBALS["templates_EN"][1][2][812] = "content_isic_my_card";
$GLOBALS["temp_desc_EN"][1][812] = "ISIC - My Card";

$GLOBALS["modules"][] = "isic_bypass";

$GLOBALS["templates_EN"][1][2][820] = "content_isic_bypass";
$GLOBALS["temp_desc_EN"][1][820] = "ISIC - Bypass logs";

$GLOBALS["modules"][] = "isicriksweb";

$GLOBALS["templates_EN"][1][2][830] = "content_isic_riksweb";
$GLOBALS["temp_desc_EN"][1][830] = "ISIC - RiksWeb login";

$GLOBALS["modules"][] = "isic_report";

$GLOBALS["templates_EN"][1][2][840] = "content_isic_report_ordered_cards";
$GLOBALS["temp_desc_EN"][1][840] = "ISIC - Reports - Ordered cards";

$GLOBALS["templates_EN"][1][2][841] = "content_isic_report_returned_cards";
$GLOBALS["temp_desc_EN"][1][841] = "ISIC - Reports - Returned cards";

$GLOBALS["templates_EN"][1][2][842] = "content_isic_report_card_log";
$GLOBALS["temp_desc_EN"][1][842] = "ISIC - Reports - Card log";

$GLOBALS["templates_EN"][1][2][843] = "content_isic_report_card_data_comm_cost";
$GLOBALS["temp_desc_EN"][1][843] = "ISIC - Reports - Card Data Commu. Cost";

$GLOBALS["templates_EN"][1][2][844] = "content_isic_report_user_status_changes";
$GLOBALS["temp_desc_EN"][1][844] = "ISIC - Reports - User status changes";

$GLOBALS["templates_EN"][1][2][845] = "content_isic_report_messages_send_log";
$GLOBALS["temp_desc_EN"][1][845] = "ISIC - Reports - Messages Send Log";

$GLOBALS["modules"][] = "isic_experimental";
$GLOBALS["templates_EN"][1][2][901] = "content_isic_experimental";
$GLOBALS["temp_desc_EN"][1][901] = "ISIC - Experimental";

$GLOBALS["modules"][] = "isic_user";
$GLOBALS["templates_EN"][1][2][850] = "content_isic_user";
$GLOBALS["temp_desc_EE"][1][850] = "ISIC - Kasutajad";
$GLOBALS["temp_desc_EN"][1][850] = "ISIC - Users";

$GLOBALS["templates_EN"][1][2][851] = "content_isic_user_add";
$GLOBALS["temp_desc_EN"][1][851] = "ISIC - Add user";

$GLOBALS["templates_EN"][1][2][852] = "content_isic_user_addmass";
$GLOBALS["temp_desc_EN"][1][852] = "ISIC - User CSV Import";

$GLOBALS["modules"][] = "isic_user_pic";
$GLOBALS["templates_EN"][1][2][853] = "content_isic_user_pic";
$GLOBALS["temp_desc_EN"][1][853] = "ISIC - User Picture import";

$GLOBALS["modules"][] = "isic_application";
$GLOBALS["templates_EN"][1][2][860] = "content_isic_application";
$GLOBALS["temp_desc_EE"][1][860] = "ISIC - Taotlused";
$GLOBALS["temp_desc_EN"][1][860] = "ISIC - Applications";

$GLOBALS["templates_EN"][1][2][861] = "content_isic_application_add";
$GLOBALS["temp_desc_EN"][1][861] = "ISIC - Add application";

$GLOBALS["templates_EN"][1][2][862] = "content_isic_application_addmass";
$GLOBALS["temp_desc_EN"][1][862] = "ISIC - Application CSV Import";

$GLOBALS["modules"][] = "isic_application_pic";
$GLOBALS["templates_EN"][1][2][863] = "content_isic_application_pic";
$GLOBALS["temp_desc_EN"][1][863] = "ISIC - Application Picture import";

$GLOBALS["templates_EN"][1][2][864] = "content_isic_application_add_user";
$GLOBALS["temp_desc_EN"][1][864] = "ISIC - Add Application - Steps";

$GLOBALS["templates_EN"][1][2][865] = "content_isic_application_add_user_hidden_school";
$GLOBALS["temp_desc_EN"][1][865] = "ISIC - Add Application - Steps (hidden school)";

$GLOBALS["modules"][] = "isic_card";
$GLOBALS["templates_EN"][1][2][870] = "content_isic_card";
$GLOBALS["temp_desc_EE"][1][870] = "ISIC - Kaardid";
$GLOBALS["temp_desc_EN"][1][870] = "ISIC - Cards";

$GLOBALS["modules"][] = "isic_user_status";
$GLOBALS["templates_EN"][1][2][880] = "content_isic_user_status";
$GLOBALS["temp_desc_EE"][1][880] = "ISIC - Kasutajastaatused";
$GLOBALS["temp_desc_EN"][1][880] = "ISIC - User Statuses";

$GLOBALS["templates_EN"][1][2][881] = "content_isic_user_status_add";
$GLOBALS["temp_desc_EE"][1][881] = "ISIC - Lisa kasutajastaatus";
$GLOBALS["temp_desc_EN"][1][881] = "ISIC - Add User Status";

$GLOBALS["templates_EN"][1][2][890] = "content_freeform";
$GLOBALS["temp_desc_EE"][1][890] = "Sisu - vabalt sisestatav";
$GLOBALS["temp_desc_EN"][1][890] = "Content - freeform";

$GLOBALS["modules"][] = "isic_school";

/*INSERT_HERE*/

// ###############################################

// END
// ###############################################

