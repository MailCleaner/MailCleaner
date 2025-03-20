<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

class Api_Model_QuarantineAPI
{

    public function getSpam($params)
    {

        function xml_escape($s)
        {
            $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
            $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);
            return $s;
        }

        if (!Zend_Registry::isRegistered('user')) {
            Zend_Registry::get('response')->setResponse(401, 'authentication required');
            return false;
        }
        if (!isset($params['id']) || !preg_match('/^([a-z,A-Z,0-9]{6}-[a-z,A-Z,0-9]{6,11}-[a-z,A-Z,0-9]{2,4})$/', $params['id'], $matches)) {
            Zend_Registry::get('response')->setResponse(500, 'Invalid message ID');
            return false;
        }
        if (!isset($params['recipient']) || !preg_match('/^(\S+)\@(\S+)$/', $params['recipient'], $matches)) {
            Zend_Registry::get('response')->setResponse(500, 'Invalid recipient');
            return false;
        }

        set_include_path(implode(PATH_SEPARATOR, [
            realpath(APPLICATION_PATH . '/../../../../classes'),
            get_include_path()
        ]));
        require_once('user/Spam.php');

        $spam = new Spam();
        $spam->loadDatas($params['id'], $params['recipient']);
        $spam->loadHeadersAndBody();

        $data = [];
        $spamdata = $spam->getAllData();
        foreach ($spamdata as $key => $value) {
            $data[$key] = $value;
        }
        $headers = $spam->getUnrefinedHeaders();
        if (!$headers || $headers == '') {
            Zend_Registry::get('response')->setResponse(500, 'Message cannot be loaded');
            return false;
        }
        $body = '';
        if (isset($params['includebody']) && $params['includebody']) {
            $body = $spam->getRawBody();
        }

        $data['headers'] = xml_escape(utf8_encode($headers));
        if ($body != '') {
            $data['body'] = xml_escape(utf8_encode($body));
        }

        Zend_Registry::get('response')->setResponse(200, 'Quarantined message ' . $params['id'] . ' data', $data);
        return true;
    }
}
