<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
 
/**
 * This class takes care of storing settings of the local connector
 * @package mailcleaner
 */
 class SQLSettings extends ConnectorSettings {
   
   /**
    * template tag
    * @var string
    */
   protected $template_tag_ = 'SQLAUTH';
   
    /**
   * Specialized settings array with default values
   * @var array
   */
   protected $spec_settings_ = array(
                              'usessl' => false,
                              'database_type' => 'mysql',
                              'database' => 'mc_config',
                              'table' => 'mysql_auth',
                              'user' => 'mailcleaner',
                              'pass' => '',
                              'login_field' => 'username',
                              'password_field' => 'password',
                              'domain_field' => 'domain',
                              'email_field' => 'email',
                              'crypt_type' => 'crypt'
                             );
                             
   /**
    * fields type
    * @var array
    */
   protected $spec_settings_type_ = array(
                              'usessl' => array('checkbox', 'true'),
                              'database_type' => array('select', array('mysql' => 'mysql')),
                              'database' => array('text', 20),
                              'table' => array('text', 20),
                              'user' => array('text', 20),
                              'pass' => array('password', 20),
                              'login_field' => array('text', 20),
                              'password_field' => array('text', 20),
                              'domain_field' => array('text', 20),
                              'email_field' => array('text', 20),
                              'crypt_type' => array('select', array('crypt' => 'crypt'))
                               );                          
                             
   public function __construct($type) {
      parent::__construct($type);
      $sysconf_ = SystemConfig::getInstance();
      // default for local connector
      $this->setSetting('server', '127.0.0.1');
      $this->setSetting('port', '3306');
      $this->setSetting('user', $sysconf_->dbusername_);
      $this->setSetting('pass', $sysconf_->dbpassword_);
      $this->setSetting('database', $sysconf_->dbconfig_);
   }
   
   /**
    * Get the SQL connection DSN
    * @return   string  connection dsn
    */
   public function getDSN() {
     $dsn = $this->getSetting('database_type')."://";
     $dsn .= $this->getSetting('user').":".$this->getSetting('pass');
     $dsn .= "@".$this->getSetting('server').":".$this->getSetting('port');
     $dsn .= "/".$this->getSetting('database');

     return $dsn;
   }
   
   public function getTemplateCondition() {
      if ($this->getType() == 'local') {
        return 'LOCALAUTH';
      }
      return $this->template_tag_;
    }
 }
?>
