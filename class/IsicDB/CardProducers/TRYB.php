<?php

require_once(SITE_PATH . "/class/scp.class.php");
require_once(SITE_PATH . "/tools/archive.php");

class CardProducers_TRYB implements CardProducers_Interface {

    private $config;
    private $scp = null;

    /**
     * End of record marker
     * @var string
     */
    private $eor_marker = "#END#\r\n";

    /**
     * Target encoding
     * @var string
     */
    private $target_encoding = "Windows-1252";

    /**
     * Source encoding
     * @var string
     */
    private $source_encoding = "UTF-8";

    /**
     * Array with all of the different logo-field names
     * @var array
     */
    private $logo_fields = array("logo_front", "logo_back", "design_front", "design_back");


    // methods

    public function __construct(array $config) {
        $this->config = $config;
        $this->config['local_source_path'] = $config['local_source_path']
            ? SITE_PATH . "/" . $config['local_source_path'] . "/"
            : SITE_PATH . "/cache/";
        $this->config['local_target_path'] = $config['local_target_path']
            ? SITE_PATH . "/" . $config['local_target_path'] . "/"
            : SITE_PATH . "/cache/";
        $this->config['ssh_id_file'] = $config['ssh_id_file'] ? SITE_PATH . "/" . $config['ssh_id_file'] : "id_rsa";
        $this->config['ssh_known_hosts_file'] = $config['ssh_id_file']
            ? SITE_PATH . "/" . $config['ssh_known_hosts_file']
            : "known_hosts";
        $this->config['ssh_target_path'] = $config['ssh_target_path'] ? $config['ssh_target_path'] . "/" : "./";
        $this->config['ssh_source_path'] = $config['ssh_source_path'] ? $config['ssh_source_path'] . "/" : "./";
    }

    private function getCardTypeSchoolDesign(array $cardList) {
        $t_design = array();
        foreach ($cardList as $cardData) {
            if ($cardData["logo_front"] || $cardData["logo_back"] || $cardData["design_front"] || $cardData["design_back"]) {
                $t_design[$cardData["type_id"]][$cardData["school_id"]] = array(
                    "logo_front" => str_replace("_thumb.", ".", $cardData["logo_front"]),
                    "logo_back" => str_replace("_thumb.", ".", $cardData["logo_back"]),
                    "design_front" => str_replace("_thumb.", ".", $cardData["design_front"]),
                    "design_back" => str_replace("_thumb.", ".", $cardData["design_back"]),
                );
            }
        }
        return $t_design;
    }

    private function formatString($str = '', $str_len = 0, $pad_str = '', $pad_typ = 0, $str_upr = false)
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
     * Total length of the record: 59 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */

