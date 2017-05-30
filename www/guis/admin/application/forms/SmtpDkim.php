<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP greylisting form
 */

class Default_Form_SmtpDkim extends ZendX_JQuery_Form
{
	private $_mta;
	public $domain = NULL;
	public $selector = NULL;
	public $pubkey = NULL;
	
    public function __construct($mta) {
    	$this->_mta = $mta;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'dkim_form');
	    
	    $domain = new  Zend_Form_Element_Text('dkim_default_domain', array(
	        'label' => 'Default DKIM domain'." :",
            'required' => false,
            'size' => 30,
            'filters'    => array('StringToLower','StringTrim')));
        $domain->setValue($this->_mta->getParam('dkim_default_domain'));
        require_once('Validate/DomainName.php');
        $domain->addValidator(new Validate_DomainName());
        $this->addElement($domain);
        
        $selector = new  Zend_Form_Element_Text('dkim_default_selector', array(
            'label' => 'Default DKIM selector'." :",
            'required' => false,
            'size' => 30,
            'filters'    => array('StringToLower','StringTrim')));
        $selector->setValue($this->_mta->getParam('dkim_default_selector'));
        $this->addElement($selector);
        
        $pkey = new Zend_Form_Element_Textarea('dkim_default_pkey', array(
              'label'    =>  $t->_('Default private key')." :",
              'required'   => false,
              'class' => 'pki_privatekey',
              'rows' => 7,
              'cols' => 50));
        $pkey->setValue($this->_mta->getParam('dkim_default_pkey'));      
        require_once('Validate/PKIPrivateKey.php');
        $pkey->addValidator(new Validate_PKIPrivateKey());
        
        $this->addElement($pkey);
        
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);

        $this->setDKIMValues();
	}

    public function setParams($request, $mta) {
		$mta->setparam('dkim_default_domain', $request->getParam('dkim_default_domain'));
        $mta->setparam('dkim_default_selector', $request->getParam('dkim_default_selector'));
        $mta->setparam('dkim_default_pkey', $request->getParam('dkim_default_pkey'));

        $this->setDKIMValues();
	}
	
	private function setDKIMValues() {
		$t = Zend_Registry::get('translate');
		        
		if ($this->_mta->getParam('dkim_default_domain') != '') {
            $this->domain = $this->_mta->getParam('dkim_default_domain');
        } else {
        	if ($this->_mta->getParam('dkim_default_selector') != '' ||
        	    $this->_mta->getParam('dkim_default_pkey') != '') {
        	    $f = $this->getElement('dkim_default_domain');
                $f->addError($t->_('Required field'));
        
        	    throw new Exception('DKIM Domain cannot be empty');    	
        	}
            $this->domain = NULL;
        }
        if ($this->_mta->getParam('dkim_default_selector') != '') {
            $this->selector = $this->_mta->getParam('dkim_default_selector');
        } else {
        	if ($this->_mta->getParam('dkim_default_domain') != '' ||
        	    $this->_mta->getParam('dkim_default_pkey') != '') {
        		throw new Exception('DKIM Selector cannot be empty');
        	}
            $this->selector = NULL;
        }
        
        $key = new Default_Model_PKI();
        $key->setPrivateKey($this->_mta->getParam('dkim_default_pkey'));
        $this->pubkey = $key->getPublicKeyNoPEM();
	}
}