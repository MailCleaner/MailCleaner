<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Spamc form
 */

class Default_Form_AntiSpam_Spamc extends Default_Form_AntiSpam_Default
{
	protected $_viewscript = 'forms/antispam/SpamcForm.phtml';
	public $_rbl_checks = array();
	public $_ip_rbls = array();
	public $_uri_rbls = array();
	public $_rbls_class = '';
	
	public function getViewScriptFile() {
		return $this->_viewscript;
	}
	
	public function __construct($module) {
		parent::__construct($module);
	}
	
	public function init() {
		parent::init();
		
		$as = new Default_Model_AntispamConfig();
		$as->find(1);
		
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
		
    	$rbllist = new Default_Model_DnsLists();
		$rbllist->load();
        
		foreach ($rbllist->getRBLs('IPRBL DNSRBL IPRWL URIRBL') as $rbl) {
			$userbl = new Zend_Form_Element_Checkbox('use_rbl_'.$rbl['name'], array(
			         'label' => $rbl['dnsname'],
                     'uncheckedValue' => "0",
	                 'checkedValue' => "1"
	              ));
	        if ($as->useRBL($rbl['name'])) {
               $userbl->setChecked(true);
	        }
	        $this->addElement($userbl);
	        $this->_rbl_checks[] = $userbl;
	        if ($rbl['type'] == 'IPRBL' || $rbl['type'] == 'DNSRBL' || $rbl['type'] == 'IPRWL') {    	
                    $this->_ip_rbls[] = $userbl;
	        }       
	        if ($rbl['type'] == 'URIRBL') {        	
 	            $this->_uri_rbls[] = $userbl;
	        }
		}
		
		$localchecks = array(
		  'use_bayes' => 'Use statistical filter',
		  'bayes_autolearn' => 'enable auto learning',
		  'use_fuzzyocr' => 'Enable text recognition in images',
		  'use_imageinfo' => 'Enable image format/size detection',
		  'use_pdfinfo' => 'Enable PDF format detection',
		  'use_botnet' => 'Enable botnet detection',
                  'dmarc_follow_quarantine_policy' => 'Honor DMARC quarantine policy',
 		);
		
		$titles = array('Enable SpamAssassin Bayesian',
				'',
				'This plugin checks for specific keywords in image/gif, image/jpeg or image/png attachments, using gocr (an optical character recognition program). ', 
				'Checks for specific  properties like format/size for image detection',
				'This plugin helps detected spam using attached PDF files',
				'Botnet looks for possible botnet sources of email by checking various DNS values',
				'Tag or quarantine mail following the DMARC record');
		$i = 0;
 		foreach ($localchecks as $checkname => $checklabel) {
		   $el = new Zend_Form_Element_Checkbox($checkname, array(
	              'label'   => $t->_($checklabel). " :",
			'title' => $t->_($titles[$i]),
                  'uncheckedValue' => "0",
	              'checkedValue' => "1"
	            ));
	       $el->setValue($as->getParam($checkname));
	       $this->addElement($el);
		$i++;
	    }
	    /*
	    $netchecks = array(
	    	'use_rbls' => array('label' => 'Enable RBLs controls', 'timeout' => 'rbls_timeout', 'title' => 'Use DNS RBLs for Spam detection'),
	    	'use_dcc' => array('label' => 'Enable DCC control', 'timeout' => 'dcc_timeout', 'title' => 'The idea of DCC is that if mail recipients could compare the mail they receive, they could recognize unsolicited bulk mail'),
	    	'use_razor' => array('label' => 'Enable Razor control', 'timeout' => 'razor_timeout', 'title' => 'Vipul's Razor is a distributed, collaborative, spam detection and filtering network'),
	        'use_pyzor' => array('label' => 'Enable Pyzor control', 'timeout' => 'pyzor_timeout', 'title' => 'Exactly the same thing than razor. Chose/Select only of one of them'),
	    	'use_spf' => array('label' => 'Enable SPF control', 'timeout' => 'spf_timeout', 'title' => 'This plugin checks a message against Sender Policy Framework records published by the domain owners in DNS to fight email address forgery and make it easier to identify spams'),
	    	'use_dkim' => array('label' => 'Enable DKIM control', 'timeout' => 'dkim_timeout', 'title' => 'DomainKeys Identified Mail (DKIM) is a method by which emails are signed by the organisation responsible for the senders domain and are placed in the DKIM-Signature: header field ')
	    );*/
		$netchecks = array(
                'use_rbls' => array('label' => 'Enable RBLs controls', 'timeout' => 'rbls_timeout', 'title' => "Use DNS RBLs for Spam detection"),
                'use_dcc' => array('label' => 'Enable DCC control', 'timeout' => 'dcc_timeout', 'title' => "The idea of DCC is that if mail recipients could compare the mail they receive, they could recognize unsolicited bulk mail"),
                'use_razor' => array('label' => 'Enable Razor control', 'timeout' => 'razor_timeout', 'title' => "Vipul's Razor is a distributed, collaborative, spam detection and filtering network"),
                'use_pyzor' => array('label' => 'Enable Pyzor control', 'timeout' => 'pyzor_timeout', 'title' => "Exactly the same thing than razor. Chose/Select only one of them"),
                'use_spf' => array('label' => 'Enable SPF control', 'timeout' => 'spf_timeout', 'title' => "This plugin checks a message against Sender Policy Framework records published by the domain owners in DNS to fight email address forgery and make it easier to identify spams"),
                'use_dkim' => array('label' => 'Enable DKIM control', 'timeout' => 'dkim_timeout', 'title' => "DomainKeys Identified Mail (DKIM) is a method by which emails are signed by the organisation responsible for the senders domain and are placed in the DKIM-Signature: header field"),
            );
	    foreach ($netchecks as $checkname => $check) {
	 	    $el = new Zend_Form_Element_Checkbox($checkname, array(
	                 'label'   => $t->_($check{'label'}). " :",
			 'title' => $t->_($check{'title'}),
                         'uncheckedValue' => "0",
	                 'checkedValue' => "1"
	              ));
	        $el->setValue($as->getParam($checkname));
	        $this->addElement($el);
	        $el_timeout = new  Zend_Form_Element_Text($check{'timeout'}, array(
	                 'label'    => $t->_('timeout')." :",
			 'title' => $t->_('Timeout: Maximum time to wait for a response'),
		         'required' => false,
		         'size' => 4,
                         'class' => 'fieldrighted',
		         'filters'    => array('Alnum', 'StringTrim')));
	        $el_timeout->setValue($as->getParam($check{'timeout'}));
            $el_timeout->addValidator(new Zend_Validate_Int());
            if (!$as->getParam($checkname)) {
            	$el_timeout->setAttrib('class', 'fieldrighted disabled');
            }
	        $this->addElement($el_timeout);
	    }
	    
	    if (! $as->getParam('use_rbls')) {
	    	$this->_rbls_class = 'hidden';
	    }
	}
	
