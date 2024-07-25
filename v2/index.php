<?php
global $config;
require_once '../config-v2.php';
require_once 'vendor/autoload.php';

use Webimpian\BayarcashSdk\Bayarcash;
use Webimpian\BayarcashSdk\Exceptions\ValidationException;

// Initialize Bayarcash SDK
$bayarcash = new Bayarcash($config['bayarcash_bearer_token']);
if ($config['environment'] === 'sandbox') {
    $bayarcash->useSandbox();
}

// Initialize variables
$error_message = false;
$errors = [];

// Payment gateway options
$payment_gateways = [
    1 => 'FPX Online Banking (CASA)',
    4 => "FPX Line of Credit (Credit Card)",
    5 => "DuitNow Online Banking/Wallets",
];

// Sample transaction details (you might want to generate these dynamically)
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
            'portal_key' => $config['bayarcash_portal_key'],
            'order_number' => $order_no,
            'amount' => $order_amount,
            'payer_name' => $buyer_name,
            'payer_email' => $buyer_email,
            'payer_telephone_number' => $buyer_tel,
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

        $data['checksum'] = $bayarcash->createChecksumValue($config['bayarcash_api_secret_key'], $checksumPayload);

        $response = $bayarcash->createPaymentIntent($data);

        if ($response->url) {
            header("Location: " . $response->url);
            exit();
        } else {
            throw new Exception('Payment URL not received');
        }
    } catch (ValidationException $exception) {
        $exceptionData = $exception->errors();
        $error_message = $exceptionData['message'];
        $errors = $exceptionData['errors'];
    } catch (Exception $exception) {
        $error_message = 'An unexpected error occurred';
        $errors = [$exception->getMessage()];
    }
}

// Include the view file
include 'checkout.php';