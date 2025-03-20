<?php

require_once('variables.php');
require_once('admin_objects.php');

require_once('system/Soaper.php');

global $admin_;
global $sysconf_;

$soaper = new Soaper();
if (!$soaper instanceof Soaper) {
    die("cannot instantiate soaper !");
}
$ret = $soaper->load($sysconf_->getPref('hostid'));
if ($ret != 'OK') {
    die("cannot load soaper : $ret");
}
$sid = $soaper->authenticateAdmin();
if (strlen($sid) != 32) {
    die("session id not valid: $sid");
}

$spools = $soaper->client_->getSpools($sid);
echo "spools: ";
var_dump($spools);
echo "<br/>";

$headers = $soaper->client_->getHeaders($sid, '1FRBPw-0000q4-6G', 'olivier@cyco.ch');
echo "headers: ";
var_dump($headers);
echo "<br/>";

$body = $soaper->client_->getBody($sid, '1FRBPw-0000q4-6G', 'olivier@cyco.ch', 20);
echo "body: ";
var_dump($body);
echo "<br/>";

echo "<br/>OK, successful";
