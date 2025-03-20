<?php

class Default_Form_Httpd extends ZendX_JQuery_Form
{
    protected $_httpd;
    protected $_firewallrule;

    public $_urlsheme = 'http';

    public function __construct($httpd, $fw)
    {
        $this->_httpd = $httpd;
        $this->_firewallrule = $fw;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');

        $this->setAttrib('id', 'httpd_form');

        $baseurl = new  Zend_Form_Element_Text('servername', [
            'label'    => $t->_('Base URL') . " :",
            'title' => $t->_("Base URL to access this we interface"),
            'size'     => 30,
            'required' => true
        ]);
        $baseurl->setValue($this->_httpd->getParam('servername'));
        $this->addElement($baseurl);

        require_once('Validate/HostList.php');
        $allowed_ip = new Zend_Form_Element_Textarea('allowed_ip', [
            'label'    =>  $t->_('Allowed IP/ranges') . " :",
            'title' => $t->_("IP/range allowed to connect to the MailCleaner web interface"),
            'required'   => false,
            'rows' => 5,
            'cols' => 30,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $allowed_ip->addValidator(new Validate_HostList());
        $allowed_ip->setValue($this->_firewallrule->getParam('allowed_ip'));
        $this->addElement($allowed_ip);

        $sslenable = new Zend_Form_Element_Checkbox('use_ssl', [
            'label'   => $t->_('Enable SSL (HTTPS)') . " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);
        if ($this->_httpd->getParam('use_ssl') == 'true') {
            $sslenable->setChecked(true);
        }
        $this->addElement($sslenable);

        $httpslisten = new  Zend_Form_Element_Text('https_port', [
            'label'    => $t->_('HTTPS port') . " :",
            'required' => false,
            'size' => 4,
            'filters'    => ['Alnum', 'StringTrim']
        ]);
        $httpslisten->setValue($this->_httpd->getParam('https_port'));
        $httpslisten->addValidator(new Zend_Validate_Int());
        $this->addElement($httpslisten);

        $httplisten = new  Zend_Form_Element_Text('http_port', [
            'label'    => $t->_('HTTP port') . " :",
            'required' => true,
            'size' => 4,
            'filters'    => ['Alnum', 'StringTrim']
        ]);
        $httplisten->setValue($this->_httpd->getParam('http_port'));
        $httplisten->addValidator(new Zend_Validate_Int());
        $this->addElement($httplisten);

        $restrictions = Zend_Registry::get('restrictions');

        $sslcert = new Zend_Form_Element_Textarea('tls_certificate_data', [
            'label'    =>  $t->_('Encoded SSL certificate') . " :",
            'required'   => false,
            'rows' => 7,
            'cols' => 50
        ]);
        $sslcert->setValue($this->_httpd->getParam('tls_certificate_data'));
        $this->addElement($sslcert);

        if ($restrictions->isRestricted('ServicesHTTP', 'certificate')) {
            $sslcert->setAttrib('disabled', 'disabled');
        } else {
            require_once('Validate/PKICertificate.php');
            $sslcert->addValidator(new Validate_PKICertificate());
        }

        $sslkey = new Zend_Form_Element_Textarea('tls_certificate_key', [
            'label'    =>  $t->_('Encoded SSL private key') . " :",
            'required'   => false,
            'rows' => 7,
            'cols' => 50
        ]);
        $sslkey->setValue($this->_httpd->getParam('tls_certificate_key'));
        $this->addElement($sslkey);

        if ($restrictions->isRestricted('ServicesHTTP', 'certificate')) {
            $sslkey->setAttrib('disabled', 'disabled');
            $sslkey->setValue("-----BEGIN RSA PRIVATE KEY-----\n                  " . $t->_('*** hidden ***') . "\n-----END RSA PRIVATE KEY-----");
        } else {
            require_once('Validate/PKIPrivateKey.php');
            $sslkey->addValidator(new Validate_PKIPrivateKey());
        }

        $sslchain = new Zend_Form_Element_Textarea('tls_certificate_chain', [
            'label'    =>  $t->_('Encoded SSL certificate chain') . " :",
            'required'   => false,
            'rows' => 7,
            'cols' => 50
        ]);
        $sslchain->setValue($this->_httpd->getParam('tls_certificate_chain'));
        $this->addElement($sslchain);

        if ($restrictions->isRestricted('ServicesHTTP', 'certificate')) {
            $sslchain->setAttrib('disabled', 'disabled');
        } else {
            require_once('Validate/PKICertificate.php');
            $sslchain->addValidator(new Validate_PKICertificate());
        }

        if ($this->_httpd->getParam('use_ssl') == 'true') {
            $this->_urlsheme = 'https';
        }
        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $httpd, $fwrule)
    {
        $t = Zend_Registry::get('translate');
        $restrictions = Zend_Registry::get('restrictions');

        $httpd->setParam('use_ssl', $request->getParam('use_ssl'));
        $httpd->setParam('servername', $request->getParam('servername'));
        $fwrule->setParam('allowed_ip', $request->getParam('allowed_ip'));
        $httpd->setParam('http_port', $request->getParam('http_port'));
        $fwrule->setParam('port', $request->getParam('http_port'));
        if ($request->getParam('use_ssl')) {
            if (!$request->getParam('https_port')) {
                throw new Exception($t->_('Please provide a valid port'));
            }
            if (!$restrictions->isRestricted('ServicesHTTP', 'certificate')) {
                if (!$request->getParam('tls_certificate_data') || !$request->getParam('tls_certificate_key')) {
                    throw new Exception($t->_('Please provide valid certificate data'));
                }
            }
            $httpd->setParam('https_port', $request->getParam('https_port'));
            if (!$restrictions->isRestricted('ServicesHTTP', 'certificate')) {
                $httpd->setParam('tls_certificate_data', $request->getParam('tls_certificate_data'));
                $httpd->setParam('tls_certificate_key', $request->getParam('tls_certificate_key'));
                $httpd->setParam('tls_certificate_chain', $request->getParam('tls_certificate_chain'));
            }
            $fwrule->setParam('port', $request->getParam('http_port') . "|" . $request->getParam('https_port'));
        }
        if ($httpd->getParam('use_ssl') && !$restrictions->isRestricted('ServicesHTTP', 'certificate')) {
            $cert = new Default_Model_PKI();
            $cert->setCertificate($httpd->getParam('tls_certificate_data'));
            $cert->setPrivateKey($httpd->getParam('tls_certificate_key'));
            if (!$cert->checkCertAndKey()) {
                throw new Exception($t->_('Certificate and key does not match'));
            }
        }

        $httpd->save();
        $fwrule->save();

        if ($httpd->getParam('use_ssl') == 'true') {
            $this->_urlsheme = 'https';
        } else {
            $this->_urlsheme = 'http';
        }
    }
}
