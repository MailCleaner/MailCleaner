<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Local callout form
 */

class Default_Form_Domain_AddressVerification_Local
{
	protected $_domain;
	
	public function __construct($domain)
	{
	    $this->_domain = $domain;
	}
	
	public function addForm($form) {
		$name = new Zend_Form_Element_Hidden('connector');
		$name->setValue('local');
		$form->addElement($name);
		
		$t = Zend_Registry::get('translate');
	}
	
	public function setParams($request, $domain) {
	   $this->setParamsFromArray(array(), $domain);
	}
	
	public function setParamsFromArray($array, $domain) {
       $domain->setParam('adcheck', 'false');
       $domain->setParam('callout', 'false');
       $domain->setParam('addlistcallout', 'true');
       $domain->setCalloutConnector('local');
	}

	public function getParams() {
		return array();
	}
	
	public function getParamsString($params) {
		return '';
	}
}
