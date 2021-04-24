<?php

function get_payment_status_name($payment_status_code)
{
    $payment_status_name_list = [
        'New',
        'Pending',
        'Unsuccessful',
        'Successful',
        'Cancelled',
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
    if (!is_dir('./logs')) {
        mkdir('./logs');
    }

    $timezone = 'Asia/Kuala_Lumpur';
    $timezone_object = new DateTimeZone($timezone);
    $today = new DateTime('now', $timezone_object);
    $timestamp = $today->format('j/n/Y h:i a');

    $log = "\n\nLog generated at ".$timestamp."\n";
    $log .= $result;

    file_put_contents('./logs/log_'.date('j.n.Y').'.log', $log, FILE_APPEND);
}
