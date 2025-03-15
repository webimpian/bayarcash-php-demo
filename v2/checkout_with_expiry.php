<?php
global $error_message, $order_no, $order_amount, $order_description, $buyer_name, $buyer_email, $buyer_tel, $payment_gateways, $api_version, $payment_url, $expiry_time;

$default_expiry = 5;
$expiry_minutes = isset($_POST['expiry_minutes']) ? intval($_POST['expiry_minutes']) : $default_expiry;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bayarcash Test - Dynamic Expired Link</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop.css">
    <script src="https://kit.fontawesome.com/fdd718b065.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 650px;
        }

        .card {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f1f3f5;
            border-bottom: 1px solid #dee2e6;
            font-weight: 500;
            padding: 0.8rem 1rem;
        }

        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .form-control {
            border-radius: 6px;
            border-color: #ced4da;
            padding: 0.6rem 0.8rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.15rem rgba(0, 123, 255, 0.1);
        }

        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
        }

        .loader-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .spinner {
            width: 48px;
            height: 48px;
        }

        .countdown-container {
            text-align: center;
            margin-top: 1rem;
        }

        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }

        .expired-alert {
            display: none;
            margin-top: 1rem;
        }

        .expiry-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.3rem;
        }

        .btn-open-payment {
            margin-top: 1rem;
        }

        .back-button {
            margin-bottom: 0.8rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        #reference-section .btn {
            transition: all 0.2s;
            border-radius: 6px;
            font-weight: 500;
        }

        #reference-section .badge {
            font-size: 0.875rem;
            border-radius: 4px;
            padding: 0.4rem 0.6rem;
            background-color: #6c757d;
        }

        .list-group-item {
            border-color: #dee2e6;
            padding: 0.8rem 1rem;
        }
    </style>
</head>
<body>
<div id="loading-overlay">
    <div class="loader-container">
        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAAXVBMVEVHcEwjOIQjOYQjOYQjOYQjOYQjOYQjSIcklNYklNYklNYjeb4kkdMklNYklNYklNYjN4Mkl9kjWZ8jOYQjKHkklNYkk9Ukk9UjOYQjOIMjY6gkfcAklNYklNYjOYRzxrQZAAAAH3RSTlMA3mj/hR0yBRk9KQiW/+xn+v//wUHc97Kh8P//ylJTk+NkDgAAANxJREFUeAHV0QWOxDAQBMAJmCHM8P9nmm6iZeFRiQLtNsGfkHk5fFB45T8JEEoZIC6kug+URZC2y7UJbIWBWxmByiArMXCj1ubSGPEcaBtzi1+Brickr4vCBIPgvBqNN2FghkAta5O+BkOsSIEFkn7zgRESxT11f1DMePrFQeU4aHwRWG4bXgW6cD63a7BwL79ZRBZ2gRW7tXbkvraO+yRK9dnXOUxSKRF3uWOFV8egrwjG0UQSgrm4MZkbeKFlgWoK2lwOQLQrgiWuVaaSUSu4weh5kutFHIeEH+MAZaoPYZ1M9b0AAAAASUVORK5CYII=" alt="Loading" class="spinner">
    </div>
