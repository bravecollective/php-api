<?php

// ----------------------------------------
// Step 1 - Create the authorization request
// ----------------------------------------

require('config.php');

// include composer autoloader
require('vendor/autoload.php');
define('USE_EXT', 'GMP');

try {
    // API Class Setup
    $api = new Brave\API($cfg_endpoint, $cfg_app_id, $cfg_privkey, $cfg_pubkey);

    // API Call Args
    $info_data = array(
	'success' => $cfg_url_success,
	'failure' => $cfg_url_failure
    );
    $result = $api->core->authorize($info_data);
} catch (Exception $e) {
    // fatal error
    die('internal core error, retry.');
}

// Redirect back to the auth platform for user authentication approval
header("Location: ".$result->location);

// Once CORE auth comes back, we continue on step2.php file
?>
