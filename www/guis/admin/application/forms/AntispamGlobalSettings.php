<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Antispam global settings form
 */

class Default_Form_AntispamGlobalSettings extends ZendX_JQuery_Form
{
	protected $_antispam;
	public $_whitelist;
	public $_warnlist;
	//blacklistmr
	public $_blacklist;
    public $_newslist;
	
	public $_whitelistenabled = 0;
	public $_warnlistenabled = 0;
	public $_blacklistenabled = 0;
	
	protected $_whitelistform;
	protected $_warnlistfrom;
	protected $_blacklistrom;
    protected $_newslistform;
	
	public function __construct($as, $whitelist, $warnlist, $blacklist, $newslist) {
		$this->_antispam = $as;
		$this->_whitelist = $whitelist;
		$this->_warnlist = $warnlist;
		$this->_blacklist = $blacklist;
        $this->_newslist = $newslist;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
	           
		$this->setAttrib('id', 'antispamglobalsettings_form');

         	$maxsize = new Zend_Form_Element_Text('global_max_size', array(
		    'label'    => $t->_('Global max scan size (KB)'). " :",
		    'required' => false,
		    'filters'    => array('StringTrim')));
        	$maxsize->addValidator(new Zend_Validate_Int());
	    	$maxsize->setValue($this->_antispam->getParam('global_max_size'));
		$this->addElement($maxsize);
		
		require_once('Validate/IpList.php');
		$trustednet = new Zend_Form_Element_Textarea('trusted_ips', array(
		      'label'    =>  $t->_('Trusted IPs/Networks')." :",
                      'title' => $t->_("These IP/ranges are whitelist for the antispam part"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $trustednet->addValidator(new Validate_IpList());
		$trustednet->setValue($this->_antispam->getParam('trusted_ips'));
		$this->addElement($trustednet);
		
		$enablewhitelists = new Zend_Form_Element_Checkbox('enable_whitelists', array(
	        'label'   => $t->_('Enable access to whitelists'). " :",
                'title' => $t->_("Activate globally that whitelist behavior is becoming available, after global whitelist also become availableActivate globally that whitelist behavior is becoming available, after global whitelist also become available"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
               $enableblacklists = new Zend_Form_Element_Checkbox('enable_blacklists', array(
                 'label'   => $t->_('Enable access to blacklists'). " :",
                'title' => $t->_("Activate globally that blacklist behavior is becoming available, after global blacklist also become availableActivate globally that blacklist behavior is becoming available, after global blacklist also become available"),
             'uncheckedValue' => "0",
                 'checkedValue' => "1"
                       ));

	    if ($this->_antispam->getParam('enable_whitelists')) {
            $enablewhitelists->setChecked(true);
            $this->_whitelistenabled = 1;
	    }
	    $this->addElement($enablewhitelists);
	    
	    $enablewarnlists = new Zend_Form_Element_Checkbox('enable_warnlists', array(
	        'label'   => $t->_('Enable access to warnlists'). " :",
                'title' => $t->_("Activate globally that warnlist behavior is becoming available, after global warnlist also become availableActivate globally that warnlist behavior is becoming available, after global warnlist also become available"),
            'uncheckedValue' => "0",
	        'checkedValue' => "1"
	              ));
	    if ($this->_antispam->getParam('enable_warnlists')) {
            $enablewarnlists->setChecked(true);
            $this->_warnlistenabled = 1;
	    }
	    $this->addElement($enablewarnlists);
	    
	    $tagmodbypasswhitelist = new Zend_Form_Element_Checkbox('tag_mode_bypass_whitelist', array(
            'label'   => $t->_('Ignore whitelist in tag mode'). " :",
            'title' => $t->_("since tag mode get all messages delivered, one may want to ignore the whitelist in this case"),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));
	if ($this->_antispam->getParam('enable_blacklists')) {
            $enableblacklists->setChecked(true);
            $this->_blacklistenabled = 1;
            }
            $this->addElement($enableblacklists);

        if ($this->_antispam->getParam('tag_mode_bypass_whitelist')) {
            $tagmodbypasswhitelist->setChecked(true);
        }
        $this->addElement($tagmodbypasswhitelist);




            $whitelistbothfrom = new Zend_Form_Element_Checkbox('whitelist_both_from', array(
            'label'   => $t->_('Apply whitelist on Body-From too'). " :",
            'title' => $t->_("By default whitelists are checked versus SMTP-From. Activating this feature will use whitelist versus Body-From as well. If unsure please leave this option unchecked."),
            'uncheckedValue' => "0",
            'checkedValue' => "1"
                  ));

        if ($this->_antispam->getParam('whitelist_both_from')) {
            $whitelistbothfrom->setChecked(true);
        }
        $this->addElement($whitelistbothfrom);



	    
	     
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
		$this->_whitelistform = new Default_Form_ElementList($this->_whitelist, 'Default_Model_WWElement', 'whitelist_');
		$this->_whitelistform->init();
		$this->_whitelistform->setAddedValues(array('recipient' => '', 'type' => 'white'));
		$this->_whitelistform->addFields($this);
	
    		$this->_warnlistform = new Default_Form_ElementList($this->_warnlist, 'Default_Model_WWElement', 'warnlist_');
		$this->_warnlistform->init();
		$this->_warnlistform->setAddedValues(array('recipient' => '', 'type' => 'warn'));
		$this->_warnlistform->addFields($this);

		$this->_blacklistform = new Default_Form_ElementList($this->_blacklist, 'Default_Model_WWElement', 'blacklist_');
                $this->_blacklistform->init();
                $this->_blacklistform->setAddedValues(array('recipient' => '', 'type' => 'black'));
                $this->_blacklistform->addFields($this);
		
		$this->_newslistform = new Default_Form_ElementList($this->_newslist, 'Default_Model_WWElement', 'newslist_');
		$this->_newslistform->init();
		$this->_newslistform->setAddedValues(array('recipient' => '', 'type' => 'wnews'));
		$this->_newslistform->addFields($this);
	}
	
	public function getWhitelistForm() {
		return $this->_whitelistform;
	}
	
   public function getWarnlistForm() {
		return $this->_warnlistform;
	}

	public function getBlacklistForm() {
                return $this->_blacklistform;
        }
	
	public function setParams($request, $as) {
		$this->_whitelistform->manageRequest($request);
		$this->_whitelistform->addFields($this);
		$this->_warnlistform->manageRequest($request);
		$this->_warnlistform->addFields($this);
		$this->_blacklistform->manageRequest($request);
                $this->_blacklistform->addFields($this);
		$this->_newslistform->manageRequest($request);
		$this->_newslistform->addFields($this);


		$as->setparam('global_max_size', $request->getParam('global_max_size'));
		$as->setparam('trusted_ips', $request->getParam('trusted_ips'));
		$as->setparam('enable_whitelists', $request->getParam('enable_whitelists'));
		$as->setparam('enable_warnlists', $request->getParam('enable_warnlists'));
		$as->setparam('enable_blacklists', $request->getParam('enable_blacklists'));
	        $as->setparam('tag_mode_bypass_whitelist', $request->getParam('tag_mode_bypass_whitelist'));
	        $as->setparam('whitelist_both_from', $request->getParam('whitelist_both_from'));
		
		$this->_whitelistenabled = $as->getParam('enable_whitelists');
		$this->_warnlistenabled = $as->getParam('enable_warnlists');
		$this->_blacklistenabled = $as->getParam('enable_blacklists');
	}
}
