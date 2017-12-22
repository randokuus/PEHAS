<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 28/02/2013
 * Time: 10:13:37
 * To change this template use File | Settings | File Templates.
 */
class IsicReport_CardDataCommCost extends IsicReport_Report
{
    /**
     * Generates report about card communication costs
     *
     * @return string parsed html
     */
    function show() {
        $txt = new Text($this->language, "module_isic_report");
        $txtc = new Text($this->language, "module_isic_card");
        $txto = new Text($this->language, "output");

        if ($this->vars["print"]) {
            $tmpl_print = "_print";
        }

        $instanceParameters = '&type=card_data_comm_cost';
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_card_data_comm_cost{$tmpl_print}.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
        }

        $tpl->addDataItem("TITLE", $txt->display("title_data_comm_cost"));

        $filterFields = array('filter_type_id', 'filter_school_id');
        foreach ($filterFields as $fieldName) {
            $this->vars[$fieldName] = $this->vars[$fieldName] ? $this->vars[$fieldName] : 0;
        }

        $cur_time = time();

        if ($this->vars["filter_beg_month"] && $this->vars["filter_beg_year"]) {
            $t_time = mktime(0, 0, 0, $this->vars["filter_beg_month"], 1, $this->vars["filter_beg_year"]);
            $beg_date = date("Y-m-d", $t_time);
        } else {
            $beg_date = date("Y-m-d", mktime(0, 0, 0, date("n", $cur_time), 1, date("Y", $cur_time)));
        }

        $b_time = strtotime($beg_date);
        $this->vars["filter_beg_month"] = date("n", $b_time);
        $this->vars["filter_beg_year"] = date("Y", $b_time);

        if ($this->vars["filter_end_month"] && $this->vars["filter_end_year"]) {
            $t_time = mktime(0, 0, 0, $this->vars["filter_end_month"], 1, $this->vars["filter_end_year"]);
            $end_date = date("Y-m-j", $t_time);
        } else {
            $end_date = date("Y-m-d", mktime(0, 0, 0, date("n", $cur_time), date("j", $cur_time), date("Y", $cur_time)));
        }

        $e_time = strtotime($end_date);
        $this->vars["filter_end_month"] = date("n", $e_time);
        $this->vars["filter_end_year"] = date("Y", $e_time);

        $tpl->addDataItem("PRESET_DATES_START", "'" . implode("','", $this->getPresetDatesStart()) . "'");
        $tpl->addDataItem("PRESET_DATES_END", "'" . implode("','", $this->getPresetDatesEnd()) . "'");
        $tpl->addDataItem("PRESET_DATES_NAME", $this->getPresetDatesName());

        $fields = array (
            "filter_beg_month" => array("select",0,0,$this->vars["filter_beg_month"],"","",""),
            "filter_beg_year" => array("select",0,0,$this->vars["filter_beg_year"],"","",""),
            "filter_end_month" => array("select",0,0,$this->vars["filter_end_month"],"","",""),
            "filter_end_year" => array("select",0,0,$this->vars["filter_end_year"],"","",""),
            "filter_type_id" => array("select",0,0,$this->vars["filter_type_id"],$this->getCardTypeList($txt->display("all_types")),""),
            "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],$this->getSchoolList($txt->display("all_schools")),"",""),
        );

        // months
        $list = array();
        for ($i = 1; $i <= 12; $i++) {
            $list[$i] = $txto->display("month_" . $i);
        }
        $fields["filter_beg_month"][4] = $list;
        $fields["filter_end_month"][4] = $list;

        // years
        $list = array();
        for ($i = 2008; $i <= date("Y"); $i++) {
            $list[$i] = $i;
        }
        $fields["filter_beg_year"][4] = $list;
        $fields["filter_end_year"][4] = $list;

        foreach ($fields as $key => $val) {
            $fdata["type"] = $val[0];
            $fdata["size"] = $val[1];
            $fdata["cols"] = $val[1];
            $fdata["rows"] = $val[2];
            $fdata["list"] = $val[4];
            $fdata["java"] = $val[5];
            $fdata["class"] = $val[6];

            $f = new AdminFields("$key", $fdata);
            $field_data = $f->display($val[3]);
            $tpl->addDataItem("FIELD_$key", $field_data);
            unset($fdata);
        }

