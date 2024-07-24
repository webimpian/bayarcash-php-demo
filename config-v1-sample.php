<?php

$config['base_url'] = 'https://php.bayarcash-demo.com/v1/';
$config['return_url'] = $config['base_url'].'return.php';
$config['environment'] = 'production';
$config['bayarcash_create_transaction_api_url'] = [
	'sandbox' => 'https://console.bayarcash-sandbox.com/transactions/add',
	'production' => 'https://console.bayar.cash/transactions/add',
];
$config['bayarcash_requery_transaction_status_api_url'] = [
	'sandbox' => 'https://console.bayarcash-sandbox.com/api/transactions/?RefNo=',
	'production' => 'https://console.bayar.cash/api/transactions/?RefNo=',
];

// Bayarcash portal key & PAT
$config['bayarcash_portal_key'] = '<Portal_Key>';
$config['bayarcash_bearer_token'] = '<PAT_Token>';

// Database
$config['bayarcash_db_host'] = 'localhost';
$config['bayarcash_db_dbname'] = 'database_name';
$config['bayarcash_db_username'] = 'database_username';
$config['bayarcash_db_password'] = 'database_password';