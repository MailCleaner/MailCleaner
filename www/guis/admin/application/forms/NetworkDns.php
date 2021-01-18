<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * DNS settings form
 */

class Default_Form_NetworkDns extends Zend_Form
{
	protected $_dns;
	
	public function __construct($dnsconfig) {
		$this->_dns = $dnsconfig;
		parent::__construct();
	}
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');
                $restrictions = Zend_Registry::get('restrictions');

		$search = new  Zend_Form_Element_Text('domainsearch', array(
		    'label'    => $t->_('DomainSearch'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $search->setValue($this->_dns->getDomainSearch());
	    require_once('Validate/DomainName.php');
            $search->addValidator(new Validate_DomainName());
            if ($restrictions->isRestricted('NetworkDns', 'domainsearch')) {
                $search->setAttrib('disabled', 'disabled');
            }
		$this->addElement($search);
		
		$primary = new  Zend_Form_Element_Text('primarydns', array(
		    'label'    => $t->_('Primary DNS server'). " :",
                    'title'    => $t->_("We recommend to use the local DNS (or the master's one if you use the cluster version) as primary DNS"),
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $primary->setValue($this->_dns->getNameServer(1));
	    $primary->addValidator(new Zend_Validate_Ip());
            if ($restrictions->isRestricted('NetworkDns', 'primarydns')) {
                $primary->setAttrib('disabled', 'disabled');
            }
		$this->addElement($primary);
		
		$secondary = new  Zend_Form_Element_Text('secondarydns', array(
		    'label'    => $t->_('Secondary DNS server'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $secondary->setValue($this->_dns->getNameServer(2));
	    $secondary->addValidator(new Zend_Validate_Ip());
            if ($restrictions->isRestricted('NetworkDns', 'secondarydns')) { 
                $secondary->setAttrib('disabled', 'disabled');
            }
		$this->addElement($secondary);
		
		$tertiary = new  Zend_Form_Element_Text('tertiarydns', array(
		    'label'    => $t->_('Tertiary DNS server'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $tertiary->setValue($this->_dns->getNameServer(3));
	    $tertiary->addValidator(new Zend_Validate_Ip());
            if ($restrictions->isRestricted('NetworkDns', 'tertiarydns')) { 
                $tertiary->setAttrib('disabled', 'disabled');
            }
		$this->addElement($tertiary);
		
		$heloname = new  Zend_Form_Element_Text('heloname', array(
				    'label'    => $t->_('Force HELO / EHLO identity with'). " :",
				    'required' => false,
				    'size' => 40,
				    'filters'    => array('StringTrim')));
		
		$heloname->setValue($this->_dns->getHeloName());
		$heloname->addValidator(new Zend_Validate_Hostname(
			Zend_Validate_Hostname::ALLOW_DNS |
			Zend_Validate_Hostname::ALLOW_LOCAL));
		if ($restrictions->isRestricted('NetworkDns', 'heloname')) {
			$heloname->setAttrib('disabled', 'disabled');
		}
		$this->addElement($heloname);
		
		$submit = new Zend_Form_Element_Submit('dnssubmit', array(
		     'label'    => $t->_('Submit')));
            if ($restrictions->isRestricted('NetworkDns', 'dnssubmit')) { 
                $submit->setAttrib('disabled', 'disabled');
            }
		$this->addElement($submit);
		
	}

}