</div>
<div id="container" class="container mt-4 mb-4">
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: #3a5999; margin-bottom: 0.5rem;">Bayarcash Test</h2>
        <p style="color: #6c757d; font-size: 1.1rem; letter-spacing: 0.5px;">Dynamic Expired Link Payment System</p>
        <div class="mx-auto" style="width: 50px; height: 3px; background-color: #4a6da7; margin-top: 10px;"></div>
    </div>

    <div class="mb-4" id="reference-section">
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a target="_blank" href="https://github.com/webimpian/bayarcash-php-demo" class="btn btn-light border px-3 py-2 d-flex align-items-center">
                <i class="fa-brands fa-github mr-2"></i>
                GitHub Repo
            </a>
            <a target="_blank" href="https://api.webimpian.support/bayarcash" class="btn btn-light border px-3 py-2 d-flex align-items-center">
                <i class="fa-solid fa-book-open mr-2"></i>
                API Docs
            </a>
        </div>
        <div class="mt-3 text-center">
            <span class="badge p-2" style="background-color: #4a6da7; color: white;">
                API Version: <span id="api-version-display"><?php echo htmlspecialchars($api_version); ?></span>
            </span>
        </div>
    </div>

    <div id="saved-payment-links-container" class="mb-3"></div>

    <div class="card mb-3" id="dynamic-payment-container" style="display: none;">
        <div class="card-header bg-success text-white">
            Payment Ready
        </div>
        <div class="card-body">
            <button id="back-to-main" class="btn btn-secondary btn-sm back-button">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>
            <h5 class="card-title">Order #<span id="dynamic-order-no"></span></h5>
            <p>Your payment link is ready. Click the button below to continue to payment.</p>

            <a href="#" id="dynamic-payment-url" target="_blank" class="btn btn-primary btn-block btn-open-payment">
                Open Payment Page <i class="fa-solid fa-external-link-alt ml-2"></i>
            </a>

            <div class="countdown-container mt-3" id="dynamic-countdown-container">
                <div class="alert alert-warning mb-0">
                    <p><strong>Payment Link Expires In:</strong></p>
                    <div class="countdown" id="dynamic-countdown">--:--:--</div>
                    <p class="expiry-info">This payment link will expire at <span id="dynamic-expiry-time"></span></p>
                </div>
            </div>

            <div class="alert alert-danger expired-alert mt-3" id="dynamic-expired-alert">
                <strong>Payment Link Expired!</strong>
                <p>The payment link for this transaction has expired. Please create a new payment.</p>
            </div>
        </div>
    </div>

    <?php if ($payment_url): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const orderNo = "<?php echo htmlspecialchars($order_no); ?>";
                const paymentUrl = "<?php echo htmlspecialchars($payment_url); ?>";
                const expiryTime = "<?php echo htmlspecialchars(is_object($expiry_time) ? $expiry_time->format('Y-m-d H:i:s') : $expiry_time); ?>";

                if (orderNo && paymentUrl && expiryTime) {
                    PaymentLinkManager.saveLink(orderNo, paymentUrl, expiryTime);
                    PaymentLinkManager.selectPayment(orderNo);
                    window.open(paymentUrl, '_blank');
                    document.getElementById('payment-form-container').style.display = 'none';
                    document.getElementById('saved-payment-links-container').style.display = 'none';
                }
            });
        </script>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger" id="error-message-container" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <?php if (!empty($errors)): ?>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card" id="payment-form-container">
        <div class="card-header">
            Transaction Details
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-0 pb-0 bayarcash-form" id="payment-form">
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
                <div class="form-group mb-3">
                    <label class="mb-1" for="expiry_minutes"><b>Link Expiry (minutes)</b></label>
                    <input type="number" name="expiry_minutes" id="expiry_minutes" class="form-control" value="<?php echo htmlspecialchars($expiry_minutes); ?>" min="1" max="1440">
                    <small class="form-text text-muted">Set how long the payment link will remain valid (1-1440 minutes)</small>
                </div>

                <hr>

                <div class="row mt-3">
                    <?php foreach($payment_gateways as $id => $label) : ?>
                        <div class="col-12 mb-2">
                            <button type="submit" name="payment_gateway" value="<?php echo $id; ?>" class="btn btn-success btn-block p-2">
                                <?php echo htmlspecialchars($label); ?>
                                <i class="fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>
    const PaymentLinkManager = {
        storageKey: 'bayarcashPaymentLinks',

        saveLink: function(orderNo, paymentUrl, expiryTime) {
            let links = this.getStoredLinks();

            links.push({
                orderNo: orderNo,
                url: paymentUrl,
                expiryTime: expiryTime,
                createdAt: new Date().toISOString()
            });

            links.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

            if (links.length > 10) {
                links = links.slice(0, 10);
            }

            localStorage.setItem(this.storageKey, JSON.stringify(links));
            return links;
        },

        getStoredLinks: function() {
            const storedLinks = localStorage.getItem(this.storageKey);
            return storedLinks ? JSON.parse(storedLinks) : [];
        },

        getLinkByOrderNo: function(orderNo) {
            const links = this.getStoredLinks();
            return links.find(link => link.orderNo === orderNo);
        },

        removeExpiredLinks: function() {
            const links = this.getStoredLinks();
            const now = new Date();

            const validLinks = links.filter(link => {
                return new Date(link.expiryTime) > now;
            });

            localStorage.setItem(this.storageKey, JSON.stringify(validLinks));
            return validLinks;
        },

        renderSavedLinks: function(containerId) {
            const validLinks = this.removeExpiredLinks();
            const container = document.getElementById(containerId);

            if (!container || validLinks.length === 0) return;

            let html = '<div class="card mb-3"><div class="card-header">Recent Payment Links</div><div class="card-body p-0"><ul class="list-group list-group-flush">';

            validLinks.slice(0, 5).forEach(link => {
                const expiryDate = new Date(link.expiryTime);
                const expiryFormatted = expiryDate.toLocaleString('en-MY', {timeZone: 'Asia/Kuala_Lumpur'});
                const now = new Date();
                const isExpired = expiryDate <= now;
                const buttonClass = isExpired ? "btn-secondary disabled" : "btn-primary";
                const statusClass = isExpired ? "text-danger" : "text-success";
                const statusText = isExpired ? "Expired" : "Valid";

                html += `
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Order #${link.orderNo}</strong>
                        <span class="${statusClass}">${statusText}</span>
                    </div>
                    <div class="small text-muted mb-2">Expires: ${expiryFormatted}</div>
                    <div>
                        <button class="btn ${buttonClass} btn-sm btn-block select-payment" data-order="${link.orderNo}">
                            View Payment <i class="fa-solid fa-eye ml-1"></i>
                        </button>
                    </div>
                </li>`;
            });

            html += '</ul></div></div>';
            container.innerHTML = html;

            setTimeout(() => {
                const selectButtons = document.querySelectorAll('.select-payment');
                selectButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        const orderNo = e.currentTarget.getAttribute('data-order');
                        this.selectPayment(orderNo);
                    });
                });
            }, 0);
        },

        selectPayment: function(orderNo) {
            const link = this.getLinkByOrderNo(orderNo);
            if (!link) return;

            document.getElementById('dynamic-order-no').textContent = link.orderNo;
            document.getElementById('dynamic-payment-url').href = link.url;
            document.getElementById('dynamic-expiry-time').textContent = new Date(link.expiryTime).toLocaleString('en-MY', {timeZone: 'Asia/Kuala_Lumpur'});

            document.getElementById('dynamic-payment-container').style.display = 'block';
            document.getElementById('saved-payment-links-container').style.display = 'none';
            document.getElementById('payment-form-container').style.display = 'none';
            document.getElementById('reference-section').style.display = 'none';

            const now = new Date();
            const expiryDate = new Date(link.expiryTime);

            if (expiryDate <= now) {
                document.getElementById('dynamic-expired-alert').style.display = 'block';
                document.getElementById('dynamic-countdown-container').style.display = 'none';
            } else {
                document.getElementById('dynamic-expired-alert').style.display = 'none';
                document.getElementById('dynamic-countdown-container').style.display = 'block';

                CountdownTimer.stop();
                CountdownTimer.start(link.expiryTime, 'dynamic-countdown', function() {
                    document.getElementById('dynamic-expired-alert').style.display = 'block';
                    document.getElementById('dynamic-countdown-container').style.display = 'none';
                });
            }
        }
    };

    const CountdownTimer = {
        timer: null,

        start: function(expiryTime, elementId, expiredCallback) {
            const expiryDate = new Date(expiryTime);
            const countdownElement = document.getElementById(elementId);

            if (elementId === 'countdown') {
                const expiryTimeElement = document.getElementById('expiry-time');
                if (expiryTimeElement && !expiryTimeElement.textContent) {
                    expiryTimeElement.textContent = expiryDate.toLocaleString('en-MY', {timeZone: 'Asia/Kuala_Lumpur'});
                }
            }

            this.timer = setInterval(() => {
                const now = new Date();
                const diff = expiryDate - now;

                if (diff <= 0) {
                    clearInterval(this.timer);
                    countdownElement.textContent = "00:00:00";
                    if (typeof expiredCallback === 'function') {
                        expiredCallback();
                    }
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                const formattedHours = String(hours).padStart(2, '0');
                const formattedMinutes = String(minutes).padStart(2, '0');
                const formattedSeconds = String(seconds).padStart(2, '0');

                countdownElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            }, 1000);
        },

        stop: function() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }
    };

    document.getElementById('payment-form').addEventListener('submit', function() {
        document.getElementById('loading-overlay').style.display = 'block';
    });

    window.addEventListener('load', function() {
        document.getElementById('loading-overlay').style.display = 'none';

        document.getElementById('back-to-main').addEventListener('click', function() {
            document.getElementById('dynamic-payment-container').style.display = 'none';
            document.getElementById('saved-payment-links-container').style.display = 'block';
            document.getElementById('payment-form-container').style.display = 'block';
            document.getElementById('reference-section').style.display = 'block';
            CountdownTimer.stop();
        });

        const validLinks = PaymentLinkManager.removeExpiredLinks();

        if (validLinks.length > 0) {
            PaymentLinkManager.renderSavedLinks('saved-payment-links-container');
        }
    });

    if (document.getElementById('current-payment-container') &&
        getComputedStyle(document.getElementById('current-payment-container')).display !== 'none') {
        document.getElementById('saved-payment-links-container').style.display = 'none';
        document.getElementById('payment-form-container').style.display = 'none';
    }
</script>
</body>
</html>