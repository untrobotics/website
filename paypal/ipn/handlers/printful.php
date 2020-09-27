<?php
require_once(BASE . '/api/printful/printful.php');

function handle_payment_notification($ipn, $payment_info, $custom) {
	global $db;
	
	payment_log("[{$payment_info->txn_id}] Hello from within the PRINTFUL handler");
	
	$printfulapi = new PrintfulCustomAPI();
	
	$amount_paid = $payment_info->mc_gross;
	$amount_revenue = $payment_info->mc_gross - $payment_info->mc_fee;
	$quantity = $payment_info->quantity;
	$order_type = $payment_info->options[0][1];
	$order_name = $payment_info->options[1][1];
	$order_variant_name = $payment_info->options[2][1];
	
	// ensure price is positive (if negative, it's a reversal)
	if ($amount_paid > 0) {
		// get the ID
		$variant_id = $custom['variant'];
		
		// get the price of this variant for x quantity
		$sync_variant = $printfulapi->get_variant($variant_id);
		$price = $sync_variant->get_price() * $quantity;
		
		// confirm the amount paid matches the the price
		if (is_numeric($price) && $price > 0 && is_numeric($amount_paid) && $amount_paid > 0) {
			if ($price != $amount_paid) {
				throw new IPNHandlerException("[{$payment_info->txn_id}]: The amount paid is incorrect (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
			}
		} else {
			throw new IPNHandlerException("[{$payment_info->txn_id}]: Unable to obtain the correct amount (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
		}
		payment_log("[{$payment_info->txn_id}] Amount paid matches item price (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
		
		// validate the shipping address (currently we only do USA shipping for the printful stuff)
		if ($code = $payment_info->validate_shipping_address('US') !== 0) {
			throw new IPNHandlerException("[{$payment_info->txn_id}]: Shipping address validation failed (code: {$code})");
		}
		payment_log("[{$payment_info->txn_id}] Successfully validated shipping address");
		
		// create a draft order -- all other validation passed
		
		$shipping_address['address1'] = $payment_info->address_street;
		$shipping_address['address2'] = null; // paypal passes the address all on one line
		$shipping_address['city'] = $payment_info->address_city;
		$shipping_address['state_code'] = $payment_info->address_state;
		$shipping_address['country_code'] = $payment_info->address_country_code;
		$shipping_address['zip'] = $payment_info->address_zip;
		$shipping_address['phone'] = $payment_info->contact_phone;
		$shipping_address['email'] = $payment_info->payer_email;
		
		$order_options = array();
		$order_options[0] = new stdClass();
		$order_options[0]->txid = $payment_info->txn_id;
		
		$draft_order = $printfulapi->create_order_single($payment_info->address_name, $shipping_address, $amount_paid, $payment_info->quantity, $variant_id, $payment_info->txn_id, $amount_paid);
		$cost = $draft_order->get_costs()->get_total();
		payment_log("[{$payment_info->txn_id}] Created draft order (id: {$draft_order->get_id()}) (cost: $cost)");
		
		// check the draft order cost (because of shipping, tax, etc.) is still below the amount of revenue
		$profit = $amount_revenue - $cost;
		if ($cost >= $amount_revenue) {
			throw new IPNHandlerException("[{$payment_info->txn_id}]: The price of the fulfillment is higher than the revenue amount (cost: {$cost}) (revenue: {$amount_revenue}) (profit: {$profit})");
		}
		payment_log("[{$payment_info->txn_id}] Confirmed fulfillment cost is less than the revenue amount (cost: {$cost}) (revenue: {$amount_revenue}) (profit: {$profit})");
		
		$email_send_status = email(
			$payment_info->payer_email,
			"Receipt for your purchase of " . $payment_info->options[1][1],
			
			"<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
			'<img src="cid:untrobotics-email-header">' .
			
			'	<div></div>' .
			
			'<div style="text-align: left; max-width: 500px; display: inline-block;">' .
			"	<p>Dear " . $payment_info->first_name . ' ' . $payment_info->last_name . ",</p>" .
			"	<p>Thank you for your purchase of <strong>{$payment_info->options[1][1]} - {$payment_info->options[2][1]}</strong> from our store. Please find a receipt for your payment below. A tracking number for your order will be e-mailed to you as soon as it is available.</p>" .
			'</div>' .
			
			'	<div></div>' .
			
			"	<div style=\"display: inline-block;padding: 15px;border: 1px solid #bdbdbd;border-radius: 10px;text-align: left;\">" .
			"		<h5 style=\"font-size: 12pt;margin: 0;font-weight: 600;\">🧾 Payment Receipt</h5>" .
			"		<ul>" .
			"			<li><strong>Order #</strong> {$draft_order->get_id()}</li>" .
			"			<li><strong>PayPal Transaction ID</strong> <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id={$payment_info->txn_id}\">{$payment_info->txn_id}</a></li>" .
			"			<li><strong>Date/Time</strong> " . date('l jS \of F Y h:i:s A T', strtotime($payment_info->payment_date)) . "</li>" .
			"			<li><strong>Payment Amount</strong> \${$amount_paid}</li>" .
			"			<li><strong>Name</strong> {$payment_info->first_name} {$payment_info->last_name}</li>" .
			"			<li><strong>Product ID</strong> {$sync_variant->get_external_id()}</li>" .
			"			<li><strong>Shipping Service</strong> {$draft_order->get_shipping_service_name()}</li>" .
			"		</ul>" .
			"	</div>" .
			"	<p></p>" .
			"	<p>If you need any assistance with your order, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>.</p>" .
			"</div>",
			
			false,
			null,
			[
				[
					'content' => base64_encode(file_get_contents('../images/unt-robotics-email-header.jpg')),
					'type' => 'image/jpeg',
					'filename' => 'unt-robotics-email-header.jpg',
					'disposition' => 'inline',
					'content_id' => 'untrobotics-email-header'
				]
			]
		);
		
		if ($email_send_status) {
			payment_log("[{$payment_info->txn_id}] Successfully sent e-mail receipt (" . var_export($email_send_status, true) . ")");
		} else {
			//throw new IPNHandlerException("[{$payment_info->txn_id}]: Failed to send e-mail receipt (" . var_export($email_send_status, true) . ")");
			AdminBot::send_message("(IPN) Alert: Failed to send e-mail receipt for order #{$draft_order->get_id()} [{$payment_info->txn_id}].");
		}
		
		// create association in the database between tx id and printful order id
		$q = $db->query('INSERT INTO printful_order_tx (txid, printful_order_id) VALUES ("' . $db->real_escape_string($payment_info->txn_id) . '", "' . $db->real_escape_string($draft_order->get_id()) . '")');
		if (!$q) {
			throw new IPNHandlerException("[{$payment_info->txn_id}]: Unable to create tx id to order id associated in the database (error: {$db->error})");
		} else {
			payment_log("[{$payment_info->txn_id}] Created tx id and order id association in the database (order id: {$draft_order->get_id()})");
		}
		
		$q = $db->query('INSERT INTO printful_order (order_id, first_name, last_name, email_address, order_name, order_variant_name, order_type)
		VALUES
		(
			"' . $db->real_escape_string($draft_order->get_id()) . '",
			"' . $db->real_escape_string($payment_info->first_name) . '",
			"' . $db->real_escape_string($payment_info->last_name) . '",
			"' . $db->real_escape_string($payment_info->payer_email) . '",
			"' . $db->real_escape_string($order_name) . '",
			"' . $db->real_escape_string($order_variant_name) . '",
			"' . $db->real_escape_string($order_type) . '"
		)');
		$printful_order_db_id = $db->insert_id;
		if (!$q) {
			throw new IPNHandlerException("[{$payment_info->txn_id}]: Unable to create printful order entry in the database (error: {$db->error})");
		} else {
			payment_log("[{$payment_info->txn_id}] Created printful order entry in the database (super secret id: {$printful_order_db_id})");
		}
		
		// confirm the transaction
		if ($ipn->getSandbox() === false) {
			$confirmed_order = $printfulapi->confirm_order($draft_order->get_id());
			// confirm the confirmation of the transaction
			if ($confirmed_order->get_status() !== PrintfulOrderStatus::PENDING) {
				throw new IPNHandlerException("[{$payment_info->txn_id}]: Confirmation of order returned non-PENDING response (status: {$confirmed_order->get_status()})");
			}
			payment_log("[{$payment_info->txn_id}] Order confirmed and sent for fulfillment (status: {$confirmed_order->get_status()})");
			
			$q = $db->query('UPDATE printful_order SET confirmed = 1 WHERE id = "' . $db->real_escape_string($printful_order_db_id) . '"');
			if (!$q) {
				payment_log("[{$payment_info->txn_id}] Failed to confirm order in the database.");
				AdminBot::send_message("(IPN) Alert: Printful order table failed to commit confirmation update for [{$payment_info->txn_id}]. (super secret id: {$printful_order_db_id})");
			} else {
				payment_log("[{$payment_info->txn_id}] Order confirmed in database.");
				AdminBot::send_message("(IPN) Alert: THIS IS A TEMPORARY NOTIFICATION. Successfully confirmed printful order [{$payment_info->txn_id}] (profit: ${$profit}, woohoo!).");
			}
		} else {
			payment_log("[{$payment_info->txn_id}] Not confirming order because this is a sandbox order.");
		}
	} else {
		// Alerting! this is some type of reversal
		payment_log("[{$payment_info->txn_id}] Amount is not positive. Alerting the executives of potential returned item.");
		AdminBot::send_message("(IPN) Alert: Printful item returned for order [{$payment_info->txn_id}]. Reversal amount: {$amount_paid}.");
	}
}