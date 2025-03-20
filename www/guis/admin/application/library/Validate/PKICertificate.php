<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Validate a list of email addresses
 */

class Validate_PKICertificate extends Zend_Validate_Abstract
{
    const MSG_CERTIFICATE = 'invalidCertificate';

    protected $_messageTemplates = [
        self::MSG_CERTIFICATE => "Not a valid certificate"
    ];

    public function isValid($value)
    {
        $this->_setValue($value);

        $pki = new Default_Model_PKI();
        $pki->setCertificate($value);
        if ($pki->checkCertificate()) {
            return true;
        }
        $this->_error(self::MSG_CERTIFICATE);
        return false;
    }
}
