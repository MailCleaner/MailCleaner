<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * empty callout form
 */

class Default_Form_Domain_AddressVerification_None
{
    protected $_domain;

    public function __construct($domain)
    {
        $this->_domain = $domain;
    }

    public function addForm($form)
    {
        $name = new Zend_Form_Element_Hidden('connector');
        $name->setValue('none');
        $form->addElement($name);

        $t = Zend_Registry::get('translate');
    }

    public function setParams($request, $domain)
    {
        $this->setParamsFromArray([], $domain);
    }

    public function setParamsFromArray($array, $domain)
    {
        $domain->setParam('callout', 'false');
        $domain->setParam('adcheck', 'false');
        $domain->setParam('addlistcallout', 'false');
        $domain->setCalloutConnector('none');
    }

    public function getParams()
    {
        return [];
    }

    public function getParamsString($params)
    {
        return '';
    }
}
