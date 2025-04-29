<?php
global $config;
require_once './../config-v2.php';
require_once 'vendor/autoload.php';
require_once 'TransactionModel.php';
require_once 'ReturnUrlModel.php';
require_once 'helper.php';

use Webimpian\BayarcashSdk\Bayarcash;

$current_config = getConfig($config, $config['environment']);

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];
$tableCreated = false;
$returnUrlTableCreated = false;
$callbackData = [];
$updateMessage = '';
$updateSuccess = false;

try {
    $bayarcashSdk = new Bayarcash($current_config['bayarcash_bearer_token']);
    if ($config['environment'] === 'sandbox') {
        $bayarcashSdk->useSandbox();
    }

    $apiSecretKey = $current_config['bayarcash_api_secret_key'];
    $validResponse = false;

    $transaction = new TransactionModel($config);
    $returnUrlModel = new ReturnUrlModel($config);

    $tableCreated = $transaction->wasTableCreated();
    $returnUrlTableCreated = $returnUrlModel->wasTableCreated();

    // Handle manual transfer status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_manual_transfer'])) {
        try {
            $refNo = $_POST['ref_no'];
            $status = $_POST['status'];
            $amount = $_POST['amount'];

            // Log the update attempt
            log_results('Updating manual bank transfer status: ' . json_encode([
                    'ref_no' => $refNo,
                    'status' => $status,
                    'amount' => $amount
                ]));

            // Call the update method
            $updateResponse = $bayarcashSdk->updateManualBankTransferStatus($refNo, $status, $amount);

            // Log the response
            log_results('Update response: ' . json_encode($updateResponse));

            // Check if update was successful
            if (is_array($updateResponse) &&
                (isset($updateResponse['success']) && $updateResponse['success'] === true) ||
                (isset($updateResponse['status']) && $updateResponse['status'] === 'success')) {

                $updateSuccess = true;
                $updateMessage = 'Status successfully updated to: ' . getStatusDescription($status);

                // Update the callback data with new status
                if (isset($callbackData['transaction_status'])) {
                    $callbackData['transaction_status'] = $status;
                    $callbackData['transaction_status_description'] = getStatusDescription($status);
                }
            } else {
                $updateMessage = 'Status update failed. Please check the logs for details.';
            }
        } catch (Exception $e) {
            log_results('Error updating manual bank transfer status: ' . $e->getMessage());
            $updateMessage = 'Error: ' . $e->getMessage();
        }
    }

    // Original callback processing code
    if (!empty($_POST) && !isset($_POST['update_manual_transfer'])) {
        $callbackData = $_POST;

        if (isset($callbackData['record_type'])) {
            $orderNumber = $callbackData['order_number'] ?? '';
            $isDev = strpos($orderNumber, 'DEV') === 0;

            switch ($callbackData['record_type']) {
                case 'pre_transaction':
                    if ($isDev) {
                        $validResponse = true;
                    } else {
                        $validResponse = $bayarcashSdk->verifyPreTransactionCallbackData($callbackData, $apiSecretKey);
                    }

                    if ($validResponse) {
                        handlePreTransaction($callbackData, $transaction);
                        $response = ['status' => 'success', 'message' => 'Data received and validated'];
                    }
                    break;
                case 'transaction':
                case 'transaction_receipt':
                    if ($isDev) {
                        $validResponse = true;
                    } else {
                        $validResponse = $bayarcashSdk->verifyTransactionCallbackData($callbackData, $apiSecretKey);
                    }

                    if ($validResponse) {
                        handleTransaction($callbackData, $transaction);
                        $response = ['status' => 'success', 'message' => 'Data received and validated'];
                    }
                    break;
                default:
                    $response = ['status' => 'error', 'message' => 'Unknown record type'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Missing record type'];
        }
    }
    elseif (!empty($_GET['transaction_id']) && !isset($_POST['update_manual_transfer'])) {
        $callbackData = array_merge([
            'status_description' => '',
            'exchange_transaction_id' => '',
            'status' => ''
        ], $_GET);

        $orderNumber = $callbackData['order_number'] ?? '';
        $isDev = strpos($orderNumber, 'DEV') === 0;

        if ($isDev) {
            $validResponse = true;
        } else {
            $validResponse = $bayarcashSdk->verifyReturnUrlCallbackData($callbackData, $apiSecretKey);
        }

        if ($validResponse) {
            handleReturnUrlTransaction($callbackData, $returnUrlModel);
            $response = ['status' => 'success', 'message' => 'Data received and validated'];
        }
    }

    if (!$validResponse && empty($_POST['update_manual_transfer'])) {
        $response = ['status' => 'error', 'message' => 'Data validation failed: checksum mismatch'];
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => 'EXCEPTION: ' . $e->getMessage()];
}

/**
 * Get status description based on status code
 *
 * @param string $statusCode The status code
 * @return string Status description
 */
function getStatusDescription($statusCode) {
    $statusMap = [
        '1' => 'Pending',
        '2' => 'Failed',
        '3' => 'Success',
        '4' => 'Cancelled',
        '5' => 'Expired'
    ];

    return $statusMap[$statusCode] ?? 'Unknown';
}

function getManualTransferStatusOptions() {
    return [
        '1' => 'Pending',
        '2' => 'Failed',
        '3' => 'Success',
        '4' => 'Cancelled',
        '5' => 'Expired'
    ];
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

    <div class="card shadow">
        <div class="card-header">
            Payment Callback Response
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

            <div class="alert alert-info">
                <strong>System Status:</strong> <?php echo ($response['status'] === 'success') ? 'Data validation successful' : $response['message']; ?>
            </div>

            <?php if (!empty($updateMessage)): ?>
                <div class="alert <?php echo $updateSuccess ? 'alert-success' : 'alert-danger'; ?>">
                    <strong><?php echo $updateSuccess ? 'Success:' : 'Error:'; ?></strong> <?php echo $updateMessage; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($callbackData['transaction_status']) || isset($callbackData['status'])): ?>
                <h5 class="font-weight-bold mb-3">
                    Payment Status
                </h5>
                <div class="alert <?php echo (($callbackData['transaction_status'] ?? $callbackData['status'] ?? '') === '3') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php
                    $statusCode = $callbackData['transaction_status'] ?? $callbackData['status'] ?? '';
                    if (!empty($_GET)) {
                        $statusName = $returnUrlModel->get_payment_status_name($statusCode);
                    } else {
                        $statusName = isset($transaction) ? $transaction->get_payment_status_name($statusCode) : getStatusDescription($statusCode);
                    }
                    $statusDescription = $callbackData['transaction_status_description'] ?? $callbackData['status_description'] ?? 'No status description available';
                    echo $statusName . ": " . $statusDescription;
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($callbackData['transaction_channel']) && $callbackData['transaction_channel'] === 'ManualBankTransfer' && isset($callbackData['order_ref_no'])): ?>
                <hr class="mt-4 mb-4">

                <div class="manual-transfer-update">
                    <h5 class="font-weight-bold mb-3">
                        Update Manual Transfer Status
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
                    Callback Data
                </h5>
                <pre><?php echo json_encode($callbackData, JSON_PRETTY_PRINT); ?></pre>
            </div>

            <?php if (isset($callbackData['exchange_reference_number'])): ?>
                <p><strong>Exchange Reference Number:</strong> <?php echo $callbackData['exchange_reference_number']; ?></p>
                <p>Please save this exchange reference number for future reference.</p>
            <?php endif; ?>

            <hr class="mt-4 mb-4">

            <div class="navigation-buttons">
                <h5 class="font-weight-bold mb-3">Navigation</h5>
                <div class="d-flex flex-wrap justify-content-between">
                    <a href="<?php echo $config['base_url']; ?>index.php" class="btn btn-primary mb-2 mr-2">
                        <i class="fa fa-home"></i> Main Page
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