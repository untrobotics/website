<?php
/**
 * PayPal Orders v2 — capture an order (modern JS SDK Smart Buttons flow).
 *
 * Receives the order id from paypal.Buttons onApprove, captures it via the v2 API,
 * verifies status=COMPLETED and that the captured amount matches the amount we
 * stored at create time (rejecting any mismatch), then builds an IPNResponse-shaped
 * record from the capture response + the stored option_pairs and hands it to the
 * shared completion adapter (paypal/ipn/process_payment.php) — the SAME code path
 * the PayPal IPN and the Stripe webhook use. Idempotency is enforced via the
 * handled_ipns table keyed on the capture id, so a replay/double-capture is a no-op.
 *
 * Nothing here is trusted from the client except the order id; the amount, custom
 * and option_pairs all come from the pending_payments row written by create.php.
 */

require('../../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
require_once(BASE . '/paypal/ipn/process_payment.php');
require_once(__DIR__ . '/paypal_api.php');

// Run with the same working directory as the PayPal IPN so the handlers'
// relative paths (logs/ipn.log, ../images/...) resolve identically.
chdir(BASE . '/paypal');

header('Content-Type: application/json');

function capture_fail($http, $message) {
	header($http);
	echo json_encode(array('success' => false, 'error' => $message));
	die();
}

// Order id arrives as JSON {order_id} (or form-encoded as a fallback).
$order_id = '';
$raw = @file_get_contents('php://input');
if (!empty($raw)) {
	$body = json_decode($raw, true);
	if (is_array($body) && !empty($body['order_id'])) {
		$order_id = $body['order_id'];
	}
}
if (empty($order_id) && !empty($_REQUEST['order_id'])) {
	$order_id = $_REQUEST['order_id'];
}
if (empty($order_id)) {
	capture_fail('HTTP/1.1 400 Bad Request', 'Missing order id.');
}

// Load the server-computed context for this order.
$q = $db->query('SELECT * FROM pending_payments WHERE order_id = "' . $db->real_escape_string($order_id) . '" LIMIT 1');
if (!$q || $q->num_rows !== 1) {
	payment_log("[{$order_id}] PayPal capture: no pending_payments context found");
	capture_fail('HTTP/1.1 404 Not Found', 'Unknown or expired order.');
}
$context = $q->fetch_array(MYSQLI_ASSOC);

// Already captured/fulfilled — no-op (handled_ipns also guards the capture id).
if ((int) $context['captured'] === 1) {
	echo json_encode(array('success' => true, 'already' => true));
	die();
}

$sandbox = ((int) $context['sandbox'] === 1);
$expected_amount = number_format((float) $context['expected_amount'], 2, '.', '');
$expected_currency = strtoupper($context['currency']);
$custom_obj = @unserialize($context['custom']);
$option_pairs = json_decode($context['option_pairs'], true);
if (!is_array($option_pairs)) {
	$option_pairs = array();
}
if (!is_array($custom_obj)) {
	capture_fail('HTTP/1.1 500 Internal Server Error', 'Corrupt order context.');
}

try {
	$paypal = new PayPalOrdersAPI($sandbox);
	$response = $paypal->capture_order($order_id);

	$status = isset($response['status']) ? $response['status'] : '';
	if ($status !== 'COMPLETED') {
		payment_log("[{$order_id}] PayPal capture returned non-COMPLETED status: {$status}");
		AdminBot::send_message("(PayPal) Capture for order {$order_id} returned status {$status}; not fulfilling.");
		capture_fail('HTTP/1.1 402 Payment Required', 'Payment was not completed.');
	}

	$pu = isset($response['purchase_units'][0]) ? $response['purchase_units'][0] : array();
	$capture = isset($pu['payments']['captures'][0]) ? $pu['payments']['captures'][0] : null;
	if ($capture === null || empty($capture['id'])) {
		throw new IPNHandlerException("[{$order_id}]: Capture response missing capture object.");
	}

	$capture_id = $capture['id'];
	$amount_value = isset($capture['amount']['value']) ? $capture['amount']['value'] : '';
	$amount_currency = isset($capture['amount']['currency_code']) ? strtoupper($capture['amount']['currency_code']) : '';

	// ---- Verify the captured amount matches what we computed at create time ----
	$captured_amount = number_format((float) $amount_value, 2, '.', '');
	if ($captured_amount !== $expected_amount || ($expected_currency !== '' && $amount_currency !== $expected_currency)) {
		AdminBot::send_message("(PayPal) Amount mismatch on capture {$capture_id} (order {$order_id}): captured {$captured_amount} {$amount_currency} vs expected {$expected_amount} {$expected_currency}. NOT fulfilling.");
		payment_log("[{$order_id}] PayPal capture amount mismatch: captured {$captured_amount} {$amount_currency} vs expected {$expected_amount} {$expected_currency}");
		capture_fail('HTTP/1.1 400 Bad Request', 'Payment amount mismatch.');
	}

	// Mark the row captured up-front so a concurrent/duplicate onApprove can't
	// re-run the handler (handled_ipns on the capture id is the second guard).
	$db->query('UPDATE pending_payments SET captured = 1 WHERE order_id = "' . $db->real_escape_string($order_id) . '"');

	// ---- Fee --------------------------------------------------------------
	$mc_fee = '0.00';
	if (isset($capture['seller_receivable_breakdown']['paypal_fee']['value'])) {
		$mc_fee = number_format((float) $capture['seller_receivable_breakdown']['paypal_fee']['value'], 2, '.', '');
	}

	// ---- Buyer ------------------------------------------------------------
	$payer = isset($response['payer']) ? $response['payer'] : array();
	$email = isset($payer['email_address']) ? $payer['email_address'] : '';
	$first_name = isset($payer['name']['given_name']) ? $payer['name']['given_name'] : '';
	$last_name = isset($payer['name']['surname']) ? $payer['name']['surname'] : '';

	$fields = array(
		'mc_gross' => $captured_amount,
		'payment_gross' => $captured_amount,
		'mc_fee' => $mc_fee,
		'payment_fee' => $mc_fee,
		'mc_currency' => $amount_currency !== '' ? $amount_currency : $expected_currency,
		'txn_id' => $capture_id,
		'payment_date' => isset($capture['create_time']) ? $capture['create_time'] : date('c'),
		'payment_status' => 'Completed',
		'payer_email' => $email,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'item_name' => $context['item_name'],
		'quantity' => (string) $context['quantity'],
		'custom' => $context['custom'],
	);

	// ---- Shipping address (when collected) --------------------------------
	$shipping = isset($pu['shipping']) ? $pu['shipping'] : null;
	if ($shipping && isset($shipping['address']) && is_array($shipping['address'])) {
		$addr = $shipping['address'];
		$street = isset($addr['address_line_1']) ? $addr['address_line_1'] : '';
		if (!empty($addr['address_line_2'])) {
			$street .= "\n" . $addr['address_line_2'];
		}
		$full_name = isset($shipping['name']['full_name']) ? $shipping['name']['full_name'] : trim("{$first_name} {$last_name}");
		$fields['address_status'] = 'confirmed';
		$fields['address_name'] = $full_name;
		$fields['address_street'] = $street;
		$fields['address_city'] = isset($addr['admin_area_2']) ? $addr['admin_area_2'] : '';
		$fields['address_state'] = isset($addr['admin_area_1']) ? $addr['admin_area_1'] : '';
		$fields['address_zip'] = isset($addr['postal_code']) ? $addr['postal_code'] : '';
		$fields['address_country_code'] = isset($addr['country_code']) ? $addr['country_code'] : '';
		$fields['residence_country'] = isset($addr['country_code']) ? $addr['country_code'] : '';
	}

	$payment_info = build_ipn_response($fields, $option_pairs);

	payment_log("[{$capture_id}] PayPal order {$order_id} captured (source: {$context['source']}, gross: {$captured_amount} {$amount_currency}, fee: {$mc_fee}, sandbox: " . ($sandbox ? 'true' : 'false') . ")");

	$gateway = new PaymentGatewayContext($sandbox);
	process_normalized_payment($gateway, $payment_info, $custom_obj, $capture_id);

	echo json_encode(array('success' => true, 'capture_id' => $capture_id));
} catch (Exception $ex) {
	// If PayPal already captured, the tx is recorded in handled_ipns before the
	// handler runs, so this won't double-fulfill on retry. Alert the admins and
	// report a soft failure to the SDK (the money may already be captured).
	payment_log("[{$order_id}] ERROR capturing PayPal order: " . $ex);
	AdminBot::send_message("(PayPal) Exception while capturing order {$order_id}: " . $ex->getMessage());
	capture_fail('HTTP/1.1 500 Internal Server Error', 'We could not finalize your payment. Please contact us if you were charged.');
}
