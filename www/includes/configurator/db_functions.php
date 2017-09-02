<?php

// Check and initialize db connection
function getDb($database, $user, $pass, $master=true) {
    try {
	$master_port='3306';
	$slave_port='3307';
	$port = $master == true ? 'port='.$master_port.';' : 'port='.$slave_port.';';
	$dbname = isset($database) && !empty($database) ? "dbname=".$database.";" : "";
        $db = new PDO('mysql:host=127.0.0.1;'.$port.$dbname.'charset=utf8', $user, $pass);
    }
    catch (Exception $e) {
	return false;
    }
    return $db;
}

?>
