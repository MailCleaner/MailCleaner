<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2009, Olivier Diserens; 2023, John Mertz
 */

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, [
    realpath(APPLICATION_PATH . '/../application/'),
    realpath(APPLICATION_PATH . '/../../guis/admin/application/models'),
    realpath(APPLICATION_PATH . '/../../guis/admin/application/library'),
    get_include_path(),
]));

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');

if (!$argv[1]) {
    echo 'NOK no service given';
    exit;
}

include('MCSoap/Services.php');
$res = MCSoap_Services::$argv[1]();
echo $res;
