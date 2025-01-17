<?php
global $error_message, $order_no, $order_amount, $order_description, $buyer_name, $buyer_email, $buyer_tel, $payment_gateways, $api_version;
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
    </div>

    <!-- Alert -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
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

    <!-- Card -->
    <div class="card shadow">
        <div class="card-header">
            Transaction Details
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-0 pb-0 bayarcash-form" id="payment-form">
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

                <div class="row mt-4">
                    <?php foreach($payment_gateways as $id => $label) : ?>
                        <div class="col-12 button mb-2">
                            <button type="submit" name="payment_gateway" value="<?php echo $id; ?>" class="btn btn-success btn-block mr-1 h-100 p-2">
                                <?php echo htmlspecialchars($label); ?>
                                <i class="fa-duotone fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>

    // Hide loading overlay on page load
    window.addEventListener('load', function() {
        document.getElementById('loading-overlay').style.display = 'none';
    });

    // Show loading overlay when form is submitted
    document.getElementById('payment-form').addEventListener('submit', function() {
        document.getElementById('loading-overlay').style.display = 'block';

        // Store the current time in localStorage
        localStorage.setItem('formSubmitTime', Date.now());
    });

    // Check if we're returning from payment site
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {

            // Page is loaded from cache (user pressed back button)
            document.getElementById('loading-overlay').style.display = 'none';
        }

        // Check if form was submitted recently
        var submitTime = localStorage.getItem('formSubmitTime');
        if (submitTime) {
            var currentTime = Date.now();
            var timeDifference = currentTime - submitTime;

            // If less than 5 minutes have passed, hide the loader
            if (timeDifference < 300000) {  // 300000 ms = 5 minutes
                document.getElementById('loading-overlay').style.display = 'none';
            }

            // Clear the stored time
            localStorage.removeItem('formSubmitTime');
        }
    });
</script>
</body>
</html>