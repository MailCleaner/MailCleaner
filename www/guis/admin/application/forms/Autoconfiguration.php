<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Mentor Reka
 * @copyright 2017, Mentor Reka
 * 
 * Auto-configuration settings form
 */

class Default_Form_Autoconfiguration extends ZendX_JQuery_Form
{
	private $MC_AUTOCONF_TAG_FILE="/spool/mailcleaner/mc-autoconf";
	
	protected $_autoconfmanager;
	
	public function __construct($autoconf) {
		$this->_autoconfmanager = $autoconf;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
	    	$view=$layout->getView();
    	
		$this->setMethod('post'); 
		$this->setAttrib('id', 'autoconfiguration_form');
        
		require_once('MailCleaner/Config.php');
	        $config = new MailCleaner_Config();
		$autoconf_enabled = file_exists($config->getOption('VARDIR').$this->MC_AUTOCONF_TAG_FILE);

        	$autoconf = new  Zend_Form_Element_Checkbox('autoconfiguration', array(
            		'label' => "Enable auto-configuration :",
		    	'required' => false));
	   	$autoconf->setValue($autoconf_enabled);
		$this->addElement($autoconf);
	   
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}

}
