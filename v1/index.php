<?php
    require_once '../config-v1.php';

    /**
     * Input required by API.
     * */
    $bayarcash_portal_key = $config['bayarcash_portal_key'];
    $return_url = $config['return_url'];
    $order_no = '12345';
    $order_amount = '1.00';
    $buyer_name = 'Muhammad Ali';
    $buyer_email = 'hai@bayarcash.com';
    $buyer_tel = '60168788787';

    $payment_gateways = [
        1 => 'FPX Online Banking (CASA)',
        4 => 'FPX Line of Credit (Credit Card)',
        5 => "DuitNow Online Banking/Wallets",
    ];

    $environment = $config['environment'];
    $api_url = $config['bayarcash_create_transaction_api_url'][$environment];
    $order_description = 'Bayaran Zakat Harta';
    $payment_form_id = md5($order_no.time()); // Safety features: Generate and assign a dynamic form ID in order to prevent any automation on the client-side.
?>

<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Bayarcash Checkout Example Using v1 API Endpoint</title>
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
                Reference from GitHub repo &#187;
            </a>
        </div>
        <div class="mt-1">
            <a target="_blank" href="https://api.webimpian.support/bayarcash">
                Bayarcash API documentation &#187;
            </a>
        </div>
	</div>
	<div class="card shadow">
		<div class="card-header">
			Transaction Details
		</div>
		<div class="card-body">
			<form id="<?php echo $payment_form_id ?>" method="POST" action="<?php echo $api_url ?>" class="mb-0 pb-0 bayarcash-form">

				<div class="card-text">
					<div class="row">
						<div class="col">

							<!-- ID Number -->
							<div class="form-group mb-3">
								<label class="mb-1" for="order_no">
									<b>ID Number</b>
								</label>
								<div>
									<input type="text" name="order_no" id="order_no" class="form-control" value="<?php echo $order_no ?>" required>
								</div>
							</div>

							<!-- Amount -->
							<div class="form-group mb-3">
								<label class="mb-1" for="order_amount">
									<b>Amount</b>
								</label>
								<div>
									<input type="text" name="order_amount" id="order_amount" class="form-control" value="<?php echo $order_amount ?>" required>
								</div>
							</div>

							<!-- Description -->
							<div class="form-group mb-3">
								<label class="mb-1" for="order_description">
									<b>Description</b>
								</label>
								<div>
									<input type="text" name="order_description" id="order_description" class="form-control" value="<?php echo $order_description ?>" required>
								</div>
							</div>

							<!-- Name -->
							<div class="form-group mb-3">
								<label class="mb-1" for="buyer_name">
									<b>Name</b>
								</label>
								<div>
									<input type="text" name="buyer_name" id="buyer_name" class="form-control" value="<?php echo $buyer_name ?>" required>
								</div>
							</div>

							<!-- Email -->
							<div class="form-group mb-3">
								<label class="mb-1" for="buyer_email">
									<b>Email</b>
								</label>
								<div>
									<input type="text" name="buyer_email" id="buyer_email" class="form-control" value="<?php echo $buyer_email ?>" required>
								</div>
							</div>

							<!-- Telephone -->
							<div class="form-group mb-3">
								<label class="mb-1" for="buyer_tel">
									<b>Telephone</b>
								</label>
								<div>
									<input type="text" name="buyer_tel" id="buyer_tel" class="form-control" value="<?php echo $buyer_tel ?>" required>
								</div>
							</div>
						</div>
					</div>
				</div>

				<input type="hidden" name="payment_gateway" id="payment_gateway" readonly="true" value="1"/> <!-- default to FPX -->
				<input type="hidden" name="return_url" readonly="true" value="<?php echo $return_url ?>"/>
				<input type="hidden" name="api_url" readonly="true" value="<?php echo $api_url ?>"/>
				<input type="hidden" name="portal_key" readonly="true" value="<?php echo $bayarcash_portal_key ?>"/>

                <hr>

				<!-- Submit -->
				<!--  Display list of payment channel buttons -->
				<div class="row mt-4">
					<?php foreach($payment_gateways as $id => $label) : ?>
						<div class="col-12 button mb-2">
							<button type="submit" class="btn btn-success btn-block mr-1 h-100 p-2" onclick="$('#payment_gateway').val(<?php echo $id; ?>);">
								<?php echo $label; ?>
                                <i class="fa-duotone fa-solid fa-arrow-right ml-2"></i>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Footer -->
<script type="text/javascript" src="js/jquery-3.2.0.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
</body>
</html>
