<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * SMTP checks form
 */

class Default_Form_SmtpChecks extends ZendX_JQuery_Form
{
	protected $_mta;
	
	public $_rbl_checks = array();
	
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
	           
		$this->setAttrib('id', 'smtpchecks_form');
	    
	    $senderverify = new Zend_Form_Element_Checkbox('verify_sender', array(
	        'label'   => $t->_('Verify sender domain'). " :",
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_mta->getParam('verify_sender')) {
            $senderverify->setChecked(true);
	    }
	    $this->addElement($senderverify);
	    
	    $forcesync = new Zend_Form_Element_Checkbox('smtp_enforce_sync', array(
	        'label'   => $t->_('Force SMTP protocol synchronization'). " :",
		'title' => $t->_('Rejects any email sent by the remote MTA without waiting the 220 SMTP response first'),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_mta->getParam('smtp_enforce_sync') && $this->_mta->getParam('smtp_enforce_sync') != 'false') {
            $forcesync->setChecked(true);
	    }
	    $this->addElement($forcesync);
	    
	    $allowmxtoip = new Zend_Form_Element_Checkbox('allow_mx_to_ip', array(
            'label'   => $t->_('Allow hosts with MX that point to IP addresses'). " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('allow_mx_to_ip') && $this->_mta->getParam('allow_mx_to_ip') != 'false') {
            $allowmxtoip->setChecked(true);
        }
        $this->addElement($allowmxtoip);
        
        $reject_bad_spf = new Zend_Form_Element_Checkbox('reject_bad_spf', array(
            'label'   => $t->_('Reject wrong SPF (fail result)'). " :",
            'title' => $t->_("Rejects mails not satisfying the domain's SPF"),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('reject_bad_spf') && $this->_mta->getParam('reject_bad_spf') != 'false') {
            $reject_bad_spf->setChecked(true);
        }
        $this->addElement($reject_bad_spf);
        
        $reject_bad_rdns = new Zend_Form_Element_Checkbox('reject_bad_rdns', array(
                    'label'   => $t->_('Reject invalid reverse DNS'). " :",
                    'uncheckedValue' => "0",
                    'checkedValue' => "1"
        ));
        if ($this->_mta->getParam('reject_bad_rdns') && $this->_mta->getParam('reject_bad_rdns') != 'false') {
        	$reject_bad_rdns->setChecked(true);
        }
        $this->addElement($reject_bad_rdns);

        $dmarc_follow_reject_policy = new Zend_Form_Element_Checkbox('dmarc_follow_reject_policy', array(
            'label'   => $t->_('Honor DMARC reject policy'). " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('dmarc_follow_reject_policy') && $this->_mta->getParam('dmarc_follow_reject_policy') != 'false') {
            $dmarc_follow_reject_policy->setChecked(true);
        }
        $this->addElement($dmarc_follow_reject_policy);

        $dmarc_enable_reports = new Zend_Form_Element_Checkbox('dmarc_enable_reports', array(
            'label'   => $t->_('Enable reporting to DMARC domains'). " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('dmarc_enable_reports') && $this->_mta->getParam('dmarc_enable_reports') != 'false') {
            $dmarc_enable_reports->setChecked(true);
        }
        $this->addElement($dmarc_enable_reports);

        
	    $callouttimeout = new  Zend_Form_Element_Text('callout_timeout', array(
	        'label'    => $t->_('Recipient verification timeout')." :",
		    'required' => false,
		    'size' => 2,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $callouttimeout->setValue($this->_mta->getParam('callout_timeout'));
        $callouttimeout->addValidator(new Zend_Validate_Int());
	    $this->addElement($callouttimeout);
	    
	    $rbltimeout = new  Zend_Form_Element_Text('rbls_timeout', array(
	        'label'    => $t->_('RBL checks timeout')." :",
		    'required' => false,
		    'size' => 2,
	        'class' => 'fieldrighted',
		    'filters'    => array('Alnum', 'StringTrim')));
	    $rbltimeout->setValue($this->_mta->getParam('rbls_timeout'));
        $rbltimeout->addValidator(new Zend_Validate_Int());
	    $this->addElement($rbltimeout);
	    
	    require_once('Validate/SMTPHostList.php');
		$rblignore = new Zend_Form_Element_Textarea('rbls_ignore_hosts', array(
		      'label'    =>  $t->_('Don\'t check these hosts')." :",
		      'title'  => $t->_('Bypass RBLs results for these IPs'),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $rblignore->addValidator(new Validate_SMTPHostList());
		$rblignore->setValue($this->_mta->getParam('rbls_ignore_hosts'));
		$this->addElement($rblignore);
	    


	    require_once('Validate/SMTPHostList.php');
		$spf_dmarc_ignore = new Zend_Form_Element_Textarea('spf_dmarc_ignore_hosts', array(
		      'label'    =>  $t->_('Don\'t check these hosts for SPF or DMARC')." :",
		      'title'  => $t->_('Bypass RBLs results for these IPs for SPF and DMARC'),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $spf_dmarc_ignore->addValidator(new Validate_SMTPHostList());
		$spf_dmarc_ignore->setValue($this->_mta->getParam('spf_dmarc_ignore_hosts'));
		$this->addElement($spf_dmarc_ignore);




		$rbllist = new Default_Model_DnsLists();
		$rbllist->load();
		foreach ($rbllist->getRBLs('IPRBL') as $rbl) {
			$userbl = new Zend_Form_Element_Checkbox('use_rbl_'.$rbl['name'], array(
			         'label' => $rbl['dnsname'],
                     'uncheckedValue' => "0",
	                 'checkedValue' => "1"
	              ));
	        if ($this->_mta->useRBL($rbl['name'])) {
               $userbl->setChecked(true);
	        }
	        $this->addElement($userbl);
	        $this->_rbl_checks[] = $userbl;
		}
		
		$outgoingvirusscan = new Zend_Form_Element_Checkbox('outgoing_virus_scan', array(
            'label'   => $t->_('Scan relayed (outgoing) messages for viruses'). " :",
            'title' => $t->_("Enable / disable the virus check for outgoing messages"),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('outgoing_virus_scan')) {
            $outgoingvirusscan->setChecked(true);
        }
        $this->addElement($outgoingvirusscan);
        
        $maskrelayedip = new Zend_Form_Element_Checkbox('mask_relayed_ip', array(
            'label'   => $t->_('Mask IP address of relayed host on port 587'). " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_mta->getParam('mask_relayed_ip')) {
            $maskrelayedip->setChecked(true);
        }
        $this->addElement($maskrelayedip);
        
        $masquerade_outgoing_helo = new Zend_Form_Element_Checkbox('masquerade_outgoing_helo', array(
                    'label'   => $t->_('Masquerade relayed HELO with sender domain'). " :",
                    'uncheckedValue' => "0",
                    'checkedValue' => "1"
        ));
        if ($this->_mta->getParam('masquerade_outgoing_helo')) {
        	$masquerade_outgoing_helo->setChecked(true);
        }
        $this->addElement($masquerade_outgoing_helo);
        
		
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $mta) {
		$mta->setparam('verify_sender', $request->getParam('verify_sender'));
        $mta->setparam('outgoing_virus_scan', $request->getParam('outgoing_virus_scan'));
        $mta->setparam('mask_relayed_ip', $request->getParam('mask_relayed_ip'));
        $mta->setparam('masquerade_outgoing_helo', $request->getParam('masquerade_outgoing_helo'));
        $mta->setparam('smtp_enforce_sync', 'false');
        if ($request->getParam('smtp_enforce_sync')) {
		  $mta->setparam('smtp_enforce_sync', 'true');
        }
        $mta->setparam('allow_mx_to_ip', 'false');
        if ($request->getParam('allow_mx_to_ip')) {
          $mta->setparam('allow_mx_to_ip', 'true');
        }

        $mta->setparam('reject_bad_spf', $request->getParam('reject_bad_spf'));
        $mta->setparam('reject_bad_rdns', $request->getParam('reject_bad_rdns'));
        $mta->setparam('dmarc_follow_reject_policy', $request->getParam('dmarc_follow_reject_policy'));
        $mta->setparam('dmarc_enable_reports', $request->getParam('dmarc_enable_reports'));
		$mta->setparam('rbls_timeout', $request->getParam('rbls_timeout'));
		$mta->setparam('callout_timeout', $request->getParam('callout_timeout'));	
		$mta->setparam('rbls_ignore_hosts', $request->getParam('rbls_ignore_hosts'));
		$mta->setparam('spf_dmarc_ignore_hosts', $request->getParam('spf_dmarc_ignore_hosts'));
		
		$rbllist = new Default_Model_DnsLists();
		$rbllist->load();
		$rblstr = '';
		foreach ($rbllist->getRBLs('IPRBL') as $rbl) {
			$checkname = 'use_rbl_'.$rbl['name'];
			if ($request->getParam($checkname)) {
				$rblstr .= $rbl['name']." ";
			}
		}
		$rblstr = preg_replace('/^\s*/', '', $rblstr);
		$mta->setparam('rbls', $rblstr);
	}

}
