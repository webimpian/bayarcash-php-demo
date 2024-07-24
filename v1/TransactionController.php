<?php

require 'TransactionModel.php';
require 'config-v1.php';

if ($_POST['buyer_ic_no'] && $_POST['order_no']) {
    $post_data = [
        'buyer_ic_no' => $_POST['buyer_ic_no'],
        'order_no'    => $_POST['order_no'],
    ];

    $transactionModel = new TransactionModel($config);

    $status = $transactionModel->init($post_data);

    echo $status;
}
