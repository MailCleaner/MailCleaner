<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Email management form
 */

class Default_Form_Manage_EmailArchiving extends Zend_Form
{
    public $_email;
    protected $_panelname = 'archiving';
    protected $_canArchive = false;

    public function __construct($email)
    {
        $this->_email = $email;

        $sysconf = new Default_Model_SystemConf();
        $sysconf->load();
        if ($sysconf->getParam('use_archiver')) {
            $this->_canArchive = true;
        }

        parent::__construct();
    }


    public function init()
    {
        parent::init();
        $this->setMethod('post');

        $t = Zend_Registry::get('translate');
        $restrictions = Zend_Registry::get('restrictions');

        $this->setAttrib('id', 'email_form');
        $panellist = new Zend_Form_Element_Select('emailpanel', [
            'required'   => false,
            'filters'    => ['StringTrim']
        ]);
        ## TODO: add specific validator
        $panellist->addValidator(new Zend_Validate_Alnum());

        foreach ($this->_email->getConfigPanels() as $panel => $panelname) {
            $panellist->addMultiOption($panel, $panelname);
        }
        $panellist->setValue($this->_panelname);
        $this->addElement($panellist);

        $panel = new Zend_Form_Element_Hidden('panel');
        $panel->setValue($this->_panelname);
        $this->addElement($panel);
        $name = new Zend_Form_Element_Hidden('address');
        $name->setValue($this->_email->getParam('address'));
        $this->addElement($name);

        $archive_mail = new Zend_Form_Element_Checkbox('archive_mail', [
            'label'   => $t->_('Archive messages') . " :",
            'uncheckedValue' => "0",
            'checkedValue' => "1"
        ]);

        if ($this->_email->getPref('archive_mail')) {
            $archive_mail->setChecked(true);
        }
        $this->addElement($archive_mail);

        $copyto_mail = new  Zend_Form_Element_Text('copyto_mail', [
            'label'    => $t->_('Send a copy of all messages to') . " :",
            'required' => false,
            'size' => 40,
            'filters'    => ['StringTrim']
        ]);
        $copyto_mail->setValue($this->_email->getPref('copyto_mail'));
        $copyto_mail->addValidator(new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_LOCAL));
        $this->addElement($copyto_mail);
        if ($restrictions->isRestricted('EmailArchiving', 'copyto')) {
            $copyto_mail->setAttrib('disabled', 'disabled');
        }

        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $email)
    {

        $restrictions = Zend_Registry::get('restrictions');

        if ($this->canArchive()) {
            $email->setPref('archive_mail', $request->getParam('archive_mail'));
        }
        if ($request->getParam('copyto_mail') && $restrictions->isRestricted('EmailArchiving', 'copyto')) {
            throw new Exception('Access restricted');
        }
        $email->setPref('copyto_mail', $request->getParam('copyto_mail'));

        return true;
    }

    public function canArchive()
    {
        return $this->_canArchive;
    }
}
