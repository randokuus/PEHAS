<?php
/**
 * EHIS-e päringu konteinertüüp.
 */
class eyl_isic_paring {
	/**
	 * Element päringu andmete hoidmiseks. 
	 */
	public $data;	 	
}

/**
 * Isikute päringu massiiv. Pseudotüüp, mida koodis otseselt vaja ei ole,
 * kuid mille olemasolu nõuab SOAP-klient.
 */
class EYL_Isik_Paring_array {
	/**
	 *
	 */
	public $item;
}

/**
 * Isikute päringu tegelik definitsioon. Selle klassi põhjal luuakse objektid 
 * ostitavate isikute jaoks.
 */
class EYL_IsikParing {
	public $isikukood;
	public $eesnimi;
	public $perenimi;
	public $synni_kp;	
}
