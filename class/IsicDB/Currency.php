<?php

class IsicDB_Currency extends IsicDB {

    protected $table = 'module_isic_currency';
    protected $insertableFields = array();
    protected $updateableFields = array();
    protected $searchableFields = array();
    protected $rateList = array();
    protected $nameList = array();
    protected $default = false;

    public function __construct() {
        parent::__construct();
        $this->assignList();
    }

	/**
     * @return the $nameList
     */
    public function getNameList() {
        return $this->nameList;
    }

	/**
     * @return the $rateList
     */
    public function getRateList() {
        return $this->rateList;
    }

    private function assignList() {
        $list = $this->listRecords();
        foreach ($list as $currency) {
            $this->rateList[$currency['name']] = $currency['rate'];
            $this->nameList[$currency['name']] = $currency['name'];
            if ($currency['is_default']) {
                $this->default = $currency['name'];
            }
        }
    }

    public function getRateByName($name) {
        return $this->rateList[$name];
    }

    public function getDefault() {
        return $this->default;
    }

    public function getDefaultRate() {
        return $this->getRateByName($this->getDefault());
    }

    public function getConversionRate($srcCurrency, $tarCurrency) {
        return $this->getRateByName($srcCurrency) / $this->getRateByName($tarCurrency);
    }

    public function getSumInGivenCurrency($sum, $srcCurrency, $tarCurrency) {
        if ($srcCurrency && $srcCurrency != $tarCurrency) {
            return round($sum * $this->getConversionRate($srcCurrency, $tarCurrency), 2);
        }
        return $sum;
    }

    public function getSumInDefaultCurrency($sum, $currency = '') {
        return $this->getSumInGivenCurrency($sum, $currency, $this->getDefault());
    }

}
