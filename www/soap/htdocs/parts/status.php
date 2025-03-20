<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * main status fetcher function
 * will execute the get_status.pl script with authorization checks
 * @param  $sid     string  session id
 * @param  $params  string  parameters passed to the get_status.pl script
 * @return          mixed   return values
 */
function getStatus($sid, $params)
{
    $admin_ = getAdmin($sid);
    if (!isset($admin_) || !$admin_ instanceof Administrator) {
        return "NOTAUTHENTICATED ($admin_)";
    }
    if (!$admin_->hasPerm(['can_view_stats'])) {
        return "NOTALLOWED";
    }

    $sysconf_ = SystemConfig::getInstance();

    $cmd = $sysconf_->SRCDIR_ . "/bin/get_status.pl $params";
    $res_a = [];
    exec($cmd, $res_a);

    return $res_a[0];
}

/**
 * return the actual messages count of each mailcleaner spool
 * @param  $sid  string  soap session id
 * @return       array   spools counts as array (key is spool name, value is message count)
 */
function getSpools($sid)
{
    $ret = getStatus($sid, "-p");
    if (!preg_match('/^(\|\d+){3}$/', $ret)) {
        return "ERRORFETCHINGSPOOLSSTATUS ($ret)";
    }
    list($tmp, $stage1, $stage2, $stage4) = preg_split('/\|/', $ret);
    $soap_res = new SoapSpools($stage1, $stage2, $stage4);
    return $soap_res;
}

/**
 * return the actual system load
 * @param  $sid  string  soap session id
 * @return       array   actual load values as array (key is time offsets, value is load)
 */
function getLoad($sid)
{
    $ret = getStatus($sid, "-l");
    if (!preg_match('/^(\|[\d\.]+){3}$/', $ret)) {
        return "ERRORFETCHINGLOADSTATUS";
    }
    list($tmp, $av5, $av10, $av15) = preg_split('/\|/', $ret);
    $soap_res = new SoapLoad($av5, $av10, $av15);
    return $soap_res;
}

/**
 * return the actual usage of both / and /var partitions
 * @param  $sid  string  soap session id
 * @return       array   actual partitions usage as array (key is mount point, value is usage in percent)
 */
function getDiskUsage($sid)
{
    $ret = getStatus($sid, "-d");
    $matches = [];
    if (!preg_match('/^\|\/\|([\d\.]+)\%\|.*\/var\|([\d\.]+)\%\|?/', $ret, $matches)) {
        return "ERRORFETCHINGDISKUSAGESTATUS";
    }
    $soap_res = new SoapDiskUsage($matches[1], $matches[2]);
    return $soap_res;
}

/**
 * return the actual memory usage
 * @param  $sid  string  soap session id
 * @return       array   memory usage as array (key is memory type, value is usage)
 */
function getMemUsage($sid)
{
    $ret = getStatus($sid, "-m");
    if (!preg_match('/^(\|\d+){4}$/', $ret)) {
        return "ERRORFETCHINGMEMUSAGESTATUS";
    }
    list($tmp, $total, $free, $stotal, $sfree) = preg_split('/\|/', $ret);
    return new SoapMemoryUsage($total, $free, $stotal, $sfree);
}

/**
 * return how long as been the oldest message keep in queue
 * @param  $sid  string  soap session id
 * @return       string  time value
 */
function getQueueTime($sid)
{
    $ret = getStatus($sid, "-t");
    return $ret;
}

/**
 * return the last patch name of the system
 * @param  $sid  string  soap session id
 * @return       string  latest patch name
 */
function getLastPatch($sid)
{
    $ret = getStatus($sid, "-u");
    if (!preg_match('/^(\d+)$/', $ret) && $ret != "") {
        return "ERRORFETCHINGLASTPATCH ()";
    }
    return $ret;
}

/**
 * get the spam/message/virus/content/bytes/user/clean counts for today
 * @param  $sid  string  soap session id
 * @return       array   today's counts as array (key is counted value, value is actual count)
 */
function getTodaysCounts($sid)
{
    $admin_ = getAdmin($sid);
    if (!$admin_ || $admin_->getPref('username') == "") {
        if (isset($admin_)) {
            return $admin_;
        }
        return "NOTAUTHENTICATED";
    }
    if (!$admin_->hasPerm(['can_view_stats'])) {
        return "NOTALLOWED";
    }
    $sysconf_ = SystemConfig::getInstance();

    $cmd = $sysconf_->SRCDIR_ . "/bin/get_today_stats.pl -A";
    $res_a = [];
    exec($cmd, $res_a);

    $res = [];
    if (!preg_match('/^([\d\.]+)([\|\d\.]+){10}$/', $res_a[0], $res)) {
        return "ERRORFETCHINGTODAYSCOUNTS " . $res[0];
    }
    list($bytes, $msg, $spam, $pspam, $virus, $pvirus, $content, $pcontent, $user, $clean, $pclean) = preg_split('/\|/', $res_a[0]);
    //return new SoapStats(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    return new SoapStats($bytes, $msg, $spam, $pspam, $virus, $pvirus, $content, $pcontent, $user, $clean, $pclean);
}

/**
 * get the spam/message/virus/content/bytes/user/clean counts for system/domain/user for a given period
 * @param   $what   string  _global, domain name or user email address
 * @param   $start  string  begin date, can be YYYYMMDD or -X where X is a number of day
 * @param   $stop   string  end date, can be YYYYMMDD or +X where X is a number of day
 * @return          array   counts as array (key is counted value, value is actual count)
 */
function getStats($what, $start, $stop)
{

    $sysconf_ = SystemConfig::getInstance();

    $cmd = $sysconf_->SRCDIR_ . "/bin/get_stats.pl $what $start $stop";
    $res_a = [];
    exec($cmd, $res_a);

    $res = [];
    if (!isset($res_a[0])) {
        return "ERRORFETCHINGCOUNTS NODATA";
    } elseif (!preg_match('/^([\d\.]+)([\|\d\.]+){10}$/', $res_a[0], $res)) {
        return "ERRORFETCHINGCOUNTS (" . $res[0] . ")";
    }
    list($msg, $spam, $highspam, $virus, $names, $others, $cleans, $bytes, $users, $domains) = preg_split('/\|/', $res_a[0]);
    return new SoapStats($bytes, $msg, $spam, 0, $virus, 0, $names + $others, 0, $users, $cleans, 0);
}
