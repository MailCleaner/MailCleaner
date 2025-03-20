<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * SMTP callout form
 */

class Default_Form_Domain_AddressVerification_Smtp
{
    protected $_domain;

    public function __construct($domain)
    {
        $this->_domain = $domain;
    }

    public function addForm($form)
    {
        $name = new Zend_Form_Element_Hidden('connector');
        $name->setValue('smtp');
        $form->addElement($name);

        $t = Zend_Registry::get('translate');

        require_once('Validate/SMTPHostList.php');
        $alternateserver = new  Zend_Form_Element_Text('alternate', [
            'label'    => $t->_('Use alternate server') . " :",
            'required' => false,
            'filters'    => ['StringToLower', 'StringTrim']
        ]);
        $alternateserver->setValue($this->_domain->getParam('altcallout'));
        $alternateserver->addValidator(new Validate_SMTPHostList());
        $form->addElement($alternateserver);
    }

    public function setParams($request, $domain)
    {
        $this->setParamsFromArray(['callout_server' => $request->getParam('alternate')], $domain);
    }

    public function setParamsFromArray($array, $domain)
    {
        if (isset($array['callout_server'])) {
            $domain->setParam('altcallout', $array['callout_server']);
        }
        $domain->setParam('adcheck', 'false');
        $domain->setParam('callout', 'true');
        $domain->setParam('addlistcallout', 'false');
        $domain->setCalloutConnector('smtp');
    }

    public function getParams()
    {
        return ['callout_server' => $this->_domain->getParam('altcallout')];
    }

    public function getParamsString($params)
    {
        return '';
    }
}
