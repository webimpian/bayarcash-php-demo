<?php
session_start();

$config = require_once '../config-v2.php';
require_once 'TransactionModel.php';
require_once 'ReturnUrlModel.php';
require_once 'helper.php';

$config['environment'] = 'dev';
$current_config = getConfig($config, $config['environment']);

$manual_transfer_status_endpoint = 'https://console.bayarcash.dev/api/manual-bank-transfer/update-status';

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];
$tableCreated = false;
$returnUrlTableCreated = false;
$callbackData = [];
$displayData = [];
$updateMessage = '';
$updateSuccess = false;

try {
    $transaction = new TransactionModel($config);
    $returnUrlModel = new ReturnUrlModel($config);

    $tableCreated = $transaction->wasTableCreated();
    $returnUrlTableCreated = $returnUrlModel->wasTableCreated();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_manual_transfer'])) {
        try {
            $refNo = $_POST['ref_no'];
            $status = $_POST['status'];
            $amount = $_POST['amount'];

            if (isset($_SESSION['callback_data'])) {
                $callbackData = $_SESSION['callback_data'];
            }

            log_results('Manual Transfer Status Update (Dev): ' . json_encode([
                'ref_no' => $refNo,
                'status' => $status,
                'amount' => $amount
            ]));

            $updateResponse = updateManualTransferStatus($refNo, $status, $amount, $current_config['bayarcash_bearer_token'], $manual_transfer_status_endpoint);

            log_results('Update Response: ' . json_encode($updateResponse));

            if ($updateResponse['success']) {
                $updateSuccess = true;
                $updateMessage = 'Status successfully updated to: ' . getStatusDescription($status);
                $response = ['status' => 'success', 'message' => 'Status updated successfully'];

                $transaction = $updateResponse['response']['transaction'] ?? [];
                $displayData = [
                    'success' => true,
                    'ref_no' => $refNo,
                    'status' => $status,
                    'status_description' => getStatusDescription($status),
                    'amount' => $amount,
                    'datetime' => $transaction['datetime'] ?? date('Y-m-d H:i:s'),
                    'order_no' => $transaction['order_no'] ?? '',
                    'buyer_name' => $transaction['buyer_name'] ?? '',
                    'buyer_email' => $transaction['buyer_email'] ?? '',
                    'payment_gateway_name' => $transaction['payment_gateway_name'] ?? 'Manual Bank Transfer',
                    'merchant_bank_account' => $transaction['merchant_bank_account'] ?? '',
                    'merchant_bank_account_holder' => $transaction['merchant_bank_account_holder'] ?? '',
                    'merchant_bank_name' => $transaction['merchant_bank_name'] ?? '',
                    'bank_transfer_type' => $transaction['bank_transfer_type'] ?? '',
                    'transfer_date' => $transaction['transfer_date'] ?? '',
                    'transfer_notes' => $transaction['transfer_notes'] ?? ''
                ];

                $callbackData = array_merge($callbackData, [
                    'transaction_status' => $status,
                    'status' => $status,
                    'order_ref_no' => $callbackData['order_ref_no'] ?? '',
                    'order_amount' => $callbackData['order_amount'] ?? '',
                    'transaction_channel' => 'ManualBankTransfer'
                ]);
            } else {
                $updateMessage = 'Status update failed: ' . ($updateResponse['error'] ?? 'Unknown error');
                $displayData = $updateResponse;
            }
        } catch (Exception $e) {
            log_results('Error updating manual bank transfer status: ' . $e->getMessage());
            $updateMessage = 'Error: ' . $e->getMessage();
        }
    }

    if (!empty($_POST) && !isset($_POST['update_manual_transfer'])) {
        $callbackData = $_POST;
        $displayData = $_POST;
        $_SESSION['callback_data'] = $callbackData;

        if (isset($callbackData['transaction_channel']) && $callbackData['transaction_channel'] === 'ManualBankTransfer') {
            $response = ['status' => 'success', 'message' => 'Manual bank transfer data received'];
        } elseif (isset($callbackData['record_type'])) {
            switch ($callbackData['record_type']) {
                case 'pre_transaction':
                    handlePreTransaction($callbackData, $transaction);
                    $response = ['status' => 'success', 'message' => 'Pre-transaction data received'];
                    break;
                case 'transaction':
                case 'transaction_receipt':
                    handleTransaction($callbackData, $transaction);
                    $response = ['status' => 'success', 'message' => 'Transaction data received'];
                    break;
                default:
                    $response = ['status' => 'error', 'message' => 'Unknown record type'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Missing record type'];
        }
    } elseif (!empty($_GET['transaction_id']) && !isset($_POST['update_manual_transfer'])) {
        $callbackData = array_merge([
            'status_description' => '',
            'exchange_transaction_id' => '',
            'status' => ''
        ], $_GET);
        $displayData = $callbackData;
        $_SESSION['callback_data'] = $callbackData;

        handleReturnUrlTransaction($callbackData, $returnUrlModel);
        $response = ['status' => 'success', 'message' => 'Return URL data received'];
    } elseif (!empty($_GET) && isset($_GET['transaction_channel']) && !isset($_POST['update_manual_transfer'])) {
        $callbackData = $_GET;
        $displayData = $_GET;
        $_SESSION['callback_data'] = $callbackData;
        $response = ['status' => 'success', 'message' => 'Manual bank transfer data received'];
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => 'EXCEPTION: ' . $e->getMessage()];
}

function updateManualTransferStatus($ref_no, $status, $amount, $bearer_token, $endpoint) {
    $data = [
        'ref_no' => $ref_no,
        'status' => $status,
        'amount' => $amount
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $bearer_token,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    log_results('Manual Transfer Status Update API Response: ' . $response);

    if (!empty($curl_error)) {
        return ['success' => false, 'error' => $curl_error];
    }

    $decoded = json_decode($response, true);

    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'response' => $decoded];
    }

    return ['success' => false, 'response' => $decoded, 'http_code' => $http_code];
}

