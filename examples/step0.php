<?php

// ------------------------------------------
// Step 0 - Create the authorization request
// ------------------------------------------

// include composer autoloader
require('vendor/autoload.php');
define('USE_EXT', 'GMP');

// Generate EC Keys

$ecdsa = new ECDSA();
$keys = $ecdsa->generateEccKeys();
var_dump($keys);

// Once we have EC Keys, we continue on step1.php file and fill in the data in keys.php
?>
