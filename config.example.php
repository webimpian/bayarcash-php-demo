<?php

$config['base_url'] = 'https://bayarcash-php-demo.test/';
$config['return_url'] = $config['base_url'].'return.php';
$config['bayarcash_base_url'] = 'https://console.bayar.cash/';
$config['bayarcash_api_url'] = $config['bayarcash_base_url'].'transactions/add';
$config['bayarcash_FPX_portal_key'] = 'XXX'; // Change this portal key with yours. Login to Bayarcash console > Portal.
$config['bayarcash_FPX_bearer_token'] = 'XXX'; // Change this token PAT with yours from email.

$config['bayarcash_db_host'] = 'localhost';
$config['bayarcash_db_dbname'] = 'bayarcashdemo';
$config['bayarcash_db_username'] = 'root';
$config['bayarcash_db_password'] = '';
