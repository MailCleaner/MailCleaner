<?php

/**
 * @license http://www.mailcleaner.net/open/licence_en.html Mailcleaner Public License
 * @package mailcleaner
 * @author Olivier Diserens, John Mertz
 * @copyright 2006, Olivier Diserens; 2023, John Mertz
 *
 * This is the controller page that will redirect to the spool page of the correct host
 */

/**
 * require administrative access
 */
require_once('admin_objects.php');
require_once('system/Soaper.php');
require_once('system/Slave.php');

global $sysconf_;
global $lang_;

// check parameters and set values
if (!isset($_GET['h']) || !is_numeric($_GET['h'])) {
    $error = "BADARGS";
} else {
    $spool = $_GET['s'];
    // connect to slave
    $host = $sysconf_->getSlaveName($_GET['h']);
    $soaper = new Soaper();
    $ret = $soaper->load($host);
    if ($ret != "OK") {
        $error = "CANNOTCONNECTTOSLAVE";
    }

    // authenticate admin
    $sid = $soaper->authenticateAdmin();
    if (preg_match('/^[A-Z]+$/', $sid)) {
        $error = $sid;
    }

    // redirect to the correct host
    $query = http_build_query(['s' => $spool, 'sid' => $sid]);
    if ($host == '127.0.0.1' || $host == 'localhost') {
        header("Location: spool.php?" . $query);
    } else {
        $hostip = gethostbyname($host);
        $proto = 'http';
        if (isset($_SERVER['HTTPS'])) {
            $proto = "https";
        }
        header("Location: $proto://$hostip/admin/spool.php?" . $query);
    }
}
