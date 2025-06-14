<?php

// ============================================================================
// CONFIGURATION AND INITIALIZATION
// ============================================================================

$config = require_once '../config-v2.php';
$config['environment'] = 'dev';
$current_config = getConfig($config, $config['environment']);

// Check for browser storage overrides from POST data
if (!empty($_POST['override_bearer_token'])) {
    $current_config['bayarcash_bearer_token'] = $_POST['override_bearer_token'];
}

if (!empty($_POST['override_portal_key'])) {
    $current_config['bayarcash_portal_key'] = $_POST['override_portal_key'];
}

// API Endpoints
$api_endpoint = 'https://console.bayarcash.dev/api/v2/payment-intents';
$mandates_endpoint = 'https://console.bayarcash.dev/api/v2/mandates';
$portals_endpoint = 'https://console.bayarcash.dev/api/v2/portals';
$api_version = 'v2';

// Payment Gateway Options
$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
    4 => 'FPX Line of Credit (Credit Card)',
    5 => 'DuitNow Online Banking/Wallets',
    6 => 'DuitNow QR',
    9 => 'QRIS Indonesia Online Banking',
    10 => 'QRIS Indonesia eWallet',
    11 => 'NETS Singapore'
];

// eMandate option - separate from payment gateways
$emandate_option = [
    'emandate' => 'eMandate Enrollment (Direct Debit)'
];

// Initialize variables
$error_message = '';
$errors = [];
$merchant_info = null;

// ============================================================================
// LOGGING FUNCTIONS
// ============================================================================

