<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP connection control form
 */

class Default_Form_SmtpConnectionControl extends ZendX_JQuery_Form
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
	           
		$this->setAttrib('id', 'connectioncontrol_form');
	    
		require_once('Validate/HostList.php');
		
		require_once('Validate/IpList.php');
		$allowconnect = new Zend_Form_Element_Textarea('smtp_conn_access', array(
		      'label'    =>  $t->_('Allow connection from hosts')." :",
                      'title' => $t->_("List of servers/IP/ranges which are allowed to send emails to this MailCleaner server"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $allowconnect->addValidator(new Validate_IpList());
		$allowconnect->setValue($this->_mta->getParam('smtp_conn_access'));
		$this->addElement($allowconnect);
		
		require_once('Validate/EmailList.php');
		$allowrelay = new Zend_Form_Element_Textarea('relay_from_hosts', array(
		      'label'    =>  $t->_('Allow external relaying for these hosts')." :",
                      'title' => $t->_("List of servers/IP/ranges which can use this MailCleaner as an external relay"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $allowrelay->addValidator(new Validate_IpList());
		$allowrelay->setValue($this->_mta->getParam('relay_from_hosts'));
		$this->addElement($allowrelay);
		
		$allow_relay_for_unknown_domains = new Zend_Form_Element_Checkbox('allow_relay_for_unknown_domains', array(
            'label'   => $t->_('Allow relaying from unknown domains'). " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('allow_relay_for_unknown_domains')) {
            $allow_relay_for_unknown_domains->setChecked(true);
        }
        $this->addElement($allow_relay_for_unknown_domains);
        
        require_once('Validate/DomainList.php');
		$relay_refused_to_domains = new Zend_Form_Element_Textarea('relay_refused_to_domains', array(
		      'label'    =>  $t->_('Reject relaying to these external domains')." :",
                      'title' => $t->_("List of servers/IP/ranges which are not allowed to connect to this MailCleaner as an external relay"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $relay_refused_to_domains->addValidator(new Validate_DomainList());
		$relay_refused_to_domains->setValue($this->_mta->getParam('relay_refused_to_domains'));
		$this->addElement($relay_refused_to_domains);
		
		$rejecthosts = new Zend_Form_Element_Textarea('host_reject', array(
		      'label'    =>  $t->_('Reject connection from these hosts')." :",
                      'title' => $t->_("Blacklist of IP sender addresses"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $rejecthosts->addValidator(new Validate_IpList());
		$rejecthosts->setValue($this->_mta->getParam('host_reject'));
		$this->addElement($rejecthosts);
		
		require_once('Validate/EmailList.php');
		$rejectsenders = new Zend_Form_Element_Textarea('sender_reject', array(
		      'label'    =>  $t->_('Reject these senders addresses')." :",
                      'title' => $t->_("Blacklist of sender mail adresses"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 50,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    #$rejectsenders->addValidator(new Validate_EmailList());
		$rejectsenders->setValue($this->_mta->getParam('sender_reject'));
		$this->addElement($rejectsenders);

		$rejectusers = new Zend_Form_Element_Textarea('user_reject', array(
		      'label'    =>  $t->_('Reject these authenticated users')." :",
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 50,
		      'filters'    => array('StringToLower', 'StringTrim')));
		$rejectusers->setValue($this->_mta->getParam('user_reject'));
		$this->addElement($rejectusers);

                require_once('Validate/EmailList.php');
                $rejectrecipient = new Zend_Form_Element_Textarea('recipient_reject', array(
                      'label'    =>  $t->_('Reject these recipient addresses')." :",
                      'title' => $t->_("Blacklist of recipient email addresses"),
                      'required'   => false,
                      'rows' => 5,
                      'cols' => 50,
                      'filters'    => array('StringToLower', 'StringTrim')));
                $rejectrecipient->addValidator(new Validate_EmailList());
                $rejectrecipient->setValue($this->_mta->getParam('recipient_reject'));
                $this->addElement($rejectrecipient);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

	public function setParams($request, $mta) {
		$mta->setparam('smtp_conn_access', $request->getParam('smtp_conn_access'));
		$mta->setparam('relay_from_hosts', $request->getParam('relay_from_hosts'));
        $mta->setparam('allow_relay_for_unknown_domains', $request->getParam('allow_relay_for_unknown_domains'));
		$mta->setparam('host_reject', $request->getParam('host_reject'));
		$mta->setparam('sender_reject', $request->getParam('sender_reject'));
                $mta->setparam('user_reject', $request->getParam('user_reject'));
                $mta->setparam('recipient_reject', $request->getParam('recipient_reject'));
		$mta->setparam('relay_refused_to_domains', $request->getParam('relay_refused_to_domains'));
		
	}
	
}
