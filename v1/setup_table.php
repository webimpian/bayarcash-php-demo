<?php

include_once './../config-v1.php';
include_once 'TransactionModel.php';

$transaction = new TransactionModel($config);
$transaction->setup();
