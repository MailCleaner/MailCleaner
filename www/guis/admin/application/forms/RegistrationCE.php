<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka
 * @copyright (C) 2017 Mentor Reka <reka.mentor@gmail.com>
 * Registration CE form
 */

class Default_Form_RegistrationCE extends ZendX_JQuery_Form
{
	
	protected $_registrationmgr;
	
	public function __construct($mgr) {
		$this->_registrationmgr = $mgr;
		parent::__construct();
	}
	
	
	public function init()
	{

           $t = Zend_Registry::get('translate');
	   $layout = Zend_Layout::getMvcInstance();
    	   $view=$layout->getView();

	   $this->setMethod('post');
           $this->setAttrib('id', 'registration_form_ce');

            $sysconf = MailCleaner_Config::getInstance();
	    require_once ('helpers/DM_Custom.php');
	    $db = DM_Custom :: getInstance('127.0.0.1', '3306', 'mailcleaner', $sysconf->getOption('MYMAILCLEANERPWD'), 'mc_community');

	    $query = "SELECT * from registration LIMIT 1";
	    $res = $db->getHash($query);


            $first_name = new  Zend_Form_Element_Text('first_name', array(
	            'label' => $t->_('First Name'). " :",
                    'required' => true));
            $first_name->setValue($res['first_name']);
            $first_name->addValidator(new Zend_Validate_Alnum());
            $this->addElement($first_name);

	    $last_name = new  Zend_Form_Element_Text('last_name', array(
                    'label' => $t->_('Last Name'). " :",
                    'required' => true));
            $last_name->setValue($res['last_name']);
            $last_name->addValidator(new Zend_Validate_Alnum());
            $this->addElement($last_name);


	    $email = new  Zend_Form_Element_Text('email', array(
                'label'    => $t->_('Email address')." :",
                'required' => false,
                'filters'    => array('StringToLower', 'StringTrim')));
            $email->setValue($res['email']);
            $email->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
            $this->addElement($email);

	    $company = new  Zend_Form_Element_Text('company_name', array(
                    'label' => $t->_('Company'). " :",
                    'required' => false));
            $company->setValue($res['company']);
            $this->addElement($company);

	    $address = new  Zend_Form_Element_Text('address', array(
                    'label' => $t->_('Address'). " :",
                    'required' => false));
            $address->setValue($res['address']);
            $this->addElement($address);

            $postal_code = new  Zend_Form_Element_Text('postal_code', array(
                    'label' => $t->_('Postal code'). " :",
                    'required' => false));
            $postal_code->setValue($res['postal_code']);
            $postal_code->addValidator(new Zend_Validate_Alnum());
            $this->addElement($postal_code);

            $city = new  Zend_Form_Element_Text('city', array(
                    'label' => $t->_('City'). " :",
                    'required' => false));
            $city->setValue($res['city']);
            $this->addElement($city);

            $country = new  Zend_Form_Element_Text('country', array(
                    'label' => $t->_('Country'). " :",
                    'required' => false));
            $country->setValue($res['country']);
            $country->addValidator(new Zend_Validate_Alnum());
            $this->addElement($country);

            $accept_newsletters = new  Zend_Form_Element_Checkbox('accept_newsletters', array(
                    'label' => $t->_('I accept to receive the general newsletters'). " :",
                    'required' => true));
            $accept_newsletters->setValue($res['accept_newsletters'] || 1);
            $this->addElement($accept_newsletters);

            $accept_releases = new  Zend_Form_Element_Checkbox('accept_releases', array(
                    'label' => $t->_('I accept to receive information about releases'). " :",
                    'required' => true));
            $accept_releases->setValue($res['accept_releases'] || 1);
            $this->addElement($accept_releases);

            $accept_send_statistics = new  Zend_Form_Element_Checkbox('accept_send_statistics', array(
                    'label' => $t->_('I accept to send anonymous statistics'). " :",
                    'required' => true));
            $accept_send_statistics->setValue($res['accept_send_statistics'] || 1);
            $this->addElement($accept_send_statistics);

            $submitce = new Zend_Form_Element_Submit('register_ce', array(
		     'label'    => $t->_('Register')));
	    $this->addElement($submitce);
	}

}
