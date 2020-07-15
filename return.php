<?php
require_once('config.php');

if (isset($_POST)) {
    $is_portal_key_valid = check_portal_key_valid($config['bayarcash_FPX_portal_key']);

    if (!$is_portal_key_valid) {
        die('Mismatched data.');
    }

    $post_data = [
        'order_ref_no' => $_POST['order_ref_no'],
        'order_no' => $_POST['order_no'],
        'transaction_currency' => $_POST['transaction_currency'],
        'order_amount' => $_POST['order_amount'],
        'buyer_name' => $_POST['buyer_name'],
        'buyer_email'  => $_POST['buyer_email'],
        'buyer_bank_name' => $_POST['buyer_bank_name'],
        'transaction_status' => $_POST['transaction_status'],
        'transaction_status_description' => $_POST['transaction_status_description'],
        'transaction_datetime' => $_POST['transaction_datetime'],
        'transaction_gateway_id' => $_POST['transaction_gateway_id'],
    ];


    displayOutputMessage($payment_status, $post_data);
}

function displayOutputMessage($payment_status, $post_data){

    $post_response = print_r($post_data, true); 

    $payment_status = get_payment_status_name($post_data['transaction_status']);

    $order_ref_no = $post_data['order_ref_no']; 

    $payment_status_message = 'Payment succesful, handle succesful payment from here';

    if ($payment_status != 'Successful') {
        $payment_status_message = 'Payment is not successful, handle unsuccessful payment from here'; 
    }

    echo "<div>{$payment_status_message}</div>";
    echo '<br>';
    echo '<div>Response is in $post_data array </div>';
    echo "<pre>{$post_response}</pre>";
    echo '<br>';
    echo '<div>To access FPX Transaction ID property</div>';
    echo '<br>';
    echo '$post_data[\'order_ref_no\']';
    echo '<br>';
    echo "<pre>{$order_ref_no}</pre>";
}

function check_portal_key_valid($portal_key)
{
    $fpx_hashed_data_from_portal = $_POST['fpx_data']; # Create a variable alias since we are going to remove $_POST['fpx_data'].

    unset($_POST['fpx_data']); # Remove this POST parameter since we are going to construct a source string and compare it with MD5 hashed data sent from the portal.

    $fpx_hashed_data_to_compare = md5($portal_key . json_encode($_POST)); # Construct the source string same like defined at the portal.

    return $fpx_hashed_data_to_compare == $fpx_hashed_data_from_portal;
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
