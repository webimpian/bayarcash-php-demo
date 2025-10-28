<?php

$config = require_once '../config-v2.php';
$config['environment'] = 'dev';
$current_config = getConfig($config, $config['environment']);
$config['return_url'] = $config['base_url'] . 'return_dev.php';

if (!empty($_POST['override_bearer_token'])) {
    $current_config['bayarcash_bearer_token'] = $_POST['override_bearer_token'];
}

if (!empty($_POST['override_portal_key'])) {
    $current_config['bayarcash_portal_key'] = $_POST['override_portal_key'];
}

$api_endpoint = 'https://console.bayarcash.dev/api/v2/payment-intents';
$mandates_endpoint = 'https://console.bayarcash.dev/api/v2/mandates';
$portals_endpoint = 'https://console.bayarcash.dev/api/v2/portals';
$manual_transfer_endpoint = 'https://console.bayarcash.dev/api/manual-bank-transfer';
$api_version = 'v2';

$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
    2 => 'Manual Bank Transfer',
    4 => 'FPX Line of Credit (Credit Card)',
    5 => 'DuitNow Online Banking/Wallets',
    6 => 'DuitNow QR',
    7 => 'SPayLater (BNPL from Shopee)',
    8 => 'Boost PayFlex (BNPL from Boost)',
    9 => 'QRIS Indonesia Online Banking',
    10 => 'QRIS Indonesia eWallet',
    11 => 'NETS Singapore',
    12 => 'Credit Card',
    13 => 'Alipay',
    14 => 'WeChat Pay',
    15 => 'PromptPay',
    16 => 'Touch n Go',
    17 => 'Boost Wallet',
    18 => 'GrabPay',
    19 => 'GrabPL',
    20 => 'ShopBack',
    21 => 'Shopee Pay',
    22 => 'JCB'
];

$emandate_option = ['emandate' => 'eMandate Enrollment (Direct Debit)'];

$merchant_banks = [
    'Affin Bank Berhad' => ['account' => '11212005986', 'holder' => 'Web Impian Sdn. Bhd.'],
    'Maybank' => ['account' => '114161439608', 'holder' => 'Web Impian Sdn. Bhd.'],
    'CIMB Bank' => ['account' => '8600123456', 'holder' => 'Web Impian Sdn. Bhd.']
];

$error_message = '';
$errors = [];
$merchant_info = null;

function logRawResponse($response, $request_data = null, $endpoint = null, $status_code = null) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'status_code' => $status_code,
        'request_data' => $request_data,
        'response' => $response
    ];

    file_put_contents($log_dir . '/payment_intent_raw.log', json_encode($log_entry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

function logError($message, $context = []) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];

    file_put_contents($log_dir . '/error.log', json_encode($log_entry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

function generateRandomName(): string {
    $firstNames = ['Ahmad', 'Mohd', 'Mohammed', 'Muhammad', 'Nur', 'Nurul', 'Siti', 'Amir', 'Aina', 'Azizah', 'Fatimah', 'Ismail', 'Zulkifli', 'Noraini', 'Hashim', 'Zainab'];
    $lastNames = ['Abdullah', 'Rahman', 'Hassan', 'Ibrahim', 'Othman', 'Ismail', 'Yusof', 'Ahmad', 'Ali', 'Aziz', 'Hamzah', 'Zainal', 'Razak', 'Kadir', 'Mahmud', 'Mustafa'];

    return $firstNames[array_rand($firstNames)] . ' bin ' . $lastNames[array_rand($lastNames)];
}

function generateRandomEmail($name, $maxLength = null): string {
    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
    $domain = $domains[array_rand($domains)];
    $username = implode('.', explode(' ', strtolower($name)));

    if ($maxLength !== null) {
        $domainLength = strlen($domain) + 1;
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
    }

    return $username . mt_rand(1, 999) . '@' . $domain;
}

function generateRandomMyKadNumber(): string {
    $year = mt_rand(70, 99);
    $month = str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
    $placeCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
    $placeCode = $placeCodes[array_rand($placeCodes)];
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
        'payer_id_type' => 1,
        'payer_id' => generateRandomMyKadNumber(),
        'frequency_mode' => 'MT',
    ];
}

function generateRandomProofOfPayment(): string {
    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0755, true);
    }

    $filename = 'proof_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.png';
    $filepath = $uploads_dir . '/' . $filename;

    $image = imagecreate(400, 200);
    imagecolorallocate($image, 255, 255, 255);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    imagestring($image, 5, 50, 90, 'Proof of Payment - ' . date('Y-m-d H:i:s'), $textColor);
    imagepng($image, $filepath);
    imagedestroy($image);

    logError('Generated proof of payment file', ['filename' => $filename, 'filepath' => $filepath]);

    return $filepath;
}

