<?php
/**
 * Klass EHIS-e X-Tee teenustega suhtlemiseks. Sisaldab X-Tee teenusega 
 * suhtlemise loogikat ja pakub kasutavatele klassidele välja selge ja lihtsa 
 * liidese EHIS-e X-Tee teenuste kasutamiseks.
 */
class EhisClient {
	/**
	 * Andmekogu nimetus X-Tee süsteemis.
	 * @access private
	 */
	private $_andmekogu = 'ehis';
	
	/**
	 * Päringu tegija isikukood. Hoitakse klassi skoobis, sest vähesed süsteemid 
	 * teevad päringuid erinevate isikute eest samas pöördumises.
	 * @access private
	 */
	private $_isikukood;
	
	/**
	 * Asutuse kood X-Tee süsteemis. Hoitakse klassi skoobis, sest vähesed süsteemid 
	 * teevad pöördumisi erinevate asutuste eest samas pöördumises.
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
	 * @param string $asutus päringu tegija asutuse kood X-Tee süsteemis
	 * @param string $isikukood päringu tegija isikukood
	 */	
	public function __construct($wsdl, $asutus, $isikukood) {
		$this->_wsdl = $wsdl;
		$this->_asutus = $asutus;
		$this->_isikukood = $isikukood;		
	}
	
	/**
	 * Teeb isikute otsimise päringu ja tagastab päringu tulemused EHIS-e 
	 * objektipuuna.
	 * @param eyl_isic_paring $isic_paring päringuobjekt otsitavate isikutega 
	 * @param string $queryId päringu unikaalne id
	 * @return mixed EHIS-e teenuse tagastatud objektipuu otsingutulemustega
	 */
	public function eyl_isic($isic_paring, $queryId = null)	{
		if (!$queryId) {
			$queryId = '0987654321';
		}
			
		$this->_initializeSoapClient('eyl_isic', $queryId);
		
		$result = $this->_client->__soapCall('eyl_isic', array($isic_paring));
		return $result;
	}

	/**
	 * Tagastab viimati tehtud päringu keha.
	 */
	public function getLastRequest() {
		return $this->_client->__getLastRequest();	
	}
	
	/**
	 * Tagastab viimati tehtud päringu päised.
	 */
	public function getLastRequestHeaders()	{
		return $this->_client->__getLastRequestHeaders();	
	}
	
	/**
	 * Tagastab viimati tehtud päringu vastuse keha.
	 */
	public function getLastResponse() {
		return $this->_client->__getLastResponse();	
	}
	
	/**
	 * Tagastab viimati tehtud päringu vastuse päised.
	 */
	public function getLastResponseHeaders() {
		return $this->_client->__getLastResponseHeaders();	
	}

	/**
	 * Initsialiseerib sisemise SOAP-kliendi meetodi jaoks, mida välja hakatakse
	 * kutsuma.
	 * @access private
	 * @param string $method meetod, mida välja hakatakse kutsuma
	 * @param string $queryId päringu id
	 */
	private function _initializeSoapClient($method, $queryId) {
		if ($this->_client == null) {
			$classMap = $this->_getClassMap();
			$options = array('trace' => true, 'classmap' => $classMap);
			$this->_client = new SoapClient($this->_wsdl, $options);
		}
		
		$headers = $this->_getSoapHeadersForMethod('eyl_isic',$queryId);
		$this->_client->__setSoapHeaders($headers);
	}
	
	/**
	 * Tagastab PHP ja WSDL vahelise tüüpide kaardistamise massiivi. Massiiv on 
	 * vajalik selleks, et PHP SOAP-klient koostaks korrektsed SOAP-pärningud.
	 * @access private
	 */
	private function _getClassMap()	{
		$classMap = array(
			'eyl_isic_paring' => 'eyl_isic_paring',
			'EYL_Isik_Paring_array' => 'EYL_Isik_Paring_array',
			'EYL_IsikParing' => 'EYL_IsikParing'
		);
		return $classMap;
	}
	
	/**
	 * Tagastab SOAP-päiste massiivi, mis on vajalik X-Tee pöördumiste jaoks.
	 * Kui on lisaks X-Tee nimeruumi kuuluvatele päistele muude nimeruumide 
	 * päiseid, siis tuleb need päised defineerida siin.
	 * 
	 * @access private
	 * @param string $method meetod, mille jaoks päiseid küsitakse
	 * @param string $queryId päringu ID
	 */
	private function _getSoapHeadersForMethod($method, $queryId) {
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