function logRawResponse($response, $request_data = null, $endpoint = null, $status_code = null) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/payment_intent_raw.log';

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'status_code' => $status_code,
        'request_data' => $request_data,
        'response' => $response
    ];

    file_put_contents($log_file, json_encode($log_entry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

function logError($message, $context = []) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/error.log';

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];

    file_put_contents($log_file, json_encode($log_entry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

// ============================================================================
// DATA GENERATION FUNCTIONS
// ============================================================================

function generateRandomName(): string {
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

function generateRandomEmail($name, $maxLength = null): string {
    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
    $domain = $domains[array_rand($domains)];

    $nameParts = explode(' ', strtolower($name));
    $username = implode('.', $nameParts);

    if ($maxLength !== null) {
        $domainLength = strlen($domain) + 1; // +1 for the @ symbol
        $availableUsernameLength = $maxLength - $domainLength;

        if (strlen($username) > $availableUsernameLength - 3) {
            $username = substr($username, 0, $availableUsernameLength - 3);
        }

        $randomNumber = mt_rand(10, 99);
        $email = $username . $randomNumber . '@' . $domain;

        if (strlen($email) > $maxLength) {
            $username = substr($username, 0, $maxLength - strlen($randomNumber . '@' . $domain));
            $email = $username . $randomNumber . '@' . $domain;
        }

        return $email;
    } else {
        $randomNumber = mt_rand(1, 999);
        return $username . $randomNumber . '@' . $domain;
    }
}

function generateRandomMyKadNumber(): string {
    // Generate random birth date (between 1970-2005 for adults)
    $year = mt_rand(70, 99);
    $month = str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT);

    // Generate random place of birth (common Malaysian state codes)
    $placeCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
    $placeCode = $placeCodes[array_rand($placeCodes)];

    // Generate random sequential number and gender digit
    $sequential = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    $gender = mt_rand(0, 9);

    return $year . $month . $day . $placeCode . $sequential . $gender;
}

function generateDefaultOrderData(): array {
    $buyer_name = generateRandomName();

    return [
        'order_no' => 'DEV' . str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT),
        'order_amount' => '1.00',
        'buyer_name' => $buyer_name,
        'buyer_email' => generateRandomEmail($buyer_name),
        'buyer_tel' => '0196788044',
        'order_description' => 'Development Test Order',
        'payer_id_type' => 1, // Always 1 for MyKad
        'payer_id' => generateRandomMyKadNumber(),
        'frequency_mode' => 'MT', // Monthly
    ];
}

// ============================================================================
// API COMMUNICATION FUNCTIONS
// ============================================================================

function makeApiRequest($url, $data = null, $custom_bearer_token = null): array {
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

    $decoded_response = json_decode($response, true);

    logRawResponse($decoded_response, $data, $url, $http_code);

    if (!empty($curl_error)) {
        logError('CURL Error', [
            'url' => $url,
            'error' => $curl_error,
            'request_data' => $data
        ]);
    }

    return [
        'status_code' => $http_code,
        'response' => $decoded_response,
        'error' => $curl_error,
        'raw_response' => $response
    ];
}

function fetchMerchantInfo($custom_bearer_token = null): array {
    global $portals_endpoint;

    $portals_result = makeApiRequest($portals_endpoint, null, $custom_bearer_token);

    if ($portals_result['status_code'] == 200 && isset($portals_result['response']['meta']['merchant'])) {
        return [
            'success' => true,
            'merchant' => $portals_result['response']['meta']['merchant']
        ];
    } else {
        $error_msg = 'Unable to fetch merchant information';
        if (isset($portals_result['response']['message'])) {
            $error_msg = $portals_result['response']['message'];
        } elseif (!empty($portals_result['error'])) {
            $error_msg = 'API Communication Error: ' . $portals_result['error'];
        } else {
            $error_msg .= ' (Status code: ' . $portals_result['status_code'] . ')';
        }

        // Log merchant info fetch error
        logError('Merchant Info Fetch Failed', [
            'status_code' => $portals_result['status_code'],
            'response' => $portals_result['response'],
            'error' => $portals_result['error'],
            'custom_bearer_token_used' => !empty($custom_bearer_token)
        ]);

        return [
            'success' => false,
            'message' => $error_msg
        ];
    }
}

// ============================================================================
// VALIDATION FUNCTIONS
// ============================================================================

function validateAmount($amount): bool {
    return !empty($amount) && is_numeric($amount) && (float)$amount > 0;
}

function validateEmailForEMandate($email, $buyer_name, $order_no): string {
    if (strlen($email) > 27) {
        $new_email = generateRandomEmail($buyer_name, 27);
        logError('Email too long for eMandate, generated new one', [
            'original_email' => $email,
            'new_email' => $new_email,
            'order_no' => $order_no
        ]);
        return $new_email;
    }
    return $email;
}

// ============================================================================
// PAYMENT PROCESSING FUNCTIONS
// ============================================================================

function buildEMandateRequest($form_data): array {
    global $current_config, $config;

    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $order_data['application_reason'] = $form_data['application_reason'] ?? ('Enrollment for ' . $order_data['order_no']);

    // Validate email length for eMandate
    $order_data['buyer_email'] = validateEmailForEMandate(
        $order_data['buyer_email'],
        $order_data['buyer_name'],
        $order_data['order_no']
    );

    return [
        'portal_key' => $current_config['bayarcash_portal_key'],
        'order_number' => $order_data['order_no'],
        'amount' => (float)$order_data['order_amount'],
        'payer_id_type' => (int)$order_data['payer_id_type'],
        'payer_id' => $order_data['payer_id'],
        'payer_name' => $order_data['buyer_name'],
        'payer_email' => $order_data['buyer_email'],
        'payer_telephone_number' => $order_data['buyer_tel'],
        'frequency_mode' => $order_data['frequency_mode'],
        'application_reason' => $order_data['application_reason'],
        'return_url' => $config['return_url'],
        'success_url' => $config['return_url'],
        'failed_url' => $config['return_url']
    ];
}

function buildPaymentIntentRequest($form_data): array {
    global $current_config, $config;

    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $payment_channel = (int)$form_data['payment_method'];

    return [
        'payment_channel' => $payment_channel,
        'portal_key' => $current_config['bayarcash_portal_key'],
        'order_number' => $order_data['order_no'],
        'amount' => (float)$order_data['order_amount'],
        'payer_name' => $order_data['buyer_name'],
        'payer_email' => $order_data['buyer_email'],
        'payer_telephone_number' => $order_data['buyer_tel'],
        'return_url' => $config['return_url']
    ];
}

function processEMandateEnrollment($form_data): array {
    global $mandates_endpoint;

    $request_data = buildEMandateRequest($form_data);
    $order_no = $request_data['order_number'];

    logError('eMandate Request Initiated', [
        'order_no' => $order_no,
        'amount' => $request_data['amount'],
        'endpoint' => $mandates_endpoint,
        'email_length' => strlen($request_data['payer_email']),
        'request_data' => $request_data
    ]);

    return makeApiRequest($mandates_endpoint, $request_data);
}

function processPaymentIntent($form_data): array {
    global $api_endpoint;

    $request_data = buildPaymentIntentRequest($form_data);
    $order_no = $request_data['order_number'];
    $payment_channel = $request_data['payment_channel'];

    logError('Payment Intent Request Initiated', [
        'order_no' => $order_no,
        'payment_channel' => $payment_channel,
        'amount' => $request_data['amount'],
        'endpoint' => $api_endpoint,
        'request_data' => $request_data
    ]);

    return makeApiRequest($api_endpoint, $request_data);
}

// ============================================================================
// ERROR HANDLING FUNCTIONS
// ============================================================================

function parseApiErrors($api_result): array {
    $errors = [];

    if (isset($api_result['response']['errors']) && is_array($api_result['response']['errors'])) {
        foreach ($api_result['response']['errors'] as $field => $error_msgs) {
            if (is_array($error_msgs)) {
                foreach ($error_msgs as $msg) {
                    $errors[] = "$field: $msg";
                }
            } else {
                $errors[] = "$field: $error_msgs";
            }
        }
    } else if (isset($api_result['response']['error']) && is_array($api_result['response']['error'])) {
        $errors = $api_result['response']['error'];
    }

    return $errors;
}

function generateErrorMessage($api_result): string {
    if (isset($api_result['response']['message'])) {
        return $api_result['response']['message'];
    } else if (isset($api_result['response']['error']) && is_array($api_result['response']['error'])) {
        return implode('; ', $api_result['response']['error']);
    } else if (isset($api_result['response']['error'])) {
        return $api_result['response']['error'];
    } else if (!empty($api_result['error'])) {
        return 'API Communication Error: ' . $api_result['error'];
    } else {
        return 'Unknown error occurred. Status code: ' . $api_result['status_code'];
    }
}

function logPaymentResult($api_result, $form_data, $payment_method, $success = false): void {
    $log_data = [
        'order_no' => $form_data['order_no'] ?? 'generated',
        'payment_method' => $payment_method,
        'status_code' => $api_result['status_code']
    ];

    if ($success) {
        $log_data['redirect_url'] = $api_result['response']['url'];
        logError('Payment/Mandate Success - Redirecting', $log_data);
    } else {
        $log_data = array_merge($log_data, [
            'expected_status' => 201,
            'actual_status' => $api_result['status_code'],
            'api_response' => $api_result['response'],
            'api_error' => $api_result['error'],
            'raw_response' => $api_result['raw_response'],
            'parsed_errors' => parseApiErrors($api_result)
        ]);
        logError('Payment/Mandate Failed', $log_data);
    }
}

// ============================================================================
// MAIN PROCESSING LOGIC
// ============================================================================

// Handle AJAX request for merchant info
if (isset($_POST['action']) && $_POST['action'] === 'fetch_merchant_info') {
    header('Content-Type: application/json');

    $custom_bearer_token = isset($_POST['bearer_token']) && !empty($_POST['bearer_token'])
        ? $_POST['bearer_token']
        : null;

    $result = fetchMerchantInfo($custom_bearer_token);
    echo json_encode($result);
    exit();
}

// Generate default order data
$default_data = generateDefaultOrderData();
$order_no = $default_data['order_no'];
$order_amount = $default_data['order_amount'];
$buyer_name = $default_data['buyer_name'];
$buyer_email = $default_data['buyer_email'];
$buyer_tel = $default_data['buyer_tel'];
$order_description = $default_data['order_description'];
$payer_id_type = $default_data['payer_id_type'];
$payer_id = $default_data['payer_id'];
$frequency_mode = $default_data['frequency_mode'];
$application_reason = 'Enrollment for ' . $order_no;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $payment_method = $_POST['payment_method'] ?? '';

    // Override default values with form data
    $order_amount = $_POST['order_amount'] ?? $order_amount;
    $buyer_name = $_POST['buyer_name'] ?? $buyer_name;
    $buyer_email = $_POST['buyer_email'] ?? $buyer_email;
    $buyer_tel = $_POST['buyer_tel'] ?? $buyer_tel;
    $order_description = $_POST['order_description'] ?? $order_description;

    // Validate amount
    if (!validateAmount($order_amount)) {
        $error_message = 'Please enter a valid amount greater than 0';
        logError('Invalid Amount', [
            'submitted_amount' => $order_amount,
            'payment_method' => $payment_method
        ]);
    } else {
        try {
            // Process payment based on method
            if ($payment_method === 'emandate') {
                $api_result = processEMandateEnrollment($_POST);
            } else {
                $api_result = processPaymentIntent($_POST);
            }

            $expected_status_code = 201;

            // Check if payment/mandate was successful
            if ($api_result['status_code'] == $expected_status_code && isset($api_result['response']['url'])) {
                logPaymentResult($api_result, $_POST, $payment_method, true);
                header("Location: " . $api_result['response']['url']);
                exit();
            } else {
                // Handle failure
                logPaymentResult($api_result, $_POST, $payment_method, false);

                $error_message = generateErrorMessage($api_result);
                $errors = parseApiErrors($api_result);
            }
        } catch (Exception $exception) {
            $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
            $errors = [];

            logError('Exception Occurred', [
                'order_no' => $order_no,
                'payment_method' => $payment_method,
                'exception_message' => $exception->getMessage(),
                'exception_trace' => $exception->getTraceAsString(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine()
            ]);
        }
    }
}

include 'checkout_dev.php';