<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Message format controls form
 */

class Default_Form_ContentMessageFormat extends ZendX_JQuery_Form
{
    protected $_dangerouscontent;

    protected $_allowoptions = ['yes' => 'allow', 'no' => 'block'];
    protected $_blockoptions = ['no' => 'allow', 'yes' => 'block'];

    protected $_fields = [
        'block_encrypt' => ['text' => 'Encrypted messages', 'options' => '_blockoptions'],
        'block_unencrypt' => ['text' => 'Unencrypted messages', 'options' => '_blockoptions'],
        'allow_passwd_archives' => ['text' => 'Password protected archives', 'options' => '_allowoptions'],
        'allow_partial' => ['text' => 'Partial contents', 'options' => '_allowoptions'],
        'allow_external_bodies' => ['text' => 'External bodies', 'options' => '_allowoptions'],
    ];

    public function __construct($dc)
    {
        $this->_dangerouscontent = $dc;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        $this->setMethod('post');


        $this->setAttrib('id', 'contenthtmlcontrols_form');

        $allowoptions = ['yes' => $t->_('allow'), 'no' => $t->_('block')];
        $blockoptions = ['no' => $t->_('allow'), 'yes' => $t->_('block')];
        $disarmoptions = ['yes' => $t->_('allow'), 'no' => $t->_('block'), 'disarm' => $t->_('disarm')];

        foreach ($this->_fields as $mf => $f) {

            $ff = new Zend_Form_Element_Select($mf, [
                'label'      => $t->_($f['text']) . " :",
                'required'   => true,
                'filters'    => ['StringTrim']
            ]);

            foreach ($this->{$f['options']} as $lk => $lv) {
                $ff->addMultiOption($lk, $t->_($lv));
            }
            $ff->setValue($this->_dangerouscontent->getParam($mf));
            $this->addElement($ff);
        }


        $submit = new Zend_Form_Element_Submit('submit', [
            'label'    => $t->_('Submit')
        ]);
        $this->addElement($submit);
    }

    public function setParams($request, $dc)
    {
        foreach ($this->_fields as $mf => $f) {
            $dc->setParam($mf, $request->getParam($mf));
        }
        $dc->save();
    }
}
