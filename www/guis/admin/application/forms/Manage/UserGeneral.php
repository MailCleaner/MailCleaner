<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * User general settings form
 */

class Default_Form_Manage_UserGeneral extends Zend_Form
{
    protected $_user;
    protected $_panelname = 'general';

    public function __construct($user)
    {
        $this->_user = $user;

        parent::__construct();
    }


    public function init()
    {
        $this->setMethod('post');

        $t = Zend_Registry::get('translate');

        $this->setAttrib('id', 'user_form');
        $panellist = new Zend_Form_Element_Select('userpanel', [
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);
        ## TODO: add specific validator
        $panellist->addValidator(new Zend_Validate_Alnum());

        foreach ($this->_user->getConfigPanels() as $panel => $panelname) {
            $panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);

        $panel = new Zend_Form_Element_Hidden('panel');
        $panel->setValue($this->_panelname);
        $this->addElement($panel);
        $name = new Zend_Form_Element_Hidden('username');
        $name->setValue($this->_user->getParam('username'));
        $this->addElement($name);


        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $domain)
    {
        foreach ([''] as $pref) {
            if ($request->getParam($pref)) {
                $domain->setPref($pref, $request->getParam($pref));
            }
        }
        return true;
    }
}
