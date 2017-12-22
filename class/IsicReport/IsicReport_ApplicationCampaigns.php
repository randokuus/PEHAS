<?php
require_once(SITE_PATH . '/' . $GLOBALS['directory']['object'] . '/IsicReport/IsicReport_Csv.php');

class IsicReport_ApplicationCampaigns extends IsicReport_Csv {
    protected $folderName = CAMPAIGN_REPORT_FOLDER_NAME;
    protected $title = 'campaign';
    protected $titleFields = array(
        'First Name', 'Last Name', 'Person number', 'School', 'Card type', 'Campaign Code', 'Mod. Time'
    );
    protected $dataClass = 'Applications';
    protected $dataMethod = 'getRecordsWithCampaignCode';
}