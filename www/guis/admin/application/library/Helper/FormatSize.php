<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Setup base view variables
 */

class MailCleaner_View_Helper_FormatSize extends Zend_View_Helper_Abstract
{

    protected $_params = [
        'sizes' => [
            'T' => 'TB',
            'G' => 'GB',
            'M' => 'MB',
            'K' => 'KB'
        ]
    ];

    public function formatSize($string = '', $params = [])
    {
        $t = Zend_Registry::get('translate');

        foreach ($params as $k => $v) {
            $this->_params[$k] = $v;
        }

        foreach ($this->_params['sizes'] as $s => $v) {
            if (preg_match('/(\d+)' . $s . '/', $string, $matches)) {
                $name = $v;
                $string = preg_replace('/' . $s . '/', ' ' . $t->_($name), $string);
            }
        }
        return $string;
    }
}
