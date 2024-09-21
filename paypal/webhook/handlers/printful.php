<?php
namespace PRINTFUL;
use AdminBot;
use PrintfulCustomAPI;
use PrintfulOrderStatus;
use \stdClass;
require_once(BASE . '/api/printful/printful.php');
require_once(__DIR__ . '/../../../api/paypal/webhook.php');

use WebhookEventHandlerException;

/**
 * @param array $order_info PayPal order info as a JSON-decoded associative array. Use {@see \PayPalCustomApi::get_order_info()} to easily get the order info.
 * @param int $item_index Index in $order_info['purchase_units][0]['items'] of the Printful item being handled
 * @param array $item_info Info about the Printful item ordered. Should be fetched from the paypal_items table. Requires fields 'item_category', 'item_name', 'variant_name', 'external_id'
 * @param string $order_id The PayPal order ID of the order. This is not the ID of the order in the paypal_orders table; this is the ID that PayPal generates
 * @return array Printful order info
 * @throws WebhookEventHandlerException
 * @throws \PrintfulCustomAPIException
 */
function handle_payment_notification(array $order_info, int $item_index, array $item_info, string $order_id, bool $duesDiscount, \PaypalWebhookEvent $event): ?array {
	global $db;

	payment_log("[{$order_id}] Hello from within the PRINTFUL handler");

	$printfulapi = new PrintfulCustomAPI();
    $purchase_unit = $order_info['purchase_units'][0];
    $payment_capture = $purchase_unit['payments']['captures'][0];
	$amount_paid = $payment_capture['seller_receivable_breakdown']['gross_amount']['value'];
	$amount_revenue = $payment_capture['seller_receivable_breakdown']['net_amount']['value'];
    $order_item = $purchase_unit['items'][$item_index];
    $quantity = (int) $order_item['quantity'];
    $payer = $order_info['payer'];
    // product category
    $order_type = $item_info['item_category'];
    // product name
	$order_name = $item_info['item_name'];
	$order_variant_name = $item_info['variant_name'];
	$currency = $payment_capture['seller_receivable_breakdown']['gross_amount']['currency_code'];

	// ensure price is positive (if negative, it's a reversal)
	if ($amount_paid > 0) {
		// get the ID
		$variant_id = $item_info['external_id'];

		// get the price of this variant for x quantity
		$sync_variant = $printfulapi->get_variant($variant_id);
		$price = $sync_variant->get_price() * $quantity;

		// confirm the amount paid matches the the price
		if (is_numeric($price) && $price > 0 && is_numeric($amount_paid) && $amount_paid > 0) {
			if ($price > $amount_paid && !$duesDiscount) {
				throw new WebhookEventHandlerException("[{$order_id}]: The amount paid is incorrect (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
			}
		} else {
			throw new WebhookEventHandlerException("[{$order_id}]: Unable to obtain the correct amount (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
		}
		payment_log("[{$order_id}] Amount paid matches item price (cost: {$price}) (paid: {$amount_paid}) (quantity: {$quantity})");
        $shipping = $purchase_unit['shipping'];
        $paypal_shipping_address = $shipping['address'];
		// validate the shipping address (currently we only do USA shipping for the printful stuff)
		if ($paypal_shipping_address['country_code'] !== 'US') {
			throw new WebhookEventHandlerException("[{$order_id}]: Shipping address is in a country that isn't the US: {$paypal_shipping_address['country_code']}");
		}
		payment_log("[{$order_id}] Successfully validated shipping address");

		// create a draft order -- all other validation passed

		$shipping_address['address1'] = $paypal_shipping_address['address_line_1'];
        if(array_key_exists('address_line_2', $paypal_shipping_address))
		    $shipping_address['address2'] = $paypal_shipping_address['address_line_2'];
        else
            $shipping_address['address2'] = null;
		$shipping_address['city'] = $paypal_shipping_address['admin_area_2'];
		$shipping_address['state_code'] = $paypal_shipping_address['admin_area_1'];
		$shipping_address['country_code'] = $paypal_shipping_address['country_code'];
		$shipping_address['zip'] = $paypal_shipping_address['postal_code'];

        if(array_key_exists('phone_number', $shipping)){
            $shipping_address['phone_number'] = $shipping['phone_number']['country_code'] . $shipping['phone_number']['national_number'];
        }
        elseif(array_key_exists('phone',$payer))
		    $shipping_address['phone'] = $payer['phone']['phone_number']['national_number'];
        else{
            $shipping_address['phone'] = null;
        }
		$shipping_address['email'] = $payer['email_address'];

		/*$order_options = array();
		$order_options[0] = new \stdClass();
		$order_options[0]->txid = $payment_info->txn_id;*/

		$draft_order = $printfulapi->create_order_single($shipping['name']['full_name'], $shipping_address, $amount_paid, $quantity, $variant_id, $order_id, $amount_paid);
        $c = $draft_order->get_costs();
        $cost = $c->get_total();
		payment_log("[{$order_id}] Created draft order (id: {$draft_order->get_id()}) (cost: $cost)");

		// check the draft order cost (because of shipping, tax, etc.) is still below the amount of revenue
		$profit = $amount_revenue - $cost;
		if ($cost >= $amount_revenue && !$duesDiscount) {
            AdminBot::send_message("[{$order_id}]: The price of the fulfillment for this order is higher than the revenue amount (total cost: {$cost}) (revenue: {$amount_revenue}) (shipping: {$c->get_shipping()}) (tax: {$c->get_tax()}");
//			throw new WebhookEventHandlerException("[{$order_id}]: The price of the fulfillment is higher than the revenue amount (cost: {$cost}) (revenue: {$amount_revenue}) (profit: {$profit})");
		}
		payment_log("[{$order_id}] Confirmed fulfillment cost is less than the revenue amount (cost: {$cost}) (revenue: {$amount_revenue}) (profit: {$profit})");

		// create association in the database between tx id and printful order id
		$q = $db->query('INSERT INTO printful_order_tx (txid, printful_order_id) VALUES ("' . $db->real_escape_string($order_id) . '", "' . $db->real_escape_string($draft_order->get_id()) . '")');
		if (!$q) {
			throw new WebhookEventHandlerException("[{$order_id}]: Unable to create tx id to order id associated in the database (error: {$db->error})");
		} else {
			payment_log("[{$order_id}] Created tx id and order id association in the database (order id: {$draft_order->get_id()})");
		}

		$q = $db->query('INSERT INTO printful_order (order_id, first_name, last_name, email_address, order_name, order_variant_name, order_type)
		VALUES
		(
			"' . $db->real_escape_string($draft_order->get_id()) . '",
			"' . $db->real_escape_string($payer['name']['given_name']) . '",
			"' . $db->real_escape_string($payer['name']['surname']) . '",
			"' . $db->real_escape_string($payer['email_address']) . '",
			"' . $db->real_escape_string($order_name) . '",
			"' . $db->real_escape_string($order_variant_name) . '",
			"' . $db->real_escape_string($order_type) . '"
		)');
		$printful_order_db_id = $db->insert_id;
		if (!$q) {
			throw new WebhookEventHandlerException("[{$order_id}]: Unable to create printful order entry in the database (error: {$db->error})");
		} else {
			payment_log("[{$order_id}] Created printful order entry in the database (super secret id: {$printful_order_db_id})");
		}

		// confirm the transaction
		if ($event->is_sandbox() === false) {
			$confirmed_order = $printfulapi->confirm_order($draft_order->get_id());
			// confirm the confirmation of the transaction
			if ($confirmed_order->get_status() !== PrintfulOrderStatus::PENDING) {
				throw new WebhookEventHandlerException("[{$order_id}]: Confirmation of order returned non-PENDING response (status: {$confirmed_order->get_status()})");
			}
			payment_log("[{$order_id}] Order confirmed and sent for fulfillment (status: {$confirmed_order->get_status()})");

			$q = $db->query('UPDATE printful_order SET confirmed = 1 WHERE id = "' . $db->real_escape_string($printful_order_db_id) . '"');
			if (!$q) {
				payment_log("[{$order_id}] Failed to confirm order in the database.");
				AdminBot::send_message("(Webhook) Alert: Printful order table failed to commit confirmation update for [{$order_id}]. (super secret id: {$printful_order_db_id})");
			} else {
				payment_log("[{$order_id}] Order confirmed in database.");
				AdminBot::send_message("(Webhook) Alert: Successfully confirmed printful order [{$order_id}] (profit: {$profit} {$currency}, woohoo!).");
			}
		} else {
			payment_log("[{$order_id}] Not confirming order because this is a sandbox order.");
            AdminBot::send_message("(Webhook) Alert: Not confirming order because this is a sandbox order.");
		}
        return ['product_id'=>$sync_variant->get_external_id(),'shipping'=>$draft_order->get_shipping_service_name(), 'order_name'=>$order_name,'variant_name'=>$order_variant_name];
	} else {
		// Alerting! this is some type of reversal
		payment_log("[{$order_id}] Amount is not positive. Alerting the executives of potential returned item.");
		AdminBot::send_message("(Webhook) Alert: Printful item returned for order [{$order_id}]. Reversal amount: {$amount_paid}.");
        return null;
	}
}