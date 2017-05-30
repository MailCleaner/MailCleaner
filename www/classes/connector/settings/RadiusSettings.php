<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class takes care of storing Radius settings
 * @package mailcleaner
 */
 class RadiusSettings extends ConnectorSettings {
   
   /**
    * template tag
    * @var string
    */
   protected $template_tag_ = 'RADIUSAUTH';
   
   /**
   * Specialized settings array with default values
   * @var array
   */
   protected $spec_settings_ = array(
                              'secret' => '',
                              'authtype'   => 'PAP'
                             );
                             
   /**
    * fields type
    * @var array
    */
   protected $spec_settings_type_ = array(
                              'secret' => array('text', 20),
                              'authtype' => array('select', array('PAP' => 'PAP', 'CHAP_MD5' => 'CHAP_MD5', 'MSCHAPv1' => 'MSCHAPv1', 'MSCHAPv2' => 'MSCHAPv2'))
                              );                          

   public function __construct($type) {
      parent::__construct($type);
      $this->setSetting('server', 'localhost');
      $this->setSetting('port', '1645');
   }   
 }
?>
