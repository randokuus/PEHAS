<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 28/02/2013
 * Time: 10:10:11
 * To change this template use File | Settings | File Templates.
 */
class IsicReport_ReturnedCards extends IsicReport_Report
{
    /**
     * Generates report about returned cards
     *
     * @return string parsed html
     */

    function show() {
        $txt = new Text($this->language, "module_isic_report");
        if ($this->vars["print"]) {
            $tmpl_print = "_print";
        }
        $instanceParameters = '&type=returned_cards';
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_returned_cards{$tmpl_print}.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
        }

        $cur_time = time();
        if ($this->vars["filter_start_date"]) {
            $this->vars["filter_start_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_start_date"]);
            $beg_date = IsicDate::getDateFormatted($this->vars["filter_start_date"], 'Y-m-d');
        } else {
            $beg_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), date("d", $cur_time) - 1, date("Y", $cur_time)), 'Y-m-d');
        }

        if ($this->vars["filter_end_date"]) {
            $this->vars["filter_end_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_end_date"]);
            $end_date = IsicDate::getDateFormatted($this->vars["filter_end_date"], 'Y-m-d');
        } else {
            $end_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), date("d", $cur_time) - 1, date("Y", $cur_time)), 'Y-m-d');
        }

        $tpl->addDataItem("PRESET_DATES_START", "'" . implode("','", $this->getPresetDatesStart()) . "'");
        $tpl->addDataItem("PRESET_DATES_END", "'" . implode("','", $this->getPresetDatesEnd()) . "'");
        $tpl->addDataItem("PRESET_DATES_NAME", $this->getPresetDatesName());

        $hidden = IsicForm::getHiddenField('filter_start_date', IsicDate::getDateFormatted($beg_date, 'd/m/Y'));
        $hidden .= IsicForm::getHiddenField('filter_end_date', IsicDate::getDateFormatted($end_date, 'd/m/Y'));

        $fields = array(
            "filter_start_date" => array("textinput", 40,0,IsicDate::getDateFormatted($beg_date, 'd/m/Y'),"","","datePicker"),
            "filter_end_date" => array("textinput", 40,0,IsicDate::getDateFormatted($end_date, 'd/m/Y'),"","","datePicker"),
            "filter_preset_dates" => array("select", 40,0,$this->vars["filter_preset_dates"],$this->getPresetDatesList(),"onChange=\"assignStartEndDateByPreseset();\"",""),
            "filter_person_name_first" => array("textinput", 40,0,$this->vars["filter_person_name_first"],"","",""),
            "filter_person_name_last" => array("textinput", 40,0,$this->vars["filter_person_name_last"],"","",""),
            "filter_person_number" => array("textinput", 40,0,$this->vars["filter_person_number"],"","",""),
            "filter_isic_number" => array("textinput", 40,0,$this->vars["filter_isic_number"],"","",""),
        );

        $this->showFormFields($fields, $tpl, $txt);

        $condition = array();
        $filter_fields = array("person_name_first", "person_name_last", "person_number", "isic_number");
        foreach ($filter_fields as $fkey) {
            $f_val = $this->vars["filter_" . $fkey];
            if ($f_val) {
                $condition[] = '`module_isic_card`.`' . $fkey . '` LIKE ' . $this->db->quote('%' . $f_val . '%');
                $hidden .= IsicForm::getHiddenField('filter_' . $fkey, $f_val);
            }
        }
        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql .= " AND";
        }

        $url_cond_print = $condition;
        $url_cond_print[] = 'filter_start_date=' . IsicDate::getDateFormatted($beg_date, 'd/m/Y');
        $url_cond_print[] = 'filter_end_date=' . IsicDate::getDateFormatted($end_date, 'd/m/Y');
        $printUrl = $general_url . "&filter=1&print=1&" . implode('&', $url_cond_print);
        $showPrintUrl = true;

        $coll_return = $this->vars["collateral_returned"];
        $url_modify = $this->isic_common->getGeneralUrlByTemplate(809);

        $empty_result = true;

        if ($this->user_type == 1) {
            $rc = &$this->db->query('
                SELECT
                    `module_isic_card`.*,
                    DATE(`module_isic_card`.`returned_date`) AS `returned_date`,
                    `module_isic_school`.`name` AS school_name,
                    `module_isic_card_type`.`name` AS type_name
                FROM
                    `module_isic_card`,
                    `module_isic_school`,
                    `module_isic_card_type`
                WHERE
                    !
                    `module_isic_card`.`school_id` = `module_isic_school`.`id` AND
                    `module_isic_card`.`type_id` = `module_isic_card_type`.`id` AND
                    `module_isic_card`.`returned_date` >= ? AND
                    `module_isic_card`.`returned_date` <= ? AND
                    `module_isic_card`.`returned` = 1 AND
                    `module_isic_card`.`confirm_payment_collateral` = 1 AND
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@)
                ORDER BY
                    `module_isic_card`.`returned_date`,
                    `module_isic_card`.`person_name_last`,
                    `module_isic_card`.`person_name_first`
                ',
                $condition_sql,
                $beg_date,
                $end_date,
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view)
            );
        } else {
            $rc = &$this->db->query('
                SELECT
                    `module_isic_card`.*,
                    DATE(`module_isic_card`.`returned_date`) AS `returned_date`,
                    `module_isic_school`.`name` AS school_name,
                    `module_isic_card_type`.`name` AS type_name
                FROM
                    `module_isic_card`,
                    `module_isic_school`,
                    `module_isic_card_type`
                WHERE
                    !
                    `module_isic_card`.`school_id` = `module_isic_school`.`id` AND
                    `module_isic_card`.`type_id` = `module_isic_card_type`.`id` AND
                    `module_isic_card`.`returned_date` >= ? AND
                    `module_isic_card`.`returned_date` <= ? AND
                    `module_isic_card`.`returned` = 1 AND
                    `module_isic_card`.`confirm_payment_collateral` = 1 AND
                    `module_isic_card`.`person_number` = ?
                ORDER BY
                    `module_isic_card`.`returned_date`,
                    `module_isic_card`.`person_name_last`,
                    `module_isic_card`.`person_name_first`
                ',
                $condition_sql,
                $beg_date,
                $end_date,
                $this->user_code
            );
        }
        //echo("<!-- " . $this->db->show_query() . " -->\n");
        while ($datac = $rc->fetch_assoc()) {
            // check if there are children for this card
            $rcc = &$this->db->query('
                SELECT
                    `module_isic_card`.`id`
                FROM
                    `module_isic_card`
                WHERE
                    `module_isic_card`.`prev_card_id` = !
                LIMIT 1
                ', $datac["id"]);
            // if no child-cards exists, then the card was returned and we should return the money to user
            //echo("<!-- " . $this->db->show_query() . " -->\n");
            if (!$rcc->num_rows()) {
                $empty_result = false;
                // finding the bankaccount
                $bank_account = "";
                if (trim($datac["person_bankaccount"])) {
                    $bank_account = $datac["person_bankaccount"];
                    if ($datac["person_bankaccount_name"]) {
                        $bank_account .= " (" . $datac["person_bankaccount_name"] . ")";
                    }
                } else {
                    $payment_info = $this->isic_payment->getCardPaymentInfo($datac["id"], 1);
                    //print_r($payment_info);
                    if (is_array($payment_info) && ($payment_info["snd_account"] != "-")) {
                        $bank_account = $payment_info["snd_account"] . " (" . $payment_info["snd_name"] . ")";
                    }
                }

                if ($this->user_type == 1) {
                    if ($this->vars["write_collateral"] && is_array($coll_return) && array_key_exists($datac["id"], $coll_return)) {
                        $t_coll = $coll_return[$datac["id"]] ? 1 : 0;
                        if ($t_coll != $datac["collateral_returned"]) {
                            $rcoll =& $this->db->query("UPDATE `module_isic_card` SET `moddate` = NOW(), `moduser` = !, `collateral_returned` = !, `collateral_returned_date` = NOW() WHERE `id` = !", $this->userid, $t_coll, $datac["id"]);
                            $datac["collateral_returned"] = $t_coll;
                        }
                    }
                }

                if (!$datac["collateral_returned"] && $this->user_type == 1 && $bank_account && !$this->vars['print']) {
                    $f = new AdminFields("collateral_returned[" . $datac["id"] . "]", array("type" => "checkbox"));
                    $collateral_returned = $f->display($datac["collateral_returned"]);
                } else {
                    $collateral_returned = $txt->display("active" . $datac["collateral_returned"]);
                }

                if ($showPrintUrl) {
                    $tpl->addDataItem("PRINT.URL", $printUrl);
                    $showPrintUrl = false;
                }
                $tpl->addDataItem("CARD_RETURNED.DATA.SCHOOL_NAME", $datac["school_name"]);
                $tpl->addDataItem("CARD_RETURNED.DATA.TYPE_NAME", $datac["type_name"]);
                $tpl->addDataItem("CARD_RETURNED.DATA.RETURNED_DATE", IsicDate::getDateFormatted($datac["returned_date"], 'd/m/Y'));
                $tpl->addDataItem("CARD_RETURNED.DATA.PERSON_NAME_FIRST", $datac["person_name_first"]);
                $tpl->addDataItem("CARD_RETURNED.DATA.PERSON_NAME_LAST", $datac["person_name_last"]);
                $tpl->addDataItem("CARD_RETURNED.DATA.ISIC_NUMBER", $datac["isic_number"]);
                $tpl->addDataItem("CARD_RETURNED.DATA.PERSON_BANK_ACCOUNT", $bank_account);
                $tpl->addDataItem("CARD_RETURNED.DATA.COLLATERAL_RETURNED", $collateral_returned);
                $tpl->addDataItem("CARD_RETURNED.DATA.URL_MODIFY", $url_modify . "&action=modify&card_id=" . $datac["id"]);
            }
        }

        if ($this->vars["filter"] && $empty_result) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("no_results_found"));
        } elseif (!$empty_result && $this->user_type == 1) {
            $tpl->addDataItem("CARD_RETURNED.TITLE", $txt->display("title_returned_cards"));
            $tpl->addDataItem("CARD_RETURNED.SELF", $general_url);
            $hidden .= IsicForm::getHiddenField('write_collateral', '1');
            $hidden .= IsicForm::getHiddenField('filter', '1');
            $tpl->addDataItem("CARD_RETURNED.CONFIRM_BUTTON.HIDDEN", $hidden);
            $tpl->addDataItem("CARD_RETURNED.CONFIRM_BUTTON.BUTTON", $txt->display("save"));
        }

        $tpl->addDataItem("SELF", $general_url);

        if ($this->vars["print"]) {
            echo $tpl->parse();
            exit();
        }

        return $tpl->parse();
    }
}
