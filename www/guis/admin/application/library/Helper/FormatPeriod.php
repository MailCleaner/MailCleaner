<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 *
 * Setup base view variables
 */

class MailCleaner_View_Helper_FormatPeriod extends Zend_View_Helper_Abstract
{

    protected $_params = [
        'periods' => [
            's' => ['second', 'seconds'],
            'm' => ['minute', 'minutes'],
            'h' => ['hour', 'hours'],
            'd' => ['day', 'days'],
            'w' => ['week', 'weeks']
        ]
    ];

    public function formatPeriod($period = '', $params = [])
    {
        $t = Zend_Registry::get('translate');

        foreach ($params as $k => $v) {
            $this->_params[$k] = $v;
        }

        $string = $period;
        foreach ($this->_params['periods'] as $p => $v) {
            if (preg_match('/(\d+)' . $p . '/', $period, $matches)) {
                $name = $v[0];
                if ($matches[1] > 1) {
                    $name = $v[1];
                }
                $string = preg_replace('/' . $p . '/', ' ' . $t->_($name), $string);
            }
        }
        return $string;
    }
}
