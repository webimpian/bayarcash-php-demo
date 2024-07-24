<?php

require_once 'config-v1.php';
require_once 'TransactionModel.php';
require_once 'helper.php';

$bearer_token = $config['bayarcash_bearer_token'];

/**
 * Query from DB for order that have the status 'pending order' preferably for today only here,
 * extract the fpx order ref no in array and
 * assign to the variable $FPX_OrderRefNo below.
 **/
$transactionModel = new TransactionModel($config);
$FPX_OrderRefNo = $transactionModel->getNewTransactionsOrderRefNo();

$results = [];

if (empty($FPX_OrderRefNo)) {
    echo 'Empty FPX_OrderRefNo';

    return;
}

$results = array_map(function ($OrderRefNo) use ($bearer_token) {
    $fpx_api_data = [
        'bearer_token'   => $bearer_token,
        'FPX_OrderRefNo' => $OrderRefNo,
    ];

    return get_transaction_statuses($fpx_api_data);
}, $FPX_OrderRefNo);

/**
 * Update payment.
 **/
if (!empty($results)) {
    foreach ($results as $payment) {
        $transactionModel->update($payment);
    }
}

// record log result
log_results(print_r($results, true));

// return back results
header('Content-type: application/json');
echo json_encode($results);

function get_transaction_statuses($fpx_api_data)
{
    $curl_output = FPX_API_cURL($fpx_api_data);

    $decoded_curl_output = json_decode($curl_output)
        ->output
        ->transactionsList
        ->recordsListData[0];

    $transaction_detail = [
        'order_no'                       => $decoded_curl_output->fpx_product_desc,
        'transaction_currency'           => $decoded_curl_output->currency,
        'order_amount'                   => $decoded_curl_output->amount,
        'buyer_name'                     => $decoded_curl_output->buyer_name,
        'buyer_email'                    => $decoded_curl_output->buyer_email,
        'buyer_bank_name'                => $decoded_curl_output->buyer_bank_name,
        'transaction_status'             => $decoded_curl_output->status,
        'transaction_status_description' => $decoded_curl_output->status_description,
        'transaction_datetime'           => $decoded_curl_output->datetime,
        'transaction_gateway_id'         => $decoded_curl_output->fpx_transaction_id,
        'order_ref_no'                   => $decoded_curl_output->exchange_order_no,
    ];

    return $transaction_detail;
}

function FPX_API_cURL(array $fpx_api_data)
{ // Function to connect to FPX API start
    global $config;

    // Fetch the configuration data.
    $environment = $config['environment']; 
    $fpx_api_url = $config['bayarcash_requery_transaction_status_api_url'][$environment];
    $fpx_order_ref_no = null;

    // Fetch the API data.
    $bearer_token = $fpx_api_data['bearer_token'];

    if (isset($fpx_api_data['FPX_OrderRefNo'])) {
        $fpx_order_ref_no = $fpx_api_data['FPX_OrderRefNo'];
    }

    // Count number of data to be POSTed.
    $fpx_api_data_count = count($fpx_api_data);

    $fpx_api_data_fields = http_build_query($fpx_api_data);

    $fpx_api_url .= $fpx_order_ref_no; // Append the FPX Exchange Ref. No. to the URL.
    // TODO: Migrate this section into WC config.
    $fpx_api_http_headers = [
        'Accept: application/json',
        'Authorization: Bearer '.$bearer_token,
    ];

    // cURL section start.
    $fpx_curl_output = null;
    $fpx_curl = curl_init();
    curl_setopt($fpx_curl, CURLOPT_URL, $fpx_api_url);
    curl_setopt($fpx_curl, CURLOPT_HTTPHEADER, $fpx_api_http_headers);
    curl_setopt($fpx_curl, CURLOPT_POST, true);
    curl_setopt($fpx_curl, CURLOPT_POSTFIELDS, $fpx_api_data_fields);
    curl_setopt($fpx_curl, CURLOPT_RETURNTRANSFER, true);
    $fpx_curl_output = curl_exec($fpx_curl);
    curl_close($fpx_curl);
    // cURL section end.

    return $fpx_curl_output;
}
