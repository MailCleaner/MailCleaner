<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this is a preference handler
 */
 require_once('helpers/PrefHandler.php');
 
/**
 * This class is only a settings wrapper for the antispam configurations
 */
class AntiSpam extends PrefHandler
{

  /**
   * antispam settings
   * @var array
   */
  private $pref_ = array(
	                   'use_spamassassin' => 1,
	                   'spamassassin_timeout' => 20,
	                   'use_bayes' => 1,
	                   'bayes_autolearn' => 1,
	                   'ok_locales' => 'fr en de it es',
	                   'use_rbls' => 1,
	                   'rbls_timeout' => 20,
	                   'use_dcc' => 1,
	                   'dcc_timeout' => 10,
	                   'use_razor' => 1,
	                   'razor_timeout' => 10,
	                   'use_pyzor' => 1,
	                   'pyzor_timeout' => 10,
                       'enable_whitelists' => 0,
                       'enable_warnlists' => 0,
		       'enable_blacklists' => 0,
                       'trusted_ips' => '',
                       'use_syslog' => 0,
                     );

/**
 * constructor
 */
public function __construct() {
    $this->addPrefSet('antispam', 'a', $this->pref_);
}

/**
 * load datas from database
 * @return         boolean  true on success, false on failure
 */
public function load() {
  return $this->loadPrefs('', '', false);
}

/**
 * save datas to database
 * @return    boolean  true on success, false on failure
 */
public function save() {
  global $sysconf_;
  $sysconf_ = SystemConfig::getInstance();
  $sysconf_->setProcessToBeRestarted('ENGINE');
  $ret = $this->savePrefs('', '', '');
  ## dump the configuration through all hosts
  return $sysconf_->dumpConfiguration('domains', '');
}
}
?>