function makeApiRequest($url, $data = null, $custom_bearer_token = null): array {
    global $current_config;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
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
        logError('CURL Error', ['url' => $url, 'error' => $curl_error, 'request_data' => $data]);
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
        return ['success' => true, 'merchant' => $portals_result['response']['meta']['merchant']];
    }

    $error_msg = 'Unable to fetch merchant information';
    if (isset($portals_result['response']['message'])) {
        $error_msg = $portals_result['response']['message'];
    } elseif (!empty($portals_result['error'])) {
        $error_msg = 'API Communication Error: ' . $portals_result['error'];
    } else {
        $error_msg .= ' (Status code: ' . $portals_result['status_code'] . ')';
    }

    logError('Merchant Info Fetch Failed', [
        'status_code' => $portals_result['status_code'],
        'response' => $portals_result['response'],
        'error' => $portals_result['error'],
        'custom_bearer_token_used' => !empty($custom_bearer_token)
    ]);

    return ['success' => false, 'message' => $error_msg];
}

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

function buildEMandateRequest($form_data): array {
    global $current_config, $config;

    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $order_data['application_reason'] = $form_data['application_reason'] ?? ('Enrollment for ' . $order_data['order_no']);
    $order_data['buyer_email'] = validateEmailForEMandate($order_data['buyer_email'], $order_data['buyer_name'], $order_data['order_no']);

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

    return [
        'payment_channel' => (int)$form_data['payment_method'],
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

    logError('eMandate Request Initiated', [
        'order_no' => $request_data['order_number'],
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

    logError('Payment Intent Request Initiated', [
        'order_no' => $request_data['order_number'],
        'payment_channel' => $request_data['payment_channel'],
        'amount' => $request_data['amount'],
        'endpoint' => $api_endpoint,
        'request_data' => $request_data
    ]);

    return makeApiRequest($api_endpoint, $request_data);
}

function processManualBankTransfer($form_data): array {
    global $manual_transfer_endpoint, $current_config, $config, $merchant_banks;

    $bank_name = array_key_first($merchant_banks);
    $bank_info = $merchant_banks[$bank_name];
    $defaults = generateDefaultOrderData();
    $order_data = array_merge($defaults, $form_data);
    $transfer_date = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $proof_file = generateRandomProofOfPayment();

    $raw_website = [
        'currency' => 'MYR',
        'total_discount' => '0.00',
        'total_tax' => '0.00',
        'total' => $order_data['order_amount'],
        'items' => [[
            'description' => $order_data['order_description'] ?? 'Development Test Order',
            'quantity' => 1,
            'total' => $order_data['order_amount']
        ]]
    ];

    $request_data = [
        'portal_key' => $current_config['bayarcash_portal_key'],
        'buyer_name' => $order_data['buyer_name'],
        'buyer_email' => $order_data['buyer_email'],
        'buyer_tel_no' => $order_data['buyer_tel'],
        'order_amount' => $order_data['order_amount'],
        'order_no' => $order_data['order_no'],
        'return_url' => $config['return_url'],
        'payment_gateway' => 2,
        'merchant_bank_name' => $bank_name,
        'merchant_bank_account' => $bank_info['account'],
        'merchant_bank_account_holder' => $bank_info['holder'],
        'bank_transfer_type' => 'Online Transfer',
        'bank_transfer_date' => $transfer_date,
        'bank_transfer_notes' => 'Payment for order ' . $order_data['order_no'],
        'raw_website' => json_encode($raw_website)
    ];

    logError('Manual Bank Transfer Request Initiated', [
        'order_no' => $order_data['order_no'],
        'amount' => $order_data['order_amount'],
        'bank' => $bank_name,
        'endpoint' => $manual_transfer_endpoint,
        'proof_file' => $proof_file,
        'request_data' => $request_data
    ]);

    $ch = curl_init($manual_transfer_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    $post_fields = $request_data;
    if (file_exists($proof_file)) {
        $post_fields['proof_of_payment'] = new CURLFile($proof_file, 'image/png', basename($proof_file));
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $current_config['bayarcash_bearer_token']
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    logRawResponse($response, $request_data, $manual_transfer_endpoint, $http_code);

    if (!empty($curl_error)) {
        logError('Manual Transfer CURL Error', ['url' => $manual_transfer_endpoint, 'error' => $curl_error, 'request_data' => $request_data]);
    }

    $decoded_response = strpos($response, '<form') !== false
        ? ['html_form' => $response, 'is_html' => true]
        : json_decode($response, true);

    if (file_exists($proof_file)) {
        unlink($proof_file);
        logError('Cleaned up proof of payment file', ['filepath' => $proof_file]);
    }

    return [
        'status_code' => $http_code,
        'response' => $decoded_response,
        'error' => $curl_error,
        'raw_response' => $response
    ];
}

function parseApiErrors($api_result): array {
    $errors = [];

    if (isset($api_result['response']['errors']) && is_array($api_result['response']['errors'])) {
        foreach ($api_result['response']['errors'] as $field => $error_msgs) {
            if (is_array($error_msgs)) {
                foreach ($error_msgs as $msg) {
                    $errors[] = "$field: " . (is_string($msg) ? $msg : json_encode($msg));
                }
            } else {
                $errors[] = "$field: $error_msgs";
            }
        }
    } elseif (isset($api_result['response']['error'])) {
        if (is_array($api_result['response']['error'])) {
            $first_key = array_key_first($api_result['response']['error']);
            $is_field_based = !is_numeric($first_key) && is_array($api_result['response']['error'][$first_key]);

            if ($is_field_based) {
                foreach ($api_result['response']['error'] as $field => $error_msgs) {
                    if (is_array($error_msgs)) {
                        foreach ($error_msgs as $msg) {
                            $errors[] = "$field: " . (is_string($msg) ? $msg : json_encode($msg));
                        }
                    } else {
                        $errors[] = "$field: $error_msgs";
                    }
                }
            } else {
                foreach ($api_result['response']['error'] as $key => $error) {
                    if (is_array($error)) {
                        $errors[] = is_numeric($key) ? json_encode($error) : "$key: " . json_encode($error);
                    } else {
                        $errors[] = is_numeric($key) ? (string)$error : "$key: $error";
                    }
                }
            }
        } else {
            $errors[] = (string)$api_result['response']['error'];
        }
    }

    return $errors;
}

function generateErrorMessage($api_result): string {
    if (isset($api_result['response']['message'])) {
        return is_string($api_result['response']['message'])
            ? $api_result['response']['message']
            : json_encode($api_result['response']['message']);
    }

    if (isset($api_result['response']['error'])) {
        if (is_array($api_result['response']['error'])) {
            $first_key = array_key_first($api_result['response']['error']);
            $is_field_based = !is_numeric($first_key) && is_array($api_result['response']['error'][$first_key]);

            if ($is_field_based) {
                $all_messages = [];
                foreach ($api_result['response']['error'] as $field => $messages) {
                    if (is_array($messages)) {
                        foreach ($messages as $msg) {
                            $all_messages[] = is_string($msg) ? $msg : json_encode($msg);
                        }
                    } else {
                        $all_messages[] = (string)$messages;
                    }
                }
                return implode(' ', $all_messages);
            }

            $errors = [];
            foreach ($api_result['response']['error'] as $error) {
                if (is_array($error)) {
                    foreach ($error as $msg) {
                        $errors[] = is_string($msg) ? $msg : json_encode($msg);
                    }
                } else {
                    $errors[] = (string)$error;
                }
            }
            return implode(' ', $errors);
        }

        return (string)$api_result['response']['error'];
    }

    if (!empty($api_result['error'])) {
        return 'API Communication Error: ' . $api_result['error'];
    }

    return 'Unknown error occurred. Status code: ' . $api_result['status_code'];
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
        logError('Payment/Mandate Failed', array_merge($log_data, [
            'expected_status' => 201,
            'actual_status' => $api_result['status_code'],
            'api_response' => $api_result['response'],
            'api_error' => $api_result['error'],
            'raw_response' => $api_result['raw_response'],
            'parsed_errors' => parseApiErrors($api_result)
        ]));
    }
}

// AJAX: Fetch merchant info
if (isset($_POST['action']) && $_POST['action'] === 'fetch_merchant_info') {
    header('Content-Type: application/json');
    $custom_bearer_token = !empty($_POST['bearer_token']) ? $_POST['bearer_token'] : null;
    echo json_encode(fetchMerchantInfo($custom_bearer_token));
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
    $order_amount = $_POST['order_amount'] ?? $order_amount;
    $buyer_name = $_POST['buyer_name'] ?? $buyer_name;
    $buyer_email = $_POST['buyer_email'] ?? $buyer_email;
    $buyer_tel = $_POST['buyer_tel'] ?? $buyer_tel;
    $order_description = $_POST['order_description'] ?? $order_description;

    if (!validateAmount($order_amount)) {
        $error_message = 'Please enter a valid amount greater than 0';
        logError('Invalid Amount', ['submitted_amount' => $order_amount, 'payment_method' => $payment_method]);
    } else {
        try {
            if ($payment_method === 'emandate') {
                $api_result = processEMandateEnrollment($_POST);
                $expected_status_code = 201;
            } elseif ($payment_method == 2) {
                $api_result = processManualBankTransfer($_POST);
                $expected_status_code = 200;

                if ($api_result['status_code'] == $expected_status_code && isset($api_result['response']['is_html']) && $api_result['response']['is_html']) {
                    logPaymentResult($api_result, $_POST, $payment_method, true);
                    echo $api_result['response']['html_form'];
                    exit();
                }
            } else {
                $api_result = processPaymentIntent($_POST);
                $expected_status_code = 201;
            }

            if ($api_result['status_code'] == $expected_status_code && isset($api_result['response']['url'])) {
                logPaymentResult($api_result, $_POST, $payment_method, true);
                header("Location: " . $api_result['response']['url']);
                exit();
            }

            logPaymentResult($api_result, $_POST, $payment_method, false);
            $error_message = generateErrorMessage($api_result);
            $errors = parseApiErrors($api_result);
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
