<?php
include_once("../class/config.php");
require_once(SITE_PATH . "/class/common.php");
$old_error_handler = set_error_handler("userErrorHandler");
hokusPokus();

require_once(SITE_PATH . "/class/".DB_TYPE.".class.php");
require_once(SITE_PATH . "/class/language.class.php");
require_once(SITE_PATH . "/class/text.class.php");
require_once(SITE_PATH . "/class/templatef.class.php");
require_once(SITE_PATH . "/class/Database.php");

// ##############################################################
// init main variables

$db = new db;
$db->connect();
$sq = new sql;

$sq->con = $db->con;
$database = new Database($sq);
load_site_settings($database);
$data = $data_settings = $site_settings;

// init language object
$lan = new Language($database, '');
$language = $lan->lan();
$GLOBALS["language"] = &$language;

$separator = ';';

$endTime = mktime(0, 0, 0, date('n'), 1, date('Y')) - 1;
$begTime = mktime(0, 0, 0, date('n', $endTime), 1, date('Y', $endTime));

$begDate = date('Y-m-d', $begTime);
$endDate = date('Y-m-d', $endTime) . ' 23:59:59';

$reportPath  = "/reports/seb/";
$reportName = 'seb_cards_' . strtolower(date('M', $begTime)) . '_' . date('Y', $begTime) . '.csv';

$columns = array(
    'Isic Number',
    'Person Name First',
    'Person Name Last',
    'Person Number',
    'Person Birthday',
    'School Name',
    'Adddate',
    'Received Date',
    'Card Type',
    'Status Name',
    'Active'
);

$str = implode($separator, $columns) . "\n";

$sql = "
SELECT
    c.isic_number,
    c.person_name_first,
    c.person_name_last,
    c.person_number,
    c.person_birthday,
    s.name AS school_name,
    c.adddate,
    c.received_date,
    t.name AS 'type_name',
    bs.name AS 'bank_status_name',
    c.active
FROM
    `module_isic_card` AS c,
    `module_isic_school` as s,
    `module_isic_card_type` as t,
    `module_isic_bank_status` as bs
WHERE
  c.school_id = s.id and
  c.type_id = t.id and
  c.bank_status_id = bs.id and
  c.kind_id = 2 and
  c.received_date >= ? and
  c.received_date <= ?
";

$res = $database->query($sql, $begDate, $endDate);
while ($data = $res->fetch_assoc()) {
    $row = array();
    $row[] = $data['isic_number'];
    $row[] = $data['person_name_first'];
    $row[] = $data['person_name_last'];
    $row[] = $data['person_number'];
    $row[] = $data['person_birthday'];
    $row[] = $data['school_name'];
    $row[] = $data['adddate'];
    $row[] = $data['received_date'];
    $row[] = $data['type_name'];
    $row[] = $data['bank_status_name'];
    $row[] = $data['active'];
    $str .= implode($separator, $row) . "\n";
}

echo 'Generating new report: ' . $reportPath . $reportName . "\n";

if (file_put_contents(SITE_PATH . '/upload' . $reportPath . $reportName, $str)) {
    $database->query(
        "INSERT INTO `files` (`type`, `name`, `folder`, `add_date`) VALUES (?, ?, ?, ?)",
        'csv', str_replace('.csv', '', $reportName), $reportPath, $database->now()
    );
    echo "done";
} else {
    echo 'ERROR';
}
