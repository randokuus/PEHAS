<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_CardDataCommCost.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_CardLog.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_OrderedCards.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_OrderedCardsDetail.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_ReturnedCards.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_UserStatusChanges.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_MessagesSendLog.php");

class isic_report {
    private $vars;

    public function __construct() {
        $this->vars = array_merge($_GET, $_POST);
    }

    function showOrderedCards() {
        if ($this->vars['detail'] || $this->vars['export']) {
            $report = new IsicReport_OrderedCardsDetail();
        } else {
            $report = new IsicReport_OrderedCards();
        }
        return $report->show();
    }

    /**
     * Generates report about returned cards
     *
     * @return string parsed html
    */
    function showReturnedCards () {
        $report = new IsicReport_ReturnedCards();
        return $report->show();
    }

    /**
     * Generates report about card changes
     *
     * @return string parsed html
    */
    function showCardLog () {
        $report = new IsicReport_CardLog();
        return $report->show();
    }

    /**
     * Generates report about card communication costs
     *
     * @return string parsed html
    */
    function showCardDataCommCost () {
        $report = new IsicReport_CardDataCommCost();
        return $report->show();
    }

    /**
     * Generates report about user status changes
     *
     * @return string parsed html
    */
    function showUserStatusChanges() {
        $report = new IsicReport_UserStatusChanges();
        return $report->show();
    }

    /**
     * Generates report about message send log
     *
     * @return string parsed html
     */
    function showMessagesSendLog() {
        $report = new IsicReport_MessagesSendLog();
        return $report->show();
    }

    /**
     * Check does the active user have access to the page/form
     *
     * @access private
     * @return boolean
     */
    function checkAccess () {
        if ($GLOBALS["pagedata"]["login"] == 1) {
            if ($this->userid && $GLOBALS["user_show"] == true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

    /**
     * Returns module parameters array
     *
     * @return array module parameters
    */
    function getParameters() {
        $ar = split(";", $GLOBALS["pagedata"]["module"]);
        for ($c = 0; $c < sizeof($ar); $c++) {
            $a = split("=", $ar[$c]);
            $this->module_param[$a[0]] = $a[1];
        }
    }

    /**
     * Creates array of module parameter values for content admin
     *
     * @return array module parameters
    */
    function moduleOptions() {
        $sq = new sql;
        return array();
        // name, type, list
    }
}