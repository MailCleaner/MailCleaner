<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * File name
 */

class Default_Model_PKI
{
    private $_privateKey;
    private $_publicKey;
    private $_certificate;
    private $_type = 'dsa';

    private $OPENSSLCOMMAND = '/usr/bin/openssl';
    private $KEYTYPES = ['dsa' => 'gendsa', 'rsa' => 'genrsa', 'ecdsa' => 'genecdsa');


    public function getPrivateKey()
    {
        return $this->_privateKey;
    }
    public function getPrivateKeyNoPEM()
    {
        return $this->removePEMHeaders($this->_privateKey);
    }

    public function getPublicKey()
    {
        return $this->_publicKey;
    }
    public function getPublicKeyNoPem()
    {
        return $this->removePEMHeaders($this->_publicKey);
    }

    public function setPrivateKey($pkey)
    {
        if (preg_match('/[^-+\/=|A-Za-z0-9\s\r\n\t]/', $pkey, $matches)) {
            var_dump($matches);
            return false;
        }
        $this->_type = $this->getKeyType($pkey);
        $this->_privateKey = $pkey;
        $this->getPublicKeyFromPrivateKey();
    }

    public function setCertificate($cert)
    {
        $this->_certificate = $cert;
    }

    public function createKey($params)
    {

        if (!isset($params)) {
            return false;
        }

        $type = 'ecdsa';
        $length = '2048';

        if (isset($params['type']) and array_key_exists(strtolower($params['type']), $this->KEYTYPES)) {
            $type = strtolower($params['type']);
        }
        if (isset($params['length']) and is_numeric($params['length'])) {
            $length = $params['length'];
        }

        $tmpfile = "/tmp/" . uniqid() . ".tmp";
        switch ($type) {
            case 'ecdsa':
                $cmd = $this->OPENSSLCOMMAND . " " . $this->KEYTYPES[$type] . " ecparam -noout -out " . $tmpfile . " -genkey";
                break;
            case 'rsa':
                $cmd = $this->OPENSSLCOMMAND . " " . $this->KEYTYPES[$type] . " -out " . $tmpfile . " " . $length;
                break;
            case 'dsa':
                $cmd = $this->OPENSSLCOMMAND . " dsaparam -noout -out " . $tmpfile . " -genkey " . $length;
                break;
        }
        $res = `$cmd`;
        $this->_privateKey = trim(file_get_contents($tmpfile));

        $this->_type = $type;
        $this->getPublicKeyFromPrivateKey();
        unlink($tmpfile);
        return true;
    }

    static public function removePEMHeaders($key)
    {
        $nkey = preg_replace('/-----[ A-Z]+-----\s*/', '', $key);
        $nkey = preg_replace('/[\n\r]/', '', $nkey);
        return trim($nkey);
    }

    private function getPublicKeyFromPrivateKey()
    {
        $tmpfile = "/tmp/" . uniqid() . ".tmp";
        $tmppubfile = "/tmp/" . uniqid() . ".tmp";
        file_put_contents($tmpfile, $this->_privateKey);

        $cmd = $this->OPENSSLCOMMAND . " " . $this->_type . " -in " . $tmpfile . " -out " . $tmppubfile . " -pubout -outform PEM";
        `$cmd`;
        if (file_exists($tmppubfile)) {
            $this->_publicKey = trim(file_get_contents($tmppubfile));
            unlink($tmppubfile);
        }
        if (file_exists($tmpfile)) {
            unlink($tmpfile);
        }
    }

    public function checkPrivateKey()
    {
        $tmpfile = "/tmp/" . uniqid() . ".tmp";
        file_put_contents($tmpfile, $this->_privateKey);
        $cmd = $this->OPENSSLCOMMAND . " " . $this->_type . " -check -in " . $tmpfile;
        $res = preg_split('/\n/', `$cmd`);
        unlink($tmpfile);
        if (preg_match('/(RSA|DSA) key ok/', $res[0])) {
            return 1;
        }
        return 0;
    }

