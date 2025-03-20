<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 */

/**
 * this file manages soap authorization
 * this is a basic scheme based on allowed ip addresses
 */

global $server;

/**
 * this is the timeout in seconds
 * @var numeric
 */
$soapsession_timeout = 60 * 10;

/**
 * will set an authorized session if coming from an authorized host
 * @param  $username  string  username used fo the request
 * @param  $usertype  string  type of user request (admin or user)
 * @param  $hostname  string  host name of the requesting client host
 * @return            string  session id if successful, error message otherwise
 */
function setAuthenticated($username, $usertype, $hostname)
{
    global $soapsession_timeout;
    $sysconf_ = SystemConfig::getInstance();

    $remote_ip = $_SERVER['REMOTE_ADDR'];
    $res = "remote = $remote_ip";
    $allowed_hosts = $sysconf_->getMastersName();
    $allowed = false;
    // check if requesting host is allowed
    foreach ($allowed_hosts as $host) {

        $ip = gethostbyname($host);
        $res .=  " | testing: $ip";
        if ($remote_ip == $ip || $remote_ip == gethostbyaddr($ip)) {
            $allowed = true;
        }
    }
    if (!$allowed) return "NOTALLOWED ($res)";


    // required here for sanity checks
    require_once('helpers/DM_SlaveSpool.php');
    $db_slavespool = DM_SlaveSpool::getInstance();
    if (!$db_slavespool instanceof DM_SlaveSpool) {
        return "ERRORWITHDBCONNECTOR";
    }

    // set session id
    $id = md5(uniqid(rand()));
    $clean_sql['username'] = $db_slavespool->sanitize($username);
    if ($usertype != 'admin') {
        $usertype = 'user';
    }
    $clean_sql['hostname'] = $db_slavespool->sanitize($hostname);
    $query = "INSERT INTO soap_auth SET id='$id', time=NOW(), user='" . $clean_sql['username'] . "', user_type='$usertype', host='" . $clean_sql['hostname'] . "'";

    if (!$db_slavespool->doExecute($query)) {
        return 'ERRORWHILESETTINGSESSION';
    }

    // purge old sessions
    $query = "DELETE FROM soap_auth WHERE CAST(UNIX_TIMESTAMP(NOW()) AS SIGNED) - CAST(UNIX_TIMESTAMP(time) AS SIGNED) >= $soapsession_timeout";
    $db_slavespool->doExecute($query);

    return $id;
}

/**
 * get the administrator object for the soap session
 * @param  $sid  string          session id used to validate the soap session
 * @return       Administrator  admin object on success, null or error message on failure
 */
function getAdmin($sid)
{
    global $soapsession_timeout;

    require_once("config/Administrator.php");
    $sysconf_ = SystemConfig::getInstance();

    // required here for sanity checks
    require_once('helpers/DM_SlaveSpool.php');
    $db_slavespool = DM_SlaveSpool::getInstance();
    if (!$db_slavespool instanceof DM_SlaveSpool) {
        return "ERRORWITHDBCONNECTOR";
    }
    $clean_sid = $db_slavespool->sanitize($sid);

    // fetch session datas in database
    $query = "SELECT user FROM soap_auth WHERE id='$clean_sid' AND (CAST(UNIX_TIMESTAMP(NOW()) AS SIGNED) - CAST(UNIX_TIMESTAMP(time) AS SIGNED) < $soapsession_timeout) AND user_type='admin'";
    $res = $db_slavespool->getHash($query);
    if (!is_array($res) || empty($res)) {
        return "NOSUCHADMIN ($clean_sid)";
    }
    $username = $res['user'];
    if (!isset($username) || $username == "") {
        return "BADADMINNAME ($username)";
    }

    // and finally instantiate the Administrator object
    $admin = new Administrator();
    if (!$admin->load($username)) {
        return "CANNOTLOADADMIN ($username)";
    }
    return $admin;
}

/**
 * fetch the username of the administrator user of the session
 * @param $sid  string  session id used to validate the soap session
 * @return      string  administrator user name on success, empty string on failure
 */
function getAdminName($sid)
{
    require_once("config/Administrator.php");
    $admin_ = getAdmin($sid);
    if (isset($admin_) && $admin_ instanceof Administrator) {
        return $admin_->getPref('username');
    }
    return "";
}

/**
 * check if user/administrator has a valid session
 * @param  $sid  string  session id used to validate the soap session
 * @param  $type string  admin/user type
 * @return       bool    true on success, false on failure
 */
function checkAuthenticated($sid, $type)
{
    if ($type == "admin") {
        require_once("config/Administrator.php");
        $admin_ = getAdmin($sid);
        if (isset($admin_) && $admin_ instanceof Administrator) {
            return true;
        }
    }
    return false;
}
