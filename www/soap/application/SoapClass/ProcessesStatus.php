<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

class SoapClass_ProcessusStatus
{
    public $proc1 = 0;
    public $proc2 = 1;

    public function getSoapedValue()
    {
        return ['proc1' => $this->proc1, 'proc2' => $this->proc2];
    }
}

