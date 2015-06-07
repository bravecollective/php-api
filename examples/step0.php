<?php

// ------------------------------------------
// Step 0 - Create the application keys
// ------------------------------------------

// include composer autoloader
require('../vendor/autoload.php');

use \Mdanter\Ecc\EccFactory;

$generator = EccFactory::getNistCurves()->generator256();
$private = $generator->createPrivateKey();
$public = $private->getPublicKey();
$adaptor = EccFactory::getAdapter();
$public_point = $public->getPoint();

// hexlify the integers
$private_hex = $adaptor->decHex($private->getSecret());
$public_hex = $adaptor->decHex($public_point->getX()).$adaptor->decHex($public_point->getY());

// keys!
$keys = [
	'public' => $public_hex,
	'private' => $private_hex,
];

var_dump($keys);

// Once we have EC Keys, we continue on step1.php file and fill in the data in config.php