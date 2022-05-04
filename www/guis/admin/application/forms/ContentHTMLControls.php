<?php
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * HTML controls form
 */

class Default_Form_ContentHTMLControls extends ZendX_JQuery_Form
{
	protected $_dangerouscontent;
	protected $_antispam;
	protected $_fields = array(
		   'allow_iframe' => array('text' => 'IFrame objects', 'silent' => 'silent_iframe'),
		   'allow_form' => array('text' => 'Forms', 'silent' => 'silent_form'),
		   'allow_script' => array('text' => 'Scripts', 'silent' => 'silent_script'),
		   'allow_codebase' => array('text' => 'Codebase objects', 'silent' => 'silent_codebase'),
		   'allow_webbugs' => array('text' => 'Web Bugs', 'silent' => 'silent_webbugs'),
		);
	
	public function __construct($dc,$as) {
		$this->_dangerouscontent = $dc;
		$this->_antispam = $as;
		parent::__construct();
	}
	
	
	public function init()
	{
		$t = Zend_Registry::get('translate');
		$layout = Zend_Layout::getMvcInstance();
    	$view=$layout->getView();
    	
		$this->setMethod('post');
		
		
		$this->setAttrib('id', 'contenthtmlcontrols_form');
	    
		$allowoptions = array('yes' => $t->_('allow'), 'no' => $t->_('block'));
		$blockoptions = array('no' => $t->_('allow'), 'yes' => $t->_('block'));
		$disarmoptions = array('yes' => $t->_('allow'), 'no' => $t->_('block'), 'disarm' => $t->_('disarm'));
		
		foreach ($this->_fields as $mf => $f) {
			
		  $ff = new Zend_Form_Element_Select($mf, array(
               'label'      => $t->_($f['text'])." :",
               'title' => $t->_("Choose action to perform when this item is detected inside an HTML document"),
               'required'   => true,
               'filters'    => array('StringTrim')));
        
          foreach ($disarmoptions as $lk => $lv) {
             $ff->addMultiOption($lk, $lv);
          }
          $ff->setValue($this->_dangerouscontent->getParam($mf));
          $this->addElement($ff);
          $sff = new Zend_Form_Element_Checkbox($f['silent'], array(
	           'label'   => $t->_('silently'),
                   'title' => $t->_("Enable/Disable warnings"),
               'uncheckedValue' => "0",
	           'checkedValue' => "1"
	       ));
	       if ($this->_dangerouscontent->getParam($f['silent']) == 'yes') {
	       	$sff->setChecked(true);
	       }
	      $this->addElement($sff);
		}
        
		require_once('Validate/IpList.php');
		$trustednet = new Zend_Form_Element_Textarea('html_wl_ips', array(
		      'label'    =>  $t->_('Trusted IPs/Networks')." :",
                      'title' => $t->_("These IP/ranges are whitelist for the HTML controls"),
		      'required'   => false,
		      'rows' => 5,
		      'cols' => 30,
		      'filters'    => array('StringToLower', 'StringTrim')));
	    $trustednet->addValidator(new Validate_IpList());
		$trustednet->setValue($this->_antispam->getParam('html_wl_ips'));
		$this->addElement($trustednet);
	    
		$submit = new Zend_Form_Element_Submit('submit', array(
		     'label'    => $t->_('Submit')));
		$this->addElement($submit);
		
	}
	
	public function setParams($request, $dc, $as) {
		foreach ($this->_fields as $mf => $f) {
			$dc->setParam($mf, $request->getParam($mf));
			if ($request->getParam($f['silent'])) {
				$dc->setParam($f['silent'], 'yes');
			} else {
				$dc->setParam($f['silent'], 'no');
			}
		}
        $dc->save();
		$as->setparam('html_wl_ips', $request->getParam('html_wl_ips'));
        $as->save();
	}
}
