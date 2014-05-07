<?php

// -------------------------------------------
// Step 2 - Process response from CORE
// -------------------------------------------

require('config.php');

// include composer autoloader
require('vendor/autoload.php');
define('USE_EXT', 'GMP');

// Get Token from core. DO THIS WITH PROPER SECURITY WHEN DEVELOPING REAL APPLICATIONS
$token = preg_replace("/[^A-Za-z0-9]/", '', $_GET['token']);
if (empty($token)) {
    die('missing token');
}

try {
    // API Class Setup
    $api = new Brave\API($cfg_endpoint, $cfg_app_id, $cfg_privkey, $cfg_pubkey);

    // Hit /info method and get data from token
    $result = $api->core->info(array('token' => $token));
} catch (Exception $e) {
    // fatal error
    die('internal core error, retry.');
}

var_export($result);

$charid = $result->character->id;
$charname = $result->character->name;

$corpid = $result->corporation->id;
$corpname = $result->corporation->name;

$allianceid = $result->alliance->id;
$alliancename = $result->alliance->name;

$tags = $result->tags;

// Make sure to save the token and setup a session to identify that user
?>
