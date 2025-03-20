<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Setup base view variables
 */

class MailCleaner_View_Helper_FormatEmail extends Zend_View_Helper_Abstract
{

    protected $_params = [
        'localpart_length' => 20,
        'domainpart_length' => 20,
        'extra_replace_string' => '...',
        'glue_string' => ', ',
        'global_length' => 100,
        'max_addresses' => 20,
    ];

    public function formatEmail($string = '', $params = [])
    {
        $t = Zend_Registry::get('translate');

        $tmpparams = $this->_params;
        foreach ($params as $k => $v) {
            $tmpparams[$k] = $v;
        }

        if (is_array($string)) {
            $emails = $string;
        } else {
            $emails = preg_split('/[,:; ]/', $string);
        }
        $cleans = [];
        foreach ($emails as $email) {
            $email = preg_replace('/\s+/', '', $email);
            if (preg_match('/^([^@]+)@(\S+)/', $email, $matches)) {
                if (strlen($matches[1]) > $tmpparams['localpart_length'] && $tmpparams['localpart_length'] > 0) {
                    $email = substr($matches[1], 0, $tmpparams['localpart_length']) . $tmpparams['extra_replace_string'];
                } else {
                    $email = $matches[1];
                }
                $email .= '@';
                if (strlen($matches[2]) > $tmpparams['domainpart_length'] && $tmpparams['domainpart_length'] > 0) {
                    $email .= substr($matches[2], 0, $tmpparams['domainpart_length']) . $tmpparams['extra_replace_string'];
                } else {
                    $email .= $matches[2];
                }
            }
            array_push($cleans, $email);
        }
        if (count($cleans) > $tmpparams['max_addresses'] && $tmpparams['max_addresses'] > 0) {
            $cleans = array_splice($cleans, 0, $tmpparams['max_addresses']);
            array_push($cleans, $tmpparams['extra_replace_string']);
        }
        $full = implode($tmpparams['glue_string'], $cleans);
        if (strlen($full) > $tmpparams['global_length'] && $tmpparams['global_length'] > 0) {
            $full = substr($full, 0, $tmpparams['global_length']) . $tmpparams['extra_replace_string'];
        }
        return $full;
    }
}
