<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");

/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 28/02/2013
 * Time: 10:15:33
 * To change this template use File | Settings | File Templates.
 */
class IsicReport_UserStatusChanges extends IsicReport_Report
{
    /**
     * Generates report about user status changes
     *
     * @return string parsed html
     */
    public function show()
    {
        $reportType = 'user_status_changes';
        $recordsPerPage = 100;

        $txt = new Text($this->language, "module_isic_report");
        $txts = new Text($this->language, "module_user_status_user");

        if ($this->vars["print"]) {
            $tmpl_print = "_print";
            $recordsPerPage = 100000;
        }

        $instanceParameters = '&type=' . $reportType;
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_{$reportType}{$tmpl_print}.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
        }

        $cur_time = time();

        if ($this->vars["filter_start_date"]) {
            $beg_date = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_start_date"]);
//            $beg_date = IsicDate::getDateFormatted($this->vars["filter_start_date"], 'Y-m-d');
        } else {
            $beg_date = IsicDate::getTimeStampFormatted(mktime(0, 0, 0, date("n", $cur_time), 1, date("Y", $cur_time)), 'Y-m-d');
        }

        if ($this->vars["filter_end_date"]) {
            $end_date = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_end_date"]);
//            $end_date = IsicDate::getDateFormatted($this->vars["filter_end_date"], 'Y-m-d');
        } else {
            $end_date = IsicDate::getCurrentTimeFormatted('Y-m-d');
        }

        // DISPLAYING FILTERS
        $tpl->addDataItem("PRESET_DATES_START", "'" . implode("','", $this->getPresetDatesStart()) . "'");
        $tpl->addDataItem("PRESET_DATES_END", "'" . implode("','", $this->getPresetDatesEnd()) . "'");
        $tpl->addDataItem("PRESET_DATES_NAME", $this->getPresetDatesName());

        $fields = array (
            "filter_start_date" => array("textinput", 40,0,IsicDate::getDateFormatted($beg_date, 'd/m/Y'),"","","datePicker"),
            "filter_end_date" => array("textinput", 40,0,IsicDate::getDateFormatted($end_date, 'd/m/Y'),"","","datePicker"),
            "filter_preset_dates" => array("select", 40,0,$this->vars["filter_preset_dates"],$this->getPresetDatesList(),"onChange=\"assignStartEndDateByPreseset();\"",""),
            "filter_user_code" => array("textinput",0,0,$this->vars["filter_user_code"],"","",""),
            "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],$this->getSchoolList($txt->display("all_schools")),"",""),
            "filter_status_id" => array("select",0,0,$this->vars["filter_status_id"],"","",""),
            "filter_mod_action" => array("select",0,0,$this->vars["filter_mod_action"],"","",""),
            "filter_mod_origin" => array("select",0,0,$this->vars["filter_mod_origin"],"","",""),
            "filter_mod_user" => array("textinput",0,0,$this->vars["filter_mod_user"],"","",""),
        );

        // status types
        $list = array('' => '');
        $allowedStatusTypes = array();
        foreach ($this->isicDbUserStatusTypes->listAllowedRecords() as $record) {
            $list[$record['id']] = $record['name'];
            $allowedStatusTypes[] = $record['id'];
        }
        $fields["filter_status_id"][4] = $list;

        // modification actions
        $actionsList = array(
            '' => '',
            'add' => $txt->display("mod_action_add"),
            'delete' => $txt->display("mod_action_delete"),
        );
        $fields["filter_mod_action"][4] = $actionsList;

        // modification origins
        $originsList = array('' => '');
        foreach ($this->isicDbUserStatuses->listOrigins() as $origin) {
            $originsList[$origin] = $txts->display("origin_type" . $origin);
        }
        $fields["filter_mod_origin"][4] = $originsList;

        // generating filter fields
        $this->showFormFields($fields, $tpl, $txt);

        $url_cond_print = array();
        $url_cond_print[] = 'filter_start_date=' . IsicDate::getDateFormatted($beg_date, 'd/m/Y');
        $url_cond_print[] = 'filter_end_date=' . IsicDate::getDateFormatted($end_date, 'd/m/Y');
        $filter_fields_print = array('user_code', 'school_id', 'status_id', 'mod_action', 'mod_origin', 'mod_user');
        foreach ($filter_fields_print as $fkey) {
            $f_val = $this->vars["filter_" . $fkey];
            if ($f_val) {
                $url_cond_print[] = "filter_" . $fkey . "=" . urlencode($f_val);
            }
        }
        $printUrl = $general_url . "&filter=1&print=1&" . implode('&', $url_cond_print);

