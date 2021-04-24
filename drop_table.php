<?php

include_once 'config.php';
include_once 'TransactionModel.php';

$transaction = new TransactionModel($config);
$transaction->destroy();
