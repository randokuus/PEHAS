<?php
/**
 * EHIS-e p�ringu konteinert��p.
 */
class eyl_isic_paring 
{
	/**
	 * Element p�ringu andmete hoidmiseks. 
	 */
	public $data;	 	
}

/**
 * Isikute p�ringu massiiv. Pseudot��p, mida koodis otseselt vaja ei ole,
 * kuid mille olemasolu n�uab SOAP-klient.
 */
class EYL_Isik_Paring_array
{
	/**
	 *
	 */
	public $item;
}

/**
 * Isikute p�ringu tegelik definitsioon. Selle klassi p�hjal luuakse objektid 
 * ostitavate isikute jaoks.
 */
class EYL_IsikParing
{
	public $isikukood;
	public $eesnimi;
	public $perenimi;
	public $synni_kp;	
}
?>