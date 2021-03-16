<?
/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens
 * @copyright 2006, Olivier Diserens
 */

require_once('variables.php');
require_once('system/SoapTypes.php');
global $SoapClassMap;

/**
 * this file will create the soap server and call the different files for adding methods
 */

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
$server = new SoapServer("mailcleaner.wsdl", array('trace' => 1, 'exceptions' => 1, 'classmap' => $SoapClassMap));

require_once('parts/authorization.php');
require_once('parts/status.php');
require_once('parts/message.php');
require_once('parts/processes.php');

$server->addFunction(array(
                        "forceContent",
                        "addToNewslist",
                        "addToWhitelist",
                        "addToBlacklist",
                        "getHeaders",
                        "getMIMEPart",
                        "getBody",
                        "setAuthenticated",
                        "checkAuthenticated",
                        "getProcessesStatus",
                        "getSpools",
                        "getLoad",
                        "getDiskUsage",
                        "getMemUsage",
                        "getQueueTime",
                        "getLastPatch",
                        "getTodaysCounts",
                        "stopService",
                        "startService",
                        "restartService",
                        "getAdminName",
                        "forceSpam",
                        "getReasons",
                        "sendToAnalyse",
                        "dumpConfiguration",
                        "processNeedsRestart",
                        "setRestartStatus",
                        "getStats")
                      );

$server->handle();
?>
