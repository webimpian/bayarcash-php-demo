<?php

// Base URL
$config['base_url'] = 'https://your-domain.com/v2/';
$config['return_url'] = $config['base_url'].'return.php';
$config['environment'] = 'sandbox'; // or 'production'

// Bayarcash portal key & PAT for production
$config['production'] = [
    'bayarcash_portal_key' => 'your_production_portal_key',
    'bayarcash_bearer_token' => 'your_production_bearer_token',
    'bayarcash_api_secret_key' => 'your_production_secret_key'
];

// Bayarcash portal key & PAT for sandbox
$config['sandbox'] = [
    'bayarcash_portal_key' => 'your_sandbox_portal_key',
    'bayarcash_bearer_token' => 'your_sandbox_bearer_token',
    'bayarcash_api_secret_key' => 'your_sandbox_secret_key'
];

// Database configuration
$config['bayarcash_db_host'] = 'localhost';
$config['bayarcash_db_dbname'] = 'your_database_name';
$config['bayarcash_db_username'] = 'your_database_username';
$config['bayarcash_db_password'] = 'your_database_password';

if (!function_exists('getConfig')) {
    function getConfig($config, $environment) {
        return $config[$environment];
    }
}