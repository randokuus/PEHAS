<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicExport.php");

abstract class IsicReport_Csv {
    /**
     * @var Database
     */
    protected $db;

    protected $folderName;
    protected $title;
    protected $titleFields;
    protected $dataClass;
    protected $dataMethod;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * @param $start_time
     * @param $end_time
     */
    public function getReport($start_time, $end_time) {
        try {
            IsicDB::assert(preg_match('/^[a-z0-9_\-]+$/i', $this->folderName), "Invalid {$this->title} report folder name");
            $reportName = date("Y-m-d_H:i:s");
            $fileFolder = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . "/" . $this->folderName;
            $filePath = "{$fileFolder}/{$reportName}.csv";
            $fileHandle = fopen($filePath, "w+");
            IsicDB::assert($fileHandle, "Unable to create a report file");
            $this->generateReport($start_time, $end_time, $fileHandle);
            fclose($fileHandle);
            $this->createFileRecord($reportName);
            IsicExport::showCsv(file_get_contents($filePath), "{$this->title}_{$reportName}.csv");
        } catch (Exception $e) {
            //
        }
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $fileHandle
     */
    protected function generateReport($start_time, $end_time, $fileHandle)
    {
        fputcsv($fileHandle, $this->titleFields);
        $resultHandle = IsicDB::factory($this->dataClass)->{$this->dataMethod}($start_time, $end_time);
        while ($data = $resultHandle->fetch_assoc()) {
            fputcsv($fileHandle, $data); // fields are saved in the same order they are selected by the query
        }
    }

    /**
     * @param $reportName
     */
    protected function createFileRecord($reportName) {
        $this->db->query(
            "INSERT INTO `files` (`type`, `name`, `folder`, `add_date`) VALUES (?, ?, ?, ?)",
            'csv', $reportName, '/' . $this->folderName . '/', $this->db->now()
        );
    }
}