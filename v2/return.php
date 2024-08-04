<?php
global $config;
require_once './../config-v2.php';
require_once 'vendor/autoload.php';
require_once 'TransactionModel.php';
require_once 'helper.php';

use Webimpian\BayarcashSdk\Bayarcash;

// Get the current configuration based on the environment
$current_config = getConfig($config, $config['environment']);

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];
$tableCreated = false;

try {
    $bayarcashSdk = new Bayarcash($current_config['bayarcash_bearer_token']);
    if ($config['environment'] === 'sandbox') {
        $bayarcashSdk->useSandbox();
    }

    $apiSecretKey = $current_config['bayarcash_api_secret_key'];
    $callbackData = $_POST;
    $validResponse = false;

    // Initialize TransactionModel
    $transaction = new TransactionModel($config);
    $tableCreated = $transaction->wasTableCreated();

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

    if (!$validResponse) {
        $response = ['status' => 'error', 'message' => 'Invalid response'];
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => 'EXCEPTION: ' . $e->getMessage()];
}

function handlePreTransaction($data, $transaction) {
    $post_data = [
        'record_type'               => $data['record_type'],
        'transaction_id'            => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'],
        'order_number'              => $data['order_number'],
        'checksum'                  => $data['checksum']
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

function handleTransaction($data, $transaction) {
    $post_data = [
        'record_type'               => $data['record_type'],
        'transaction_id'            => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'],
        'exchange_transaction_id'   => $data['exchange_transaction_id'],
        'order_number'              => $data['order_number'],
        'currency'                  => $data['currency'],
        'amount'                    => $data['amount'],
        'payer_name'                => $data['payer_name'],
        'payer_email'               => $data['payer_email'],
        'payer_bank_name'           => $data['payer_bank_name'],
        'status'                    => $data['status'],
        'status_description'        => $data['status_description'],
        'datetime'                  => $data['datetime'],
        'checksum'                  => $data['checksum']
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
<h1>Payment Callback Response</h1>
    <?php if ($tableCreated): ?>
        <p class="info"><strong>Info:</strong> Transactions table was created in the database.</p>
    <?php endif; ?>

    <h2>
        Status: <span class="<?php echo $response['status'] === 'success' ? 'success' : 'error'; ?>"><?php echo ucfirst($response['status']); ?></span>
    </h2>

    <div class="message <?php echo $response['status']; ?>">
        <strong>Message:</strong> <?php echo $response['message']; ?>
    </div>

    <h3>Callback Data:</h3>
    <pre><?php echo json_encode($callbackData, JSON_PRETTY_PRINT); ?></pre>

    <?php if (isset($callbackData['exchange_reference_number'])): ?>
        <p><strong>FPX Transaction ID (exchange_reference_number):</strong> <?php echo $callbackData['exchange_reference_number']; ?></p>
        <p>Please save this FPX Transaction ID for future reference.</p>
    <?php endif; ?>

    <?php if (isset($callbackData['status'])): ?>
        <h3>Payment Status:</h3>
        <p style="background-color: <?php echo $callbackData['status'] === '3' ? '#e0f7e0' : '#f7e0e0'; ?>; padding: 10px; border-radius: 5px;">
            <?php echo get_payment_status_name($callbackData['status']) . ": " . $callbackData['status_description']; ?>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
