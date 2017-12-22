<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicReport/IsicReport_Report.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicLogger.php");

/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 28/02/2013
 * Time: 10:07:22
 * To change this template use File | Settings | File Templates.
 */
class IsicReport_OrderedCardsDetail extends IsicReport_Report
{

    /**
     * Generates report about ordered cards - detail view
     *
     * @return string parsed html
     */

    function show() {
        $txt = new Text($this->language, "module_isic_report");
        if ($this->vars["print"]) {
            $tmpl_name_suffix = "_print";
        }
        if ($this->vars['export']) {
            $tmpl_name_suffix = '_csv';
        }
        $instanceParameters = '&type=ordered_cards_detail';
        $tpl = $this->isicTemplate->initTemplateInstance("module_isic_report_ordered_cards_detail{$tmpl_name_suffix}.html", $instanceParameters);
        if ($this->isicTemplate->getCached()) {
            return $this->isicTemplate->getCached();
        }

        if ($this->vars["content"]) {
            $general_url = $_SERVER["PHP_SELF"] . "?content=" . $this->vars["content"];
        }

        if (isset($this->vars["back_url"]) && $this->vars["back_url"] != "") {
            $backUrl = htmlspecialchars($this->vars["back_url"]);
            $backUrl = str_replace("amp;", "", $backUrl);
        }
        else {
            $backUrl = "";
            foreach ($this->vars as $paramName => $paramValue) {
                if (preg_match("/^parent_/", $paramName)) {
                    $backUrl .= htmlspecialchars(preg_replace("/^parent_/", "filter_", $paramName). "=". $paramValue). "&";
                }
            }
            if ($backUrl != "") {
                $backUrl = substr($backUrl, 0, strlen($backUrl) - 1);
            }
            $backUrl = $general_url. "&". $backUrl. "&filter=1&back=1";
        }
        $url_cond = array();
        $tpl->addDataItem("TITLE", $txt->display("title_ordered_cards"));
        if ($this->vars["date"]) {
            $tDate = IsicDate::getDateFormatted($this->vars["date"], 'Y-m-d');
            if ($tDate) {
                $beg_date = $tDate . " 00:00:00";
                $end_date = $tDate . " 23:59:59";
                $url_cond[] = "date=" . urlencode($tDate);
            }
        } else if ($this->vars["filter_start_date"] && $this->vars["filter_end_date"]) {
            $this->vars["filter_start_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_start_date"]);
            $beg_date = IsicDate::getDateFormatted($this->vars["filter_start_date"], 'Y-m-d');
            $this->vars["filter_end_date"] = IsicDate::getDateFormattedFromEuroToDb($this->vars["filter_end_date"]);
            $end_date = IsicDate::getDateFormatted($this->vars["filter_end_date"], 'Y-m-d') . ' 23:59:59';
        }

        $school_id = 0;
        if ($this->vars["filter_school_id"]) {
            $school_id = intval($this->vars["filter_school_id"]);
            $url_cond[] = "filter_school_id=" . urlencode($this->vars["filter_school_id"]);
        }

        $type_id = 0;
        if ($this->vars["filter_type_id"]) {
            $type_id = $this->vars["filter_type_id"];
            $url_cond[] = "filter_type_id=" . urlencode($this->vars["filter_type_id"]);
        }

        $this->vars['filter_currency'] = $this->getCurrencyFilter($this->vars['filter_currency']);
        $url_cond[] = "filter_currency=" . urlencode($this->vars['filter_currency']);

        $fields = array(
            "filter_type_id" => array("select",0,0,$this->vars["filter_type_id"],$this->getCardTypeList($txt->display("all_types")),""),
            "filter_kind_id" => array("select",0,0,$this->vars["filter_kind_id"],$this->getKinds($txt),""),
            "filter_school_id" => array("select", 0,0,$this->vars["filter_school_id"],$this->getSchoolList($txt->display("all_schools")),"",""),
            "filter_bank_id" => array("select", 0, 0, $this->vars["filter_bank_id"], $this->isicDbBanks->getBankList($txt->display("all_banks")), "", ""),
            "filter_person_name_first" => array("textinput", 40,0,$this->vars["filter_person_name_first"],"","",""),
            "filter_person_name_last" => array("textinput", 40,0,$this->vars["filter_person_name_last"],"","",""),
            "filter_person_number" => array("textinput", 40,0,$this->vars["filter_person_number"],"","",""),
            "filter_isic_number" => array("textinput", 40,0,$this->vars["filter_isic_number"],"","",""),
            "filter_card_number" => array("textinput", 40,0,$this->vars["filter_card_number"],"","",""),
            "filter_currency" => array("select", 40,0,$this->vars["filter_currency"],$this->isicDbCurrency->getNameList(),"",""),
        );
        $this->showFormFields($fields, $tpl, $txt);
        $condition = array();
        $filter_fields = array("person_name_first", "person_name_last", "person_number", "isic_number", "card_number", "kind_id", "bank_id");
        foreach ($filter_fields as $fkey) {
            $f_val = $this->vars["filter_" . $fkey];
            if ($f_val) {
                $condition[] = '`module_isic_card`.`' . $fkey . '` LIKE ' . $this->db->quote('%' . $f_val . '%');
                $url_cond[] = "filter_" . $fkey . "=" . urlencode($f_val);
            }
        }

