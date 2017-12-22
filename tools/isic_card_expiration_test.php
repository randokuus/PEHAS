<?php
include("../class/config.php");
require(SITE_PATH . "/class/common.php");

$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require(SITE_PATH . "/class/".DB_TYPE.".class.php");
require(SITE_PATH . "/class/language.class.php");
require(SITE_PATH . "/class/text.class.php");
require(SITE_PATH . "/class/templatef.class.php");
require(SITE_PATH . "/class/Database.php");
require_once(SITE_PATH . "/class/admin.session.class.php");
require_once(SITE_PATH . "/class/IsicCommon.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;
$sq2 = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$ic = IsicCommon::getInstance();

$expiration = $_REQUEST['expiration'] ? $_REQUEST['expiration'] : "2011-12-31";
$card_type = $_REQUEST['card_type'] ? $_REQUEST['card_type'] : 14;
$b_y = $_REQUEST['b_y'] ? $_REQUEST['b_y'] : 2010;
$e_y = $_REQUEST['e_y'] ? $_REQUEST['e_y'] : 2012;

$card_type_data = $ic->getCardTypeRecord($card_type);
$exp_type_name = array("End of the year", "Same month");
$exp_repl_card = array("New", "Old");


echo "<form action=\"{$_SERVER['PHP_SELF']}\">\n";
echo "Old exp.: <input size=\"10\" maxlength=\"10\" type=\"text\" name=\"expiration\" value=\"{$expiration}\">";
echo "Card type: <input size=\"2\" maxlength=\"2\" type=\"text\" name=\"card_type\" value=\"{$card_type}\">";
echo "Beg year: <input size=\"4\" maxlength=\"4\" type=\"text\" name=\"b_y\" value=\"{$b_y}\">";
echo "End year<input size=\"4\" maxlength=\"4\" type=\"text\" name=\"e_y\" value=\"{$e_y}\">";
echo "<input type=\"submit\" value=\"Calc\">";
echo "</form>";

echo "Old expiration: " . $expiration . "<br>\n";
echo "Card type: " . utf8_decode($card_type_data['name']) . "<br>\n";
echo "Exp. years: " . $card_type_data['expiration_year'] . "<br>\n";
echo "Exp. break: " . $card_type_data['expiration_break'] . "<br>\n";
echo "Exp. break day: " . $card_type_data['expiration_break_day'] . "<br>\n";
echo "Exp. type: " . $exp_type_name[$card_type_data['expiration_type']] . "<br>\n";
echo "Prolong limit: " . $card_type_data['prolong_limit'] . " (" . $ic->calcExpirationProlongLimit($expiration, $card_type_data['prolong_limit']) . ")" . "<br>\n";
echo "Repl. card exp.: " . $exp_repl_card[$card_type_data['expiration_repl_card']] . "<br>\n";

echo "<table border=\"1\">\n";
echo "<tr>\n";
echo "<th width=\"33%\">Curr. date</th>\n";
echo "<th width=\"33%\">Calc. exp. (new card)</th>\n";
//echo "<th width=\"33%\">Calc. exp. (don't check repl. exp. card)</th>\n";
echo "</tr>\n";

for ($year = $b_y; $year <= $e_y; $year++) {
    for ($mon = 1; $mon <= 12; $mon++) {
        for ($day = 1; $day <= 28; $day += 15) {
            $ic->setCurrentTime(mktime(0, 0, 0, $mon, $day, $year));
            echo "<tr>\n"; 
            echo "<td>" . date("Y-m-d", $ic->getCurrentTime()) . "</td>\n";
            echo "<td>" . $ic->getCardExpiration($card_type, $expiration, true) . "</td>\n";
  //          echo "<td>" . $ic->getCardExpiration($card_type, $expiration, false) . "</td>\n";
            echo "</tr>\n"; 
        }
    }
}

echo "</table>\n";
