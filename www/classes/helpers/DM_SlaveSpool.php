<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */
 
/**
 * this is a DataManager instance
 */
require_once ('helpers/DataManager.php');

/**
 * connect to the slave spool database
 */
class DM_SlaveSpool extends DataManager {

    private static $instance;

    public function __construct() {
        parent :: __construct();
        
        $socket = $this->getConfig('VARDIR')."/run/mysql_slave/mysqld.sock";
        $this->setOption('SOCKET', $socket);
        $this->setOption('DATABASE', 'mc_spool');
    }

    public static function getInstance() {
        if (empty (self :: $instance)) {
            self :: $instance = new DM_SlaveSpool();
        }
        return self :: $instance;
    }

}
?>