        if ($this->vars['filter_school_joined']) {
            if ($this->vars['filter_school_joined'] == '2') {
                $this->vars['filter_school_joined'] = '0';
            }
            $condition[] = '`module_isic_school`.`joined` = ' . $this->db->quote($this->vars["filter_school_joined"]);
        }

        $condition_sql = implode(" AND ", $condition);
        if ($condition_sql) {
            $condition_sql .= " AND";
        }
        $log = new IsicLogger();
        $printUrl = $general_url . "&detail=1&print=1&" . implode('&', $url_cond);
        if ($beg_date && $end_date) {
            $tpl->addDataItem("PRINT.URL", $printUrl);
            $bankNames = $this->isicDbBanks->getBankList('', false);
            $knownCardIds = array();
            foreach ($this->card_kind as $tid => $kind_id) {
                $param_list = array(
                    "condition_sql" => $condition_sql,
                    "beg_date" => $beg_date,
                    "end_date" => $end_date,
                    "type_id" => $type_id,
                    "kind_id" => $kind_id,
                    "school_id" => $school_id
                );
                for ($query_type = 1; $query_type <= 2 ; $query_type++) {
                    if ($query_type == 1) {
                        $rc = $this->getOrderedCardsDetailQuery($param_list);
                    } else {
                        $rc = $this->getOrderedCardsDetailQueryAppl($param_list);
                    }
                    if (!$rc) {
                        continue;
                    }

                    while ($data = $rc->fetch_assoc()) {
                        $cardId = $query_type == 1 ? $data["id"] : $data["card_id"];
                        if (in_array($cardId, $knownCardIds)) {
                            continue;
                        }
                        $knownCardIds[] = $cardId;
                        $paymentData = $this->getPaymentValues($query_type, $data);
                        $status = $this->getOrderedCardsDetailCardStatus($query_type, $data);
                        $tpl->addDataItem("DATA.PERSON_NAME_FIRST", $data["person_name_first"]);
                        $tpl->addDataItem("DATA.PERSON_NAME_LAST", $data["person_name_last"]);
                        $tpl->addDataItem("DATA.PERSON_NUMBER", $data["person_number"]);
                        $tpl->addDataItem("DATA.EXPIRATION_DATE",
                            IsicDate::getDateFormatted($data["expiration_date"]));
                        $tpl->addDataItem("DATA.ISIC_NUMBER", $data["isic_number"]);
                        $tpl->addDataItem("DATA.ACTIVE", $txt->display("active" . $data["active"]));
                        $tpl->addDataItem("DATA.CARD_TYPE_NAME", $data["card_type_name"]);
                        $tpl->addDataItem("DATA.PERSON_STRU_UNIT", $data["person_stru_unit"]);
                        $tpl->addDataItem("DATA.CARD_STATUS_NAME", $txt->display("status_" . $status));
                        $tpl->addDataItem("DATA.IMAGE", IsicImage::getImgTagForUrl(
                            IsicImage::getPictureUrl($data['pic'], 'thumb')));
                        $tpl->addDataItem("DATA.SCHOOL_NAME", $data["school_name"]);
                        $tpl->addDataItem("DATA.ORDER_DATE", IsicDate::getDateFormatted($data["creation_date"]));

                        $compensationSchoolName = '';
                        foreach(array('collateral', 'cost', 'delivery') as $paymentType) {
                            $tpl->addDataItem("DATA.CONFIRM_PAYMENT_{$paymentType}",
                                $txt->display("active" . $data["confirm_payment_" . $paymentType])
                            );
                            $tpl->addDataItem("DATA.{$paymentType}_SUM", $paymentData[$paymentType]['sum']);
                            $tpl->addDataItem("DATA.{$paymentType}_PAYMENT_METHOD",
                                $paymentData[$paymentType]['payment_method'] ?
                                $txt->display('payment_method' . $paymentData[$paymentType]['payment_method']) : ''
                            );
                            $tpl->addDataItem("DATA.{$paymentType}_BANK", $paymentData[$paymentType]['bank'] ?
                                $bankNames[$paymentData[$paymentType]['bank']] : ''
                            );
                            $compensationKeyName = 'compensation_' . $paymentType;
                            if (array_key_exists($compensationKeyName, $paymentData)) {
                                $compensationSchoolName = $paymentData[$compensationKeyName]['compensation_school'];
                                $log->addDebug($paymentData[$compensationKeyName], 'paymentData');
                                $tpl->addDataItem("DATA.{$compensationKeyName}_SUM",
                                    $paymentData[$compensationKeyName]['sum']);
                            }
                        }
                        $log->addDebug($compensationSchoolName, 'comp_school_name');
                        $tpl->addDataItem("DATA.SCHOOL_NAME_COMPENSATION", $compensationSchoolName);
                    }
                }
            }
        }
        $tpl->addDataItem("BACK_URL.URL", $backUrl);
        $hidden = IsicForm::getHiddenField('date', IsicDate::getDateFormatted($beg_date, 'Y-m-d'));
        $hidden .= IsicForm::getHiddenField('back_url', $backUrl);
        $tpl->addDataItem("HIDDEN", $hidden);
        $tpl->addDataItem("SELF", $general_url);