	public function setParams($request, $module) {
		parent::setParams($request, $module);
		
		$as = new Default_Model_AntispamConfig();
		$as->find(1);
		
		$rbllist = new Default_Model_DnsLists();
		$rbllist->load();
		$rblstr = '';
		foreach ($rbllist->getRBLs('IPRBL DNSRBL IPRWL URIRBL') as $rbl) {
			$checkname = 'use_rbl_'.$rbl['name'];
			if ($request->getParam($checkname)) {
				$rblstr .= $rbl['name']." ";
			}
		}
		$rblstr = preg_replace('/^\s*/', '', $rblstr);
		if ($request->getParam('use_rbls')) {
           $as->setParam('sa_rbls', $rblstr);
		}
		
		foreach (array('use_bayes', 'bayes_autolearn', 'use_fuzzyocr', 'use_imageinfo', 'use_pdfinfo', 'use_botnet', 'dmarc_follow_quarantine_policy') as $p) {
			$as->setParam($p, $request->getParam($p));
		}
		
	    foreach (array(
	        'use_rbls' => 'rbls_timeout',
	    	'use_dcc' => 'dcc_timeout',
	    	'use_razor' => 'razor_timeout',
	    	'use_pyzor' => 'pyzor_timeout',
	    	'use_spf' => 'spf_timeout',
	    	'use_dkim' => 'dkim_timeout',
	    ) as $p => $t) {
			$as->setParam($p, $request->getParam($p));
			if ($request->getParam($p)) {
                 $as->setParam($t, $request->getParam($t));
			}
		}
		$as->save();
		
		if ($as->getParam('use_rbls')) {
			$this->_rbls_class = '';
		} else {
			$this->_rbls_class = 'hidden';
		}
		foreach (array(
		    'use_rbls' => 'rbls_timeout',
	    	'use_dcc' => 'dcc_timeout',
	    	'use_razor' => 'razor_timeout',
	    	'use_pyzor' => 'pyzor_timeout',
	    	'use_spf' => 'spf_timeout',
	    	'use_dkim' => 'dkim_timeout',
		) as $p => $t) {
			$this->getElement($t)->setValue($as->getParam($t));
			if (!$as->getParam($p)) {
            	$this->getElement($t)->setAttrib('class', 'fieldrighted disabled');
			} else {
            	$this->getElement($t)->setAttrib('class', 'fieldrighted');
			}
		}
	}
	
}
