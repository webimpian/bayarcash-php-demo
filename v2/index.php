<?php
global $config;
require_once '../config-v2.php';
require_once 'vendor/autoload.php';

use Webimpian\BayarcashSdk\Bayarcash;
use Webimpian\BayarcashSdk\Exceptions\ValidationException;

// Get the current configuration based on the environment
$current_config = getConfig($config, $config['environment']);

// Initialize Bayarcash SDK
$bayarcash = new Bayarcash($current_config['bayarcash_bearer_token']);
$api_version = 'v3';
$bayarcash->setApiVersion($api_version);

if ($config['environment'] === 'sandbox') {
    $bayarcash->useSandbox();
}

// Initialize variables
$error_message = '';
$errors = [];

// Payment gateway options
$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
    2 => 'Manual Bank Transfer',
    3 => 'Direct Debit via FPX',
    4 => 'FPX Line of Credit (Credit Card)',
    5 => 'DuitNow Online Banking/Wallets',
    6 => 'DuitNow QR',
    7 => 'SPayLater (BNPL from Shopee)',
    8 => 'Boost PayFlex (BNPL from Boost)',
    9 => 'QRIS Indonesia Online Banking',
    10 => 'QRIS Indonesia eWallet',
    11 => 'NETS Singapore'
];

// Add merchant bank details
$merchant_banks = [
    'Affin Bank Berhad' => [
        'account' => '11212005986',
        'holder' => 'Web Impian Sdn. Bhd.'
    ],
    'Maybank' => [
        'account' => '114161439608',
        'holder' => 'Web Impian Sdn. Bhd.'
    ],
    'CIMB Bank' => [
        'account' => '8600123456',
        'holder' => 'Web Impian Sdn. Bhd.'
    ]
];

function logRawResponse($response) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/payment_intent_raw.log';
    $data = is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT);
    file_put_contents($log_file, $data . "\n", FILE_APPEND);
}

// Sample transaction details
$order_no = str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT);
$order_amount = '10';
$buyer_name = 'John Doe';
$buyer_email = 'john.doe@example.com';
$buyer_tel = '60123456789';
$order_description = 'Gelang Emas 916 - 2.36g';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_no = $_POST['order_no'] ?? $order_no;
    $order_amount = $_POST['order_amount'] ?? $order_amount;
    $buyer_name = $_POST['buyer_name'] ?? $buyer_name;
    $buyer_email = $_POST['buyer_email'] ?? $buyer_email;
    $buyer_tel = $_POST['buyer_tel'] ?? $buyer_tel;
    $order_description = $_POST['order_description'] ?? $order_description;
    $payment_channel = $_POST['payment_gateway'];

    try {
        if ($payment_channel == Bayarcash::MANUAL_TRANSFER) {
            // Define the selected merchant bank
            $bank_name = array_key_first($merchant_banks);
            $bank_info = $merchant_banks[$bank_name];
            $transfer_date = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Prepare raw_website data
            $raw_website = [
                'currency' => 'MYR',
                'total_discount' => '0.00',
                'total_tax' => '0.00',
                'total' => $order_amount,
                'items' => [
                    [
                        'description' => $order_description,
                        'quantity' => 1,
                        'total' => $order_amount
                    ]
                ]
            ];

            // Prepare data for manual bank transfer
            $manual_data = [
                'portal_key' => $current_config['bayarcash_portal_key'],
                'buyer_name' => $buyer_name,
                'buyer_email' => $buyer_email,
                'buyer_tel_no' => $buyer_tel,
                'order_amount' => $order_amount,
                'order_no' => $order_no,
                'return_url' => $config['return_url'],
                'payment_gateway' => $payment_channel,
                'merchant_bank_name' => $bank_name,
                'merchant_bank_account' => $bank_info['account'],
                'merchant_bank_account_holder' => $bank_info['holder'],
                'bank_transfer_type' => 'Online Transfer',
                'bank_transfer_date' => $transfer_date,
                'bank_transfer_notes' => 'Payment for order ' . $order_no,
                'raw_website' => json_encode($raw_website)
            ];

            $proof_file = __DIR__ . '/pngtree-navigation-bar-3d-search-url-png-image_6360655.png';
            if (!file_exists($proof_file)) {
                throw new Exception("Proof file not found");
            }
            $manual_data['proof_of_payment'] = $proof_file;

            logRawResponse("Manual bank transfer request: " . json_encode($manual_data));

            // Process the payment
            $response = $bayarcash->createManualBankTransfer($manual_data, true);
            logRawResponse("Response: " . (is_string($response) ? $response : json_encode($response)));

            if (is_array($response) && isset($response['html_form'])) {
                echo $response['html_form'];
                exit();
            }

            $error_message = 'No valid payment form received';
            logRawResponse("Error: " . $error_message);
        } else {
            // For other payment methods - use standard payment intent flow
            $data = [
                'portal_key' => $current_config['bayarcash_portal_key'],
                'order_number' => $order_no,
                'amount' => $order_amount,
                'description' => $order_description,
                'payer_name' => $buyer_name,
                'payer_email' => $buyer_email,
                'payer_telephone_number' => $buyer_tel,
                'callback_url' => $config['return_url'],
                'return_url' => $config['return_url'],
                'payment_channel' => $payment_channel,
                'fpx_buyer_bank_code' => '',
                'fpx_buyer_bank_name' => '',
                'metadata' => '',
            ];

            // Generate checksum
            $data['checksum'] = $bayarcash->createPaymentIntentChecksumValue(
                $current_config['bayarcash_api_secret_key'],
                $data
            );

            // Create payment intent
            $response = $bayarcash->createPaymentIntent($data);
            logRawResponse($response);

            if (isset($response->url)) {
                header("Location: " . $response->url);
                exit();
            } else {
                $error_message = 'Payment URL not received';
            }
        }
    } catch (ValidationException $exception) {
        $exceptionData = $exception->errors();
        $error_message = is_array($exceptionData['message'])
            ? implode(' ', $exceptionData['message'])
            : (string)$exceptionData['message'];

        $errors = [];
        if (isset($exceptionData['errors']) && is_array($exceptionData['errors'])) {
            foreach ($exceptionData['errors'] as $error) {
                $errors[] = is_array($error) ? implode(' ', $error) : (string)$error;
            }
        }

        logRawResponse($exceptionData);
    } catch (Exception $exception) {
        $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
        logRawResponse(['exception' => $exception->getMessage()]);
    }
}

// Include the view file
include 'checkout.php';