        if ($this->vars["print"]) {
            echo $tpl->parse();
            exit();
        }

        if ($this->vars['export']) {
            IsicExport::showCsv($tpl->parse(), 'ordered_cards.csv');
        }

        return $tpl->parse();
    }

    public function getPaymentValues($query_type, $data)
    {
        $costCollData = array(
            'cost' => array(),
            'collateral' => array(),
            'delivery' => array()
        );
        if ($query_type == 1) { // cards
            if ($data["confirm_payment_collateral"]) {
                $costCollData['collateral'] = $this->getPaymentData($data['id'],
                    $this->isicDbPayments->getTypeCollateral());
            }
            if ($data["confirm_payment_cost"]) {
                $costCollData['cost'] = $this->getPaymentData($data['id'],
                    $this->isicDbPayments->getTypeCost());
                $costCollData['compensation_cost'] = $this->getPaymentData($data['id'],
                    $this->isicDbPayments->getTypeCompensation());
            }
            if ($data["confirm_payment_delivery"]) {
                $costCollData['delivery'] = $this->getPaymentData($data['id'],
                    $this->isicDbPayments->getTypeDelivery());
                $costCollData['compensation_delivery'] = $this->getPaymentData($data['id'],
                    $this->isicDbPayments->getTypeCompensationDelivery());
            }
        } else { // applications
//            $costCollData['collateral']['sum'] = $data["confirm_payment_collateral"] ?
//                IsicNumber::getMoneyFormatted(
//                    $this->isicDbCurrency->getSumInGivenCurrency(
//                        $data["collateral_sum"],
//                        $data['currency'],
//                        $this->vars['filter_currency']
//                    )
//                ) :
//                "";
//            $costCollData['cost']['sum'] = $data["confirm_payment_cost"] ?
//                IsicNumber::getMoneyFormatted(
//                    $this->isicDbCurrency->getSumInGivenCurrency(
//                        $data["cost_sum"],
//                        $data['currency'],
//                        $this->vars['filter_currency']
//                    )
//                ) :
//                "";
        }
        return $costCollData;
    }

    private function getPaymentData($cardId, $paymentType) {
        $paymentData = array();
        $payment = $this->isicDbPayments->getPaymentByCard($cardId, $paymentType);
        $tmpSum = $this->isicDbPayments->getPaymentSumInCurrency($payment, $this->vars['filter_currency']);
        $paymentData['sum'] = IsicNumber::getMoneyFormatted($tmpSum);
        $paymentData['payment_method'] = $payment['payment_method'];
        $paymentData['bank'] = $payment['bank_id'];
        $paymentData['compensation_school'] = '';
        if ($payment['compensation_id']) {
            $compensationData = $this->isicDbSchoolCompensationsUser->getRecord($payment['compensation_id']);
            if ($compensationData) {
                $tmpSchool = $this->isicDbSchools->getRecord($compensationData['school_id']);
                $paymentData['compensation_school'] = $tmpSchool['name'];
            }
        }

        return $paymentData;
    }

    private function getOrderedCardsDetailCardStatus($type, $data)
    {
        $status = '';
        if ($type == 1 && !$data['application_type_id']) { // card
            if ($data["prev_card_id"]) {
                $prev_card_status = $this->isic_common->getCardStatus($data["prev_card_id"]);
                $prolong_status_id = $this->isicDbCardStatuses->getCardStatusProlongId($data["type_id"]);
                if ($prev_card_status == $prolong_status_id) {
                    $status = "prolong";
                } else {
                    $status = "replace";
                }
            } else {
                $status = "first";
            }
        } else { // application
            switch ($data["application_type_id"]) {
                case 1: // replace
                    $status = "replace";
                    break;
                case 2: // prolong
                    $status = "prolong";
                    break;
                case 3: // first time
                    $status = "first";
                    break;
                default :
                    $status = "";
                    break;
            }
        }
        return $status;
    }

    function getOrderedCardsDetailQuery($param_list) {
        $sql = "SELECT
                    `module_isic_card`.*,
                    IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name,
                    IF (`module_isic_application`.`id`, `module_isic_application`.`application_type_id`, 0) AS `application_type_id`
                FROM
                    `module_isic_card`
                LEFT JOIN
                    `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                LEFT JOIN
                    `module_isic_application` ON `module_isic_card`.`id` = `module_isic_application`.`card_id`
                LEFT JOIN
                    `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                WHERE
                    !
                    `module_isic_card`.`exported` >= ? AND
                    `module_isic_card`.`exported` <= ? AND
                    (`module_isic_card`.`type_id` = ! OR ! = 0) AND
                    `module_isic_card`.`exported` > ? AND
                    (`module_isic_card`.`school_id` = ! OR ! = 0) AND
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@) AND
                    `module_isic_card`.`kind_id` = ! ";
        if ($this->user_type == $this->isic_common->user_type_user) {
            $sql .= " AND `module_isic_card`.`person_number` = ?";
        }
        $sql .= " ORDER BY `module_isic_card`.`exported`";

        $max_exported = "2009-09-03";
        if ($param_list["kind_id"] == 2) {
            $max_exported = "2100-01-01";
        }

        if ($this->user_type == $this->isic_common->user_type_admin) {
            $rc = &$this->db->query($sql,
                $param_list["condition_sql"],
                $param_list["beg_date"],
                $param_list["end_date"],
                $param_list["type_id"],
                $param_list["type_id"],
                "0000-00-00",
                $param_list["school_id"],
                $param_list["school_id"],
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $param_list["kind_id"]
            );
        } else if ($this->user_type == $this->isic_common->user_type_user) {
            $rc = &$this->db->query($sql,
                $param_list["condition_sql"],
                $param_list["beg_date"],
                $param_list["end_date"],
                $param_list["type_id"],
                $param_list["type_id"],
                "0000-00-00",
                $max_exported,
                $param_list["school_id"],
                $param_list["school_id"],
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $param_list["kind_id"],
                $this->user_code
            );
        }
        return $rc;
    }

    function getOrderedCardsDetailQueryAppl($param_list) {
        $sql = "SELECT
                    `module_isic_application`.*,
                    `module_isic_card`.`kind_id`,
                    `module_isic_card`.`active` AS `active`,
                    `module_isic_card`.`isic_number` AS `isic_number`,
                    IF(`module_isic_school`.`id`, `module_isic_school`.`name`, '') AS school_name,
                    IF(`module_isic_card_type`.`id`, `module_isic_card_type`.`name`, '') AS card_type_name
                FROM
                    `module_isic_application`,
                    `module_isic_card`
                LEFT JOIN
                    `module_isic_card_type` ON `module_isic_card`.`type_id` = `module_isic_card_type`.`id`
                LEFT JOIN
                    `module_isic_school` ON `module_isic_card`.`school_id` = `module_isic_school`.`id`
                WHERE
                    !
                    `module_isic_card`.`id` = `module_isic_application`.`card_id` AND
                    DATE(`module_isic_card`.`exported`) >= ? AND
                    DATE(`module_isic_card`.`exported`) <= ? AND
                    (`module_isic_card`.`type_id` = ! OR ! = 0) AND
                    `module_isic_card`.`exported` > ? AND
                    (`module_isic_card`.`school_id` = ! OR ! = 0) AND
                    `module_isic_card`.`school_id` IN (!@) AND
                    `module_isic_card`.`type_id` IN (!@) AND
                    `module_isic_card`.`kind_id` = ! ";
        if ($this->user_type == $this->isic_common->user_type_user) {
            $sql .= " AND `module_isic_card`.`person_number` = ?";
        }
        $sql .= " ORDER BY `module_isic_card`.`exported`";

        if ($this->user_type == $this->isic_common->user_type_admin) {
            $rc = &$this->db->query($sql,
                $param_list["condition_sql"],
                $param_list["beg_date"],
                $param_list["end_date"],
                $param_list["type_id"],
                $param_list["type_id"],
                "0000-00-00",
                $param_list["school_id"],
                $param_list["school_id"],
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $param_list["kind_id"]
            );
        } else if ($this->user_type == $this->isic_common->user_type_user) {
            $rc = &$this->db->query($sql,
                $param_list["condition_sql"],
                $param_list["beg_date"],
                $param_list["end_date"],
                $param_list["type_id"],
                $param_list["type_id"],
                "0000-00-00",
                $param_list["school_id"],
                $param_list["school_id"],
                IsicDB::getIdsAsArray($this->allowed_schools),
                IsicDB::getIdsAsArray($this->allowed_card_types_view),
                $param_list["kind_id"],
                $this->user_code
            );
        }
        return $rc;
    }
}
