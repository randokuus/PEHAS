<?php
/**
 * Klass EHIS-e X-Tee teenustega suhtlemiseks. Sisaldab X-Tee teenusega 
 * suhtlemise loogikat ja pakub kasutavatele klassidele v�lja selge ja lihtsa 
 * liidese EHIS-e X-Tee teenuste kasutamiseks.
 */
class EhisClient
{
	/**
	 * Andmekogu nimetus X-Tee s�steemis.
	 * @access private
	 */
	private $_andmekogu = 'ehis';
	
	/**
	 * P�ringu tegija isikukood. Hoitakse klassi skoobis, sest v�hesed s�steemid 
	 * teevad p�ringuid erinevate isikute eest samas p��rdumises.
	 * @access private
	 */
	private $_isikukood;
	
	/**
	 * Asutuse kood X-Tee s�steemis. Hoitakse klassi skoobis, sest v�hesed s�steemid 
	 * teevad p��rdumisi erinevate asutuste eest samas p��rdumises.
	 * @access private
	 */
	private $_asutus;

	/**
	 * SOAP-kliendi instants.
	 * @access private
	 */
	private $_client;
	
	/**
	 * WSDL-definitsiooni asukoht. 
	 * @access private
	 */
	private $_wsdl;

	/**
	 * Klassi konstruktor
	 * @param string $wsdl teenuse WSDL-definitsiooni asukoht
	 * @param string $asutus p�ringu tegija asutuse kood X-Tee s�steemis
	 * @param string $isikukood p�ringu tegija isikukood
	 */	
	public function __construct($wsdl, $asutus, $isikukood)
	{
		$this->_wsdl = $wsdl;
		$this->_asutus = $asutus;
		$this->_isikukood = $isikukood;		
	}
	
	/**
	 * Teeb isikute otsimise p�ringu ja tagastab p�ringu tulemused EHIS-e 
	 * objektipuuna.
	 * @param eyl_isic_paring $isic_paring p�ringuobjekt otsitavate isikutega 
	 * @param string $queryId p�ringu unikaalne id
	 * @return mixed EHIS-e teenuse tagastatud objektipuu otsingutulemustega
	 */
	public function eyl_isic($isic_paring, $queryId = null)
	{
		if(!$queryId)
			$queryId = '0987654321';
			
		$this->_initializeSoapClient('eyl_isic', $queryId);
		
		$result = $this->_client->__soapCall('eyl_isic', array($isic_paring));
		return $result;
	}

	/**
	 * Tagastab viimati tehtud p�ringu keha.
	 */
	public function getLastRequest()
	{
		return $this->_client->__getLastRequest();	
	}
	
	/**
	 * Tagastab viimati tehtud p�ringu p�ised.
	 */
	public function getLastRequestHeaders()
	{
		return $this->_client->__getLastRequestHeaders();	
	}
	
	/**
	 * Tagastab viimati tehtud p�ringu vastuse keha.
	 */
	public function getLastResponse()
	{
		return $this->_client->__getLastResponse();	
	}
	
	/**
	 * Tagastab viimati tehtud p�ringu vastuse p�ised.
	 */
	public function getLastResponseHeaders()
	{
		return $this->_client->__getLastResponseHeaders();	
	}

	/**
	 * Initsialiseerib sisemise SOAP-kliendi meetodi jaoks, mida v�lja hakatakse
	 * kutsuma.
	 * @access private
	 * @param string $method meetod, mida v�lja hakatakse kutsuma
	 * @param string $queryId p�ringu id
	 */
	private function _initializeSoapClient($method, $queryId)
	{
		if($this->_client == null)
		{
			$classMap = $this->_getClassMap();
			$options = array('trace' => true, 'classmap' => $classMap);
			$this->_client = new SoapClient($this->_wsdl, $options);
		}
		
		$headers = $this->_getSoapHeadersForMethod('eyl_isic',$queryId);
		$this->_client->__setSoapHeaders($headers);
	}
	
	/**
	 * Tagastab PHP ja WSDL vahelise t��pide kaardistamise massiivi. Massiiv on 
	 * vajalik selleks, et PHP SOAP-klient koostaks korrektsed SOAP-p�rningud.
	 * @access private
	 */
	private function _getClassMap()
	{
		$classMap = array(
						'eyl_isic_paring' => 'eyl_isic_paring',
						'EYL_Isik_Paring_array' => 'EYL_Isik_Paring_array',
						'EYL_IsikParing' => 'EYL_IsikParing'
					);
		return $classMap;
	}
	
	/**
	 * Tagastab SOAP-p�iste massiivi, mis on vajalik X-Tee p��rdumiste jaoks.
	 * Kui on lisaks X-Tee nimeruumi kuuluvatele p�istele muude nimeruumide 
	 * p�iseid, siis tuleb need p�ised defineerida siin.
	 * 
	 * @access private
	 * @param string $method meetod, mille jaoks p�iseid k�sitakse
	 * @param string $queryId p�ringu ID
	 */
	private function _getSoapHeadersForMethod($method, $queryId)
	{
		$nsXTee = 'http://x-tee.riik.ee/xsd/xtee.xsd';
		
		$headers = array(
									new SoapHeader($nsXTee,'asutus',$this->_asutus),
									new SoapHeader($nsXTee,'andmekogu',$this->_andmekogu),
									new SoapHeader($nsXTee,'isikukood',$this->_isikukood),
									new SoapHeader($nsXTee,'id',$queryId),
									new SoapHeader($nsXTee,'nimi','ehis.'.$method.'.v1')
								);
		return $headers;		
	}
}
?>