<?php

$bearer_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI1IiwianRpIjoiOTgzNWExNWYxMmE2ODI4YmU3ZmI3ZDE2ZDc5ZDI0MGRkMDE5YjE3MDVlOTI3NmM1MDQyNTZmNTQxOTBhYmY1YjkwZTU0NGQ5NTgxYzYzZDEiLCJpYXQiOjE1ODQxNTc4MzksIm5iZiI6MTU4NDE1NzgzOSwiZXhwIjoxNjE1NjkzODM5LCJzdWIiOiI1Iiwic2NvcGVzIjpbIioiXX0.xYE0NDMZe0lV4bl2mJ6MkQaq4nepEjY8iLzaBwHsrgJOwg-qMs0p-zrdsBijnVBaL5sOH6obvxVdph42_EohV1KYhCrhN1QmBub7ws7A2w8qheOIp8suZswlE380R3mTPyTrdmzWLjWazg8AVZ3m9PHwM3s-lvRXupSrK-JiAHxYlDmyHA0wDbkPRs3p3fAbcb7g6-lO_TcnI-a73Xk3mTroQC0z5SFnFfastDLGHFEPJkI8TjYkbjb4taar8rDUbhWWfBbe7EpRu0fG-cPAkE64CWnsd-G0G_a0PMmIhxjx5SJNJs8iIPZi4kNf9qkFH69i7RQAkc3jNXoPPkzMrz6lo21S1uXrjxKrC2BwlYmx80t-NeoR9cMCXQd-rVtVGOPkWKY_-gT9qSA9xDexT-bg_NEEHBBqU3pzkPfXByzQZEP7MN1hk02-AWN25XYL6TGSz6jU5jIMw8MhQqgNLnfmKrAM9VUNPZeMOFrWaMa6383FHpIOtnkBtad8NWNuWjr7BQVcASZAAyLjgkkkwD0NHX8tT-AjsWSSTq2U5i3uAlqtYsytgnz_e9NkjEZDF7h3uilCIuX5dqI7SCrw-9O4ad3vJNTVW5RKGU7vqiVyIsTygILJpkJj4nMDMoyBWu00dVqagcjhfzVaZIYQcKgipsK_az8-kTCA53j2gIw";

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

// return back results
return json_encode($results);

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
