<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * we do set some global variables here
 */

// debug or not
ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 1);

// do the logging stuff as soon as possible
require_once('Log.php');

$MCLOGLEVEL = PEAR_LOG_WARNING;  // normal is: PEAR_LOG_WARNING or PEAR_LOG_INFO
require_once('helpers/DataManager.php');
require_once('system/SystemConfig.php');
$conf_ = DataManager::getFileConfig(SystemConfig::$CONFIGFILE_);
$log_ = Log::singleton('file', $conf_['VARDIR'] . "/log/apache/webgui.log", 'none', [], $MCLOGLEVEL);
global $log_;

## set the timezone
if (file_exists('/etc/timezone')) {
    $timezone = file_get_contents('/etc/timezone');
    if (is_string($timezone)) {
        date_default_timezone_set(preg_replace('/\s+/', '', $timezone));
    }
}
