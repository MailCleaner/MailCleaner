<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class takes care of storing Tequily settings
 * @package mailcleaner
 */
 class TequilaSettings extends ConnectorSettings {
   
   /**
    * template tag
    * @var string
    */
   protected $template_tag_ = 'TEQUILAAUTH';
   
   /**
   * Specialized settings array with default values
   * @var array
   */
   protected $spec_settings_ = array(
                              'usessl' => false,  
                              'fields' => '',
                              'url' => '',
                              'loginfield' => '',
                              'realnameformat' => '',
                              'allowsfilter' => ''
                             );
                             
   /**
    * fields type
    * @var array
    */
   protected $spec_settings_type_ = array(
                              'usessl' => array('checkbox', 'true'),
                              'url' => array('text', 20),
                              'fields' => array('text', 30),
                              'loginfield' => array('text', 20),
                              'realnameformat' => array('text', 30),
                              'allowsfilter' => array('text', 30)
                             );                          

   public function __construct($type) {
      parent::__construct($type);
      $this->setSetting('server', 'localhost');
      $this->setSetting('port', '80');
   }   
 }
?>
