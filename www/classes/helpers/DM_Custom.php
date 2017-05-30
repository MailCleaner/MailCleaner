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
 * connect to a database with customs parameters
 */
class DM_Custom extends DataManager {

    private static $instance;

    public function __construct($host, $port, $username, $password, $database) {
        parent :: __construct();
        
        $this->setOption('HOST', $host);
        $this->setOption('PORT', $port);
        $this->setOption('USER', $username);
        $this->setOption('PASSWORD', $password);
        $this->setOption('DATABASE', $database);
    }

    public static function getInstance($host, $port, $username, $password, $database) {
        self :: $instance = new DM_Custom($host, $port, $username, $password, $database);
        return self :: $instance;
    }

}
?>
