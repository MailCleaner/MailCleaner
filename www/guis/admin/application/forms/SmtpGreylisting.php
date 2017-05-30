<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP greylisting form
 */

class Default_Form_SmtpGreylisting extends ZendX_JQuery_Form
{
	protected $_greylist;
	
	public function __construct($greylist) {
		$this->_greylist = $greylist;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'greylisting_form');
	    
		$retrymin = new  Zend_Form_Element_Text('retry_min', array(
		    'required' => false,
		    'size' => 6,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $retrymin->setValue($this->_greylist->getParam('retry_min'));
        $retrymin->addValidator(new Zend_Validate_Int());
	    $this->addElement($retrymin);
	    
		$retrymax = new  Zend_Form_Element_Text('retry_max', array(
		    'required' => false,
		    'size' => 6,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $retrymax->setValue($this->_greylist->getParam('retry_max'));
        $retrymax->addValidator(new Zend_Validate_Int());
	    $this->addElement($retrymax);
	    
	    $expiretime = new  Zend_Form_Element_Text('expire', array(
		    'required' => false,
	        'label' => $t->_('Records expiration')." :",
                'title' => $t->_("Cached item timelife"),
		    'size' => 6,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $expiretime->setValue($this->_greylist->getParam('expire'));
        $expiretime->addValidator(new Zend_Validate_Int());
	    $this->addElement($expiretime);
	    
	    require_once('Validate/DomainList.php');
	    $avoiddomains = new Zend_Form_Element_Textarea('avoid_domains', array(
		      'label'    =>  $t->_('Avoid greylisting for these domains')." :",
                      'title' => $t->_("Whitelist for the greylist (!)"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $avoiddomains->addValidator(new Validate_DomainList());
		$avoiddomains->setValue(preg_replace('/\s+/', "\n", $this->_greylist->getParam('avoid_domains')));
		$this->addElement($avoiddomains);
	    
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

    public function setParams($request, $mta) {
		$mta->setparam('retry_min', $request->getParam('retry_min'));
		$mta->setparam('retry_max', $request->getParam('retry_max'));
		$mta->setparam('expire', $request->getParam('expire'));
		$mta->setparam('avoid_domains', $request->getParam('avoid_domains'));
		
	}
}
