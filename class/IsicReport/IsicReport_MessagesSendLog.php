<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

class IsicReport_MessagesSendLog extends IsicReport_Report
{
    /**
     * Generates report about sms send log
     *
     * @return string parsed html
     */

    function show() {
        if (!$this->isicDbUsers->isCurrentUserAdmin()) {
            return 'Access not allowed ...';
        }

        $txt = new Text($this->language, "module_isic_report");

        $instanceParameters = '&type=messages_send_log';
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_messages_send_log.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        list($beg_date, $end_date) = $this->getFilterDates();

        $tpl->addDataItem("TITLE", $txt->display("title_messages_send_log"));

        $fields = array(
            "filter_start_date" => array("textinput", 40,0,IsicDate::getDateFormatted($beg_date, 'd/m/Y'),"","","datePicker"),
            "filter_end_date" => array("textinput", 40,0,IsicDate::getDateFormatted($end_date, 'd/m/Y'),"","","datePicker"),
            "filter_school_id" => array("select", 0, 0, $this->vars["filter_school_id"], $this->getSchoolList($txt->display("all_schools")), "", ""),
        );

        $this->showFormFields($fields, $tpl, $txt);

        $empty_result = true;

        $rc = $this->db->query("
            SELECT
                `l`.*,
                `u`.*,
                `s`.`name` AS `school_name`
            FROM
                `module_messages_send_log` AS `l`,
                `module_user_users` AS `u`,
                `module_isic_school` AS `s`
            WHERE
                `l`.`status` = 0 AND
                `l`.`sendtime` >= ? AND
                `l`.`sendtime` <= ? AND
                `l`.`user_id` = `u`.`user` AND
                `l`.`school_id` = `s`.`id` AND
                `s`.`id` IN (!@) AND
                (`s`.`id` = ? OR ? = ?)
            ",
            $beg_date,
            $end_date . ' 23:59:59',
            IsicDB::getIdsAsArray($this->allowed_schools),
            $this->vars["filter_school_id"],
            $this->vars["filter_school_id"],
            '0'
        );
//echo "<!-- " . $this->db->show_query() . " -->\n";
        while ($datal = $rc->fetch_assoc()) {
            $tpl->addDataItem("SEND_LIST.DATA.SENDER", $datal["name_first"] . ' ' . $datal["name_last"]);
            $tpl->addDataItem("SEND_LIST.DATA.TO", $datal["to"]);
            $tpl->addDataItem("SEND_LIST.DATA.TEXT", $datal["text"]);
            $tpl->addDataItem("SEND_LIST.DATA.SCHOOL", $datal["school_name"]);
            $tpl->addDataItem("SEND_LIST.DATA.SENDTIME", IsicDate::getDateFormatted($datal["sendtime"], 'd/m/Y H:i:s'));
//            $tpl->addDataItem("SEND_LIST.DATA.COST", round($datal["message_price"], 2));
            $empty_result = false;
        }

        if ($this->vars["filter"] && $empty_result) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("no_results_found"));
        }

        if ($this->vars["print"]) {
            echo $tpl->parse();
            exit();
        }

        return $tpl->parse();
    }
}
