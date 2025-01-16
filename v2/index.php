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
if ($config['environment'] === 'sandbox') {
    $bayarcash->useSandbox();
}

// Initialize variables
$error_message = '';  // Initialize as empty string
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

// Sample transaction details
$order_no = str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT);
$order_amount = '1';
$buyer_name = 'John Doe';
$buyer_email = 'john.doe@example.com';
$buyer_tel = '60123456789';
$order_description = 'Gelang Emas 916 - 2.36g';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_channel = $_POST['payment_gateway'];

    try {
        $data = [
            'portal_key' => $current_config['bayarcash_portal_key'],
            'order_number' => $order_no,
            'amount' => $order_amount,
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

        $checksumPayload = [
            'payment_channel' => $data['payment_channel'],
            'order_number' => $data['order_number'],
            'amount' => $data['amount'],
            'payer_name' => $data['payer_name'],
            'payer_email' => $data['payer_email'],
        ];

        $data['checksum'] = $bayarcash->createChecksumValue($current_config['bayarcash_api_secret_key'], $checksumPayload);

        $response = $bayarcash->createPaymentIntent($data);

        if ($response->url) {
            header("Location: " . $response->url);
            exit();
        } else {
            $error_message = 'Payment URL not received';
        }
    } catch (ValidationException $exception) {
        // Properly handle the validation exception
        $exceptionData = $exception->errors();
        // Convert array message to string if necessary
        if (is_array($exceptionData['message'])) {
            $error_message = implode(' ', $exceptionData['message']);
        } else {
            $error_message = (string)$exceptionData['message'];
        }
        // Ensure errors is an array of strings
        $errors = [];
        if (isset($exceptionData['errors']) && is_array($exceptionData['errors'])) {
            foreach ($exceptionData['errors'] as $error) {
                if (is_array($error)) {
                    $errors[] = implode(' ', $error);
                } else {
                    $errors[] = (string)$error;
                }
            }
        }
    } catch (Exception $exception) {
        $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
        $errors = [];
    }
}

// Include the view file
include 'checkout.php';
?>