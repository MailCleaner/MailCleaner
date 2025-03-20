<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of email addresses
 */

class Validate_PKIPrivateKey extends Zend_Validate_Abstract
{
    const MSG_PRIVATEKEY = 'invalidPrivateKey';

    protected $_messageTemplates = [
        self::MSG_PRIVATEKEY => "Not a valid private key"
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        $pki = new Default_Model_PKI();
        $pki->setPrivateKey($value);
        if ($pki->checkPrivateKey()) {
            return true;
        }
        $this->_error(self::MSG_PRIVATEKEY);
        return false;
    }
}
