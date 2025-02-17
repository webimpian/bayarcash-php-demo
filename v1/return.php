<?php

require_once './../config-v1.php';
require_once 'TransactionModel.php';
require_once 'helper.php';

if (isset($_POST['fpx_pre_transaction_data'])) {
    $post_data = [
        'order_ref_no' => $_POST['fpx_pre_transaction_data']['fpx_exchange_order_number'],
        'order_no'     => $_POST['fpx_pre_transaction_data']['fpx_order_number'],
    ];

    if (empty($post_data['order_ref_no'])) {
        return;
    }

    $transaction = new TransactionModel($config);
    $transaction->updateByOrderNo($post_data);
}

if (isset($_POST['fpx_data'])) {
    $is_portal_key_valid = check_portal_key_valid($config['bayarcash_portal_key']);

    if (!$is_portal_key_valid) {
        exit('Mismatched data.');
    }

    $post_data = [
        'order_ref_no'                   => $_POST['order_ref_no'],
        'order_no'                       => $_POST['order_no'],
        'transaction_currency'           => $_POST['transaction_currency'],
        'order_amount'                   => $_POST['order_amount'],
        'buyer_name'                     => $_POST['buyer_name'],
        'buyer_email'                    => $_POST['buyer_email'],
        'buyer_bank_name'                => $_POST['buyer_bank_name'],
        'transaction_status'             => $_POST['transaction_status'],
        'transaction_status_description' => $_POST['transaction_status_description'],
        'transaction_datetime'           => $_POST['transaction_datetime'],
        'transaction_gateway_id'         => $_POST['transaction_gateway_id'],
    ];

    $payment_status = get_payment_status_name($post_data['transaction_status']);

    handlePayment($payment_status, $post_data, $config);
}

// DOBW Callback
if (isset($_POST['record_type']) && $_POST['record_type'] === 'transaction_receipt') {
    $secret_key = $config['bayarcash_api_secret_key'];

    if (! verify_dobw_transaction_callback($_POST, $secret_key)) {
        exit('Mismatched data.');
    }

    $post_data = [
        'order_ref_no'                   => $_POST['exchange_reference_number'],
        'order_no'                       => $_POST['order_no'],
        'transaction_currency'           => $_POST['currency'],
        'order_amount'                   => $_POST['amount'],
        'buyer_name'                     => $_POST['payer_name'],
        'buyer_email'                    => $_POST['payer_email'],
        'buyer_bank_name'                => $_POST['payer_bank_name'],
        'transaction_status'             => $_POST['status'],
        'transaction_status_description' => $_POST['status_description'],
        'transaction_datetime'           => $_POST['datetime'],
        'transaction_gateway_id'         => $_POST['exchange_transaction_id'],
    ];

    $payment_status = get_payment_status_name($post_data['status']);

    handlePayment($payment_status, $post_data, $config);
}

function verify_dobw_transaction_callback(array $callbackData, string $secretKey)
{
    $callbackChecksum = $callbackData['checksum'];

    $payload = [
        'record_type' => $callbackData['record_type'],
        'transaction_id' => $callbackData['transaction_id'],
        'exchange_reference_number' => $callbackData['exchange_reference_number'],
        'exchange_transaction_id' => $callbackData['exchange_transaction_id'],
        'order_number' => $callbackData['order_number'],
        'currency' => $callbackData['currency'],
        'amount' => $callbackData['amount'],
        'payer_name' => $callbackData['payer_name'],
        'payer_email' => $callbackData['payer_email'],
        'payer_bank_name' => $callbackData['payer_bank_name'],
        'status' => $callbackData['status'],
        'status_description' => $callbackData['status_description'],
        'datetime' => $callbackData['datetime'],
    ];

    ksort($payload);
    $payload = implode('|', $payload);

    return hash_hmac('sha256', $payload, $secretKey) === $callbackChecksum;
}

function handlePayment($payment_status, $post_data, $config)
{
    $post_response = print_r($post_data, true);

    $payment_status = get_payment_status_name($post_data['transaction_status']);

    $order_ref_no = $post_data['order_ref_no'];

    $transaction = new TransactionModel($config);

    if ($payment_status == 'Successful') {
        $payment_status_message = 'Payment is successful, handle successful payment from here.';
        $transaction->update($post_data);
    }

    if ($payment_status == 'Unsuccessful') {
        $payment_status_message = 'Payment is unsuccessful, handle unsuccessful payment from here.';
        $transaction->update($post_data);
    }

    echo "<div>{$payment_status_message}</div>";
    echo '<br>';
    echo '<div>Response is in $post_data array </div>';
    echo "<pre>{$post_response}</pre>";
    echo '<br>';
    echo '<div>To access FPX Transaction ID property.</div>';
    echo '<br>';
    echo '$post_data[\'order_ref_no\']';
    echo '<br>';
    echo "<pre>{$order_ref_no}</pre>";
    echo '<br>';
    echo '<div>Please save this FPX Transaction ID for future reference.</div>';
}

function check_portal_key_valid($portal_key)
{
    $fpx_hashed_data_from_portal = $_POST['fpx_data']; // Create a variable alias since we are going to remove $_POST['fpx_data'].

    unset($_POST['fpx_data']); // Remove this POST parameter since we are going to construct a source string and compare it with MD5 hashed data sent from the portal.

    $fpx_hashed_data_to_compare = md5($portal_key.json_encode($_POST)); // Construct the source string same like defined at the portal.

    return $fpx_hashed_data_to_compare == $fpx_hashed_data_from_portal;
}
