# How to use PHP-API in your projects

## 1. Install GMP for php

```bash
sudo apt-get install php5-gmp
```

OR

```bash
sudo yum install php5-gmp
```

## 2. Restart php-fpm or apache
```bash
$ sudo /etc/init.d/php-fpm restart
```

OR

```bash
$ sudo /etc/init.d/php5-fpm restart
```

OR

```bash
$ sudo /etc/init.d/apache2 restart
```

## 3. Install composer

```bash    
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 4. Setup skeleton composer.json file
```json
{
    "name": "yourproject/name",
    "description": "this is a test",
    "require": {
            "bravecollective/php-api": "1.0.*"
    }
}
```

## 5. Install dependencies
```bash
composer install
```

## 6. Configuration

Copy config.php into your project and edit it with your keys and other information

## 7. Use php-api

```php
<?php
require 'vendor/autoload.php';
require 'config.php';

// create API instance
$api = new Brave\API($cfg_endpoint, $cfg_app_id, $cfg_privkey, $cfg_pubkey);

// API Call Arguments
$info_data = array(
	'success' => $cfg_url_success,
	'failure' => $cfg_url_failure
);

// Make authorize call to validate a user with your app
$result = $api->core->authorize($info_data);

// Redirect back to the auth platform for user authentication approval
header("Location: ".$result->location);
```