        $empty_result = true;
        $g_active_list = array();
        $g_school_list = array();
        $g_type_list = array();
        $g_total_cards = 0;
        $g_total_sum = 0;

        $by = $this->vars["filter_beg_year"];
        $ey = $this->vars["filter_end_year"];

        for ($y = $by; $y <= $ey; $y++) {
            if ($by != $ey) {
                if ($y == $by) {
                    $bm = $this->vars["filter_beg_month"] + 0;
                    $em = 12;
                } elseif ($y == $ey) {
                    $bm = 1;
                    $em = $this->vars["filter_end_month"] + 0;
                } else {
                    $bm = 1;
                    $em = 12;
                }
            } else {
                $bm = $this->vars["filter_beg_month"] + 0;
                $em = $this->vars["filter_end_month"] + 0;
            }
            for ($m = $bm; $m <= $em; $m++) {
                $q_beg_date = date("Y-m-d 00:00:00", mktime(0, 0, 0, $m, 1, $y));
                $q_end_date = date("Y-m-j 23:59:59", mktime(0, 0, 0, $m, 1, $y));

                if ($this->user_type == 1) {
                    $rc = &$this->db->query("
                        SELECT
                            `module_isic_card`.*,
                            IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                            IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name
                        FROM
                            `module_isic_card`
                        LEFT JOIN
                            `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                        LEFT JOIN
                            `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                        WHERE
                            `module_isic_card`.`activation_date` <> '0000-00-00' AND
                            `module_isic_card`.`expiration_date` <> '0000-00-00' AND
                            `module_isic_card`.`activation_date` <= ? AND
                            `module_isic_card`.`expiration_date` >= ? AND
                            (`module_isic_card`.`school_id` = ! OR ! = 0) AND
                            (`module_isic_card`.`type_id` = ! OR ! = 0) AND
                            `module_isic_card`.`type_id` IN (!@) AND
                            `module_isic_card`.`school_id` IN (!@)
                        ",
                        $q_end_date, $q_beg_date,
                        $this->vars["filter_school_id"], $this->vars["filter_school_id"],
                        $this->vars["filter_type_id"], $this->vars["filter_type_id"],
                        IsicDB::getIdsAsArray($this->allowed_card_types_view),
                        IsicDB::getIdsAsArray($this->allowed_schools)
                    );

                    if ($rc->num_rows()) {
                        $empty_result = false;
                        $active_list = array();
                        $school_list = array();
                        $type_list = array();
                        $total_cards = 0;
                        $total_sum = 0;

                        while ($datac = $rc->fetch_assoc()) {
                            $active = true;
                            // if card is not active at the moment, then look, if it was de-activated during
                            // our given time-frame
                            if (!$datac["active"]) {
                                if ($datac["deactivation_time"] >= $q_beg_date && $datac["deactivation_time"] <= $q_end_date) {
                                    // card was active in our time frame
                                } else { // card was non-active at the time
                                    $active = false;
                                }
                            }
                            if ($active) {
                                if (!array_key_exists($datac["school_id"], $school_list)) {
                                    $school_list[$datac["school_id"]] = $datac["school_name"];
                                }
                                if (!array_key_exists($datac["school_id"], $g_school_list)) {
                                    $g_school_list[$datac["school_id"]] = $datac["school_name"];
                                }
                                if (!array_key_exists($datac["type_id"], $type_list)) {
                                    $type_list[$datac["type_id"]] = $datac["card_type_name"];
                                }
                                if (!array_key_exists($datac["type_id"], $g_type_list)) {
                                    $g_type_list[$datac["type_id"]] = $datac["card_type_name"];
                                }
                                $active_list[$datac["school_id"]][$datac["type_id"]]++;
                            }
                        }

                        $tpl->addDataItem("PERIOD.NAME", $txto->display("month_" . $m) . ", " . $y);

                        foreach ($school_list as $school_id => $school_name) {
                            foreach ($type_list as $type_id => $type_name) {
                                $sum = 0;
                                $comm_param = $this->getCardDataCommCost($school_id, $type_id, $q_beg_date, $q_end_date);
                                if ($comm_param) {
                                    $sum = $comm_param["sum_period"] + $comm_param["sum_card"] * $active_list[$school_id][$type_id];
                                }

                                $cards = $active_list[$school_id][$type_id];
                                $total_cards += $cards;
                                $total_sum += $sum;

                                $g_active_list[$school_id][$type_id]["cards"] += $cards;
                                $g_active_list[$school_id][$type_id]["sum"] += $sum;

                                $tpl->addDataItem("PERIOD.DATA.STYLE", "");
                                $tpl->addDataItem("PERIOD.DATA.SCHOOL", $school_name);
                                $tpl->addDataItem("PERIOD.DATA.TYPE", $type_name);
                                $tpl->addDataItem("PERIOD.DATA.CARDS", $cards);
                                $tpl->addDataItem("PERIOD.DATA.SUM", $sum);
                            }
                        }

                        $tpl->addDataItem("PERIOD.DATA.STYLE", "total");
                        $tpl->addDataItem("PERIOD.DATA.SCHOOL", "<b>" . $txt->display("total") . "</b>");
                        $tpl->addDataItem("PERIOD.DATA.TYPE", "");
                        $tpl->addDataItem("PERIOD.DATA.CARDS", "<b>" . $total_cards . "</b>");
                        $tpl->addDataItem("PERIOD.DATA.SUM", "<b>" . $total_sum . "</b>");

                        $g_total_cards += $total_cards;
                        $g_total_sum += $total_sum;
                    }
                }
            }
        }

