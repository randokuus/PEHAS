<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

/**
 */
class IsicReport_OrderedCards extends IsicReport_Report
{

    /**
     * Generates report about ordered cards
     *
     * @return string parsed html
     */
    public function show()
    {
        $tpl = $this->getTemplateInstance();
        list($beg_date, $end_date) = $this->initFilterVarsAndGetDates();
        $txt = new Text($this->language, "module_isic_report");
        $this->showAllFormFields($beg_date, $end_date, $txt, $tpl);

        if ($this->vars["filter"]) {
            list($url_cond, $condition_sql) = $this->getUrlAndSqlConditions();
            list($detail_url, $printUrl) = $this->getDetailAndPrintUrl($beg_date, $end_date, $url_cond);
            $showPrintUrl = true;

            $kind_columns = array("regular", "combined");
            $stat_columns = array("first", "replace", "prolong", "total");
            $data_columns = array("qty");

            $cols_type = 8 + 1;
            $cols_kind = 4;
            $cols_stat = 1;

            if ($this->vars["filter_collateral"]) {
                $cols_type += 8;
                $cols_kind += 4;
                $cols_stat += 1;
                $data_columns[] = "sum1";
            }
            if ($this->vars["filter_cost"]) {
                $cols_type += 8;
                $cols_kind += 4;
                $cols_stat += 1;
                $data_columns[] = "sum2";
            }

            $empty_result = true;
            $cardTypeList = $this->getCardTypeListData($this->vars['filter_type_id'], $txt);
            $schoolList = $this->getFilteredSchoolList($beg_date, $end_date, $txt);

            $param_list = array(
                "condition_sql" => $condition_sql,
                "beg_date" => $beg_date,
                "end_date" => $end_date,
                'currency' => $this->vars['filter_currency']
            );
            $dates_all = $day_data_all = $col_data_all = array();
            //$this->getOrderedCardsData(null, $param_list, $dates_all, $day_data_all, $col_data_all);
            $this->getOrderedCardsDataAppl(null, $param_list, $dates_all, $day_data_all, $col_data_all);
            foreach ($schoolList as $key_s => $datas) {
                $first_type = true;

                foreach ($cardTypeList as $key_ct => $datact) {
                    $filters = array(
                        'school_id' => $datas["id"],
                        'is_school_filter' => $this->vars["filter_school_id"] ? 1 : 0,
                        'type_id' => $datact['id'],
                        'school_id_next' => (isset($schoolList[$key_s + 1]['id']) ? $schoolList[$key_s + 1]['id'] : null),
                        'type_id_next' => (isset($cardTypeList[$key_ct + 1]['id']) ? $cardTypeList[$key_ct + 1]['id'] : null)
                    );
                    //$this->getOrderedCardsData($filters, $param_list, $dates_all, $day_data_all, $col_data_all);
                    $this->getOrderedCardsDataAppl($filters, $param_list, $dates_all, $day_data_all, $col_data_all);
                    if (!isset($day_data_all[$datas["id"]])) {
                        break;
                    }

                    $day_data = array(); // array for single dates
                    $col_data = array(); // array for type columns
                    $first_row = true;
                    $dates = array();

                    if (isset($dates_all[$datas["id"]][$datact['id']])) {
                        $day_data = $day_data_all[$datas["id"]][$datact['id']];
                        $col_data = $col_data_all[$datas["id"]][$datact['id']];
                        $dates = $dates_all[$datas["id"]][$datact['id']];
                        sort($dates);
                    }

                    foreach ($dates as $date) {
                        $empty_result = false;
                        if ($first_type) {
                            $first_type = false;
                            $tpl->addDataItem("SCHOOL.NAME", $datas["name"]);
                            if ($showPrintUrl) {
                                $tpl->addDataItem("SCHOOL.PRINT.URL", $printUrl);
                                $showPrintUrl = false;
                            }
                        }
                        if ($first_row) {
                            $first_row = false;
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.NAME", $datact["name"]);
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.COLS_TYPE", $cols_type);
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.COLS_KIND", $cols_kind);
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.COLS_STAT", $cols_stat);
                            $this->showOrderedCardsColumnTitles($tpl, $txt, $kind_columns, $stat_columns, $data_columns);
                        }
                        if ($this->vars['filter_sum_all_types'] || $this->vars['filter_sum_all_schools']) {
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.DATE",
                                IsicDate::getDateFormatted($beg_date, 'd/m/Y') . ' - ' .
                                IsicDate::getDateFormatted($end_date, 'd/m/Y')
                            );
                        } else {
                            $url = $this->getDetailReportUrl($detail_url, $datas, $date, $datact);
                            $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.DATE",
                                "<a href=\"$url\">" . IsicDate::getDateFormatted($date, 'd/m/Y') . "</a>"
                            );
                        }
                        $this->showOrderedCardsDataRowsDetail($tpl, $stat_columns, $data_columns, $day_data, $date);
                    }

                    if (sizeof($dates)) {
                        $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.DATE", "<b>" . $txt->display("total") . "</b>");
                        $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.STYLE", "tTotal");
                        $this->showOrderedCardsDataRowsTotal($tpl, $stat_columns, $data_columns, $col_data);
                    }

                    // for clear memory
                    unset($dates_all[$datas["id"]][$datact['id']]);
                    unset($day_data_all[$datas["id"]][$datact['id']]);
                    unset($col_data_all[$datas["id"]][$datact['id']]);
                }
            }

