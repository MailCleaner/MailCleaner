<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller page that will display the mrtg graphics
 */

/**
 * requires admin, session and view
 */
require_once("admin_objects.php");
require_once('view/Language.php');
require_once('config/Administrator.php');
require_once('view/Template.php');
require_once('view/Form.php');
require_once("view/Documentor.php");

/**
 * session globals
 */
global $lang_;
global $sysconf_;

// create the view objects and get form results
$form = new Form('filter', 'post', $_SERVER['PHP_SELF']);
$posted = $form->getResult();
if (!isset($posted['times'])) {
    $posted['times'] = 'day';
}
if (!isset($posted['stats'])) {
    $posted['stats'] = 'all';
}

//create view
$template_ = new Template('stats.tmpl');
$documentor = new Documentor();

// prepare the select fields information
$hosts = $sysconf_->getSlavesName();
$hosts[$lang_->print_txt('ALL')] = 'all';
$stats_ = [
    $lang_->print_txt('SPAMSMESSAGES') => 'messages',
    $lang_->print_txt('SPAMSPERCENTS') => 'pmessages',
    $lang_->print_txt('SPOOLSDESC') => 'spools',
    $lang_->print_txt('CPU') => 'cpu',
    $lang_->print_txt('LOAD') => 'load',
    $lang_->print_txt('MEMORY') => 'memory',
    $lang_->print_txt('NET') => 'network',
    $lang_->print_txt('DISK') => 'disk',
    $lang_->print_txt('ALL') => 'all',
];
if (!isset($posted['stats'])) {
    $posted['stats'] = 'all';
}
$times_ = [
    $lang_->print_txt('DAY') => 'day',
    $lang_->print_txt('WEEK') => 'week',
    $lang_->print_txt('MONTH') => 'month',
    $lang_->print_txt('YEAR') => 'year',
    $lang_->print_txt('ALL') => 'all',
];

// and output the page
$replace = [
    '__DOC_STATSTITLE__' => $documentor->help_button('STATSTITLE'),
    '__LANG__' => $lang_->getLanguage(),
    '__ERROR__' => $lang_->print_txt($error),
    '__MESSAGE__' => $lang_->print_txt($message),
    "__FORM_BEGIN_FILTER__" => $form->open(),
    "__FORM_CLOSE_FILTER__" => $form->close(),
    "__HOSTLIST__" => $form->select('host', $hosts, $posted['host'], ''),
    "__STATSLIST__" => $form->select('stats', $stats_, $posted['stats'], ''),
    "__PERIODLIST__" => $form->select('times', $times_, $posted['times'], ''),
    "__REFRESH_BUTTON__" => $form->submit('submit', $lang_->print_txt('REFRESH'), ''),
    "__HOSTLIST_DRAW__" => drawHosts($template_, $posted),
];

$template_->output($replace);

/**
 * draw a host block
 * @param  $template   array  template to be used
 * @param  $posted     array  values posted from the form
 * @return             string html string displaying the host block
 */
function drawHosts($template, $posted)
{
    global $sysconf_;
    global $lang_;

    if (isset($hosts)) {
        unset($hosts);
    }
    if (!isset($posted['host'])) {
        $hosts_arr = $sysconf_->getSlavesName();
        $hosts[current($hosts_arr)] = current($hosts_arr);
    } else {
        $hosts[$posted['host']] = $posted['host'];
    }
    if ($posted['host'] == "all") {
        $hosts = $sysconf_->getSlavesName();
    }

    $ret = "";
    $matches = [];
    foreach ($hosts as $key => $val) {
        $t = $template->getTemplate('HOST');
        if (preg_match('/\_\_LANG\_([A-Z0-9]+)\_\_/', $t, $matches)) {
            $t = preg_replace('/\_\_LANG\_([A-Z0-9]+)\_\_/', $lang_->print_txt($matches[1]), $t);
        }
        $t = str_replace("__HOSTNAME__", $key, $t);
        $t = str_replace("__STAT__", drawStat($template, $posted, $val), $t);
        $ret .= $t;
    }
    return $ret;
}

/**
 * draw the needed statistic type for a host
 * @param  $template   array  template to be used
 * @param  $posted     array  values posted from the form
 * @param  $host       string host to be processed
 * @return             string html string displaying the stats block
 */
function drawStat($template, $posted, $host)
{
    global $stats_;
    global $documentor;

    if (isset($stats)) {
        unset($stats);
    }
    if (!isset($posted['stats']) || $posted['stats'] == 'all') {
        $stats = $stats_;
    } else {
        $key = array_search($posted['stats'], $stats_);
        $stats[$key] = $posted['stats'];
    }
    $ret = "";

    $matches = [];
    foreach ($stats as $key => $val) {
        if ($val == 'all') {
            continue;
        }
        $t = $template->getTemplate('STAT');
        if (preg_match('/\_\_LANG\_([A-Z0-9]+)\_\_/', $t, $matches)) {
            $t = preg_replace('/\_\_LANG\_([A-Z0-9]+)\_\_/', $lang_->print_txt($matches[1]), $t);
        }
        $t = str_replace("__STATNAME__", $key, $t);
        $t = str_replace("__HELPPERIOD__", $documentor->help_button(strtoupper($val)), $t);
        $t = str_replace("__PERIODS__", drawPeriod($template, $posted, $host, $val), $t);
        $ret .= $t;
    }

    return $ret;
}

/**
 * draw the needed statistic periods
 * @param  $template   array  template to be used
 * @param  $posted     array  values posted from the form
 * @param  $host       string host to be processed
 * @param  $stat       string statistic type to be displayed
 * @return             string html string displaying the stats block
 */
function drawPeriod($template, $posted, $host, $stat)
{
    global $times_;

    if (isset($times)) {
        unset($times);
    }
    if (!isset($posted['times']) || $posted['times'] == 'all') {
        $times = $times_;
    } else {
        $times[$posted['times']] = $posted['times'];
    }
    $ret = "";
    $matches = [];
    foreach ($times as $key => $val) {
        if ($val == 'all') {
            continue;
        }
        $t = $template->getTemplate('PERIODS');
        if (preg_match('/\_\_LANG\_([A-Z0-9]+)\_\_/', $t, $matches)) {
            $t = preg_replace('/\_\_LANG\_([A-Z0-9]+)\_\_/', $lang_->print_txt($matches[1]), $t);
        }
        $t = str_replace("__PERIODNAME__", $key, $t);
        $t = str_replace("__IMAGE__", "/mrtg/" . $host . "/" . $stat . "-" . $val . "-full.png", $t);
        //$t = str_replace("__IMAGEFULL__", "/mrtg/".$host."/".$stat."-".$val."-full.png", $t);
        $ret .= $t;
    }
    return $ret;
}
