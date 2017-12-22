<?php

class IsicDB_BankCardPic extends IsicDB {

    protected $table = 'module_isic_bank_pic';

    protected $insertableFields = array(
        'bank_id', 'pic', 'isic_pic'
    );

    protected $updateableFields = array(
        'bank_id', 'pic', 'isic_pic'
    );

    protected $searchableFields = array(
        'bank_id', 'pic', 'isic_pic'
    );
}
