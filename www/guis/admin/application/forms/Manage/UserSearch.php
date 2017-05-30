<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * User/Email search form
 */

class Default_Form_Manage_UserSearch extends Zend_Form
{
    protected $_params;
    
    public function __construct($params) {
        $this->_params = $params;
		parent::__construct();
    }
    
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');
			
		$searchtypeField = new  Zend_Form_Element_Select('type', array(
	        'label'    => $t->_('Search by')." :",
		    'required' => false));
		$searchtypeField->addMultiOption('user', $t->_('user'));
		$searchtypeField->addMultiOption('email', $t->_('email address'));
	    $searchtypeField->setValue($this->_params['type']);
	    $this->addElement($searchtypeField);

	    $username = new  Zend_Form_Element_Text('search', array(
	        'label'    => $t->_('Username')." :",
		    'required' => false));
	    $username->setValue($this->_params['search']);
	    $this->addElement($username);
	    
	    $domainField = new  Zend_Form_Element_Select('domain', array(
		    'required' => false));
	    $domain = new Default_Model_Domain();
	    $domains = $domain->fetchAllName();
	    $domainField->addMultiOption('', $t->_('select...'));
	    foreach ($domains as $d) {
	        $domainField->addMultiOption($d->getParam('name'), $d->getParam('name'));
	    }
	    $domainField->setValue($this->_params['domain']);
	    $this->addElement($domainField);
	    
	    $submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Edit or add >>'),
	         'class' => 'useraddsubmit' ));
		$this->addElement($submit);
	}

}