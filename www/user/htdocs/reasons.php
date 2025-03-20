<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the reasons list display page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

require_once('variables.php');
require_once("view/Language.php");
require_once("user/ReasonSet.php");
require_once("view/Template.php");

$sysconf = SystemConfig::getInstance();

// get the reason set object
$rs_ = new ReasonSet();
if (!isset($_GET['id']) || !isset($_GET['a']) || !isset($_GET['s'])) {
    die("BADPARAMS");
}
$rs_->getReasons($_GET['id'], $_GET['a'], $sysconf->getSlaveName($_GET['s']));

// set defaults
$heightfactor = 24;
$heightlimit = 500;

// create view
$template_ = new Template('reasons.tmpl');
$heightfactor = $template_->getDefaultValue('heightfactor');
$heightlimit = $template_->getDefaultValue('heightlimit');

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

// prepare replacements
$replace = [
    '__HEIGHT__' => get_window_height($heightfactor, $heightlimit, $rs_->getNbReasons()),
    '__TOTAL_SCORE__' => round($rs_->getTotalScore(), 2),
    '__REASONS_LIST__' => $rs_->getHtmlList($template_->getTemplate('REASON')),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];
//display page
$template_->output($replace);

/**
 * calculate the window height corresponding of the number of criteria
 * @param $factor  numeric  factor corresponding of each line height
 * @param $limit   numeric  maximum height allowed
 * @param $n       numeric  number of lines
 * @return         numeric  height of window
 */
function get_window_height($factor, $limit, $n)
{
    $ret = $limit;
    if ($n < ($limit / $factor)) {
        $ret = $n * $factor;
    }
    return $ret;
}
