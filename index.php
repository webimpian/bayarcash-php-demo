<?php
require_once('config.php');

/**
 * Input required by API
 * */
$fpx_portal_key = $config['bayarcash_FPX_portal_key'];
$return_url = $config['return_url'];
$order_no = 'ORDER1';
$order_amount = '1.00';
$buyer_name = 'John Doe';
$buyer_email = 'johndoe@example.com';
$buyer_tel = '0168788787';
$payment_gateway = 1;

$api_url = $config['bayarcash_api_url'];
$order_description = 'Pencil';
$payment_form_id = md5($order_no . time()); # Safety features: Generate and assign a dynamic form ID in order to prevent any automation on the client-side.


?>
<html>

<head>
	<title>Bayarcash Checkout Example</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css">
	<style type="text/css">
		.span-error {
			background-color: #f2dede;
			border-color: #eed3d7;
			color: #b94a48;
		}
	</style>
</head>

<body>
<div id="loader" style="display: none;"></div>
<div id="container" class="container">
	<form id="<?php echo $payment_form_id ?>" name="frmPayment" method="POST" action="<?php echo $api_url ?>">
		<div style="background-color: black; padding: 10px 20px 20px;">
			<img src="https://webimpian.com/wp-content/themes/aplikasiniaga/images/logo-webimpian.png" alt="Web Impian Sdn. Bhd." width="200">
		</div>
		<br>

		<span>Order No: </span>
		<fieldset>
			<input type="text" name="order_no" readonly="true" value="<?php echo $order_no ?>"/>
			<span id="error_order_no" style="display: none;" class="span-error">Order No Required</span>
		</fieldset>

		<span>Order Amount: </span>
		<fieldset>
			<input type="text" name="order_amount" readonly="true" value="<?php echo $order_amount ?>"/>
			<span id="error_order_amount" style="display: none;" class="span-error">Order Amount Required</span>
		</fieldset>

		<span>Order description: </span>
		<fieldset>
			<input type="text" name="order_description" readonly="true" value="<?php echo $order_description ?> "/>
			<span id="error_order_description" style="display: none;" class="span-error">Order Description Required</span>
		</fieldset>


		<span>Buyer name: </span>
		<fieldset>
			<input type="text" name="buyer_name" readonly="true" value="<?php echo $buyer_name ?>"/>
			<span id="error_buyer_name" style="display: none;" class="span-error">Buyer Name Required</span>
		</fieldset>


		<span>Buyer email: </span>
		<fieldset>
			<input type="email" name="buyer_email" readonly="true" value="<?php echo $buyer_email ?>"/>
			<span id="error_buyer_email" style="display: none;" class="span-error">Buyer Email Required</span>
		</fieldset>


		<span>Buyer Telephone Number: </span>
		<fieldset>
			<input type="text" name="buyer_tel" readonly="true" value="<?php echo $buyer_tel ?>"/>
			<span id="error_buyer_tel" style="display: none;" class="span-error">Buyer Telephone Number Required</span>
		</fieldset>

		<input type="hidden" name="payment_gateway" readonly="true" value="<?php echo $payment_gateway ?>"/>
		<input type="hidden" name="return_url" readonly="true" value="<?php echo $return_url ?>"/>
		<input type="hidden" name="api_url" readonly="true" value="<?php echo $api_url ?>"/>
		<input type="hidden" name="portal_key" readonly="true" value="<?php echo $fpx_portal_key ?>"/>

		<button id="checkoutButton" class="button-success pure-button button-large" type="button">Checkout</button>
		<input type="submit" id="submitButton" style="display: none;"/>

	</form>

</div>

<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<script type
"text/javascript">
window.onload=function() {
jQuery("#checkoutButton").on('click', function() {
jQuery('form[name="frmPayment"]').submit();
})
}
</script>
<
/body>
< /html>