    public function getCertificateData()
    {

        $data = ['valid' => 0, 'issuer' => '', 'until' => '', 'subject' => '', 'error' => '', 'selfsigned' => 0, 'expired' => 0];
        $tmpfile = "/tmp/" . uniqid() . ".tmp";
        file_put_contents($tmpfile, $this->_certificate);
        $cmd = $this->OPENSSLCOMMAND . " x509 -in " . $tmpfile . " -text -noout 2>&1";
        $res = preg_split('/\n/', `$cmd`);
        $subject_full;
        unlink($tmpfile);
        foreach ($res as $line) {
            if (preg_match('/^\s*Issuer: (.*)/', $line, $matches)) {
                $data['issuer'] = $matches[1];
                $data['valid'] = 1;
            }
            if (preg_match('/^\s*Not After\s*: (.*)/', $line, $matches)) {
                $data['until'] = $matches[1];
            }
            if (preg_match('/^\s*Subject: (.*)/', $line, $matches)) {
                $data['subject'] = $matches[1];
                $subject_full = $matches[1];
                if (preg_match('/O\s*=\s*([^,]+)/', $data['subject'], $matches)) {
                    $data['subject'] = $matches[1];
                }
            }
        }
        if ($data['issuer'] == $subject_full && $subject_full != '') {
            $data['selfsigned'] = 1;
        }
        if (!$data['valid']) {
            $data['error'] = $res[0];
        }
        if ($data['until'] != '') {
            $date_until = new Zend_Date($data['until'], "MMM d HH:mm:ss yyyy ZZ");
            if ($date_until->isEarlier(new Zend_Date())) {
                $data['expired'] = 1;
                $data['error'] = 'Certificate has expired (' . $date_until . ')';
                $data['valid'] = 1;
            }
        }
        return $data;
    }

    public function checkCertificate()
    {
        $data = $this->getCertificateData();
        if ($data['valid']) {
            return true;
        }
        return false;
    }

    public function getKeyType($pkey) {
        if (preg_match('/([A-Z]+) PRIVATE KEY/', $pkey, $matches)) {
            if ($matches[1] == 'ECDSA') { return 'ecdsa'; }
            if ($matches[1] == 'RSA') { return 'rsa'; }
            if ($matches[1] == 'RSA') { return 'dsa'; }
            return 'rsa';
        }
    }

    public function checkCertAndKey()
    {
        $tmpfile = "/tmp/" . uniqid() . ".tmp";
        file_put_contents($tmpfile, $this->_certificate);
        $tmpfilekey = "/tmp/" . uniqid() . ".tmp";
        file_put_contents($tmpfilekey, $this->_privateKey);
        $type = $this->getKeyType($this->_privateKey);
        if ($type == 'rsa') {
            $cmd = $this->OPENSSLCOMMAND . " x509 -noout -modulus -in $tmpfile | " . $this->OPENSSLCOMMAND . " md5";
            $certhash = `$cmd`;
            $cmd2 = $this->OPENSSLCOMMAND . " rsa -noout -modulus -in $tmpfilekey | " . $this->OPENSSLCOMMAND . " md5";
            $keyhash = `$cmd2`;
        } elseif ($type == 'ecdsa') {
            $cmd = $this->OPENSSLCOMMAND." x509 -in $tmpfile -pubout | ".$this->OPENSSLCOMMAND." md5";
            $certhash = `$cmd`;
            $cmd2 = $this->OPENSSLCOMMAND." pkey -in $tmpfilekey -pubout | ".$this->OPENSSLCOMMAND." md5";
            $keyhash = `$cmd2`;
        }
        if (!isset($certhash) || !isset($keyhash) || $certhash != $keyhash) {
            $data['error'] = 'Private key does not match certificate';
            $data['valid'] = 0;
            return false;
        }
        return true;
    }
}
