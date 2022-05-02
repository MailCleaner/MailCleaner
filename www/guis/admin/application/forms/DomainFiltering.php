<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Domain filtering settings form
 */

class Default_Form_DomainFiltering extends Zend_Form
{
	protected $_domain;
	protected $_panelname = 'filtering';

        public $_whitelist;
        public $_warnlist;
	public $_blacklist;
        public $_newslist;

        public $_whitelistenabled = 0;
        public $_warnlistenabled = 0;
	public $_blacklistenabled = 0;
	
	public function __construct($domain, $whitelist, $warnlist, $blacklist, $newslist)
	{
	    $this->_domain = $domain;
            $this->_whitelist = $whitelist;
            $this->_warnlist = $warnlist;
	    $this->_blacklist = $blacklist;
            $this->_newslist = $newslist;
	    parent::__construct();
	}
	
	
	public function init()
	{
		$this->setMethod('post');
			
		$t = Zend_Registry::get('translate');

		$this->setAttrib('id', 'domain_form');
	    $panellist = new Zend_Form_Element_Select('domainpanel', array(
            'required'   => false,
            'filters'    => array('StringTrim')));
	    ## TODO: add specific validator
	    $panellist->addValidator(new Zend_Validate_Alnum());
        
        foreach ($this->_domain->getConfigPanels() as $panel => $panelname) {
        	$panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);
        
        $panel = new Zend_Form_Element_Hidden('panel');
		$panel->setValue($this->_panelname);
		$this->addElement($panel);
		$name = new Zend_Form_Element_Hidden('name');
		$name->setValue($this->_domain->getParam('name'));
		$this->addElement($name);
		
		$useantispam = new Zend_Form_Element_Checkbox('spamwall', array(
	        'label'   => $t->_('Enable advanced antispam controls'). " :",
                'title' => $t->_("Enable/Disable antispam part of MailCleaner"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('spamwall')) {
            $useantispam->setChecked(true);
	    }
	    $this->addElement($useantispam);
	    
	    $usecontent = new Zend_Form_Element_Checkbox('contentwall', array(
	        'label'   => $t->_('Enable dangerous content controls'). " :",
                'title' => $t->_("Enable / disable antivirus part of MailCleaner"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('contentwall')) {
            $usecontent->setChecked(true);
	    }
	    $this->addElement($usecontent);
	    
	    $greylist = new Zend_Form_Element_Checkbox('greylist', array(
	        'label'   => $t->_('Enable greylisting'). " :",
                'title' => $t->_("Enable/Disable greylisting (http://www.greylisting.org/)"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getParam('greylist')) {
            $greylist->setChecked(true);
	    }
	    $this->addElement($greylist);
	    
	    $antispoof = new Zend_Form_Element_Checkbox('prevent_spoof', array(
            'label'   => $t->_('Enable antispoof'). " :",
            'title' => $t->_("Rejects messages from the domain you are configuring sent from an IP which is not authorized. If you need to add hosts to the list of allowed senders for your domain, please consider using SPF"), 
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_domain->getPref('prevent_spoof')) {
            $antispoof->setChecked(true);
        }
        $this->addElement($antispoof);

	$reject_capital_domain = new Zend_Form_Element_Checkbox('reject_capital_domain', array(
            'label'   => $t->_('Reject domains containing capital letters'). " :",
            'title' => $t->_("Forbidss the use of capital letters in the sender s domain name."),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
        if ($this->_domain->getPref('reject_capital_domain')) {
            $reject_capital_domain->setChecked(true);
        }
	$this->addElement($reject_capital_domain);

        $require_incoming_tls = new Zend_Form_Element_Checkbox('require_incoming_tls', array(
	        'label'   => $t->_('Reject unencrypted SMTP sessions to this domain'). " :",
                'title' => $t->_("Refuse all unencrypted connection with other MTA"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('require_incoming_tls')) {
            $require_incoming_tls->setChecked(true);
	    }
	    $this->addElement($require_incoming_tls);
	    
	    $enablewhitelist = new Zend_Form_Element_Checkbox('enable_whitelists', array(
	        'label'   => $t->_('Enable whitelists'). " :",
                'title' => $t->_("Enable the use of whitelist /!\ (http://www.mailcleaner.net/antispam/documentations/whitelist.html) must be enabled in Configuration > Anti-Spam first"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('enable_whitelists')) {
                $enablewhitelist->setChecked(true);
                $this->_whitelistenabled = 1;
	    }
	    $this->addElement($enablewhitelist);

	    $enableblacklist = new Zend_Form_Element_Checkbox('enable_blacklists', array(
                'label'   => $t->_('Enable blacklists'). " :",
                'title' => $t->_("Enable the blacklist feature"),
            'uncheckedValue' => "0",
                'checkedValue' => "1"
                      ));
            if ($this->_domain->getPref('enable_blacklists')) {
                $enableblacklist->setChecked(true);
                $this->_blacklistenabled = 1;
            }
            $this->addElement($enableblacklist);
	    
	    $enablewarnlist = new Zend_Form_Element_Checkbox('enable_warnlists', array(
	        'label'   => $t->_('Enable warnlists'). " :",
                'title' => $t->_("Enable / disable the use of warnlist. This list alerts the user when a mail comes from sender from the list."),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('enable_warnlists')) {
                $enablewarnlist->setChecked(true);
                $this->_warnlistenabled = 1;
	    }
	    $this->addElement($enablewarnlist);
	    
	    $warnwwhit = new Zend_Form_Element_Checkbox('notice_wwlists_hit', array(
	        'label'   => $t->_('Warn admin on white/warn list hit'). " :",
                'title' => $t->_("Alert the administrator for every hit in white / warnlist"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_domain->getPref('notice_wwlists_hit')) {
            $warnwwhit->setChecked(true);
	    }
	    $this->addElement($warnwwhit);

	    ### newsl
	    $allowNewsletters = new Zend_Form_Element_Checkbox('allow_newsletters', array(
	        'label'   =>  $t->_('Allow newsletters by default'). " :",
                'title' => $t->_("By default, the newsletters are delivered"),
	        'uncheckedValue' => "0",
	        'checkedValue' => "1"));
	    
	    if ($this->_domain->getPref('allow_newsletters')) {
	        $allowNewsletters->setChecked(true);
	    }
            
            $this->addElement($allowNewsletters);
            
            $this->_whitelistform = new Default_Form_ElementList($this->_whitelist, 'Default_Model_WWElement', 'whitelist_');
                $this->_whitelistform->init();
                $this->_whitelistform->setAddedValues(array('recipient' => '@'.$this->_domain->getParam('name'), 'type' => 'white'));
                $this->_whitelistform->addFields($this);

	    $this->_blacklistform = new Default_Form_ElementList($this->_blacklist, 'Default_Model_WWElement', 'blacklist_');
                $this->_blacklistform->init();
                $this->_blacklistform->setAddedValues(array('recipient' => '@'.$this->_domain->getParam('name'), 'type' => 'black'));
                $this->_blacklistform->addFields($this);

            $this->_warnlistform = new Default_Form_ElementList($this->_warnlist, 'Default_Model_WWElement', 'warnlist_');
                $this->_warnlistform->init();
                $this->_warnlistform->setAddedValues(array('recipient' => '@'.$this->_domain->getParam('name'), 'type' => 'warn'));
                $this->_warnlistform->addFields($this);
	    
            $this->_newslistform = new Default_Form_ElementList($this->_newslist, 'Default_Model_WWElement', 'newslist_');
                $this->_newslistform->init();
                $this->_newslistform->setAddedValues(array('recipient' => '@'.$this->_domain->getParam('name'), 'type' => 'wnews'));
                $this->_newslistform->addFields($this);

		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
	}

   public function setParams($request, $domain) {
        ### newsl
    	foreach (array('spamwall', 'contentwall', 'enable_whitelists', 'enable_warnlists', 'enable_blacklists', 'notice_wwlists_hit' , 'allow_newsletters') as $p) {
    	    $domain->setPref($p, $request->getParam($p));
    	}

        $this->_whitelistform->manageRequest($request);
        $this->_whitelistform->addFields($this);
        $this->_warnlistform->manageRequest($request);
        $this->_warnlistform->addFields($this);
        $this->_blacklistform->manageRequest($request);
        $this->_blacklistform->addFields($this);
        $this->_newslistform->manageRequest($request);
        $this->_newslistform->addFields($this);

    	$domain->setPref('viruswall', $domain->getPref('contentwall'));
    	$domain->setParam('greylist', $request->getParam('greylist'));
	$domain->setPref('prevent_spoof', $request->getParam('prevent_spoof'));
	$domain->setPref('reject_capital_domain', $request->getParam('reject_capital_domain'));
        $domain->setPref('require_incoming_tls', $request->getParam('require_incoming_tls'));
 
        ### newsl
        $domain->setPref('allow_newsletters', $request->getParam('allow_newsletters'));

        $this->_whitelistenabled = $domain->getPref('enable_whitelists');
        $this->_warnlistenabled = $domain->getPref('enable_warnlists');
        $this->_blacklistenabled = $domain->getPref('enable_blacklists');

        return true;
     }

	public function wwlistsEnabled() {
		$antispam = new Default_Model_AntispamConfig();
		$antispam->find(1);
		$ret = array();

		if ( $antispam->getParam('enable_whitelists') ) {
			$ret[] = 'whitelist';
		}
		if ($antispam->getParam('enable_warnlists') ) {
			$ret[] = 'warnlist';
		}
		if ($antispam->getParam('enable_blacklists') ) {
			$ret[] = 'blacklist';
		}
		return $ret;
	}

}
