<?php

$username = 'testuser2@fastnet.local';
$pass = "test";

$key = "test";

$cipher = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
$iv = 'mailclea';
mcrypt_generic_init($cipher, $key, $iv);
$encrypted_password = mcrypt_generic($cipher, $pass);
echo "pass: " . $encrypted_password . "<br>";
$id = base64_encode($username . "=" . $encrypted_password);
$url = "http://mcintra/login.php?id=" . urlencode($id) . "&lang=fr";

echo "<a href=\"$url\">login</a>";
