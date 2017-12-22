<?php

class IsicDB_ApplicationTypes extends IsicDB {
    const APPLICATION_TYPE_REPLACE = 1;
    const APPLICATION_TYPE_PROLONG = 2;
    const APPLICATION_TYPE_FIRST = 3;

    protected $table = 'module_isic_application_type';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
    );

    protected $applTypeNames = false;

    protected $compensatedApplicationTypes = array(
        self::APPLICATION_TYPE_FIRST
    );

    public function getNameById($id) {
        if ($this->applTypeNames) {
            return $this->applTypeNames[$id];
        }
        $this->applTypeNames = array();
        $r = &$this->db->query("SELECT `id`, `name` FROM `module_isic_application_type`");
        while ($data = $r->fetch_assoc()) {
            $this->applTypeNames[$data["id"]] = $data["name"];
        }
        return $this->applTypeNames[$id];
    }

    public function getTypeReplace() {
        return self::APPLICATION_TYPE_REPLACE;
    }

    public function getTypeProlong() {
        return self::APPLICATION_TYPE_PROLONG;
    }

    public function getTypeFirst() {
        return self::APPLICATION_TYPE_FIRST;
    }

    /**
     * @return array
     */
    public function getCompensatedApplicationTypes()
    {
        return $this->compensatedApplicationTypes;
    }

    public function isCompensatedApplicationType($applicationType) {
        return in_array($applicationType, $this->getCompensatedApplicationTypes());
    }
}
