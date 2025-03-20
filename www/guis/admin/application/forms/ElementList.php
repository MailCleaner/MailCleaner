<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Generic element list form
 */

class Default_Form_ElementList extends ZendX_JQuery_Form
{
    protected $_list;
    protected $_elementClass;
    protected $_prefix = '';
    protected $_added_values = [];

    public function __construct($list, $class, $prefix = '')
    {
        $this->_list = $list;
        $this->_elementClass = $class;
        $this->_prefix = $prefix;
        parent::__construct();
    }


    public function init()
    {
        $t = Zend_Registry::get('translate');
        $layout = Zend_Layout::getMvcInstance();
        $view = $layout->getView();

        foreach ($this->_list as $element) {
            $this->addCheck($element);
        }
        $this->setMethod('post');

        $remove = new Zend_Form_Element_Submit($this->_prefix . 'remove', [
            'label'    => $t->_('Remove selected elements'),
            'title' => $t->_("Remove the selected items from the list"),
        ]);
        $this->addElement($remove);

        $disable = new Zend_Form_Element_Submit($this->_prefix . 'disable', [
            'label'    => $t->_('Enable/Disable selected elements'),
            'title' => $t->_("Switch the state of the selected items from Enable to Disable or from Disable to Enable"),
        ]);
        $this->addElement($disable);

        $addelement = new  Zend_Form_Element_Text($this->_prefix . 'addelement', [
            'required' => false,
            'class' => 'addelementfield',
            'filters'    => ['StringTrim']
        ]);
        $this->addElement($addelement);

        $addcomment = new  Zend_Form_Element_Text($this->_prefix . 'addcomment', [
            'required' => false,
            'class' => 'addcommentfield',
            'filters'    => ['StringTrim']
        ]);
        $this->addElement($addcomment);

        $add = new Zend_Form_Element_Submit($this->_prefix . 'add', [
            'label'    => $t->_('< Add element'),
            'title' => $t->_("Add the element in the Address field to the desired list"),
        ]);
        $this->addElement($add);
    }

    protected function addCheck($element)
    {
        $check = new Zend_Form_Element_Checkbox($this->_prefix . 'list_select_' . $element->getId(), [
            'uncheckedValue' => "0",
            'checkedValue' => "1",
            'class' => 'unchecked'
        ]);
        $this->addElement($check);;
    }

    public function setAddedValues($values)
    {
        $this->_added_values = $values;
    }

    public function manageRequest($request)
    {
        if ($request->getParam($this->_prefix . 'disable') != "") {
            $this->disableElements($request);
        }
        if ($request->getParam($this->_prefix . 'remove') != "") {
            $this->removeElements($request);
        }
        if ($request->getParam($this->_prefix . 'add') != "") {
            $this->addListElement($request);
        }
    }


    protected function disableElements($request)
    {
        foreach ($this->_list as $element) {
            if ($request->getParam($this->_prefix . 'list_select_' . $element->getId())) {
                if ($element->getStatus()) {
                    $element->disable();
                } else {
                    $element->enable();
                }
            }
        }
    }

    protected function removeElements($request)
    {
        foreach ($this->_list as $element) {
            if ($request->getParam($this->_prefix . 'list_select_' . $element->getId())) {
                $element->delete();
            }
        }
    }

    protected function addListElement($request)
    {
        if (!$request->getParam($this->_prefix . 'addelement')) {
            throw new Exception('provide element value');
        }

        $class = $this->_elementClass;

        $element = new $class();
        $element->setValue($request->getParam($this->_prefix . 'addelement'));
        $element->setComment($request->getParam($this->_prefix . 'addcomment'));
        foreach ($this->_added_values as $key => $value) {
            $element->setParam($key, $value);
        }

        $element->enable();
        $this->addCheck($element);
    }

    public function setParams($request, $list)
    {
    }


    public function addFields($form)
    {
        foreach ($this->getElements() as $el) {
            $form->addElement($el);
        }
    }
}
