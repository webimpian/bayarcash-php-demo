<?php

include_once('TransactionModel.php');

$transactionModel = new TransactionModel();
$transactionModel->setup();
$transactionModel->insert([
    'order_no' => 'ORDER1',
    'order_ref_no' => '8098080980'
]);

// $transactionModel->insert_transaction([
//     'order_no' => 'ORDER1',
//     'order_ref_no' => '8098080980'
// ]);

//
// $transactionModel->update_transaction([
//     'order_no' => 'NEW_ORDER_1',
//     'order_ref_no' => '8098080980'
// ]);
//
// $transactionModel->getAll();

// $FPX_OrderRefNo = $transactionModel->getNewTransactionsOrderRefNo(); 
// var_dump($FPX_OrderRefNo);
