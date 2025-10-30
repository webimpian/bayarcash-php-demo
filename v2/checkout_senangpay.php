<?php
global $error_message, $order_no, $order_amount, $order_description, $buyer_name, $buyer_email, $buyer_tel, $payment_gateways, $api_version, $merchant_info, $current_config;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SenangPay Checkout - Bayarcash v2 API</title>
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
        .senangpay-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .senangpay-logo img {
            max-width: 200px;
            height: auto;
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

    <!-- SenangPay Logo -->
    <div class="senangpay-logo">
        <img src="https://senangpay.com/wp-content/uploads/2023/07/WEBSITE-senangPay-DOKU-LOGO-300x86.png" alt="SenangPay">
    </div>

    <!-- Merchant Information Card -->
    <div class="card shadow mb-3" id="merchant-info-card" style="display: none;">
        <div class="card-header bg-success text-white">
            <i class="fas fa-check-circle"></i> Connected Merchant Account
            <?php if ($config['environment'] === 'dev'): ?>
                <button type="button" class="btn btn-sm btn-outline-light float-right ml-1" onclick="fetchMerchantInfo()" title="Refresh merchant info">
                    <i class="fas fa-sync"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-light float-right" id="config-button">
                    <i class="fas fa-cog"></i> Override Config
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
            <i class="fas fa-shopping-cart"></i> Transaction Details
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-0 pb-0 bayarcash-form" id="payment-form">
                <!-- Hidden fields for browser storage overrides -->
                <input type="hidden" name="override_bearer_token" id="override_bearer_token" value="">
                <input type="hidden" name="override_portal_key" id="override_portal_key" value="">
                <input type="hidden" name="payment_method" id="payment_method" value="">

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

                <hr>

                <!-- Payment Gateway Buttons -->
                <div class="payment-buttons" id="payment-buttons">
                    <h6 class="mb-3"><i class="fas fa-credit-card"></i> Select Payment Method</h6>
                    <div class="row mt-3">
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
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4 mb-3">
        <div class="text-muted">
            <span id="api-version-display"><?php echo htmlspecialchars($api_version); ?></span> •
            <span class="badge badge-warning">Development</span>
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

    function submitForm(paymentMethod) {
        document.getElementById('payment_method').value = paymentMethod;
        setHiddenFormFields();
        document.getElementById('loading-overlay').style.display = 'block';
        localStorage.setItem('formSubmitTime', Date.now());
        document.getElementById('payment-form').submit();
    }

    function fetchMerchantInfo() {
        const bearerOverride = localStorage.getItem('bayarcash_bearer_token_override');
        const merchantCard = document.getElementById('merchant-info-card');
        const merchantContent = document.getElementById('merchant-info-content');
        const merchantHeader = merchantCard.querySelector('.card-header');

        merchantHeader.className = 'card-header bg-success text-white';
        merchantHeader.innerHTML = '<i class="fas fa-check-circle"></i> Connected Merchant Account' +
            (typeof openModal === 'function' ?
                '<button type="button" class="btn btn-sm btn-outline-light float-right ml-1" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                '<i class="fas fa-sync"></i>' +
                '</button>' +
                '<button type="button" class="btn btn-sm btn-outline-light float-right" onclick="openModal()">' +
                '<i class="fas fa-cog"></i> Override Config' +
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
                    if (bearerOverride) {
                        merchantContent.innerHTML =
                            '<p class="mb-2"><strong>Merchant Name:</strong> ' + escapeHtml(merchant.name) + '</p>' +
                            '<p class="mb-2"><strong>Email:</strong> ' + escapeHtml(merchant.email) + '</p>' +
                            '<small class="text-info"><i class="fas fa-info-circle"></i> Using browser override bearer token</small>';
                    } else {
                        merchantContent.innerHTML =
                            '<p class="mb-2"><strong>Merchant Name:</strong> <span class="text-muted">Default Account</span></p>' +
                            '<p class="mb-2"><strong>Email:</strong> <span class="text-muted">Using config default</span></p>' +
                            '<small class="text-muted"><i class="fas fa-info-circle"></i> Using config default bearer token</small>';
                    }
                } else {
                    merchantHeader.className = 'card-header bg-warning text-dark';
                    merchantHeader.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Merchant Connection Issue' +
                        (typeof openModal === 'function' ?
                            '<button type="button" class="btn btn-sm btn-outline-dark float-right ml-1" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                            '<i class="fas fa-sync"></i>' +
                            '</button>' +
                            '<button type="button" class="btn btn-sm btn-outline-dark float-right" onclick="openModal()">' +
                            '<i class="fas fa-cog"></i> Override Config' +
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
                    (typeof openModal === 'function' ?
                        '<button type="button" class="btn btn-sm btn-outline-light float-right ml-1" onclick="fetchMerchantInfo()" title="Refresh merchant info">' +
                        '<i class="fas fa-sync"></i>' +
                        '</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-light float-right" onclick="openModal()">' +
                        '<i class="fas fa-cog"></i> Override Config' +
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

        const paymentGatewayBtns = document.querySelectorAll('.payment-gateway-btn');
        paymentGatewayBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const gateway = this.getAttribute('data-gateway');
                submitForm(gateway);
            });
        });

        // Handle config button click using event delegation
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'config-button') {
                e.preventDefault();
                openModal();
            }
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

                    setHiddenFormFields();

                    fetchMerchantInfo();

                    closeModal();

                    showAlert('warning', 'All configuration overrides cleared!');
                }
            });
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