    private function createModuleADMN($data = false) {
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
     * Returns basename for given pic-file
     *
     * @param string $pic
     * @return string basename of the pic
    */
    private function getPicBaseName($pic = '') {
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
    private function splitString($str, $max_len = 0) {
        return explode("\n", wordwrap($str, $max_len, "\n"));
    }


    /**
     * Creates string of school data with max given length
     *
     * @param string $data
     * @return string module value of a card record
    */
    private function createSchoolData($data, $add_data, $max_len) {
        if ($data) {
            $data .= " ";
        }
        if (mb_strlen($data . $add_data) <= $max_len) {
            $data .= $add_data;
        }
        return trim($data);
    }

    /**
     * Creates Card Data module part of a card record
     * Total length of the record: 773 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    private function createModuleCARD($data = false) {
        $t_row = "";
        // Cardholder name
        $t_row .= $this->formatString($data['person_name_first'] . ' ' . $data['person_name_last'], 30, ' ', STR_PAD_RIGHT, true);
        // ID code
        $t_row .= $this->formatString($data['person_number'], 11, ' ', STR_PAD_RIGHT, true);
        // Date of birth
        $t_row .= $this->formatString(date("d.m.Y", strtotime($data['person_birthday'])), 10, ' ', STR_PAD_RIGHT, true);
        // Picture
        $t_row .= $this->formatString($this->getPicBaseName($data['pic']), 20, ' ', STR_PAD_RIGHT, false);
        // Card expiration date short
        $t_row .= $this->formatString(date('m/y', strtotime($data['expiration_date'])), 5, ' ', STR_PAD_RIGHT, false);
        // Card expirtaion date long
        $t_row .= $this->formatString(date('m/Y', strtotime($data['expiration_date'])), 7, ' ', STR_PAD_RIGHT, false);
        // Card serial number - ISIC number
        $t_row .= $this->formatString($data['isic_number'], 30, ' ', STR_PAD_RIGHT, false);
        // School name
        $t_row .= $this->formatString($data['school_name'], 30, ' ', STR_PAD_RIGHT, false);
        // School code
        $t_row .= $this->formatString($data['school_ehis_code'], 10, ' ', STR_PAD_RIGHT, false);
        // Address on the card
        $s_addr = "";
        $s_addr .= $data['school_address1'];
        if ($data['school_address2']) {
            if ($s_addr) {
                $s_addr .= ", ";
            }
            $s_addr .= $data['school_address2'];
        }
        if ($data['school_address3']) {
            if ($s_addr) {
                $s_addr .= ", ";
            }
            $s_addr .= $data['school_address3'];
        }
        if ($data['school_address']) {
            if ($s_addr) {
                $s_addr .= ", ";
            }
            $s_addr .= $data['school_address4'];
        }
        $school_data = "";
        $school_data = $this->createSchoolData($school_data, $s_addr, 50);

        $school_data2 = "";
        $school_data2 = $this->createSchoolData($school_data2, $data['school_web'], 50);
        $school_data2 = $this->createSchoolData($school_data2, $data['school_phone'], 50);
        $school_data2 = $this->createSchoolData($school_data2, $data['school_email'], 50);

        // splitting structure unit into smaller parts
        $t_stru_unit = $this->splitString($data["person_stru_unit"], 25);

        // School contact data line 1
        $t_row .= $this->formatString($school_data, 50, ' ', STR_PAD_RIGHT, false);
        // School contact data line 2
        $t_row .= $this->formatString($school_data2, 50, ' ', STR_PAD_RIGHT, false);
        // School class
        $t_row .= $this->formatString($data['person_class'], 10, ' ', STR_PAD_RIGHT, true);
        // School department
        $t_row .= $this->formatString(isset($t_stru_unit[0]) ? $t_stru_unit[0] : '', 30, ' ', STR_PAD_RIGHT, true);
        // Chair / proffessorship
        $t_row .= $this->formatString($data['person_stru_unit2'], 30, ' ', STR_PAD_RIGHT, true);
        // Position
        $t_row .= $this->formatString($data['person_position'], 50, ' ', STR_PAD_RIGHT, true);
        // Staff number
        $t_row .= $this->formatString($data['person_staff_number'], 20, ' ', STR_PAD_RIGHT, true);
        // Logo name front side
        $t_row .= $this->formatString($this->getPicBaseName($data['logo_front']), 20, ' ', STR_PAD_RIGHT, false);
        // Logo name back side
        $t_row .= $this->formatString($this->getPicBaseName($data['logo_back']), 20, ' ', STR_PAD_RIGHT, false);
        // Card background design front
        $t_row .= $this->formatString($this->getPicBaseName($data['design_front']), 20, ' ', STR_PAD_RIGHT, false);
        // Card background design back
        $t_row .= $this->formatString($this->getPicBaseName($data['design_back']), 20, ' ', STR_PAD_RIGHT, false);
        // Free field 1 - used as second part of the structure unit
        $t_row .= $this->formatString(isset($t_stru_unit[1]) ? $t_stru_unit[1] : '', 50, ' ', STR_PAD_RIGHT, true);
        // Free field 2 - used as third part of the structure unit
        $t_row .= $this->formatString(isset($t_stru_unit[2]) ? $t_stru_unit[2] : '', 50, ' ', STR_PAD_RIGHT, true);
        // Free field 3 - Given name(s) only i.e. "Ott"
        $t_row .= $this->formatString($data["person_name_first"], 50, ' ', STR_PAD_RIGHT, true);
        // Free field 4 - Surname only i.e. "N�idis"
        $t_row .= $this->formatString($data["person_name_last"], 50, ' ', STR_PAD_RIGHT, true);
        // Free field 5
        $t_row .= $this->formatString("", 50, ' ', STR_PAD_RIGHT, true);
        // Free field 6
        $t_row .= $this->formatString("", 50, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'CARD' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;

        return $t_row;
    }

      /**
     * Creates Magnetic Data Data module part of a card record
     * Total length of the record: 217 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    private function createModuleMAGN($data = false) {
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
    private function createModuleRFID($data = false) {
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
     * Total length of the record: 404 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    private function createModuleCARR($data = false) {
        $t_row = "";
        // Address field 1 - Receiver name
        $t_row .= $this->formatString($data["person_name_first"] . ' ' . $data["person_name_last"], 50, ' ', STR_PAD_RIGHT, true);
        // Address field 2 - Receiver school name
        $t_row .= $this->formatString($this->getSchoolName($data), 50, ' ', STR_PAD_RIGHT, true);
        // Address field 3 - Department / Chair / Position or Class reference
        $t_row .= $this->formatString($data[""], 70, ' ', STR_PAD_RIGHT, true);
        // Address field 4 - Street
        $t_row .= $this->formatString($this->getAddressField($data, 1), 50, ' ', STR_PAD_RIGHT, true);
        // Address field 5 - Zip code
        $t_row .= $this->formatString($this->getAddressField($data, 4), 10, ' ', STR_PAD_RIGHT, true);
        // Address field 6 - City
        $t_row .= $this->formatString($this->getAddressField($data, 2), 35, ' ', STR_PAD_RIGHT, true);
        // Address field 7 - Office / Appartment location
        $t_row .= $this->formatString($data[""], 50, ' ', STR_PAD_RIGHT, true);
        // Address field 8 - Country
        $t_row .= $this->formatString($this->getAddressField($data, 3), 35, ' ', STR_PAD_RIGHT, true);
        // Language code
        $t_row .= $this->formatString("", 2, ' ', STR_PAD_RIGHT, true);
        // Language code description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
        // Enclosure code
        $t_row .= $this->formatString("", 2, ' ', STR_PAD_RIGHT, true);
        // Enclosure description
        $t_row .= $this->formatString("", 30, ' ', STR_PAD_RIGHT, true);
        // adding the name of the module and the length of the data part
        $t_row = 'CARR' . $this->formatString(strlen($t_row), 6, '0', STR_PAD_LEFT, false) . $t_row;
        return $t_row;
    }

    private function getSchoolName($data) {
        if ($data['school_joined']) {
            return $data['school_name'];
        }
        return '';
    }

    private function getAddressField($data, $fieldNumber) {
        if ($data['school_joined']) {
            return $data['school_address' . $fieldNumber];
        } else {
            return $data['delivery_addr' . $fieldNumber];
        }
    }

    /*
     * Creates Shipment Data module part of a card record
     * Total length of the record: 105 bytes
     *
     * @param array $data data of a single card record
     * @return string module value of a card record
    */
    private function createModuleSHIP($data = false) {
        $t_row = "";
        // Shipment type 1 code
        $t_row .= $this->formatString($this->getShipmentCode($data), 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 1 description
        $t_row .= $this->formatString($this->getShipmentDescription($data), 20, ' ', STR_PAD_RIGHT, true);
        // Shipment type 2 code
        $t_row .= $this->formatString("", 1, ' ', STR_PAD_RIGHT, true);
        // Shipment type 2 description
        $t_row .= $this->formatString("", 20, ' ', STR_PAD_RIGHT, true);
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

    private function getShipmentCode($data) {
        if ($data['school_joined']) {
            return 'K'; // kreutzwald
        }
        return 'P'; // post
    }

    private function getShipmentDescription($data) {
        if ($data['school_joined']) {
            return 'Kreuzwaldi 4 kontor'; // kreutzwald
        }
        return 'Post'; // post
    }

    /**
     * Export cards
     * @return array Successfully transferred list of cardData
     */
    public function exportCards(array $cardList, array $transferData) {
        $t_record = '';
        $row_count = 0;
        $pic_names = array();
        $logo_names = array();
        $export_timestamp = date("Y-m-d H:i:s");
        $design_card_type_school = $this->getCardTypeSchoolDesign($cardList);

        foreach ($cardList as $cardData) {

            $row_count++;
            $t_design = $design_card_type_school[$cardData["type_id"]][$cardData["school_id"]];
            if (is_array($t_design)) {
                // in case of front of back-side designs present, then logos will not be used
                if ($t_design["design_front"] || $t_design["design_back"]) {
                    $cardData["design_front"] = $t_design["design_front"];
                    $cardData["design_back"] = $t_design["design_back"];
                } elseif ($t_design["logo_front"] || $t_design["logo_back"]) {
                    $cardData["logo_front"] = $t_design["logo_front"];
                    $cardData["logo_back"] = $t_design["logo_back"];
                }
            }

            // adding logo fields into array if not yet added
            foreach ($this->logo_fields as $logo_field) {
                if ($cardData[$logo_field] && !in_array($cardData[$logo_field], $logo_names)) {
                    $logo_names[] = $cardData[$logo_field];
                }
            }
            $cardData["export_timestamp"] = $export_timestamp;
            $cardData["row_count"] = $row_count;
            $t_record .= $this->getTRow($cardData);
            if ($cardData['pic']) {
              $pic_names[] = $cardData['pic'];
            }

        }

        if ($t_record) {
          $out_fname = $transferData["order_name"];
          $fname = basename($out_fname, ".txt");
          $this->saveFile($out_fname, $this->config['local_source_path'], $t_record);

          for ($i = 0; $i < sizeof($pic_names); $i++) {
            $pic_names[$i] = SITE_PATH . $pic_names[$i];
          }

          for ($i = 0; $i < sizeof($logo_names); $i++) {
            $logo_names[$i] = SITE_PATH . $logo_names[$i];
          }

          // creating tar-archive
          $tar_fname = $this->config['local_source_path'] . $fname . ".tar";
          $tar = new tar_file($tar_fname);
          $tar->set_options(array('basedir' => $this->config['local_source_path'], 'overwrite' => 1, 'storepaths' => 0));
          $tar->add_files(array($this->config['local_source_path'] . $out_fname));
          $tar->add_files($pic_names);
          $tar->add_files($logo_names);
          $tar->create_archive();

          $scp = $this->getScp();
          if (!$scp->upload($tar_fname, $this->config['ssh_target_path'])) {
               throw new Exception($scp->getErrors());
          }

        }
        return $cardList;
    }


    private function getTRow($row = array()) {
      $t_row = '';
      $t_row .= str_pad($row['row_count'], 6, '0', STR_PAD_LEFT);
      $t_row .= $this->createModuleADMN($row);
      $t_row .= $this->createModuleCARD($row);
      $t_row .= $this->createModuleMAGN($row);
      $t_row .= $this->createModuleRFID($row);
      $t_row .= $this->createModuleCARR($row);
      $t_row .= $this->createModuleSHIP($row);
      $t_row .= $this->eor_marker;
      return $t_row;
    }

    private function saveFile($filename = '', $folder = '', $data) {
        if ($filename && $folder && $fp = fopen($folder . $filename, "wb+")) {
            if (fwrite($fp, $data)) {
                if (fclose($fp)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getScp() {
        if (is_object($this->scp)) {
            return $this->scp;
        }
        return $this->scp = new scp(
            $this->config['ssh_known_hosts_file'],
            $this->config['ssh_id_file'],
            $this->config['ssh_hostname'],
            $this->config['ssh_username']
        );
    }

    private function markImportedFileAsInvalid($dir, $file) {
        rename("$dir/$file", "$dir/error/$file");
        $this->getScp()->delete($this->config['ssh_source_path'] . "/" . $file);
    }

    private function markImportedFileAsProcessed($dir, $file) {
        rename("$dir/$file", "$dir/imported/$file");
        $this->getScp()->delete($this->config['ssh_source_path'] . "/" . $file);
    }

    public function importChips(array $cardList, array $transferData) {
        static $shouldDownload = true;
        $src_path = $this->config['ssh_source_path'] . "/log_ISIC*.txt";
        $tar_path = $this->config['local_target_path'];
        if ($shouldDownload) {
            $shouldDownload = false;
            if (!$this->getScp()->download($src_path, $tar_path)) {
                $error = trim($this->getScp()->getErrors());
                if ($error != "Transfer failed with following error-code: 1 ()") {
                    throw new Exception($error);
                }
            }
        }
        $opendir = addslashes($tar_path);
        if (!($dir = @opendir($opendir))) {
            throw new Exception("Can't open local target directory");
        }
        while (($file = @readdir($dir)) !== false) {
            if (!is_dir($opendir . $file) && $file != "." && $file != "..") {
                if ($fp = fopen($opendir . $file, "rb")) {
                    if (feof($fp)) {
                        fclose($fp);
                        $this->markImportedFileAsInvalid($opendir, $file);  // empty file
                        continue;
                    }
                    $orderName = fgetcsv($fp, 1024);
                    $orderName = $orderName[0];
                    if (strpos($orderName, "ISIC") === false) {
                        fclose($fp);
                        $this->markImportedFileAsInvalid($opendir, $file);  // incorrect first line
                        continue;
                    }
                    if ($orderName != $transferData['order_name']) {
                        continue;
                    }
                    $chipFile = new CardProducers_ChipFile($file);
                    while (!feof($fp)) {
                        $t_line = fgetcsv($fp, 1024, ":");
                        if ($t_line[0]) {
                            $t_line[1] = str_replace(" ", "", $t_line[1]);
                            $chipFile->addChip($t_line[1], $t_line[0]);
                        }
                    }
                    fclose($fp);
                    if (count($cardList) == count($chipFile->getChips())) {
                        $this->markImportedFileAsProcessed($opendir, $file);
                    } else {
                        $this->markImportedFileAsInvalid($opendir, $file);  // some chips are missing
                    }
                    return $chipFile;
                }
            }
        }
        return null;
    }

    public function getTransferName($id, $date, $seq) {
        return 'ISIC' . date("Ymd", strtotime($date)) . str_pad($seq, 2, "0", STR_PAD_LEFT) . ".txt";
    }

    public function getConfigFields() {
        return array(
            'local_source_path',    // former ISIC_PATH
            'local_target_path',    // former $tar_path in import (not in config for some reason)
            'ssh_hostname',         // former TARGET_HOSTNAME / SOURCE_HOSTNAME
            'ssh_username',         // former TARGET_USERNAME / SOURCE_USERNAME
            'ssh_id_file',          // former ID_FILE
            'ssh_known_hosts_file', // former HOST_FILE
            'ssh_target_path',      // former TARGET_PATH
            'ssh_source_path',		// former SOURCE_PATH
            'ssh_regular_masks',    // formerly defined in code
            'ssh_bank_masks'        // formerly defined in code
        );
    }

    public function __destruct() {
        // remove all remaining files and mark them as invalid
        $opendir = addslashes($this->config['local_target_path']);
        if ($dir = @opendir($opendir)) {
            while (($file = @readdir($dir)) !== false) {
                if (!is_dir($opendir . $file) && $file != "." && $file != "..") {
                    $this->markImportedFileAsInvalid($opendir, $file);  // no order found
                }
            }
        }
    }

}