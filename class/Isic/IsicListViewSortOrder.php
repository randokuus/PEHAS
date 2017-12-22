<?php

class IsicListViewSortOrder {

    private $orderFields = false;
    private $orderBy = false;
    private $orderByDefault = false;
    private $sort = false;
    private $sortOrder = 'asc';
    private $sortOrderReverse = 'desc';

    public function __construct($fields, $orderByDefault, $vars) {
        $this->setOrderFields($fields);
        $this->setOrderByDefault($orderByDefault);
        $this->assignSortFields($vars['sort'], $vars['sort_order']);
    }

    public function assignSortFields($sort, $sortOrder) {
        if ($sort != '' && $this->orderFields[$sort]) {
            $this->sort = $sort;
            $this->orderBy = $this->orderFields[$this->sort];
            if ($sortOrder == 'asc') {
                $this->sortOrder = 'asc';
                $this->sortOrderReverse = 'desc';
            }
            else if ($sortOrder == 'desc') {
                $this->sortOrder = 'desc';
                $this->sortOrderReverse = 'asc';
            }
            else {
                $this->sortOrder = 'asc';
                $this->sortOrderReverse = 'desc';
            }
        }

        if (!$this->orderBy) {
            $this->sort = $this->orderByDefault;
            $this->orderBy = $this->orderFields[$this->orderByDefault];
            $this->sortOrder = 'asc';
            $this->sortOrderReverse = 'desc';
        }
    }

    public function showTitleFields($tpl, $txt, $url, $classes = array()) {
        foreach ($this->orderFields as $key => $val) {
            $class = $this->isCurrentField($key) ? 'sort' . ($this->sortOrder == 'desc' ? ' sortUp' : '') : '';
            $tpl->addDataItem('TITLE.URL', $url . '&sort=' . $key . ($this->isCurrentField($key) ? '&sort_order=' . $this->sortOrderReverse : ''));
            $tpl->addDataItem('TITLE.CLASS', $class);
            $tpl->addDataItem('TITLE.NAME', $txt->display($key));
            if (isset($classes[$key])) {
                $tpl->addDataItem('TITLE.TH_CLASS', $classes[$key]);
            }
        }
    }

    private function isCurrentField($field) {
        return $this->sort == $field;
    }

    /**
     * @param $sort the $sort to set
     */
    public function setSort($sort) {
        $this->sort = $sort;
    }

    /**
     * @return the $sort
     */
    public function getSort() {
        return $this->sort;
    }

    /**
     * @param $orderByDefault the $orderByDefault to set
     */
    public function setOrderByDefault($orderByDefault) {
        $this->orderByDefault = $orderByDefault;
    }

    /**
     * @return the $orderByDefault
     */
    public function getOrderByDefault() {
        return $this->orderByDefault;
    }

	/**
     * @param $sortOrderReverse the $sortOrderReverse to set
     */
    public function setSortOrderReverse($sortOrderReverse) {
        $this->sortOrderReverse = $sortOrderReverse;
    }

	/**
     * @param $sortOrder the $sortOrder to set
     */
    public function setSortOrder($sortOrder) {
        $this->sortOrder = $sortOrder;
    }

	/**
     * @param $orderBy the $orderBy to set
     */
    public function setOrderBy($orderBy) {
        $this->orderBy = $orderBy;
    }

	/**
     * @return the $sortOrderReverse
     */
    public function getSortOrderReverse() {
        return $this->sortOrderReverse;
    }

	/**
     * @return the $sortOrder
     */
    public function getSortOrder() {
        return $this->sortOrder;
    }

	/**
     * @return the $orderBy
     */
    public function getOrderBy() {
        return $this->orderBy;
    }

	/**
     * @param $orderFields the $orderFields to set
     */
    public function setOrderFields($orderFields) {
        $this->orderFields = $orderFields;
    }

	/**
     * @return the $orderFields
     */
    public function getOrderFields() {
        return $this->orderFields;
    }
}
