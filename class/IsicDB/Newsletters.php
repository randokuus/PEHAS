<?php

class IsicDB_Newsletters extends IsicDB
{

    protected $table = 'module_isic_newsletters';
    protected $searchableFields = array("id", "name", "card_types", "active");
    protected $insertableFields = array("name", "card_types", "active");
    protected $updateableFields = array("name", "card_types", "active");

    public function getAllActiveNewsletters()
    {
        return $this->findRecords(array("active" => "1"));
    }

    public function getActiveNewslettersNames()
    {
        $newslettersList = $this->findRecords(array("active" => "1"));
        $list = array();
        foreach ($newslettersList as $newsletterData) {
            $list[$newsletterData["id"]] = $newsletterData["name"];
        }
        return $list;
    }

    public function getAllowedNewslettersByCardType($cardType)
    {
        $return = array();
        $newslettersList = $this->getAllActiveNewsletters();
        foreach ($newslettersList as $newsletterData) {
            $cardTypes = parent::getIdsAsArray($newsletterData["card_types"]);
            if (in_array($cardType, $cardTypes)) {
                $return[] = $newsletterData;
            }
        }

        return $return;
    }

    public function getNameListByAllowedNewsletters(array $cardTypeIds)
    {
        if (!is_array($cardTypeIds)) {
            $cardTypeIds = (array)$cardTypeIds;
        }
        $newslettersList = $this->getAllActiveNewsletters();
        $list = array();
        foreach ($newslettersList as $newsletterData) {
            $cardTypes = parent::getIdsAsArray($newsletterData["card_types"]);
            if (count(array_intersect($cardTypeIds, $cardTypes)) > 0) {
                $newletterId = $newsletterData["id"];
                $list[$newsletterData["id"]] = $newsletterData["name"];
            }
        }
        return $list;
    }

    public function createIfNotExists($newsletterName, $cardTypes)
    {
        $newsletterList = $this->findRecords(array("name" => $newsletterName));
        if (count($newsletterList) == 0) {
            $this->insertRecord(array("name" => $newsletterName, "card_types" => $cardTypes, "active" => '1'));
        }
    }

    public function getIdByName($name)
    {
        $newsletterList = $this->findRecords(array("name" => $name));
        if (count($newsletterList) > 0) {
            return $newsletterList[0]["id"];
        }
        return false;
    }

    public function getNameById($id)
    {
        $newsletterList = $this->getRecord($id);
        if ($newsletterList) {
            return $newsletterList["name"];
        }
        return false;
    }

}
