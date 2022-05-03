<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * User authentification form
 */

class Default_Form_Manage_UserAuthentification extends Zend_Form
{
	protected $_user;
	protected $_domain;
	protected $_local;
	protected $_panelname = 'authentification';

	public function __construct($user, $domain)
	{
		$this->_user = $user;
		$this->_domain = $domain;
		$this->_local = $this->_user->getLocalUserObject();

		parent::__construct();
	}


	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'user_form');
		$panellist = new Zend_Form_Element_Select('userpanel', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
		## TODO: add specific validator
		$panellist->addValidator(new Zend_Validate_Alnum());

		foreach ($this->_user->getConfigPanels() as $panel => $panelname) {
			$panellist->addMultiOption($panel, $panelname);
		}
		$panellist->setValue($this->_panelname);
		$this->addElement($panellist);

		$panel = new Zend_Form_Element_Hidden('panel');
		$panel->setValue($this->_panelname);
		$this->addElement($panel);
		$name = new Zend_Form_Element_Hidden('username');
		$name->setValue($this->_user->getParam('username'));
		$this->addElement($name);
		$domain = new Zend_Form_Element_Hidden('domain');
		if ($this->_user->getParam('domain')) {
			$domain->setValue($this->_user->getParam('domain'));
		} else {
			$domain->setValue($this->_domain);
		}
		$this->addElement($domain);

		$realname = new  Zend_Form_Element_Text('realname', array(
            'label'   => $t->_('Real name')." :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
		$realname->setValue($this->_local->getParam('realname'));
		$this->addElement($realname);
	  
		if ($this->_user->getDomainObject()->isFetcherLocal()) {
			$address = new  Zend_Form_Element_Text('email', array(
            'label'   => $t->_('Email address')." :",
		    'required' => true,
		    'filters'    => array('StringToLower', 'StringTrim')));
			$address->setValue($this->_local->getParam('email'));
			$this->addElement($address);
		}
	  
		$password = new  Zend_Form_Element_Password('password', array(
	        'label'    => $t->_('Password')." :",
	        'renderPassword' => true,
		    'required' => false));
		if ($this->_local->getParam('password') != '') {
			$password->setValue('_keeppassword1_');
		}
		$this->addElement($password);
	  
		$confirm = new  Zend_Form_Element_Password('confirm', array(
	        'label'    => $t->_('Confirm')." :",
	        'renderPassword' => true,
		    'required' => false));
		if ($this->_local->getParam('password') != '') {
			$confirm->setValue('_keeppassword2_');
		}
		$this->addElement($confirm);

		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
	}

	public function setParams($request, $user) {
		$local = $user->getLocalUserObject();
		foreach (array('realname', 'email') as $pref) {
			if ($request->getParam($pref)) {
				$local->setParam($pref, $request->getParam($pref));
			}
		}

		if ($request->getParam('password') != '_keeppassword1_') {
			if ($request->getParam('password') != $request->getParam('confirm')) {
				throw new Exception('Password missmatch');
			} else {
				$local->setPassword($request->getParam('password'));
			}
		}
		$local->save(); 
        if (preg_match('/^\s*$/', $request->getParam('password'))) {
             # generate and send password
            require_once("Pear/Text/Password.php");
            $pass = Text_Password::create(12, 'pronounceable', 'numeric');
            $local->setPassword($pass);
            foreach ($user->getAddresses() as $add) {
                $config = MailCleaner_Config::getInstance();
                $cmd = $config->getOption('SRCDIR')."/bin/send_userpassword.pl '".$add."' '".$user->getParam('username')."' '".$pass."' 0";
                $res = `$cmd`;
            }
        }
        $local->save();
		return true;
	}

}
