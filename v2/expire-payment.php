<?php
global $config;
require_once '../config-v2.php';
require_once 'vendor/autoload.php';

use Webimpian\BayarcashSdk\Bayarcash;
use Webimpian\BayarcashSdk\Exceptions\ValidationException;

$current_config = getConfig($config, $config['environment']);

$bayarcash = new Bayarcash($current_config['bayarcash_bearer_token']);

$api_version = 'v3';
$bayarcash->setApiVersion($api_version);

if ($config['environment'] === 'sandbox') {
    $bayarcash->useSandbox();
}

$error_message = '';
$errors = [];
$payment_url = '';
$expiry_time = '';

$payment_gateways = [
    1 => 'FPX Online Banking (CASA)'
];

function logRawResponse($response) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/payment_intent_raw.log';
    file_put_contents($log_file, json_encode($response, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
}

$order_no = str_pad(mt_rand(1, 9999), 6, '0', STR_PAD_RIGHT);
$order_amount = '1';
$buyer_name = 'John Doe';
$buyer_email = 'john.doe@example.com';
$buyer_tel = '60123456789';
$order_description = 'Gelang Emas 916 - 2.36g';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_channel = $_POST['payment_gateway'];

    $expiry_minutes = isset($_POST['expiry_minutes']) ? intval($_POST['expiry_minutes']) : 30;

    if ($expiry_minutes < 1) {
        $expiry_minutes = 1;
    } elseif ($expiry_minutes > 1440) {
        $expiry_minutes = 1440;
    }

    $tz = new DateTimeZone('Asia/Kuala_Lumpur');
    $expiry_time = new DateTime('now', $tz);
    $expiry_time->modify("+{$expiry_minutes} minutes");
    $expiry_time_formatted = $expiry_time->format('Y-m-d H:i:s');

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
            'link_expired_at' => $expiry_time_formatted
        ];

        $data['checksum'] = $bayarcash->createPaymentIntenChecksumValue($current_config['bayarcash_api_secret_key'], $data);

        $response = $bayarcash->createPaymentIntent($data);

       // logRawResponse($response);

        if ($response->url) {
            $payment_url = $response->url;
        } else {
            $error_message = 'Payment URL not received';
        }
    } catch (ValidationException $exception) {
        $exceptionData = $exception->errors();
        if (is_array($exceptionData['message'])) {
            $error_message = implode(' ', $exceptionData['message']);
        } else {
            $error_message = (string)$exceptionData['message'];
        }

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

        $log_data = [
            'order_no' => $order_no,
            'data' => $data,
            'exception' => $exceptionData,
            'message' => $error_message . "\nValidation Errors: " . implode(", ", $errors)
        ];
        logRawResponse($log_data);

    } catch (Exception $exception) {
        $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
        $errors = [];

        $log_data = [
            'order_no' => $order_no,
            'data' => $data ?? [],
            'exception' => ['message' => $exception->getMessage()],
            'message' => $error_message
        ];
        logRawResponse($log_data);
    }
}

include 'checkout_with_expiry.php';