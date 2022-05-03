<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP ssl settings form
 */

class Default_Form_SmtpTls extends ZendX_JQuery_Form
{
	protected $_mta;
	
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
	           
		$this->setAttrib('id', 'smtptls_form');

        $restrictions = Zend_Registry::get('restrictions');
	    
		$sslenable = new Zend_Form_Element_Checkbox('use_incoming_tls', array(
	        'label'   => $t->_('Enable SSL/TLS'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_mta->getParam('use_incoming_tls')) {
            $sslenable->setChecked(true);
	    }
	    $this->addElement($sslenable);

            $ciphers = new  Zend_Form_Element_Text('ciphers', array(
                        'label'    => $t->_('Accepted ciphers')." :",
			'title' => $t->_("If you are unsure about what to set there, leave the field empty to get default value"),
                        'required' => false,
                        'size' => 40,
                        'filters'    => array()));
            $ciphers->setValue($this->_mta->getParam('ciphers'));
            $this->addElement($ciphers);

	    
	    $ssmtplisten = new Zend_Form_Element_Checkbox('tls_use_ssmtp_port', array(
	        'label'   => $t->_('Enable obsolete SMTPS port 465'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_mta->getParam('tls_use_ssmtp_port')) {
            $ssmtplisten->setChecked(true);
	    }
	    $this->addElement($ssmtplisten);
	    
	    $forbidclearauth = new Zend_Form_Element_Checkbox('forbid_clear_auth', array(
            'label'   => $t->_('Forbid unencrypted SMTP authentication'). " :",
            'title' => $t->_("Force the SMTP authentication to be encrypted"),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('forbid_clear_auth')) {
            $forbidclearauth->setChecked(true);
        }
        $this->addElement($forbidclearauth);
	    
	    $sslcert = new Zend_Form_Element_Textarea('tls_certificate_data', array(
		      'label'    =>  $t->_('Encoded SSL certificate')." :",
		      'required'   => false,
		      'rows' => 7,
		      'cols' => 50));
		$sslcert->setValue($this->_mta->getParam('tls_certificate_data'));
        if ($restrictions->isRestricted('ServicesSMTP', 'certificate')) {
             $sslcert->setAttrib('disabled', 'disabled');
        } else {
            require_once('Validate/PKICertificate.php');
            $sslcert->addValidator(new Validate_PKICertificate());
        }
		$this->addElement($sslcert);
		
		$sslkey = new Zend_Form_Element_Textarea('tls_certificate_key', array(
		      'label'    =>  $t->_('Encoded SSL private key')." :",
		      'required'   => false,
		      'rows' => 7,
		      'cols' => 50));
		$sslkey->setValue($this->_mta->getParam('tls_certificate_key'));
        if ($restrictions->isRestricted('ServicesSMTP', 'certificate')) {
             $sslkey->setAttrib('disabled', 'disabled');
             $sslkey->setValue("-----BEGIN RSA PRIVATE KEY-----\n                  ".$t->_('*** hidden ***')."\n-----END RSA PRIVATE KEY-----");
        } else {
            require_once('Validate/PKIPrivateKey.php');
            $sslkey->addValidator(new Validate_PKIPrivateKey());
        }
		$this->addElement($sslkey);
		
		require_once('Validate/HostList.php');
        $require_tls = new Zend_Form_Element_Textarea('hosts_require_tls', array(
              'label'    =>  $t->_('Force encryption to these hosts')." :",
              'title' => $t->_("Force the MailCleaner server to use enryption when dealing with those distant hosts"),
              'required'   => false,
              'rows' => 5,
              'cols' => 30,
              'filters'    => array('StringToLower', 'StringTrim')));
        $require_tls->addValidator(new Validate_HostList());
        $require_tls->setValue($this->_mta->getParam('hosts_require_tls'));
        $this->addElement($require_tls);
        
        $require_incoming_tls = new Zend_Form_Element_Textarea('hosts_require_incoming_tls', array(
              'label'    =>  $t->_('Force encryption from these hosts')." :",
              'title' => $t->_("Force the distant server to use encryption when dealing with the MailCleaner server"),
              'required'   => false,
              'rows' => 5,
              'cols' => 30,
              'filters'    => array('StringToLower', 'StringTrim')));
        $require_incoming_tls->addValidator(new Validate_HostList());
        $require_incoming_tls->setValue($this->_mta->getParam('hosts_require_incoming_tls'));
        $this->addElement($require_incoming_tls);
        
        require_once('Validate/DomainList.php');
        $domains_require_tls_to = new Zend_Form_Element_Textarea('domains_require_tls_to', array(
              'label'    =>  $t->_('Force encryption to these external domains')." :",
              'title' => $t->_("Force the MailCleaner server to use enryption when dealing with those distant domains"),
              'required'   => false,
              'rows' => 5,
              'cols' => 30,
              'filters'    => array('StringToLower', 'StringTrim')));
        $domains_require_tls_to->addValidator(new Validate_DomainList());
        $domains_require_tls_to->setValue($this->_mta->getParam('domains_require_tls_to'));
        $this->addElement($domains_require_tls_to);
        
        $domains_require_tls_from = new Zend_Form_Element_Textarea('domains_require_tls_from', array(
              'label'    =>  $t->_('Force encryption from these external domains')." :",
              'title' => $t->_("Force the distant domains to use encryption when dealing with the MailCleaner server"),
              'required'   => false,
              'rows' => 5,
              'cols' => 30,
              'filters'    => array('StringToLower', 'StringTrim')));
        $domains_require_tls_from->addValidator(new Validate_DomainList());
        $domains_require_tls_from->setValue($this->_mta->getParam('domains_require_tls_from'));
        $this->addElement($domains_require_tls_from);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

	public function setParams($request, $mta) {
        $restrictions = Zend_Registry::get('restrictions');
		$mta->setParam('use_incoming_tls', $request->getParam('use_incoming_tls'));
		if ($request->getParam('use_incoming_tls')) {
			$mta->setParam('forbid_clear_auth', $request->getParam('forbid_clear_auth'));
			$mta->setParam('tls_use_ssmtp_port', $request->getParam('tls_use_ssmtp_port'));
			if (!$restrictions->isRestricted('ServicesSMTP', 'certificate')) {
				$mta->setParam('tls_certificate_data', $request->getParam('tls_certificate_data'));
				$mta->setParam('tls_certificate_key', $request->getParam('tls_certificate_key'));
			}
			$mta->setParam('hosts_require_tls', $request->getParam('hosts_require_tls'));
			$mta->setParam('hosts_require_incoming_tls', $request->getParam('hosts_require_incoming_tls'));
			$mta->setParam('domains_require_tls_from', $request->getParam('domains_require_tls_from'));
			$mta->setParam('domains_require_tls_to', $request->getParam('domains_require_tls_to'));
			if ( $request->getParam('ciphers') == '')  {
				$mta->setParam('ciphers', 'ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM:!SSLv2');
			} else {
				$mta->setParam('ciphers', $request->getParam('ciphers'));
			}

		}
	}
	
}
