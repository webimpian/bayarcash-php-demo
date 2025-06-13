<?php
$config = require_once '../config-v2.php';

$config['environment'] = 'dev';

$current_config = getConfig($config, $config['environment']);

// Check for browser storage overrides from POST data
if (isset($_POST['override_bearer_token']) && !empty($_POST['override_bearer_token'])) {
    $current_config['bayarcash_bearer_token'] = $_POST['override_bearer_token'];
}

if (isset($_POST['override_portal_key']) && !empty($_POST['override_portal_key'])) {
    $current_config['bayarcash_portal_key'] = $_POST['override_portal_key'];
}

$api_endpoint = 'https://console.bayarcash.dev/api/v2/payment-intents';
$portals_endpoint = 'https://console.bayarcash.dev/api/v2/portals';
$api_version = 'v2';

$error_message = '';
$errors = [];
$merchant_info = null;

$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
//    2 => 'Manual Bank Transfer',
//    3 => 'Direct Debit via FPX',
    4 => 'FPX Line of Credit (Credit Card)',
    5 => 'DuitNow Online Banking/Wallets',
    6 => 'DuitNow QR',
//    7 => 'SPayLater (BNPL from Shopee)',
//    8 => 'Boost PayFlex (BNPL from Boost)',
    9 => 'QRIS Indonesia Online Banking',
    10 => 'QRIS Indonesia eWallet',
    11 => 'NETS Singapore'
];

function generateRandomName(): string
{
    $firstNames = [
        'Ahmad', 'Mohd', 'Mohammed', 'Muhammad', 'Nur', 'Nurul', 'Siti', 'Amir',
        'Aina', 'Azizah', 'Fatimah', 'Ismail', 'Zulkifli', 'Noraini', 'Hashim', 'Zainab'
    ];

    $lastNames = [
        'Abdullah', 'Rahman', 'Hassan', 'Ibrahim', 'Othman', 'Ismail', 'Yusof', 'Ahmad',
        'Ali', 'Aziz', 'Hamzah', 'Zainal', 'Razak', 'Kadir', 'Mahmud', 'Mustafa'
    ];

    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];

    return $firstName . ' bin ' . $lastName;
}

function generateRandomEmail($name): string
{
    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
    $domain = $domains[array_rand($domains)];

    $nameParts = explode(' ', strtolower($name));
    $username = implode('.', $nameParts);

    $randomNumber = mt_rand(1, 999);

    return $username . $randomNumber . '@' . $domain;
}

function makeApiRequest($url, $data = null, $custom_bearer_token = null): array
{
    global $current_config;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    // Use custom bearer token if provided, otherwise use current config
    $bearer_token = $custom_bearer_token ?? $current_config['bayarcash_bearer_token'];
    if ($bearer_token) {
        $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'status_code' => $http_code,
        'response' => json_decode($response, true),
        'error' => $curl_error
    ];
}

// Handle AJAX request for merchant info
if (isset($_POST['action']) && $_POST['action'] === 'fetch_merchant_info') {
    header('Content-Type: application/json');

    $custom_bearer_token = isset($_POST['bearer_token']) && !empty($_POST['bearer_token'])
        ? $_POST['bearer_token']
        : null;

    $portals_result = makeApiRequest($portals_endpoint, null, $custom_bearer_token);

    if ($portals_result['status_code'] == 200 && isset($portals_result['response']['meta']['merchant'])) {
        echo json_encode([
            'success' => true,
            'merchant' => $portals_result['response']['meta']['merchant']
        ]);
    } else {
        $error_msg = 'Unable to fetch merchant information';
        if (isset($portals_result['response']['message'])) {
            $error_msg = $portals_result['response']['message'];
        } elseif (!empty($portals_result['error'])) {
            $error_msg = 'API Communication Error: ' . $portals_result['error'];
        } else {
            $error_msg .= ' (Status code: ' . $portals_result['status_code'] . ')';
        }

        echo json_encode([
            'success' => false,
            'message' => $error_msg
        ]);
    }
    exit();
}

$order_no = 'DEV' . str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT);
$order_amount = '1.00';
$buyer_name = generateRandomName();
$buyer_email = generateRandomEmail($buyer_name);
$buyer_tel = '0196788044';
$order_description = 'Development Test Order';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $payment_channel = $_POST['payment_gateway'];

    $order_amount = $_POST['order_amount'] ?? $order_amount;
    $buyer_name = $_POST['buyer_name'] ?? $buyer_name;
    $buyer_email = $_POST['buyer_email'] ?? $buyer_email;
    $buyer_tel = $_POST['buyer_tel'] ?? $buyer_tel;
    $order_description = $_POST['order_description'] ?? $order_description;

    if (empty($order_amount) || !is_numeric($order_amount) || (float)$order_amount <= 0) {
        $error_message = 'Please enter a valid amount greater than 0';
    } else {
        try {
            $request_data = [
                'payment_channel' => (int)$payment_channel,
                'portal_key' => $current_config['bayarcash_portal_key'],
                'order_number' => $order_no,
                'amount' => (float)$order_amount,
                'payer_name' => $buyer_name,
                'payer_email' => $buyer_email,
                'payer_telephone_number' => $buyer_tel,
                'return_url' => $config['return_url']
            ];

            $api_result = makeApiRequest($api_endpoint, $request_data);

            if ($api_result['status_code'] == 201 && isset($api_result['response']['url'])) {
                header("Location: " . $api_result['response']['url']);
                exit();
            } else {
                if (isset($api_result['response']['message'])) {
                    $error_message = $api_result['response']['message'];
                } else if (!empty($api_result['error'])) {
                    $error_message = 'API Communication Error: ' . $api_result['error'];
                } else {
                    $error_message = 'Unknown error occurred. Status code: ' . $api_result['status_code'];
                }

                if (isset($api_result['response']['errors']) && is_array($api_result['response']['errors'])) {
                    $errors = [];
                    foreach ($api_result['response']['errors'] as $field => $error_msgs) {
                        if (is_array($error_msgs)) {
                            foreach ($error_msgs as $msg) {
                                $errors[] = "$field: $msg";
                            }
                        } else {
                            $errors[] = "$field: $error_msgs";
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
            $errors = [];
        }
    }
}

include 'checkout_dev.php';