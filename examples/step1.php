<?php

// ----------------------------------------
// Step 1 - Create the authorization request
// ----------------------------------------

// include composer autoloader
require('vendor/autoload.php');
define('USE_EXT', 'GMP');

// API Keys
$application_id = ''; //
$public_key = '';  //
$private_key = ''; //

// API Class Setup
$api = new Brave\API('https://core.bravecollective.net/api', $application_id, $private_key, $public_key);

// API Call Args
$info_data = array(
	'success' => 'SUCCESS_URL',
	'failure' => 'FAILURE_URL'
);
$result = $api->core->authorize($info_data);

// Redirect back to the auth platform for user authentication approval
header("Location: ".$result->location);

// Once CORE auth comes back, we continue on step2.php file