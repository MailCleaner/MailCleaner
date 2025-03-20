<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller for the dynamic statistics display
 */

/**
 * require admin session and view
 */
require_once("admin_objects.php");
require_once("system/SystemConfig.php");
require_once("system/Slave.php");
require_once("view/Template.php");
require_once("system/SoapTypes.php");

/**
 * session globals
 */
global $admin_;
global $sysconf_;
global $lang_;

// not allowed if we are not a master
if ($sysconf_->ismaster_ < 1) {
    exit;
}
// check authorizations
$admin_->checkPermissions(['can_view_stats']);

// initial values
$gstatus = 1; // 1 for ok, 0 for critical
$gload = 0;
$gspools = ['in' => 0, 'fi' => 0, 'out' => 0];
$gcounts = ['msgs' => 0, 'spams' => 0, 'viruses' => 0];

// get and check each slaves
$slaves = $sysconf_->getSlaves();
foreach ($slaves as $slave) {
    // first decrease soap timeout
    $slave->setSoapTimeout(5);
    if (!$slave->isAvailable()) {
        $gstatus = 0;
        continue;
    }

    // get processes status
    $status = $slave->getProcessesStatus();
    if (!SoapProcesses::isOK($status)) {
        $gstatus = 0;
    }

    // get load values
    $load = $slave->getLoads();
    if ($load->avg15 > $gload) {
        $gload = $load->avg15;
    }

    // get spools
    $spools = $slave->getSpoolsCount();
    if ($spools->incoming > $gspools['in']) {
        $gspools['in'] = $spools->incoming_;
    }
    if ($spools->filtering > $gspools['fi']) {
        $gspools['fi'] = $spools->filtering_;
    }
    if ($spools->outgoing > $gspools['out']) {
        $gspools['out'] = $spools->outgoing_;
    }

    // get counts
    $counts = $slave->getTodaysCounts();
    $gcounts['msgs'] += $counts->msg;
    $gcounts['spams'] += $counts->spam;
    $gcounts['viruses'] += $counts->virus;
}

// create view
$template_ = new Template('dyn_stats.tmpl');

// prepare replacements
$replace = [
    "__SYSTEM_STATUS__" => getStatus($gstatus),
    "__SYSTEM_LOAD__" => getLoad($gload),
    "__SPOOLSTATUS__" => getSpools($gspools),
    "__NB_MESSAGES__" => $gcounts['msgs'],
    "__NB_SPAMS__" => $gcounts['spams'],
    "__NB_VIRUSES__" => $gcounts['viruses'],
    "__SERVER_SELF__" => $_SERVER['PHP_SELF']
];

// output page
$template_->output($replace);

/**
 * return the processes status string
 * @param  $status  numeric  status of the processes
 * @return          string   html status string
 */
function getStatus($status)
{
    if ($status == 0) {
        return "<font color=\"#FF0000\">CRITICAL</font>";
    }
    return "<font color=\"#00AA00\">OK</font>";
}

/**
 * return the load status
 * @param  $status  numeric  max load
 * @return          string   html status string
 */
function getLoad($gload)
{
    $medium = 7;
    $high = 20;
    if ($gload > $high) {
        return "<font color=\"#FF0000\">HIGH</font>";
    }
    if ($gload > $medium) {
        return "<font color=\"#BBBB00\">MEDIUM</font>";
    }
    return "<font color=\"#00AA00\">LOW</font>";
}

/**
 * return the spools status
 * @param  $status  array    spools status
 * @return          string   html status string
 */
function getSpools($gspool)
{
    $medium = ['in' => 1000, 'fi' => '1000', 'out' => 1000];
    $high = ['in' => 2000, 'fi' => '3000', 'out' => 5000];
    foreach ($gspool as $stage => $value) {
        if ($value > $high[$stage]) {
            return "<font color=\"#FF0000\">HIGH</font>";
        }
        if ($value > $medium[$stage]) {
            return "<font color=\"#BBBB00\">MEDIUM</font>";
        }
    }
    return "<font color=\"#00AA00\">LOW</font>";
}