function getStatusDescription($statusCode) {
    $statusMap = [
        '2' => 'Failed',
        '3' => 'Success',
        '4' => 'Cancelled',
        '5' => 'Expired'
    ];

    return $statusMap[$statusCode] ?? 'Unknown';
}

function getManualTransferStatusOptions() {
    return [
        '2' => 'Failed',
        '3' => 'Success',
        '4' => 'Cancelled',
        '5' => 'Expired'
    ];
}

function handlePreTransaction($data, $transaction): void {
    $post_data = [
        'record_type' => $data['record_type'],
        'transaction_id' => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'] ?? '',
        'order_number' => $data['order_number'] ?? '',
        'checksum' => $data['checksum'] ?? ''
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

function handleTransaction($data, $transaction): void {
    $post_data = [
        'record_type' => $data['record_type'],
        'transaction_id' => $data['transaction_id'],
        'exchange_reference_number' => $data['exchange_reference_number'] ?? '',
        'exchange_transaction_id' => $data['exchange_transaction_id'] ?? '',
        'order_number' => $data['order_number'] ?? '',
        'currency' => $data['currency'] ?? '',
        'amount' => $data['amount'] ?? '',
        'payer_name' => $data['payer_name'] ?? '',
        'payer_email' => $data['payer_email'] ?? '',
        'payer_bank_name' => $data['payer_bank_name'] ?? '',
        'status' => $data['status'] ?? '',
        'status_description' => $data['status_description'] ?? '',
        'datetime' => $data['datetime'] ?? '',
        'checksum' => $data['checksum'] ?? ''
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

function handleReturnUrlTransaction($data, $returnUrlModel): void {
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
    <title>Bayarcash Dev - Payment Response</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop.css">
    <script src="https://kit.fontawesome.com/fdd718b065.js" crossorigin="anonymous"></script>
</head>
<body>
<div id="container" class="container col-4 mt-3 mb-4 container-width">

    <div class="mb-3">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Dev Environment</strong> - Direct API calls (no SDK)
        </div>
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

    <div class="card shadow">
        <div class="card-header">
            <i class="fa fa-code"></i> Payment Response (Dev)
        </div>
        <div class="card-body">
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

            <?php if (!empty($updateMessage)): ?>
                <div class="alert <?php echo $updateSuccess ? 'alert-success' : 'alert-danger'; ?>">
                    <strong><?php echo $updateSuccess ? 'Success:' : 'Error:'; ?></strong> <?php echo $updateMessage; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-<?php echo ($response['status'] === 'success') ? 'success' : 'danger'; ?>">
                    <strong>System Status:</strong> <?php echo ($response['status'] === 'success') ? 'Data received successfully' : $response['message']; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($callbackData['transaction_status']) || isset($callbackData['status'])): ?>
                <h5 class="font-weight-bold mb-3">
                    Payment Status
                </h5>
                <div class="alert <?php echo (($callbackData['transaction_status'] ?? $callbackData['status'] ?? '') === '3') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php
                    $statusCode = $callbackData['transaction_status'] ?? $callbackData['status'] ?? '';
                    $statusDescription = getStatusDescription($statusCode);
                    echo $statusDescription;
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($callbackData['transaction_channel']) && $callbackData['transaction_channel'] === 'ManualBankTransfer' && isset($callbackData['order_ref_no'])): ?>
                <hr class="mt-4 mb-4">

                <div class="manual-transfer-update">
                    <h5 class="font-weight-bold mb-3">
                        Update Manual Transfer Status
                        <span class="badge badge-info ml-2">Direct API</span>
                    </h5>

                    <form method="post" action="">
                        <input type="hidden" name="update_manual_transfer" value="1">
                        <input type="hidden" name="ref_no" value="<?php echo htmlspecialchars($callbackData['order_ref_no']); ?>">
                        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($callbackData['order_amount']); ?>">

                        <div class="form-group">
                            <label for="status">New Status:</label>
                            <select class="form-control" id="status" name="status">
                                <?php
                                $statuses = getManualTransferStatusOptions();
                                foreach ($statuses as $code => $description):
                                    $selected = ($callbackData['transaction_status'] == $code) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $code; ?>" <?php echo $selected; ?>>
                                        <?php echo $description; ?> (<?php echo $code; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-sync"></i> Update Status
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <div>
                <h5 class="font-weight-bold mb-3">
                    <?php echo !empty($updateMessage) ? 'Update Response' : 'Callback Data'; ?>
                </h5>
                <pre><?php echo json_encode($displayData, JSON_PRETTY_PRINT); ?></pre>
            </div>

            <?php if (isset($displayData['exchange_reference_number'])): ?>
                <p><strong>Exchange Reference Number:</strong> <?php echo $displayData['exchange_reference_number']; ?></p>
                <p>Please save this exchange reference number for future reference.</p>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <div class="navigation-buttons">
                <h5 class="font-weight-bold mb-3">Navigation</h5>
                <div class="d-flex flex-wrap justify-content-between">
                    <a href="<?php echo $config['base_url']; ?>index.php" class="btn btn-primary mb-2 mr-2">
                        <i class="fa fa-home"></i> Main Page (SDK)
                    </a>
                    <a href="<?php echo $config['base_url']; ?>dev.php" class="btn btn-info mb-2 mr-2">
                        <i class="fa fa-code"></i> Dev Testing
                    </a>
                    <a href="<?php echo $config['base_url']; ?>expire-payment.php" class="btn btn-warning mb-2">
                        <i class="fa fa-clock"></i> Expired Payment
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</body>
</html>
