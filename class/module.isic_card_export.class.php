<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicCharacterValidator.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicNameSplitter.php");

class isic_card_export {
    const VALID_SYMBOL_CODES_PERSON_NAME = '32,39,45,65-90,96-122,138,142-146,150,151,154,158,192-214,216-246,248-255';
    const VALID_SYMBOL_CODES_SCHOOL_NAME = '32,39,45,46,48-57,65-90,96-122,138,142-146,150,151,154,158,192-214,216-246,248-255';
    const VALID_SYMBOL_CODES_PERSON_NUMBER = '45,48-57,65-90';

    /**
     * Database instance
     *
     * @var Database
     * @access protected
     */
    var $db;

    /**
     * End of record marker
     *
     * @var string
     * @access protected
     */
    var $eor_marker = "#END#\r\n";

    /**
     * Target encoding
     *
     * @var string
     * @access protected
     */

    var $target_encoding = "Windows-1252";

    /**
     * Source encoding
     *
     * @var string
     * @access protected
     */

    var $source_encoding = "UTF-8";

    /**
     * Card design file names for Card type - school combinations
     *
     * @var array
     * @access protected
     */

    var $design_card_type_school = false;

    /**
     * Array with all of the different logo-field names
     *
     * @var array
     * @access protected
     */

    var $logo_fields = array("logo_front", "logo_back", "design_front", "design_back");

    private $isicDbCardShipments = false;
    private $validationFields = array(
        'person_name_first' => null,
        'person_name_last' => null,
        'school_name' => null,
        'person_number' => null
    );

    /**
     * @var IsicNameSplitter
     */
    private $isicNameSplitter;

    /**
     * Class constructor
     *
     * @global $GLOBALS['site_settings']['template']
     * @global $GLOBALS['language']
     * @global $GLOBALS['database']
     */
    function __construct() {
        $this->db = &$GLOBALS['database'];
        $this->design_card_type_school = $this->getCardTypeSchoolDesign();
        $this->isicDbCardShipments = IsicDB::factory('CardShipments');
        $this->isicNameSplitter = new IsicNameSplitter();
        $this->initFieldValidators();
    }