        if ($this->vars["filter"] && $empty_result) {
            $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("no_results_found"));
        } elseif ($this->vars["filter_beg_month"] != $this->vars["filter_end_month"] || $this->vars["filter_beg_year"] != $this->vars["filter_end_year"]) {
            $tpl->addDataItem("PERIOD.NAME", $txt->display("total"));

            foreach ($g_school_list as $school_id => $school_name) {
                foreach ($g_type_list as $type_id => $type_name) {
                    $cards = $g_active_list[$school_id][$type_id]["cards"];
                    $sum = $g_active_list[$school_id][$type_id]["sum"];
                    if ($cards) {
                        $tpl->addDataItem("PERIOD.DATA.STYLE", "");
                        $tpl->addDataItem("PERIOD.DATA.SCHOOL", $school_name);
                        $tpl->addDataItem("PERIOD.DATA.TYPE", $type_name);
                        $tpl->addDataItem("PERIOD.DATA.CARDS", $cards);
                        $tpl->addDataItem("PERIOD.DATA.SUM", $sum);
                    }
                }
            }

            $tpl->addDataItem("PERIOD.DATA.STYLE", "total");
            $tpl->addDataItem("PERIOD.DATA.SCHOOL", "<b>" . $txt->display("total") . "</b>");
            $tpl->addDataItem("PERIOD.DATA.TYPE", "");
            $tpl->addDataItem("PERIOD.DATA.CARDS", "<b>" . $g_total_cards . "</b>");
            $tpl->addDataItem("PERIOD.DATA.SUM", "<b>" . $g_total_sum . "</b>");
        }

        if ($this->vars["print"]) {
            echo $tpl->parse();
            exit();
        }

        return $tpl->parse();
    }

    /**
     * Creates a list card data communication cost parameters
     *
     * @access private
     * @return array
     */

    function getCardDataCommCost($school_id, $type_id, $beg_date, $end_date)
    {
        $r = &$this->db->query("SELECT * FROM `module_isic_card_type_school_cost` WHERE `school_id` = ! AND `type_id` = ! AND `beg_date` <= ? AND `end_date` >= ? LIMIT 1", $school_id, $type_id, $end_date, $beg_date);
        if ($r->num_rows() && $data = $r->fetch_assoc()) {
            return array(
                "sum_card" => $data["sum_card"],
                "sum_period" => $data["sum_period"]
            );
        }
        return false;
    }
}
