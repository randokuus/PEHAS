<?php

class IsicDB_CardTypeSchools extends IsicDB {

    protected $table = 'module_isic_card_type_school';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'type_id', 'school_id'
    );

    public function getPictureExpiration($id, $schoolId) {
        $cardTypeRecord = $this->findRecord(array(
            'type_id' => $id,
            'school_id' => $schoolId
        ));
        if ($cardTypeRecord) {
            return intval($cardTypeRecord['picture_expiration']);
        }
        /** @var IsicDB_CardTypes $cardTypes */
        $cardTypes = IsicDB::factory('CardTypes');
        return $cardTypes->getPictureExpiration($id);
    }
}
