<?php

class IsicReport_CompoundCards {
    const SEPARATOR = ';';

    /** @var Database */
    private $db;

    /** @var  IsicDB_Cards */
    private $isicDbCards;

    private $titleColumns = array(
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
        'Active',
        'Expiration Date',
        'Bank Description'
    );

    private $dataColumns = array(
        'isic_number',
        'person_name_first',
        'person_name_last',
        'person_number',
        'person_birthday',
        'school_name',
        'adddate',
        'received_date',
        'type_name',
        'bank_status_name',
        'active',
        'expiration_date',
        'bank_description'
    );

    private $reportPath = '/reports/';
    private $reportName = '_cards_';

    private $bankMetaData = array(
        1 => 'seb',
        2 => 'swed'
    );

    public function __construct($db, $isicDbCards) {
        $this->db = $db;
        $this->isicDbCards = $isicDbCards;
    }

    public function generateAndSaveReport($bankId, $begTime, $endTime) {
        $reportPath  = $this->reportPath . $this->bankMetaData[$bankId] . '/';
        $reportName = $this->bankMetaData[$bankId] . $this->reportName . strtolower(date('M', $begTime)) . '_' . date('Y', $begTime) . '.csv';
        $begDate = date('Y-m-d', $begTime);
        $endDate = date('Y-m-d', $endTime) . ' 23:59:59';

        $content = $this->generateReport($bankId, $begDate, $endDate);
        $this->saveReport($reportPath, $reportName, $content);
    }

    public function generateReport($bankId, $begDate, $endDate) {
        $str = implode(self::SEPARATOR, $this->titleColumns) . "\n";
        $cardList = $this->isicDbCards->getBankCardsReceivedInBetweenDates($bankId, $begDate, $endDate);
//        echo $this->db->show_query();
        foreach ($cardList as $data) {
            $row = $this->generateReportRowFromData($data);
            $str .= implode(self::SEPARATOR, $row) . "\n";
        }
        return $str;
    }

    private function generateReportRowFromData($data) {
        $row = array();
        foreach ($this->dataColumns as $column) {
            $row[] = $data[$column];
        }
        return $row;
    }

    private function saveReport($reportPath, $reportName, $content) {
        if (file_put_contents(SITE_PATH . '/upload' . $reportPath . $reportName, $content)) {
            $this->db->query(
                "INSERT INTO `files` (`type`, `name`, `folder`, `add_date`) VALUES (?, ?, ?, ?)",
                'csv', str_replace('.csv', '', $reportName), $reportPath, $this->db->now()
            );
            return true;
        }
        return false;
    }
}