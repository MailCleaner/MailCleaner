<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Antivirus global settings form
 */

class Default_Form_AntivirusGlobalSettings extends ZendX_JQuery_Form
{
	protected $_antivirus;
	
	public function __construct($av) {
		$this->_antivirus = $av;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'antivirusglobalsettings_form');
	     
	    $maxattach = new  Zend_Form_Element_Text('max_attachments_per_message', array(
            'label'   => $t->_('Maximum attachments per message')." :",
            'title' => $t->_("A message having more attachments than this is not analyzed. The message is sent to quarantine or tagged"),
		    'required' => false,
	        'size' => 5,
	        'class' => 'fieldrighted',
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $maxattach->setValue($this->_antivirus->getParam('max_attachments_per_message'));
        $maxattach->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxattach);	
	    
	    $maxattachsize = new  Zend_Form_Element_Text('max_attach_size', array(
            'label'   => $t->_('Maximum attachment size')." :",
            'title' => $t->_("If an attachment weights more than this, he will not be analyzed and the message is either sent to quarantine or tagged"),
		    'required' => false,
	        'size' => 10,
	        'class' => 'fieldrighted',
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $maxattachsize->setValue($this->_antivirus->getParam('max_attach_size'));
        $maxattachsize->addValidator(new Zend_Validate_Int());
	    $this->addElement($maxattachsize);	
	    
	    $maxattachsizeenable = new Zend_Form_Element_Checkbox('max_attach_size_enable', array(
	        'label'   => $t->_('no maximum size'),
                'title' => $t->_("MailScanner will check all attachments"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('max_attach_size') < 0 ) {
            $maxattachsizeenable->setChecked(true);
            $maxattachsize->setValue('');
            $maxattachsize->setAttrib('class', 'fieldrighted disabled');
	    }
	    $this->addElement($maxattachsizeenable);
	    
	    $max_archive_depth = new  Zend_Form_Element_Text('max_archive_depth', array(
            'label'   => $t->_('Content control maximum archive depth')." :",
            'title' => $t->_("Depth of \"archives in archives\" which will be analyzed"),
		    'required' => false,
	        'size' => 10,
	        'class' => 'fieldrighted',
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $max_archive_depth->setValue($this->_antivirus->getParam('max_archive_depth'));
        $max_archive_depth->addValidator(new Zend_Validate_Int());
	    $this->addElement($max_archive_depth);	
	    
	    $max_archive_depth_disable = new Zend_Form_Element_Checkbox('max_archive_depth_disable', array(
	        'label'   => $t->_('disable content controls in archives'),
                'title' => $t->_("If checked, MailCleaner wont check the items inside archives / If unchecked the files in the archives will be analyzed with the same rules as files not in archives"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('max_archive_depth') == 0) {
            $max_archive_depth_disable->setChecked(true);
            $max_archive_depth->setValue('');
            $max_archive_depth->setAttrib('class', 'fieldrighted disabled');
	    }
	    $this->addElement($max_archive_depth_disable);
	    
        
	    $expand_tnef = new Zend_Form_Element_Checkbox('expand_tnef', array(
	        'label'   => $t->_('Expand TNEF (winmail.dat) attachments')." :",
                'title' => $t->_("Extract files from the TNEF attachment (TNEF is an archive like format). This is required to perform antivirus checks on the content"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('expand_tnef') == 'yes') {
            $expand_tnef->setChecked(true);
	    }
	    $this->addElement($expand_tnef);
	    
	    $deliver_bad_tnef = new Zend_Form_Element_Checkbox('deliver_bad_tnef', array(
	        'label'   => $t->_('Still deliver bad TNEF attachments')." :",
                'title' => $t->_("Delivers the TNEF attachements even if they are seen as corrupted by MailCleaner"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('deliver_bad_tnef') == 'yes') {
            $deliver_bad_tnef->setChecked(true);
	    }
	    $this->addElement($deliver_bad_tnef);
	    
	    $usetnefcontent = new Zend_Form_Element_Select('usetnefcontent', array(
            'label'      => $t->_('Use decoded TNEF attachments')." :",
            'title' => $t->_("Choose action to perform with the TNEF attachment s scontent"),
            'required'   => false,
            'filters'    => array('StringTrim')));
        
	    $tnefactions = array(
	      'no' => 'do nothing but checking content',
	      'add' => 'add decoded content to message',
	      'replace' => 'replace encoded content with decoded content'
	    );
        foreach ($tnefactions as $lk => $lv) {
        	$usetnefcontent->addMultiOption($lk, $t->_($lv));
        }
        $usetnefcontent->setValue($this->_antivirus->getParam('usetnefcontent'));
        $this->addElement($usetnefcontent);
        
        if ($this->_antivirus->getParam('expand_tnef') != 'yes') {
            $deliver_bad_tnef->setAttrib('class', 'disabled');
            $usetnefcontent->setAttrib('class', 'disabled');
        }
	    
        $send_notices = new Zend_Form_Element_Checkbox('send_notices', array(
	        'label'   => $t->_('Sent notice to administrator')." :",
                'title' => $t->_("If one of the rule above is met, the administrator will be warned"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antivirus->getParam('send_notices') == 'yes') {
            $send_notices->setChecked(true);
	    }
	    $this->addElement($send_notices);
	    
	    $notices_to = new  Zend_Form_Element_Text('notices_to', array(
            'label'   => $t->_('Administrator address')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $notices_to->setValue($this->_antivirus->getParam('notices_to'));
        $notices_to->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL));
	    $this->addElement($notices_to);	
	    
	    if ($this->_antivirus->getParam('send_notices') != 'yes') {
            $notices_to->setAttrib('class', 'disabled');
	    }
	    
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $av) {
        $av->setParam('max_attachments_per_message', $request->getParam('max_attachments_per_message'));
        
        if ($request->getParam('max_attach_size_enable')) {
        	$av->setParam('max_attach_size', -1);
        } else {
        	if (!$request->getParam('max_attach_size')) {
                $this->getElement('max_attach_size')->setAttrib('class', 'fieldrighted');
        		throw new Exception('please provide a maximum attachment size');
        	}
        	$av->setParam('max_attach_size', $request->getParam('max_attach_size'));
        }
        
	    if ($request->getParam('max_archive_depth_disable')) {
        	$av->setParam('max_archive_depth', 0);
        } else {
        	$av->setParam('max_archive_depth', $request->getParam('max_archive_depth'));
        }
        
        if ($request->getParam('expand_tnef')) {
           $av->setParam('expand_tnef', 'yes');
           if ($request->getParam('deliver_bad_tnef')) {
               $av->setParam('deliver_bad_tnef', 'yes');
           } else {
               $av->setParam('deliver_bad_tnef', 'no');
           }
           $av->setParam('usetnefcontent', $request->getParam('usetnefcontent'));
        } else {
           $av->setParam('expand_tnef', 'no');
        }
        
        if ($request->getParam('send_notices')) {
        	$av->setParam('send_notices', 'yes');
        	$av->setParam('notices_to', $request->getParam('notices_to'));
        } else {
        	$av->setParam('send_notices', 'no');
        }
	    
        $av->save();
        
        if ($av->getParam('max_archive_depth') == 0) {
        	$this->getElement('max_archive_depth_disable')->setChecked(true);
            $this->getElement('max_archive_depth')->setValue('');
            $this->getElement('max_archive_depth')->setAttrib('class', 'fieldrighted disabled');
        } else {
            $this->getElement('max_archive_depth')->setAttrib('class', 'fieldrighted');
        }
        
	    if ($av->getParam('max_attach_size') < 0) {
        	$this->getElement('max_attach_size_enable')->setChecked(true);
            $this->getElement('max_attach_size')->setValue('');
            $this->getElement('max_attach_size')->setAttrib('class', 'fieldrighted disabled');
        } else {
            $this->getElement('max_attach_size')->setAttrib('class', 'fieldrighted');
        }
        
	   if ($av->getParam('expand_tnef') != 'yes') {
            $this->getElement('deliver_bad_tnef')->setAttrib('class', 'disabled');
            $this->getElement('usetnefcontent')->setAttrib('class', 'disabled');
        } else {
        	$this->getElement('deliver_bad_tnef')->setAttrib('class', '');
            $this->getElement('usetnefcontent')->setAttrib('class', '');
        }
        $this->getElement('usetnefcontent')->setValue($av->getParam('usetnefcontent'));
        if ($av->getParam('deliver_bad_tnef') == 'yes') {
        	$this->getElement('deliver_bad_tnef')->setChecked(true);
        }
        
	    if ($av->getParam('send_notices') != 'yes') {
            $this->getElement('notices_to')->setAttrib('class', 'disabled');
	    } else {
	        $this->getElement('notices_to')->setAttrib('class', '');
	    }
        $this->getElement('notices_to')->setValue($av->getParam('notices_to'));
        
	}
}