        // PROCESSING QUERY

        if ($this->vars['filter']) {

            // defining base query fields
            $baseFields = array();
            $baseFields[] = "`affected_user`.`user_code` AS 'user_code'";
            $baseFields[] = "`affected_user`.`name_first` AS 'user_name_first'";
            $baseFields[] = "`affected_user`.`name_last` AS 'user_name_last'";
            $baseFields[] = "`status`.`name` AS 'status_name'";
            $baseFields[] = "`school`.`name` AS 'school_name'";
            $baseFields[] = "CONCAT(`admin`.`name_first`, ' ', `admin`.`name_last`) AS `mod_user_name`";

            // defining addition query fields
            $additionFields = $baseFields;
            $additionFields[] = "'add' AS `mod_action`";
            $additionFields[] = "`user_status`.`addtype` AS `mod_origin`";
            $additionFields[] = "DATE(`user_status`.`addtime`) AS `mod_date`";
            $additionFields[] = "`user_status`.`addtime` AS `mod_time`";
            $additionFields = implode(", ", $additionFields);

            // defining deletion query fields
            $deletionFields = $baseFields;
            $deletionFields[] = "'delete' AS `mod_action`";
            $deletionFields[] = "`user_status`.`modtype` AS `mod_origin`";
            $deletionFields[] = "DATE(`user_status`.`modtime`) AS `mod_date`";
            $deletionFields[] = "`user_status`.`modtime` AS `mod_time`";
            $deletionFields = implode(", ", $deletionFields);

            // defining query base sources
            $baseSources = array();
            $baseSources[] = "FROM `module_user_status_user` AS `user_status`";
            $baseSources[] = "JOIN `module_user_users` AS `affected_user` ON `user_status`.`user_id` = `affected_user`.`user`";
            $baseSources[] = "JOIN `module_user_status` AS `status` ON `user_status`.`status_id` = `status`.`id`";
            $baseSources[] = "JOIN `module_isic_school` AS `school` ON `user_status`.`school_id` = `school`.`id`";

            // defining query addition sources
            $additionSources = $baseSources;
            $additionSources[] = "JOIN `module_user_users` AS `admin` ON `user_status`.`adduser` = `admin`.`user`";
            $additionSources = implode(" ", $additionSources);

            // defining query addition sources
            $deletionSources = $baseSources;
            $deletionSources[] = "JOIN `module_user_users` AS `admin` ON `user_status`.`moduser` = `admin`.`user`";
            $deletionSources = implode(" ", $deletionSources);

            // defining base query filters
            $baseFilters = array();
            $schools = $this->vars['filter_school_id'] && in_array($this->vars['filter_school_id'], $this->allowed_schools, true)
                ? array($this->vars['filter_school_id'])
                : $this->allowed_schools;
            if (!in_array(-1, $schools)) {
                $baseFilters[] =  "`school`.`id` IN (" . implode(",", $schools) . ")";
            }

            $statuses = $this->vars['filter_status_id'] && in_array($this->vars['filter_status_id'], $allowedStatusTypes, true)
                ? array($this->vars['filter_status_id'])
                : $allowedStatusTypes;
            $baseFilters[] = "`status`.`id` IN (" . implode(",", $statuses) . ")";
            if ($this->vars['filter_user_code']) {
                $baseFilters[] = "`affected_user`.`user_code` = " . $this->db->quote($this->vars['filter_user_code']);
            }

            // defining addition query filters
            $additionFilters = $baseFilters;
            if ($beg_date) {
                $additionFilters[] = "DATE(`user_status`.`addtime`) >= " . $this->db->quote($beg_date);
            }
            if ($end_date) {
                $additionFilters[] = "DATE(`user_status`.`addtime`) <= " . $this->db->quote($end_date);
            }
            if ($this->vars['filter_mod_origin']) {
                $additionFilters[] = "`user_status`.`addtype` = " . $this->db->quote($this->vars['filter_mod_origin']);
            }
            if ($this->vars['filter_mod_user']) {
                $additionFilters[] = "(
                `admin`.`user_code` LIKE " . $this->db->quote('%' . $this->vars['filter_mod_user'] . '%') . " OR
                CONCAT(`admin`.`name_first`, ' ', `admin`.`name_last`) LIKE " . $this->db->quote('%' . $this->vars['filter_mod_user'] . '%') .
                    ")";
            }
            $additionFilters = implode(" AND ", $additionFilters);

            // defining deletion query filters
            $deletionFilters = $baseFilters;
            $deletionFilters[] = "`user_status`.`modtime` <> '0000-00-00 00:00:00' AND `user_status`.`active` = 0";
            if ($beg_date) {
                $deletionFilters[] = "DATE(`user_status`.`modtime`) >= " . $this->db->quote($beg_date);
            }
            if ($end_date) {
                $deletionFilters[] = "DATE(`user_status`.`modtime`) <= " . $this->db->quote($end_date);
            }
            if ($this->vars['filter_mod_origin']) {
                $deletionFilters[] = "`user_status`.`modtype` = " . $this->db->quote($this->vars['filter_mod_origin']);
            }
            if ($this->vars['filter_mod_user']) {
                $deletionFilters[] = "(
                `admin`.`user_code` LIKE " . $this->db->quote('%' . $this->vars['filter_mod_user'] . '%') . " OR
                CONCAT(`admin`.`name_first`, ' ', `admin`.`name_last`) LIKE " . $this->db->quote('%' . $this->vars['filter_mod_user'] . '%') .
                    ")";
            }
            $deletionFilters = implode(" AND ", $deletionFilters);

            // defining a count query
            $countQuery = array();
            if (!$this->vars['filter_mod_action'] || $this->vars['filter_mod_action'] == "add") {
                $countQuery[] = "SELECT COUNT(*) AS 'count' $additionSources WHERE $additionFilters";
            }
            if (!$this->vars['filter_mod_action'] || $this->vars['filter_mod_action'] == "delete") {
                $countQuery[] = "SELECT COUNT(*) AS 'count' $deletionSources WHERE $deletionFilters";
            }
            $countQuery = implode(" UNION ", $countQuery);

            // defining a data query
            $dataQuery = array();
            if (!$this->vars['filter_mod_action'] || $this->vars['filter_mod_action'] == "add") {
                $dataQuery[] = "SELECT $additionFields $additionSources WHERE $additionFilters";
            }
            if (!$this->vars['filter_mod_action'] || $this->vars['filter_mod_action'] == "delete") {
                $dataQuery[] = "SELECT $deletionFields $deletionSources WHERE $deletionFilters";
            }
            $offset = (max(intval($this->vars['page']), 1) - 1) * $recordsPerPage;
            $dataQuery = implode(" UNION ", $dataQuery) . " ORDER BY `mod_time` DESC LIMIT $offset, $recordsPerPage";

            // executing queries
            $countResult = $this->db->query($countQuery);
            $dataResult = $this->db->query($dataQuery);
//            print('<pre>'.$this->db->_last_sql.'</pre>');

            // displaying page list
            $recordsCount = 0;
            while ($data = $countResult->fetch_assoc()) {
                $recordsCount += $data['count'];
            }

            if ($this->vars["filter"]) {

                if ($recordsCount == 0) {
                    $tpl->addDataItem("MESSAGE.MESSAGE", $txt->display("no_results_found"));
                }

                else {
                    // displaying pages
                    $pagesTotal = max(ceil($recordsCount / $recordsPerPage), 1);
                    $tpl->addDataItem("PAGES.DUMMY", 1);
                    foreach (range(1, $pagesTotal) as $num) {
                        $tpl->addDataItem("PAGES.PAGE.DUMMY", 1);
                        $pageType = max($this->vars['page'], 1) == $num ? 'CURRENT' : 'NOT_CURRENT';
                        $url = $_SERVER["PHP_SELF"] . '?' . http_build_query(array_merge($this->vars, array('page' => $num)));
                        $tpl->addDataItem("PAGES.PAGE.$pageType.NUMBER", $num);
                        $tpl->addDataItem("PAGES.PAGE.$pageType.URL", $url);
                    }

                    // displaying results
                    $tpl->addDataItem("RESULT.TITLE", $txt->display("title_" . $reportType));
                    $tpl->addDataItem("RESULT.PRINT.URL", $printUrl);
                    if ($dataResult) {
                        while ($data = $dataResult->fetch_assoc()) {
                            $tpl->addDataItem("RESULT.RECORD.DUMMY", 1);
                            $data['mod_date'] = IsicDate::getDateTimeFormatted($data['mod_time']);
                            $data['mod_action'] = array_key_exists($data['mod_action'], $actionsList) ? $actionsList[$data['mod_action']] : '';
                            $data['mod_origin'] = array_key_exists($data['mod_origin'], $originsList) ? $originsList[$data['mod_origin']] : '';
                            foreach ($data as $key => $value) {
                                $tpl->addDataItem("RESULT.RECORD.$key", htmlspecialchars($value));
                            }
                        }
                    }
                }
            }
        }

        if ($this->vars["print"]) {
            echo $tpl->parse();
            exit();
        }

        return $tpl->parse();
    }
}
