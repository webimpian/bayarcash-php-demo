<?php

// Base URL
$appConfig['base_url'] = 'https://your-domain.com/v2/';
$appConfig['return_url'] = $appConfig['base_url'].'return.php';
$appConfig['environment'] = 'sandbox'; // or 'production'

// Bayarcash portal key & PAT for production
$appConfig['production'] = [
    'bayarcash_portal_key' => 'your_production_portal_key',
    'bayarcash_bearer_token' => 'your_production_bearer_token',
    'bayarcash_api_secret_key' => 'your_production_secret_key'
];

// Bayarcash portal key & PAT for sandbox
$appConfig['sandbox'] = [
    'bayarcash_portal_key' => 'your_sandbox_portal_key',
    'bayarcash_bearer_token' => 'your_sandbox_bearer_token',
    'bayarcash_api_secret_key' => 'your_sandbox_secret_key'
];

// Database configuration
$appConfig['bayarcash_db_host'] = 'localhost';
$appConfig['bayarcash_db_dbname'] = 'your_database_name';
$appConfig['bayarcash_db_username'] = 'your_database_username';
$appConfig['bayarcash_db_password'] = 'your_database_password';

if (!function_exists('getConfig')) {
    function getConfig($config, $environment) {
        return $config[$environment];
    }
}

// Return the configuration array
return $appConfig;