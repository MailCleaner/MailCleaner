<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */


/**
 * This class takes care of storing Radius settings
 * @package mailcleaner
 */
class RadiusSettings extends ConnectorSettings
{

    /**
     * template tag
     * @var string
     */
    protected $template_tag_ = 'RADIUSAUTH';

    /**
     * Specialized settings array with default values
     * @var array
     */
    protected $spec_settings_ = [
        'secret' => '',
        'authtype'   => 'PAP'
    ];

    /**
     * fields type
     * @var array
     */
    protected $spec_settings_type_ = [
        'secret' => ['text', 20],
        'authtype' => ['select', ['PAP' => 'PAP', 'CHAP_MD5' => 'CHAP_MD5', 'MSCHAPv1' => 'MSCHAPv1', 'MSCHAPv2' => 'MSCHAPv2']]
    ];

    public function __construct($type)
    {
        parent::__construct($type);
        $this->setSetting('server', 'localhost');
        $this->setSetting('port', '1645');
    }
}
