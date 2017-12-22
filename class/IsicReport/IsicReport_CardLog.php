<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 28/02/2013
 * Time: 10:12:15
 * To change this template use File | Settings | File Templates.
 */
class IsicReport_CardLog extends IsicReport_Report
{
    /**
     * Generates report about card changes
     *
     * @return string parsed html
     */

    function show() {
        $txt = new Text($this->language, "module_isic_report");
        $txtc = new Text($this->language, "module_isic_card");

        if ($this->vars["print"]) {
            $tmpl_print = "_print";
        }

        $instanceParameters = '&type=card_log';
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_card_log{$tmpl_print}.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
        }

        $tpl->addDataItem("TITLE", $txt->display("title_card_log"));

        $fields = array (
            "filter_person_name_first" => array("textinput", 40,0,$this->vars["filter_person_name_first"],"","",""),
            "filter_person_name_last" => array("textinput", 40,0,$this->vars["filter_person_name_last"],"","",""),
            "filter_person_number" => array("textinput", 40,0,$this->vars["filter_person_number"],"","",""),
            "filter_isic_number" => array("textinput", 40,0,$this->vars["filter_isic_number"],"","",""),
        );

        $this->showFormFields($fields, $tpl, $txt);

        $condition = array();
        $url_cond = array();
        $filter_fields = array("person_name_first", "person_name_last", "person_number", "isic_number");
        foreach ($filter_fields as $fkey) {
            $f_val = trim($this->vars["filter_" . $fkey]);
            if (strlen($f_val) > 3) {
                $condition[] = '`module_isic_card`.`' . $fkey . '` LIKE ' . $this->db->quote('%' . $f_val . '%');
                $url_cond[] = "filter_" . $fkey . "=" . urlencode($f_val);
            }
        }
        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql .= " AND";
        }
        $printUrl = $general_url . "&filter=1&print=1&" . implode('&', $url_cond);
        $showPrintUrl = true;

        $empty_result = true;

        if ($condition_sql) {
            if ($this->user_type == 1) {
                $rc = &$this->db->query("
                    SELECT
                        `module_isic_card`.*,
                        IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                    FROM
                        `module_isic_card`
                    LEFT JOIN
                        `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                    WHERE
                        !
                        `module_isic_card`.`type_id` IN (!@) AND
                        `module_isic_card`.`school_id` IN (!@)
                    ",
                    $condition_sql,
                    IsicDB::getIdsAsArray($this->allowed_card_types_view),
                    IsicDB::getIdsAsArray($this->allowed_schools)
                );
            } else {
                $rc = &$this->db->query("
                    SELECT
                        `module_isic_card`.*,
                        IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                    FROM
                        `module_isic_card`
                    LEFT JOIN
                        `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                    WHERE
                        !
                        `module_isic_card`.`person_number` = ?
                    ", $condition_sql, $this->user_code);
            }
//echo "<!-- " . $this->db->show_query() . " -->\n";
            if ($rc->num_rows() == 1) {
                while ($datac = $rc->fetch_assoc()) {
                    // check if there are children for this card
                    $rl = &$this->db->query('
                        SELECT
                            `module_isic_card_log`.*,
                            `module_user_users`.`username` AS user_username,
                            `module_user_users`.`name_first` AS user_name_first,
                            `module_user_users`.`name_last` AS user_name_last
                        FROM
                            `module_isic_card_log`
                        LEFT JOIN
                            `module_user_users`
                        ON
                            `module_isic_card_log`.`event_user` = `module_user_users`.`user`
                        WHERE
                            `module_isic_card_log`.`card_id` = !
                        ORDER BY
                            `module_isic_card_log`.`event_date`
                        ', $datac["id"]);
//echo "<!-- " . $this->db->show_query() . " -->\n";

                    while ($datal = $rl->fetch_assoc()) {
                        if ($datal["event_type"] == 1) {
                            $event_body = $this->isic_common->parseCardLogAdd($datal["event_body"]);
                        } else {
                            $event_body = $this->isic_common->parseCardLogMod($this->isic_common->fixCardLogRecord($datal["event_body"]));
                        }
                        if ($event_body) {
                            $first_row = true;
                            foreach ($event_body as $field_name => $field_value) {
                                if ($showPrintUrl) {
                                    $tpl->addDataItem("PRINT.URL", $printUrl);
                                    $showPrintUrl = false;
                                }
                                $tpl->addDataItem("CARD_LOG.DATA.DATE", $first_row ? IsicDate::getDateTimeFormatted($datal["event_date"], 'd/m/Y H:i:s') : "");
                                $tpl->addDataItem("CARD_LOG.DATA.TYPE", $first_row ? $txt->display("log_type" . $datal["event_type"]) : "");
                                $tpl->addDataItem("CARD_LOG.DATA.USER", $first_row ? (($datal["user_name_first"] . ' ' . $datal["user_name_last"]) . " (" . ($datal["user_username"] ? $datal["user_username"] : ('User: ' . $datal["event_user"])) . ")") : "");
                                $tpl->addDataItem("CARD_LOG.DATA.BODY_NAME", $txtc->display(str_replace("_id", "", $field_name)));
                                $tpl->addDataItem("CARD_LOG.DATA.BODY_OLD", $field_value[0]);
                                $tpl->addDataItem("CARD_LOG.DATA.BODY_NEW", $field_value[1]);
                                $first_row = false;
                            }
                            $empty_result = false;
                        }
                    }
                }
            } else {
                while ($datac = $rc->fetch_assoc()) {
                    if ($showPrintUrl) {
                        $tpl->addDataItem("PRINT.URL", $printUrl);
                        $showPrintUrl = false;
                    }
                    $tpl->addDataItem("CARD_LIST.DATA.URL_DETAIL", $general_url . "&filter=1&filter_isic_number=" . $datac["isic_number"] . "&filter_person_name_first=" . urlencode($datac["person_name_first"]) . "&filter_person_name_last=" . urlencode($datac["person_name_last"]) . "&filter_person_number=" . $datac["person_number"]);
                    $tpl->addDataItem("CARD_LIST.DATA.PERSON_NAME_FIRST", $datac["person_name_first"]);
                    $tpl->addDataItem("CARD_LIST.DATA.PERSON_NAME_LAST", $datac["person_name_last"]);
                    $tpl->addDataItem("CARD_LIST.DATA.PERSON_NUMBER", $datac["person_number"]);
                    $tpl->addDataItem("CARD_LIST.DATA.EXPIRATION_DATE", IsicDate::getDateFormatted($datac["expiration_date"], 'd/m/Y'));
                    $tpl->addDataItem("CARD_LIST.DATA.ISIC_NUMBER", $datac["isic_number"]);
                    $tpl->addDataItem("CARD_LIST.DATA.ACTIVE", $txt->display("active" . $datac["active"]));
                    $tpl->addDataItem("CARD_LIST.DATA.CARD_TYPE_NAME", $datac["card_type_name"]);
                    $empty_result = false;
                }
            }
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
