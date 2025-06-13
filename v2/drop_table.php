<?php

$config = require_once '../config-v2.php';
include_once 'TransactionModel.php';

$transaction = new TransactionModel($config);
$transaction->destroy();
