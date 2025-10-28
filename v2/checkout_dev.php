<?php
global $error_message, $order_no, $order_amount, $order_description, $buyer_name, $buyer_email, $buyer_tel, $payment_gateways, $emandate_option, $api_version, $merchant_info, $current_config, $payer_id_type, $payer_id, $frequency_mode, $application_reason;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bayarcash Checkout Example Using v2 API Endpoint</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop.css">
    <script src="https://kit.fontawesome.com/fdd718b065.js" crossorigin="anonymous"></script>
    <style>
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
        }
        .loader-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .spinner {
            width: 60px;
            height: 60px;
            animation: pulse 1.5s ease-in-out infinite, spin 6s linear infinite;
        }
        .spinner-shadow {
            position: absolute;
            width: 60px;
            height: 60px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: shadow-pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes shadow-pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.4; }
        }
        .config-status {
            font-size: 0.85em;
            margin-top: 5px;
        }
        .override-active {
            color: #28a745;
            font-weight: bold;
        }
        .config-default {
            color: #6c757d;
        }
        .modal {
            display: none;
        }
        .modal.show {
            display: block !important;
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: #000;
            opacity: 0.5;
        }
        .payment-type-switcher {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 400px;
            margin: 0 auto;
        }
        .switch-label {
            font-weight: 500;
            font-size: 14px;
        }
        .switch-label.active {
            color: #28a745;
            font-weight: bold;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin: 0 15px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .emandate-fields {
            background: #e8f4f8;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #17a2b8;
        }
        .emandate-fields.hidden {
            display: none;
        }
        .emandate-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        .payment-buttons.hidden {
            display: none;
        }
        .emandate-buttons.hidden {
            display: none;
        }
    </style>
</head>
<body>
<div id="loading-overlay">
    <div class="loader-container">
        <div class="spinner-shadow"></div>
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAAXVBMVEVHcEwjOIQjOYQjOYQjOYQjOYQjOYQjSIcklNYklNYklNYjeb4kkdMklNYklNYklNYjN4Mkl9kjWZ8jOYQjKHkklNYkk9Ukk9UjOYQjOIMjY6gkfcAklNYklNYjOYRzxrQZAAAAH3RSTlMA3mj/hR0yBRk9KQiW/+xn+v//wUHc97Kh8P//ylJTk+NkDgAAANxJREFUeAHV0QWOxDAQBMAJmCHM8P9nmm6iZeFRiQLtNsGfkHk5fFB45T8JEEoZIC6kug+URZC2y7UJbIWBWxmByiArMXCj1ubSGPEcaBtzi1+Brickr4vCBIPgvBqNN2FghkAta5O+BkOsSIEFkn7zgRESxT11f1DMePrFQeU4aHwRWG4bXgW6cD63a7BwL79ZRBZ2gRW7tXbkvraO+yRK9dnXOUxSKRF3uWOFV8egrwjG0UQSgrm4MZkbeKFlgWoK2lwOQLQrgiWuVaaSUSu4weh5kutFHIeEH+MAZaoPYZ1M9b0AAAAASUVORK5CYII=" alt="Loading" class="spinner">
    </div>
</div>
<div id="container" class="container col-4 mt-3 mb-4 container-width">

    <!-- Reference -->
    <div class="mb-3">
        <div>
            <a target="_blank" href="https://github.com/webimpian/bayarcash-php-demo">
                Reference from GitHub repo &#187;
            </a>
        </div>
        <div class="mt-1">
            <a target="_blank" href="https://api.webimpian.support/bayarcash">
                Bayarcash API documentation &#187;
            </a>
        </div>
        <div class="mt-1">
            <strong>API Version:</strong> <span id="api-version-display"><?php echo htmlspecialchars($api_version); ?></span>
        </div>
        <?php if ($config['environment'] === 'dev'): ?>
            <div class="mt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="config-button">
                    <i class="fas fa-cog"></i> Override Config
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Configuration Status (Dev Only) -->
    <?php if ($config['environment'] === 'dev'): ?>
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="config-status">
                    <div>
                        <strong>Bearer Token:</strong>
                        <span id="bearer-status" class="config-default">Using config default</span>
                    </div>
                    <div>
                        <strong>Portal Key:</strong>
                        <span id="portal-status" class="config-default">Using config default</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Merchant Information Card -->
    <div class="card shadow mb-3" id="merchant-info-card" style="display: none;">
        <div class="card-header bg-success text-white">
            <i class="fas fa-check-circle"></i> Connected Merchant Account
            <?php if ($config['environment'] === 'dev'): ?>
                <button type="button" class="btn btn-sm btn-outline-light float-right" onclick="fetchMerchantInfo()" title="Refresh merchant info">
                    <i class="fas fa-sync"></i>
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12" id="merchant-info-content">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading merchant information...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            <?php if (!empty($errors) && count($errors) > 1): ?>
                <hr class="my-2">
                <strong>Details:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif (!empty($errors) && count($errors) === 1): ?>
                <?php
                    // Only show detail if it's different from main message
                    $detail = htmlspecialchars($errors[0]);
                    $main = htmlspecialchars($error_message);
                    if (stripos($main, $detail) === false && stripos($detail, $main) === false):
                ?>
                    <hr class="my-2">
                    <small><?php echo $detail; ?></small>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="card shadow">
        <div class="card-header">
            Transaction Details
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-0 pb-0 bayarcash-form" id="payment-form">
                <!-- Hidden fields for browser storage overrides -->
                <input type="hidden" name="override_bearer_token" id="override_bearer_token" value="">
                <input type="hidden" name="override_portal_key" id="override_portal_key" value="">
                <input type="hidden" name="payment_method" id="payment_method" value="">
                <input type="hidden" name="payment_type" id="hidden_payment_type" value="payment">

                <!-- Payment Type Switcher -->
                <div class="payment-type-switcher">
                    <h6 class="mb-3 text-center"><i class="fas fa-credit-card"></i> Payment Type</h6>
                    <div class="switch-container">
                        <span class="switch-label active" id="payment-label">One-time Payment</span>
                        <label class="switch">
                            <input type="checkbox" id="payment-type-toggle">
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label" id="emandate-label">eMandate Enrollment</span>
                    </div>
                </div>

                <div class="card-text">
                    <div class="row">
                        <div class="col">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="order_no"><b>Order Number</b></label>
                                <input type="text" name="order_no" id="order_no" class="form-control" value="<?php echo htmlspecialchars($order_no); ?>" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="order_amount"><b>Amount</b></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">RM</span>
                                    </div>
                                    <input type="number" name="order_amount" id="order_amount" class="form-control" value="<?php echo htmlspecialchars($order_amount); ?>">
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="order_description"><b>Description</b></label>
                                <input type="text" name="order_description" id="order_description" class="form-control" value="<?php echo htmlspecialchars($order_description); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_name"><b>Name</b></label>
                                <input type="text" name="buyer_name" id="buyer_name" class="form-control" value="<?php echo htmlspecialchars($buyer_name); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_email"><b>Email</b></label>
                                <input type="email" name="buyer_email" id="buyer_email" class="form-control" value="<?php echo htmlspecialchars($buyer_email); ?>">
                            </div>
                            <div class="form-group mb-3">
                                <label class="mb-1" for="buyer_tel"><b>Telephone</b></label>
                                <input type="tel" name="buyer_tel" id="buyer_tel" class="form-control" value="<?php echo htmlspecialchars($buyer_tel); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- eMandate Specific Fields -->
                <div class="emandate-fields hidden" id="emandate-fields">
                    <div class="emandate-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>eMandate Information:</strong> This will set up a recurring direct debit mandate for future automatic payments.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="payer_id_type"><b>ID Type</b></label>
                                <select name="payer_id_type" id="payer_id_type" class="form-control">
                                    <option value="1" selected>MyKad (NRIC)</option>
                                </select>
                                <small class="text-muted">Currently supports MyKad only</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="payer_id"><b>MyKad Number</b></label>
                                <input type="text" name="payer_id" id="payer_id" class="form-control" value="<?php echo htmlspecialchars($payer_id); ?>" maxlength="12" pattern="[0-9]{12}">
                                <small class="text-muted">12-digit MyKad number without dashes</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="frequency_mode"><b>Frequency</b></label>
                                <select name="frequency_mode" id="frequency_mode" class="form-control">
                                    <option value="MT" selected>Monthly</option>
                                    <option value="WK">Weekly</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="mb-1" for="application_reason"><b>Application Reason</b></label>
                                <input type="text" name="application_reason" id="application_reason" class="form-control" value="<?php echo htmlspecialchars($application_reason); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Payment Gateway Buttons -->
                <div class="payment-buttons" id="payment-buttons">
                    <div class="row mt-4">
                        <?php foreach($payment_gateways as $id => $label) : ?>
                            <div class="col-12 button mb-2">
                                <button type="button" class="btn btn-success btn-block mr-1 h-100 p-2 payment-gateway-btn" data-gateway="<?php echo $id; ?>">
                                    <?php echo htmlspecialchars($label); ?>
                                    <i class="fa-duotone fa-solid fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- eMandate Button -->
                <div class="emandate-buttons hidden" id="emandate-buttons">
                    <div class="row mt-4">
                        <div class="col-12 button mb-2">
                            <button type="button" class="btn btn-info btn-block mr-1 h-100 p-2" id="emandate-submit-btn">
                                <i class="fas fa-file-contract"></i>
                                Proceed with eMandate Enrollment
                                <i class="fa-duotone fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($config['environment'] === 'dev'): ?>
    <div class="modal fade" id="configModal" tabindex="-1" role="dialog" aria-labelledby="configModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="configModalLabel">
                        <i class="fas fa-cog"></i> Override Configuration
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> These overrides are stored in your browser's local storage and will only affect this browser session. Leave fields empty to use default config values.
                    </div>

                    <div class="form-group">
                        <label for="override-bearer-input"><strong>Bearer Token Override</strong></label>
                        <textarea class="form-control" id="override-bearer-input" rows="3" placeholder="Enter custom bearer token or leave empty to use config default"></textarea>
                        <small class="form-text text-muted">This will override the bayarcash_bearer_token from config</small>
                    </div>

                    <div class="form-group">
                        <label for="override-portal-input"><strong>Portal Key Override</strong></label>
                        <input type="text" class="form-control" id="override-portal-input" placeholder="Enter custom portal key or leave empty to use config default">
                        <small class="form-text text-muted">This will override the bayarcash_portal_key from config</small>
                    </div>

                    <div class="mt-3">
                        <h6>Current Config Values:</h6>
                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted">
                                    <strong>Bearer Token:</strong> <?php echo htmlspecialchars(substr($current_config['bayarcash_bearer_token'], 0, 50) . '...'); ?><br>
                                    <strong>Portal Key:</strong> <?php echo htmlspecialchars($current_config['bayarcash_portal_key']); ?><br>
                                    <em class="text-info">* Merchant info will refresh automatically when overrides are saved</em>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="clear-overrides">
                        <i class="fas fa-trash"></i> Clear All Overrides
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-overrides">
                        <i class="fas fa-save"></i> Save Overrides
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- JS -->
<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>
    function updateConfigStatus() {
        const bearerStatus = document.getElementById('bearer-status');
        const portalStatus = document.getElementById('portal-status');

        if (!bearerStatus || !portalStatus) return;

        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override');
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override');

        if (bearerOverride && bearerOverride.trim() !== '') {
            bearerStatus.className = 'override-active';
            bearerStatus.textContent = 'Using browser override';
        } else {
            bearerStatus.className = 'config-default';
            bearerStatus.textContent = 'Using config default';
        }

        if (portalOverride && portalOverride.trim() !== '') {
            portalStatus.className = 'override-active';
            portalStatus.textContent = 'Using browser override';
        } else {
            portalStatus.className = 'config-default';
            portalStatus.textContent = 'Using config default';
        }
    }

    function loadOverrideValues() {
        const bearerInput = document.getElementById('override-bearer-input');
        const portalInput = document.getElementById('override-portal-input');

        if (!bearerInput || !portalInput) return;

        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override') || '';
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override') || '';

        bearerInput.value = bearerOverride;
        portalInput.value = portalOverride;
    }

    function setHiddenFormFields() {
        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override') || '';
        const portalOverride = localStorage.getItem('bayarcash_portal_key_override') || '';

        document.getElementById('override_bearer_token').value = bearerOverride;
        document.getElementById('override_portal_key').value = portalOverride;
    }

    function togglePaymentType() {
        const toggle = document.getElementById('payment-type-toggle');
        const paymentLabel = document.getElementById('payment-label');
        const emandateLabel = document.getElementById('emandate-label');
        const hiddenPaymentType = document.getElementById('hidden_payment_type');
        const emandateFields = document.getElementById('emandate-fields');
        const paymentButtons = document.getElementById('payment-buttons');
        const emandateButtons = document.getElementById('emandate-buttons');

        if (toggle.checked) {
            // eMandate mode
            paymentLabel.classList.remove('active');
            emandateLabel.classList.add('active');
            hiddenPaymentType.value = 'emandate';
            emandateFields.classList.remove('hidden');
            paymentButtons.classList.add('hidden');
            emandateButtons.classList.remove('hidden');
        } else {
            // Payment mode
            paymentLabel.classList.add('active');
            emandateLabel.classList.remove('active');
            hiddenPaymentType.value = 'payment';
            emandateFields.classList.add('hidden');
            paymentButtons.classList.remove('hidden');
            emandateButtons.classList.add('hidden');
        }
    }

    function submitForm(paymentMethod) {
        document.getElementById('payment_method').value = paymentMethod;
        setHiddenFormFields();
        document.getElementById('loading-overlay').style.display = 'block';
        localStorage.setItem('formSubmitTime', Date.now());
        document.getElementById('payment-form').submit();
    }

    function generateRandomMyKad() {
        const year = String(Math.floor(Math.random() * 30) + 70).padStart(2, '0');
        const month = String(Math.floor(Math.random() * 12) + 1).padStart(2, '0');
        const day = String(Math.floor(Math.random() * 28) + 1).padStart(2, '0');

        const placeCodes = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16'];
        const placeCode = placeCodes[Math.floor(Math.random() * placeCodes.length)];

        const sequential = String(Math.floor(Math.random() * 999) + 1).padStart(3, '0');
        const gender = Math.floor(Math.random() * 10);

        return year + month + day + placeCode + sequential + gender;
    }

    function fetchMerchantInfo() {
        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override');
        const merchantCard = document.getElementById('merchant-info-card');
        const merchantContent = document.getElementById('merchant-info-content');
        const merchantHeader = merchantCard.querySelector('.card-header');

        merchantHeader.className = 'card-header bg-success text-white';
        merchantHeader.innerHTML = '<i class="fas fa-check-circle"></i> Connected Merchant Account' +
            (document.getElementById('config-button') ?
                '<button type="button" class="btn btn-sm btn-outline-light float-right" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                '<i class="fas fa-sync"></i>' +
                '</button>' : '');

        merchantCard.style.display = 'block';
        merchantContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading merchant information...</div>';

        const requestData = new FormData();
        requestData.append('action', 'fetch_merchant_info');
        if (bearerOverride && bearerOverride.trim() !== '') {
            requestData.append('bearer_token', bearerOverride.trim());
        }

        fetch(window.location.href, {
            method: 'POST',
            body: requestData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.merchant) {
                    var merchant = data.merchant;
                    merchantContent.innerHTML =
                        '<p class="mb-2"><strong>Merchant Name:</strong> ' + escapeHtml(merchant.name) + '</p>' +
                        '<p class="mb-2"><strong>Email:</strong> ' + escapeHtml(merchant.email) + '</p>' +
                        (bearerOverride ? '<small class="text-info"><i class="fas fa-info-circle"></i> Using browser override bearer token</small>' :
                            '<small class="text-muted"><i class="fas fa-info-circle"></i> Using config default bearer token</small>');
                } else {
                    merchantHeader.className = 'card-header bg-warning text-dark';
                    merchantHeader.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Merchant Connection Issue' +
                        (document.getElementById('config-button') ?
                            '<button type="button" class="btn btn-sm btn-outline-dark float-right" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                            '<i class="fas fa-sync"></i>' +
                            '</button>' : '');
                    merchantContent.innerHTML =
                        '<div class="text-warning">' +
                        '<strong>Error:</strong> ' + escapeHtml(data.message || 'Unable to fetch merchant information') + '</div>' +
                        (bearerOverride ? '<small class="text-info mt-2 d-block"><i class="fas fa-info-circle"></i> Using browser override bearer token</small>' :
                            '<small class="text-muted mt-2 d-block"><i class="fas fa-info-circle"></i> Using config default bearer token</small>');
                }
            })
            .catch(error => {
                merchantHeader.className = 'card-header bg-danger text-white';
                merchantHeader.innerHTML = '<i class="fas fa-times-circle"></i> Connection Error' +
                    (document.getElementById('config-button') ?
                        '<button type="button" class="btn btn-sm btn-outline-light float-right" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                        '<i class="fas fa-sync"></i>' +
                        '</button>' : '');
                merchantContent.innerHTML =
                    '<div class="text-danger"><strong>Connection Error:</strong> Unable to reach API</div>';
                console.error('Merchant info fetch error:', error);
            });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check' : 'exclamation-triangle') + '"></i> ' + message +
            '<button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>';

        const container = document.querySelector('.container');
        const firstCard = container.querySelector('.mb-3');
        container.insertBefore(alertDiv, firstCard.nextSibling);

        setTimeout(function() {
            if (alertDiv.parentNode) {
                alertDiv.style.opacity = '0';
                setTimeout(function() {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 300);
            }
        }, 3000);
    }

    function openModal() {
        const modal = document.getElementById('configModal');
        if (!modal) return;

        loadOverrideValues();
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.id = 'modal-backdrop';
        document.body.appendChild(backdrop);
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.style.paddingRight = '17px';
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        const modal = document.getElementById('configModal');
        if (!modal) return;

        const backdrop = document.getElementById('modal-backdrop');
        modal.style.display = 'none';
        modal.classList.remove('show');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
        document.body.style.paddingRight = '';
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        setHiddenFormFields();
        fetchMerchantInfo();

        const paymentTypeToggle = document.getElementById('payment-type-toggle');
        if (paymentTypeToggle) {
            paymentTypeToggle.addEventListener('change', togglePaymentType);
        }

        togglePaymentType();

        const paymentGatewayBtns = document.querySelectorAll('.payment-gateway-btn');
        paymentGatewayBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const gateway = this.getAttribute('data-gateway');
                submitForm(gateway);
            });
        });

        const emandateSubmitBtn = document.getElementById('emandate-submit-btn');
        if (emandateSubmitBtn) {
            emandateSubmitBtn.addEventListener('click', function() {
                submitForm('emandate');
            });
        }

        // MyKad field validation
        const payerIdField = document.getElementById('payer_id');
        if (payerIdField) {
            payerIdField.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');

                if (this.value.length > 12) {
                    this.value = this.value.substring(0, 12);
                }
            });

            payerIdField.addEventListener('dblclick', function() {
                this.value = generateRandomMyKad();
                showAlert('info', 'Random MyKad number generated!');
            });

            payerIdField.setAttribute('title', 'Double-click to generate random MyKad number');
        }

        const configButton = document.getElementById('config-button');
        if (configButton) {
            updateConfigStatus();

            configButton.addEventListener('click', function(e) {
                e.preventDefault();
                openModal();
            });

            const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'modal-backdrop') {
                    closeModal();
                }
            });

            const saveButton = document.getElementById('save-overrides');
            if (saveButton) {
                saveButton.addEventListener('click', function() {
                    const bearerInput = document.getElementById('override-bearer-input');
                    const portalInput = document.getElementById('override-portal-input');

                    if (!bearerInput || !portalInput) return;

                    const bearerValue = bearerInput.value.trim();
                    const portalValue = portalInput.value.trim();

                    if (bearerValue) {
                        localStorage.setItem('bayarcash_bearer_token_override', bearerValue);
                    } else {
                        localStorage.removeItem('bayarcash_bearer_token_override');
                    }

                    if (portalValue) {
                        localStorage.setItem('bayarcash_portal_key_override', portalValue);
                    } else {
                        localStorage.removeItem('bayarcash_portal_key_override');
                    }

                    updateConfigStatus();
                    setHiddenFormFields();

                    fetchMerchantInfo();

                    closeModal();

                    showAlert('success', 'Configuration overrides saved successfully!');
                });
            }

            const clearButton = document.getElementById('clear-overrides');
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    if (confirm('Are you sure you want to clear all configuration overrides?')) {
                        localStorage.removeItem('bayarcash_bearer_token_override');
                        localStorage.removeItem('bayarcash_portal_key_override');

                        const bearerInput = document.getElementById('override-bearer-input');
                        const portalInput = document.getElementById('override-portal-input');

                        if (bearerInput) bearerInput.value = '';
                        if (portalInput) portalInput.value = '';

                        updateConfigStatus();
                        setHiddenFormFields();

                        fetchMerchantInfo();

                        closeModal();

                        showAlert('warning', 'All configuration overrides cleared!');
                    }
                });
            }
        }
    });

    window.addEventListener('load', function() {
        document.getElementById('loading-overlay').style.display = 'none';
    });

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        const submitTime = localStorage.getItem('formSubmitTime');
        if (submitTime) {
            const currentTime = Date.now();
            const timeDifference = currentTime - submitTime;

            if (timeDifference < 300000) {
                document.getElementById('loading-overlay').style.display = 'none';
            }

            localStorage.removeItem('formSubmitTime');
        }
    });
</script>
</body>
</html>