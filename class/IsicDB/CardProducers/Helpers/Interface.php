<?php

interface CardProducers_Interface {
    
    public function __construct(array $config);
    
    /**
     * Export cards
     * @param array List of cards. Each element is cardData as a fetched database row.
     * @param array Order data as a fetched database row.
     * @return array Should return array of successfully transferred cardDatas
     */
    public function exportCards(array $cardList, array $transferData);
    
    /**
     * Import chips
     * @param array List of cards. Each element is cardData as a fetched database row.
     * @param array Order data as a fetched database row.
     * @return CardProducers_ChipFile
     */
    public function importChips(array $cardList, array $transferData);
    
    public function getTransferName($id, $date, $seq);
    
    public function getConfigFields();
    
}
