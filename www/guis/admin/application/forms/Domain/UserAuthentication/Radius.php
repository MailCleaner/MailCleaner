<?php 
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2009, Olivier Diserens
 * 
 * Radius user authentication settings form
 */

class Default_Form_Domain_UserAuthentication_Radius
{
	protected $_domain;
    protected $_settings = array(
                        "radiussecret" => '',
                        "radiusauthtype" => 'PAP'
     );
	
	public function __construct($domain)
	{
	    $this->_domain = $domain;
	}
	
	public function addForm($form) {
		$name = new Zend_Form_Element_Hidden('connector');
		$name->setValue('none');
		$form->addElement($name);
		
		$t = Zend_Registry::get('translate');
		
		require_once('Validate/SMTPHostList.php');
		$server = new  Zend_Form_Element_Text('authserver', array(
	        'label'    => $t->_('Authentication server')." :",
		    'required' => false,
		    'filters'    => array('StringToLower', 'StringTrim')));
	    $server->setValue($this->_domain->getPref('auth_server'));
        $server->addValidator(new Validate_SMTPHostList());
	    $form->addElement($server);
	    
	    $this->_settings = $this->getParams();
	    
		$secret = new  Zend_Form_Element_Password('radiussecret', array(
	        'label'    => $t->_('Secret')." :",
		    'required' => false,
	        'renderPassword' => true,
		    'filters'    => array('StringTrim')));
	    $secret->setValue($this->_settings['radiussecret']);
	    $form->addElement($secret);
	    
	    $auth_type = new Zend_Form_Element_Select('radiusauthtype', array(
            'label'      => $t->_('Authentication type')." :",
            'required'   => false,
            'filters'    => array('StringTrim')));
        
        foreach (array('PAP', 'CHAP_MD5', 'MSCHAPv1', 'MSCHAPv2') as $value) {
        	$auth_type->addMultiOption($value, $value);
        }
        $auth_type->setValue($this->_settings['radiusauthtype']);
        $form->addElement($auth_type);
	}
	
	public function setParams($request, $domain) {
       $array = array(
          'auth_server' => $request->getParam('authserver'),
          'radiussecret' => $request->getParam('radiussecret'),
          'radiusauthtype' => $request->getParam('radiusauthtype')
       );
       $this->setParamsFromArray($array, $domain);
  	}
	
    public function setParamsFromArray($array, $domain) {
       $domain->setPref('auth_type', 'radius');
       $domain->setPref('auth_server', $array['auth_server']);
       $domain->setPref('auth_param', $this->getParamsString($array));
    }

    public function getParams() {
       $params = $this->_settings;
       if ($this->_domain->getAuthConnector() != 'radius') {
           return $params;
       }
       if (preg_match('/([^:]*)/', $this->_domain->getPref('auth_param'), $matches)) {
            $params['radiussecret'] = $matches[1];
            $params['radiusauthtype'] = $matches[1];
        }
        foreach ($params as $key => $value) {
            $params[$key] = preg_replace('/__C__/', ':', $value);
        }
        $params['auth_server'] = $this->_domain->getPref('auth_server');
        return $params;
    }
    
    public function getParamsString($params) {
       $fields = array('radiussecret', 'radiusauthtype');
       $str = '';
        foreach ($fields as $key) {
            if (isset($params[$key])) {
                $params[$key] = preg_replace('/:/', '__C__', $params[$key]);
            } else {
                $params[$key] = $this->_settings[$key];
            }
            $str .= ':'.$params[$key];
        }
        $str = preg_replace('/^:/', '', $str);
        return $str;
    }
}