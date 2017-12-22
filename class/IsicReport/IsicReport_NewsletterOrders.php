<?php
require_once(SITE_PATH . '/' . $GLOBALS['directory']['object'] . '/IsicReport/IsicReport_Csv.php');

class IsicReport_NewsletterOrders extends IsicReport_Csv {
    protected $folderName = NEWSLETTER_REPORT_FOLDER_NAME;
    protected $title = 'newsletter';
    protected $titleFields = array(
        'First Name', 'Last Name', 'Email Address', 'Newsletter Name', 'State', 'Date'
    );
    protected $dataClass = 'NewslettersOrders';
    protected $dataMethod = 'getRecordsForReportAsResultHandle';
}