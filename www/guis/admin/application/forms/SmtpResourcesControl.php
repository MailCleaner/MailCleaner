<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP resources control form
 */

class Default_Form_SmtpResourcesControl extends ZendX_JQuery_Form
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
	           
		$this->setAttrib('id', 'resourcescontrol_form');
	    
		$conntimeout = new  Zend_Form_Element_Text('smtp_receive_timeout', array(
	        'label'    => $t->_('SMTP session timeout')." :",
                'title' => $t->_("Maximum time between each telnet commands composing the SMTP session"),
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
		
		$value = $this->_mta->getParam('smtp_receive_timeout');
		$value = preg_replace('/[^0-9]/', '', $value);
	    $conntimeout->setValue($value);
        $conntimeout->addValidator(new Zend_Validate_Int());
		$this->addElement($conntimeout);


                $max_rcpt = new  Zend_Form_Element_Text('max_rcpt', array(
                        'label'    => $t->_('Maximum number of recipients')." :",
                        'title' => $t->_("Maximum number of a recipients for a message"),
                        'required' => false,
                        'size' => 3,
                        'class' => 'fieldrighted',
                        'filters'    => array('Alnum', 'StringTrim')));

                $value = $this->_mta->getParam('max_rcpt');
                $value = preg_replace('/[^0-9]/', '', $value);
                $max_rcpt->setValue($value);
                $max_rcpt->addValidator(new Zend_Validate_Int());
                $this->addElement($max_rcpt);

	    
	    $maxsimconn = new  Zend_Form_Element_Text('smtp_accept_max', array(
	        'label'    => $t->_('overall')." :",
                'title' => $t->_("Number of maximum simultaneous connections"),
		    'required' => false,
		    'size' => 6,
            'class' => 'max_conn_field',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $maxsimconn->setValue($this->_mta->getParam('smtp_accept_max'));
        $maxsimconn->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxsimconn);
	    
	    $maxhostconn = new  Zend_Form_Element_Text('smtp_accept_max_per_host', array(
	        'label'    => $t->_('per external host')." :",
                'title' => $t->_("Number of maximum simultaneous connections per external IP"),
		    'required' => false,
		    'size' => 6,
            'class' => 'max_conn_field',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $maxhostconn->setValue($this->_mta->getParam('smtp_accept_max_per_host'));
        $maxhostconn->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxhostconn);

	    $maxhosttrustconn = new  Zend_Form_Element_Text('smtp_accept_max_per_trusted_host', array(
            'label'    => $t->_('per trusted host')." :",
            'title' => $t->_("Number of maximum simultaneous connections per trusted host"),
            'required' => false,
            'size' => 6,
	        'class' => 'max_conn_field',
            'filters'    => array('Alnum', 'StringTrim')));
        $maxhosttrustconn->setValue($this->_mta->getParam('smtp_accept_max_per_trusted_host'));
        $maxhosttrustconn->addValidator(new Zend_Validate_Int());
        $this->addElement($maxhosttrustconn);
        
	    $reserveconn = new  Zend_Form_Element_Text('smtp_reserve', array(
            'label'    => $t->_('Reserved connections for relaying hosts')." :",
            'required' => false,
            'size' => 6,
	        'class' => 'max_conn_field',
            'filters'    => array('Alnum', 'StringTrim')));
        $reserveconn->setValue($this->_mta->getParam('smtp_reserve'));
        $reserveconn->addValidator(new Zend_Validate_Int());
        $this->addElement($reserveconn);

        $rcptmaxpercon = new  Zend_Form_Element_Text('smtp_accept_max_per_connection', array(
                'label'    => $t->_('Maximum messages per connection')." :",
                'required' => false,
                'size' => 6,
                'class' => 'max_conn_field',
                'filters'    => array('Alnum', 'StringTrim')));
        $rcptmaxpercon->setValue($this->_mta->getParam('smtp_accept_max_per_connection'));
        $rcptmaxpercon->addValidator(new Zend_Validate_Int());
        $this->addElement($rcptmaxpercon);
        
        $loadreserveconn = new  Zend_Form_Element_Text('smtp_load_reserve', array(
            'label'    => $t->_('Refuse untrusted connections if load is higher than')." :",
            'required' => false,
            'size' => 6,
            'filters'    => array('Alnum', 'StringTrim')));
        $loadreserveconn->setValue($this->_mta->getParam('smtp_load_reserve'));
        $loadreserveconn->addValidator(new Zend_Validate_Int());
        $this->addElement($loadreserveconn);
	    
	    $maxmsgsize = new  Zend_Form_Element_Text('global_msg_max_size', array(
	        'label'    => $t->_('Global maximum message size')." :",
                'title' => $t->_("Messages bigger than this will be refused"),
		    'required' => false,
		    'size' => 10,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $value = $this->_mta->getParam('global_msg_max_size');
	    if (preg_match('/^(\d+)([MK])/', $value, $matches)) {
	    	$value = $matches[1];
	    	if ($matches[2] == 'M') {
	    		$value = $value * 1024;
	    	}
	    }
	    $maxmsgsize->setValue($value);
        $maxmsgsize->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxmsgsize);
	    
	    $adderrorsreplyto = new  Zend_Form_Element_Text('errors_reply_to', array(
	                'label'    => $t->_('Add Reply-To address to error messages')." :",
	                'required' => false,
	                'size' => 40,
	                'filters'    => array()));
	    $adderrorsreplyto->setValue($this->_mta->getParam('errors_reply_to'));
	    require_once('Validate/EmailHeader.php');
        $adderrorsreplyto->addValidator(new Validate_EmailHeader(Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($adderrorsreplyto);
	    
	    $retryrule = new Default_Model_RetryRule($this->_mta->getParam('retry_rule'));
	    
	    $retrydelayvalue = new  Zend_Form_Element_Text('retry_delay_value', array(
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $retrydelayvalue->setValue($retryrule->getDelayValue());
        $retrydelayvalue->addValidator(new Zend_Validate_Int());
	    $this->addElement($retrydelayvalue);
	    
	    $retrydelayunit = new Zend_Form_Element_Select('retry_delay_unit', array(
            'required'   => true,
            'filters'    => array('StringTrim')));
        
        foreach ($retryrule->getUnits() as $unit => $unitname) {
        	$retrydelayunit->addMultiOption($unit, $t->_($unitname));
        }
        $retrydelayunit->setValue($retryrule->getDelayUnit());
        $this->addElement($retrydelayunit);
        
        
	    $retrycutoffvalue = new  Zend_Form_Element_Text('retry_cutoff_value', array(
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $retrycutoffvalue->setValue($retryrule->getCutoffValue());
        $retrycutoffvalue->addValidator(new Zend_Validate_Int());
	    $this->addElement($retrycutoffvalue);
	    
	    $retrycutoffunit = new Zend_Form_Element_Select('retry_cutoff_unit', array(
            'required'   => true,
            'filters'    => array('StringTrim')));
        
        foreach ($retryrule->getUnits() as $unit => $unitname) {
        	$retrycutoffunit->addMultiOption($unit, $t->_($unitname));
        }
        $retrycutoffunit->setValue($retryrule->getCutoffUnit());
        $this->addElement($retrycutoffunit);
	    
        foreach (array('rate', 'trusted_rate') as $rtype ) {
          $ratelimit = new Default_Model_RatelimitRule($this->_mta->getParam($rtype.'limit_rule'));
          $ratecount =  new  Zend_Form_Element_Text($rtype.'_count', array(
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
          $ratecount->setValue($ratelimit->getCountValue());
          $ratecount->addValidator(new Zend_Validate_Int());
	      $this->addElement($ratecount);
	    
	      $rateintervalvalue = new  Zend_Form_Element_Text($rtype.'_interval_value', array(
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	      $rateintervalvalue->setValue($ratelimit->getIntervalValue());
          $rateintervalvalue->addValidator(new Zend_Validate_Int());
	      $this->addElement($rateintervalvalue);
	    
	      $rateintervalunit = new Zend_Form_Element_Select($rtype.'_interval_unit', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
        
          foreach ($ratelimit->getUnits() as $unit => $unitname) {
        	$rateintervalunit->addMultiOption($unit, $t->_($unitname));
          }
          $rateintervalunit->setValue($ratelimit->getIntervalUnit());
          $this->addElement($rateintervalunit);
        
	      $ratedelay = new  Zend_Form_Element_Text($rtype.'limit_delay', array(
	        'label' => $t->_('Delay imposed to fast senders').' : ',
                'title' => $t->_("When a sending host is too fast, you can impose him a delay, voiding some spam techniques"),
		    'required' => false,
		    'size' => 3,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	      $ratedelay->setValue($this->_mta->getParam($rtype.'limit_delay'));
          $ratedelay->addValidator(new Zend_Validate_Int());
	      $this->addElement($ratedelay);
	    
	      $rateenable = new Zend_Form_Element_Checkbox($rtype.'limit_enable', array(
	        'label'   => $t->_($rtype.'limit_enable'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	      if ($this->_mta->getParam($rtype.'limit_enable')) {
            $rateenable->setChecked(true);
	      }
	      $this->addElement($rateenable);
        }
        
        require_once('Validate/HostList.php');      
        require_once('Validate/IpList.php');
        $no_ratelimit_hosts = new Zend_Form_Element_Textarea('no_ratelimit_hosts', array(
              'label'    =>  $t->_('No rate limiting for these hosts')." :",
              'title' => $t->_("Whitelist for the rate limit"),
              'required'   => false,
              'rows' => 5,
              'cols' => 30,
              'filters'    => array('StringToLower', 'StringTrim')));
        $no_ratelimit_hosts->addValidator(new Validate_IpList());
        $no_ratelimit_hosts->setValue($this->_mta->getParam('no_ratelimit_hosts'));
        $this->addElement($no_ratelimit_hosts);
        
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $mta) {
		$mta->setparam('smtp_receive_timeout', $request->getParam('smtp_receive_timeout').'s');
		$mta->setparam('max_rcpt', $request->getParam('max_rcpt'));
		$mta->setparam('smtp_accept_max', $request->getParam('smtp_accept_max'));
		$mta->setparam('smtp_accept_max_per_host', $request->getParam('smtp_accept_max_per_host'));
		$mta->setparam('smtp_accept_max_per_trusted_host', $request->getParam('smtp_accept_max_per_trusted_host'));
                $mta->setparam('smtp_accept_max_per_connection', $request->getParam('smtp_accept_max_per_connection'));
        $mta->setparam('smtp_reserve', $request->getParam('smtp_reserve'));
        $mta->setparam('smtp_load_reserve', $request->getParam('smtp_load_reserve'));
		$mta->setparam('global_msg_max_size', $request->getParam('global_msg_max_size')."K");
		$mta->setParam('errors_reply_to' , $request->getParam('errors_reply_to'));
		
		$retryrule = new Default_Model_RetryRule($this->_mta->getParam('retry_rule'));
		$retryrule->setDelay($request->getParam('retry_delay_value'), $request->getParam('retry_delay_unit'));
		$retryrule->setCutoff($request->getParam('retry_cutoff_value'), $request->getParam('retry_cutoff_unit'));
		$mta->setParam('retry_rule', $retryrule->getRetryRule());
		
		foreach (array('rate', 'trusted_rate') as $rtype ) {
			
    		$mta->setParam($rtype.'limit_enable', $request->getParam($rtype.'limit_enable'));
	    	if ($request->getParam($rtype.'limit_enable')) {
                $ratelimit = new Default_Model_RatelimitRule($this->_mta->getParam($rtype.'limit_rule'));
		        $ratelimit->setCount($request->getParam($rtype.'_count'));
		        $ratelimit->setInterval($request->getParam($rtype.'_interval_value'), $request->getParam($rtype.'_interval_unit'));
		        $mta->setParam($rtype.'limit_rule', $ratelimit->getRatelimitRule());
	            $mta->setParam($rtype.'limit_delay', $request->getParam($rtype.'limit_delay'));
		    }
		}
		$mta->setParam('no_ratelimit_hosts' , $request->getParam('no_ratelimit_hosts'));
	}

}
