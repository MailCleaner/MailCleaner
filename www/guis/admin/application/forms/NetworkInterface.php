<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Network interface form
 */

class Default_Form_NetworkInterface extends Zend_Form
{
	protected $_interfaces = array();
	protected $_interface;
	
	public function __construct($interfaces, $interface)
	{
	    $this->_interfaces = $interfaces;

	    if (!$interface instanceof Default_Model_NetworkInterface) {
	    	$interface = new Default_Model_NetworkInterface();
	    }
	    if ($interface->getName() == '')  {
	    	$this->_interface = $interface->fetchFirst($interfaces);
	    } else {
            $this->_interface = $interface;
	    }

	    parent::__construct();
	}
	
	public function getInterface() {
		return $this->_interface;
	}
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');
		$restrictions = Zend_Registry::get('restrictions');

		$this->setAttrib('id', 'interface_form');
	    $iflist = new Zend_Form_Element_Select('selectinterface', array(
            'label'      => $t->_('Interface')." :",
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $iflist->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_interfaces as $if) {
        	$iflist->addMultiOption($if->getName(), $if->getName());
        }
        $iflist->setValue($this->_interface->getName());
        $this->addElement($iflist);
		

	$enableConfigurator = new Zend_Form_Element_Checkbox('enable_configurator', array(
            'label'   => $t->_('Enable configurator interface (192.168.1.42)'). " :",
            'title' => $t->_("Enable configurator interface (192.168.1.42)"),
            'uncheckedValue' => "false",
            'checkedValue' => "true"
        ));
	require_once('MailCleaner/Config.php');
        $config = new MailCleaner_Config();
	$tmp_file=$config->getOption('VARDIR')."/run/configurator/dis_conf_interface.enable";
	$enableConfigurator->setValue(file_exists($tmp_file) ? "true" : "false");
	$this->addElement($enableConfigurator);

         $address = new  Zend_Form_Element_Text('address', array(
		    'label'    => $t->_('IPv4 address'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $address->setValue($this->_interface->getIPv4Param('address'));
	    $address->addValidator(new Zend_Validate_Ip());
		$this->addElement($address);
		if ($restrictions->isRestricted('NetworkInterface', 'address')) {
			$address->setAttrib('disabled', 'disabled');
		}
		
		$virtaddresses = new  Zend_Form_Element_Textarea('virtual_addresses', array(
				    'label'    => $t->_('Additional IPv4 addresses'). " :",
				    'required' => false,
		            'rows' => 3,
		            'cols' => 30,
				    'filters'    => array('StringTrim')));
		$virtaddresses->setValue(implode("\n", $this->_interface->getIPv4Param('virtual_addresses')));
		require_once('Validate/IpList.php');
		$virtaddresses->addValidator(new Validate_IpList());
		$this->addElement($virtaddresses);
		if ($restrictions->isRestricted('NetworkInterface', 'address')) {
			$virtaddresses->setAttrib('disabled', 'disabled');
		}
		
		$netmask = new  Zend_Form_Element_Text('netmask', array(
		    'label'    => $t->_('Network mask'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $netmask->setValue($this->_interface->getIPv4Param('netmask'));
	    $netmask->addValidator(new Zend_Validate_Ip());
		$this->addElement($netmask);
		if ($restrictions->isRestricted('NetworkInterface', 'netmask')) {
			$netmask->setAttrib('disabled', 'disabled');
		}
		
		$gateway = new  Zend_Form_Element_Text('gateway', array(
		    'label'    => $t->_('Gateway'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $gateway->setValue($this->_interface->getIPv4Param('gateway'));
	    $gateway->addValidator(new Zend_Validate_Ip());
		$this->addElement($gateway);
		if ($restrictions->isRestricted('NetworkInterface', 'gateway')) {
			$gateway->setAttrib('disabled', 'disabled');
		}
		
		$ifname = new Zend_Form_Element_Hidden('interface');
		$ifname->setValue($this->_interface->getName());
		$this->addElement($ifname);
		
		$ipv4mode = new Zend_Form_Element_Select('ipv4mode', array(
				            'label'      => $t->_('Configure IPv4')." :",
				            'required'   => true,
				            'filters'    => array('StringTrim')));
		
		$ipv4mode->addMultiOption('disabled', $t->_('Disabled'));
		$ipv4mode->addMultiOption('static', $t->_('Manually'));
		$ipv4mode->addMultiOption('dhcp', $t->_('Dynamically through DHCP'));
		$ipv4mode->setValue($this->_interface->getIPv4Param('mode'));
                if ($restrictions->isRestricted('NetworkInterface', 'ipv4mode')) {
                        $ipv4mode->setAttrib('disabled', 'disabled');
                }
		$this->addElement($ipv4mode);
		
		$ipv6mode = new Zend_Form_Element_Select('ipv6mode', array(
						            'label'      => $t->_('Configure IPv6')." :",
						            'required'   => true,
						            'filters'    => array('StringTrim')));
		
		$ipv6mode->addMultiOption('disabled', $t->_('Disabled'));
		$ipv6mode->addMultiOption('static', $t->_('Manually'));
		$ipv6mode->addMultiOption('manual', $t->_('Automatically'));
		$ipv6mode->setValue($this->_interface->getIPv6Param('mode'));
		$this->addElement($ipv6mode);
	        if ($restrictions->isRestricted('NetworkInterface', 'ipv6mode')) {
                        $ipv6mode->setAttrib('disabled', 'disabled');
                }	
		$ipv6address = new  Zend_Form_Element_Text('ipv6address', array(
				    'label'    => $t->_('IPv6 address'). " :",
				    'required' => false,
				    'size' => 46,
				    'filters'    => array('StringTrim')));
		$ipv6address->setValue($this->_interface->getIPv6Param('address'));
		$ipv6address->addValidator(new Zend_Validate_Ip());
		$this->addElement($ipv6address);
		if ($restrictions->isRestricted('NetworkInterface', 'address')) {
			$ipv6address->setAttrib('disabled', 'disabled');
		}
		
		$ipv6netmask = new  Zend_Form_Element_Text('ipv6netmask', array(
				    'label'    => $t->_('Prefix length'). " :",
				    'required' => false,
				    'size'     => 3,
				    'filters'    => array('StringTrim')));
		$ipv6netmask->setValue($this->_interface->getIPv6Param('netmask'));
		$ipv6netmask->addValidator(new Zend_Validate_Int());
		$this->addElement($ipv6netmask);
		if ($restrictions->isRestricted('NetworkInterface', 'netmask')) {
			$ipv6netmask->setAttrib('disabled', 'disabled');
		}
		
		$ipv6gateway = new  Zend_Form_Element_Text('ipv6gateway', array(
				    'label'    => $t->_('Gateway'). " :",
				    'required' => false,
				    'size' => 46,
				    'filters'    => array('StringTrim')));
		$ipv6gateway->setValue($this->_interface->getIPv6Param('gateway'));
		$ipv6gateway->addValidator(new Zend_Validate_Ip());
		$this->addElement($ipv6gateway);
		if ($restrictions->isRestricted('NetworkInterface', 'gateway')) {
			$ipv6gateway->setAttrib('disabled', 'disabled');
		}
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		if ($restrictions->isRestricted('NetworkInterface', 'submit')) {
			$submit->setAttrib('disabled', 'disabled');
		}
				
		$reloadnet = new Zend_Form_Element_Button('relaodnetnow', array(
		     'label'    => $t->_('Reload network now')));
		$this->addElement($reloadnet);
		if ($restrictions->isRestricted('NetworkInterface', 'relaodnetnow')) {
			$reloadnet->setAttrib('disabled', 'disabled');
		}
	}
	
	public function setParams($request, $interface) {
		$t = Zend_Registry::get('translate');
                $restrictions = Zend_Registry::get('restrictions');
                if ($restrictions->isRestricted('NetworkInterface', 'submit')) {
                        throw new Exception('Access restricted');
                }
		
        if ($request->getParam('ipv4mode') == 'static') {
        	if ($request->getParam('address') == '') {
        		$f = $this->getElement('address');
        		$f->addError($t->_('Required field'));
        		throw new Exception('IPv4 address must be provided');
        	}
        	if ($request->getParam('netmask') == '') {
        		$f = $this->getElement('netmask');
        		$f->addError($t->_('Required field'));
        		throw new Exception('Network mask must be provided');
        	}
        	$interface->setIPv4Param('mode', 'static');
        	$interface->setIPv4Param('address', $request->getParam('address'));
        	$interface->setIPv4Param('virtual_addresses', $request->getParam('virtual_addresses'));
        	$interface->setIPv4Param('netmask', $request->getParam('netmask'));
        	$interface->setIPv4Param('gateway', $request->getParam('gateway'));
        } elseif ($request->getParam('ipv4mode') == 'dhcp') {
        	$interface->setIPv4Param('mode', 'dhcp');
        } else {
        	$interface->setIPv4Param('mode', 'disabled');
        }
        
        if ($request->getParam('ipv6mode') == 'static') {
        	if ($request->getParam('ipv6address') == '') {
        		$f = $this->getElement('ipv6address');
        		$f->addError($t->_('Required field'));
        		throw new Exception('IPv6 address must be provided');
        	}
        	if ($request->getParam('ipv6netmask') == '') {
        		$f = $this->getElement('ipv6netmask');
        		$f->addError($t->_('Required field'));
        		throw new Exception('Prefix length must be provided');
        	}
        	$interface->setIPv6Param('mode', 'static');
        	$interface->setIPv6Param('address', $request->getParam('ipv6address'));
        	$interface->setIPv6Param('netmask', $request->getParam('ipv6netmask'));
        	$interface->setIPv6Param('gateway', $request->getParam('ipv6gateway'));	
        } elseif ($request->getParam('ipv6mode') == 'manual') {
        	$interface->setIPv6Param('mode', 'manual');
        } else {
        	$interface->setIPv6Param('mode', 'disabled');
        }
	}

}
