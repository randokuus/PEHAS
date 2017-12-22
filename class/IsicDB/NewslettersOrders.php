<?php

class IsicDB_NewslettersOrders extends IsicDB
{
    protected $table = 'module_isic_newsletters_orders';
    protected $searchableFields = array("id", "user", "newsletter_id", "application_id", "active", "mod_date");
    protected $insertableFields = array("id", "user", "newsletter_id", "application_id", "active", "mod_date", "mod_user");
    protected $updateableFields = array("active", "mod_date", "mod_user");

    private function getActiveStatus($id)
    {
        $orders = $this->getRecord($id);
        return $orders["active"];
    }

    public function saveReport($reportText)
    {
        $reportName = date("Y-m-d_H:i:s");
        file_put_contents(NEWSLETTER_ORDERS_REPORT_PATH . "/" . $reportName . ".csv", $reportText);
        $this->db->query("INSERT INTO `files`(`type`, `name`, `folder`, `add_date`) VALUES ('csv', '{$reportName}', '/newsletters/', '{$this->db->now()}')");
        //echo $this->db->show_query(). "<br>";
    }

    public function getRecordsForReportAsResultHandle($startDate = "", $endDate = "")
    {
        $dateConditions = array();
        $ordersTable = $this->getTableQuoted();
        $newslettersTable = IsicDB::factory('Newsletters')->getTableQuoted();
        $usersTable = IsicDB::factory('Users')->getTableQuoted();
        if ($startDate) {
            $dateConditions[] = "$ordersTable.`mod_date` >= " . $this->db->quote($startDate . " 00:00:00");
        }
        if ($endDate) {
            $dateConditions[] = "$ordersTable.`mod_date` <= " . $this->db->quote($endDate . " 23:59:59");
        }
        $dateWhere = "1";
        if (count($dateConditions) > 0) {
            $dateWhere = implode(" AND ", $dateConditions);
        }
        // fields are selected in the same order they appear in CSV
        $res = $this->db->query("
            SELECT
                $usersTable.`name_first`,
                $usersTable.`name_last`,
                $usersTable.`email`,
                $newslettersTable.`name`,
                IF ($ordersTable.`active` = '1', 'Active', 'Cancelled'),
                $ordersTable.`mod_date`
            FROM $ordersTable
                LEFT JOIN $newslettersTable ON $ordersTable.`newsletter_id` = $newslettersTable.`id`
                LEFT JOIN $usersTable ON $ordersTable.`user` = $usersTable.`user`
            WHERE !
            ORDER BY
                $ordersTable.`mod_date` ASC",
            $dateWhere
        );
        $this->assertResult($res);
        return $res;
    }

    private function getUserNewsletterIds($userid)
    {
        $return = array();
        $userNewslettersList = $this->findRecords(array("user" => $userid, "active" => '1'));
        foreach ($userNewslettersList as $userNewsletterData) {
            $return[] = $userNewsletterData["newsletter_id"];
        }
        return $return;
    }

    public function isNewsletterInUserOrder($newsletterId, $userid)
    {
        return (count($this->findRecords(array("user" => $userid, "newsletter_id" => $newsletterId, "active" => '1'))) > 0);
    }

    public function filterExistingNewslettersInOrders(array $newslettersList, $userid, $isExist = false)
    {
        $return = array();
        $userNewslettersList = $this->getUserNewsletterIds($userid);
        foreach ($newslettersList as $newsletterId => $newsletterName) {
            if (in_array($newsletterId, $userNewslettersList) == $isExist) {
                $return[$newsletterId] = $newsletterName;
            }
        }
        return $return;
    }

    public function updateUserOrders($userId, $applicationId, array $newsletterList, $whoEdit = "", $doCancel = false)
    {
        $date = date("Y-m-d H:i:s");
        if (!is_array($newsletterList)) {
            $newsletterList = explode(",", $newsletterList);
        }
        $existsOrders = $this->findRecords(array("user" => $userId));
        $existNewslettersOrders = array();
        foreach ($existsOrders as $order) {
            $existNewslettersOrders[$order["newsletter_id"]] = $order["id"];
        }
        foreach ($newsletterList as $newsletterId) {
            if (isset($existNewslettersOrders[$newsletterId])) {
                $orderId = $existNewslettersOrders[$newsletterId];
                if ($this->getActiveStatus($orderId) == '0') {
                    $this->updateRecord(
                        $orderId,
                        array(
                            "active" => "1",
                            "mod_date" => $date,
                            "mod_user" => $whoEdit
                        )
                    );
                }
                unset($existNewslettersOrders[$newsletterId]);
            } else {
                $this->insertRecord(
                    array(
                        "user" => $userId,
                        "newsletter_id" => $newsletterId,
                        "application_id" => $applicationId,
                        "active" => "1",
                        "mod_date" => $date,
                        "mod_user" => $whoEdit
                    )
                );
            }
        }
        if ($doCancel) {
            foreach ($existNewslettersOrders as $orderId) {
                $this->updateRecord(
                    $orderId,
                    array(
                        "active" => "0",
                        "mod_date" => $date,
                        "mod_user" => $whoEdit
                    )
                );
            }
        }
    }

    public function createIfNotExists($newsletterId, $userid, $applicationId)
    {
        $orderList = $this->findRecords(array("user" => $userid, "newsletter_id" => $newsletterId));
        if (count($orderList) == 0) {
            $this->insertRecord(array("user" => $userid, "newsletter_id" => $newsletterId, "application_id" => $applicationId, "active" => '1', "mod_date" => date("Y-m-d H:i:s"), "mod_user" => "system"));
        }
    }

    public function getUserNewslettersByApplicationId($userid, $applicationId)
    {
        $return = array();
        $newsletters = IsicDB::factory('Newsletters');
        $newsletterNames = $newsletters->getActiveNewslettersNames();;
        $orderList = $this->findRecords(array("user" => $userid, "application_id" => $applicationId, "active" => '1'));
        foreach ($orderList as $orderData) {
            if ($orderData["application_id"] != '0') {
                $return[$orderData["newsletter_id"]] = $newsletterNames[$orderData["newsletter_id"]];
            }
        }
        return $return;
    }

}