    public function initFieldValidators()
    {
        $nameValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_PERSON_NAME);
        $schoolNameValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_SCHOOL_NAME);
        $personNumberValidator = new IsicCharacterValidator(self::VALID_SYMBOL_CODES_PERSON_NUMBER);
        $this->validationFields['person_name_first'] = $nameValidator;
        $this->validationFields['person_name_last'] = $nameValidator;
        $this->validationFields['person_number'] = $personNumberValidator;
        $this->validationFields['school_name'] = $schoolNameValidator;
    }

    /**
     * Exports all of the cards into data-file
     *
     * @param array $pic_list array for returning picture names
     * @param array $card_ids array of card id's to export
     * @param array $logo_list array for returning school logos and design-file names
     * @param int $order_id order id from order table (module_isic_card_transfer)
     * @return string header and body part in special card-file format
    */
    function exportCards(&$pic_list, $card_ids = false, &$logo_list, $order_id) {
        if (is_array($card_ids) && sizeof($card_ids) > 0) {
            $r = &$this->db->query('
                SELECT
                    `module_isic_card`.`id`,
                    `module_isic_card`.`pic`
                FROM
                    `module_isic_card`
                WHERE
                    `module_isic_card`.`exported` = ? AND
                    `module_isic_card`.`confirm_admin` = 1 AND
                    `module_isic_card`.`id` IN (!@)
                ', '0000-00-00 00:00:00', $card_ids);

            $t_record = '';
            $row_count = 0;
            $export_timestamp = date("Y-m-d H:i:s");
            while ($row = $r->fetch_assoc()) {
                $row_count++;
                $t_record .= $this->createRecord($row['id'], $row_count, $export_timestamp, $logo_list);
                if ($row['pic']) {
                    $pic_list[] = $row['pic'];
                }
                $r2 = &$this->db->query('
                UPDATE
                    `module_isic_card`
                SET
                    `module_isic_card`.`exported` = ?,
                    `module_isic_card`.`order_id` = !
                WHERE
                    `module_isic_card`.`id` = !
                ', $export_timestamp, $order_id, $row['id']);
            }
            if ($t_record) {
                return $t_record;
            }
        }
        return ''; // no records to export
    }

    function getCommonSqlSelectFromJoin() {
        $sql = "
            SELECT
                `module_isic_card`.*,
                IF(`module_isic_school`.`id`, IF(`module_isic_school`.`card_name` <> '', `module_isic_school`.`card_name`, `module_isic_school`.`name`), '') AS school_name,
                IF(`module_isic_school`.`id`, `module_isic_school`.`ehis_code`, '') AS school_ehis_code,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address1`, '') AS school_address1,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address2`, '') AS school_address2,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address3`, '') AS school_address3,
                IF(`module_isic_school`.`id`, `module_isic_school`.`address4`, '') AS school_address4,
                IF(`module_isic_school`.`id`, `module_isic_school`.`email`, '') AS school_email,
                IF(`module_isic_school`.`id`, `module_isic_school`.`phone`, '') AS school_phone,
                IF(`module_isic_school`.`id`, `module_isic_school`.`web`, '') AS school_web,
                IF(`module_isic_school`.`id`, `module_isic_school`.`joined`, 0) AS school_joined,
                IF(`module_isic_bank`.`id`, `module_isic_bank`.`name`, '') AS bank_name,
                IF(`module_isic_card_kind`.`id`, `module_isic_card_kind`.`name`, '') AS card_kind_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`prefix`, '') AS card_type_prefix,
                IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`tryb_export_name_split`, '') AS tryb_export_name_split,
                IF(`module_isic_card_delivery`.`id`, `module_isic_card_delivery`.`shipment_id`, 0) AS `card_shipment_id`
            FROM
                `module_isic_card`
            LEFT JOIN
                `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
            LEFT JOIN
                `module_isic_card_kind` ON `module_isic_card`.`kind_id` = `module_isic_card_kind`.`id`
            LEFT JOIN
                `module_isic_bank` ON `module_isic_card`.`bank_id` = `module_isic_bank`.`id`
            LEFT JOIN
                `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
            LEFT JOIN
                `module_isic_card_delivery` ON `module_isic_card`.`delivery_id` = `module_isic_card_delivery`.`id` AND
                `module_isic_card_delivery`.`active` = 1
        ";
        return $sql;
    }

    /**
     * Exports all of the cards into array
     *
     * @param int $card_type card type
     * @param int $school_id school id
     * @param array $card_ids array of card id-s
     * @return array of card records to be export
    */
    function exportCardsArray($card_type = 0, $school_id = 0, $card_ids = false) {
        $card_list = array();
        if (!$card_type) {
            $card_type = 0;
        }
        if (!$school_id) {
            $school_id = 0;
        }

        if (is_array($card_ids) && sizeof($card_ids) > 0) {
            $r = &$this->db->query(
                $this->getCommonSqlSelectFromJoin() .
                '
                WHERE
                    `module_isic_card`.`exported` = ? AND
                    `module_isic_card`.`confirm_admin` = 1 AND
                    `module_isic_card`.`id` IN (!@)
                ', '0000-00-00 00:00:00', $card_ids);
        } else {
            $r = &$this->db->query(
                $this->getCommonSqlSelectFromJoin() .
                '
                WHERE
                    (`module_isic_card`.`type_id` = ! OR ! = 0) AND
                    (`module_isic_card`.`school_id` = ! OR ! = 0) AND
                    `module_isic_card`.`confirm_admin` = 1 AND
                    `module_isic_card`.`exported` = ?
                ', $card_type, $card_type, $school_id, $school_id, '0000-00-00 00:00:00');
        }

        while ($row = $r->fetch_assoc()) {
            $card_list[] = $row;
        }
        return $card_list;
    }

    /**
     * Creates a record of a card file
     *
     * @param integer $id id of a card-table line
     * @param integer $row_count row counter
     * @param string $export_timestamp timestamp in form of yyyy-mm-dd hh:mm:ss
     * @param array $logo_list array for returning school logos and design-file names
     * @return string single line for card-file
    */
    function createRecord($id = 0, $row_count = 0, $export_timestamp = '', &$logo_list) {

        $r = &$this->db->query(
            $this->getCommonSqlSelectFromJoin() .
            "
            WHERE
                `module_isic_card`.`id` = !
            ", $id);

        $t_row = '';

        while ($row = $r->fetch_assoc()) {
            $t_design = $this->design_card_type_school[$row["type_id"]][$row["school_id"]];
            if (is_array($t_design)) {
                // in case of front of back-side designs present, then logos will not be used
                if ($t_design["design_front"] || $t_design["design_back"]) {
                    $row["design_front"] = $t_design["design_front"];
                    $row["design_back"] = $t_design["design_back"];
                } elseif ($t_design["logo_front"] || $t_design["logo_back"]) {
                    $row["logo_front"] = $t_design["logo_front"];
                    $row["logo_back"] = $t_design["logo_back"];
                }
            }

            // adding logo fields into array if not yet added
            foreach ($this->logo_fields as $logo_field) {
                if ($row[$logo_field] && !in_array($row[$logo_field], $logo_list)) {
                    $logo_list[] = $row[$logo_field];
                }
            }

            $row["export_timestamp"] = $export_timestamp;
            $row["row_count"] = $row_count;
            $t_row .= str_pad($row_count, 6, '0', STR_PAD_LEFT);
            $t_row .= $this->createModuleADMN($row);
            $t_row .= $this->createModuleCARD($row);
            $t_row .= $this->createModuleMAGN($row);
            $t_row .= $this->createModuleRFID($row);
            $t_row .= $this->createModuleCARR($row);
            $t_row .= $this->createModuleSHIP($row);
        }

        $t_row .= $this->eor_marker;
        return $t_row;
    }

    /**
     * Returns the list of design-files for card-type-school combinations
     *
     * @return array of card-type-schools with design-file names
    */
    function getCardTypeSchoolDesign() {

        $r = &$this->db->query("
            SELECT
                `module_isic_card_type_school`.*
            FROM
                `module_isic_card_type_school`
            ");

        $t_design = array();

        while ($row = $r->fetch_assoc()) {
            if ($row["logo_front"] || $row["logo_back"] || $row["design_front"] || $row["design_back"]) {
                $t_design[$row["type_id"]][$row["school_id"]] = array(
                    "logo_front" => str_replace("_thumb.", ".", $row["logo_front"]),
                    "logo_back" => str_replace("_thumb.", ".", $row["logo_back"]),
                    "design_front" => str_replace("_thumb.", ".", $row["design_front"]),
                    "design_back" => str_replace("_thumb.", ".", $row["design_back"]),
                );
            }
        }

        return $t_design;
    }

    /**
     * Formats given string according to given parameters
     *
     * @param string $str string to format
     * @param int $str_len the length we want this string to be
     * @param string $pad_str padding string
     * @param int $pad_typ type of the padding
     * @param boolean $str_upr weather we should convert string to uppercase

     * @return string formatted string
    */
    function formatString($str = '', $str_len = 0, $pad_str = '', $pad_typ = 0, $str_upr = false)
    {
        $f_str = $str;
        if ($str_upr) {
            $f_str = mb_convert_case($f_str, MB_CASE_UPPER, $this->source_encoding);
        }
        $f_str = mb_convert_encoding($f_str, $this->target_encoding, $this->source_encoding);
        $f_str = substr($f_str, 0, $str_len);
        if (strlen($pad_str)) {
            $f_str = str_pad($f_str, $str_len, $pad_str, $pad_typ);
        }
        return $f_str;
    }

    /**
     * Creates Administration Data module part of a card record
     * Total length of the record: 49 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleADMN($data = false) {
        $t_row = "";
        // Card type code
        $t_row .= $this->formatString($data['card_type_prefix'], 3, ' ', STR_PAD_RIGHT, true);
        // Card type name
        $t_row .= $this->formatString($data['card_type_name'], 30, ' ', STR_PAD_RIGHT, true);
        // Order creation timestamp
        $t_row .= $this->formatString(date("d.m.Y_H:i", strtotime($data['export_timestamp'])), 16, ' ', STR_PAD_RIGHT, false);
        // adding the name of the module and the length of the data part
        $t_row = 'ADMN' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    /**
     * Creates Card Data module part of a card record
     * Total length of the record: 912 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleCARD($data = false) {
        $t_row = "";
        $cardHolderNames = $this->getCardHolderNames($data);
        // 1. Cardholder name
        $t_row .= $this->formatString($cardHolderNames[0], 30, ' ', STR_PAD_RIGHT, true);
        // 2. ID code
        $t_row .= $this->formatString($data['person_number'], 30, ' ', STR_PAD_RIGHT, true);
        // 3. Date of birth
        $t_row .= $this->formatString(date("d.m.Y", strtotime($data['person_birthday'])), 10, ' ', STR_PAD_RIGHT, true);
        // 4. Picture
        $t_row .= $this->formatString($this->getPicBaseName($data['pic']), 20, ' ', STR_PAD_RIGHT, false);
        // 5. Card expiration date short
        $t_row .= $this->formatString(date('m/y', strtotime($data['expiration_date'])), 5, ' ', STR_PAD_RIGHT, false);
        // 6. Card expirtaion date long
        $t_row .= $this->formatString(date('m/Y', strtotime($data['expiration_date'])), 7, ' ', STR_PAD_RIGHT, false);
        // 7. Card serial number - ISIC number
        $t_row .= $this->formatString($data['isic_number'], 30, ' ', STR_PAD_RIGHT, false);
        // 8. School name
        $t_row .= $this->formatString($data['school_name'], 30, ' ', STR_PAD_RIGHT, false);
        // 9. School code
        $t_row .= $this->formatString($data['school_ehis_code'], 10, ' ', STR_PAD_RIGHT, false);
        // Address on the card
        $s_addr = $this->assembleSchoolAddress('', $data['school_address1']);
        $s_addr = $this->assembleSchoolAddress($s_addr, $data['school_address2']);
        $s_addr = $this->assembleSchoolAddress($s_addr, $data['school_address3']);
        $s_addr = $this->assembleSchoolAddress($s_addr, $data['school_address4']);
        $school_data = $this->createSchoolData('', $s_addr, 50);
        $school_data2 = $this->createSchoolData('', $data['school_web'], 50);
        $school_data2 = $this->createSchoolData($school_data2, $data['school_phone'], 50);
        $school_data2 = $this->createSchoolData($school_data2, $data['school_email'], 50);

        // splitting structure unit into smaller parts
        $t_stru_unit = $this->splitString($data["person_stru_unit"], 50);

        // 10. School contact data line 1
        $t_row .= $this->formatString($school_data, 50, ' ', STR_PAD_RIGHT, false);
        // 11. School contact data line 2
        $t_row .= $this->formatString($school_data2, 50, ' ', STR_PAD_RIGHT, false);
        // 12. School class
        $t_row .= $this->formatString($data['person_class'], 10, ' ', STR_PAD_RIGHT, true);
        // 13. School department
        $t_row .= $this->formatString(isset($t_stru_unit[0]) ? $t_stru_unit[0] : '', 50, ' ', STR_PAD_RIGHT, true);
        // 14. Chair / proffessorship
        $t_row .= $this->formatString($data['person_stru_unit2'], 50, ' ', STR_PAD_RIGHT, true);
        // 15. Position
        $t_row .= $this->formatString($data['person_position'], 50, ' ', STR_PAD_RIGHT, true);
        // 16. Staff number
        $t_row .= $this->formatString($data['person_staff_number'], 20, ' ', STR_PAD_RIGHT, true);
        // 17. Logo name front side
        $t_row .= $this->formatString($this->getPicBaseName($data['logo_front']), 50, ' ', STR_PAD_RIGHT, false);
        // 18. Logo name back side
        $t_row .= $this->formatString($this->getPicBaseName($data['logo_back']), 50, ' ', STR_PAD_RIGHT, false);
        // 19. Card background design front
        $t_row .= $this->formatString($this->getPicBaseName($data['design_front']), 50, ' ', STR_PAD_RIGHT, false);
        // 20. Card background design back
        $t_row .= $this->formatString($this->getPicBaseName($data['design_back']), 50, ' ', STR_PAD_RIGHT, false);
        // 21. Structural Unit level 1 - line 2
        $t_row .= $this->formatString(isset($t_stru_unit[1]) ? $t_stru_unit[1] : '', 50, ' ', STR_PAD_RIGHT, true);
        // 22. Structural Unit level 1 - line 3
        $t_row .= $this->formatString(isset($t_stru_unit[2]) ? $t_stru_unit[2] : '', 50, ' ', STR_PAD_RIGHT, true);
        // 23. Given name(s) only i.e. "Ott"
        $t_row .= $this->formatString($data["person_name_first"], 50, ' ', STR_PAD_RIGHT, true);
        // 24. Surname only i.e. "Näidis"
        $t_row .= $this->formatString($data["person_name_last"], 50, ' ', STR_PAD_RIGHT, true);
        // 25. Second part of the card holder name, in case according card type has Tryb split checked
        $t_row .= $this->formatString($cardHolderNames[1], 30, ' ', STR_PAD_RIGHT, true);
        // 26. School name line 2
        $t_row .= $this->formatString("", 30, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'CARD' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;

        return $t_row;
    }

    public function getCardHolderNames($data) {
        $names = array('', '');
        if ($data['tryb_export_name_split']) {
            $tmpNames = $this->isicNameSplitter->splitDouble($data['person_name_first'], $data['person_name_last']);
            for ($i = 0; $i < sizeof($names); $i++) {
                if (array_key_exists($i, $tmpNames)) {
                    $names[$i] = $tmpNames[$i];
                }
            }
        } else {
            $names[0] = $data['person_name_first'] . ' ' . $data['person_name_last'];
        }
        return $names;
    }

    /**
     * Creates Magnetic Data Data module part of a card record
     * Total length of the record: 217 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleMAGN($data = false) {
        $t_row = "";
        // ISO track 1 value
        $t_row .= $this->formatString("", 76, ' ', STR_PAD_RIGHT, true);
        // ISO track 2 value
        $t_track = date("YmdHi", strtotime($data['export_timestamp']));
        $t_track .= $this->formatString($data["row_count"], 6, '0', STR_PAD_LEFT, false);
        $t_track .= "=000000000000000000";
        $t_row .= $this->formatString($t_track, 37, ' ', STR_PAD_RIGHT, false);
        // ISO track 3 value
        $t_row .= $this->formatString("", 104, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'MAGN' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    /**
     * Creates RFID Data module part of a card record
     * Total length of the record: 62 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleRFID($data = false) {
        $t_row = "";
        // Free field 1
        $t_row .= $this->formatString("", 30, ' ', STR_PAD_RIGHT, true);
        // Free field 2
        $t_row .= $this->formatString("", 30, ' ', STR_PAD_RIGHT, true);
        // Key index
        $t_row .= $this->formatString("", 2, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'RFID' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    /**
     * Creates Carrier Data module part of a card record
     * Total length of the record: 405 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleCARR($data = false) {
        $t_row = "";
        // 1. Address field 1 - Receiver name
        $t_row .= $this->formatString($data["person_name_first"] . ' ' . $data["person_name_last"], 50, ' ', STR_PAD_RIGHT, true);
        // 2. Address field 2 - Receiver school name
        $t_row .= $this->formatString($this->getSchoolName($data), 50, ' ', STR_PAD_RIGHT, true);
        // 3. Address field 3 - Department / Chair / Position or Class reference
        $t_row .= $this->formatString($data[""], 70, ' ', STR_PAD_RIGHT, true);
        // 4. Address field 4 - Street
        $t_row .= $this->formatString($this->getAddressField($data, 1), 50, ' ', STR_PAD_RIGHT, true);
        // 5. Address field 5 - Zip code
        $t_row .= $this->formatString($this->getAddressField($data, 4), 10, ' ', STR_PAD_RIGHT, true);
        // 6. Address field 6 - City
        $t_row .= $this->formatString($this->getAddressField($data, 2), 35, ' ', STR_PAD_RIGHT, true);
        // 7. Address field 7 - Office / Appartment location
        $t_row .= $this->formatString($data[""], 50, ' ', STR_PAD_RIGHT, true);
        // 8. Address field 8 - Country
        $t_row .= $this->formatString($this->getAddressField($data, 3), 35, ' ', STR_PAD_RIGHT, true);
        // 9. Class number
        $t_row .= $this->formatString($data['person_class'], 3, ' ', STR_PAD_RIGHT, true);
        // 10. Language code description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
        // 11. Enclosure code
        $t_row .= $this->formatString("", 2, ' ', STR_PAD_RIGHT, true);
        // 12. Enclosure description
        $t_row .= $this->formatString("", 30, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'CARR' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    function getSchoolName($data) {
        if ($data['card_shipment_id']) {
            $record = $this->isicDbCardShipments->getRecord($data['card_shipment_id']);
            if ($record) {
                return $record['name'];
            }
        }
        return '';
    }

    function getAddressField($data, $fieldNumber) {
        if ($data['card_shipment_id']) {
            $record = $this->isicDbCardShipments->getRecord($data['card_shipment_id']);
            if ($record) {
                return $record['delivery_addr' . $fieldNumber];
            }
        } else {
            return $data['delivery_addr' . $fieldNumber];
        }
        return '';
    }

    /**
     * Creates Shipment Data module part of a card record
     * Total length of the record: 105 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    function createModuleSHIP($data = false) {
        $t_row = "";
        // Shipment type 1 code
        $t_row .= $this->formatString($this->getShipmentCode($data), 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 1 description
        $t_row .= $this->formatString($this->getShipmentDescription($data), 20, ' ', STR_PAD_RIGHT, true);
        // Shipment type 2 code
        $t_row .= $this->formatString($this->getBranchCode($data), 5, '0', STR_PAD_LEFT, true);
        // Shipment type 2 description
        $t_row .= $this->formatString($this->getBranchDescription($data), 16, ' ', STR_PAD_RIGHT, true);
        // Shipment type 3 code
        $t_row .= $this->formatString("", 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 3 description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
        // Shipment type 4 code
        $t_row .= $this->formatString("", 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 4 description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
        // Shipment type 5 code
        $t_row .= $this->formatString("", 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 5 description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'SHIP' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    function getShipmentCode($data) {
        if ($data['card_shipment_id']) {
            return 'B'; // branch
        }
        return 'P'; // post
    }

    function getShipmentDescription($data) {
        if ($data['card_shipment_id']) {
            return 'Branch Forwarder'; // branch
        }
        return 'Postal Courier'; // post
    }

    function getBranchCode($data) {
        if ($data['card_shipment_id']) {
            return $data['card_shipment_id']; // branch
        }
        return ''; // post
    }

    function getBranchDescription($data) {
        if ($data['card_shipment_id']) {
            $record = $this->isicDbCardShipments->getRecord($data['card_shipment_id']);
            if ($record) {
                return $record['name']; // branch
            }
        }
        return ''; // post
    }

    /**
     * Returns basename for given pic-file
     *
     * @param string $pic
     * @return string basename of the pic
    */
    function getPicBaseName($pic = '')
    {
        $t_pic = $pic;
        if ($pic) {
            $t_pic_info = pathinfo($pic);
            $t_pic = $t_pic_info['basename'];
        }

        return $t_pic;
    }

    /**
     * Splits given string into array of strings with element as long as needed
     *
     * @param string $str
     * @param int $max_len
     * @return array string divided into parts
    */
    function splitString($str, $max_len = 0) {
        return explode("\n", wordwrap($str, $max_len, "\n"));
    }

    private function assembleSchoolAddress($addr, $part) {
        if (!$addr) {
            return $part;
        }
        if (!$part) {
            return $addr;
        }
        return $addr . ', ' . $part;
    }

    /**
     * Creates string of school data with max given length
     *
     * @param string $data
     * @return string module value of a card record
    */
    function createSchoolData($data, $add_data, $max_len) {
        if (!$data) {
            $data = $add_data;
        } else if (mb_strlen($data . $add_data) <= $max_len) {
            $data .= ' ' . $add_data;
        }

        return trim($data);
    }

    /**
     * Generates array with order data
     *
     * @return array with order_id, and filename
    */
    function createOrder() {
        $seqStart = 30;
        $date = date("Y-m-d");

        $r = &$this->db->query('
            SELECT MAX(`module_isic_card_transfer`.`sequence`) AS seq 
            FROM `module_isic_card_transfer` 
            WHERE `module_isic_card_transfer`.`date` = ?',
            $date
        );
        if ($data = $r->fetch_assoc()) {
            $seq = $data['seq'] ? ($data["seq"] + 1) : $seqStart;
            $order_name = 'ISIC' . date("Ymd", strtotime($date)) .
                str_pad($seq, 2, "0", STR_PAD_LEFT);
            $this->db->query('INSERT INTO `module_isic_card_transfer` 
                (`date`, `sequence`, `entrydate`, `order_name`) VALUES (?, ?, NOW(), ?)',
                $date, $seq, $order_name . '.txt'
            );
            $order_id = $this->db->insert_id();
        }

        return array("order_id" => $order_id, "filename" => $order_name);
    }

    /**
     * Saves given data into flat-file
     *
     * @param string $filename filename of created file
     * @param string $folder folder where file will be saved
     * @param string $data data contents of a file
     * @return boolean true if saving was successful, false otherwise
    */
    function saveFile($filename = '', $folder = '', $data) {
        if ($filename && $folder && $fp = fopen($folder . $filename, "wb+")) {
            if (fwrite($fp, $data)) {
                if (fclose($fp)) {
                    return true;
                }
            }
        }
        return false;
    }

    function getLastExportTime() {
        $r = &$this->db->query('
            SELECT
                `module_isic_card_transfer`.`entrydate`
            FROM
                `module_isic_card_transfer`
            ORDER BY
                `module_isic_card_transfer`.`id` DESC
            LIMIT 1'
        );

        $data = $r->fetch_assoc();
        return $data ? $data['entrydate'] : false;
    }

    public function isRecordValid($data, &$errorFields) {
        $errorFields = array();
        $isValid = true;

        foreach ($this->validationFields as $field => $validator) {
            if (!$validator->hasValidCharacters(
                mb_convert_encoding($data[$field], $this->target_encoding, $this->source_encoding))
            ) {
                $errorFields[] = $field;
                $isValid = false;
            }
        }
        return $isValid;
    }
}
