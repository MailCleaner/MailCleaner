<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Interface login form
 */

class Default_Form_Login extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $t = Zend_Registry::get('translate');

        $usernameField = $this->createElement('text', 'username', [
            'label'      => $t->_('Username') . " :",
            'required'   => true,
            'filters'    => ['StringTrim'],
        ]);
        #$usernameField->addValidator(new Zend_Validate_Alnum());

        $usernameField->setDecorators([
            'ViewHelper',
            'Errors',
            [['data' => 'HtmlTag'], ['tag' => 'td', 'class' => 'element']],
            ['Label', ['tag' => 'td']],
            [['row' => 'HtmlTag'], ['tag' => 'tr']]
        ]);
        $usernameField->removeDecorator('Errors');
        $this->addElement($usernameField);

        $passwordField = $this->createElement('password', 'password', [
            'label'      => $t->_('Password') . " :",
            'required'   => true,
            'filters'    => ['StringTrim'],
            'validators' => [['validator' => 'StringLength', 'options' => [0, 100]]],
            'allowEmpty' => true,
        ]);
        $passwordField->setDecorators([
            'ViewHelper',
            'Errors',
            [['data' => 'HtmlTag'], ['tag' => 'td', 'class' => 'element']],
            ['Label', ['tag' => 'td']],
            [['row' => 'HtmlTag'], ['tag' => 'tr']],
        ]);
        $passwordField->removeDecorator('Errors');
        $this->addElement($passwordField);

        $loginButton = $this->createElement('submit', 'submit', ['label'      => 'login']);
        $loginButton->setDecorators([
            'ViewHelper',
            [['data' => 'HtmlTag'], ['tag' => 'td', 'class' => 'element']],
            [['label' => 'HtmlTag'], ['tag' => 'td', 'placement' => 'prepend']],
            [['row' => 'HtmlTag'], ['tag' => 'tr']],
        ]);
        $this->addElement($loginButton);

        $this->setDecorators([
            'FormElements',
            ['HtmlTag', ['tag' => 'table']],
            'Form',
        ]);
    }
}
