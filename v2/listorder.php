<?php
$config = require_once '../config-v2.php';
require_once 'vendor/autoload.php';
require_once 'TransactionModel.php';
require_once 'ReturnUrlModel.php';
require_once 'helper.php';

// Initialize both models
$transaction = new TransactionModel($config);
$returnUrlModel = new ReturnUrlModel($config);

// Get all transactions
try {
    $regularTransactions = $transaction->getAllTransactions();
    $returnUrlTransactions = $returnUrlModel->getAllTransactions();
    $activeTab = !empty($returnUrlTransactions) ? 'return-url' : 'regular';
} catch (Exception $e) {
    $error = 'Error fetching transactions: ' . $e->getMessage();
}

function displayValue($value, $default = ''): string {
    return htmlspecialchars($value ?? $default);
}

function formatAmount($amount, $currency): string {
    $currency = $currency ?? '';
    $amount = isset($amount) ? number_format((float)$amount, 2) : '0.00';
    return $currency . ' ' . $amount;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Transactions</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop.css">
    <script src="https://kit.fontawesome.com/fdd718b065.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #22c55e;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            background-color: #f8fafc;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            padding: 1rem;
            white-space: nowrap;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .nav-tabs {
            border-bottom: none;
            padding: 0 1rem;
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-right: 0.5rem;
            transition: all 0.2s;
            background-color: transparent;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-tabs .nav-link:not(.active) .badge-count {
            background: rgba(100, 116, 139, 0.2);
            color: #64748b;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .badge-success {
            background-color: var(--success-color);
            color: white;
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .badge-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .tab-pane.active {
            animation: fadeIn 0.3s ease-out;
        }

        .btn-home {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-home:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="m-0">List Transactions</h2>
        <a href="index.php" class="btn btn-outline-primary btn-home">
            <i class="fas fa-home"></i> Home
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h6 class="text-muted mb-2">Callback Transactions</h6>
            <h3 class="m-0"><?php echo count($regularTransactions); ?></h3>
        </div>
        <div class="stat-card">
            <h6 class="text-muted mb-2">Return URL Transactions</h6>
            <h3 class="m-0"><?php echo count($returnUrlTransactions); ?></h3>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
        <li class="nav-item <?php echo ($activeTab === 'callback' ? 'active' : ''); ?>">
            <a class="nav-link <?php echo ($activeTab === 'callback' ? 'active' : ''); ?>"
               id="callback-tab"
               data-toggle="tab"
               href="#callback"
               role="tab"
               aria-expanded="<?php echo ($activeTab === 'callback' ? 'true' : 'false'); ?>">
                <i class="fas fa-server" aria-hidden="true"></i>
                Callback Transactions
                <?php if (!empty($regularTransactions)): ?>
                    <span class="badge-count"><?php echo count($regularTransactions); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo ($activeTab === 'return-url' ? 'active' : ''); ?>">
            <a class="nav-link <?php echo ($activeTab === 'return-url' ? 'active' : ''); ?>"
               id="return-url-tab"
               data-toggle="tab"
               href="#return-url"
               role="tab">
                <i class="fas fa-reply"></i>
                Return URL Transactions
                <?php if (!empty($returnUrlTransactions)): ?>
                    <span class="badge-count"><?php echo count($returnUrlTransactions); ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="transactionTabsContent">
        <!-- Callback Transactions Tab -->
        <div class="tab-pane <?php echo ($activeTab === 'callback' ? 'show active' : ''); ?>"
             id="callback"
             role="tabpanel">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Record Type</th>
                        <th>Reference Number</th>
                        <th>Order Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payer Name</th>
                        <th>Payer Email</th>
                        <th>Bank</th>
                        <th>Created At</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($regularTransactions)): ?>
                        <?php foreach ($regularTransactions as $t): ?>
                            <tr>
                                <td><?php echo displayValue($t['id']); ?></td>
                                <td><?php echo displayValue($t['transaction_id']); ?></td>
                                <td><?php echo displayValue($t['record_type']); ?></td>
                                <td><?php echo displayValue($t['exchange_reference_number']); ?></td>
                                <td><?php echo displayValue($t['order_number']); ?></td>
                                <td><?php echo formatAmount($t['amount'], $t['currency']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($t['status'] === 'Successful') ? 'success' : (($t['status'] === 'Pending') ? 'warning' : 'danger'); ?>">
                                        <?php echo displayValue($t['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo displayValue($t['payer_name']); ?></td>
                                <td><?php echo displayValue($t['payer_email']); ?></td>
                                <td><?php echo displayValue($t['payer_bank_name']); ?></td>
                                <td><?php echo displayValue($t['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-4">No callback transactions found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return URL Transactions Tab -->
        <div class="tab-pane <?php echo ($activeTab === 'return-url' ? 'show active' : ''); ?>"
             id="return-url"
             role="tabpanel">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Reference Number</th>
                        <th>Exchange Trx ID</th>
                        <th>Order Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Bank</th>
                        <th>Created At</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($returnUrlTransactions)): ?>
                        <?php foreach ($returnUrlTransactions as $t): ?>
                            <tr>
                                <td><?php echo displayValue($t['id']); ?></td>
                                <td><?php echo displayValue($t['transaction_id']); ?></td>
                                <td><?php echo displayValue($t['exchange_reference_number']); ?></td>
                                <td><?php echo displayValue($t['exchange_transaction_id']); ?></td>
                                <td><?php echo displayValue($t['order_number']); ?></td>
                                <td><?php echo formatAmount($t['amount'], $t['currency']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($t['status'] === 'Successful') ? 'success' : (($t['status'] === 'Pending') ? 'warning' : 'danger'); ?>">
                                        <?php echo displayValue($t['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo displayValue($t['payer_bank_name']); ?></td>
                                <td><?php echo displayValue($t['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">No return URL transactions found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
</div>

<!-- JS -->
<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Activate Bootstrap tabs
        $('#transactionTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        // Show active tab on page load
        var activeTab = '<?php echo $activeTab; ?>';
        $('#transactionTabs a[href="#' + activeTab + '"]').tab('show');

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Handle tab change animation
        $('.nav-tabs a').on('shown.bs.tab', function(e) {
            $($(e.target).attr('href'))
                .find('.table-container')
                .addClass('show');
        });
    });
</script>
</body>
</html>
