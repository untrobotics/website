<?php
/**
 * PayPal Orders v2 — create an order (modern JS SDK Smart Buttons flow).
 *
 * Mirrors api/stripe/create-checkout-session.php: the price is ALWAYS recomputed
 * server-side (dues from dues_config, merch from Printful) — the client only
 * sends which options it wants, never an amount. We create a CAPTURE order, then
 * persist the server-computed context (custom, option_pairs, expected amount,
 * sandbox flag) in pending_payments keyed by the PayPal order id so capture.php
 * never has to trust anything from the client. Returns JSON {id} for paypal.Buttons.
 *
 * Sandbox vs live is chosen by $untrobotics->get_sandbox(), consistent with the
 * old PaymentButton code and with the client-id the page loaded the SDK with.
 */

require('../../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
require_once(__DIR__ . '/paypal_api.php');

header('Content-Type: application/json');

function fail($http, $message) {
	header($http);
	echo json_encode(array('error' => $message));
	die();
}

// auth() populates $userinfo and flips $untrobotics into sandbox mode for sandbox
// users; harmless for guests (merch). Must run before reading get_sandbox().
$auth = auth();
$sandbox = $untrobotics->get_sandbox();

$source = isset($_REQUEST['source']) ? $_REQUEST['source'] : '';

try {
	if ($source === 'dues') {
		// Dues require a logged-in member (merch checkout stays guest-friendly).
		if (!$auth) {
			fail('HTTP/1.1 401 Unauthorized', 'You must be logged in to pay dues.');
		}
		$userinfo = $auth[0];

		// ---- DUES: recompute amount/terms/custom server-side (mirror Stripe) ----
		$tshirt = !empty($_REQUEST['t-shirt']) ? $_REQUEST['t-shirt'] : false;
		$fullyear = filter_var(@$_REQUEST['full-year'], FILTER_VALIDATE_BOOLEAN);

		$q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
		if (!$q || $q->num_rows !== 2) {
			AdminBot::send_message("Unable to determine the dues payment price (paypal)");
			throw new RuntimeException("Unable to determine dues payment price");
		}
		$r = $q->fetch_all(MYSQLI_ASSOC);
		$mapped_config = array();
		array_walk($r, function (&$val, $_key) use (&$mapped_config) {
			$mapped_config[$val['key']] = $val['value'];
		});

		$t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
		$single_semester_dues_price = $mapped_config['semester_price'];
		$full_year_dues_price = $single_semester_dues_price * 2;
		$current_term = $untrobotics->get_current_term();
		$next_term = $untrobotics->get_next_term();

		// only allow paying for the full year during the autumn semester
		$permit_full_year_payment = $current_term == Semester::AUTUMN;
		if (!$permit_full_year_payment && $fullyear) {
			AdminBot::send_message("Someone is trying to pay for the full year (dues, paypal) in the Spring semester");
			throw new RuntimeException("Unable to pay for full year at this time");
		}

		$custom = array(
			'source' => 'DUES_PAYMENT',
			'uid' => $userinfo['id'],
			'include-tshirt' => $tshirt
		);

		$cost = $fullyear ? $full_year_dues_price : $single_semester_dues_price;
		if ($tshirt) {
			$cost += $t_shirt_dues_purchase_price;
		}
		$n_semesters = $fullyear ? 2 : 1;

		// Option pairs encode the semester/year exactly like the PayPal button so
		// the DUES handler reads them unchanged ([0]=Semester, [1]=Year, ...).
		$option_pairs = array(
			array('Semester', Semester::get_name_from_value($current_term)),
			array('Year', (string) $untrobotics->get_current_year())
		);
		if ($fullyear) {
			$option_pairs[] = array('Semester1', Semester::get_name_from_value($next_term));
			$option_pairs[] = array('Year1', (string) $untrobotics->get_next_year());
		}

		$item_name = "UNT Robotics Dues (x{$n_semesters})" . ($tshirt ? " + T-shirt" : "");
		$currency = 'USD';
		$quantity = 1;
		// A dues t-shirt is shipped, so collect a shipping address for it.
		$needs_shipping = (bool) $tshirt;

	} else if ($source === 'merch') {
		// ---- MERCH: recompute price from Printful (mirror Stripe) ---------------
		require(BASE . '/api/printful/printful.php');
		require(BASE . '/template/functions/functions.php');

		$external_product_id = isset($_REQUEST['product']) ? $_REQUEST['product'] : '';
		$variant_id = isset($_REQUEST['variant']) ? $_REQUEST['variant'] : '';
		$quantity = isset($_REQUEST['quantity']) ? max(1, intval($_REQUEST['quantity'])) : 1;

		if (empty($external_product_id) || empty($variant_id)) {
			throw new RuntimeException("Missing product or variant.");
		}

		$printfulapi = new PrintfulCustomAPI();
		$product = $printfulapi->get_product('@' . $external_product_id);
		if ($product === null) {
			throw new RuntimeException("Unknown product.");
		}

		$selected_variant = null;
		foreach ($product->get_variants() as $variant) {
			if ($variant->get_id() == $variant_id) {
				$selected_variant = $variant;
				break;
			}
		}
		if ($selected_variant === null) {
			throw new RuntimeException("Unknown variant for this product.");
		}

		$catalog_product = $printfulapi->get_catalog_product($product->get_variants()[0]->get_product()->get_product_id());

		// Price recomputed from Printful — exactly what the PRINTFUL handler will
		// re-validate via get_variant($custom['variant'])->get_price().
		$unit_price = $selected_variant->get_price();
		$currency = $selected_variant->get_currency();
		if (empty($currency)) {
			$currency = $product->get_product_currency();
		}
		if (!is_numeric($unit_price) || $unit_price <= 0) {
			throw new RuntimeException("Unable to determine the product price.");
		}
		$currency = strtoupper($currency);

		$variant_variant_name = preg_replace("@.* - (.+)$@i", "$1", $selected_variant->get_name());

		$custom = array(
			'source' => 'PRINTFUL_PRODUCT',
			'product' => $external_product_id,
			'variant' => $selected_variant->get_id()
		);

		// Type/Product/Variant option pairs — the PRINTFUL handler reads
		// options[0]=Type, options[1]=Product, options[2]=Variant.
		$option_pairs = array(
			array('Type', $catalog_product->get_type_name()),
			array('Product', $product->get_name()),
			array('Variant', $variant_variant_name)
		);

		$item_name = $product->get_name() . ' - ' . $variant_variant_name;
		$cost = $unit_price * $quantity;
		$needs_shipping = true; // physical merch always ships

	} else if ($source === 'donation') {
		// ---- DONATION: variable amount chosen by the donor (nothing to recompute).
		$amount = round((float) (isset($_REQUEST['amount']) ? $_REQUEST['amount'] : 0), 2);
		if ($amount < 1 || $amount > 10000) {
			throw new RuntimeException('Please enter a donation amount between $1 and $10,000.');
		}
		$custom = array('source' => 'DONATION');
		$option_pairs = array();
		$item_name = 'UNT Robotics Donation';
		$currency = 'USD';
		$quantity = 1;
		$cost = $amount;
		$needs_shipping = false;

	} else {
		fail('HTTP/1.1 400 Bad Request', 'Unknown payment source.');
	}

	// ---- Build the v2 Order payload ----------------------------------------
	$unit_amount = number_format($cost / $quantity, 2, '.', '');
	$item_total = number_format($cost, 2, '.', '');

	$order_payload = array(
		'intent' => 'CAPTURE',
		'purchase_units' => array(array(
			'custom_id' => serialize($custom), // belt-and-suspenders; capture trusts the DB row
			'description' => substr($item_name, 0, 127),
			'amount' => array(
				'currency_code' => $currency,
				'value' => $item_total,
				'breakdown' => array(
					'item_total' => array(
						'currency_code' => $currency,
						'value' => $item_total,
					),
				),
			),
			'items' => array(array(
				'name' => substr($item_name, 0, 127),
				'quantity' => (string) $quantity,
				'unit_amount' => array(
					'currency_code' => $currency,
					'value' => $unit_amount,
				),
			)),
		)),
		'application_context' => array(
			'brand_name' => WEBSITE_NAME,
			'user_action' => 'PAY_NOW',
			'shipping_preference' => $needs_shipping ? 'GET_FROM_FILE' : 'NO_SHIPPING',
		),
	);

	$paypal = new PayPalOrdersAPI($sandbox);
	$response = $paypal->create_order($order_payload);

	if (!isset($response['id'])) {
		throw new RuntimeException("PayPal did not return an order id.");
	}
	$order_id = $response['id'];

	// ---- Persist the server-computed context so capture never trusts client ----
	$stmt = $db->query('INSERT INTO pending_payments
		(order_id, source, custom, option_pairs, expected_amount, currency, item_name, quantity, sandbox)
		VALUES (
		"' . $db->real_escape_string($order_id) . '",
		"' . $db->real_escape_string($custom['source']) . '",
		"' . $db->real_escape_string(serialize($custom)) . '",
		"' . $db->real_escape_string(json_encode($option_pairs)) . '",
		"' . $db->real_escape_string($item_total) . '",
		"' . $db->real_escape_string($currency) . '",
		"' . $db->real_escape_string($item_name) . '",
		"' . $db->real_escape_string((string) $quantity) . '",
		"' . ($sandbox ? '1' : '0') . '"
		)');
	if (!$stmt) {
		throw new RuntimeException("Failed to persist pending payment context: {$db->error}");
	}

	echo json_encode(array('id' => $order_id));
} catch (Exception $ex) {
	AdminBot::send_message("(PayPal) Failed to create order ({$source}): " . $ex->getMessage());
	fail('HTTP/1.1 500 Internal Server Error', 'Unable to start checkout. Please try again.');
}
