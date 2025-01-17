<?php
//error_reporting(E_ALL & ~E_DEPRECATED);

global $config;
require_once '../config-v2.php';
require_once 'vendor/autoload.php';

use Webimpian\BayarcashSdk\Bayarcash;
use Webimpian\BayarcashSdk\Exceptions\ValidationException;

// Get the current configuration based on the environment
$current_config = getConfig($config, $config['environment']);

// Initialize Bayarcash SDK
$bayarcash = new Bayarcash($current_config['bayarcash_bearer_token']);

// Set API version
$api_version = 'v3';
$bayarcash->setApiVersion($api_version);

// Use sandbox environment if configured
if ($config['environment'] === 'sandbox') {
    $bayarcash->useSandbox();
}

// Initialize variables
$transaction = null;
$error_message = '';

// Process transaction lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lookup_type = $_POST['lookup_type'] ?? 'transaction_id';
    $lookup_value = trim($_POST[$lookup_type] ?? '');

    try {
        if ($lookup_type === 'transaction_id' && !empty($lookup_value)) {
            // Retrieve transaction details by ID
            $transaction = $bayarcash->getTransaction($lookup_value);
        } elseif ($lookup_type === 'order_number' && !empty($lookup_value)) {
            // Use SDK method to get transaction by order number
            $transaction = $bayarcash->getTransactionByOrderNumber($lookup_value);
        }
    } catch (ValidationException $exception) {
        $exceptionData = $exception->errors();
        if (is_array($exceptionData['message'])) {
            $error_message = implode(' ', $exceptionData['message']);
        } else {
            $error_message = (string)$exceptionData['message'];
        }
    } catch (Exception $exception) {
        $error_message = 'An unexpected error occurred: ' . $exception->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.5;
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            transition: all 0.3s ease;
        }
        .radio-option input[type="radio"] {
            display: none; /* Hide the default radio button */
        }
        .radio-custom {
            width: 18px;
            height: 18px;
            border: 2px solid #007bff;
            border-radius: 50%;
            margin-right: 8px;
            position: relative;
            transition: all 0.3s ease;
        }
        .radio-custom::after {
            content: '';
            width: 10px;
            height: 10px;
            background: #007bff;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: all 0.3s ease;
        }
        .radio-option input[type="radio"]:checked + .radio-custom::after {
            transform: translate(-50%, -50%) scale(1);
        }
        .radio-option:hover .radio-custom {
            border-color: #0056b3;
        }
        .radio-option input[type="radio"]:checked + .radio-custom {
            border-color: #0056b3;
        }
        .radio-option:hover .radio-label {
            color: #0056b3;
        }
        .radio-label {
            transition: color 0.3s ease;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .transaction-result {
            margin-top: 20px;
        }
        .transaction-result h3 {
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Transaction Details</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Lookup Type</label>
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="lookup_type" id="type_transaction_id" value="transaction_id"
                        <?php echo (!isset($_POST['lookup_type']) || $_POST['lookup_type'] === 'transaction_id') ? 'checked' : ''; ?>>
                    <span class="radio-custom"></span>
                    <span class="radio-label">Transaction ID</span>
                </label>
                <label class="radio-option">
                    <input type="radio" name="lookup_type" id="type_order_number" value="order_number"
                        <?php echo (isset($_POST['lookup_type']) && $_POST['lookup_type'] === 'order_number') ? 'checked' : ''; ?>>
                    <span class="radio-custom"></span>
                    <span class="radio-label">Order Number</span>
                </label>
            </div>
        </div>

        <div class="form-group" id="transaction_id_input">
            <label for="transaction_id">Transaction ID</label>
            <input type="text" id="transaction_id" name="transaction_id"
                   value="<?php echo htmlspecialchars($_POST['transaction_id'] ?? ''); ?>">
        </div>

        <div class="form-group" id="order_number_input" style="display: none;">
            <label for="order_number">Order Number</label>
            <input type="text" id="order_number" name="order_number"
                   value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>">
        </div>

        <button type="submit">Look Up Transaction</button>
    </form>

    <!-- Always display the response section -->
    <div class="transaction-result">
        <h3>Transaction Result:</h3>
        <pre><?php
            if ($transaction !== null) {
                echo htmlspecialchars(json_encode($transaction, JSON_PRETTY_PRINT));
            } else {
                echo "No transaction data found or lookup value is empty.";
            }
            ?></pre>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const transactionIdInput = document.getElementById('transaction_id_input');
        const orderNumberInput = document.getElementById('order_number_input');
        const radioButtons = document.querySelectorAll('input[name="lookup_type"]');

        function updateInputVisibility() {
            const selectedValue = document.querySelector('input[name="lookup_type"]:checked').value;
            if (selectedValue === 'transaction_id') {
                transactionIdInput.style.display = 'block';
                orderNumberInput.style.display = 'none';
            } else {
                transactionIdInput.style.display = 'none';
                orderNumberInput.style.display = 'block';
            }
        }

        radioButtons.forEach(radio => {
            radio.addEventListener('change', updateInputVisibility);
        });

        // Initialize visibility based on current selection
        updateInputVisibility();
    });
</script>
</body>
</html>