            if ($empty_result) {
                $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("no_results_found"));
            }

            if ($this->vars["print"]) {
                echo $tpl->parse();
                exit();
            }
        }
        return $tpl->parse();
    }

    private function getDetailReportUrl($detail_url, $datas, $date, $datact)
    {
        $backParams = array();
        foreach ($this->vars as $paramName => $paramVal) {
            $backParams[] = str_replace("filter_", "parent_", $paramName) . "=" . $paramVal;
        }
        $backUrl = implode("&", $backParams);
        $url = $detail_url . "&filter_school_id=" . $datas["id"] . "&date=" . $date . "&" . $backUrl;
        if (!$this->vars["filter_sum_all_types"]) {
            $url .= "&filter_type_id=" . $datact["id"];
            return $url;
        }
        return $url;
    }

    protected function getCardTypeListData($filterId, $txt) {
        if ($this->vars['filter_sum_all_types'] && !$filterId) {
            return array(array(
                'id' => -1,
                'name' => $txt->display("all_types")
            ));
        }
        return parent::getCardTypeListData($filterId, $txt);
    }


    private function getFilteredSchoolList($beg_date, $end_date, $txt)
    {
        if ($this->vars['filter_sum_all_schools'] &&
            (!$this->vars['filter_school_id'] || !$this->vars['filter_region_id'])) {
            return array(array(
                'id' => -1,
                'name' => $txt->display("all_schools")
            ));
        }

        if ($this->user_type == 1) {
            $rs = & $this->db->query('
                    SELECT
                        `module_isic_school`.`id`,
                        `module_isic_school`.`name`,
                        `module_isic_school`.`ehl_code`
                    FROM
                        `module_isic_school`
                    WHERE
                        `module_isic_school`.`id` IN (!@) AND
                        (`module_isic_school`.`id` = ? OR ? = ?) AND
                        (`module_isic_school`.`region_id` = ? OR ? = ?)
                    ORDER BY
                        `module_isic_school`.`name`',
                IsicDB::getIdsAsArray($this->allowed_schools),
                $this->vars["filter_school_id"],
                $this->vars["filter_school_id"],
                '0',
                $this->vars["filter_region_id"],
                $this->vars["filter_region_id"],
                '0'
            );
        } else {
            $rs = & $this->db->query('
                    SELECT
                        `module_isic_school`.`id`,
                        `module_isic_school`.`name`,
                        `module_isic_school`.`ehl_code`
                    FROM
                        `module_isic_school`,
                        `module_isic_card`
                    WHERE
                        `module_isic_school`.`id` = `module_isic_card`.`school_id` AND
                        `module_isic_card`.`person_number` = ? AND
                        DATE(`module_isic_card`.`exported`) >= ? AND
                        DATE(`module_isic_card`.`exported`) <= ?  AND
                        (`module_isic_school`.`id` = ? OR ? = ?) AND
                        (`module_isic_school`.`region_id` = ? OR ? = ?)
                    ORDER BY
                        `module_isic_school`.`name`',
                $this->user_code,
                $beg_date,
                $end_date,
                $this->vars["filter_school_id"],
                $this->vars["filter_school_id"],
                '0',
                $this->vars["filter_region_id"],
                $this->vars["filter_region_id"],
                '0'
            );
        }

        $schools = array();
        while ($data = $rs->fetch_assoc()) {
            if ($this->isicDbSchools->isEhlRegion($data)) {
                continue;
            }
            $schools[] = $data;
        }
        return $schools;
    }

    private function getDetailAndPrintUrl($beg_date, $end_date, $url_cond)
    {
        $detail_url = $this->getDetailUrl($url_cond);
        $url_cond_print = $url_cond;
        $url_cond_print[] = 'filter_start_date=' . IsicDate::getDateFormatted($beg_date, 'd/m/Y');
        $url_cond_print[] = 'filter_end_date=' . IsicDate::getDateFormatted($end_date, 'd/m/Y');
        $filter_fields_print = array('school_id', 'type_id', 'sum_all_types', 'collateral');
        foreach ($filter_fields_print as $fkey) {
            $f_val = $this->vars["filter_" . $fkey];
            if ($f_val) {
                $url_cond_print[] = "filter_" . $fkey . "=" . urlencode($f_val);
            }
        }
        $printUrl = $this->getGeneralUrl() . "&filter=1&print=1&" . implode('&', $url_cond_print);
        return array($detail_url, $printUrl);
    }

    private function getUrlAndSqlConditions()
    {
        $condition = array();
        $url_cond = array();
        $url_cond[] = "filter_currency=" . urlencode($this->vars['filter_currency']);
        $filter_fields = array("person_name_first", "person_name_last", "person_number", "isic_number", "kind_id", "bank_id");
        foreach ($filter_fields as $fkey) {
            $f_val = $this->vars["filter_" . $fkey];
            if ($f_val) {
                $condition[] = '`module_isic_card`.`' . $fkey . '` LIKE ' . $this->db->quote('%' . $f_val . '%');
                $url_cond[] = "filter_" . $fkey . "=" . urlencode($f_val);
            }
        }

        if (IsicDB::factory("Users")->isCurrentUserSuperAdmin()) {
            if ($this->vars["filter_school_joined"]) {
                if ($this->vars["filter_school_joined"] == '2') {
                    $this->vars["filter_school_joined"] = '0';
                }
                $condition[] = '`module_isic_school`.`joined` LIKE ' . $this->db->quote('%' . $this->vars["filter_school_joined"]);
            }
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql .= " AND";
        }
        return array($url_cond, $condition_sql);
    }

    private function showAllFormFields($beg_date, $end_date, $txt, $tpl)
    {
        $tpl->addDataItem("TITLE", $txt->display("title_ordered_cards"));
        $tpl->addDataItem("PRESET_DATES_START", "'" . implode("','", $this->getPresetDatesStart()) . "'");
        $tpl->addDataItem("PRESET_DATES_END", "'" . implode("','", $this->getPresetDatesEnd()) . "'");
        $tpl->addDataItem("PRESET_DATES_NAME", $this->getPresetDatesName());

        if (IsicDB::factory("Users")->isCurrentUserSuperAdmin()) {
            $joined_field = $this->getFormJoinedFields($txt);
            $this->showFormFields($joined_field, $tpl, $txt, "JOINED");
        }
        if (IsicDB::factory("Users")->isCurrentUserAdmin()) {
            $export_field = $this->getFormExportFields($txt);
            $this->showFormFields($export_field, $tpl, $txt, "EXPORT");
        }
        $fields = $this->getFormFields($beg_date, $end_date, $txt);
        $this->showFormFields($fields, $tpl, $txt);
    }

    private function getFormJoinedFields($txt)
    {
        $joined_field = array(
            "filter_school_joined" => array(
                "select", 40, 0, $this->vars["filter_school_joined"], $this->getSchoolJoined($txt), "", ""
            )
        );
        return $joined_field;
    }

    private function getFormExportFields($txt)
    {
        $fields = array(
            "export_button" => array(
                "button", 0, 0, $txt->display('export'), '', 'onclick="javascript:submitForm(\'export\');"', ''
            )
        );
        return $fields;
    }

    private function getFormFields($beg_date, $end_date, $txt)
    {
        $fields = array(
            "filter_start_date" => array("textinput", 40, 0, IsicDate::getDateFormatted($beg_date, 'd.m.Y'), "", "", "datePicker"),
            "filter_end_date" => array("textinput", 40, 0, IsicDate::getDateFormatted($end_date, 'd.m.Y'), "", "", "datePicker"),
            "filter_preset_dates" => array("select", 40, 0, $this->vars["filter_preset_dates"], $this->getPresetDatesList(), "onChange=\"assignStartEndDateByPreseset();\"", ""),
            "filter_sum_all_types" => array("checkbox", 0, 0, $this->vars["filter_sum_all_types"], "", "", ""),
            "filter_sum_all_schools" => array("checkbox", 0, 0, $this->vars["filter_sum_all_schools"], "", "", ""),
            "filter_collateral" => array("checkbox", 0, 0, $this->vars["filter_collateral"], "", "", ""),
            "filter_type_id" => array("select", 0, 0, $this->vars["filter_type_id"], $this->getCardTypeList($txt->display("all_types")), ""),
            "filter_kind_id" => array("select", 0, 0, $this->vars["filter_kind_id"], $this->getKinds($txt), ""),
            "filter_school_id" => array("select", 0, 0, $this->vars["filter_school_id"], $this->getSchoolList($txt->display("all_schools")), "", ""),
            "filter_region_id" => array("select", 0, 0, $this->vars["filter_region_id"], $this->getRegionList($txt->display("all_regions")), "", ""),
            "filter_bank_id" => array("select", 0, 0, $this->vars["filter_bank_id"], $this->isicDbBanks->getBankList($txt->display("all_banks")), "", ""),
            "filter_person_name_first" => array("textinput", 40, 0, $this->vars["filter_person_name_first"], "", "", ""),
            "filter_person_name_last" => array("textinput", 40, 0, $this->vars["filter_person_name_last"], "", "", ""),
            "filter_person_number" => array("textinput", 40, 0, $this->vars["filter_person_number"], "", "", ""),
            "filter_isic_number" => array("textinput", 40, 0, $this->vars["filter_isic_number"], "", "", ""),
            "filter_currency" => array("select", 40, 0, $this->vars["filter_currency"], $this->isicDbCurrency->getNameList(), "", ""),
        );
        return $fields;
    }

    private function initFilterVarsAndGetDates()
    {
        $this->vars['filter_currency'] = $this->getCurrencyFilter($this->vars['filter_currency']);
        $this->vars["filter_cost"] = 1;
        $filterFields = array('filter_collateral', 'filter_sum_all_types', 'filter_type_id', 'filter_school_id', 'filter_bank_id');
        foreach ($filterFields as $fieldName) {
            $this->vars[$fieldName] = $this->vars[$fieldName] ? $this->vars[$fieldName] : 0;
        }

        $cur_time = time();
        if ($this->vars["filter_start_date"]) {
            if ($this->vars["back"] != '1') {
                $this->vars["filter_start_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_start_date"]);
            }
            $beg_date = IsicDate::getDateFormatted($this->vars["filter_start_date"], 'Y-m-d');
        } else {
            $beg_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), 1, date("Y", $cur_time)), 'Y-m-d');
        }
        if ($this->vars["filter_end_date"]) {
            if ($this->vars["back"] != '1') {
                $this->vars["filter_end_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_end_date"]);
            }
            $end_date = IsicDate::getDateFormatted($this->vars["filter_end_date"], 'Y-m-d');
        } else {
            $end_date = IsicDate::getCurrentTimeFormatted('Y-m-d');
        }
        return array($beg_date, $end_date);
    }

    private function getDetailUrl($url_cond)
    {
        $url_filter = implode("&", $url_cond);
        $detail_url = $this->getGeneralUrl() . "&detail=1&" . $url_filter;
        return $detail_url;
    }

    private function getGeneralUrl()
    {
        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
            return $general_url;
        }
        return '#';
    }

    private function getTemplateInstance() {
        if ($this->vars["print"]) {
            $tmpl_print = "_print";
        }
        $instanceParameters = '&type=ordered_cards';
        return $this->isicTemplate->initTemplateInstance("module_isic_report_ordered_cards{$tmpl_print}.html", $instanceParameters);
    }

    private function showOrderedCardsColumnTitles($tpl, $txt, $kind_columns, $stat_columns, $data_columns) {
        for ($i = 0; $i < sizeof($kind_columns); $i++) {
            for ($j = 0; $j < sizeof($stat_columns); $j++) {
                for ($k = 0; $k < sizeof($data_columns); $k++) {
                    $tpl->addDataItem("SCHOOL.CARD_TYPE.TITLE.TITLE", $txt->display($data_columns[$k]));
                }
            }
        }
    }

    private function showOrderedCardsDataRowsDetail($tpl, $stat_columns, $data_columns, $day_data, $date) {
        foreach ($this->card_kind as $kind_id) {
            for ($j = 0; $j < sizeof($stat_columns); $j++) {
                $cur_status = $stat_columns[$j];
                for ($k = 0; $k < sizeof($data_columns); $k++) {
                    $t_val = 0;
                    if ($cur_status == "total") {
                        // going through all of the status columns but last which is the total itself
                        for ($l = 0; $l < sizeof($stat_columns) - 1; $l++) {
                            $t_status = $stat_columns[$l];
                            $t_val += $day_data[$data_columns[$k]][$date][$kind_id][$t_status];
                        }
                        if ($data_columns[$k] != 'qty') {
                            $t_val = IsicNumber::getMoneyFormatted($t_val);
                        }
                        $t_val = "<b>" . $t_val . "</b>";
                    } else {
                        $t_val = $day_data[$data_columns[$k]][$date][$kind_id][$cur_status];
                        if ($data_columns[$k] != 'qty') {
                            $t_val = IsicNumber::getMoneyFormatted($t_val);
                        }
                    }
                    if ($data_columns[$k] != 'qty') {
                        //$t_val = IsicNumber::getMoneyFormatted($t_val);
                    }
                    $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.KIND.VAL", $t_val ? $t_val : 0);
                }
            }
        }
    }

    private function showOrderedCardsDataRowsTotal($tpl, $stat_columns, $data_columns, $col_data) {
        foreach ($this->card_kind as $kind_id) {
            for ($j = 0; $j < sizeof($stat_columns); $j++) {
                $cur_status = $stat_columns[$j];
                for ($k = 0; $k < sizeof($data_columns); $k++) {
                    $t_val = 0;
                    if ($cur_status == "total") {
                        // going through all of the status columns but last which is the total itself
                        for ($l = 0; $l < sizeof($stat_columns) - 1; $l++) {
                            $t_status = $stat_columns[$l];
                            $t_val += $col_data[$data_columns[$k]][$kind_id][$t_status];
                        }
                    } else {
                        $t_val = $col_data[$data_columns[$k]][$kind_id][$cur_status];
                    }
                    if ($data_columns[$k] != 'qty') {
                        $t_val = IsicNumber::getMoneyFormatted($t_val);
                    }
                    $tpl->addDataItem("SCHOOL.CARD_TYPE.DATA.KIND.VAL", "<b>" . ($t_val ? $t_val : 0) . "</b>");
                }
            }
        }
    }

    /**
     * Handles orderd applications query result and assigns according values to arrays
     *
     * @param array $filters filters data or null
     * @param array $param_list parameter list
     * @param array &$dates dates list
     * @param array &$day_data day data
     * @param array &$col_data column data
     * @return null
     */
    function getOrderedCardsDataAppl($filters, $param_list, &$dates, &$day_data, &$col_data) {
        static $rc;
        if ($filters == null) {
            //$rc = $this->getOrderedCardsQueryAppl($param_list);
            $rc = $this->getOrderedCardsQuery($param_list);
            //echo("<!-- " . $this->db->show_query() . " -->\n");
            return;
        }

        while ($datac = $rc->fetch_assoc()) {
            $schoolId = $filters['school_id'] == -1 ? -1 : $datac['school_id'];
            $typeId = $filters['type_id'] == -1 ? -1 : $datac['type_id'];
            $exportDate = ($schoolId == -1 || $typeId == -1) ? '0000-00-00' : $datac["exported"];

            if ($filters['is_school_filter'] && $filters['school_id'] != $schoolId &&
                $filters['school_id_next'] != $schoolId) {
                //echo "<br>{$filters['school_id']} / {$filters['school_id_next']}  <> {$filters['type_id']} / {$filters['type_id_next']}!!! {$schoolId}";
//                 continue;
            }
            if ($filters['type_id'] != $typeId &&
                $filters['type_id_next'] != $typeId) {
                //continue;
            }
            $kind_id = $datac["kind_id"];
            if (!isset($dates[$schoolId])) {
                $dates[$schoolId] = array();
            }
            if (!isset($dates[$schoolId][$typeId])) {
                $dates[$schoolId][$typeId] = array();
            }
            if (!isset($day_data[$schoolId][$typeId])) {
                $day_data[$schoolId][$typeId] = array();
            }
            if (!isset($col_data[$schoolId][$typeId])) {
                $col_data[$schoolId][$typeId] = array();
            }
            if (!in_array($exportDate, $dates[$schoolId][$typeId])) {
                $dates[$schoolId][$typeId][] = $exportDate;
            }

            switch ($datac["application_type_id"]) {
                case 1: // replace
                    $applType = "replace";
                    break;
                case 2: // prolong
                    $applType = "prolong";
                    break;
                case 3: // first time
                    $applType = "first";
                    break;
                default :
                    $applType = "";
                    break;
            }
            if ($applType) {
                $card_coll = $card_cost = 0;

                if ($datac["confirm_payment_collateral"]) {
                    $card_coll = $this->isicDbCurrency->getSumInGivenCurrency($datac["collateral_sum"], $datac['currency'], $param_list['currency']);
                }
                if ($datac["confirm_payment_cost"]) {
                    $card_cost = $this->isicDbCurrency->getSumInGivenCurrency($datac["cost_sum"], $datac['currency'], $param_list['currency']);
                }

                // qty
                $day_data[$schoolId][$typeId]["qty"][$exportDate][$kind_id][$applType]++;
                $col_data[$schoolId][$typeId]["qty"][$kind_id][$applType]++;
                // collateral sum
                $day_data[$schoolId][$typeId]["sum1"][$exportDate][$kind_id][$applType] += $card_coll;
                $col_data[$schoolId][$typeId]["sum1"][$kind_id][$applType] += $card_coll;
                // cost sum
                $day_data[$schoolId][$typeId]["sum2"][$exportDate][$kind_id][$applType] += $card_cost;
                $col_data[$schoolId][$typeId]["sum2"][$kind_id][$applType] += $card_cost;
            }

            if ($filters['school_id'] != $schoolId) {
//                break;
            }
            if ($filters['type_id'] != $typeId) {
//                break;
            }
        }
    }

    /**
     * Performs query on card table and returns query result object
     *
     * @param array $query_param parameter list
     * @return object query result
     */
    function getOrderedCardsQuery($query_param) {
        $sql = '
            SELECT
                `module_isic_card`.*,
                `module_isic_application`.`currency`,
                IF (`module_isic_application`.`id`, `module_isic_application`.`collateral_sum`, 0) AS `collateral_sum`,
                IF (`module_isic_application`.`id`, `module_isic_application`.`cost_sum`, 0) AS `cost_sum`,
                IF (`module_isic_application`.`id`, `module_isic_application`.`application_type_id`, 3) AS `application_type_id`,
                DATE(`module_isic_card`.`exported`) AS `exported`
            FROM
                `module_isic_card`
            LEFT JOIN `module_isic_application` ON `module_isic_card`.`id` = `module_isic_application`.`card_id`
            LEFT JOIN `module_isic_school` ON `module_isic_school`.`id` = `module_isic_card`.`school_id`
            LEFT JOIN `module_isic_card_type` ON `module_isic_card_type`.`id` = `module_isic_card`.`type_id`
            WHERE
                !
                DATE(`module_isic_card`.`exported`) >= ? AND
                DATE(`module_isic_card`.`exported`) <= ? AND
                DATE(`module_isic_card`.`exported`) >= ?
        ';
        if ($this->user_type == $this->isic_common->user_type_user) {
            $sql .= ' AND `module_isic_card`.`person_number` = ?';
        }

        $sql .= 'ORDER BY `module_isic_card`.`exported`';

        if ($this->user_type == $this->isic_common->user_type_admin) {
            $rc = &$this->db->query($sql, $query_param["condition_sql"], $query_param["beg_date"],
                $query_param["end_date"], "0000-00-00");
        } else {
            $rc = &$this->db->query($sql, $query_param["condition_sql"], $query_param["beg_date"],
                $query_param["end_date"], "0000-00-00", $this->user_code);
        }
        return $rc;
    }
}
