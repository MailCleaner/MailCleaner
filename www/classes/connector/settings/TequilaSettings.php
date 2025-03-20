<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of storing Tequila settings
 * @package mailcleaner
 */
class TequilaSettings extends ConnectorSettings
{

    /**
     * template tag
     * @var string
     */
    protected $template_tag_ = 'TEQUILAAUTH';

    /**
     * Specialized settings array with default values
     * @var array
     */
    protected $spec_settings_ = [
        'usessl' => false,
        'fields' => '',
        'url' => '',
        'loginfield' => '',
        'realnameformat' => '',
        'allowsfilter' => ''
    ];

    /**
     * fields type
     * @var array
     */
    protected $spec_settings_type_ = [
        'usessl' => ['checkbox', 'true'],
        'url' => ['text', 20],
        'fields' => ['text', 30],
        'loginfield' => ['text', 20],
        'realnameformat' => ['text', 30],
        'allowsfilter' => ['text', 30]
    ];

    public function __construct($type)
    {
        parent::__construct($type);
        $this->setSetting('server', 'localhost');
        $this->setSetting('port', '80');
    }
}
