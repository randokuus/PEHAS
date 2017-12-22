<?php
require_once(SITE_PATH . "/class/Isic/IsicFileOpenException.php");

class IsicChipNumberFileImporter {
    /** @var  IsicDB_Cards */
    protected $isicDbCards;

    protected $errors;

    protected $importLog;

    public function __construct($dbCards) {
        $this->isicDbCards = $dbCards;
    }

    public function importFile($filePath) {
        $fp = fopen($filePath, "rb");
        if (!$fp) {
            throw new IsicFileOpenException('Could not open file: ' . $filePath);
        }

        $line_count = 0;
        $this->resetErrors();
        $this->resetImportLog();
        while (!feof($fp)) {
            $t_line = fgetcsv($fp, 1000, ":");
            if (count($t_line) != 3 || !$t_line[0]) {
                continue;
            }
            $line_count++;
            $chipNumber = $t_line[0];
            $isicNumber = str_replace(" ", "", $t_line[1]);
            $panNumber = str_replace(" ", "", $t_line[2]);
            $result = $this->isicDbCards->saveChipAndPanNumber($isicNumber, $chipNumber, $panNumber);
            $this->addImportLog($line_count . ". " . $isicNumber . ': (Chip: ' . $chipNumber .
                "), (Pan: " . $panNumber . "): " . $result['status']);
            if (false == $result['status']) {
                $this->addError($result['message']);
            }
        }
        fclose($fp);
        return $this->importLog;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    public function resetErrors()
    {
        $this->errors = array();
    }

    public function addImportLog($log)
    {
        $this->importLog[] = $log;
    }

    /**
     * @return mixed
     */
    public function getImportLog()
    {
        return $this->importLog;
    }

    public function resetImportLog()
    {
        $this->importLog = array();
    }
}
