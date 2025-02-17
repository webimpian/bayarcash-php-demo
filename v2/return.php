<?php
global $config;
require_once './../config-v2.php';
require_once 'vendor/autoload.php';
require_once 'TransactionModel.php';
require_once 'ReturnUrlModel.php';
require_once 'helper.php';

use Webimpian\BayarcashSdk\Bayarcash;

// Get the current configuration based on the environment
$current_config = getConfig($config, $config['environment']);

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];
$tableCreated = false;
$returnUrlTableCreated = false;
$callbackData = [];

try {
    $bayarcashSdk = new Bayarcash($current_config['bayarcash_bearer_token']);
    if ($config['environment'] === 'sandbox') {
        $bayarcashSdk->useSandbox();
    }

    $apiSecretKey = $current_config['bayarcash_api_secret_key'];
    $validResponse = false;

    // Initialize both models
    $transaction = new TransactionModel($config);
    $returnUrlModel = new ReturnUrlModel($config);

    // Check table creation status
    $tableCreated = $transaction->wasTableCreated();
    $returnUrlTableCreated = $returnUrlModel->wasTableCreated();

    // Handle POST requests
    if (!empty($_POST)) {
        $callbackData = $_POST;

        if (isset($callbackData['record_type'])) {
            switch ($callbackData['record_type']) {
                case 'pre_transaction':
                    $validResponse = $bayarcashSdk->verifyPreTransactionCallbackData($callbackData, $apiSecretKey);
                    if ($validResponse) {
                        handlePreTransaction($callbackData, $transaction);
                        $response = ['status' => 'success', 'message' => 'Pre-transaction processed successfully'];
                    }
                    break;
                case 'transaction':
                case 'transaction_receipt':
                    $validResponse = $bayarcashSdk->verifyTransactionCallbackData($callbackData, $apiSecretKey);
                    if ($validResponse) {
                        handleTransaction($callbackData, $transaction);
                        $response = ['status' => 'success', 'message' => 'Transaction processed successfully'];
                    }
                    break;
                default:
                    $response = ['status' => 'error', 'message' => 'Unknown record type'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Missing record type'];
        }
    }
    // Handle GET requests (return URL)
    elseif (!empty($_GET['transaction_id'])) {
        $callbackData = array_merge([
            'status_description' => '',
            'exchange_transaction_id' => '',
            'status' => ''
        ], $_GET);

        $validResponse = $bayarcashSdk->verifyReturnUrlCallbackData($callbackData, $apiSecretKey);
        if ($validResponse) {
            handleReturnUrlTransaction($callbackData, $returnUrlModel);
            $response = ['status' => 'success', 'message' => 'Return URL transaction processed successfully'];
        }
    }

    if (!$validResponse) {
        $response = ['status' => 'error', 'message' => 'Invalid response'];
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => 'EXCEPTION: ' . $e->getMessage()];
}

function handlePreTransaction($data, $transaction): void
{
    $post_data = [
        'record_type'               => $data['record_type'],
        'transaction_id'            => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'] ?? '',
        'order_number'              => $data['order_number'] ?? '',
        'checksum'                  => $data['checksum'] ?? ''
    ];

    if (empty($post_data['transaction_id'])) {
        return;
    }

    try {
        $existingTransaction = $transaction->getByTransactionId($post_data['transaction_id']);
        if ($existingTransaction) {
            $transaction->update($post_data);
        } else {
            $transaction->insert($post_data);
        }
    } catch (Exception $e) {
        log_results('Error handling pre-transaction: ' . $e->getMessage());
    }
}

function handleTransaction($data, $transaction): void
{
    $post_data = [
        'record_type'               => $data['record_type'],
        'transaction_id'            => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'] ?? '',
        'exchange_transaction_id'   => $data['exchange_transaction_id'] ?? '',
        'order_number'              => $data['order_number'] ?? '',
        'currency'                  => $data['currency'] ?? '',
        'amount'                    => $data['amount'] ?? '',
        'payer_name'                => $data['payer_name'] ?? '',
        'payer_email'               => $data['payer_email'] ?? '',
        'payer_bank_name'           => $data['payer_bank_name'] ?? '',
        'status'                    => $data['status'] ?? '',
        'status_description'        => $data['status_description'] ?? '',
        'datetime'                  => $data['datetime'] ?? '',
        'checksum'                  => $data['checksum'] ?? ''
    ];

    try {
        $existingTransaction = $transaction->getByTransactionId($post_data['transaction_id']);
        if ($existingTransaction) {
            $transaction->update($post_data);
        } else {
            $transaction->insert($post_data);
        }
    } catch (Exception $e) {
        log_results('Error handling transaction: ' . $e->getMessage());
    }
}

function handleReturnUrlTransaction($data, $returnUrlModel): void
{
    // Add default values for potentially missing keys
    $data = array_merge([
        'status_description' => '',
        'exchange_transaction_id' => '',
        'status' => ''
    ], $data);

    try {
        $existingTransaction = $returnUrlModel->getByTransactionId($data['transaction_id']);
        if ($existingTransaction) {
            $returnUrlModel->update($data);
        } else {
            $returnUrlModel->insert($data);
        }
    } catch (Exception $e) {
        log_results('Error handling return URL transaction: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayarcash Payment Callback Response</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop.css">
    <script src="https://kit.fontawesome.com/fdd718b065.js" crossorigin="anonymous"></script>
</head>
<body>
<div id="container" class="container col-4 mt-3 mb-4 container-width">

    <!-- Reference -->
    <div class="mb-3">
        <div>
            <a target="_blank" href="https://github.com/webimpian/bayarcash-php-demo">
                Reference from GitHub repo »
            </a>
        </div>
        <div class="mt-1">
            <a target="_blank" href="https://api.webimpian.support/bayarcash">
                Bayarcash API documentation »
            </a>
        </div>
    </div>

    <!-- Card -->
    <div class="card shadow">
        <div class="card-header">
            Payment Callback Response
        </div>
        <div class="card-body">
            <!-- Tables Creation Status -->
            <?php if ($tableCreated || $returnUrlTableCreated): ?>
                <div class="alert alert-info">
                    <?php if ($tableCreated): ?>
                        <div><strong>Info:</strong> Transactions table was created in the database.</div>
                    <?php endif; ?>
                    <?php if ($returnUrlTableCreated): ?>
                        <div><strong>Info:</strong> Return URL transactions table was created in the database.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Status -->
            <div class="alert <?php echo $response['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo ucfirst($response['status']); ?>. <?php echo $response['message']; ?>
            </div>

            <hr class="mt-4 mb-4">

            <!-- Callback data -->
            <div>
                <h5 class="font-weight-bold mb-3">
                    Callback Data
                </h5>
                <pre><?php echo json_encode($callbackData, JSON_PRETTY_PRINT); ?></pre>
            </div>

            <!-- Exchange reference number -->
            <?php if (isset($callbackData['exchange_reference_number'])): ?>
                <p><strong>Exchange Reference Number:</strong> <?php echo $callbackData['exchange_reference_number']; ?></p>
                <p>Please save this exchange reference number for future reference.</p>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <!-- Payment status -->
            <?php if (isset($callbackData['status'])): ?>
                <h5 class="font-weight-bold mb-3">
                    Payment Status
                </h5>
                <div class="alert <?php echo $callbackData['status'] === '3' ? 'alert-success' : 'alert-danger'; ?>">
                    <?php
                    if (!empty($_GET)) {
                        $statusName = $returnUrlModel->get_payment_status_name($callbackData['status']);
                    } else {
                        $statusName = $transaction->get_payment_status_name($callbackData['status']);
                    }
                    echo $statusName . ": " . ($callbackData['status_description'] ?? 'No status description available');
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JS -->
<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</body>
</html>