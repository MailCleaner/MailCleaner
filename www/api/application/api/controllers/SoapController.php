<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 *
 * index page controller
 */

class SoapController extends Zend_Controller_Action
{

	private $_url = '';
	private $_options = array (
          'soap_version' => SOAP_1_2,
          'uri' => '',
	);

	public function init()
	{
		Zend_Registry::set('soap', true);

		$this->_url = 'http://';
		if (isset($_SERVER['HTTPS'])) {
			$this->_url = 'https://';
		}
		$this->_url .= 'localhost'.":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI']."?wsdl";
		$this->_options{'uri'} = $this->_url;

		require_once('SoapInterface.php');

	}

	public function indexAction()
	{
		if(isset($_GET['wsdl'])) {
			$this->handleWSDL();
		} else {
			$this->handleSOAP();
		}
	}

	private function handleWSDL() {
		require_once('Zend/Soap/AutoDiscover.php');
		$autodiscover = new Zend_Soap_AutoDiscover();
		$autodiscover->setClass('Api_Model_SoapInterface');
		$autodiscover->handle();
	}

	private function handleSOAP() {

		require_once('Zend/Soap/Server.php');
		$server = new Zend_Soap_Server($this->_url);

		$server->setClass('Api_Model_SoapInterface');
		$server->setObject(new Api_Model_SoapInterface());

		$server->handle();
	}
}