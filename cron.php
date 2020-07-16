<?php
require_once('config.php');

$bearer_token = $config['bayarcash_FPX_bearer_token'];

/**
* Query from DB for order that have the status 'pending order' preferably for today only here,
* extract the fpx order ref no in array and
* assign to the variable $FPX_OrderRefNo below
**/
$FPX_OrderRefNo = [
    "1-593-263-118-994880",
    "1-594-106-159-975990",
    "1-594-259-001-444911"
];

$results = [];

$results = array_map(function ($OrderRefNo) use ($bearer_token) {
    $fpx_api_data = array(
        "bearer_token" => $bearer_token,
        "FPX_OrderRefNo" => $OrderRefNo
    );

    return get_transaction_statuses($fpx_api_data);
}, $FPX_OrderRefNo);

/**
* Based on results, update the purchase records either successful or unsuccessful here 
**/

// Group results into successful_payment and unsuccessful_payment
$successful_payment = array_map(function ($result){
    if($result['status_value'] == 'Successful') {
        return $result;
    }
}, $results);


$unsuccessful_payment = array_map(function ($result){
    if($result['status_value'] == 'Unsuccessful') {
        return $result;
    }
}, $results);

// update pending payment status to paid payment
foreach($successful_payment as $payment){
    $fpx_order_ref_no = $payment['exchange_order_no'];
    $order_no = $payment['fpx_product_desc'];
}

// update pending payment status to failed payment
foreach($unsuccessful_payment as $payment){
    $fpx_order_ref_no = $payment['exchange_order_no'];
    $order_no = $payment['fpx_product_desc'];
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
        ->recordsListData[0]
        ->transaction_details;

    $transaction_detail = [
        'status_value' => get_payment_status_name($decoded_curl_output->status->value),
        'status_description' => $decoded_curl_output->status_description->value,
        'amount' => $decoded_curl_output->amount->value,
        'fpx_product_desc' => $decoded_curl_output->fpx_product_desc->value,
        'buyer_name' => $decoded_curl_output->buyer_name->value,
        'buyer_email' => $decoded_curl_output->buyer_email->value,
        'exchange_order_no' => $decoded_curl_output->exchange_order_no->value,
        'transaction_id' => $decoded_curl_output->transaction_id->value,
        'datetime' => $decoded_curl_output->datetime->value,
        'bank_name' => $decoded_curl_output->buyer_bank_name->value
    ];

    return $transaction_detail;
}

function FPX_API_cURL(array $fpx_api_data)
{ # Function to connect to FPX API start.
    # Fetch the configuration data.
    $fpx_api_url = 'https://console.bayar.cash/api' . '/transactions/?RefNo=';
    $fpx_order_ref_no = null;

    # Fetch the API data.
    $bearer_token = $fpx_api_data["bearer_token"];

    if (isset($fpx_api_data['FPX_OrderRefNo']))
        $fpx_order_ref_no = $fpx_api_data['FPX_OrderRefNo'];

    # Count number of data to be POSTed.
    $fpx_api_data_count = count($fpx_api_data);

    $fpx_api_data_fields = http_build_query($fpx_api_data);

    $fpx_api_url .= $fpx_order_ref_no; # Append the FPX Exchange Ref. No. to the URL.
    # TODO: Migrate this section into WC config.
    $fpx_api_http_headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $bearer_token
    );

    # cURL section start.
    $fpx_curl_output = null;
    $fpx_curl = curl_init();
    curl_setopt($fpx_curl, CURLOPT_URL, $fpx_api_url);
    curl_setopt($fpx_curl, CURLOPT_HTTPHEADER, $fpx_api_http_headers);
    curl_setopt($fpx_curl, CURLOPT_POST, true);
    curl_setopt($fpx_curl, CURLOPT_POSTFIELDS, $fpx_api_data_fields);
    curl_setopt($fpx_curl, CURLOPT_RETURNTRANSFER, true);
    $fpx_curl_output = curl_exec($fpx_curl);
    curl_close($fpx_curl);
    # cURL section end.

    return $fpx_curl_output;
}

function get_payment_status_name($payment_status_code)
{
    $payment_status_name_list = [
        'New',
        'Pending',
        'Unsuccessful',
        'Successful',
        'Cancelled'
    ];

    $is_Id = array_key_exists($payment_status_code, $payment_status_name_list);

    if (!$is_Id) {
        return;
    }

    return $payment_status_name_list[$payment_status_code];
}

function log_results($result)
{
    // create logs directory if doesn't exist
    if(! is_dir('./logs')){
        mkdir('./logs');
    }

    $timezone = 'Asia/Kuala_Lumpur';
    $timezone_object = new DateTimeZone($timezone);
    $today = new DateTime("now", $timezone_object);
    $timestamp = $today->format('j/n/Y h:i a');

    $log = "\n\nLog generated at " . $timestamp . "\n";
    $log .= $result;

    file_put_contents('./logs/log_'.date("j.n.Y").'.log', $log, FILE_APPEND);
}
