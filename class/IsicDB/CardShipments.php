<?php

class IsicDB_CardShipments extends IsicDB {

    protected $table = 'module_isic_card_shipment';

    protected $insertableFields = array(
    );

    protected $updateableFields = array(
    );

    protected $searchableFields = array(
        'active'
    );
}
