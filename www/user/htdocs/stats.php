<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the statistics page
 */

if ($_SERVER["REQUEST_METHOD"] == "HEAD") {
    return 200;
}

/**
 * require valid session
 */
require_once("objects.php");
require_once("user/Statistics.php");
require_once("view/Template.php");
require_once("view/Form.php");
global $sysconf_;
global $lang_;
global $user_;

// some defaults
$select_wanted = 'ALL';
$wanted_addresses_ = $user_->getAddresses();
$wanted_stats_ = [];
$startdate = 'today';
$stopdate = 'today';
$period = $user_->getPref('gui_displayed_days');
$datetype = 'period';

// create view
$template_ = new Template('stats.tmpl');

$today = @getdate();
// get posted values
$form = new Form('filter', 'post', $_SERVER['PHP_SELF']);
$posted = $form->getResult();

// process input and set up $wanted_addresses_
if (isset($posted['a']) && $posted['a']) {
    $select_wanted = $posted['a'];
    if ($select_wanted != 'ALL') {
        $wanted_addresses_ = [$select_wanted];
    }
}
if (isset($posted['datetype']) && $posted['datetype'] == 'date') {
    $startdate = $posted['startyear'] . sprintf('%02d', $posted['startmonth']) . sprintf('%02d', $posted['startday']);
    $stopdate = $posted['stopyear'] . sprintf('%02d', $posted['stopmonth']) . sprintf('%02d', $posted['stopday']);
    $datetype = 'date';
} else {
    if (isset($posted['period']) && $posted['period']) {
        $period = $posted['period'];
    }
    $startdate = '-' . $period;
    $period = $period;
}

// create Statistics objects
$global_stat = new Statistics();
foreach ($wanted_addresses_ as $a) {
    $a = strtolower($a);
    $wanted_stats_[$a] = new Statistics();
    if (!$user_->hasAddress($a) || !$wanted_stats_[$a]->load($a, $startdate, $stopdate)) {
        continue;
    }
    $wanted_stats_[$a]->generateGraphs($template_);
    $global_stat->addStats($wanted_stats_[$a]->getStats());
}
$global_stat->setDate('start', $startdate);
$global_stat->setDate('stop', $stopdate);
$global_stat->generateGraphs($template_);

if (count($wanted_stats_) > 1) {
    $template_->setCondition('GLOBALSTAT', 1);
}

$addresses = $user_->getAddressesForSelect();
if (count($addresses) > 1) {
    $addresses[$lang_->print_txt('ALL')] = 'ALL';
}
$periods = [
    '1' => 1,
    '3' => 3,
    '7' => 7,
    '15' => 15,
    '30' => 30,
    '90' => 90,
    '180' => 180,
    '365' => 365
];
$days = [];
for ($i = 1; $i < 32; $i++) {
    $days[$i] = $i;
}
$months = [];
for ($i = 1; $i < 13; $i++) {
    $months[$lang_->print_txt('MONTHAB' . $i)] = $i;
}
$years = [];
for ($i = 2006; $i <= $today['year']; $i++) {
    $years[$i] = $i;
}

$startd = Statistics::getAnyDateAsArray($startdate);
$stopd = Statistics::getAnyDateAsArray($stopdate);
if (!isset($posted['datetype'])) {
    $posted['datetype'] = '';
}

// Registered?
require_once('helpers/DataManager.php');
$file_conf = DataManager::getFileConfig($sysconf_::$CONFIGFILE_);
$is_enterprise = 0;
if (isset($file_conf['REGISTERED']) && $file_conf['REGISTERED'] == '1') {
    $is_enterprise = 1;
}

$replace = [
    '__PRINT_USERNAME__' => $user_->getName(),
    '__LINK_LOGOUT__' => '/logout.php',
    '__BEGIN_FILTER_FORM__' => $form->open(),
    '__END_FILTER_FORM__' => $form->close(),
    '__ADDRESS_SELECTOR__' => $form->select('a', $addresses, $select_wanted, ""),

    '__INPUT_PERIODRADIO__' => $form->radiojs('datetype', 'period', $datetype, 'javascript:useDateSearchType(\'period\');'),
    '__INPUT_DATERADIO__' => $form->radiojs('datetype', 'date', $datetype, 'javascript:useDateSearchType(\'date\');'),
    '__INPUT_PERIODSELECT__' => $form->input('period', 5, $period),
    '__DATESEARCHTYPE__' => $posted['datetype'],

    '__INPUT_STARTDAY__' => $form->select('startday', $days, $startd['day'], ';'),
    '__INPUT_STARTMONTH__' => $form->select('startmonth', $months, $startd['month'], ';'),
    '__INPUT_STARTYEAR__' => $form->select('startyear', $years, $startd['year'], ';'),
    '__INPUT_STOPDAY__' => $form->select('stopday', $days, $stopd['day'], ';'),
    '__INPUT_STOPMONTH__' => $form->select('stopmonth', $months, $stopd['month'], ';'),
    '__INPUT_STOPYEAR__' => $form->select('stopyear', $years, $stopd['year'], ';'),
    '__REFRESH_BUTTON__' => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),
    '__DISPLAY_STATSLIST__' => displayStatsList(),
    '__DISPLAY_GLOBALSTAT__' => displayGlobalStats(),
    '__COPYRIGHTLINK__' => $is_enterprise ? "www.mailcleaner.net" : "www.mailcleaner.org",
    '__COPYRIGHTTEXT__' => $is_enterprise ? $lang_->print_txt('COPYRIGHTEE') : $lang_->print_txt('COPYRIGHTCE'),
];

// display page
$template_->output($replace);

function displayStatsList()
{
    global $template_;
    global $wanted_stats_;
    global $global_stat;

    $ret = "";
    foreach ($wanted_stats_ as $add => $stat) {
        $ret .= $stat->getStatInTemplate($template_, 'STAT');
    }
    return $ret;
}

function displayGlobalStats()
{
    global $template_;
    global $wanted_stats_;
    global $global_stat;

    if (count($wanted_stats_) < 2) {
        return "";
    }
    return $global_stat->getStatInTemplate($template_, 'GLOBALSTAT');
}
